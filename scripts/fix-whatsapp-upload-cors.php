<?php

/**
 * Script pour diagnostiquer et corriger les problèmes CORS pour l'upload WhatsApp
 */

// Vérifier et créer le fichier .htaccess si nécessaire
$htaccessPath = __DIR__ . '/../public/api/.htaccess';
$htaccessContent = <<<'EOD'
<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "http://localhost:5173"
    Header set Access-Control-Allow-Methods "GET, POST, OPTIONS"
    Header set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With"
    Header set Access-Control-Allow-Credentials "true"
    Header set Access-Control-Max-Age "3600"
</IfModule>

# Handle OPTIONS requests
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_METHOD} OPTIONS
    RewriteRule ^(.*)$ $1 [R=200,L]
</IfModule>
EOD;

// Créer le répertoire api s'il n'existe pas
$apiDir = dirname($htaccessPath);
if (!is_dir($apiDir)) {
    mkdir($apiDir, 0755, true);
    echo "Répertoire créé : $apiDir\n";
}

// Créer ou mettre à jour le fichier .htaccess
if (!file_exists($htaccessPath)) {
    file_put_contents($htaccessPath, $htaccessContent);
    echo "Fichier .htaccess créé : $htaccessPath\n";
} else {
    echo "Fichier .htaccess existe déjà : $htaccessPath\n";
}

// Vérifier les headers CORS dans tous les fichiers PHP pertinents
$phpFiles = [
    __DIR__ . '/../public/api.php',
    __DIR__ . '/../public/graphql.php',
    __DIR__ . '/../public/api/whatsapp/upload.php',
    __DIR__ . '/../public/whatsapp/upload.php'
];

$correctHeaders = <<<'EOD'
// CORS Headers
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Max-Age: 3600");

// Handle OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
EOD;

foreach ($phpFiles as $file) {
    if (file_exists($file)) {
        echo "\nVérification de : $file\n";
        
        $content = file_get_contents($file);
        
        // Vérifier si les headers CORS sont déjà présents
        if (strpos($content, 'Access-Control-Allow-Origin') !== false) {
            echo "- Headers CORS déjà présents\n";
            
            // Vérifier l'origine
            if (strpos($content, 'localhost:3000') !== false) {
                echo "- ATTENTION : Origin configuré sur localhost:3000 au lieu de localhost:5173\n";
                echo "  Correction nécessaire !\n";
            } elseif (strpos($content, 'localhost:5173') !== false) {
                echo "- Origin correct : localhost:5173\n";
            }
            
            // Vérifier session_start() et sa position
            if (strpos($content, 'session_start()') !== false) {
                $sessionPos = strpos($content, 'session_start()');
                $headerPos = strpos($content, 'header("Access-Control');
                
                if ($sessionPos < $headerPos) {
                    echo "- ATTENTION : session_start() est appelé AVANT les headers CORS\n";
                    echo "  Cela peut causer des problèmes !\n";
                }
            }
        } else {
            echo "- Headers CORS manquants\n";
        }
    } else {
        echo "\nFichier non trouvé : $file\n";
    }
}

// Vérifier la configuration du frontend
echo "\n\nVérification du frontend :\n";

$frontendFiles = [
    __DIR__ . '/../frontend/src/services/api.ts',
    __DIR__ . '/../frontend/vite.config.ts',
    __DIR__ . '/../frontend/src/components/whatsapp/WhatsAppMediaUpload.vue'
];

foreach ($frontendFiles as $file) {
    if (file_exists($file)) {
        echo "\n$file :\n";
        $content = file_get_contents($file);
        
        // Vérifier les configurations d'URL
        if (strpos($content, 'localhost:8000') !== false) {
            echo "- Utilise localhost:8000\n";
        }
        if (strpos($content, 'localhost:5173') !== false) {
            echo "- Utilise localhost:5173\n";
        }
        if (strpos($content, '/api/whatsapp/upload.php') !== false) {
            echo "- URL d'upload : /api/whatsapp/upload.php\n";
        }
        if (strpos($content, '/whatsapp/upload.php') !== false) {
            echo "- URL d'upload : /whatsapp/upload.php\n";
        }
    }
}

// Test de la requête OPTIONS
echo "\n\nTest de la requête OPTIONS :\n";
$testUrl = 'http://localhost:8000/api/whatsapp/upload.php';

$ch = curl_init($testUrl);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'OPTIONS');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Origin: http://localhost:5173',
    'Access-Control-Request-Method: POST',
    'Access-Control-Request-Headers: Content-Type'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Code HTTP : $httpCode\n";
echo "Headers de réponse :\n";
echo $response . "\n";

// Recommandations
echo "\n\nRecommandations :\n";
echo "1. Assurez-vous que le serveur PHP est redémarré après les modifications\n";
echo "2. Vérifiez que Apache/Nginx autorise les headers personnalisés\n";
echo "3. Testez l'upload depuis le frontend après les corrections\n";
echo "4. Si le problème persiste, vérifiez les logs du serveur\n";