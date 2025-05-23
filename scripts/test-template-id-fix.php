<?php

// Charger le bootstrap pour avoir accès aux services et repositories
require_once __DIR__ . '/../src/bootstrap-rest.php';

use App\Entities\WhatsApp\WhatsAppTemplate;
use App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateRepositoryInterface;

// Fonction de log
function logMessage($message, $isError = false) {
    echo ($isError ? "[ERREUR] " : "[INFO] ") . $message . PHP_EOL;
}

// Récupérer le repository des templates
try {
    /** @var WhatsAppTemplateRepositoryInterface $templateRepository */
    $templateRepository = $container->get(WhatsAppTemplateRepositoryInterface::class);
    logMessage("Repository de templates récupéré avec succès");
} catch (\Exception $e) {
    logMessage("Erreur lors de la récupération du repository: " . $e->getMessage(), true);
    exit(1);
}

// Tester findOneBy avec le champ name (l'ancien code utilisait template_id)
try {
    // Trouver tous les templates pour le test
    $templates = $templateRepository->findApproved();
    
    if (empty($templates)) {
        logMessage("Aucun template trouvé pour le test. Création d'un template de test...");
        
        // Créer un template de test si aucun n'existe
        $template = new WhatsAppTemplate();
        $template->setName('test_template');
        $template->setTemplateId('test_template_id');
        $template->setLanguage('fr');
        $template->setHeaderFormat('TEXT');
        $template->setHasMediaHeader(false);
        $template->setHasButtons(false);
        $template->setButtonsCount(0);
        $template->setStatus('APPROVED');
        $template->setCategory('UTILITY');
        
        $templateRepository->save($template);
        logMessage("Template de test créé avec succès");
        
        // Rafraîchir la liste des templates
        $templates = $templateRepository->findApproved();
    }
    
    // Afficher les templates trouvés
    logMessage("Templates trouvés: " . count($templates));
    foreach ($templates as $index => $template) {
        $templateId = $template->getTemplateId();
        $name = $template->getName();
        
        logMessage("Template #{$index}: ID={$templateId}, Nom={$name}");
        
        // Tester la recherche par name (l'ancien code utilisait template_id)
        $foundTemplate = $templateRepository->findOneBy(['name' => $name]);
        
        if ($foundTemplate) {
            logMessage("✅ Template trouvé par name: {$name}");
        } else {
            logMessage("❌ Template NON trouvé par name: {$name}", true);
        }
        
        // Tester la recherche par templateId si disponible
        if ($templateId) {
            // Recherche par le nouveau templateId
            $foundByTemplateId = $templateRepository->findOneBy(['templateId' => $templateId]);
            
            if ($foundByTemplateId) {
                logMessage("✅ Template trouvé par templateId: {$templateId}");
            } else {
                logMessage("❌ Template NON trouvé par templateId: {$templateId}", true);
                
                // Vérifier si la propriété existe
                $reflection = new ReflectionClass($template);
                $hasTemplateIdProperty = $reflection->hasProperty('templateId');
                logMessage("Propriété templateId existe: " . ($hasTemplateIdProperty ? "Oui" : "Non"));
                
                // Afficher toutes les propriétés pour diagnostic
                $properties = $reflection->getProperties();
                logMessage("Propriétés disponibles: " . count($properties));
                foreach ($properties as $property) {
                    logMessage("- " . $property->getName());
                }
            }
        }
        
        // Limiter à 3 templates pour ne pas surcharger les logs
        if ($index >= 2) {
            logMessage("Affichage limité aux 3 premiers templates.");
            break;
        }
    }
    
    logMessage("Test terminé avec succès");
    
} catch (\Exception $e) {
    logMessage("Erreur lors du test: " . $e->getMessage(), true);
    logMessage("Trace: " . $e->getTraceAsString(), true);
    exit(1);
}