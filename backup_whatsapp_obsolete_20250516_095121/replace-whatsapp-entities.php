<?php

/**
 * Script pour remplacer les anciennes entités WhatsApp par les nouvelles
 * qui correspondent exactement aux schémas de tables existants
 */

echo "=== Remplacement des entités WhatsApp ===\n\n";

// Les fichiers à remplacer
$replacements = [
    'WhatsAppMessageHistory.php' => 'WhatsAppMessageHistoryFixed.php',
    'WhatsAppQueue.php' => 'WhatsAppQueueExisting.php',
    'WhatsAppTemplate.php' => 'WhatsAppTemplateFixed.php',
];

$basePath = __DIR__ . '/../src/Entities/WhatsApp/';

// 1. Sauvegarder les anciennes entités
echo "1. Sauvegarde des anciennes entités...\n";
$backupPath = $basePath . 'backup/';
if (!is_dir($backupPath)) {
    mkdir($backupPath, 0755, true);
}

foreach ($replacements as $old => $new) {
    $oldFile = $basePath . $old;
    $backupFile = $backupPath . $old . '.backup';
    
    if (file_exists($oldFile)) {
        copy($oldFile, $backupFile);
        echo "   - Sauvegardé: $old\n";
    }
}

// 2. Remplacer les entités
echo "\n2. Remplacement des entités...\n";
foreach ($replacements as $old => $new) {
    $oldFile = $basePath . $old;
    $newFile = $basePath . $new;
    
    if (file_exists($newFile)) {
        // Lire le contenu de la nouvelle entité
        $content = file_get_contents($newFile);
        
        // Remplacer le nom de classe dans le contenu
        $oldClassName = pathinfo($old, PATHINFO_FILENAME);
        $newClassName = pathinfo($new, PATHINFO_FILENAME);
        $content = str_replace($newClassName, $oldClassName, $content);
        
        // Écrire dans l'ancien fichier
        file_put_contents($oldFile, $content);
        echo "   - Remplacé: $old\n";
    }
}

// 3. Supprimer les fichiers temporaires
echo "\n3. Nettoyage...\n";
foreach ($replacements as $old => $new) {
    $newFile = $basePath . $new;
    if (file_exists($newFile)) {
        unlink($newFile);
        echo "   - Supprimé: $new\n";
    }
}

echo "\n=== Remplacement terminé ===\n";
echo "\nLes anciennes entités ont été sauvegardées dans: $backupPath\n";
echo "Pour restaurer, copiez les fichiers .backup en enlevant l'extension .backup\n";