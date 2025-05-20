<?php

declare(strict_types=1);

$entityManager = require __DIR__ . '/../src/bootstrap-doctrine-simple.php';

use App\Entities\User;

// Vérifier les utilisateurs admin dans la base
$users = $entityManager->getRepository(User::class)->findAll();

echo "=== Utilisateurs dans la base ===\n";
foreach ($users as $user) {
    echo "ID: " . $user->getId() . "\n";
    echo "Username: " . $user->getUsername() . "\n";
    echo "Email: " . $user->getEmail() . "\n";
    echo "Admin: " . ($user->isAdmin() ? 'Oui' : 'Non') . "\n";
    echo "Password Hash: " . $user->getPassword() . "\n";
    echo "---\n";
}

// Tester le login avec username = admin
$adminUser = $entityManager->getRepository(User::class)->findOneBy(['username' => 'admin']);

if ($adminUser) {
    echo "\nUtilisateur admin trouvé:\n";
    echo "Username: " . $adminUser->getUsername() . "\n";
    echo "Email: " . $adminUser->getEmail() . "\n";
    
    // Test de vérification du mot de passe
    $passwords = ['admin123', 'admin', 'password123'];
    
    foreach ($passwords as $password) {
        $isValid = password_verify($password, $adminUser->getPassword());
        echo "Mot de passe '$password': " . ($isValid ? 'VALIDE' : 'INVALIDE') . "\n";
    }
} else {
    echo "\nAucun utilisateur avec username = 'admin' trouvé\n";
}