<?php

// Ce script teste directement le fonctionnement du WhatsAppService

// Charger le bootstrap
require_once __DIR__ . '/../src/bootstrap-rest.php';

// Fonction de log
function log_message($message) {
    echo "[LOG] " . $message . PHP_EOL;
}

try {
    log_message("Début du test de WhatsAppService");
    
    // Récupérer le service WhatsApp
    $whatsAppService = $container->get(\App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface::class);
    log_message("Service WhatsApp récupéré avec succès");
    
    // Récupérer le repository des templates
    $templateRepository = $container->get(\App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateRepositoryInterface::class);
    log_message("Repository des templates récupéré avec succès");
    
    // Vérifier si nous avons accès à la connexion à la base de données
    $conn = $container->get(\Doctrine\DBAL\Connection::class);
    log_message("Connexion à la base de données récupérée avec succès");
    
    // Exécuter une requête SQL directe pour vérifier la structure de la table
    try {
        $sql = "PRAGMA table_info(whatsapp_templates)";
        $result = $conn->executeQuery($sql)->fetchAllAssociative();
        
        log_message("Structure de la table whatsapp_templates détectée:");
        foreach ($result as $column) {
            log_message("  - " . $column['name'] . " (" . $column['type'] . ")");
        }
        
        // Vérifier s'il existe des templates dans la base
        $sql = "SELECT COUNT(*) FROM whatsapp_templates";
        $count = $conn->executeQuery($sql)->fetchOne();
        log_message("Nombre de templates dans la base de données: " . $count);
        
        if ($count > 0) {
            // Récupérer quelques templates pour les tests
            $sql = "SELECT id, name, language, status, category FROM whatsapp_templates LIMIT 2";
            $templates = $conn->executeQuery($sql)->fetchAllAssociative();
            
            foreach ($templates as $template) {
                log_message("Template trouvé: ID=" . $template['id'] . ", Nom=" . $template['name'] . ", Langue=" . $template['language']);
                
                // Tester findOneBy avec le nom du template
                $foundTemplate = $templateRepository->findOneBy(['name' => $template['name']]);
                
                if ($foundTemplate) {
                    log_message("✅ Succès: Template trouvé par son nom via findOneBy");
                    log_message("  - ID: " . $foundTemplate->getId());
                    log_message("  - Nom: " . $foundTemplate->getName());
                    log_message("  - Langue: " . $foundTemplate->getLanguage());
                    log_message("  - Catégorie: " . $foundTemplate->getCategory());
                    log_message("  - getTemplateId(): " . $foundTemplate->getTemplateId());
                } else {
                    log_message("❌ Échec: Template non trouvé par son nom via findOneBy");
                }
            }
        } else {
            log_message("Aucun template trouvé dans la base de données pour tester");
        }
        
    } catch (\Exception $e) {
        log_message("Erreur lors de la requête SQL: " . $e->getMessage());
    }
    
    log_message("Test terminé avec succès");
    
} catch (\Exception $e) {
    log_message("Erreur: " . $e->getMessage());
    log_message("Trace: " . $e->getTraceAsString());
}