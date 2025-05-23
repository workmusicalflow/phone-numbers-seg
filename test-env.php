<?php
// Charger l'autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Charger les variables d'environnement depuis .env
if (class_exists('\\Dotenv\\Dotenv') && file_exists(__DIR__ . '/.env')) {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    echo "Fichier .env chargé avec succès\n";
} else {
    echo "Impossible de charger le fichier .env\n";
}

// Liste des variables WhatsApp à vérifier
$whatsappVars = [
    'WHATSAPP_APP_ID',
    'WHATSAPP_PHONE_NUMBER_ID',
    'WHATSAPP_WABA_ID',
    'WHATSAPP_API_VERSION',
    'WHATSAPP_ACCESS_TOKEN',
    'WHATSAPP_API_TOKEN',
    'WHATSAPP_WEBHOOK_VERIFY_TOKEN',
    'WHATSAPP_WEBHOOK_CALLBACK_URL'
];

// Vérifier chaque variable
echo "\nVariables WhatsApp:\n";
echo "=================\n";
foreach ($whatsappVars as $var) {
    $value = $_ENV[$var] ?? getenv($var) ?? 'Non définie';
    $maskedValue = $var === 'WHATSAPP_ACCESS_TOKEN' || $var === 'WHATSAPP_API_TOKEN' 
        ? substr($value, 0, 10) . '...' . (strlen($value) > 20 ? substr($value, -10) : '') 
        : $value;
    
    echo "$var: $maskedValue\n";
}