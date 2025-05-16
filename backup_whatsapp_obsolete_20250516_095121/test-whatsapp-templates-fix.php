<?php
/**
 * Script de test pour les templates WhatsApp
 * 
 * Ce script teste les différentes méthodes de récupération des templates,
 * pour confirmer que notre solution fonctionne correctement.
 */

require_once __DIR__ . '/../src/bootstrap-doctrine.php';

// Configurer les chemins de journalisation
$logFile = __DIR__ . '/../var/logs/test-templates-fix.log';
file_put_contents($logFile, "=== TEST DE RÉCUPÉRATION DES TEMPLATES WHATSAPP ===\n" . date('Y-m-d H:i:s') . "\n\n", FILE_APPEND);

// Fonction de journalisation
function log_message($message, $data = []) {
    global $logFile;
    file_put_contents(
        $logFile, 
        date('Y-m-d H:i:s') . " - $message" . 
        (empty($data) ? "" : " - " . json_encode($data, JSON_PRETTY_PRINT)) . 
        "\n", 
        FILE_APPEND
    );
}

// Fonction pour tester la récupération directe par SQL
function test_direct_sql($userId = 2) {
    log_message("TEST 1: Récupération directe par SQL pour userId=$userId");
    
    try {
        $db = new SQLite3(__DIR__ . '/../var/database.sqlite');
        $query = "SELECT 
            id, user_id as userId, template_name as templateName, 
            language_code as languageCode, body_variables_count as bodyVariablesCount,
            has_header_media as hasHeaderMedia, is_special_template as isSpecialTemplate,
            header_media_url as headerMediaUrl, created_at as createdAt, 
            updated_at as updatedAt 
        FROM whatsapp_user_templates 
        WHERE user_id = :userId 
        ORDER BY created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        $templates = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $templates[] = $row;
        }
        
        log_message("Résultat SQL direct: " . count($templates) . " templates trouvés");
        
        if (count($templates) > 0) {
            log_message("Premier template:", $templates[0]);
        } else {
            log_message("Aucun template trouvé");
        }
        
        return $templates;
    } catch (Exception $e) {
        log_message("ERREUR SQL: " . $e->getMessage());
        return [];
    }
}

// Fonction pour tester le repository Doctrine
function test_doctrine_repository($userId = 2) {
    log_message("TEST 2: Récupération via Repository Doctrine pour userId=$userId");
    
    try {
        global $entityManager;
        $repository = $entityManager->getRepository('App\Entities\WhatsApp\WhatsAppUserTemplate');
        
        // Vérifions d'abord quelle classe est utilisée pour ce repository
        $repoClass = get_class($repository);
        log_message("Classe de repository utilisée: $repoClass");
        
        // Vérifions si nous avons bien la méthode findByUser ou si nous utilisons findBy
        if (method_exists($repository, 'findByUser')) {
            log_message("Utilisation de la méthode findByUser");
            $templates = $repository->findByUser($userId);
        } else {
            log_message("Méthode findByUser non disponible, utilisation de findBy");
            $user = $entityManager->getRepository('App\Entities\User')->find($userId);
            if ($user) {
                $templates = $repository->findBy(['user' => $user]);
            } else {
                $templates = [];
                log_message("Utilisateur ID=$userId non trouvé");
            }
        }
        
        log_message("Résultat Repository: " . count($templates) . " templates trouvés");
        
        if (count($templates) > 0) {
            $firstTemplate = $templates[0];
            $templateData = [
                'id' => $firstTemplate->getId(),
                'templateName' => $firstTemplate->getTemplateName(),
                'languageCode' => $firstTemplate->getLanguageCode()
            ];
            log_message("Premier template:", $templateData);
        } else {
            log_message("Aucun template trouvé");
        }
        
        return $templates;
    } catch (Exception $e) {
        log_message("ERREUR REPOSITORY: " . $e->getMessage());
        log_message("Trace: " . $e->getTraceAsString());
        return [];
    }
}

// Fonction pour tester le service d'interface
function test_service_interface($userId = 2) {
    log_message("TEST 3: Récupération via Service pour userId=$userId");
    
    try {
        global $container;
        $service = $container->get('App\Services\Interfaces\WhatsApp\WhatsAppUserTemplateServiceInterface');
        
        $serviceClass = get_class($service);
        log_message("Classe de service utilisée: $serviceClass");
        
        if (method_exists($service, 'getTemplatesByUser')) {
            log_message("Utilisation de la méthode getTemplatesByUser");
            $templates = $service->getTemplatesByUser($userId);
            
            log_message("Résultat Service: " . count($templates) . " templates trouvés");
            
            if (count($templates) > 0) {
                log_message("Premier template:", $templates[0]);
            } else {
                log_message("Aucun template trouvé");
            }
            
            return $templates;
        } else {
            log_message("Méthode getTemplatesByUser non disponible dans le service");
            return [];
        }
    } catch (Exception $e) {
        log_message("ERREUR SERVICE: " . $e->getMessage());
        return [];
    }
}

// Fonction pour tester l'API d'urgence
function test_emergency_api($userId = 2) {
    log_message("TEST 4: Récupération via API d'urgence pour userId=$userId");
    
    try {
        $apiUrl = "http://localhost/emergency-whatsapp-templates.php?userId=$userId";
        log_message("URL de l'API: $apiUrl");
        
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => 'Content-Type: application/json',
                'ignore_errors' => true
            ]
        ];
        
        $context = stream_context_create($options);
        $response = file_get_contents($apiUrl, false, $context);
        
        if ($response === false) {
            log_message("ERREUR API: Impossible d'accéder à l'API d'urgence");
            return [];
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            log_message("ERREUR API: Réponse JSON invalide - " . json_last_error_msg());
            return [];
        }
        
        if (!isset($data['data']['whatsappUserTemplates'])) {
            log_message("ERREUR API: Structure de réponse inattendue", $data);
            return [];
        }
        
        $templates = $data['data']['whatsappUserTemplates'];
        
        log_message("Résultat API: " . count($templates) . " templates trouvés");
        
        if (count($templates) > 0) {
            log_message("Premier template:", $templates[0]);
        } else {
            log_message("Aucun template trouvé");
        }
        
        return $templates;
    } catch (Exception $e) {
        log_message("ERREUR API: " . $e->getMessage());
        return [];
    }
}

// Exécuter les tests
log_message("DÉBUT DES TESTS");

// Tester avec l'ID utilisateur 2 (testuser)
$sqlTemplates = test_direct_sql(2);
$repositoryTemplates = test_doctrine_repository(2);
$serviceTemplates = test_service_interface(2);
// $apiTemplates = test_emergency_api(2);  // Nécessite un serveur web en cours d'exécution

// Résumé des résultats
log_message("\nRÉSUMÉ DES RÉSULTATS POUR USERID=2:");
log_message("SQL Direct: " . count($sqlTemplates) . " templates");
log_message("Repository Doctrine: " . count($repositoryTemplates) . " templates");
log_message("Service: " . count($serviceTemplates) . " templates");
// log_message("API d'urgence: " . count($apiTemplates) . " templates");

log_message("\nTESTS TERMINÉS");

// Afficher un message de succès
echo "Tests terminés. Résultats dans $logFile\n";