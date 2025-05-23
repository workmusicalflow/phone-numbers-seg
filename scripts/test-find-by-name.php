<?php

// Charger le bootstrap pour avoir accès aux services et repositories
require_once __DIR__ . '/../src/bootstrap-rest.php';

use App\Entities\WhatsApp\WhatsAppTemplate;
use App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateRepositoryInterface;

// Fonction de log
function logMessage($message, $isError = false) {
    echo ($isError ? "[ERREUR] " : "[INFO] ") . $message . PHP_EOL;
}

try {
    // Récupérer les services requis
    $templateRepository = $container->get(WhatsAppTemplateRepositoryInterface::class);
    
    logMessage("Services récupérés avec succès");
    
    // Créer un template de test
    $templateName = 'test_template_' . uniqid();
    $template = new WhatsAppTemplate();
    $template->setName($templateName);
    $template->setLanguage('fr');
    $template->setStatus('APPROVED');
    $template->setCategory('UTILITY');
    $template->setBodyText('Test message avec {{1}}');
    $template->setBodyVariablesCount(1);
    
    // Sauvegarder le template
    $templateRepository->save($template);
    logMessage("Template créé avec succès: $templateName");
    
    // Vérifier que le template peut être retrouvé par son nom
    $foundTemplate = $templateRepository->findOneBy(['name' => $templateName]);
    if ($foundTemplate) {
        logMessage("✅ Template trouvé par son nom");
        
        // Vérifier que getTemplateId() retourne une valeur
        $templateId = $foundTemplate->getTemplateId();
        logMessage("getTemplateId() retourne: " . ($templateId ?? "null"));
        
        // Vérifier que la catégorie est bien définie
        logMessage("Catégorie du template: " . $foundTemplate->getCategory());
        
        // Vérifier d'autres propriétés
        logMessage("Langue du template: " . $foundTemplate->getLanguage());
        logMessage("Langue via getLanguageCode(): " . $foundTemplate->getLanguageCode());
        logMessage("ID du template: " . $foundTemplate->getId());
        
        logMessage("✅ Toutes les propriétés ont été correctement récupérées");
    } else {
        logMessage("❌ Template NON trouvé après création", true);
    }
    
    logMessage("Test terminé avec succès");
    
} catch (\Exception $e) {
    logMessage("Erreur lors du test: " . $e->getMessage(), true);
    logMessage("Trace: " . $e->getTraceAsString(), true);
}