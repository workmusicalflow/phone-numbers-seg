<?php

/**
 * Vérifier les métadonnées des templates WhatsApp
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Charger l'EntityManager
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

echo "=== Vérification des Templates WhatsApp ===\n\n";

try {
    $templateRepository = $entityManager->getRepository(\App\Entities\WhatsApp\WhatsAppTemplate::class);
    $templates = $templateRepository->findAll();
    
    echo "Templates trouvés: " . count($templates) . "\n\n";
    
    foreach ($templates as $template) {
        echo "Template:\n";
        echo "  - ID interne: " . $template->getId() . "\n";
        echo "  - Nom: " . $template->getName() . "\n";
        echo "  - Meta Template ID: " . ($template->getMetaTemplateId() ?? 'NULL') . "\n";
        echo "  - Template ID: " . ($template->getTemplateId() ?? 'NULL') . "\n";
        echo "  - Language: " . $template->getLanguage() . "\n";
        echo "  - Status: " . $template->getStatus() . "\n";
        echo "  - Category: " . ($template->getCategory() ?? 'NULL') . "\n";
        echo "  - Is Active: " . ($template->isActive() ? 'Oui' : 'Non') . "\n";
        echo "  - Is Global: " . ($template->isGlobal() ? 'Oui' : 'Non') . "\n";
        echo "\n";
    }
    
    // Tester la recherche par metaTemplateId
    echo "\n=== Test de recherche par metaTemplateId ===\n";
    $testId = '701148195639572';
    $foundTemplate = $templateRepository->findOneBy(['metaTemplateId' => $testId]);
    
    if ($foundTemplate) {
        echo "✅ Template trouvé avec metaTemplateId '$testId': " . $foundTemplate->getName() . "\n";
    } else {
        echo "❌ Aucun template trouvé avec metaTemplateId '$testId'\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ Erreur: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}