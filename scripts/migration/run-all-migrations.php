<?php

/**
 * Script principal pour exécuter toutes les migrations de données vers Doctrine ORM
 * 
 * Ce script exécute séquentiellement tous les scripts de migration dans l'ordre approprié
 * pour assurer l'intégrité des données et le respect des dépendances entre entités.
 * 
 * Usage: php scripts/migration/run-all-migrations.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

// Configuration du logger
$containerDefinitions = require __DIR__ . '/../../src/config/di.php';
$containerBuilder = new \DI\ContainerBuilder();
$containerBuilder->addDefinitions($containerDefinitions);
$container = $containerBuilder->build();
$logger = $container->get(\Psr\Log\LoggerInterface::class);

// Définition de l'ordre des migrations
$migrationScripts = [
    'migrate-users.php' => 'Utilisateurs',
    'migrate-contacts.php' => 'Contacts',
    'migrate-contact-groups.php' => 'Groupes de contacts',
    'migrate-contact-group-memberships.php' => 'Appartenances aux groupes',
    'migrate-segments.php' => 'Segments personnalisés',
    'migrate-phone-numbers.php' => 'Numéros de téléphone',
    'migrate-phone-number-segments.php' => 'Associations numéros-segments',
    'migrate-sms-history.php' => 'Historique SMS',
    'migrate-sms-orders.php' => 'Commandes SMS'
];

// Fonction pour exécuter un script de migration
function runMigrationScript($scriptPath, $description, $logger)
{
    $logger->info("=== Début de la migration des $description ===");

    // Exécution du script
    $command = PHP_BINARY . ' ' . $scriptPath;
    $output = [];
    $returnCode = 0;

    exec($command, $output, $returnCode);

    // Affichage de la sortie
    foreach ($output as $line) {
        echo $line . PHP_EOL;
    }

    // Vérification du code de retour
    if ($returnCode !== 0) {
        $logger->error("La migration des $description a échoué avec le code $returnCode");
        return false;
    }

    $logger->info("=== Fin de la migration des $description ===");
    return true;
}

// Exécution des migrations
$logger->info('Début du processus de migration complet');
$startTime = microtime(true);
$successCount = 0;
$failureCount = 0;

foreach ($migrationScripts as $script => $description) {
    $scriptPath = __DIR__ . '/' . $script;

    if (!file_exists($scriptPath)) {
        $logger->error("Le script de migration $script n'existe pas");
        $failureCount++;
        continue;
    }

    $scriptStartTime = microtime(true);
    $success = runMigrationScript($scriptPath, $description, $logger);
    $scriptEndTime = microtime(true);
    $scriptDuration = round($scriptEndTime - $scriptStartTime, 2);

    if ($success) {
        $logger->info("Migration des $description terminée en $scriptDuration secondes");
        $successCount++;
    } else {
        $logger->error("Migration des $description échouée après $scriptDuration secondes");
        $failureCount++;

        // Demander à l'utilisateur s'il souhaite continuer
        echo "La migration des $description a échoué. Voulez-vous continuer avec les migrations suivantes ? (o/n) ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim(strtolower($line)) != 'o') {
            $logger->error('Migration interrompue par l\'utilisateur');
            fclose($handle);
            exit(1);
        }
        fclose($handle);
    }

    // Pause entre les migrations
    sleep(1);
}

$endTime = microtime(true);
$totalDuration = round($endTime - $startTime, 2);

// Affichage des statistiques
$logger->info("=== Résumé de la migration ===");
$logger->info("Migrations réussies: $successCount");
$logger->info("Migrations échouées: $failureCount");
$logger->info("Durée totale: $totalDuration secondes");

if ($failureCount > 0) {
    $logger->warning("Certaines migrations ont échoué. Veuillez vérifier les logs pour plus de détails.");
    exit(1);
} else {
    $logger->info("Toutes les migrations ont été exécutées avec succès.");
    exit(0);
}
