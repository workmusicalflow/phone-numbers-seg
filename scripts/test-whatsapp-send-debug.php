<?php
/**
 * Test de debug pour l'envoi WhatsApp
 */

require_once __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;

// Configuration
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../src/config/di.php');
$container = $containerBuilder->build();

// Obtenir les services
$userRepo = $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class);
$whatsappService = $container->get(\App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface::class);
$logger = $container->get(\Psr\Log\LoggerInterface::class);

// Obtenir l'utilisateur admin
$user = $userRepo->findById(1);
echo "Utilisateur : " . $user->getUsername() . "\n\n";

try {
    echo "Envoi du message WhatsApp...\n";
    
    // Ajouter un logger qui affiche sur la console
    class ConsoleLogger extends \Psr\Log\AbstractLogger {
        public function log($level, \Stringable|string $message, array $context = []): void {
            echo "[{$level}] {$message}\n";
            if (!empty($context)) {
                echo "Context: " . json_encode($context, JSON_PRETTY_PRINT) . "\n";
            }
        }
    }
    
    // Créer un service avec logger console pour debug
    $debugLogger = new ConsoleLogger();
    $apiClient = $container->get(\App\Services\Interfaces\WhatsApp\WhatsAppApiClientInterface::class);
    $messageRepo = $container->get(\App\Repositories\Interfaces\WhatsApp\WhatsAppMessageHistoryRepositoryInterface::class);
    $templateRepo = $container->get(\App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateRepositoryInterface::class);
    $config = $container->get('whatsapp.config');
    
    $templateService = $container->get(\App\Services\Interfaces\WhatsApp\WhatsAppTemplateServiceInterface::class);
    
    $debugService = new \App\Services\WhatsApp\WhatsAppService(
        $apiClient,
        $messageRepo,
        $templateRepo,
        $debugLogger,
        $config,
        $templateService
    );
    
    $result = $debugService->sendMessage(
        $user,
        '+2250777104936',
        'text',
        'Test debug envoi WhatsApp - ' . date('H:i:s')
    );
    
    echo "\nRésultat :\n";
    echo "ID : " . ($result->getId() ?? 'NULL') . "\n";
    echo "Phone : " . ($result->getPhoneNumber() ?? 'NULL') . "\n";
    echo "Status : " . ($result->getStatus() ?? 'NULL') . "\n";
    echo "WABA ID : " . ($result->getWabaMessageId() ?? 'NULL') . "\n";
    
} catch (\Exception $e) {
    echo "\n❌ Erreur : " . $e->getMessage() . "\n";
    echo "Fichier : " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace :\n" . $e->getTraceAsString() . "\n";
}