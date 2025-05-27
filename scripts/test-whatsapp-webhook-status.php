<?php

/**
 * Script de test pour simuler les webhooks de statut WhatsApp
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Configuration
$webhookUrl = 'http://localhost:8000/whatsapp/webhook.php';
$appSecret = 'your_app_secret'; // À remplacer par le vrai secret

// Simuler différents statuts pour un message
$wabaMessageId = 'wamid.HBgNMjI1MDcwMDAwMDFWAgARGBJGRDA2QTM3MkI3NUE4OUY0NTQA';

// Créer les payloads de webhook pour différents statuts
$statuses = ['sent', 'delivered', 'read'];

foreach ($statuses as $status) {
    echo "\n=== Test du statut: $status ===\n";
    
    $payload = [
        'object' => 'whatsapp_business_account',
        'entry' => [
            [
                'id' => '123456789',
                'changes' => [
                    [
                        'value' => [
                            'messaging_product' => 'whatsapp',
                            'metadata' => [
                                'display_phone_number' => '+1234567890',
                                'phone_number_id' => '987654321'
                            ],
                            'statuses' => [
                                [
                                    'id' => $wabaMessageId,
                                    'status' => $status,
                                    'timestamp' => (string)time(),
                                    'recipient_id' => '22507000001'
                                ]
                            ]
                        ],
                        'field' => 'messages'
                    ]
                ]
            ]
        ]
    ];
    
    // Convertir en JSON
    $jsonPayload = json_encode($payload);
    
    // Calculer la signature
    $signature = 'sha256=' . hash_hmac('sha256', $jsonPayload, $appSecret);
    
    // Envoyer la requête
    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-Hub-Signature-256: ' . $signature
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Réponse HTTP: $httpCode\n";
    echo "Réponse: $response\n";
    
    // Attendre un peu entre les statuts
    sleep(1);
}

echo "\n✅ Test terminé\n";