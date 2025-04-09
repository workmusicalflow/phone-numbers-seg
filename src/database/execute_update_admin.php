<?php

/**
 * Script pour exécuter la mise à jour du flag is_admin pour l'utilisateur Admin
 */

require_once __DIR__ . '/../../vendor/autoload.php';

// Charger les variables d'environnement en premier
$dotenvPath = __DIR__ . '/../../';
if (file_exists($dotenvPath . '.env')) {
    $dotenv = \Dotenv\Dotenv::createImmutable($dotenvPath);
    $dotenv->load();
}

// Charger la configuration de la base de données après avoir chargé les variables d'environnement
require_once __DIR__ . '/../config/database.php';
$config = require __DIR__ . '/../config/database.php';

// Connexion à la base de données SQLite
try {
    $sqliteConfig = $config['sqlite'];
    $dbFile = $sqliteConfig['path'];

    if (!file_exists($dbFile)) {
        die("Le fichier de base de données SQLite n'existe pas: $dbFile\nExécutez d'abord 'composer db:init'\n");
    }

    $dsn = "sqlite:$dbFile";
    $db = new PDO($dsn);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connexion à la base de données SQLite réussie\n";

    // Exécuter la requête SQL pour mettre à jour le flag is_admin
    echo "Exécution de la mise à jour du flag is_admin pour l'utilisateur Admin...\n";
    $sql = file_get_contents(__DIR__ . '/update_admin_flag.sql');
    $result = $db->exec($sql);

    if ($result === false) {
        echo "Erreur lors de l'exécution de la requête SQL.\n";
    } else if ($result === 0) {
        echo "Aucune ligne n'a été mise à jour. L'utilisateur Admin n'existe peut-être pas.\n";
    } else {
        echo "Mise à jour réussie. $result ligne(s) mise(s) à jour.\n";
    }
} catch (PDOException $e) {
    die("Échec de la connexion à la base de données: " . $e->getMessage() . "\n");
}

echo "Opération terminée.\n";
