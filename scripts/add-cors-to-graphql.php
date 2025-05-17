<?php

$graphqlFile = __DIR__ . '/../public/graphql.php';
$content = file_get_contents($graphqlFile);

// Vérifier si CORS est déjà configuré
if (strpos($content, 'Access-Control-Allow-Origin') !== false) {
    echo "CORS est déjà configuré dans graphql.php\n";
    exit(0);
}

// Ajouter les headers CORS au début du fichier, après <?php
$corsHeaders = <<<'PHP'

// CORS headers for cross-origin requests
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = ['http://localhost:5173', 'http://localhost:5174'];

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

PHP;

// Insérer après la balise PHP d'ouverture
$newContent = str_replace('<?php', '<?php' . $corsHeaders, $content);

// Écrire le fichier modifié
file_put_contents($graphqlFile, $newContent);

echo "Headers CORS ajoutés à graphql.php\n";