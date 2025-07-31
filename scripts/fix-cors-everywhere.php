<?php

declare(strict_types=1);

// Script pour corriger les headers CORS dans tous les fichiers PHP publics

$publicDir = dirname(__DIR__) . '/public';
$frontendPort = '5173'; // Le port actuel du frontend Vite
$backendPort = '8000';

// Pattern pour rechercher les headers CORS
$patterns = [
    '/header\s*\(\s*[\'"]Access-Control-Allow-Origin[\'"].*?\);/i',
    '/header\s*\(\s*[\'"]Access-Control-Allow-Credentials[\'"].*?\);/i',
    '/header\s*\(\s*[\'"]Access-Control-Allow-Methods[\'"].*?\);/i',
    '/header\s*\(\s*[\'"]Access-Control-Allow-Headers[\'"].*?\);/i',
    '/header\s*\(\s*[\'"]Access-Control-Max-Age[\'"].*?\);/i',
];

// Headers CORS corrects
$correctHeaders = [
    'header("Access-Control-Allow-Origin: http://localhost:' . $frontendPort . '");',
    'header("Access-Control-Allow-Credentials: true");',
    'header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");',
    'header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");',
    'header("Access-Control-Max-Age: 3600");',
];

// Fonction pour ajouter les headers CORS si pas présents
function addCorsHeaders($content) {
    global $correctHeaders;
    
    // Chercher la première balise PHP
    $phpTagPattern = '/<\?php/i';
    if (!preg_match($phpTagPattern, $content)) {
        return $content;
    }
    
    // Vérifier si les headers CORS sont déjà présents
    $hasCors = false;
    foreach ($GLOBALS['patterns'] as $pattern) {
        if (preg_match($pattern, $content)) {
            $hasCors = true;
            break;
        }
    }
    
    if (!$hasCors) {
        // Ajouter les headers après la première balise PHP
        $corsBlock = "\n\n// CORS Headers\n" . implode("\n", $correctHeaders) . "\n";
        $content = preg_replace('/<\?php/i', '<?php' . $corsBlock, $content, 1);
    }
    
    return $content;
}

// Fonction récursive pour parcourir les dossiers
function processPHPFiles($dir) {
    global $patterns, $correctHeaders;
    
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            processPHPFiles($path);
        } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            echo "Traitement de : $path\n";
            
            $content = file_get_contents($path);
            $originalContent = $content;
            
            // Remplacer les anciens headers CORS
            foreach ($patterns as $pattern) {
                $content = preg_replace($pattern, '', $content);
            }
            
            // Ajouter les headers corrects
            $content = addCorsHeaders($content);
            
            // Vérifier les requêtes OPTIONS
            if (!strpos($content, "REQUEST_METHOD'] === 'OPTIONS'")) {
                // Ajouter la gestion des OPTIONS après les headers CORS
                $optionsHandler = <<<'PHP'

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
PHP;
                $content = preg_replace(
                    '/(header\("Access-Control-Max-Age: 3600"\);)/',
                    '$1' . $optionsHandler,
                    $content
                );
            }
            
            if ($content !== $originalContent) {
                file_put_contents($path, $content);
                echo "✅ Mis à jour : $path\n";
            } else {
                echo "⏭️ Pas de changement : $path\n";
            }
        }
    }
}

// Fichiers spécifiques à corriger
$specificFiles = [
    '/public/api/whatsapp/upload.php',
    '/public/whatsapp/upload.php',
    '/public/graphql.php',
    '/public/api.php',
    '/public/index.php'
];

foreach ($specificFiles as $file) {
    $filePath = dirname(__DIR__) . $file;
    if (file_exists($filePath)) {
        echo "\nTraitement du fichier spécifique : $filePath\n";
        
        $content = file_get_contents($filePath);
        $originalContent = $content;
        
        // Remplacer les headers existants
        foreach ($patterns as $pattern) {
            $content = preg_replace($pattern, '', $content);
        }
        
        // Ajouter les headers corrects
        $content = addCorsHeaders($content);
        
        // Corriger le port dans les contenus existants
        $content = str_replace('localhost:3000', 'localhost:' . $frontendPort, $content);
        
        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            echo "✅ Fichier corrigé avec succès\n";
        }
    }
}

// Créer un fichier .htaccess global pour le dossier public
$htaccessContent = <<<'HTACCESS'
<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "http://localhost:5173"
    Header set Access-Control-Allow-Credentials "true"
    Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With"
    Header set Access-Control-Max-Age "3600"
</IfModule>

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_METHOD} OPTIONS
    RewriteRule ^(.*)$ $1 [R=200,L]
</IfModule>
HTACCESS;

file_put_contents($publicDir . '/.htaccess', $htaccessContent);
echo "\n✅ Fichier .htaccess créé dans le dossier public\n";

echo "\n🎉 Configuration CORS corrigée avec succès !\n";