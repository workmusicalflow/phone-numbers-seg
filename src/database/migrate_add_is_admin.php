<?php

/**
 * Script pour exécuter les migrations qui ajoutent le champ is_admin à la table users
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

// Afficher le driver de base de données utilisé
$driver = $config['driver'];
echo "Utilisation de $driver comme base de données\n";

// Connexion à la base de données SQLite
try {
    $dbFile = $config['sqlite']['path'];

    if (!file_exists($dbFile)) {
        die("Le fichier de base de données SQLite n'existe pas: $dbFile\nExécutez d'abord 'composer db:init'\n");
    }

    $dsn = "sqlite:$dbFile";
    $db = new PDO($dsn);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connexion à la base de données SQLite réussie\n";

    // Exécuter la migration
    echo "Exécution de la migration pour ajouter le champ is_admin à la table users...\n";
    $migrationSql = file_get_contents(__DIR__ . '/migrations/add_is_admin_to_users_sqlite.sql');
    $db->exec($migrationSql);
    echo "Migration exécutée avec succès.\n";
} catch (PDOException $e) {
    die("Échec de la connexion à la base de données: " . $e->getMessage() . "\n");
}

// Mettre à jour l'administrateur existant
try {
    $stmt = $db->prepare('UPDATE users SET is_admin = 1 WHERE username = :username');
    $adminUsername = 'Admin';
    $stmt->bindParam(':username', $adminUsername, PDO::PARAM_STR);
    $stmt->execute();

    $rowCount = $stmt->rowCount();
    if ($rowCount > 0) {
        echo "L'administrateur a été mis à jour avec le flag is_admin = 1.\n";
    } else {
        echo "Aucun administrateur trouvé avec le nom d'utilisateur 'Admin'.\n";
    }
} catch (PDOException $e) {
    echo "Erreur lors de la mise à jour de l'administrateur: " . $e->getMessage() . "\n";
}

echo "Migration terminée.\n";
