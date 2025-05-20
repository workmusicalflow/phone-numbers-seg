<?php

/**
 * Test d'envoi de message WhatsApp avec template
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/bootstrap-doctrine.php';

// Créer le conteneur DI
$container = new App\GraphQL\DIContainer();
$whatsappService = $container->get(App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface::class);
$userRepo = $container->get(App\Repositories\Interfaces\UserRepositoryInterface::class);

// Récupérer l'utilisateur admin
$adminUser = $userRepo->findOneBy(['isAdmin' => true]);
if (!$adminUser) {
    throw new \Exception("Aucun utilisateur admin trouvé");
}

$testPhone = '+33123456789'; // Numéro de test

echo "Test d'envoi de message WhatsApp avec template\n";
echo "=============================================\n\n";

try {
    // 1. Lister les templates disponibles
    echo "1. Templates disponibles:\n";
    $templates = $whatsappService->getUserTemplates($adminUser);
    
    // Chercher le template hello_world en anglais (qui existe dans Meta)
    $helloWorldTemplate = null;
    foreach ($templates as $template) {
        if ($template->getName() === 'hello_world' && $template->getLanguage() === 'en_US') {
            $helloWorldTemplate = $template;
            break;
        }
    }
    
    if (!$helloWorldTemplate) {
        throw new \Exception("Template hello_world en anglais non trouvé");
    }
    
    echo "   ✓ Template trouvé: " . $helloWorldTemplate->getName() . " (en_US)\n";
    echo "   Body: " . $helloWorldTemplate->getBodyText() . "\n";
    echo "   Variables: " . $helloWorldTemplate->getVariableCount() . "\n\n";
    
    // 2. Préparer les paramètres
    echo "2. Préparation des paramètres:\n";
    // Le template hello_world en anglais semble ne pas avoir de paramètres
    $parameters = []; 
    if ($helloWorldTemplate->getVariableCount() > 0) {
        $parameters = ['John Doe']; // Pour remplacer {{1}}
    }
    echo "   ✓ Paramètres: " . json_encode($parameters) . "\n\n";
    
    // 3. Envoyer le message template
    echo "3. Envoi du message template:\n";
    $messageHistory = $whatsappService->sendTemplateMessage(
        $adminUser,
        $testPhone,
        $helloWorldTemplate->getName(),
        $helloWorldTemplate->getLanguage(),
        null, // headerImageUrl
        $parameters // bodyParams
    );
    
    echo "   ✓ Message envoyé avec succès!\n";
    echo "   ID Meta: " . $messageHistory->getWabaMessageId() . "\n\n";
    $metaMessageId = $messageHistory->getWabaMessageId();
    
    // 4. Vérifier dans l'historique
    echo "4. Vérification dans l'historique:\n";
    $messageRepo = $container->get(App\Repositories\Interfaces\WhatsApp\WhatsAppMessageHistoryRepositoryInterface::class);
    $lastMessage = $messageRepo->findOneBy(['wabaMessageId' => $metaMessageId]);
    
    if ($lastMessage) {
        echo "   ✓ Message trouvé dans l'historique\n";
        echo "   ID: " . $lastMessage->getId() . "\n";
        echo "   Status: " . $lastMessage->getStatus() . "\n";
        echo "   Type: " . $lastMessage->getType() . "\n";
        echo "   Content: " . substr($lastMessage->getContent() ?? 'N/A', 0, 50) . "...\n";
    } else {
        echo "   ⚠ Message non trouvé dans l'historique\n";
    }
    
} catch (\Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
    echo "Trace : " . $e->getTraceAsString() . "\n";
}

echo "\nTest terminé.\n";