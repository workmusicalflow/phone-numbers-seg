<?php

declare(strict_types=1);

$token_info_path = __DIR__ . '/../docs/Meta-API-Cloud-wha-business/mes-info-API-cloud-Meta.md';

echo "Tentative de lecture du fichier: $token_info_path\n";

if (file_exists($token_info_path)) {
    $token_info = file_get_contents($token_info_path);
    
    echo "Contenu du fichier:\n";
    echo "-------------------------\n";
    echo $token_info;
    echo "\n-------------------------\n\n";
    
    echo "Tentative d'extraction du token...\n";
    
    // Essayer différents patterns d'extraction
    $patterns = [
        '/token d\'accès utilisateur système.*?:(.+)$/',
        '/token d\'accès utilisateur système.*?:\s*(.+)$/',
        '/token d\'accès utilisateur système[^:]*:\s*(.+)$/'
    ];
    
    foreach ($patterns as $index => $pattern) {
        echo "Pattern $index: $pattern\n";
        if (preg_match($pattern, $token_info, $matches)) {
            $token = trim($matches[1]);
            echo "Token trouvé avec le pattern $index: " . substr($token, 0, 10) . "...\n";
        } else {
            echo "Aucun token trouvé avec le pattern $index\n";
        }
    }
    
    // Recherche de lignes contenant "token"
    if (preg_match_all('/.*token.*$/im', $token_info, $matches)) {
        echo "\nLignes contenant 'token':\n";
        foreach ($matches[0] as $line) {
            echo "- " . trim($line) . "\n";
        }
    }
    
} else {
    echo "Fichier introuvable: $token_info_path\n";
}

// Au lieu d'utiliser des regex, extrayons directement la ligne
echo "\nApproche alternative: extraction directe de la ligne 13\n";
$lines = explode("\n", $token_info);
if (isset($lines[12])) { // Index 12 correspond à la ligne 13 (base 0)
    $line13 = $lines[12];
    echo "Ligne 13: " . $line13 . "\n";
    
    if (strpos($line13, ':') !== false) {
        $token = trim(substr($line13, strpos($line13, ':') + 1));
        echo "Token extrait: " . substr($token, 0, 10) . "...\n";
    } else {
        echo "Pas de séparateur ':' trouvé dans la ligne 13\n";
    }
} else {
    echo "Ligne 13 introuvable dans le fichier\n";
}