<?php
/**
 * Script de vérification de la configuration WhatsApp
 * Vérifie que toutes les variables d'environnement sont correctement configurées
 */

echo "=== Vérification de la configuration WhatsApp ===\n\n";

// Chargement de la configuration
$configFile = dirname(__DIR__) . '/src/config/whatsapp.php';
if (!file_exists($configFile)) {
    echo "❌ Fichier de configuration introuvable : $configFile\n";
    exit(1);
}

$config = require $configFile;

// Variables obligatoires
$requiredVars = [
    'app_id' => 'WHATSAPP_APP_ID',
    'phone_number_id' => 'WHATSAPP_PHONE_NUMBER_ID',
    'whatsapp_business_account_id' => 'WHATSAPP_WABA_ID',
    'access_token' => 'WHATSAPP_ACCESS_TOKEN',
    'webhook_verify_token' => 'WHATSAPP_WEBHOOK_VERIFY_TOKEN',
];

$errors = [];
$warnings = [];

echo "Vérification des variables obligatoires :\n";
foreach ($requiredVars as $configKey => $envVar) {
    if (empty($config[$configKey])) {
        echo "❌ $envVar non définie\n";
        $errors[] = "$envVar doit être définie";
    } else {
        echo "✅ $envVar OK\n";
    }
}

echo "\nVérification des variables optionnelles :\n";

// API Version
if (empty($config['api_version'])) {
    echo "⚠️  API Version non définie (par défaut : v22.0)\n";
    $warnings[] = "WHATSAPP_API_VERSION non définie, utilisation de v22.0 par défaut";
} else {
    echo "✅ API Version : {$config['api_version']}\n";
}

// Webhook URL
if (empty($config['webhook_callback_url'])) {
    echo "⚠️  Webhook URL non définie\n";
    $warnings[] = "WHATSAPP_WEBHOOK_CALLBACK_URL non définie, nécessaire en production";
} else {
    echo "✅ Webhook URL : {$config['webhook_callback_url']}\n";
}

// Vérification de la validité du token (format seulement)
if (!empty($config['access_token'])) {
    if (strlen($config['access_token']) < 50) {
        echo "⚠️  Le token d'accès semble trop court\n";
        $warnings[] = "Le token d'accès semble invalide ou trop court";
    }
}

// Test de connectivité à l'API (optionnel)
echo "\nTest de connexion à l'API Meta :\n";
if (!empty($config['access_token']) && !empty($config['app_id'])) {
    $debugUrl = "https://graph.facebook.com/debug_token?input_token={$config['access_token']}&access_token={$config['access_token']}";
    
    try {
        $ch = curl_init($debugUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if (isset($data['data']['is_valid']) && $data['data']['is_valid']) {
                echo "✅ Token valide\n";
                if (isset($data['data']['expires_at'])) {
                    $expiresAt = date('Y-m-d H:i:s', $data['data']['expires_at']);
                    echo "   Expire le : $expiresAt\n";
                }
            } else {
                echo "❌ Token invalide\n";
                $errors[] = "Le token d'accès est invalide";
            }
        } else {
            echo "⚠️  Impossible de vérifier le token (HTTP $httpCode)\n";
            $warnings[] = "Impossible de vérifier la validité du token";
        }
    } catch (Exception $e) {
        echo "⚠️  Erreur lors du test : " . $e->getMessage() . "\n";
        $warnings[] = "Erreur lors du test de connexion : " . $e->getMessage();
    }
} else {
    echo "⏭️  Test ignoré (token ou app_id manquant)\n";
}

// Résumé
echo "\n=== Résumé ===\n";
if (empty($errors)) {
    echo "✅ Configuration valide\n";
} else {
    echo "❌ Configuration invalide\n";
    echo "Erreurs :\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

if (!empty($warnings)) {
    echo "\n⚠️  Avertissements :\n";
    foreach ($warnings as $warning) {
        echo "  - $warning\n";
    }
}

// Suggestions
echo "\n=== Suggestions ===\n";
echo "1. Créez un fichier .env.whatsapp basé sur .env.whatsapp.example\n";
echo "2. Configurez les variables d'environnement dans votre système\n";
echo "3. Pour le développement local, utilisez localtunnel pour le webhook\n";
echo "4. Générez un token système de longue durée (60 jours) dans Meta Business Manager\n";

exit(empty($errors) ? 0 : 1);