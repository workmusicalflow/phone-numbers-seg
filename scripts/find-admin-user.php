<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entities\User;

try {
    // Utiliser l'entity manager depuis le bootstrap
    $em = require __DIR__ . '/../src/bootstrap-doctrine.php';
    
    // Trouver tous les utilisateurs admin
    $adminUsers = $em->getRepository(User::class)->findBy(['isAdmin' => true]);
    
    echo "Utilisateurs admin trouvés:\n";
    foreach ($adminUsers as $user) {
        echo sprintf(
            "- ID: %d, Email: %s, Username: %s\n",
            $user->getId(),
            $user->getEmail(),
            $user->getUsername()
        );
    }
    
    // Trouver tous les utilisateurs (limite à 5)
    echo "\nTous les utilisateurs (max 5):\n";
    $allUsers = $em->getRepository(User::class)->findBy([], [], 5);
    
    foreach ($allUsers as $user) {
        echo sprintf(
            "- ID: %d, Email: %s, Username: %s\n",
            $user->getId(),
            $user->getEmail(),
            $user->getUsername()
        );
    }
    
} catch (\Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}