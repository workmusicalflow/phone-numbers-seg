<?php

declare(strict_types=1);

// Définir directement les constantes de configuration
$WHATSAPP_API_VERSION = 'v22.0';
$WHATSAPP_WABA_ID = '664409593123173';
$WHATSAPP_ACCESS_TOKEN = 'EAAQ93dlFUw4BOZCu6OPmzQuo47pE8eYgGCJLWaQzeyHo03ZCmUWNOQZABt0NeJgVfx9zgurvJc3YynNmFZBgfsCslzydmfzdWZA3onZCyGQsgSo1ZAC6o7ZCgzukF10wmeCjfWcWItPeOw0hanzT0V5ShOIQZCEzVF9qP2aGALaD5ZCTvy95DhjlUwOwijVNAEXpGzEG0YKIsRI8ZCngj9BiXLltt3azinQQYgPBIs9bZA6K';

echo "=== Test de récupération des templates WhatsApp depuis l'API Cloud Meta ===\n\n";

// 1. Récupérer les templates depuis l'API Cloud
function fetchTemplatesFromMeta($wabaId, $apiVersion, $accessToken) {
    $url = "https://graph.facebook.com/{$apiVersion}/{$wabaId}/message_templates";
    $ch = curl_init($url);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($httpCode !== 200) {
        echo "Erreur HTTP {$httpCode} lors de la récupération des templates\n";
        if ($response) {
            echo "Réponse: " . $response . "\n";
        }
        curl_close($ch);
        return null;
    }
    
    curl_close($ch);
    return json_decode($response, true);
}

// 2. Récupérer les détails d'un template spécifique (pour plus d'informations)
function fetchTemplateDetails($templateId, $apiVersion, $accessToken) {
    $url = "https://graph.facebook.com/{$apiVersion}/{$templateId}?fields=name,components,language,status,category";
    $ch = curl_init($url);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($httpCode !== 200) {
        echo "Erreur HTTP {$httpCode} lors de la récupération des détails du template\n";
        if ($response) {
            echo "Réponse: " . $response . "\n";
        }
        curl_close($ch);
        return null;
    }
    
    curl_close($ch);
    return json_decode($response, true);
}

// Fonction pour afficher les composants d'un template de manière lisible
function displayTemplateComponents($components) {
    if (!$components || !is_array($components)) {
        return "Aucun composant défini";
    }
    
    $output = [];
    
    foreach ($components as $component) {
        $type = $component['type'] ?? 'INCONNU';
        $result = "[$type] ";
        
        switch ($type) {
            case 'HEADER':
                $format = $component['format'] ?? 'TEXT';
                $result .= "Format: $format";
                if ($format === 'TEXT' && isset($component['text'])) {
                    $result .= " - Texte: " . $component['text'];
                }
                break;
                
            case 'BODY':
                if (isset($component['text'])) {
                    // Compter les variables {{n}}
                    preg_match_all('/{{[0-9]+}}/', $component['text'], $matches);
                    $varCount = count($matches[0]);
                    
                    $text = $component['text'];
                    if (strlen($text) > 70) {
                        $text = substr($text, 0, 67) . '...';
                    }
                    
                    $result .= "Texte: \"$text\" ($varCount variables)";
                }
                break;
                
            case 'FOOTER':
                if (isset($component['text'])) {
                    $result .= "Texte: \"" . $component['text'] . "\"";
                }
                break;
                
            case 'BUTTONS':
                if (isset($component['buttons']) && is_array($component['buttons'])) {
                    $buttonCount = count($component['buttons']);
                    $result .= "$buttonCount boutons: ";
                    
                    $buttonDetails = [];
                    foreach ($component['buttons'] as $button) {
                        $buttonType = $button['type'] ?? 'INCONNU';
                        $buttonText = $button['text'] ?? '';
                        $buttonDetails[] = "$buttonType \"$buttonText\"";
                    }
                    
                    $result .= implode(', ', $buttonDetails);
                }
                break;
                
            default:
                $result .= json_encode($component);
        }
        
        $output[] = $result;
    }
    
    return implode("\n      ", $output);
}

// Fonction pour afficher un template de manière lisible
function displayTemplate($template, $detailed = false) {
    $name = $template['name'] ?? 'Inconnu';
    $id = $template['id'] ?? '';
    $status = $template['status'] ?? 'INCONNU';
    $category = $template['category'] ?? 'STANDARD';
    $language = $template['language'] ?? '';
    
    echo "  • Template: $name ($id)\n";
    echo "    - Statut: $status\n";
    echo "    - Catégorie: $category\n";
    echo "    - Langue: $language\n";
    
    if ($detailed && isset($template['components'])) {
        echo "    - Composants:\n      " . displayTemplateComponents($template['components']) . "\n";
    }
    
    echo "\n";
}

try {
    // Étape 1: Récupérer la liste des templates disponibles
    echo "1. Récupération de la liste des templates disponibles...\n\n";
    
    $templatesResponse = fetchTemplatesFromMeta($WHATSAPP_WABA_ID, $WHATSAPP_API_VERSION, $WHATSAPP_ACCESS_TOKEN);
    
    if (!$templatesResponse || !isset($templatesResponse['data'])) {
        echo "Aucun template trouvé ou erreur de récupération.\n";
        if ($templatesResponse) {
            echo "Réponse: " . json_encode($templatesResponse, JSON_PRETTY_PRINT) . "\n";
        }
        exit(1);
    }
    
    $templates = $templatesResponse['data'];
    $templateCount = count($templates);
    
    echo "Nombre de templates trouvés: $templateCount\n\n";
    echo "Liste des templates:\n";
    
    // Grouper les templates par catégorie pour une meilleure lisibilité
    $templatesByCategory = [];
    foreach ($templates as $template) {
        $category = $template['category'] ?? 'STANDARD';
        if (!isset($templatesByCategory[$category])) {
            $templatesByCategory[$category] = [];
        }
        $templatesByCategory[$category][] = $template;
    }
    
    foreach ($templatesByCategory as $category => $categoryTemplates) {
        echo "\n=== Catégorie: $category (" . count($categoryTemplates) . " templates) ===\n\n";
        
        foreach ($categoryTemplates as $template) {
            displayTemplate($template);
        }
    }
    
    // Étape 2: Récupérer les détails de quelques templates (limité à 3 pour éviter trop de requêtes)
    echo "\n2. Récupération des détails de quelques templates...\n\n";
    
    $detailedTemplateCount = min(3, $templateCount);
    $sampleTemplates = array_slice($templates, 0, $detailedTemplateCount);
    
    foreach ($sampleTemplates as $template) {
        $templateId = $template['id'];
        echo "Détails du template: " . $template['name'] . "\n";
        
        $templateDetails = fetchTemplateDetails($templateId, $WHATSAPP_API_VERSION, $WHATSAPP_ACCESS_TOKEN);
        
        if ($templateDetails) {
            displayTemplate($templateDetails, true);
        } else {
            echo "  Impossible de récupérer les détails de ce template.\n\n";
        }
    }
    
    echo "=== Test terminé avec succès ===\n";
    
} catch (\Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}