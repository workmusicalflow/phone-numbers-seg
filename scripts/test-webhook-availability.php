<?php
// Test de disponibilité du webhook localtunnel

// Configuration
$webhookUrl = 'https://oracle-whatsapp.loca.lt/whatsapp/webhook.php';
$verifyToken = 'oracle_whatsapp_webhook_verification_token';

echo "Test de disponibilité du webhook WhatsApp\n";
echo "URL du webhook: $webhookUrl\n\n";

// Test 1: Vérifier que l'URL est accessible
echo "1. Test d'accessibilité de l'URL:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $webhookUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_VERBOSE, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "   ❌ Erreur cURL: $error\n";
} else {
    echo "   ✓ URL accessible\n";
    echo "   Code HTTP: $httpCode\n";
}

// Test 2: Vérifier le challenge de vérification WhatsApp
echo "\n2. Test du challenge de vérification WhatsApp:\n";
$hubMode = 'subscribe';
$hubChallenge = 'test_challenge_123456789';
$verifyUrl = $webhookUrl . '?' . http_build_query([
    'hub.mode' => $hubMode,
    'hub.challenge' => $hubChallenge,
    'hub.verify_token' => $verifyToken
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $verifyUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "   ❌ Erreur cURL: $error\n";
} else {
    echo "   ✓ Requête envoyée\n";
    echo "   Code HTTP: $httpCode\n";
    echo "   Réponse: $response\n";
    
    if ($response === $hubChallenge) {
        echo "   ✓ Challenge de vérification correctement retourné\n";
    } else {
        echo "   ❌ Challenge incorrect (attendu: $hubChallenge)\n";
    }
}

// Test 3: Envoyer un webhook de test
echo "\n3. Test d'envoi de webhook de message:\n";
$testWebhookData = [
    'object' => 'whatsapp_business_account',
    'entry' => [
        [
            'id' => '123456789',
            'changes' => [
                [
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'metadata' => [
                            'display_phone_number' => '1234567890',
                            'phone_number_id' => '123456789'
                        ],
                        'messages' => [
                            [
                                'from' => '2250777104936',
                                'id' => 'wamid.TEST_' . time(),
                                'timestamp' => time(),
                                'type' => 'text',
                                'text' => [
                                    'body' => 'Test webhook message'
                                ]
                            ]
                        ]
                    ],
                    'field' => 'messages'
                ]
            ]
        ]
    ]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $webhookUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testWebhookData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Hub-Signature: test_signature'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "   ❌ Erreur cURL: $error\n";
} else {
    echo "   ✓ Webhook envoyé\n";
    echo "   Code HTTP: $httpCode\n";
    if ($response) {
        echo "   Réponse: $response\n";
    }
}

echo "\n4. Configuration suggérée pour WhatsApp Business:\n";
echo "   URL du webhook: $webhookUrl\n";
echo "   Token de vérification: $verifyToken\n";
echo "\n";
echo "   Pour configurer dans Meta Business Suite:\n";
echo "   1. Allez dans Meta for Developers\n";
echo "   2. Sélectionnez votre app\n";
echo "   3. WhatsApp > Configuration\n";
echo "   4. Webhook > Edit\n";
echo "   5. Callback URL: $webhookUrl\n";
echo "   6. Verify Token: $verifyToken\n";
echo "   7. Webhook fields: messages, message_status\n";

echo "\nTest terminé.\n";