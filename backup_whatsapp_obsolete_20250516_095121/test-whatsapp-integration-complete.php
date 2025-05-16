<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use App\Entities\WhatsApp\WhatsAppTemplate;

// Configuration
$config = [
    'base_url' => 'https://graph.facebook.com/',
    'api_version' => 'v18.0',
    'access_token' => 'TEST_ACCESS_TOKEN',
    'phone_number_id' => '123456789',
    'whatsapp_business_account_id' => '987654321'
];

// Logger
$logger = new Logger('whatsapp');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

// Entity Manager
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

echo "=== Test d'intégration WhatsApp ===\n\n";

// 1. Test de la création du client API
try {
    echo "1. Test du client API WhatsApp...\n";
    $client = new \App\Services\WhatsApp\WhatsAppApiClient($logger, $config);
    echo "✓ Client API créé avec succès\n\n";
} catch (Exception $e) {
    echo "✗ Erreur création client: " . $e->getMessage() . "\n\n";
}

// 2. Test des repositories
try {
    echo "2. Test des repositories WhatsApp...\n";
    
    // Test MessageHistory repository
    $messageHistoryRepo = new \App\Repositories\Doctrine\WhatsApp\WhatsAppMessageHistoryRepository(
        $entityManager, 
        \App\Entities\WhatsApp\WhatsAppMessageHistory::class
    );
    echo "✓ Repository MessageHistory créé\n";
    
    // Test Template repository
    $templateRepo = new \App\Repositories\Doctrine\WhatsApp\WhatsAppTemplateRepository(
        $entityManager,
        \App\Entities\WhatsApp\WhatsAppTemplate::class
    );
    echo "✓ Repository Template créé\n";
    
    // Test Queue repository
    $queueRepo = new \App\Repositories\Doctrine\WhatsApp\WhatsAppQueueRepository(
        $entityManager,
        \App\Entities\WhatsApp\WhatsAppQueue::class
    );
    echo "✓ Repository Queue créé\n\n";
} catch (Exception $e) {
    echo "✗ Erreur repositories: " . $e->getMessage() . "\n\n";
}

// 3. Test du service WhatsApp
try {
    echo "3. Test du service WhatsApp...\n";
    if (isset($templateRepo) && isset($messageHistoryRepo)) {
        $service = new \App\Services\WhatsApp\WhatsAppService(
            $client,
            $messageHistoryRepo,
            $templateRepo,
            $logger,
            $config
        );
        echo "✓ Service WhatsApp créé\n\n";
    } else {
        echo "✗ Le service nécessite que les repositories soient créés d'abord\n\n";
    }
} catch (Exception $e) {
    echo "✗ Erreur service: " . $e->getMessage() . "\n\n";
}

// 4. Test de création d'un template
try {
    echo "4. Test création d'un template...\n";
    if (isset($templateRepo)) {
        $template = new WhatsAppTemplate();
        $template->setName('welcome_message');
        $template->setLanguage('fr');
        $template->setCategory('MARKETING');
        $template->setStatus('APPROVED');
        $template->setComponents([
            [
                'type' => 'BODY',
                'text' => 'Bonjour {{1}}, bienvenue chez Oracle!'
            ]
        ]);
        
        $templateRepo->save($template);
        echo "✓ Template créé avec succès\n\n";
    } else {
        echo "✗ Repository template non disponible\n\n";
    }
} catch (Exception $e) {
    echo "✗ Erreur création template: " . $e->getMessage() . "\n\n";
}

// 5. Test de lecture des templates
try {
    echo "5. Test lecture des templates...\n";
    if (isset($templateRepo)) {
        $templates = $templateRepo->findAll();
        echo "Nombre de templates: " . count($templates) . "\n";
        
        foreach ($templates as $template) {
            echo "- Template: " . $template->getName() . " (" . $template->getLanguage() . ")\n";
        }
        echo "\n";
    } else {
        echo "✗ Repository template non disponible\n\n";
    }
} catch (Exception $e) {
    echo "✗ Erreur lecture templates: " . $e->getMessage() . "\n\n";
}

echo "=== Tests terminés ===\n";