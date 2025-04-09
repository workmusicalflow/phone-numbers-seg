<?php

/**
 * Script pour créer des comptes utilisateurs (admin ou réguliers)
 * 
 * Usage: php create_user.php [--admin] [--username=USERNAME] [--email=EMAIL] [--password=PASSWORD] [--credit=CREDIT] [--limit=LIMIT]
 * 
 * Options:
 *   --admin       Crée un compte administrateur (défaut: false)
 *   --username    Nom d'utilisateur (requis)
 *   --email       Email de l'utilisateur (requis)
 *   --password    Mot de passe (défaut: généré aléatoirement)
 *   --credit      Crédit SMS initial (défaut: 10 pour users, 1000 pour admin)
 *   --limit       Limite de SMS (défaut: 10 pour users, null pour admin)
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

// Parser les arguments
$options = getopt('', ['admin', 'username:', 'email:', 'password:', 'credit:', 'limit:']);

$isAdmin = isset($options['admin']);
$username = $options['username'] ?? null;
$email = $options['email'] ?? null;
$password = $options['password'] ?? bin2hex(random_bytes(8));
$credit = isset($options['credit']) ? (int)$options['credit'] : ($isAdmin ? 1000 : 10);
$limit = isset($options['limit']) ? (int)$options['limit'] : ($isAdmin ? null : 10);

// Validation des paramètres
if (!$username) {
    die("Le nom d'utilisateur est requis. Utilisez --username=USERNAME\n");
}
if (!$email) {
    die("L'email est requis. Utilisez --email=EMAIL\n");
}

// Charger la configuration de la base de données
$config = require __DIR__ . '/../config/database.php';

// Forcer l'utilisation de SQLite
$config['driver'] = 'sqlite';
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

// Vérification si l'utilisateur existe déjà
try {
    $existingUser = $userRepository->findByUsername($username);
    if ($existingUser) {
        die("L'utilisateur '$username' existe déjà (ID: {$existingUser->getId()}).\n");
    }
} catch (Exception $e) {
    echo "Avertissement: " . $e->getMessage() . "\n";
}

// Création de l'utilisateur
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$user = new User(
    $username,
    $hashedPassword,
    null, // id
    $email,
    $credit,
    $limit,
    $isAdmin
);

try {
    $savedUser = $userRepository->save($user);
    echo "Utilisateur créé avec succès.\n";
    echo "ID: " . $savedUser->getId() . "\n";
    echo "Nom d'utilisateur: $username\n";
    echo "Email: $email\n";
    echo "Mot de passe: $password\n";
    echo "Crédit SMS: $credit\n";
    echo "Limite SMS: " . ($limit ?? 'Aucune') . "\n";
    echo "Type: " . ($isAdmin ? 'Administrateur' : 'Utilisateur standard') . "\n";
} catch (Exception $e) {
    die("Erreur lors de la création de l'utilisateur: " . $e->getMessage() . "\n");
}
