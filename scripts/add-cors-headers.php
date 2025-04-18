<?php

/**
 * This script adds CORS headers to the main PHP entry points
 * to allow cross-origin requests from the frontend to the backend.
 */

// Files to modify
$files = [
    __DIR__ . '/../public/graphql.php',
    __DIR__ . '/../public/api.php',
    __DIR__ . '/../public/index.php'
];

// CORS headers to add
$corsHeaders = <<<'EOD'
// Add CORS headers
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit();
}

EOD;

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);

        // Check if CORS headers are already added
        if (strpos($content, 'Access-Control-Allow-Origin') === false) {
            // Find the position after the opening PHP tag
            $phpTagPos = strpos($content, '<?php');
            if ($phpTagPos !== false) {
                $insertPos = $phpTagPos + 5; // Position after <?php

                // Insert CORS headers after the PHP opening tag
                $newContent = substr($content, 0, $insertPos) . "\n\n" . $corsHeaders . substr($content, $insertPos);

                // Write the modified content back to the file
                file_put_contents($file, $newContent);
                echo "Added CORS headers to {$file}\n";
            } else {
                echo "Could not find PHP opening tag in {$file}\n";
            }
        } else {
            echo "CORS headers already exist in {$file}\n";
        }
    } else {
        echo "File not found: {$file}\n";
    }
}

echo "CORS headers have been added to the PHP entry points.\n";
