<?php

declare(strict_types=1);

$entityManager = require __DIR__ . '/../src/bootstrap-doctrine-simple.php';

use App\Entities\User;

// Vérifier les utilisateurs dans la base
$users = $entityManager->getRepository(User::class)->findAll();

echo "=== Utilisateurs dans la base ===\n";
foreach ($users as $user) {
    echo "Email: " . $user->getEmail() . "\n";
    echo "Admin: " . ($user->isAdmin() ? 'Oui' : 'Non') . "\n";
    echo "Password Hash: " . $user->getPassword() . "\n";
    echo "---\n";
}

// Tester le login
$adminUser = $entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);

if ($adminUser) {
    echo "\nTest de vérification du mot de passe:\n";
    
    // Test avec différents mots de passe
    $passwords = ['admin123', 'password123', 'admin'];
    
    foreach ($passwords as $password) {
        $isValid = password_verify($password, $adminUser->getPassword());
        echo "Mot de passe '$password': " . ($isValid ? 'VALIDE' : 'INVALIDE') . "\n";
    }
}