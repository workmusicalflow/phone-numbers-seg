<?php

declare(strict_types=1);

/**
 * Script de test pour la requête GraphQL fetchApprovedWhatsAppTemplates
 * 
 * Ce script teste directement le contrôleur et le résolveur pour s'assurer qu'ils
 * fonctionnent correctement et ne retournent jamais null.
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Entities\User;
use App\GraphQL\Controllers\WhatsApp\WhatsAppTemplateController;
use App\GraphQL\Resolvers\WhatsApp\WhatsAppTemplateResolver;
use App\GraphQL\Types\WhatsApp\TemplateFilterInput;
use Psr\Container\ContainerInterface;

// Récupérer le conteneur
$container = require __DIR__ . '/../src/container.php';

// Créer un utilisateur factice pour le test
$mockUser = new User();
$mockUser->setId(1);
$mockUser->setUsername('test_user');

echo "Test de la requête fetchApprovedWhatsAppTemplates\n";
echo "===============================================\n\n";

// Tester le contrôleur
try {
    echo "Test du contrôleur WhatsAppTemplateController:\n";
    $controller = $container->get(WhatsAppTemplateController::class);
    $result = $controller->fetchApprovedWhatsAppTemplates(null, $mockUser);

    echo "Résultat du contrôleur: " . (is_array($result) ? "Array avec " . count($result) . " éléments" : "Non-array: " . gettype($result)) . "\n";
    
    if (empty($result)) {
        echo "ATTENTION: Le contrôleur a retourné un tableau vide. C'est valide pour GraphQL mais peut indiquer un problème avec l'API Meta.\n";
    } else {
        echo "Premier élément du tableau: " . json_encode(reset($result)) . "\n";
    }
} catch (\Throwable $e) {
    echo "ERREUR avec le contrôleur: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n";

// Tester le résolveur
try {
    echo "Test du résolveur WhatsAppTemplateResolver:\n";
    $resolver = $container->get(WhatsAppTemplateResolver::class);
    $result = $resolver->fetchApprovedWhatsAppTemplates(null, $mockUser);

    echo "Résultat du résolveur: " . (is_array($result) ? "Array avec " . count($result) . " éléments" : "Non-array: " . gettype($result)) . "\n";
    
    if (empty($result)) {
        echo "ATTENTION: Le résolveur a retourné un tableau vide. C'est valide pour GraphQL mais peut indiquer un problème avec l'API Meta.\n";
    } else {
        echo "Premier élément du tableau: " . json_encode(reset($result)) . "\n";
    }
} catch (\Throwable $e) {
    echo "ERREUR avec le résolveur: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n";

// Tester le service directement
try {
    echo "Test du service WhatsAppTemplateService:\n";
    $service = $container->get(\App\Services\Interfaces\WhatsApp\WhatsAppTemplateServiceInterface::class);
    $result = $service->fetchApprovedTemplatesFromMeta([]);

    echo "Résultat du service: " . (is_array($result) ? "Array avec " . count($result) . " éléments" : "Non-array: " . gettype($result)) . "\n";
    
    if (empty($result)) {
        echo "ATTENTION: Le service a retourné un tableau vide. Peut-être un problème de connexion à l'API Meta.\n";
    } else {
        echo "Premier élément du tableau: " . json_encode(reset($result)) . "\n";
    }
} catch (\Throwable $e) {
    echo "ERREUR avec le service: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n";

// Test avec le client API WhatsApp
try {
    echo "Test direct du client API WhatsApp:\n";
    $apiClient = $container->get(\App\Services\Interfaces\WhatsApp\WhatsAppApiClientInterface::class);
    $templates = $apiClient->getTemplates();

    echo "Résultat de l'API: " . (is_array($templates) ? "Array avec " . count($templates) . " éléments" : "Non-array: " . gettype($templates)) . "\n";
    
    if (empty($templates)) {
        echo "ATTENTION: L'API a retourné un tableau vide. Vérifiez les credentials et la connectivité à l'API Meta.\n";
    } else {
        echo "Premier élément du tableau: " . json_encode(reset($templates)) . "\n";
    }
} catch (\Throwable $e) {
    echo "ERREUR avec l'API client: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nTest terminé\n";