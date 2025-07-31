<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entities\User;

try {
    // Utiliser l'entity manager depuis le bootstrap
    $em = require __DIR__ . '/../src/bootstrap-doctrine.php';
    
    // Trouver l'utilisateur admin
    $adminUser = $em->getRepository(User::class)->findOneBy(['username' => 'admin']);
    
    if (!$adminUser) {
        echo "Utilisateur admin non trouvé!\n";
    } else {
        echo "Utilisateur admin trouvé:\n";
        echo "- ID: " . $adminUser->getId() . "\n";
        echo "- Username: " . $adminUser->getUsername() . "\n";
        echo "- Email: " . $adminUser->getEmail() . "\n";
        
        // Vérifier le mot de passe
        $passwords = ['changeme', 'admin', 'password', '123456'];
        echo "\nTest des mots de passe courants:\n";
        foreach ($passwords as $password) {
            if (password_verify($password, $adminUser->getPassword())) {
                echo "✓ Mot de passe valide: $password\n";
            } else {
                echo "✗ Mot de passe invalide: $password\n";
            }
        }
    }
    
} catch (\Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}