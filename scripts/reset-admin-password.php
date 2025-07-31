<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/bootstrap-doctrine.php';

try {
    $entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';
    
    // Nouveau mot de passe
    $newPassword = 'admin123';
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
    
    // Mettre à jour le mot de passe admin
    $sql = "UPDATE users SET password = :password WHERE username = 'admin'";
    $stmt = $entityManager->getConnection()->prepare($sql);
    $stmt->bindValue('password', $hashedPassword);
    $stmt->executeStatement();
    
    echo "Mot de passe admin mis à jour avec succès!\n";
    echo "Nouveau mot de passe : $newPassword\n";
    echo "Hash : $hashedPassword\n";
    
} catch (\Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
    exit(1);
}