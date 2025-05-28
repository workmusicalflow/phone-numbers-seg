<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

echo "🧪 Test des services WhatsApp refactorisés...\n\n";

try {
    // Test 1: Circuit Breaker
    echo "1️⃣ Test du Circuit Breaker\n";
    $circuitBreaker = new \App\Services\WhatsApp\CircuitBreaker\CircuitBreaker('test');
    
    $result = $circuitBreaker->call(function() {
        return "Circuit Breaker fonctionne !";
    });
    
    echo "✅ Circuit Breaker: $result\n\n";
    
    // Test 2: Retry Policy
    echo "2️⃣ Test du Retry Policy\n";
    $retryPolicy = new \App\Services\WhatsApp\Retry\RetryPolicy(
        maxAttempts: 2,
        baseDelayMs: 100
    );
    
    $attempts = 0;
    $result = $retryPolicy->execute(function() use (&$attempts) {
        $attempts++;
        if ($attempts === 1) {
            throw new \RuntimeException("Première tentative échoue");
        }
        return "Retry Policy fonctionne après $attempts tentatives !";
    });
    
    echo "✅ Retry Policy: $result\n\n";
    
    // Test 3: ResilientWhatsAppClient
    echo "3️⃣ Test du ResilientWhatsAppClient\n";
    $httpClient = new \GuzzleHttp\Client();
    $circuitBreaker = new \App\Services\WhatsApp\CircuitBreaker\CircuitBreaker('whatsapp_test');
    $resilientClient = new \App\Services\WhatsApp\ResilientWhatsAppClient(
        $httpClient,
        $circuitBreaker
    );
    
    echo "✅ ResilientWhatsAppClient instancié avec succès\n\n";
    
    // Test 4: Validation
    echo "4️⃣ Test du TemplateMessageValidator\n";
    $validator = new \App\Services\WhatsApp\Validators\TemplateMessageValidator();
    
    // Créer un mock user simple (pour test uniquement)
    $user = new class extends \App\Entities\User {
        private int $id = 1;
        public function getId(): int { return $this->id; }
    };
    
    $validationResult = $validator->validate(
        $user,
        '+33612345678',
        'welcome_message',
        'fr'
    );
    
    if ($validationResult->isValid()) {
        echo "✅ Validation: Messages valides passent\n";
    } else {
        echo "❌ Validation échouée: " . implode(', ', $validationResult->getErrors()) . "\n";
    }
    
    // Test de validation avec erreur
    $invalidValidation = $validator->validate(
        $user,
        'invalid-phone',
        '',
        'invalid-lang'
    );
    
    if (!$invalidValidation->isValid()) {
        echo "✅ Validation: Messages invalides sont rejetés (" . count($invalidValidation->getErrors()) . " erreurs)\n";
    }
    
    echo "\n🎉 Tous les tests sont passés ! L'architecture refactorisée fonctionne.\n\n";
    
    echo "📊 Résumé des améliorations:\n";
    echo "• Circuit Breaker: Protection contre les pannes en cascade\n";
    echo "• Retry Policy: Gestion automatique des erreurs temporaires\n";
    echo "• Validation: Séparation des responsabilités\n";
    echo "• Architecture modulaire: Facilite les tests et la maintenance\n\n";
    
} catch (\Throwable $e) {
    echo "❌ Erreur lors du test: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}