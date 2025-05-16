<?php
/**
 * Script pour surveiller les webhooks WhatsApp en temps réel
 */

$logDir = __DIR__ . '/../var/logs/whatsapp/';

// Couleurs pour le terminal
$colors = [
    'reset' => "\033[0m",
    'bold' => "\033[1m",
    'green' => "\033[32m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m",
    'red' => "\033[31m",
    'cyan' => "\033[36m"
];

echo $colors['cyan'] . $colors['bold'] . "=== Monitoring des webhooks WhatsApp ===" . $colors['reset'] . "\n\n";

// Trouver le fichier de log le plus récent
$files = glob($logDir . 'webhook_*.json');
if (empty($files)) {
    echo $colors['yellow'] . "Aucun fichier de log trouvé. En attente..." . $colors['reset'] . "\n";
    sleep(2);
    exit;
}

// Trier par date de modification (le plus récent en premier)
usort($files, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

$latestFile = $files[0];
echo $colors['green'] . "Surveillance du fichier : " . basename($latestFile) . $colors['reset'] . "\n\n";

// Position actuelle dans le fichier
$lastPosition = 0;

while (true) {
    clearstatcache();
    
    // Si le fichier a changé
    if (filesize($latestFile) > $lastPosition) {
        $handle = fopen($latestFile, 'r');
        fseek($handle, $lastPosition);
        
        $content = '';
        while (!feof($handle)) {
            $content .= fread($handle, 8192);
        }
        
        $lastPosition = ftell($handle);
        fclose($handle);
        
        // Parser et afficher les nouvelles entrées
        $entries = explode("\n\n", trim($content));
        foreach ($entries as $entry) {
            if (!empty($entry)) {
                $data = json_decode($entry, true);
                if ($data) {
                    displayEntry($data, $colors);
                }
            }
        }
    }
    
    // Vérifier s'il y a un nouveau fichier
    $newFiles = glob($logDir . 'webhook_*.json');
    if (!empty($newFiles)) {
        usort($newFiles, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        if ($newFiles[0] != $latestFile) {
            echo "\n" . $colors['yellow'] . "Nouveau fichier de log détecté : " . basename($newFiles[0]) . $colors['reset'] . "\n\n";
            $latestFile = $newFiles[0];
            $lastPosition = 0;
        }
    }
    
    sleep(1);
}

function displayEntry($data, $colors) {
    $type = $data['type'] ?? 'unknown';
    $timestamp = $data['timestamp'] ?? date('Y-m-d H:i:s');
    
    echo $colors['blue'] . "[" . $timestamp . "] " . $colors['reset'];
    
    switch ($type) {
        case 'verification':
            echo $colors['green'] . "VÉRIFICATION" . $colors['reset'] . " - ";
            echo "Mode: " . ($data['params']['hub_mode'] ?? 'N/A') . "\n";
            break;
            
        case 'incoming_message':
            echo $colors['bold'] . $colors['green'] . "MESSAGE ENTRANT" . $colors['reset'] . "\n";
            echo "  De: " . ($data['from'] ?? 'N/A') . "\n";
            echo "  Type: " . ($data['message_type'] ?? 'N/A') . "\n";
            if (isset($data['content'])) {
                echo "  Contenu: " . $colors['cyan'] . $data['content'] . $colors['reset'] . "\n";
            }
            echo "  ID: " . ($data['message_id'] ?? 'N/A') . "\n";
            break;
            
        case 'status_update':
            echo $colors['yellow'] . "STATUT" . $colors['reset'] . " - ";
            echo "Message ID: " . ($data['message_id'] ?? 'N/A') . " - ";
            echo "Statut: " . $colors['bold'] . ($data['status'] ?? 'N/A') . $colors['reset'] . "\n";
            if (isset($data['errors'])) {
                echo "  " . $colors['red'] . "Erreur: " . json_encode($data['errors']) . $colors['reset'] . "\n";
            }
            break;
            
        case 'notification':
            echo $colors['cyan'] . "NOTIFICATION" . $colors['reset'] . "\n";
            if (isset($data['payload'])) {
                echo "  " . substr(json_encode($data['payload']), 0, 200) . "...\n";
            }
            break;
            
        default:
            echo $colors['yellow'] . strtoupper($type) . $colors['reset'] . "\n";
            echo "  " . json_encode($data) . "\n";
    }
    
    echo str_repeat('-', 60) . "\n";
}