<?php

/**
 * Script pour créer le compte administrateur par défaut
 * 
 * Ce script crée un compte administrateur avec les informations suivantes :
 * - Nom d'utilisateur : Admin
 * - Mot de passe : oraclesms2025-0
 * - Crédit SMS initial : 1000
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Repositories/UserRepository.php';

use App\Models\User;
use App\Repositories\UserRepository;

// Charger les variables d'environnement
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Charger la configuration de la base de données
$config = require __DIR__ . '/../config/database.php';

// Forcer l'utilisation de SQLite
$config['driver'] = 'sqlite';
$dbDriver = 'sqlite';
echo "Utilisation de SQLite comme base de données\n";

// Connexion à la base de données
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
} catch (PDOException $e) {
    die("Échec de la connexion à la base de données: " . $e->getMessage() . "\n");
}

// Création du repository
$userRepository = new UserRepository($db);

// Vérification si l'administrateur existe déjà
try {
    $existingAdmin = $userRepository->findByUsername('Admin');
    if ($existingAdmin) {
        echo "L'administrateur existe déjà (ID: {$existingAdmin->getId()}).\n";
        exit(0);
    }
} catch (Exception $e) {
    echo "Erreur lors de la vérification de l'existence de l'administrateur: " . $e->getMessage() . "\n";
    // Continuer car l'erreur peut être due à l'absence de la table users
}

// Création de l'administrateur
$adminUsername = 'Admin';
$adminPassword = 'oraclesms2025-0';
$hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);

$admin = new User(
    $adminUsername,
    $hashedPassword,
    null, // id
    null, // email
    1000, // crédit SMS initial pour l'admin
    null, // pas de limite
    true  // is_admin
);

try {
    $savedAdmin = $userRepository->save($admin);
    echo "Administrateur créé avec succès. ID: " . $savedAdmin->getId() . "\n";
    echo "Nom d'utilisateur: $adminUsername\n";
    echo "Mot de passe: $adminPassword\n";
} catch (Exception $e) {
    echo "Erreur lors de la création de l'administrateur: " . $e->getMessage() . "\n";
}
