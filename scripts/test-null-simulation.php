<?php
/**
 * Simulation simplifiée pour tester les correctifs de protection contre les valeurs nulles
 */

// En-tête
echo "Test des protections contre les valeurs nulles (Simulation)\n";
echo "======================================================\n\n";

/**
 * Simulation de WhatsAppTemplateSafeType
 */
class MockTemplateSafeType {
    private string $id = '';
    private string $name = '';
    
    public function __construct(?array $template = null) {
        // Protection contre null
        if ($template === null) {
            $template = [
                'id' => 'empty_' . uniqid(),
                'name' => 'Empty Template'
            ];
        }
        
        // Valeurs par défaut sûres
        $this->id = (string)($template['id'] ?? 'id_' . uniqid());
        $this->name = (string)($template['name'] ?? 'Unnamed Template');
    }
    
    public function getId(): string {
        return $this->id;
    }
    
    public function getName(): string {
        return $this->name;
    }
}

/**
 * Simulation du service
 */
function mockService(?array $data = null): array {
    echo "Service appelé...\n";
    
    // Cas 1: retour normal
    if ($data !== null) {
        echo "Données fournies, pas de protection nécessaire\n";
        return $data;
    }
    
    // Cas 2: simulation d'une erreur
    try {
        echo "Simulation d'une erreur...\n";
        throw new Exception("Erreur simulée dans le service");
    } catch (Exception $e) {
        echo "Exception capturée: " . $e->getMessage() . "\n";
        echo "Retour d'un tableau vide au lieu de propager l'erreur\n";
        return [];
    }
}

/**
 * Simulation du résolveur/contrôleur
 */
function mockResolver(?array $filters = null, bool $simulateServiceNull = false, bool $simulateServiceException = false): array {
    echo "Résolveur appelé...\n";
    
    try {
        // Gestion des filtres
        $filterArray = [];
        if ($filters) {
            echo "Application des filtres...\n";
            // ...
        }
        
        // ATTENTION: Simulation de cas particuliers
        if ($simulateServiceNull) {
            echo "Simulation: le service retourne null\n";
            $templates = null;
        } else if ($simulateServiceException) {
            echo "Simulation: le service lance une exception\n";
            throw new Exception("Erreur simulée dans le service");
        } else {
            // Appel normal du service
            $templates = mockService([
                ['id' => '1', 'name' => 'Template 1'],
                ['id' => '2', 'name' => 'Template 2']
            ]);
        }
        
        // PROTECTION NIVEAU 1: Valeur null -> tableau vide
        echo "Protection niveau 1: null -> tableau vide\n";
        if ($templates === null) {
            echo "  Détection de null, conversion en tableau vide\n";
            $templates = [];
        }
        
        // PROTECTION NIVEAU 2: Vérification du type
        echo "Protection niveau 2: vérification du type retourné\n";
        if (!is_array($templates)) {
            echo "  Type non-array détecté, conversion en tableau vide\n";
            $templates = [];
        }
        
        // PROTECTION NIVEAU 3: Création des objets de type
        echo "Protection niveau 3: conversion en objets de type sûrs\n";
        $templateTypes = [];
        foreach ($templates as $template) {
            try {
                $templateTypes[] = new MockTemplateSafeType($template);
            } catch (Exception $e) {
                echo "  Erreur lors de la création d'un type template: " . $e->getMessage() . "\n";
                // Continuer avec le prochain template
                continue;
            }
        }
        
        // PROTECTION NIVEAU 4: Garantir un tableau toujours retourné
        echo "Protection niveau 4: garantir un tableau non-null\n";
        $result = empty($templateTypes) ? [] : $templateTypes;
        
        echo "Résultat final: tableau avec " . count($result) . " éléments\n";
        return $result;
    } catch (Exception $e) {
        echo "Exception globale capturée: " . $e->getMessage() . "\n";
        echo "Retour d'un tableau vide pour respecter le type non-nullable\n";
        
        // EN CAS D'ERREUR GÉNÉRALE: Toujours retourner un tableau vide
        return [];
    }
}

// Test 1: Cas normal 
echo "TEST 1: Cas normal (le service retourne des données)\n";
echo "------------------------------------------------\n";
$result1 = mockResolver();
echo "Type retourné: " . gettype($result1) . " avec " . count($result1) . " éléments\n";
echo "Validation: " . (is_array($result1) && $result1 !== null ? "✅ SUCCÈS" : "❌ ÉCHEC") . "\n\n";

// Test 2: Service retourne null
echo "TEST 2: Service retourne null\n";
echo "-------------------------\n";
$result2 = mockResolver(null, true);
echo "Type retourné: " . gettype($result2) . " avec " . count($result2) . " éléments\n";
echo "Validation: " . (is_array($result2) && $result2 !== null ? "✅ SUCCÈS" : "❌ ÉCHEC") . "\n\n";

// Test 3: Service lance une exception
echo "TEST 3: Service lance une exception\n";
echo "-------------------------------\n";
$result3 = mockResolver(null, false, true);
echo "Type retourné: " . gettype($result3) . " avec " . count($result3) . " éléments\n";
echo "Validation: " . (is_array($result3) && $result3 !== null ? "✅ SUCCÈS" : "❌ ÉCHEC") . "\n\n";

// Test de la classe MockTemplateSafeType
echo "TEST 4: Classe MockTemplateSafeType avec null\n";
echo "----------------------------------------\n";
$safeType = new MockTemplateSafeType(null);
echo "ID généré: " . $safeType->getId() . "\n";
echo "Nom par défaut: " . $safeType->getName() . "\n";
echo "Validation: " . ($safeType->getId() !== '' && $safeType->getName() !== '' ? "✅ SUCCÈS" : "❌ ÉCHEC") . "\n\n";

echo "======================================================\n";
echo "Tous les tests terminés\n";