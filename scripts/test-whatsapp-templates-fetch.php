<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Gestion des arguments de la ligne de commande
$options = getopt('c::d::v::h', ['config::', 'debug::', 'verbose::', 'help', 'curl-only', 'direct-only']);

// Afficher l'aide si demandé
if (isset($options['h']) || isset($options['help'])) {
    echo "Usage: php test-whatsapp-templates-fetch.php [options]\n";
    echo "Options:\n";
    echo "  -c, --config=FILE   Utiliser un fichier de configuration spécifique\n";
    echo "  -d, --debug         Activer le mode débogage avec logs détaillés\n";
    echo "  -v, --verbose       Activer le mode verbeux\n";
    echo "  --curl-only         Utiliser uniquement la méthode cURL directe\n";
    echo "  --direct-only       Tester uniquement l'API directe, pas le service\n";
    echo "  -h, --help          Afficher cette aide\n";
    exit(0);
}

// Déterminer les options
$debugMode = isset($options['d']) || isset($options['debug']);
$verboseMode = isset($options['v']) || isset($options['verbose']);
$curlOnly = isset($options['curl-only']);
$directOnly = isset($options['direct-only']);

// Afficher un message de début
echo "==== Test de l'API WhatsApp Templates ====\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "Mode Debug: " . ($debugMode ? 'Activé' : 'Désactivé') . "\n";
echo "Mode Verbeux: " . ($verboseMode ? 'Activé' : 'Désactivé') . "\n";
echo "Méthode cURL uniquement: " . ($curlOnly ? 'Oui' : 'Non') . "\n";
echo "Test API directe uniquement: " . ($directOnly ? 'Oui' : 'Non') . "\n\n";

try {
    // Chargement du conteneur DI
    $containerBuilder = new \DI\ContainerBuilder();
    $containerBuilder->addDefinitions(__DIR__ . '/../src/config/di.php');
    $container = $containerBuilder->build();
    
    // Configuration du logger - Si debug activé, utiliser echo logger
    if ($debugMode) {
        // Créer un logger qui affiche tout à l'écran
        $logger = new class() extends \Psr\Log\AbstractLogger {
            public function log($level, string|\Stringable $message, array $context = []): void {
                echo "[" . strtoupper($level) . "] " . $message . "\n";
                if (!empty($context)) {
                    echo "Context: " . json_encode($context, JSON_PRETTY_PRINT) . "\n";
                }
            }
        };
    } else {
        $logger = $container->get(\Psr\Log\LoggerInterface::class);
    }
    
    // Obtenir les services nécessaires
    $config = $container->get('whatsapp.config');
    
    // Afficher la configuration actuelle
    echo "Configuration WhatsApp:\n";
    echo "- API Version: " . $config['api_version'] . "\n";
    echo "- WABA ID: " . $config['whatsapp_business_account_id'] . "\n";
    echo "- Phone Number ID: " . $config['phone_number_id'] . "\n";
    echo "- Access Token Length: " . (strlen($config['access_token']) > 0 ? strlen($config['access_token']) . " caractères" : "Non défini") . "\n\n";
    
    if ($curlOnly) {
        // Test direct avec cURL
        echo "=== Test direct API avec cURL ===\n";
        
        $url = 'https://graph.facebook.com/' . 
            $config['api_version'] . '/' . 
            $config['whatsapp_business_account_id'] . 
            '/message_templates?limit=100';
        
        echo "URL: " . $url . "\n";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $config['access_token'],
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        echo "HTTP Code: " . $httpCode . "\n";
        
        if ($error) {
            echo "cURL Error: " . $error . "\n";
        } else {
            echo "Response received (" . strlen($response) . " bytes)\n";
            
            if ($verboseMode) {
                echo "Raw Response:\n" . $response . "\n\n";
            }
            
            $result = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo "JSON Decode Error: " . json_last_error_msg() . "\n";
            } else {
                echo "JSON decoded successfully.\n";
                
                if (isset($result['data']) && is_array($result['data'])) {
                    echo "Nombre de templates récupérés: " . count($result['data']) . "\n";
                    
                    if (!empty($result['data'])) {
                        echo "Premier template:\n";
                        if ($verboseMode) {
                            print_r($result['data'][0]);
                        } else {
                            echo "ID: " . ($result['data'][0]['id'] ?? 'N/A') . "\n";
                            echo "Name: " . ($result['data'][0]['name'] ?? 'N/A') . "\n";
                            echo "Category: " . ($result['data'][0]['category'] ?? 'N/A') . "\n";
                            echo "Language: " . ($result['data'][0]['language'] ?? 'N/A') . "\n";
                        }
                    } else {
                        echo "Aucun template trouvé.\n";
                    }
                } else {
                    echo "La clé 'data' n'existe pas ou n'est pas un tableau.\n";
                    if (isset($result['error'])) {
                        echo "Erreur API: " . json_encode($result['error'], JSON_PRETTY_PRINT) . "\n";
                    } else {
                        echo "Structure de la réponse: " . json_encode(array_keys($result)) . "\n";
                    }
                }
            }
        }
        
        exit(0);
    }
    
    // Créer l'API client
    $apiClient = $container->get(\App\Services\Interfaces\WhatsApp\WhatsAppApiClientInterface::class);
    $templateService = $container->get(\App\Services\Interfaces\WhatsApp\WhatsAppTemplateServiceInterface::class);
    
    // Création d'un utilisateur factice pour les tests
    $userEntity = new \App\Entities\User();
    $userEntity->setUsername('test_user');
    $userEntity->setApiKey('test_api_key');
    
    // Test de récupération directe des templates via l'API
    if (!$directOnly) {
        echo "\n=== Test API Client getTemplates() ===\n";
        $startTime = microtime(true);
        
        try {
            $templates = $apiClient->getTemplates();
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);
            
            echo "✅ SUCCÈS en {$executionTime} secondes\n";
            echo "Nombre de templates récupérés: " . count($templates) . "\n";
            
            if (!empty($templates)) {
                echo "Premier template:\n";
                if ($verboseMode) {
                    print_r($templates[0]);
                } else {
                    echo "ID: " . ($templates[0]['id'] ?? 'N/A') . "\n";
                    echo "Name: " . ($templates[0]['name'] ?? 'N/A') . "\n";
                    echo "Category: " . ($templates[0]['category'] ?? 'N/A') . "\n";
                    echo "Language: " . ($templates[0]['language'] ?? 'N/A') . "\n";
                }
            } else {
                echo "Aucun template trouvé.\n";
            }
        } catch (\Exception $e) {
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);
            
            echo "❌ ÉCHEC en {$executionTime} secondes\n";
            echo "Erreur: " . $e->getMessage() . "\n";
            
            if ($debugMode) {
                echo "Trace:\n" . $e->getTraceAsString() . "\n";
            }
        }
    }
    
    // Test de récupération via le service
    echo "\n=== Test Template Service fetchApprovedTemplatesFromMeta() ===\n";
    $startTime = microtime(true);
    
    try {
        $approvedTemplates = $templateService->fetchApprovedTemplatesFromMeta();
        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 2);
        
        echo "✅ SUCCÈS en {$executionTime} secondes\n";
        echo "Nombre de templates approuvés: " . count($approvedTemplates) . "\n";
        
        if (!empty($approvedTemplates)) {
            echo "Premier template approuvé:\n";
            if ($verboseMode) {
                print_r($approvedTemplates[0]);
            } else {
                echo "ID: " . ($approvedTemplates[0]['id'] ?? 'N/A') . "\n";
                echo "Name: " . ($approvedTemplates[0]['name'] ?? 'N/A') . "\n";
                echo "Category: " . ($approvedTemplates[0]['category'] ?? 'N/A') . "\n";
                echo "Language: " . ($approvedTemplates[0]['language'] ?? 'N/A') . "\n";
                echo "Status: " . ($approvedTemplates[0]['status'] ?? 'N/A') . "\n";
            }
            
            // Affiche les métadonnées enrichies
            echo "\nMétadonnées enrichies:\n";
            echo "- Has Media Header: " . ($approvedTemplates[0]['hasMediaHeader'] ? 'Oui' : 'Non') . "\n";
            echo "- Body Variables Count: " . ($approvedTemplates[0]['bodyVariablesCount'] ?? 0) . "\n";
            echo "- Has Buttons: " . ($approvedTemplates[0]['hasButtons'] ? 'Oui' : 'Non') . "\n";
            echo "- Buttons Count: " . ($approvedTemplates[0]['buttonsCount'] ?? 0) . "\n";
            
            // Vérifier si on peut convertir en WhatsAppTemplateType
            if (!$directOnly) {
                echo "\n=== Test de conversion en WhatsAppTemplateType ===\n";
                try {
                    $templateType = new \App\GraphQL\Types\WhatsApp\WhatsAppTemplateType($approvedTemplates[0]);
                    
                    echo "✅ Conversion réussie\n";
                    echo "Propriétés:\n";
                    echo "- ID: " . $templateType->getId() . "\n";
                    echo "- Nom: " . $templateType->getName() . "\n";
                    echo "- Langue: " . $templateType->getLanguage() . "\n";
                    echo "- Catégorie: " . $templateType->getCategory() . "\n";
                } catch (\Exception $e) {
                    echo "❌ Échec de la conversion\n";
                    echo "Erreur: " . $e->getMessage() . "\n";
                    
                    if ($debugMode) {
                        echo "Trace:\n" . $e->getTraceAsString() . "\n";
                    }
                }
            }
        } else {
            echo "Aucun template approuvé trouvé.\n";
        }
    } catch (\Exception $e) {
        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 2);
        
        echo "❌ ÉCHEC en {$executionTime} secondes\n";
        echo "Erreur: " . $e->getMessage() . "\n";
        
        if ($debugMode) {
            echo "Trace:\n" . $e->getTraceAsString() . "\n";
        }
    }
    
    echo "\n=== Test terminé ===\n";
    
} catch (\Exception $e) {
    echo "ERREUR CRITIQUE: " . $e->getMessage() . "\n";
    
    if ($debugMode) {
        echo "Trace:\n" . $e->getTraceAsString() . "\n";
    }
    
    exit(1);
}