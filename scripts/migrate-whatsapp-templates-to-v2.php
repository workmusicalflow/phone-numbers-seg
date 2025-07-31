<?php

declare(strict_types=1);

$entityManager = require __DIR__ . '/../src/bootstrap-doctrine-simple.php';

use App\Entities\WhatsApp\WhatsAppTemplate;

echo "=== Migration des templates WhatsApp vers le format API V2 ===\n";

try {
    // Récupérer tous les templates WhatsApp
    $templates = $entityManager->getRepository(WhatsAppTemplate::class)->findAll();
    
    echo "Nombre total de templates trouvés: " . count($templates) . "\n";
    
    $migratedCount = 0;
    $failedCount = 0;
    $alreadyV2Count = 0;
    
    foreach ($templates as $template) {
        $templateName = $template->getName();
        $templateLanguage = $template->getLanguage();
        
        echo "\nTraitement du template: $templateName ($templateLanguage)\n";
        
        // Extraire le JSON des composants
        $componentsJson = $template->getComponentsJson();
        if (empty($componentsJson)) {
            // Générer le JSON des composants si nécessaire
            $componentsJson = generateComponentsJsonFromFields($template);
            if ($componentsJson) {
                echo "- Génération du JSON des composants depuis les champs\n";
            } else {
                echo "- ERREUR: Impossible de générer le JSON des composants\n";
                $failedCount++;
                continue;
            }
        }
        
        // Vérifier si le JSON est déjà au format V2
        $isAlreadyV2 = isJsonFormatV2($componentsJson);
        if ($isAlreadyV2) {
            echo "- Le template est déjà au format V2\n";
            $alreadyV2Count++;
            continue;
        }
        
        // Convertir le JSON au format V2
        $v2ComponentsJson = convertToV2Format($template, $componentsJson);
        if (!$v2ComponentsJson) {
            echo "- ERREUR: Échec de la conversion au format V2\n";
            $failedCount++;
            continue;
        }
        
        // Mettre à jour le template avec le nouveau format
        $template->setComponentsJson($v2ComponentsJson);
        $template->setApiVersion('v2');
        
        $entityManager->persist($template);
        echo "- Template migré avec succès vers le format V2\n";
        $migratedCount++;
    }
    
    // Appliquer les changements
    $entityManager->flush();
    
    echo "\n=== Migration terminée ===\n";
    echo "- Templates migrés: $migratedCount\n";
    echo "- Templates déjà au format V2: $alreadyV2Count\n";
    echo "- Échecs: $failedCount\n";
    
} catch (\Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

/**
 * Détermine si un JSON de composants est déjà au format V2 de l'API Meta
 */
function isJsonFormatV2(string $componentsJson): bool {
    try {
        $components = json_decode($componentsJson, true);
        
        // Vérifier si c'est un tableau
        if (!is_array($components)) {
            return false;
        }
        
        // Vérifications de format V2
        foreach ($components as $component) {
            // Vérifier la présence de la clé 'parameters'
            if (isset($component['parameters']) && is_array($component['parameters'])) {
                // Au moins un composant a un format Meta Cloud API
                return true;
            }
        }
        
        return false;
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * Génère le JSON des composants à partir des champs standards d'un template
 */
function generateComponentsJsonFromFields(WhatsAppTemplate $template): string {
    $components = [];
    
    // Header
    $headerFormat = $template->getHeaderFormat();
    if ($headerFormat && $headerFormat !== WhatsAppTemplate::HEADER_FORMAT_NONE) {
        $headerComponent = [
            'type' => 'HEADER',
            'format' => $headerFormat
        ];
        
        if ($headerFormat === WhatsAppTemplate::HEADER_FORMAT_TEXT) {
            $headerComponent['text'] = $template->getHeaderText() ?? '';
        }
        
        $components[] = $headerComponent;
    }
    
    // Body
    $bodyText = $template->getBodyText();
    if ($bodyText) {
        $components[] = [
            'type' => 'BODY',
            'text' => $bodyText
        ];
    }
    
    // Footer
    $footerText = $template->getFooterText();
    if ($footerText) {
        $components[] = [
            'type' => 'FOOTER',
            'text' => $footerText
        ];
    }
    
    if (empty($components)) {
        return '';
    }
    
    return json_encode($components);
}

/**
 * Convertit un JSON de composants du format V1 au format V2
 */
function convertToV2Format(WhatsAppTemplate $template, string $componentsJson): ?string {
    try {
        $components = json_decode($componentsJson, true);
        
        // Si le format n'est pas un tableau, le convertir
        if (!is_array($components)) {
            echo "- Conversion d'un format objet en tableau\n";
            // Certains templates peuvent avoir un format d'objet au lieu d'un tableau
            $componentsArray = [];
            foreach ($components as $key => $value) {
                $componentsArray[] = [
                    'type' => strtoupper($key),
                    ...$value
                ];
            }
            $components = $componentsArray;
        }
        
        $v2Components = [];
        
        foreach ($components as $component) {
            $componentType = strtoupper($component['type'] ?? '');
            
            switch ($componentType) {
                case 'HEADER':
                    $v2Components[] = convertHeaderComponentToV2($component);
                    break;
                    
                case 'BODY':
                    $v2Components[] = convertBodyComponentToV2($component);
                    break;
                    
                case 'FOOTER':
                    // Les footers n'ont pas de paramètres, on les garde tels quels
                    $v2Components[] = $component;
                    break;
                    
                case 'BUTTONS':
                    // Les boutons ne sont pas inclus dans cette version V2
                    break;
                    
                default:
                    // Composant inconnu, on le garde tel quel
                    $v2Components[] = $component;
            }
        }
        
        return json_encode($v2Components);
    } catch (\Exception $e) {
        echo "- Erreur lors de la conversion au format V2: " . $e->getMessage() . "\n";
        return null;
    }
}

/**
 * Convertit un composant d'en-tête au format V2
 */
function convertHeaderComponentToV2(array $component): array {
    $headerFormat = strtoupper($component['format'] ?? 'TEXT');
    $v2Component = [
        'type' => 'HEADER'
    ];
    
    // Ajouter espace pour les paramètres selon le format
    switch ($headerFormat) {
        case 'TEXT':
            if (isset($component['text'])) {
                $v2Component['text'] = $component['text'];
            }
            break;
            
        case 'IMAGE':
        case 'VIDEO':
        case 'DOCUMENT':
            $v2Component['format'] = $headerFormat;
            // Pour les médias, nous n'ajoutons pas de paramètres par défaut
            // car ils seront ajoutés dynamiquement lors de l'utilisation
            break;
    }
    
    return $v2Component;
}

/**
 * Convertit un composant de corps au format V2
 */
function convertBodyComponentToV2(array $component): array {
    $v2Component = [
        'type' => 'BODY'
    ];
    
    if (isset($component['text'])) {
        $v2Component['text'] = $component['text'];
        
        // Extraire les variables pour documentation (on ne les ajoute pas comme paramètres)
        $variables = extractVariablesFromText($component['text']);
        if (!empty($variables)) {
            echo "- Variables détectées dans le corps: " . implode(', ', $variables) . "\n";
        }
    }
    
    return $v2Component;
}

/**
 * Extrait les variables d'un texte avec le motif {{N}}
 */
function extractVariablesFromText(string $text): array {
    $variables = [];
    
    preg_match_all('/{{(\d+)}}/', $text, $matches);
    
    if (!empty($matches[1])) {
        $variables = array_unique($matches[1]);
    }
    
    return $variables;
}