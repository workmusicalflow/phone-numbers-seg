<?php
/**
 * Script de sauvegarde automatique de la base de données
 */

$databasePath = dirname(__DIR__) . '/var/database.sqlite';
$backupDir = dirname(__DIR__) . '/var/backups';

// Créer le répertoire de sauvegarde s'il n'existe pas
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Vérifier que la base de données existe
if (!file_exists($databasePath)) {
    echo "❌ Base de données introuvable : $databasePath\n";
    exit(1);
}

// Générer un nom de fichier unique avec timestamp
$timestamp = date('Y-m-d_His');
$backupPath = $backupDir . '/database_' . $timestamp . '.sqlite';

echo "=== Sauvegarde de la base de données ===\n";
echo "Source : $databasePath\n";
echo "Destination : $backupPath\n";

// Effectuer la copie
if (copy($databasePath, $backupPath)) {
    echo "✅ Sauvegarde créée avec succès !\n";
    
    // Vérifier la taille
    $originalSize = filesize($databasePath);
    $backupSize = filesize($backupPath);
    
    echo "Taille originale : " . formatBytes($originalSize) . "\n";
    echo "Taille sauvegarde : " . formatBytes($backupSize) . "\n";
    
    // Nettoyer les anciennes sauvegardes (garder seulement les 10 dernières)
    $backups = glob($backupDir . '/database_*.sqlite');
    if (count($backups) > 10) {
        arsort($backups);
        $toDelete = array_slice($backups, 10);
        foreach ($toDelete as $oldBackup) {
            unlink($oldBackup);
            echo "Suppression ancienne sauvegarde : " . basename($oldBackup) . "\n";
        }
    }
    
    // Créer un lien symbolique vers la dernière sauvegarde
    $latestLink = $backupDir . '/latest.sqlite';
    if (file_exists($latestLink)) {
        unlink($latestLink);
    }
    symlink($backupPath, $latestLink);
    echo "✅ Lien 'latest.sqlite' mis à jour\n";
    
} else {
    echo "❌ Erreur lors de la sauvegarde !\n";
    exit(1);
}

function formatBytes($size) {
    $units = array('B', 'KB', 'MB', 'GB');
    $i = 0;
    while ($size >= 1024 && $i < count($units) - 1) {
        $size /= 1024;
        $i++;
    }
    return round($size, 2) . ' ' . $units[$i];
}

echo "\nPour restaurer cette sauvegarde :\n";
echo "cp $backupPath $databasePath\n";