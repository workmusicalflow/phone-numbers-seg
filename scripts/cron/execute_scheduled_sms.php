#!/usr/bin/env php
<?php

/**
 * Script d'exécution des SMS planifiés
 * 
 * Ce script est destiné à être exécuté périodiquement par un cron job pour
 * vérifier et exécuter les SMS planifiés qui sont dus.
 * 
 * Exemple de configuration cron (exécution toutes les 5 minutes) :
 * # */5 * * * * php /chemin/vers/execute_scheduled_sms.php
 * 
 * Options :
 * --limit=N : Limite le nombre de SMS à exécuter (défaut: 1500)
 * --verbose : Affiche des informations détaillées pendant l'exécution
 * --help : Affiche l'aide
 */

// Définir le chemin de base
define('BASE_PATH', realpath(__DIR__ . '/../../'));

// Charger l'autoloader de Composer
require_once BASE_PATH . '/vendor/autoload.php';

// Analyser les arguments de la ligne de commande
$options = getopt('', ['limit::', 'verbose', 'help', 'id::']);
$limit = isset($options['limit']) ? (int)$options['limit'] : 100;
$verbose = isset($options['verbose']);
$specificId = isset($options['id']) ? (int)$options['id'] : null;

// Afficher l'aide si demandé
if (isset($options['help'])) {
    echo "Script d'exécution des SMS planifiés\n\n";
    echo "Options :\n";
    echo "  --limit=N    Limite le nombre de SMS à exécuter (défaut: 100)\n";
    echo "  --id=N       Exécute un SMS planifié spécifique par son ID\n";
    echo "  --verbose    Affiche des informations détaillées pendant l'exécution\n";
    echo "  --help       Affiche cette aide\n";
    exit(0);
}

// Charger le conteneur d'injection de dépendances
$containerBuilder = new \DI\ContainerBuilder();
$definitions = require BASE_PATH . '/src/config/di.php';
$containerBuilder->addDefinitions($definitions);
$container = $containerBuilder->build();

// Récupérer le service d'exécution des SMS planifiés
try {
    // Ajouter le service ScheduledSMSExecutionService au conteneur
    $container->set(\App\Services\Interfaces\ScheduledSMSExecutionServiceInterface::class, function ($container) {
        return new \App\Services\ScheduledSMSExecutionService(
            $container->get(\App\Repositories\ScheduledSMSRepository::class),
            $container->get(\App\Repositories\ScheduledSMSLogRepository::class),
            $container->get(\App\Repositories\SenderNameRepository::class),
            $container->get(\App\Services\Interfaces\SMSSenderServiceInterface::class),
            $container->get(\App\Services\Interfaces\RealtimeNotificationServiceInterface::class),
            $container->get(\App\Services\Interfaces\ErrorLoggerServiceInterface::class),
            $container->get(\Psr\Log\LoggerInterface::class)
        );
    });

    $service = $container->get(\App\Services\Interfaces\ScheduledSMSExecutionServiceInterface::class);

    // Exécuter les SMS planifiés
    if ($specificId !== null) {
        // Exécuter un SMS planifié spécifique
        if ($verbose) {
            echo "Exécution du SMS planifié #$specificId...\n";
        }
        $result = $service->executeSpecificScheduledSMS($specificId);
    } else {
        // Exécuter tous les SMS planifiés dus
        if ($verbose) {
            echo "Exécution des SMS planifiés (limite: $limit)...\n";
        }
        $result = $service->executeScheduledSMS($limit);
    }

    // Afficher le résultat
    if ($verbose) {
        echo "Statut: " . $result['status'] . "\n";
        echo "Message: " . $result['message'] . "\n";
        
        if (isset($result['executed'])) {
            echo "SMS exécutés: " . $result['executed'] . "\n";
        }
        
        if (isset($result['results']) && is_array($result['results'])) {
            echo "Détails:\n";
            foreach ($result['results'] as $id => $smsResult) {
                echo "  SMS #$id: " . $smsResult['status'] . "\n";
                if (isset($smsResult['successful_sends'])) {
                    echo "    Envois réussis: " . $smsResult['successful_sends'] . "/" . $smsResult['total_recipients'] . "\n";
                }
                if (isset($smsResult['failed_sends']) && $smsResult['failed_sends'] > 0) {
                    echo "    Envois échoués: " . $smsResult['failed_sends'] . "/" . $smsResult['total_recipients'] . "\n";
                    if (isset($smsResult['errors']) && is_array($smsResult['errors'])) {
                        echo "    Erreurs:\n";
                        foreach ($smsResult['errors'] as $error) {
                            echo "      - $error\n";
                        }
                    }
                }
            }
        } elseif (isset($result['result'])) {
            $smsResult = $result['result'];
            echo "Détails:\n";
            echo "  Statut: " . $smsResult['status'] . "\n";
            echo "  Envois réussis: " . $smsResult['successful_sends'] . "/" . $smsResult['total_recipients'] . "\n";
            if ($smsResult['failed_sends'] > 0) {
                echo "  Envois échoués: " . $smsResult['failed_sends'] . "/" . $smsResult['total_recipients'] . "\n";
                if (isset($smsResult['errors']) && is_array($smsResult['errors'])) {
                    echo "  Erreurs:\n";
                    foreach ($smsResult['errors'] as $error) {
                        echo "    - $error\n";
                    }
                }
            }
        }
    }

    // Définir le code de sortie
    exit($result['status'] === 'success' ? 0 : 1);
} catch (\Exception $e) {
    // Afficher l'erreur
    echo "Erreur: " . $e->getMessage() . "\n";
    if ($verbose) {
        echo "Trace:\n" . $e->getTraceAsString() . "\n";
    }
    exit(1);
}