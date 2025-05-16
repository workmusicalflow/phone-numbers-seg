<?php
/**
 * Script de test direct pour les templates WhatsApp
 * 
 * Ce script teste uniquement la récupération directe par SQL, ce qui ne nécessite
 * pas l'EntityManager ou le container Doctrine.
 */

// Configurer les chemins de journalisation
$logFile = __DIR__ . '/../var/logs/test-templates-fix.log';
file_put_contents($logFile, "=== TEST DE RÉCUPÉRATION DIRECTE DES TEMPLATES WHATSAPP ===\n" . date('Y-m-d H:i:s') . "\n\n", FILE_APPEND);

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
        $db = new SQLite3(__DIR__ . '/../var/data.db');
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

// Fonction pour tester l'API d'urgence
function test_emergency_api($userId = 2) {
    log_message("TEST 2: Récupération via API d'urgence pour userId=$userId");
    
    try {
        // Utilisez cette URL pour un test sur localhost, modifiez selon votre environnement
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
log_message("DÉBUT DES TESTS DIRECTS");

// Tester avec l'ID utilisateur 2 (testuser)
$sqlTemplates = test_direct_sql(2);

// Si vous avez un serveur web en cours d'exécution, décommentez cette ligne
// $apiTemplates = test_emergency_api(2);

// Résumé des résultats
log_message("\nRÉSUMÉ DES RÉSULTATS POUR USERID=2:");
log_message("SQL Direct: " . count($sqlTemplates) . " templates");
// log_message("API d'urgence: " . count($apiTemplates) . " templates");

log_message("\nTESTS DIRECTS TERMINÉS");

// Afficher les templates trouvés
echo "=== TEMPLATES TROUVÉS EN BASE DE DONNÉES POUR L'UTILISATEUR 2 ===\n";
foreach ($sqlTemplates as $i => $template) {
    echo ($i+1) . ". " . $template['templateName'] . " (Lang: " . $template['languageCode'] . ")\n";
}

echo "\nTests terminés. Résultats dans $logFile\n";