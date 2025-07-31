<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/bootstrap-doctrine.php';

use App\Repositories\Interfaces\UserRepositoryInterface;
use DI\Container;
use DI\ContainerBuilder;

// Créer le conteneur DI
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../src/config/di.php');
$container = $containerBuilder->build();

// Récupérer le repository utilisateur
$userRepository = $container->get(UserRepositoryInterface::class);

// Trouver l'utilisateur admin
$user = $userRepository->findByUsername('admin');

if (!$user) {
    echo "Utilisateur admin non trouvé.\n";
    exit(1);
}

// Générer un nouveau mot de passe haché
$newPassword = 'admin123';
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

// Mettre à jour le mot de passe
$user->setPassword($hashedPassword);

// Sauvegarder les modifications
$userRepository->save($user);

echo "Mot de passe de l'utilisateur admin mis à jour avec succès.\n";
echo "Nouveau mot de passe: $newPassword\n";