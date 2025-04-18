<?php

/**
 * Script de migration des contacts depuis la base de données legacy vers Doctrine ORM
 * 
 * Ce script récupère tous les contacts de la base de données legacy et les migre
 * vers les entités Doctrine ORM. Il vérifie également l'intégrité des données migrées.
 * 
 * Usage: php scripts/migration/migrate-contacts.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Entities\Contact as DoctrineContact;
use Doctrine\ORM\EntityManagerInterface;

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Initialisation du conteneur DI
$containerDefinitions = require __DIR__ . '/../../src/config/di.php';
$containerBuilder = new \DI\ContainerBuilder();
$containerBuilder->addDefinitions($containerDefinitions);
$container = $containerBuilder->build();

// Récupération des dépendances
$entityManager = $container->get(EntityManagerInterface::class);
$legacyContactRepository = $container->get(\App\Repositories\ContactRepository::class);
$doctrineContactRepository = $container->get(\App\Repositories\Interfaces\ContactRepositoryInterface::class);
$doctrineUserRepository = $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class);

// Configuration du logger
$logger = $container->get(\Psr\Log\LoggerInterface::class);
$logger->info('Début de la migration des contacts');

// Create a log file for detailed debugging
$logFile = __DIR__ . '/../../logs/migrate-contacts-' . date('Y-m-d-H-i-s') . '.log';
file_put_contents($logFile, "=== Migration des contacts démarrée à " . date('Y-m-d H:i:s') . " ===\n");

// Helper function to log to both logger and file
function logMessage($message, $logger, $logFile, $level = 'info')
{
    $logger->$level($message);
    file_put_contents($logFile, date('Y-m-d H:i:s') . " [$level] $message\n", FILE_APPEND);

    // Also output to console for immediate feedback
    echo date('Y-m-d H:i:s') . " [$level] $message\n";
}

logMessage('Connexion à la base de données source et destination établie', $logger, $logFile, 'info');

try {
    // Vérifier que l'utilisateur AfricaQSHE (ID 2) existe dans Doctrine
    logMessage('Vérification de l\'existence de l\'utilisateur AfricaQSHE (ID 2)...', $logger, $logFile, 'info');
    $africaQSHEUser = $doctrineUserRepository->findById(2);

    if (!$africaQSHEUser) {
        logMessage('ERREUR: L\'utilisateur AfricaQSHE (ID 2) n\'existe pas dans la base Doctrine. Migration impossible.', $logger, $logFile, 'error');
        exit(1);
    }

    logMessage('Utilisateur AfricaQSHE (ID 2) trouvé dans la base Doctrine.', $logger, $logFile, 'info');

    // Obtenir le nombre total de contacts à migrer
    logMessage('Comptage du nombre total de contacts à migrer...', $logger, $logFile, 'info');
    $totalContacts = $legacyContactRepository->count();
    logMessage(sprintf('Nombre total de contacts à migrer: %d', $totalContacts), $logger, $logFile, 'info');

    // Vérifier que le nombre de contacts correspond à l'attendu (1602)
    if ($totalContacts != 1602) {
        logMessage(sprintf('ATTENTION: Le nombre de contacts trouvés (%d) ne correspond pas au nombre attendu (1602)', $totalContacts), $logger, $logFile, 'warning');
    }

    // Définir la taille des lots pour le traitement par pagination
    $batchSize = 100;
    $totalBatches = ceil($totalContacts / $batchSize);
    logMessage(sprintf('Traitement par lots de %d contacts (%d lots au total)', $batchSize, $totalBatches), $logger, $logFile, 'info');

    // Compteurs pour les statistiques
    $migratedCount = 0;
    $skippedCount = 0;
    $errorCount = 0;
    $errorDetails = [];

    // Variables pour le suivi du temps
    $startTime = microtime(true);
    $lastUpdateTime = $startTime;

    // Traiter les contacts par lots
    $processedTotal = 0;

    for ($batchNumber = 0; $batchNumber < $totalBatches; $batchNumber++) {
        $offset = $batchNumber * $batchSize;
        $limit = $batchSize;

        logMessage(sprintf(
            'Chargement du lot %d/%d (offset: %d, limit: %d)...',
            $batchNumber + 1,
            $totalBatches,
            $offset,
            $limit
        ), $logger, $logFile, 'info');

        // Récupérer un lot de contacts
        $legacyContacts = $legacyContactRepository->findAll($limit, $offset);
        $batchCount = count($legacyContacts);
        logMessage(sprintf('Lot %d: %d contacts chargés', $batchNumber + 1, $batchCount), $logger, $logFile, 'info');

        // Migration des contacts du lot
        foreach ($legacyContacts as $index => $legacyContact) {
            $processedTotal++;
            try {
                $phoneNumber = $legacyContact->getPhoneNumber();
                logMessage(sprintf('Traitement du contact %d/%d: %s', $index + 1, count($legacyContacts), $phoneNumber), $logger, $logFile, 'debug');

                // Vérifier si le contact existe déjà dans Doctrine
                logMessage(sprintf('Recherche du contact %s dans la base Doctrine...', $phoneNumber), $logger, $logFile, 'debug');
                $existingContact = $doctrineContactRepository->findByPhoneNumber($phoneNumber);

                if ($existingContact) {
                    logMessage(sprintf('Le contact %s existe déjà, mise à jour...', $phoneNumber), $logger, $logFile, 'info');

                    // Mise à jour des propriétés
                    $existingContact->setName($legacyContact->getName());
                    $existingContact->setEmail($legacyContact->getEmail());
                    $existingContact->setNotes($legacyContact->getNotes());
                    $existingContact->setUpdatedAt(new \DateTime()); // Mettre à jour la date de modification

                    // Persister les modifications
                    $entityManager->persist($existingContact);
                    $skippedCount++;
                } else {
                    logMessage(sprintf('Création d\'un nouveau contact pour %s...', $phoneNumber), $logger, $logFile, 'debug');

                    // Récupérer l'utilisateur associé
                    $userId = $legacyContact->getUserId();
                    $user = null;

                    if ($userId) {
                        logMessage(sprintf('Recherche de l\'utilisateur ID %d...', $userId), $logger, $logFile, 'debug');
                        $user = $doctrineUserRepository->findById($userId);
                        if (!$user) {
                            logMessage(sprintf('Utilisateur ID %d non trouvé pour le contact %s', $userId, $phoneNumber), $logger, $logFile, 'warning');
                        } else {
                            logMessage(sprintf('Utilisateur ID %d trouvé', $userId), $logger, $logFile, 'debug');
                        }
                    }

                    // Création d'un nouveau contact Doctrine
                    $doctrineContact = new DoctrineContact();
                    $doctrineContact->setName($legacyContact->getName());
                    $doctrineContact->setPhoneNumber($phoneNumber);
                    $doctrineContact->setEmail($legacyContact->getEmail());
                    $doctrineContact->setNotes($legacyContact->getNotes());

                    if ($user) {
                        $doctrineContact->setUserId($user->getId());
                    } else if ($userId) {
                        $doctrineContact->setUserId($userId);
                    }

                    $doctrineContact->setCreatedAt(new \DateTime($legacyContact->getCreatedAt()));
                    $doctrineContact->setUpdatedAt(new \DateTime($legacyContact->getUpdatedAt()));

                    // Persister le nouveau contact
                    $entityManager->persist($doctrineContact);
                    $migratedCount++;
                    logMessage(sprintf('Contact %s créé avec succès', $phoneNumber), $logger, $logFile, 'debug');
                }

                // Flush tous les 50 contacts pour éviter de surcharger la mémoire
                if (($migratedCount + $skippedCount) % 50 === 0) {
                    logMessage('Flush des changements en base de données...', $logger, $logFile, 'debug');
                    $entityManager->flush();

                    // Calculer le pourcentage de progression et le temps estimé restant
                    $processed = $migratedCount + $skippedCount + $errorCount;
                    $percentage = round(($processed / $totalContacts) * 100, 2);

                    // Calculer le temps écoulé et estimer le temps restant
                    $currentTime = microtime(true);
                    $elapsedTime = $currentTime - $startTime;
                    $elapsedMinutes = (int) floor($elapsedTime / 60);
                    $elapsedSeconds = (int) ($elapsedTime % 60);

                    // Estimer le temps restant
                    if ($processed > 0) {
                        $timePerItem = $elapsedTime / $processed;
                        $remainingItems = $totalContacts - $processed;
                        $estimatedRemainingTime = $timePerItem * $remainingItems;
                        $remainingMinutes = (int) floor($estimatedRemainingTime / 60);
                        $remainingSeconds = (int) ($estimatedRemainingTime % 60);

                        // Afficher la progression avec le temps
                        logMessage(sprintf(
                            'Progression: %d/%d contacts traités (%d%%) - %d créés, %d mis à jour, %d erreurs - Temps écoulé: %dm %ds - Temps restant estimé: %dm %ds',
                            $processed,
                            $totalContacts,
                            $percentage,
                            $migratedCount,
                            $skippedCount,
                            $errorCount,
                            $elapsedMinutes,
                            $elapsedSeconds, // Use the integer directly
                            $remainingMinutes,
                            $remainingSeconds // Use the integer directly
                        ), $logger, $logFile, 'info');
                    } else {
                        // Afficher la progression sans estimation du temps restant
                        logMessage(sprintf(
                            'Progression: %d/%d contacts traités (%d%%) - %d créés, %d mis à jour, %d erreurs - Temps écoulé: %dm %ds',
                            $processed,
                            $totalContacts,
                            $percentage,
                            $migratedCount,
                            $skippedCount,
                            $errorCount,
                            $elapsedMinutes,
                            $elapsedSeconds // Use the integer directly
                        ), $logger, $logFile, 'info');
                    }

                    // Mettre à jour le temps de la dernière mise à jour
                    $lastUpdateTime = $currentTime;
                }
            } catch (\Exception $e) {
                $errorMessage = sprintf('Erreur lors de la migration du contact %s: %s', $legacyContact->getPhoneNumber(), $e->getMessage());
                logMessage($errorMessage, $logger, $logFile, 'error');
                logMessage('Stack trace: ' . $e->getTraceAsString(), $logger, $logFile, 'error');
                $errorCount++;
                $errorDetails[] = [
                    'phoneNumber' => $legacyContact->getPhoneNumber(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ];
            }
        }

        // Flush à la fin de chaque lot
        logMessage(sprintf('Flush des changements à la fin du lot %d...', $batchNumber + 1), $logger, $logFile, 'info');
        $entityManager->flush();
    }

    // Flush final pour les contacts restants
    logMessage('Flush final des changements en base de données...', $logger, $logFile, 'info');
    $entityManager->flush();

    // Vérification de l'intégrité des données
    logMessage('Vérification de l\'intégrité des données...', $logger, $logFile, 'info');
    $doctrineContacts = $doctrineContactRepository->findAll();
    $integrityCheck = count($doctrineContacts) >= count($legacyContacts) - $errorCount;

    if ($integrityCheck) {
        logMessage('Vérification de l\'intégrité des données réussie', $logger, $logFile, 'info');
    } else {
        logMessage(sprintf(
            'Vérification de l\'intégrité des données échouée: %d contacts dans Doctrine, %d attendus',
            count($doctrineContacts),
            count($legacyContacts) - $errorCount
        ), $logger, $logFile, 'error');
    }

    // Enregistrer les détails des erreurs dans le fichier de log
    if (count($errorDetails) > 0) {
        logMessage('Détails des erreurs rencontrées:', $logger, $logFile, 'error');
        foreach ($errorDetails as $index => $error) {
            logMessage(sprintf(
                'Erreur %d: Contact %s - %s',
                $index + 1,
                $error['phoneNumber'],
                $error['error']
            ), $logger, $logFile, 'error');
        }

        // Écrire les détails complets dans un fichier d'erreurs séparé
        $errorFile = __DIR__ . '/../../logs/migrate-contacts-errors-' . date('Y-m-d-H-i-s') . '.log';
        file_put_contents($errorFile, json_encode($errorDetails, JSON_PRETTY_PRINT));
        logMessage(sprintf('Détails des erreurs enregistrés dans %s', $errorFile), $logger, $logFile, 'info');
    }

    // Calculer le temps total écoulé
    $endTime = microtime(true);
    $totalElapsedTime = $endTime - $startTime;
    $totalElapsedMinutes = (int) floor($totalElapsedTime / 60);
    $totalElapsedSeconds = (int) ($totalElapsedTime % 60);

    // Calculer la vitesse moyenne de traitement
    $totalProcessed = $migratedCount + $skippedCount + $errorCount;
    $averageTimePerItem = $totalProcessed > 0 ? (float)($totalElapsedTime / $totalProcessed) : 0;
    $itemsPerSecond = $averageTimePerItem > 0 ? (float)(1 / $averageTimePerItem) : 0;

    // Affichage des statistiques
    logMessage(sprintf(
        'Migration terminée: %d contacts migrés, %d mis à jour, %d erreurs - Temps total: %dm %ds - Vitesse moyenne: %.2f contacts/seconde',
        $migratedCount,
        $skippedCount,
        $errorCount,
        $totalElapsedMinutes,
        $totalElapsedSeconds, // Use the integer directly
        $itemsPerSecond
    ), $logger, $logFile, 'info');
} catch (\Exception $e) {
    logMessage(sprintf('Erreur critique lors de la migration des contacts: %s', $e->getMessage()), $logger, $logFile, 'error');
    logMessage('Stack trace: ' . $e->getTraceAsString(), $logger, $logFile, 'error');
    exit(1);
}

logMessage('Fin de la migration des contacts', $logger, $logFile, 'info');
exit(0);
