<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap DI container
$builder = new \DI\ContainerBuilder();
$builder->addDefinitions(require __DIR__ . '/../src/config/di.php');
$container = $builder->build();

// Get auth service
$authService = $container->get(\App\Services\Interfaces\AuthServiceInterface::class);

echo "=== Test AuthService ===\n\n";

// Test authentication directement
try {
    echo "Test login: admin@example.com / admin123\n";
    $user = $authService->authenticate('admin@example.com', 'admin123');
    
    if ($user) {
        echo "Login réussi!\n";
        echo "ID: " . $user->getId() . "\n";
        echo "Email: " . $user->getEmail() . "\n";
        echo "Admin: " . ($user->isAdmin() ? 'Oui' : 'Non') . "\n";
        
        // Créer une session
        session_start();
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['is_authenticated'] = true;
        echo "Session créée avec ID: " . session_id() . "\n";
    } else {
        echo "Login échoué\n";
    }
} catch (\Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}