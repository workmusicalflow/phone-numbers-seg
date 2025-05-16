<?php

require_once __DIR__ . '/../vendor/autoload.php';
$entityManager = require_once __DIR__ . '/../src/bootstrap-doctrine.php';

// Créer un contexte de test
use DI\ContainerBuilder;

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(require __DIR__ . '/../src/config/di.php');
$container = $containerBuilder->build();

echo "Préparation des requêtes GraphQL pour tester l'intégration WhatsApp\n";
echo "================================================\n\n";

// Test GraphQL - Envoyer un message WhatsApp
$query = '
mutation SendWhatsAppMessage($message: WhatsAppMessageInput!) {
    sendWhatsAppMessage(message: $message) {
        id
        wabaMessageId
        phoneNumber
        direction
        type
        content
        status
        createdAt
    }
}
';

$variables = [
    'message' => [
        'recipient' => '+2250101010101',
        'type' => 'text',
        'content' => 'Test message depuis Oracle via GraphQL'
    ]
];

// Appel GraphQL
$data = [
    'query' => $query,
    'variables' => $variables
];

echo "1. Test mutation sendWhatsAppMessage:\n";
echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

// Test de la query d'historique
$historyQuery = '
query WhatsAppHistory($limit: Int!, $offset: Int) {
    whatsAppHistory(limit: $limit, offset: $offset) {
        id
        wabaMessageId
        phoneNumber
        direction
        type
        content
        status
        timestamp
        createdAt
    }
}
';

$historyVariables = [
    'limit' => 10,
    'offset' => 0
];

echo "2. Test query whatsAppHistory:\n";
echo json_encode([
    'query' => $historyQuery,
    'variables' => $historyVariables
], JSON_PRETTY_PRINT) . "\n\n";

// Test de la mutation sendWhatsAppTemplate
$templateQuery = '
mutation SendWhatsAppTemplate($template: WhatsAppTemplateSendInput!) {
    sendWhatsAppTemplate(template: $template) {
        id
        wabaMessageId
        phoneNumber
        direction
        type
        templateName
        templateLanguage
        status
        createdAt
    }
}
';

$templateVariables = [
    'template' => [
        'recipient' => '+2250101010101',
        'templateName' => 'hello_world',
        'languageCode' => 'fr',
        'body1Param' => 'Oracle'
    ]
];

echo "3. Test mutation sendWhatsAppTemplate:\n";
echo json_encode([
    'query' => $templateQuery,
    'variables' => $templateVariables
], JSON_PRETTY_PRINT) . "\n\n";

// Test query pour compter les messages
$countQuery = '
query WhatsAppMessageCount($status: String, $direction: String) {
    whatsAppMessageCount(status: $status, direction: $direction)
}
';

$countVariables = [
    'status' => 'sent',
    'direction' => 'OUTGOING'
];

echo "4. Test query whatsAppMessageCount:\n";
echo json_encode([
    'query' => $countQuery,
    'variables' => $countVariables
], JSON_PRETTY_PRINT) . "\n\n";

echo "================================================\n";
echo "Ces requêtes peuvent être utilisées via GraphiQL ou l'API GraphQL\n";
echo "URL GraphQL: http://localhost/graphql.php\n";
echo "\nNote: L'authentification doit être gérée via session PHP ou JWT si implémenté\n";
echo "Pour tester via CLI, utilisez un cookie de session valide ou implémentez JWT\n";

// Test local des services
echo "\n================================================\n";
echo "Test des services WhatsApp directement:\n\n";

try {
    $whatsappService = $container->get(\App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface::class);
    echo "✓ WhatsApp Service initialisé\n";
    
    $whatsappApiClient = $container->get(\App\Services\Interfaces\WhatsApp\WhatsAppApiClientInterface::class);
    echo "✓ WhatsApp API Client initialisé\n";
    
    $whatsappMessageRepo = $container->get(\App\Repositories\Interfaces\WhatsApp\WhatsAppMessageHistoryRepositoryInterface::class);
    echo "✓ WhatsApp Message Repository initialisé\n";
    
    echo "\nTous les services WhatsApp sont correctement configurés!\n";
    
} catch (\Exception $e) {
    echo "✗ Erreur: " . $e->getMessage() . "\n";
}