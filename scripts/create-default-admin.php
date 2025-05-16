<?php
/**
 * Script pour créer un utilisateur admin par défaut
 */

require_once __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;
use Doctrine\ORM\EntityManagerInterface;
use App\Entities\User;

echo "=== Création d'un utilisateur admin par défaut ===\n\n";

try {
    $containerBuilder = new ContainerBuilder();
    $containerBuilder->addDefinitions(__DIR__ . '/../src/config/di.php');
    $container = $containerBuilder->build();

    $entityManager = $container->get(EntityManagerInterface::class);
    
    // Vérifier s'il existe déjà un admin
    $existingAdmin = $entityManager->getRepository(User::class)
        ->findOneBy(['isAdmin' => true]);
    
    if ($existingAdmin) {
        echo "Un admin existe déjà : " . $existingAdmin->getEmail() . "\n";
        exit(0);
    }
    
    // Créer un nouvel admin
    $admin = new User();
    $admin->setUsername('admin');
    $admin->setEmail('admin@oracle.local');
    $admin->setPassword(password_hash('admin123', PASSWORD_DEFAULT));
    $admin->setIsAdmin(true);
    $admin->setCreatedAt(new \DateTime());
    $admin->setSmsCredit(1000);
    
    $entityManager->persist($admin);
    $entityManager->flush();
    
    echo "✅ Admin créé avec succès !\n";
    echo "Username : admin\n";
    echo "Email : admin@oracle.local\n";
    echo "Mot de passe : admin123\n";
    echo "\nIMPORTANT : Changez ce mot de passe dès la première connexion !\n";
    
} catch (\Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    echo "Trace : " . $e->getTraceAsString() . "\n";
    exit(1);
}