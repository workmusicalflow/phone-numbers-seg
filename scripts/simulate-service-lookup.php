<?php

// Charger le bootstrap pour avoir accès aux services et repositories
require_once __DIR__ . '/../src/bootstrap-rest.php';

use App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateRepositoryInterface;

// Fonction de log
function logMessage($message, $isError = false) {
    echo ($isError ? "[ERREUR] " : "[INFO] ") . $message . PHP_EOL;
}

try {
    // Récupérer les services requis
    $templateRepository = $container->get(WhatsAppTemplateRepositoryInterface::class);
    
    logMessage("Services récupérés avec succès");
    
    // Récupérer tous les templates existants
    $templates = $templateRepository->findApproved();
    
    if (empty($templates)) {
        logMessage("Aucun template trouvé dans la base de données.");
        
        // Vérifier les noms des champs avec une requête SQL directe
        $conn = $container->get(\Doctrine\DBAL\Connection::class);
        $sql = "PRAGMA table_info(whatsapp_templates)";
        $result = $conn->executeQuery($sql)->fetchAllAssociative();
        
        logMessage("Structure de la table whatsapp_templates:");
        foreach ($result as $column) {
            logMessage("- " . $column['name'] . " (" . $column['type'] . ")");
        }
        
        // Vérifier les données existantes
        $sql = "SELECT * FROM whatsapp_templates LIMIT 5";
        $result = $conn->executeQuery($sql)->fetchAllAssociative();
        
        if (empty($result)) {
            logMessage("Aucune donnée dans la table whatsapp_templates.");
        } else {
            logMessage("Données existantes dans la table whatsapp_templates:");
            foreach ($result as $row) {
                logMessage(json_encode($row));
            }
        }
    } else {
        logMessage("Nombre de templates trouvés: " . count($templates));
        
        // Tester la recherche par name (simuler le code de WhatsAppService)
        foreach ($templates as $index => $template) {
            $templateName = $template->getName();
            
            // Simuler la recherche utilisée dans WhatsAppService.php (lignes 231, 430, 1468)
            $foundTemplate = $templateRepository->findOneBy(['name' => $templateName]);
            
            if ($foundTemplate) {
                logMessage("✅ Template '$templateName' trouvé par son nom");
                logMessage("   Catégorie: " . ($foundTemplate->getCategory() ?? 'non définie'));
                
                // Notre fix fonctionne
                logMessage("   getTemplateId() retourne: " . $foundTemplate->getTemplateId());
                
                // Limiter à 2 templates pour éviter de surcharger les logs
                if ($index >= 1) {
                    logMessage("Affichage limité aux 2 premiers templates pour plus de clarté.");
                    break;
                }
            } else {
                logMessage("❌ Template '$templateName' NON trouvé par son nom", true);
            }
        }
        
        logMessage("✅ La recherche par nom fonctionne correctement");
    }
    
    logMessage("Test terminé avec succès");
    
} catch (\Exception $e) {
    logMessage("Erreur lors du test: " . $e->getMessage(), true);
    logMessage("Trace: " . $e->getTraceAsString(), true);
}