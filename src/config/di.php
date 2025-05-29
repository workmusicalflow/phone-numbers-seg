<?php

use DI\ContainerBuilder;

/**
 * Configuration du conteneur d'injection de dépendances (Modularisé)
 *
 * Ce fichier charge et fusionne les définitions de dépendances depuis
 * des fichiers modulaires situés dans le sous-répertoire 'di/'.
 */

// Chemin vers le répertoire des configurations modulaires
$configDir = __DIR__ . '/di/';

// Liste des fichiers de configuration à charger
$configFiles = [
    // emergency.php a été retiré (solution temporaire),
    $configDir . 'repositories.php',
    $configDir . 'services.php',
    $configDir . 'graphql.php',
    $configDir . 'validators.php',
    $configDir . 'factories.php',
    $configDir . 'interfaces.php',
    $configDir . 'dataloaders.php', // Added DataLoaders configuration
    $configDir . 'whatsapp.php',    // Configuration WhatsApp Business API
    $configDir . 'whatsapp-bulk-send.php', // Configuration bulk send WhatsApp
    $configDir . 'other.php', // Core setup, controllers, middleware, observers
];

$definitions = [];

// Charger et fusionner les définitions de chaque fichier
foreach ($configFiles as $configFile) {
    if (file_exists($configFile)) {
        $definitions = array_merge($definitions, require $configFile);
    } else {
        // Optionnel: Logguer une erreur ou lancer une exception si un fichier manque
        error_log("Fichier de configuration DI manquant : " . $configFile);
    }
}

// Créer le builder de conteneur (si nécessaire de retourner le builder ou le conteneur ici)
// $containerBuilder = new ContainerBuilder();
// $containerBuilder->addDefinitions($definitions);

// Retourner les définitions fusionnées pour être utilisées par le builder ailleurs
// (par exemple, dans le point d'entrée de l'application comme public/index.php ou public/graphql.php)
return $definitions;
