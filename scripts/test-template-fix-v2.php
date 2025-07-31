<?php

// Charger le bootstrap pour avoir accès aux services et repositories
require_once __DIR__ . '/../src/bootstrap-rest.php';

use App\Entities\WhatsApp\WhatsAppTemplate;
use App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateRepositoryInterface;
use App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface;

// Fonction de log
function logMessage($message, $isError = false) {
    echo ($isError ? "[ERREUR] " : "[INFO] ") . $message . PHP_EOL;
}

try {
    // Récupérer le service WhatsApp (où se trouve la logique problématique)
    /** @var WhatsAppServiceInterface $whatsAppService */
    $whatsAppService = $container->get(WhatsAppServiceInterface::class);
    logMessage("Service WhatsApp récupéré avec succès");

    // Récupérer le repository des templates
    /** @var WhatsAppTemplateRepositoryInterface $templateRepository */
    $templateRepository = $container->get(WhatsAppTemplateRepositoryInterface::class);
    logMessage("Repository de templates récupéré avec succès");

    // 1. Créer un template de test simple
    $templateName = 'test_greeting_' . uniqid();
    $template = new WhatsAppTemplate();
    $template->setName($templateName);
    $template->setLanguage('fr');
    $template->setStatus('APPROVED');
    $template->setCategory('UTILITY');
    
    // Sauvegarder le template
    $templateRepository->save($template);
    logMessage("Template de test créé avec succès: " . $templateName);
    
    // 2. Vérifier qu'on peut retrouver le template par son nom
    $foundTemplate = $templateRepository->findOneBy(['name' => $templateName]);
    
    if ($foundTemplate) {
        logMessage("✅ ETAPE 1 - Le template a été correctement retrouvé par son nom");
        
        // Afficher les attributs du template
        logMessage("ID: " . $foundTemplate->getId());
        logMessage("Nom: " . $foundTemplate->getName());
        logMessage("Langue: " . $foundTemplate->getLanguage());
        logMessage("TemplateId: " . ($foundTemplate->getTemplateId() ?? "null"));
        
        // Tester la réponse de getTemplateId() (devrait retourner le nom si templateId n'est pas défini)
        logMessage("getTemplateId() retourne: " . $foundTemplate->getTemplateId());
        
        // 3. Utiliser le WhatsAppService comme dans le code problématique
        // Simuler une recherche comme dans le code problématique de WhatsAppService.php
        
        // Première approche avec findOneBy
        $simulationTemplate = $templateRepository->findOneBy(['name' => $templateName]);
        if ($simulationTemplate) {
            logMessage("✅ ETAPE 2 - Simulation réussie: template trouvé par name");
        } else {
            logMessage("❌ ETAPE 2 - Simulation échouée: template NON trouvé par name", true);
        }
        
        // Récupérer la méthode findByMetaNameAndLanguage mise à jour
        $metaNameTemplate = $templateRepository->findByMetaNameAndLanguage($templateName, 'fr');
        if ($metaNameTemplate) {
            logMessage("✅ ETAPE 3 - findByMetaNameAndLanguage réussie: template trouvé");
        } else {
            logMessage("❌ ETAPE 3 - findByMetaNameAndLanguage échouée: template NON trouvé", true);
        }
        
        logMessage("Test terminé avec succès");
    } else {
        logMessage("❌ Test échoué: Template non retrouvé après création", true);
    }
    
} catch (\Exception $e) {
    logMessage("Erreur lors du test: " . $e->getMessage(), true);
    logMessage("Trace: " . $e->getTraceAsString(), true);
    exit(1);
}