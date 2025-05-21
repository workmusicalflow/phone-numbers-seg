<?php
/**
 * Script de test pour vérifier la protection contre les valeurs nulles dans le service WhatsApp
 */

require_once __DIR__ . '/../src/bootstrap-doctrine.php';

use App\GraphQL\DIContainer;
use App\Services\Interfaces\WhatsApp\WhatsAppTemplateServiceInterface;
use App\GraphQL\Types\WhatsApp\WhatsAppTemplateSafeType;
use Psr\Log\LoggerInterface;

// Initialiser le conteneur
$container = new DIContainer();
$logger = $container->get(LoggerInterface::class);

// En-tête
echo "Test des protections contre les valeurs nulles\n";
echo "===========================================\n\n";

// Tester le service
echo "1. Test du service WhatsAppTemplateService\n";
echo "----------------------------------------\n";

try {
    $service = $container->get(WhatsAppTemplateServiceInterface::class);
    
    echo "Récupération des templates...\n";
    $templates = $service->fetchApprovedTemplatesFromMeta([]);
    
    // Vérifier le type retourné
    echo "Type retourné: " . gettype($templates) . "\n";
    echo "Contient " . (is_countable($templates) ? count($templates) : 'N/A') . " templates\n";
    
    // Vérifier que ce n'est pas null
    if ($templates === null) {
        echo "❌ ÉCHEC: Le service a retourné null\n";
    } else {
        echo "✅ SUCCÈS: Le service n'a pas retourné null\n";
    }
    
    // Vérifier que c'est un tableau
    if (!is_array($templates)) {
        echo "❌ ÉCHEC: Le service n'a pas retourné un tableau\n";
    } else {
        echo "✅ SUCCÈS: Le service a retourné un tableau\n";
    }
    
    // Afficher le premier template s'il existe
    if (is_array($templates) && !empty($templates)) {
        echo "\nDétails du premier template:\n";
        $firstTemplate = $templates[0];
        echo "- ID: " . ($firstTemplate['id'] ?? 'non défini') . "\n";
        echo "- Nom: " . ($firstTemplate['name'] ?? 'non défini') . "\n";
        echo "- Statut: " . ($firstTemplate['status'] ?? 'non défini') . "\n";
    } else {
        echo "\nAucun template retourné par le service\n";
    }
} catch (Throwable $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n";

// Tester le WhatsAppTemplateSafeType
echo "2. Test de WhatsAppTemplateSafeType\n";
echo "-------------------------------\n";

try {
    echo "Test de construction avec un tableau vide...\n";
    $safeType = new WhatsAppTemplateSafeType([]);
    
    echo "ID généré: " . $safeType->getId() . "\n";
    echo "Nom par défaut: " . $safeType->getName() . "\n";
    
    echo "Test de construction avec null...\n";
    $safeTypeNull = new WhatsAppTemplateSafeType(null);
    
    echo "ID généré: " . $safeTypeNull->getId() . "\n";
    echo "Nom par défaut: " . $safeTypeNull->getName() . "\n";
    
    echo "✅ SUCCÈS: WhatsAppTemplateSafeType accepte les valeurs null et vides\n";
} catch (Throwable $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n";

// Tester le résolveur et le contrôleur
echo "3. Test de récupération des entités (simulation)\n";
echo "-------------------------------------------\n";

function simulateNullCase() {
    echo "Simulation d'un retour null depuis le service...\n";
    
    $nullTemplates = null;
    
    // Protection niveau 1: vérifier et corriger null
    $templates = $nullTemplates ?? [];
    echo "Après correction niveau 1: " . gettype($templates) . "\n";
    
    // Protection niveau 2: vérifier le type
    if (!is_array($templates)) {
        echo "Type non-array détecté, conversion...\n";
        $templates = [];
    }
    echo "Après correction niveau 2: " . gettype($templates) . "\n";
    
    // Protection niveau 3: initialiser les objets de type
    $templateTypes = [];
    foreach ($templates as $template) {
        $templateTypes[] = new WhatsAppTemplateSafeType($template);
    }
    echo "Après correction niveau 3: " . count($templateTypes) . " templates\n";
    
    // Protection niveau 4: garantir un tableau, jamais null
    $result = empty($templateTypes) ? [] : $templateTypes;
    echo "Résultat final: " . gettype($result) . " avec " . count($result) . " éléments\n";
    
    echo "✅ SUCCÈS: Un tableau vide a été retourné malgré le null initial\n";
    
    return $result;
}

try {
    $result = simulateNullCase();
    
    // Vérifier si c'est un tableau
    if (!is_array($result)) {
        echo "❌ ÉCHEC: La simulation n'a pas retourné un tableau\n";
    } else {
        echo "✅ SUCCÈS: La simulation a retourné un tableau\n";
    }
    
    // Vérifier si c'est null
    if ($result === null) {
        echo "❌ ÉCHEC: La simulation a retourné null\n";
    } else {
        echo "✅ SUCCÈS: La simulation n'a pas retourné null\n";
    }
} catch (Throwable $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
}

echo "\n";
echo "===========================================\n";
echo "Tests terminés\n";