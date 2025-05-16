<?php
/**
 * Test d'envoi de message WhatsApp avec template
 */

require_once __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;

// Charger les variables d'environnement
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Configuration
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../src/config/di.php');
$container = $containerBuilder->build();

// Services
$userRepo = $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class);
$whatsappService = $container->get(\App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface::class);

// Obtenir l'utilisateur admin
$user = $userRepo->findById(1);
echo "=== Test d'envoi WhatsApp avec Template ===\n\n";
echo "Utilisateur : " . $user->getUsername() . "\n\n";

try {
    // 1. D'abord, obtenons la liste des templates disponibles
    echo "1. Récupération des templates disponibles...\n";
    $templates = $whatsappService->getUserTemplates($user);
    
    if (empty($templates)) {
        echo "Aucun template disponible. Testons avec le template par défaut 'hello_world'.\n\n";
        $templateName = 'hello_world';
        $languageCode = 'en_US';
    } else {
        echo "Templates disponibles :\n";
        foreach ($templates as $template) {
            echo "- " . ($template['name'] ?? 'Sans nom') . " (" . ($template['language'] ?? 'Sans langue') . ")\n";
        }
        // Utiliser le premier template disponible
        $templateName = $templates[0]['name'] ?? 'hello_world';
        $languageCode = $templates[0]['language'] ?? 'en_US';
        echo "\nUtilisation du template : $templateName ($languageCode)\n";
    }
    
    // 2. Envoyer un message avec le template
    echo "\n2. Envoi du message template...\n";
    
    $result = $whatsappService->sendTemplateMessage(
        $user,
        '+2250777104936', // Votre numéro
        $templateName,
        $languageCode,
        null, // Pas d'image d'en-tête
        [] // Pas de paramètres (pour un template simple)
    );
    
    echo "\n✅ Message template envoyé avec succès !\n";
    echo "ID : " . ($result->getId() ?? 'NULL') . "\n";
    echo "WABA ID : " . ($result->getWabaMessageId() ?? 'NULL') . "\n";
    echo "Status : " . ($result->getStatus() ?? 'NULL') . "\n";
    echo "Template : " . ($result->getTemplateName() ?? 'NULL') . "\n";
    
} catch (\Exception $e) {
    echo "\n❌ Erreur : " . $e->getMessage() . "\n";
    echo "Fichier : " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace :\n" . $e->getTraceAsString() . "\n";
}

// 3. Vérifier l'historique
echo "\n3. Vérification de l'historique...\n";
try {
    $whatsappRepo = $container->get(\App\Repositories\Interfaces\WhatsApp\WhatsAppMessageHistoryRepositoryInterface::class);
    $recentMessages = $whatsappRepo->findByUser($user, 5, 0);
    
    echo "Messages récents :\n";
    foreach ($recentMessages as $msg) {
        echo sprintf(
            "- [%s] %s: %s (%s)\n",
            $msg->getCreatedAt()->format('Y-m-d H:i:s'),
            $msg->getType(),
            substr($msg->getContent() ?? 'Sans contenu', 0, 50),
            $msg->getStatus()
        );
    }
} catch (\Exception $e) {
    echo "Erreur lors de la récupération de l'historique : " . $e->getMessage() . "\n";
}

echo "\n=== Fin du test ===\n";