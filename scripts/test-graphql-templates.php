<?php

/**
 * Script de test pour les resolvers GraphQL WhatsApp Templates
 * 
 * Ce script permet de tester les nouvelles implémentations des resolvers GraphQL
 * qui utilisent maintenant le client REST WhatsApp avec mécanismes de fallback.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Initialiser le conteneur DI
$containerBuilder = new \DI\ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../src/config/di.php');
$container = $containerBuilder->build();

// Obtenir les services nécessaires
$logger = $container->get(\Psr\Log\LoggerInterface::class);
$userRepository = $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class);
$templateResolver = $container->get(\App\GraphQL\Resolvers\WhatsApp\WhatsAppTemplateResolver::class);

// Obtenir un utilisateur pour les tests
$user = $userRepository->findOneBy(['id' => 1]);
if (!$user) {
    echo "Erreur: Utilisateur avec ID=1 non trouvé.\n";
    exit(1);
}

echo "=== Test des resolvers GraphQL WhatsApp Templates ===\n\n";

// Test 1: fetchApprovedWhatsAppTemplates
echo "[TEST 1] fetchApprovedWhatsAppTemplates - Sans filtre\n";
try {
    $startTime = microtime(true);
    $templates = $templateResolver->fetchApprovedWhatsAppTemplates(null, $user);
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);
    
    echo "  Succès! " . count($templates) . " templates trouvés en $duration ms\n";
    
    // Afficher quelques exemples
    if (count($templates) > 0) {
        echo "  Exemples:\n";
        $samples = array_slice($templates, 0, 3);
        foreach ($samples as $index => $template) {
            echo "  - Template " . ($index + 1) . ": " . $template->getName() . " (" . $template->getLanguage() . ")\n";
        }
    }
} catch (\Exception $e) {
    echo "  Erreur: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 2: fetchApprovedWhatsAppTemplates - Avec filtre
echo "[TEST 2] fetchApprovedWhatsAppTemplates - Avec filtre de langue\n";
try {
    $filter = new \App\GraphQL\Types\WhatsApp\TemplateFilterInput();
    $filter->language = 'fr';
    
    $startTime = microtime(true);
    $templates = $templateResolver->fetchApprovedWhatsAppTemplates($filter, $user);
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);
    
    echo "  Succès! " . count($templates) . " templates en français trouvés en $duration ms\n";
    
    // Afficher quelques exemples
    if (count($templates) > 0) {
        echo "  Exemples:\n";
        $samples = array_slice($templates, 0, 3);
        foreach ($samples as $index => $template) {
            echo "  - Template " . ($index + 1) . ": " . $template->getName() . " (" . $template->getLanguage() . ")\n";
        }
    }
} catch (\Exception $e) {
    echo "  Erreur: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 3: searchWhatsAppTemplates - Recherche avancée
echo "[TEST 3] searchWhatsAppTemplates - Recherche avancée\n";
try {
    $filter = new \App\GraphQL\Types\WhatsApp\TemplateFilterInput();
    $filter->hasMediaHeader = true; // Templates avec en-tête média
    
    $startTime = microtime(true);
    $templates = $templateResolver->searchWhatsAppTemplates($filter, 10, 0, $user);
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);
    
    echo "  Succès! " . count($templates) . " templates avec média trouvés en $duration ms\n";
    
    // Afficher quelques exemples
    if (count($templates) > 0) {
        echo "  Exemples:\n";
        $samples = array_slice($templates, 0, 3);
        foreach ($samples as $index => $template) {
            echo "  - Template " . ($index + 1) . ": " . $template->getName() . " (" . $template->getHeaderFormat() . ")\n";
        }
    }
} catch (\Exception $e) {
    echo "  Erreur: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 4: whatsAppTemplatesByHeaderFormat
echo "[TEST 4] whatsAppTemplatesByHeaderFormat - Templates avec image\n";
try {
    $startTime = microtime(true);
    $templates = $templateResolver->getTemplatesByHeaderFormat('IMAGE', 'APPROVED', $user);
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);
    
    echo "  Succès! " . count($templates) . " templates avec en-tête IMAGE trouvés en $duration ms\n";
    
    // Afficher quelques exemples
    if (count($templates) > 0) {
        echo "  Exemples:\n";
        $samples = array_slice($templates, 0, 3);
        foreach ($samples as $index => $template) {
            echo "  - Template " . ($index + 1) . ": " . $template->getName() . " (" . $template->getLanguage() . ")\n";
        }
    }
} catch (\Exception $e) {
    echo "  Erreur: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 5: mostUsedWhatsAppTemplates
echo "[TEST 5] mostUsedWhatsAppTemplates - Templates les plus utilisés\n";
try {
    $startTime = microtime(true);
    $templates = $templateResolver->getMostUsedTemplates(5, $user);
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);
    
    echo "  Succès! " . count($templates) . " templates populaires trouvés en $duration ms\n";
    
    // Afficher tous les templates populaires avec leur nombre d'utilisations
    if (count($templates) > 0) {
        echo "  Templates les plus utilisés:\n";
        foreach ($templates as $index => $template) {
            echo "  - " . ($index + 1) . ". " . $template->getName() . " (" . $template->getUsageCount() . " utilisations)\n";
        }
    } else {
        echo "  Aucun template utilisé trouvé.\n";
    }
} catch (\Exception $e) {
    echo "  Erreur: " . $e->getMessage() . "\n";
}
echo "\n";

echo "=== Tests terminés ===\n";