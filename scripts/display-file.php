<?php
$file_path = __DIR__ . '/../docs/Meta-API-Cloud-wha-business/mes-info-API-cloud-Meta.md';
echo "Contenu brut du fichier ligne par ligne:\n\n";
$lines = file($file_path);
foreach ($lines as $line_num => $line) {
    echo "Ligne " . ($line_num + 1) . ": " . bin2hex($line) . "\n";
    echo "Ligne " . ($line_num + 1) . " (texte): " . htmlspecialchars($line) . "\n\n";
}
?>