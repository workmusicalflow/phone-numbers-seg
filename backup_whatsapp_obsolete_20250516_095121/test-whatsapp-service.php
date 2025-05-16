<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Configuration simple
$config = [
    'base_url' => 'https://graph.facebook.com/',
    'api_version' => 'v18.0',
    'access_token' => 'TEST_ACCESS_TOKEN',
    'phone_number_id' => '123456789',
    'whatsapp_business_account_id' => '987654321'
];

// Logger simple
$logger = new class implements \Psr\Log\LoggerInterface {
    public function emergency($message, array $context = []) { $this->doLog('EMERGENCY', $message, $context); }
    public function alert($message, array $context = []) { $this->doLog('ALERT', $message, $context); }
    public function critical($message, array $context = []) { $this->doLog('CRITICAL', $message, $context); }
    public function error($message, array $context = []) { $this->doLog('ERROR', $message, $context); }
    public function warning($message, array $context = []) { $this->doLog('WARNING', $message, $context); }
    public function notice($message, array $context = []) { $this->doLog('NOTICE', $message, $context); }
    public function info($message, array $context = []) { $this->doLog('INFO', $message, $context); }
    public function debug($message, array $context = []) { $this->doLog('DEBUG', $message, $context); }
    
    private function doLog($level, $message, array $context = []) {
        echo "[$level] $message";
        if (!empty($context)) {
            echo " - " . json_encode($context);
        }
        echo "\n";
    }
    
    public function log($level, $message, array $context = []) {
        $this->doLog($level, $message, $context);
    }
};

try {
    echo "=== Test du client API WhatsApp ===\n\n";
    
    // Test de la création du client
    echo "1. Création du client API WhatsApp...\n";
    $client = new \App\Services\WhatsApp\WhatsAppApiClient($logger, $config);
    echo "✓ Client créé avec succès\n\n";
    
    // Test de la structure de message
    echo "2. Test de la structure d'un message texte...\n";
    $payload = [
        'messaging_product' => 'whatsapp',
        'to' => '22507000000',
        'type' => 'text',
        'text' => [
            'body' => 'Test message'
        ]
    ];
    echo "Payload: " . json_encode($payload, JSON_PRETTY_PRINT) . "\n";
    
    echo "\n=== Tests terminés ===\n";
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}