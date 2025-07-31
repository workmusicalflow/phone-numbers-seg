<?php
/**
 * Script de test du webhook WhatsApp
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Test du webhook WhatsApp\n\n";

// Vérifier les paramètres GET
echo "Paramètres GET:\n";
var_dump($_GET);
echo "\n";

// Tester la vérification simple
$hubMode = $_GET['hub_mode'] ?? '';
$hubToken = $_GET['hub_verify_token'] ?? '';
$hubChallenge = $_GET['hub_challenge'] ?? '';

echo "Mode: $hubMode\n";
echo "Token: $hubToken\n";
echo "Challenge: $hubChallenge\n\n";

// Vérification simple
$expectedToken = 'oracle_whatsapp_verify_token_2025';

if ($hubMode === 'subscribe' && $hubToken === $expectedToken) {
    echo "✓ Vérification réussie\n";
    echo $hubChallenge;
} else {
    echo "✗ Vérification échouée\n";
    http_response_code(403);
}