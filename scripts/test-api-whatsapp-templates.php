<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Gestion des arguments de la ligne de commande
$options = getopt('u:p:dv', ['url:', 'params:', 'debug', 'verbose']);

// Configuration par défaut
$url = isset($options['u']) ? $options['u'] : (isset($options['url']) ? $options['url'] : '/api/whatsapp/templates/approved.php');
$params = isset($options['p']) ? $options['p'] : (isset($options['params']) ? $options['params'] : 'force_meta=true&force_refresh=true&use_cache=false&debug=true');
$debugMode = isset($options['d']) || isset($options['debug']);
$verboseMode = isset($options['v']) || isset($options['verbose']);

// Vérifier si l'URL est relative ou absolue
if (strpos($url, 'http') !== 0) {
    // URL relative, construire l'URL complète
    $baseUrl = 'http://localhost';
    $url = rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
}

// Ajouter les paramètres à l'URL
$url .= (strpos($url, '?') === false ? '?' : '&') . $params;

// Afficher le message de début
echo "==== Test API WhatsApp Templates ====\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "URL: " . $url . "\n";
echo "Mode Debug: " . ($debugMode ? 'Activé' : 'Désactivé') . "\n";
echo "Mode Verbeux: " . ($verboseMode ? 'Activé' : 'Désactivé') . "\n\n";

// Effectuer la requête HTTP
$startTime = microtime(true);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

// Exécution de la requête
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$executionTime = microtime(true) - $startTime;

curl_close($ch);

// Afficher les résultats
echo "=== Résultats de la requête ===\n";
echo "Durée: " . round($executionTime, 2) . " secondes\n";
echo "HTTP Code: " . $httpCode . "\n";
echo "Content-Type: " . $contentType . "\n";

if ($error) {
    echo "ERREUR: " . $error . "\n";
    exit(1);
}

echo "Taille de la réponse: " . strlen($response) . " octets\n\n";

// Si la réponse est du JSON, la décoder et afficher les informations
if (strpos($contentType, 'application/json') !== false || strpos($contentType, 'text/json') !== false) {
    $result = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "ERREUR: Impossible de décoder la réponse JSON: " . json_last_error_msg() . "\n";
        
        if ($debugMode) {
            echo "Réponse brute:\n" . $response . "\n";
        }
        
        exit(1);
    }
    
    echo "=== Analyse de la réponse ===\n";
    
    // Afficher le statut global
    echo "Statut: " . ($result['status'] ?? 'Non spécifié') . "\n";
    
    // Afficher les métadonnées
    if (isset($result['meta'])) {
        echo "Source: " . ($result['meta']['source'] ?? 'Non spécifiée') . "\n";
        echo "Fallback utilisé: " . (isset($result['meta']['usedFallback']) && $result['meta']['usedFallback'] ? 'Oui' : 'Non') . "\n";
        echo "Timestamp: " . ($result['meta']['timestamp'] ?? 'Non spécifié') . "\n";
    }
    
    // Afficher les templates
    if (isset($result['templates']) && is_array($result['templates'])) {
        $count = count($result['templates']);
        echo "Nombre de templates: " . $count . "\n";
        
        if ($count > 0 && ($verboseMode || $debugMode)) {
            // Afficher les détails du premier template
            echo "\nPremier template:\n";
            $template = $result['templates'][0];
            
            echo "- ID: " . ($template['id'] ?? 'Non spécifié') . "\n";
            echo "- Nom: " . ($template['name'] ?? 'Non spécifié') . "\n";
            echo "- Catégorie: " . ($template['category'] ?? 'Non spécifiée') . "\n";
            echo "- Langue: " . ($template['language'] ?? 'Non spécifiée') . "\n";
            echo "- Statut: " . ($template['status'] ?? 'Non spécifié') . "\n";
            
            echo "- Variables dans le corps: " . ($template['bodyVariablesCount'] ?? 'Non spécifié') . "\n";
            echo "- En-tête média: " . (isset($template['hasMediaHeader']) && $template['hasMediaHeader'] ? 'Oui' : 'Non') . "\n";
            echo "- Boutons: " . (isset($template['hasButtons']) && $template['hasButtons'] ? 'Oui (' . ($template['buttonsCount'] ?? '?') . ')' : 'Non') . "\n";
            
            if ($verboseMode) {
                echo "\nStructure complète du premier template:\n";
                print_r($template);
            }
            
            // Afficher les noms de tous les templates
            echo "\nNoms de tous les templates:\n";
            $templateNames = array_map(function($t) {
                return ($t['name'] ?? 'Inconnu') . ' (' . ($t['language'] ?? '?') . ')';
            }, $result['templates']);
            
            echo implode(', ', $templateNames) . "\n";
        }
    } else {
        echo "Aucun template trouvé dans la réponse.\n";
    }
    
    // Afficher les messages d'erreur ou d'avertissement
    if (isset($result['message'])) {
        echo "\nMessage: " . $result['message'] . "\n";
    }
    
    if (isset($result['warning'])) {
        echo "\nAvertissement: " . $result['warning'] . "\n";
    }
    
    if (isset($result['notice'])) {
        echo "\nNotice: " . $result['notice'] . "\n";
    }
    
    if (isset($result['error_code'])) {
        echo "\nCode d'erreur: " . $result['error_code'] . "\n";
    }
    
    // Afficher la réponse brute en mode debug
    if ($debugMode) {
        echo "\n=== Réponse JSON complète ===\n";
        print_r($result);
    }
} else {
    // Si la réponse n'est pas du JSON, afficher la réponse brute
    echo "La réponse n'est pas au format JSON.\n";
    echo "Réponse brute:\n" . $response . "\n";
}

echo "\n=== Test terminé ===\n";