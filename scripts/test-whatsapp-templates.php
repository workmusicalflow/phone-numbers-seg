<?php

/**
 * Script de test pour les templates WhatsApp
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/bootstrap-doctrine.php';
require_once __DIR__ . '/../src/config/di.php';

use App\Services\WhatsApp\WhatsAppService;
use App\Entities\WhatsApp\WhatsAppTemplate;
use App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateRepositoryInterface;
use App\Entities\User;

// Créer le conteneur DI
$container = new App\GraphQL\DIContainer();

// Test 1 : Créer un template de test
echo "1. Création d'un template de test\n";
$templateRepo = $container->get(WhatsAppTemplateRepositoryInterface::class);

$template = new WhatsAppTemplate();
$template->setName('hello_world');
$template->setLanguage('fr');
$template->setCategory(WhatsAppTemplate::CATEGORY_UTILITY);
$template->setStatus(WhatsAppTemplate::STATUS_APPROVED);
$template->setBodyText('Bonjour {{1}}, ceci est un message de test.');
$template->setIsActive(true);

try {
    $templateRepo->save($template);
    echo "   ✓ Template créé avec succès\n";
} catch (\Exception $e) {
    echo "   ✗ Erreur : " . $e->getMessage() . "\n";
}

// Test 2 : Récupérer les templates approuvés
echo "\n2. Récupération des templates approuvés\n";
try {
    $templates = $templateRepo->findApproved();
    echo "   ✓ " . count($templates) . " templates trouvés\n";
    
    foreach ($templates as $t) {
        echo "   - " . $t->getName() . " (" . $t->getLanguage() . ")\n";
    }
} catch (\Exception $e) {
    echo "   ✗ Erreur : " . $e->getMessage() . "\n";
}

// Test 3 : Test du service WhatsApp
echo "\n3. Test du service WhatsApp\n";
$whatsappService = $container->get(App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface::class);

// Récupérer l'admin
$userRepo = $container->get(App\Repositories\Interfaces\UserRepositoryInterface::class);
$admin = $userRepo->findOneBy(['username' => 'admin']);

if ($admin) {
    $templates = $whatsappService->getUserTemplates($admin);
    echo "   ✓ " . count($templates) . " templates disponibles pour l'admin\n";
} else {
    echo "   ✗ Admin non trouvé\n";
}

// Test 4 : Tester la construction des composants
echo "\n4. Test de construction des composants\n";
if (!empty($templates)) {
    $testTemplate = $templates[0];
    
    try {
        $components = $whatsappService->buildTemplateComponents($testTemplate, [
            'body' => ['John Doe']
        ]);
        
        echo "   ✓ Composants construits : " . json_encode($components, JSON_PRETTY_PRINT) . "\n";
    } catch (\Exception $e) {
        echo "   ✗ Erreur : " . $e->getMessage() . "\n";
    }
}

echo "\nTest terminé.\n";