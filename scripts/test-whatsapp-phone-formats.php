<?php
/**
 * Script de test pour vérifier les différents formats de numéros de téléphone
 * et leur compatibilité avec l'API WhatsApp
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entities\User;
use App\Entities\WhatsApp\WhatsAppMessageHistory;
use App\Services\PhoneNumberNormalizerService;
use GuzzleHttp\Client;

// Le numéro cible de base
$basePhoneNumber = '2250777104936';
$message = "Test de format de numéro WhatsApp (%s) envoyé à %s";

// Différents formats de numéros à tester
$numberFormats = [
    // Format international avec +
    '+2250777104936' => 'Format international avec + (E.164)',
    
    // Format international sans +
    '2250777104936' => 'Format international sans +',
    
    // Format avec séparation du code pays
    '+225 0777104936' => 'Format avec espace après code pays',
    
    // Format local avec 0 initial
    '0777104936' => 'Format local avec 0 initial',
    
    // Format sans 0 initial
    '777104936' => 'Format sans 0 initial',
    
    // Format avec 225 et sans 0
    '225777104936' => 'Format avec 225 et sans 0',
    
    // Format avec 225 et avec 0
    '2250777104936' => 'Format avec 225 et avec 0',
    
    // Format avec tirets
    '+225-077-710-4936' => 'Format avec tirets',
    
    // Format avec espaces
    '+225 077 710 4936' => 'Format avec espaces',
    
    // Format avec parenthèses pour le code pays
    '(+225) 0777104936' => 'Format avec parenthèses'
];

echo "=== Test des formats de numéros de téléphone pour WhatsApp ===\n\n";

// Charger la configuration
$config = require __DIR__ . '/../src/config/whatsapp.php';

// Vérifier la configuration
if (empty($config['phone_number_id'])) {
    echo "Erreur : WHATSAPP_PHONE_NUMBER_ID n'est pas défini dans l'environnement\n";
    echo "Configuration actuelle : " . json_encode($config, JSON_PRETTY_PRINT) . "\n";
    exit(1);
}

// Obtenir l'EntityManager via le bootstrap
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

// Charger l'utilisateur admin
$userRepository = $entityManager->getRepository(User::class);
$user = $userRepository->find(1); // ID admin = 1

if (!$user) {
    echo "Erreur : utilisateur admin non trouvé\n";
    exit(1);
}

echo "Utilisateur : " . $user->getUsername() . "\n\n";

// Créer le service de normalisation
$normalizer = new PhoneNumberNormalizerService();

// Créer le client HTTP
$httpClient = new Client([
    'base_uri' => $config['base_url'] ?? 'https://graph.facebook.com/',
    'timeout' => 30,
    'headers' => [
        'Authorization' => 'Bearer ' . $config['access_token'],
        'Content-Type' => 'application/json'
    ]
]);

// Endpoint pour les messages
$endpoint = $config['api_version'] . '/' . $config['phone_number_id'] . '/messages';

// Résultats des tests
$results = [];

// Tester chaque format
foreach ($numberFormats as $testNumber => $description) {
    echo "\n--- Test du format: {$description} ---\n";
    echo "Numéro original: {$testNumber}\n";
    
    // Normaliser le numéro pour l'affichage
    $normalizedNumber = $normalizer->normalize($testNumber);
    echo "Numéro normalisé par notre service: {$normalizedNumber}\n";
    
    // Préparer le payload avec le numéro original
    $payloadOriginal = [
        'messaging_product' => 'whatsapp',
        'to' => $testNumber,
        'type' => 'text',
        'text' => [
            'body' => sprintf($message, "format original", $testNumber)
        ]
    ];
    
    // Tester avec le numéro original
    try {
        $response = $httpClient->post($endpoint, [
            'json' => $payloadOriginal
        ]);
        
        $result = json_decode($response->getBody()->getContents(), true);
        
        if (isset($result['messages'][0]['id'])) {
            echo "✓ Message envoyé avec succès au format original!\n";
            echo "ID WhatsApp : " . $result['messages'][0]['id'] . "\n";
            
            $results[$testNumber]['original'] = [
                'success' => true,
                'message_id' => $result['messages'][0]['id']
            ];
            
            // Sauvegarder dans l'historique
            $messageHistory = new WhatsAppMessageHistory();
            $messageHistory->setWabaMessageId($result['messages'][0]['id']);
            $messageHistory->setPhoneNumber($testNumber);
            $messageHistory->setOracleUser($user);
            $messageHistory->setType('text');
            $messageHistory->setDirection('sent');
            $messageHistory->setStatus('sent');
            $messageHistory->setContent(sprintf($message, "format original", $testNumber));
            $messageHistory->setCreatedAt(new \DateTime());
            
            $entityManager->persist($messageHistory);
            $entityManager->flush();
        } else {
            echo "Réponse inattendue : " . json_encode($result) . "\n";
            $results[$testNumber]['original'] = [
                'success' => false,
                'error' => 'Réponse inattendue'
            ];
        }
        
    } catch (\GuzzleHttp\Exception\ClientException $e) {
        $errorBody = $e->getResponse()->getBody()->getContents();
        $errorData = json_decode($errorBody, true);
        
        echo "✗ Erreur API avec format original : " . ($errorData['error']['message'] ?? $e->getMessage()) . "\n";
        $results[$testNumber]['original'] = [
            'success' => false,
            'error' => $errorData['error']['message'] ?? $e->getMessage()
        ];
    } catch (Exception $e) {
        echo "✗ Erreur avec format original : " . $e->getMessage() . "\n";
        $results[$testNumber]['original'] = [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
    
    // Attendre un peu pour éviter de dépasser les limites d'API
    sleep(2);
    
    // Tester avec le numéro normalisé (uniquement si différent)
    if ($normalizedNumber !== $testNumber) {
        echo "\nTest avec le numéro normalisé : $normalizedNumber\n";
        
        $payloadNormalized = [
            'messaging_product' => 'whatsapp',
            'to' => $normalizedNumber,
            'type' => 'text',
            'text' => [
                'body' => sprintf($message, "format normalisé", $normalizedNumber)
            ]
        ];
        
        try {
            $response = $httpClient->post($endpoint, [
                'json' => $payloadNormalized
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            
            if (isset($result['messages'][0]['id'])) {
                echo "✓ Message envoyé avec succès au format normalisé!\n";
                echo "ID WhatsApp : " . $result['messages'][0]['id'] . "\n";
                
                $results[$testNumber]['normalized'] = [
                    'success' => true,
                    'message_id' => $result['messages'][0]['id']
                ];
                
                // Sauvegarder dans l'historique
                $messageHistory = new WhatsAppMessageHistory();
                $messageHistory->setWabaMessageId($result['messages'][0]['id']);
                $messageHistory->setPhoneNumber($normalizedNumber);
                $messageHistory->setOracleUser($user);
                $messageHistory->setType('text');
                $messageHistory->setDirection('sent');
                $messageHistory->setStatus('sent');
                $messageHistory->setContent(sprintf($message, "format normalisé", $normalizedNumber));
                $messageHistory->setCreatedAt(new \DateTime());
                
                $entityManager->persist($messageHistory);
                $entityManager->flush();
            } else {
                echo "Réponse inattendue : " . json_encode($result) . "\n";
                $results[$testNumber]['normalized'] = [
                    'success' => false,
                    'error' => 'Réponse inattendue'
                ];
            }
            
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $errorBody = $e->getResponse()->getBody()->getContents();
            $errorData = json_decode($errorBody, true);
            
            echo "✗ Erreur API avec format normalisé : " . ($errorData['error']['message'] ?? $e->getMessage()) . "\n";
            $results[$testNumber]['normalized'] = [
                'success' => false,
                'error' => $errorData['error']['message'] ?? $e->getMessage()
            ];
        } catch (Exception $e) {
            echo "✗ Erreur avec format normalisé : " . $e->getMessage() . "\n";
            $results[$testNumber]['normalized'] = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
        
        // Attendre un peu pour éviter de dépasser les limites d'API
        sleep(2);
    }
}

// Afficher un résumé des résultats
echo "\n\n=== RÉSUMÉ DES TESTS ===\n";
echo "Format | Test format original | Test format normalisé\n";
echo "-------|---------------------|---------------------\n";

foreach ($numberFormats as $testNumber => $description) {
    $originalResult = isset($results[$testNumber]['original']) ? 
        ($results[$testNumber]['original']['success'] ? "✓ OK" : "✗ ÉCHEC") : "Non testé";
    
    $normalizedResult = isset($results[$testNumber]['normalized']) ? 
        ($results[$testNumber]['normalized']['success'] ? "✓ OK" : "✗ ÉCHEC") : "Non testé";
    
    echo sprintf("%-30s | %-20s | %-20s\n", 
        $description . " (" . $testNumber . ")",
        $originalResult,
        $normalizedResult
    );
}

echo "\n=== CONCLUSION ===\n";
// Compter les succès pour déterminer le meilleur format
$successCounts = [];
foreach ($results as $number => $result) {
    if (!empty($result['original']['success'])) {
        $format = $number;
        $successCounts[$format] = ($successCounts[$format] ?? 0) + 1;
    }
    if (!empty($result['normalized']['success'])) {
        $format = $normalizer->normalize($number);
        $successCounts[$format] = ($successCounts[$format] ?? 0) + 1;
    }
}

// Trouver le format avec le plus de succès
arsort($successCounts);
$bestFormats = array_keys(array_filter($successCounts, function($count) use ($successCounts) {
    return $count === reset($successCounts);
}));

if (!empty($bestFormats)) {
    echo "Format(s) recommandé(s) pour l'API WhatsApp :\n";
    foreach ($bestFormats as $format) {
        echo "- $format\n";
    }
    
    echo "\nSuggestion d'implémentation : Toujours normaliser les numéros au format ";
    echo reset($bestFormats) . " avant de les envoyer à l'API WhatsApp.\n";
} else {
    echo "Aucun format n'a fonctionné correctement. Vérifiez la configuration de l'API ou les autorisations du compte.\n";
}

echo "\n=== Fin du test ===\n";