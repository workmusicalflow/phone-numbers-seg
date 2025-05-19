<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\WhatsApp\WhatsAppApiClient;

// Configuration
$whatsappConfig = require __DIR__ . '/../src/config/whatsapp.php';

// Créer le client API
$apiClient = new WhatsAppApiClient($whatsappConfig);

// Test de l'upload de média
try {
    // Chemin vers un fichier test (créez un fichier image test)
    $testImagePath = __DIR__ . '/test-image.jpg';
    
    if (!file_exists($testImagePath)) {
        // Créer une image test si elle n'existe pas
        $image = imagecreate(200, 200);
        $color = imagecolorallocate($image, 100, 100, 100);
        imagefill($image, 0, 0, $color);
        imagejpeg($image, $testImagePath);
        imagedestroy($image);
        echo "Image test créée: $testImagePath\n";
    }
    
    // Tester l'upload via l'API Meta/WhatsApp
    $ch = curl_init();
    $phoneNumberId = $whatsappConfig['phone_number_id'];
    $url = "https://graph.facebook.com/{$whatsappConfig['api_version']}/{$phoneNumberId}/media";
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$whatsappConfig['access_token']}",
    ]);
    
    $postData = [
        'messaging_product' => 'whatsapp',
        'file' => new CURLFile($testImagePath, 'image/jpeg', 'test-image.jpg'),
    ];
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    echo "Response: $response\n";
    
    if ($error) {
        echo "CURL Error: $error\n";
    }
    
    // Décoder la réponse
    $result = json_decode($response, true);
    
    if (isset($result['id'])) {
        echo "✅ Upload réussi! Media ID: {$result['id']}\n";
        
        // Maintenant, envoyer un message avec ce média
        $recipientNumber = "237650000000"; // Remplacez par un numéro test
        $sendData = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $recipientNumber,
            'type' => 'image',
            'image' => [
                'id' => $result['id'],
                'caption' => 'Test image upload'
            ]
        ];
        
        $sendUrl = "https://graph.facebook.com/{$whatsappConfig['api_version']}/{$phoneNumberId}/messages";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $sendUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$whatsappConfig['access_token']}",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($sendData));
        
        $sendResponse = curl_exec($ch);
        $sendHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "\nEnvoi du message - HTTP Code: $sendHttpCode\n";
        echo "Response: $sendResponse\n";
        
    } else {
        echo "❌ Erreur upload: " . json_encode($result) . "\n";
    }
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}