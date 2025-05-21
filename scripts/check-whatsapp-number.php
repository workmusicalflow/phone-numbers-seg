<?php
/**
 * Utilitaire de vérification et de formatage des numéros WhatsApp
 * 
 * Vérifie si un numéro est au format E.164 (standard international)
 * et le corrige si nécessaire.
 * 
 * Usage: php check-whatsapp-number.php <phone_number>
 */

// Vérifier les arguments
if ($argc < 2) {
    die("Usage: php check-whatsapp-number.php <phone_number>\n");
}

// Récupérer le numéro de téléphone
$phoneNumber = $argv[1];

// Fonction pour normaliser un numéro en format E.164
function normalizeToE164($number, $defaultCountryCode = '225')
{
    // Supprimer tous les caractères non numériques sauf le + initial
    $hasPlus = substr($number, 0, 1) === '+';
    $digitsOnly = preg_replace('/[^0-9]/', '', $number);
    
    // Vérifier si le numéro commence déjà par un code pays
    $startsWithCountryCode = false;
    if ($hasPlus) {
        // Le numéro a un + initial, donc il devrait déjà avoir un code pays
        $startsWithCountryCode = true;
    } elseif (substr($digitsOnly, 0, strlen($defaultCountryCode)) === $defaultCountryCode) {
        // Le numéro commence par le code pays par défaut
        $startsWithCountryCode = true;
    }
    
    // Supprimer le 0 initial si présent (après le code pays)
    if ($startsWithCountryCode && strlen($defaultCountryCode) > 0) {
        if (substr($digitsOnly, strlen($defaultCountryCode), 1) === '0') {
            $digitsOnly = substr($digitsOnly, 0, strlen($defaultCountryCode)) . 
                         substr($digitsOnly, strlen($defaultCountryCode) + 1);
        }
    } elseif (substr($digitsOnly, 0, 1) === '0') {
        // Supprimer le 0 initial et ajouter le code pays
        $digitsOnly = $defaultCountryCode . substr($digitsOnly, 1);
    } else {
        // Pas de 0 initial et pas de code pays détecté, ajouter le code pays
        if (!$startsWithCountryCode) {
            $digitsOnly = $defaultCountryCode . $digitsOnly;
        }
    }
    
    // Ajouter le + initial pour le format E.164
    return '+' . $digitsOnly;
}

// Analyse du numéro fourni
echo "Numéro original: $phoneNumber\n";

// Normaliser le numéro
$normalized = normalizeToE164($phoneNumber);
echo "Numéro normalisé (E.164): $normalized\n";

// Extraire le format sans le +
$withoutPlus = substr($normalized, 1);
echo "Format sans +: $withoutPlus\n";

// Identifier les composants du numéro
$countryCode = '';
$subscriberNumber = '';

// Détection du code pays (pour la Côte d'Ivoire)
if (substr($withoutPlus, 0, 3) === '225') {
    $countryCode = '225';
    $subscriberNumber = substr($withoutPlus, 3);
    echo "Code pays: +$countryCode\n";
    echo "Numéro d'abonné: $subscriberNumber\n";
    
    // Vérifier si le numéro commence par un 0
    if (substr($subscriberNumber, 0, 1) === '0') {
        echo "⚠️ Attention: Le numéro d'abonné commence par 0, cela pourrait causer des problèmes.\n";
        $correctedSubscriber = substr($subscriberNumber, 1);
        echo "Suggestion: Utilisez plutôt +$countryCode$correctedSubscriber\n";
    }
    
    // Vérifier la longueur du numéro d'abonné (pour la Côte d'Ivoire, généralement 8 chiffres)
    if (strlen($subscriberNumber) !== 10 && strlen($subscriberNumber) !== 8) {
        echo "⚠️ Attention: Le numéro d'abonné devrait avoir 8 ou 10 chiffres pour la Côte d'Ivoire.\n";
    }
} else {
    echo "⚠️ Code pays non reconnu comme Côte d'Ivoire (225).\n";
}

// Générer des formats alternatifs pour les tests
echo "\nFormats recommandés pour les tests WhatsApp:\n";
echo "1. Format E.164 avec +: $normalized\n";
echo "2. Format E.164 sans +: $withoutPlus\n";
if (!empty($countryCode) && !empty($subscriberNumber)) {
    echo "3. Format sans code pays: $subscriberNumber\n";
    
    // Si le numéro d'abonné ne commence pas par 0
    if (substr($subscriberNumber, 0, 1) !== '0') {
        echo "4. Format local (avec 0): 0$subscriberNumber\n";
    }
}

// Recommendations pour l'API WhatsApp
echo "\nRecommandations pour l'API WhatsApp:\n";
echo "- Utilisez toujours le format E.164 avec + ($normalized) dans les interfaces utilisateur\n";
echo "- Pour les appels API, testez avec et sans le + pour déterminer le format préféré\n";
echo "- Stockez les numéros au format E.164 dans la base de données pour une meilleure standardisation\n";
echo "- Implémentez une normalisation cohérente dans toute l'application\n";

// Vérification de la table whatsapp_message_history
try {
    $dbPath = __DIR__ . '/../var/database.sqlite';
    if (file_exists($dbPath)) {
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Vérifier si le numéro existe dans l'historique
        $withoutPlusEscaped = $pdo->quote($withoutPlus);
        $normalizedEscaped = $pdo->quote($normalized);
        
        $query = "SELECT * FROM whatsapp_message_history 
                 WHERE recipient_phone IN ($withoutPlusEscaped, $normalizedEscaped)
                 ORDER BY created_at DESC
                 LIMIT 5";
        
        $stmt = $pdo->query($query);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($results) > 0) {
            echo "\nHistorique des messages pour ce numéro:\n";
            foreach ($results as $row) {
                echo "- {$row['created_at']}: Template '{$row['template_name']}', Statut: {$row['status']}\n";
                echo "  Message ID: {$row['message_id']}\n";
                echo "  WhatsApp ID: {$row['wa_id']}\n";
            }
        } else {
            echo "\nAucun historique trouvé pour ce numéro dans la base de données.\n";
        }
    } else {
        echo "\nBase de données non trouvée à l'emplacement: $dbPath\n";
    }
} catch (PDOException $e) {
    echo "\nErreur lors de la vérification de l'historique: " . $e->getMessage() . "\n";
}