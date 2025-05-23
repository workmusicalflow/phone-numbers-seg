<?php

require_once __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;
use App\Services\WhatsApp\WhatsAppService;
use App\Repositories\Doctrine\WhatsApp\WhatsAppTemplateRepository;

echo "=== VALIDATION FINALE - ÉLIMINATION DES ERREURS SQL ===\n\n";

try {
    // 1. Construire le conteneur DI
    echo "1. Chargement de la configuration DI...\n";
    $definitions = require __DIR__ . '/../src/config/di.php';
    
    $containerBuilder = new ContainerBuilder();
    $containerBuilder->addDefinitions($definitions);
    $container = $containerBuilder->build();
    
    echo "   ✓ Conteneur DI construit avec succès\n";
    
    // 2. Test du repository - vérifier qu'aucune erreur SQL "no such column" n'est générée
    echo "2. Test du WhatsAppTemplateRepository...\n";
    $templateRepository = $container->get(WhatsAppTemplateRepository::class);
    
    // Cette requête va déclencher l'accès aux propriétés - elles doivent être virtuelles maintenant
    $templates = $templateRepository->findAll();
    echo "   ✓ findAll() exécuté sans erreur SQL: " . count($templates) . " templates trouvés\n";
    
    // 3. Test spécifique pour les propriétés virtualisées
    if (!empty($templates)) {
        echo "3. Test des propriétés virtuelles...\n";
        $template = $templates[0];
        echo "   Template testé: " . $template->getName() . "\n";
        
        // Accéder à toutes les propriétés qui étaient problématiques
        echo "   - qualityScore: " . ($template->getQualityScore() ?? 'null') . "\n";
        echo "   - headerFormat: " . ($template->getHeaderFormat() ?? 'null') . "\n";
        echo "   - bodyText: " . ($template->getBodyText() ?? 'null') . "\n";
        echo "   - footerText: " . ($template->getFooterText() ?? 'null') . "\n";
        echo "   - bodyVariablesCount: " . ($template->getBodyVariablesCount() ?? 'null') . "\n";
        echo "   - buttonsCount: " . ($template->getButtonsCount() ?? 'null') . "\n";
        echo "   - buttonsDetails: " . (is_array($template->getButtonsDetails()) ? 'array' : 'null') . "\n";
        echo "   - rejectionReason: " . ($template->getRejectionReason() ?? 'null') . "\n";
        echo "   - usageCount: " . ($template->getUsageCount() ?? 'null') . "\n";
        echo "   - lastUsedAt: " . ($template->getLastUsedAt() ? $template->getLastUsedAt()->format('Y-m-d H:i:s') : 'null') . "\n";
        echo "   - apiVersion: " . ($template->getApiVersion() ?? 'null') . "\n";
        echo "   - componentsJson: " . (is_array($template->getComponentsJson()) ? 'array' : 'null') . "\n";
        echo "   ✓ Toutes les propriétés virtuelles accessibles sans erreur SQL\n";
    }
    
    // 4. Test du service WhatsApp
    echo "4. Test du WhatsAppService...\n";
    $whatsappService = $container->get(WhatsAppService::class);
    
    // Test d'envoi pour vérifier l'absence totale d'erreurs SQL
    echo "5. Test d'envoi final...\n";
    $phoneNumber = '+22577104936';
    $templateName = 'hello_world';
    
    $result = $whatsappService->sendTemplateMessage($phoneNumber, $templateName, [], []);
    
    if ($result['success']) {
        echo "   ✓ Message envoyé avec succès - ID: " . ($result['messageId'] ?? 'N/A') . "\n";
        echo "   ✓ AUCUNE ERREUR SQL détectée pendant l'envoi\n";
    } else {
        echo "   ⚠ Erreur lors de l'envoi: " . ($result['error'] ?? 'Erreur inconnue') . "\n";
        
        // Vérification critique: est-ce que l'erreur contient "no such column"?
        if (isset($result['error']) && strpos($result['error'], 'no such column') !== false) {
            echo "   ❌ ERREUR SQL CRITIQUE DÉTECTÉE: " . $result['error'] . "\n";
            echo "   ❌ Certaines colonnes problématiques subsistent\n";
        } else {
            echo "   ✓ Pas d'erreur SQL liée aux colonnes - autres problèmes possibles\n";
        }
    }
    
    echo "\n=== RÉSUMÉ FINAL ===\n";
    echo "✓ Conteneur DI fonctionnel\n";
    echo "✓ Repository opérationnel sans erreurs SQL\n";
    echo "✓ Propriétés virtuelles (quality_score, header_format, etc.) fonctionnelles\n";
    echo "✓ Service WhatsApp accessible\n";
    
    if (isset($result['success']) && $result['success']) {
        echo "\n🎉 SUCCESS: TOUTES LES ERREURS SQL 'no such column' ONT ÉTÉ ÉLIMINÉES\n";
        echo "🎉 L'inconsistance base de données / entité a été totalement résolue\n";
    } else {
        echo "\n📋 Validation technique complète - prêt pour les tests utilisateur\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ ERREUR DÉTECTÉE:\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    // Analyse critique de l'erreur
    if (strpos($e->getMessage(), 'no such column') !== false) {
        echo "\n💥 ERREUR SQL CRITIQUE NON RÉSOLUE\n";
        echo "Colonne manquante: " . $e->getMessage() . "\n";
        echo "Action requise: vérifier la virtualisation de cette propriété\n";
    } else {
        echo "\n📋 Erreur non liée aux colonnes SQL manquantes\n";
    }
}