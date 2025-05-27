#!/usr/bin/env php
<?php

/**
 * Script simplifié de traitement de la queue WhatsApp
 * À exécuter via cron toutes les minutes
 */

require_once __DIR__ . '/../../vendor/autoload.php';

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
    
    // Connexion à la base de données pour vérifier
    $connection = $entityManager->getConnection();
    
    // Compter les messages en attente
    $sql = "SELECT COUNT(*) as count FROM whatsapp_queue WHERE status = 'pending'";
    $result = $connection->executeQuery($sql);
    $count = $result->fetchOne();
    
    logMessage("$count messages en attente dans la queue");
    
    if ($count > 0) {
        logMessage("TODO: Implémenter le traitement des messages");
        // Pour l'instant, on affiche juste le nombre
        // L'implémentation complète viendra après
    }
    
    logMessage("Traitement terminé");
    
} catch (Exception $e) {
    logMessage("ERREUR: " . $e->getMessage(), 'ERROR');
    logMessage("Stack trace: " . $e->getTraceAsString(), 'ERROR');
    exit(1);
}