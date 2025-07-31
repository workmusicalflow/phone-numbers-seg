<?php

/**
 * Script pour vérifier les templates dans la base de données locale
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/bootstrap-doctrine.php';

// Créer le conteneur DI
$container = new App\GraphQL\DIContainer();
$templateRepo = $container->get(App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateRepositoryInterface::class);

echo "Templates dans la base de données locale:\n";
echo "=======================================\n\n";

$templates = $templateRepo->findAll();

foreach ($templates as $index => $template) {
    echo ($index + 1) . ". Template:\n";
    echo "   ID: " . $template->getId() . "\n";
    echo "   Name: " . $template->getName() . "\n";
    echo "   Language: " . $template->getLanguage() . "\n";
    echo "   Status: " . $template->getStatus() . "\n";
    echo "   Category: " . $template->getCategory() . "\n";
    echo "   Body: " . substr($template->getBodyText(), 0, 50) . "...\n";
    echo "   Meta Template ID: " . ($template->getMetaTemplateId() ?? 'N/A') . "\n";
    echo "   Active: " . ($template->isActive() ? 'Yes' : 'No') . "\n";
    echo "\n";
}

echo "Total: " . count($templates) . " templates\n";