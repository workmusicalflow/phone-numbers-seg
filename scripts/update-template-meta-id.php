<?php

/**
 * Script pour mettre à jour le metaTemplateId des templates
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Charger l'EntityManager
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

echo "=== Mise à jour du metaTemplateId ===\n\n";

try {
    // Mapping des templates connus avec leurs IDs Meta
    $metaTemplateIds = [
        'qshe_day_3' => '701148195639572',
        // Ajouter d'autres mappings si nécessaire
    ];
    
    $templateRepository = $entityManager->getRepository(\App\Entities\WhatsApp\WhatsAppTemplate::class);
    
    foreach ($metaTemplateIds as $templateName => $metaId) {
        $template = $templateRepository->findOneBy(['name' => $templateName]);
        
        if ($template) {
            $template->setMetaTemplateId($metaId);
            echo "✅ Template '$templateName' mis à jour avec metaTemplateId: $metaId\n";
        } else {
            echo "❌ Template '$templateName' non trouvé\n";
        }
    }
    
    // Sauvegarder les changements
    $entityManager->flush();
    
    echo "\n✅ Mise à jour terminée!\n";
    
    // Vérifier la mise à jour
    echo "\n=== Vérification ===\n";
    $template = $templateRepository->findOneBy(['metaTemplateId' => '701148195639572']);
    if ($template) {
        echo "✅ Template trouvé par metaTemplateId: " . $template->getName() . "\n";
    } else {
        echo "❌ Template non trouvé avec metaTemplateId '701148195639572'\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ Erreur: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}