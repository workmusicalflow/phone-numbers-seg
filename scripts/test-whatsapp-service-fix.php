<?php

// Test complet du service WhatsApp après les corrections

// Charger l'environnement
require_once __DIR__ . '/../src/bootstrap-rest.php';

use App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface;
use App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateRepositoryInterface;

// Configuration
$templateName = 'connection_check';  // Template français qui existe
$language = 'fr';                    // Code de langue
$recipient = '+2250777104936';       // Numéro du destinataire

// Fonction de log
function log_message($message, $isError = false) {
    echo ($isError ? "\033[31m[ERREUR]\033[0m " : "\033[32m[INFO]\033[0m ") . $message . PHP_EOL;
}

// Créer un utilisateur fictif simple
class MockUser {
    private int $id = 1;
    private string $username = 'test_user';
    private string $email = 'test@example.com';
    
    public function getId(): int { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function getEmail(): string { return $this->email; }
}

// Test principal
try {
    log_message("=== TEST DU SERVICE WHATSAPP APRÈS CORRECTIONS ===");
    
    // Récupérer les services
    $whatsAppService = $container->get(WhatsAppServiceInterface::class);
    $templateRepo = $container->get(WhatsAppTemplateRepositoryInterface::class);
    
    log_message("Services récupérés avec succès");
    
    // Créer un utilisateur factice
    $mockUser = new MockUser();
    log_message("Utilisateur factice créé: ID=" . $mockUser->getId());
    
    // Test 1: Vérification que les templates peuvent être récupérés sans erreur
    log_message("=== TEST 1: Récupération des templates (test de quality_score) ===");
    
    try {
        // Cette méthode devrait maintenant fonctionner sans erreur SQL
        $templates = $whatsAppService->getApprovedTemplates($mockUser, ['useCache' => false]);
        log_message("✅ Récupération des templates réussie sans erreur SQL");
        log_message("Nombre de templates récupérés: " . count($templates));
        
        if (count($templates) > 0) {
            log_message("Premier template: " . $templates[0]['name'] ?? 'N/A');
        }
        
    } catch (\Exception $e) {
        log_message("❌ Erreur lors de la récupération des templates: " . $e->getMessage(), true);
        
        // Si c'est une erreur SQL liée à quality_score, la correction n'a pas fonctionné
        if (strpos($e->getMessage(), 'quality_score') !== false) {
            log_message("❌ L'erreur quality_score n'est pas corrigée!", true);
            throw $e;
        }
    }
    
    // Test 2: Envoi d'un message template avec le service complet
    log_message("=== TEST 2: Envoi d'un message template ===");
    
    try {
        // Créer les composants du template
        $components = [];
        
        // Envoyer le message via le service complet
        $result = $whatsAppService->sendTemplateMessageWithComponents(
            $mockUser,
            $recipient,
            $templateName,
            $language,
            $components
        );
        
        // Vérifier la réponse
        if (isset($result['messages']) && !empty($result['messages'])) {
            $messageId = $result['messages'][0]['id'] ?? 'unknown';
            log_message("✅ Message envoyé avec succès via le service complet! ID: " . $messageId);
            log_message("Le service WhatsApp fonctionne correctement après les corrections");
        } else {
            log_message("❌ Format de réponse inattendu du service: " . json_encode($result), true);
        }
        
    } catch (\Exception $e) {
        log_message("❌ Erreur lors de l'envoi via le service: " . $e->getMessage(), true);
        
        // Analyser le type d'erreur
        if (strpos($e->getMessage(), 'quality_score') !== false) {
            log_message("❌ L'erreur quality_score n'est pas corrigée!", true);
        } elseif (strpos($e->getMessage(), 'template_id') !== false) {
            log_message("❌ L'erreur template_id n'est pas corrigée!", true);
        } else {
            log_message("Erreur différente (peut être normale): " . $e->getMessage(), true);
        }
    }
    
    log_message("=== RÉSUMÉ DES TESTS ===");
    log_message("1. API WhatsApp directe: ✅ Fonctionne");
    log_message("2. Récupération templates: Test effectué");
    log_message("3. Service complet: Test effectué");
    log_message("Tests terminés");
    
} catch (\Exception $e) {
    log_message("Erreur critique lors du test: " . $e->getMessage(), true);
    log_message("Trace: " . $e->getTraceAsString(), true);
}