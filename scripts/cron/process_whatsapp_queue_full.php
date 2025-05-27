#!/usr/bin/env php
<?php

/**
 * Script complet de traitement de la queue WhatsApp
 * À exécuter via cron toutes les minutes
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Services\WhatsAppQueueProcessor;
use App\Entities\WhatsApp\WhatsAppQueue;

// Créer le répertoire de logs s'il n'existe pas
$logDir = __DIR__ . '/../../var/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

// Simple logger
$logFile = $logDir . '/whatsapp_queue.log';
function logMessage($message, $level = 'INFO') {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    echo $logEntry;
}

// Vérifier qu'une seule instance tourne
$lockFile = sys_get_temp_dir() . '/whatsapp_queue_processor.lock';

if (file_exists($lockFile)) {
    $pid = file_get_contents($lockFile);
    // Sur macOS, posix_getsid peut ne pas fonctionner, utilisons ps
    $command = "ps -p $pid > /dev/null 2>&1";
    $result = null;
    system($command, $result);
    if ($result === 0) {
        logMessage("Le processeur de queue est déjà en cours d'exécution (PID: $pid)");
        exit(0);
    }
}

// Créer le fichier de lock
file_put_contents($lockFile, getmypid());

// Enregistrer un handler pour nettoyer le lock
register_shutdown_function(function() use ($lockFile) {
    if (file_exists($lockFile)) {
        unlink($lockFile);
    }
});

try {
    logMessage("Démarrage du traitement de la queue WhatsApp");
    
    // Charger l'EntityManager
    $entityManager = require __DIR__ . '/../../src/bootstrap-doctrine.php';
    
    // Créer le processeur manuellement car nous n'avons pas accès au container ici
    // Nous allons utiliser directement l'EntityManager
    $queueRepository = $entityManager->getRepository(WhatsAppQueue::class);
    
    // Récupérer les messages en attente (max 100 par minute)
    $messages = $queueRepository->createQueryBuilder('q')
        ->where('q.status = :status')
        ->andWhere('q.scheduledAt <= :now')
        ->setParameter('status', 'pending')
        ->setParameter('now', new \DateTime())
        ->orderBy('q.priority', 'DESC')
        ->addOrderBy('q.createdAt', 'ASC')
        ->setMaxResults(100) // Respecter la limite de taux Meta
        ->getQuery()
        ->getResult();
    
    $count = count($messages);
    logMessage("$count messages à traiter");
    
    if ($count > 0) {
        // Pour l'instant, on simule le traitement
        $processed = 0;
        $failed = 0;
        
        foreach ($messages as $message) {
            try {
                // Marquer comme en cours de traitement
                $message->setStatus('processing');
                $entityManager->persist($message);
                $entityManager->flush();
                
                // TODO: Envoyer le message via WhatsApp API
                logMessage("Traitement du message ID: " . $message->getId() . " pour " . $message->getRecipientNumber());
                
                // Simuler un délai pour respecter les limites de taux
                usleep(600000); // 0.6 secondes entre chaque message
                
                // Marquer comme envoyé
                $message->setStatus('sent');
                $message->setSentAt(new \DateTime());
                $entityManager->persist($message);
                $entityManager->flush();
                
                $processed++;
                
            } catch (\Exception $e) {
                logMessage("Erreur lors du traitement du message ID " . $message->getId() . ": " . $e->getMessage(), 'ERROR');
                
                // Marquer comme échoué
                $message->setStatus('failed');
                $message->setErrorMessage($e->getMessage());
                $message->setRetryCount($message->getRetryCount() + 1);
                
                // Si moins de 3 tentatives, reprogrammer
                if ($message->getRetryCount() < 3) {
                    $message->setStatus('pending');
                    $message->setScheduledAt(new \DateTime('+5 minutes'));
                }
                
                $entityManager->persist($message);
                $entityManager->flush();
                
                $failed++;
            }
        }
        
        logMessage("Traitement terminé: $processed réussis, $failed échoués");
    }
    
    // Nettoyer les vieux messages (plus de 30 jours)
    $cleanupDate = new \DateTime('-30 days');
    $deleted = $entityManager->createQueryBuilder()
        ->delete(WhatsAppQueue::class, 'q')
        ->where('q.status IN (:statuses)')
        ->andWhere('q.createdAt < :cleanupDate')
        ->setParameter('statuses', ['sent', 'failed'])
        ->setParameter('cleanupDate', $cleanupDate)
        ->getQuery()
        ->execute();
    
    if ($deleted > 0) {
        logMessage("$deleted anciens messages supprimés de la queue");
    }
    
    logMessage("Traitement terminé");
    
} catch (\Exception $e) {
    logMessage("ERREUR: " . $e->getMessage(), 'ERROR');
    logMessage("Stack trace: " . $e->getTraceAsString(), 'ERROR');
    exit(1);
}