<?php

/**
 * Script de migration pour créer la table admin_action_logs
 * 
 * Ce script exécute le fichier SQL de migration pour créer la table admin_action_logs
 * qui stockera les journaux des actions effectuées par les administrateurs.
 */

// Charger la configuration de la base de données
$dbConfig = require __DIR__ . '/../config/database.php';

// Déterminer le driver de base de données à utiliser
$driver = $dbConfig['driver'] ?? 'sqlite';

echo "Utilisation de $driver comme base de données\n";

try {
    // Créer une connexion PDO
    if ($driver === 'mysql') {
        $dsn = "mysql:host={$dbConfig['mysql']['host']};port={$dbConfig['mysql']['port']};dbname={$dbConfig['mysql']['database']}";
        $pdo = new PDO($dsn, $dbConfig['mysql']['username'], $dbConfig['mysql']['password']);
    } else {
        // Par défaut, utiliser SQLite
        $dsn = "sqlite:{$dbConfig['sqlite']['path']}";
        $pdo = new PDO($dsn);
    }

    // Configurer PDO pour lancer des exceptions en cas d'erreur
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Lire le fichier SQL de migration
    if ($driver === 'sqlite') {
        $sqlFile = __DIR__ . '/migrations/create_admin_action_logs_table_sqlite.sql';
        if (!file_exists($sqlFile)) {
            $sqlFile = __DIR__ . '/create_admin_action_logs_table_sqlite.sql';
        }
    } else {
        $sqlFile = __DIR__ . '/migrations/create_admin_action_logs_table.sql';
        if (!file_exists($sqlFile)) {
            $sqlFile = __DIR__ . '/create_admin_action_logs_table.sql';
        }
    }

    if (!file_exists($sqlFile)) {
        throw new Exception("Fichier SQL de migration introuvable: $sqlFile");
    }

    $sql = file_get_contents($sqlFile);

    // Exécuter les requêtes SQL
    $pdo->exec($sql);

    echo "Migration réussie: Table admin_action_logs créée\n";
} catch (PDOException $e) {
    echo "Échec de la connexion à la base de données: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
