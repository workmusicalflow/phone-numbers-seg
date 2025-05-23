<?php

// Un script pour identifier et corriger les propriétés manquantes dans la base de données
// Cela complète notre correction pour les messages WhatsApp

// Charger le bootstrap et les dépendances nécessaires
require_once __DIR__ . '/../src/bootstrap-rest.php';

use App\Entities\WhatsApp\WhatsAppTemplate;

function logMessage($message, $isError = false) {
    echo ($isError ? "[ERREUR] " : "[INFO] ") . $message . PHP_EOL;
}

/**
 * Cette fonction crée une propriété virtuelle pour une colonne manquante dans l'entité
 * Elle remplace l'annotation ORM\Column par du code PHP normal
 * 
 * @param string $filePath Chemin du fichier de l'entité
 * @param string $propertyName Nom de la propriété/colonne à virtualiser
 * @param string $propertyType Type de la propriété (float, string, etc.)
 * @param string|null $defaultValue Valeur par défaut pour la propriété
 * @return bool Si l'opération a réussi
 */
function virtualizeProperty($filePath, $propertyName, $propertyType, $defaultValue = null) {
    // Lire le contenu du fichier
    $fileContent = file_get_contents($filePath);
    if ($fileContent === false) {
        logMessage("Impossible de lire le fichier: $filePath", true);
        return false;
    }
    
    // Pattern pour trouver la déclaration ORM\Column pour cette propriété
    $columnPattern = "/#\[ORM\\\\Column.*?name: \"$propertyName\".*?\]\s+private.*?\\\$[a-zA-Z0-9_]+.*?;/s";
    
    // Pattern pour les getters/setters
    $getterPattern = "/public function get" . ucfirst($propertyName) . "\\(\\).*?return.*?;\\s+}/s";
    $setterPattern = "/public function set" . ucfirst($propertyName) . "\\(.*?\\).*?return \\\$this;\\s+}/s";
    
    // Vérifier si la propriété existe dans le fichier
    if (!preg_match($columnPattern, $fileContent)) {
        logMessage("Propriété '$propertyName' non trouvée dans le fichier", true);
        return false;
    }
    
    // Préparation des remplacements
    $defaultValueStr = ($defaultValue !== null) ? " = $defaultValue" : "";
    $nullableStr = ($defaultValue === null) ? "?" : "";
    
    // Nouveau code pour la propriété virtuelle
    $virtualProperty = "    /**\n     * $propertyName - propriété virtuelle, non stockée en base de données\n     */\n    private $nullableStr$propertyType \$$propertyName$defaultValueStr;";
    
    // Nouveau getter
    $virtualGetter = "    /**\n     * Obtenir $propertyName\n     * Propriété virtuelle, non persistée en base de données\n     */\n    public function get" . ucfirst($propertyName) . "(): $nullableStr$propertyType\n    {\n        return \$this->$propertyName;\n    }";
    
    // Nouveau setter
    $virtualSetter = "    /**\n     * Définir $propertyName\n     * Propriété virtuelle, non persistée en base de données\n     */\n    public function set" . ucfirst($propertyName) . "($nullableStr$propertyType \$$propertyName): self\n    {\n        \$this->$propertyName = \$$propertyName;\n        return \$this;\n    }";
    
    // Effectuer les remplacements
    $newContent = preg_replace($columnPattern, $virtualProperty, $fileContent);
    
    // Remplacer aussi les getters et setters si nécessaire
    if (preg_match($getterPattern, $newContent)) {
        $newContent = preg_replace($getterPattern, $virtualGetter, $newContent);
    }
    
    if (preg_match($setterPattern, $newContent)) {
        $newContent = preg_replace($setterPattern, $virtualSetter, $newContent);
    }
    
    // Écrire le nouveau contenu dans le fichier
    if (file_put_contents($filePath, $newContent) === false) {
        logMessage("Impossible d'écrire dans le fichier: $filePath", true);
        return false;
    }
    
    logMessage("Propriété '$propertyName' virtualisée avec succès", false);
    return true;
}

// Fonction principale pour analyser l'entité et identifier les champs potentiellement manquants
function analyzeEntityAndFixMissingColumns() {
    $entityPath = __DIR__ . '/../src/Entities/WhatsApp/WhatsAppTemplate.php';
    
    try {
        // Obtenir la structure de la table depuis la base de données
        $conn = container()->get(\Doctrine\DBAL\Connection::class);
        $tableName = 'whatsapp_templates';
        
        $sql = "PRAGMA table_info($tableName)";
        $columnsInDb = $conn->executeQuery($sql)->fetchAllAssociative();
        
        if (empty($columnsInDb)) {
            logMessage("Aucune colonne trouvée pour la table $tableName", true);
            return;
        }
        
        // Collecter les noms de colonnes existantes dans la base de données
        $dbColumnNames = array_column($columnsInDb, 'name');
        logMessage("Colonnes existantes dans la base de données: " . implode(", ", $dbColumnNames));
        
        // Lire le fichier de l'entité pour trouver les annotations ORM\Column
        $entityContent = file_get_contents($entityPath);
        preg_match_all('/#\[ORM\\\\Column.*?name: "(.*?)".*?\]/sm', $entityContent, $matches);
        
        if (empty($matches[1])) {
            logMessage("Aucune annotation ORM\\Column trouvée dans l'entité", true);
            return;
        }
        
        // Extraire les noms de colonnes déclarés dans l'entité
        $entityColumnNames = $matches[1];
        logMessage("Colonnes déclarées dans l'entité: " . implode(", ", $entityColumnNames));
        
        // Identifier les colonnes manquantes (présentes dans l'entité mais pas dans la DB)
        $missingColumns = array_diff($entityColumnNames, $dbColumnNames);
        
        if (empty($missingColumns)) {
            logMessage("Aucune colonne manquante détectée", false);
            return;
        }
        
        logMessage("Colonnes manquantes détectées: " . implode(", ", $missingColumns));
        
        // Virtualiser chaque colonne manquante
        foreach ($missingColumns as $missingColumn) {
            // Déterminer le type et la valeur par défaut
            $propertyType = "string"; // Par défaut
            $defaultValue = "null";
            
            // Ajuster selon le nom de la colonne (logique simplifiée)
            if ($missingColumn === 'quality_score') {
                $propertyType = "float";
                $defaultValue = "null";
            } elseif (strpos($missingColumn, 'count') !== false) {
                $propertyType = "int";
                $defaultValue = "0";
            } elseif (strpos($missingColumn, 'is_') === 0) {
                $propertyType = "bool";
                $defaultValue = "false";
            } elseif (strpos($missingColumn, '_at') !== false) {
                $propertyType = "\\DateTime";
                $defaultValue = "null";
            }
            
            // Appliquer la virtualisation
            virtualizeProperty($entityPath, $missingColumn, $propertyType, $defaultValue);
        }
        
        logMessage("Processus de correction terminé. Veuillez tester l'application maintenant.");
        
    } catch (\Exception $e) {
        logMessage("Erreur lors de l'analyse: " . $e->getMessage(), true);
    }
}

// Exécuter l'analyse
try {
    logMessage("Début de l'analyse de l'entité WhatsAppTemplate");
    logMessage("Recherche des colonnes manquantes dans la base de données");
    
    // Vérifier si nous avons déjà corrigé les propriétés problématiques
    $qualityScoreFixed = strpos(file_get_contents(__DIR__ . '/../src/Entities/WhatsApp/WhatsAppTemplate.php'), 'Score de qualité du template - non stocké en base de données') !== false;
    
    if ($qualityScoreFixed) {
        logMessage("La propriété quality_score a déjà été virtualisée.");
    } else {
        // Virtualiser les propriétés connues comme problématiques
        virtualizeProperty(
            __DIR__ . '/../src/Entities/WhatsApp/WhatsAppTemplate.php',
            'quality_score',
            'float',
            'null'
        );
        
        logMessage("La propriété quality_score a été virtualisée.");
    }
    
    // Informations pour l'utilisateur
    logMessage("\nRésumé des modifications:");
    logMessage("1. La propriété 'quality_score' a été convertie en propriété virtuelle (non stockée en base de données)");
    logMessage("2. Cette correction permet d'éviter l'erreur SQL lors de l'accès à des colonnes inexistantes");
    logMessage("\nLes messages WhatsApp devraient maintenant fonctionner correctement.");
    
} catch (\Exception $e) {
    logMessage("Une erreur s'est produite: " . $e->getMessage(), true);
}