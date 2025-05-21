<?php
/**
 * Utilitaire de vérification de format de numéro pour WhatsApp
 * Basé sur les recommandations de l'API Meta
 */

require_once __DIR__ . '/../vendor/autoload.php';
use App\Services\PhoneNumberNormalizerService;

// Fonction pour vérifier si un numéro est au format WhatsApp correct
function isValidWhatsAppFormat($phoneNumber) {
    // WhatsApp utilise le format E.164
    // Format: +[code pays][numéro de téléphone]
    // Sans espaces, tirets ou autres caractères
    return preg_match('/^\+[1-9]\d{1,14}$/', $phoneNumber) === 1;
}

// Fonction pour diagnostiquer les problèmes de formatage
function diagnoseWhatsAppNumberFormat($phoneNumber) {
    $issues = [];
    
    // Vérifier si commençe par +
    if (substr($phoneNumber, 0, 1) !== '+') {
        $issues[] = "Le numéro ne commence pas par le caractère +";
    }
    
    // Vérifier les caractères non autorisés
    if (preg_match('/[^0-9+]/', $phoneNumber)) {
        $issues[] = "Le numéro contient des caractères non autorisés (espaces, tirets, parenthèses, etc.)";
    }
    
    // Vérifier la longueur
    $digitsOnly = preg_replace('/[^0-9]/', '', $phoneNumber);
    if (strlen($digitsOnly) < 5) {
        $issues[] = "Le numéro est trop court";
    }
    if (strlen($digitsOnly) > 15) {
        $issues[] = "Le numéro est trop long (max 15 chiffres)";
    }
    
    // Vérifier si code pays commence par 0
    if (preg_match('/^\+0/', $phoneNumber)) {
        $issues[] = "Le code pays ne peut pas commencer par 0";
    }
    
    return $issues;
}

// Récupérer le numéro à tester depuis l'argument de ligne de commande ou demander à l'utilisateur
$testNumber = $argv[1] ?? null;

if (!$testNumber) {
    echo "Entrez le numéro de téléphone à vérifier : ";
    $testNumber = trim(fgets(STDIN));
}

// Créer le service de normalisation
$normalizer = new PhoneNumberNormalizerService();

echo "\n=== Vérification de format pour WhatsApp ===\n";
echo "Numéro testé : $testNumber\n";

// Normaliser le numéro avec notre service
$normalizedNumber = $normalizer->normalize($testNumber);
echo "Numéro normalisé : $normalizedNumber\n";

// Vérifier si le numéro est au format WhatsApp valide
$isValid = isValidWhatsAppFormat($normalizedNumber);
echo "Format WhatsApp valide : " . ($isValid ? "OUI ✓" : "NON ✗") . "\n";

// Si non valide, diagnostiquer les problèmes
if (!$isValid) {
    echo "\nProblèmes détectés avec le format :\n";
    $issues = diagnoseWhatsAppNumberFormat($normalizedNumber);
    
    if (empty($issues)) {
        echo "Aucun problème évident détecté, mais le format n'est pas standard.\n";
    } else {
        foreach ($issues as $index => $issue) {
            echo " " . ($index + 1) . ". $issue\n";
        }
    }
    
    // Suggérer une correction
    $correctedNumber = '+' . preg_replace('/[^0-9]/', '', $normalizedNumber);
    
    // Si le numéro commence par un code pays avec 0, supprimer le 0
    if (substr($correctedNumber, 1, 1) === '0') {
        $correctedNumber = '+' . substr($correctedNumber, 2);
    }
    
    echo "\nFormat suggéré : $correctedNumber\n";
}

echo "\n=== Formats alternatifs pour ce numéro ===\n";
$possibleFormats = $normalizer->getPossibleFormats($testNumber);
foreach ($possibleFormats as $index => $format) {
    $isValidFormat = isValidWhatsAppFormat($format);
    echo ($index + 1) . ". $format " . ($isValidFormat ? "(Format WhatsApp valide ✓)" : "(Non valide pour WhatsApp ✗)") . "\n";
}

echo "\n=== Informations sur les standards de numérotation ===\n";
echo "WhatsApp utilise le format E.164 pour les numéros de téléphone :\n";
echo "- Commence par le caractère + (plus)\n";
echo "- Suivi du code pays (1-3 chiffres, ne commence pas par 0)\n";
echo "- Suivi du numéro de téléphone\n";
echo "- Pas d'espaces, tirets ou autres caractères\n";
echo "- Maximum 15 chiffres au total (hors le +)\n";
echo "\nExemple: +22507XXXXXXXX (pour la Côte d'Ivoire)\n";

echo "\n=== Recommandations pour l'API ===\n";
echo "Pour l'API WhatsApp Business, toujours :\n";
echo "1. Normaliser les numéros au format E.164 (avec le +)\n";
echo "2. Supprimer tous les caractères non numériques (sauf le +)\n";
echo "3. S'assurer que le code pays ne commence pas par 0\n";
echo "4. Vérifier que la longueur totale est correcte\n";