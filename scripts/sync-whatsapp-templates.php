<?php

/**
 * Script pour synchroniser les templates WhatsApp depuis l'API Meta
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/bootstrap-doctrine.php';

// Créer le conteneur DI
$container = new App\GraphQL\DIContainer();
$whatsappService = $container->get(App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface::class);
$userRepo = $container->get(App\Repositories\Interfaces\UserRepositoryInterface::class);

echo "Synchronisation des templates WhatsApp depuis l'API Meta...\n";

try {
    $count = $whatsappService->syncTemplatesFromMeta();
    echo "✓ $count templates synchronisés avec succès.\n";
    
    // Afficher les templates disponibles
    $adminUser = $userRepo->findOneBy(['isAdmin' => true]);
    if (!$adminUser) {
        throw new \Exception("Aucun utilisateur admin trouvé");
    }
    $templates = $whatsappService->getUserTemplates($adminUser);
    echo "\nTemplates disponibles:\n";
    foreach ($templates as $template) {
        echo "- " . $template->getName() . " (" . $template->getLanguage() . ") - " . $template->getStatus() . "\n";
        echo "  Catégorie: " . $template->getCategory() . "\n";
        echo "  Body: " . substr($template->getBodyText(), 0, 50) . "...\n";
        if ($template->getVariableCount() > 0) {
            echo "  Variables: " . $template->getVariableCount() . "\n";
        }
        echo "\n";
    }
} catch (\Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
    echo "Trace : " . $e->getTraceAsString() . "\n";
}