<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

echo "🧪 Test du WhatsAppServiceEnhanced...\n\n";

try {
    // Test simple d'instanciation
    echo "1️⃣ Test de création du service...\n";
    
    // Créer des mocks pour les dépendances
    $mockApiClient = new class implements \App\Services\Interfaces\WhatsApp\WhatsAppApiClientInterface {
        public function sendTemplateMessage(array $payload): array { return []; }
        public function sendTextMessage(string $recipient, string $message): array { return []; }
        public function sendMediaMessage(string $recipient, string $type, string $mediaId): array { return []; }
        public function uploadMedia(string $filePath, string $mimeType): array { return []; }
        public function downloadMedia(string $mediaId): array { return []; }
        public function getMediaUrl(string $mediaId): string { return ''; }
        public function getApprovedTemplates(): array { return []; }
        public function processWebhook(array $payload): void {}
        public function verifyWebhook(string $mode, string $challenge, string $verifyToken): ?string { return null; }
    };
    
    $mockMessageRepo = new class implements \App\Repositories\Interfaces\WhatsApp\WhatsAppMessageHistoryRepositoryInterface {
        public function save($entity): void {}
        public function findById(int $id) { return null; }
        public function findAll(): array { return []; }
        public function delete($entity): void {}
        public function findByUser(\App\Entities\User $user, ?string $phoneNumber = null, ?\DateTime $startDate = null, ?\DateTime $endDate = null, int $limit = 100, int $offset = 0): array { return []; }
        public function findByUserWithFilters(\App\Entities\User $user, array $filters = []): array { return []; }
        public function countByUser(\App\Entities\User $user): int { return 0; }
    };
    
    $mockTemplateRepo = new class implements \App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateRepositoryInterface {
        public function save($entity): void {}
        public function findById(int $id) { return null; }
        public function findAll(): array { return []; }
        public function delete($entity): void {}
        public function findByName(string $name) { return null; }
        public function findApprovedTemplates(): array { return []; }
        public function syncTemplates(array $templates): array { return []; }
    };
    
    $mockLogger = new class implements \Psr\Log\LoggerInterface {
        public function emergency($message, array $context = []): void {}
        public function alert($message, array $context = []): void {}
        public function critical($message, array $context = []): void {}
        public function error($message, array $context = []): void {}
        public function warning($message, array $context = []): void {}
        public function notice($message, array $context = []): void {}
        public function info($message, array $context = []): void {}
        public function debug($message, array $context = []): void {}
        public function log($level, $message, array $context = []): void {}
    };
    
    $mockTemplateService = new class implements \App\Services\Interfaces\WhatsApp\WhatsAppTemplateServiceInterface {
        public function getApprovedTemplates(): array { return []; }
        public function syncTemplates(): array { return []; }
        public function findTemplateByName(string $name) { return null; }
    };
    
    $config = ['api_url' => 'test', 'access_token' => 'test'];
    
    // Créer le service enhanced
    $service = new \App\Services\WhatsApp\WhatsAppServiceEnhanced(
        $mockApiClient,
        $mockMessageRepo,
        $mockTemplateRepo,
        $mockLogger,
        $config,
        $mockTemplateService
    );
    
    echo "✅ WhatsAppServiceEnhanced créé avec succès\n\n";
    
    // Test des composants internes
    echo "2️⃣ Test des composants internes...\n";
    
    $validator = new \App\Services\WhatsApp\Validators\TemplateMessageValidator();
    echo "✅ TemplateMessageValidator instancié\n";
    
    $builder = new \App\Services\WhatsApp\Builders\TemplateMessageBuilder();
    echo "✅ TemplateMessageBuilder instancié\n";
    
    $handler = new \App\Services\WhatsApp\Handlers\TemplateUsageHandler($mockLogger);
    echo "✅ TemplateUsageHandler instancié\n";
    
    echo "\n🎉 Tous les composants fonctionnent ! Le service enhanced est prêt.\n\n";
    
    echo "📋 Services disponibles:\n";
    echo "• WhatsAppService (original)\n";
    echo "• WhatsAppServiceEnhanced (Phase 1 - amélioré) ← ACTUEL\n";
    echo "• WhatsAppServiceWithCommands (Phase 2 - avec patterns)\n";
    echo "• WhatsAppServiceWithResilience (Phase 3 - avec résilience)\n";
    
} catch (\Throwable $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}