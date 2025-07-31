<?php

declare(strict_types=1);

// Charger l'EntityManager
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine-simple.php';

use App\Entities\WhatsApp\WhatsAppTemplate;
use App\GraphQL\Types\WhatsApp\WhatsAppUserTemplateAdapter;

echo "=== Test de l'adaptateur WhatsAppUserTemplateAdapter ===\n\n";

try {
    // Créer un adaptateur de templates
    $adapter = new WhatsAppUserTemplateAdapter();
    
    // Récupérer les templates existants
    $templates = $entityManager->getRepository(WhatsAppTemplate::class)->findAll();
    
    if (empty($templates)) {
        echo "Aucun template trouvé. Veuillez d'abord exécuter le script de test templates.\n";
        exit(0);
    }
    
    echo "Test de l'adaptateur avec " . count($templates) . " templates:\n\n";
    
    foreach ($templates as $template) {
        echo "Template: " . $template->getName() . " (" . $template->getLanguage() . ")\n";
        
        // Tester différentes méthodes de l'adaptateur
        echo "  • ID: " . $adapter->getId($template) . "\n";
        echo "  • Nom du template: " . $adapter->templateName($template) . "\n";
        echo "  • Langue: " . $adapter->languageCode($template) . "\n";
        echo "  • Catégorie: " . $adapter->getCategory($template) . "\n";
        echo "  • Est spécial (déprécié): " . ($adapter->isSpecialTemplate($template) ? 'Oui' : 'Non') . "\n";
        echo "  • Variables corps: " . $adapter->bodyVariablesCount($template) . "\n";
        echo "  • A un média en-tête: " . ($adapter->hasHeaderMedia($template) ? 'Oui' : 'Non') . "\n";
        echo "  • URL média en-tête: " . ($adapter->headerMediaUrl($template) ?: 'Non défini') . "\n";
        echo "  • Est actif: " . ($adapter->isActive($template) ? 'Oui' : 'Non') . "\n";
        echo "  • Est global: " . ($adapter->isGlobal($template) ? 'Oui' : 'Non') . "\n";
        echo "  • Corps: " . $adapter->bodyText($template) . "\n";
        echo "\n";
    }
    
    echo "=== Test terminé avec succès ===\n";
    
} catch (\Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}