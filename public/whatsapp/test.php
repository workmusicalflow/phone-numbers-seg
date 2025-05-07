<?php
// Afficher toutes les erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclusion du chargeur automatique
require_once __DIR__ . '/../../vendor/autoload.php';

use DI\ContainerBuilder;

try {
    echo "Étape 1: Création du conteneur DI" . PHP_EOL;
    $containerBuilder = new ContainerBuilder();
    $containerBuilder->addDefinitions(__DIR__ . '/../../src/config/di.php');
    $container = $containerBuilder->build();
    
    echo "Étape 2: Vérification de whatsapp.config" . PHP_EOL;
    $config = $container->get('whatsapp.config');
    echo "Config récupérée avec succès: " . json_encode(array_keys($config)) . PHP_EOL;
    
    echo "Étape 3: Récupération du contrôleur" . PHP_EOL;
    $controller = $container->get('App\\GraphQL\\Controllers\\WhatsApp\\WebhookController');
    echo "Contrôleur récupéré avec succès: " . get_class($controller) . PHP_EOL;
    
    echo "Étape 4: Test de la vérification du webhook" . PHP_EOL;
    $response = $controller->verifyWebhook('subscribe', $config['webhook_verify_token'], '1234567890');
    echo "Réponse de vérification: " . $response . PHP_EOL;
    
    echo "Test réussi!" . PHP_EOL;
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . PHP_EOL;
    echo "Trace:" . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}