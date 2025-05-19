<?php

declare(strict_types=1);

// Test de l'upload WhatsApp

// Simuler une session authentifiée avant tout output
session_start();
$_SESSION['user_id'] = 1; // Admin user

require_once __DIR__ . '/../vendor/autoload.php';

echo "Test de l'upload WhatsApp\n";
echo "========================\n\n";

// Créer un fichier test
$testImagePath = sys_get_temp_dir() . '/test_whatsapp_image.jpg';
file_put_contents($testImagePath, base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAYEBQYFBAYGBQYHBwYIChAKCgkJChQODwwQFxQYGBcUFhYaHSUfGhsjHBYWICwgIyYnKSopGR8tMC0oMCUoKSj/2wBDAQcHBwoIChMKChMoGhYaKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCj/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAr/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdABmX/9k='));

// Créer le conteneur DI
$container = new \App\GraphQL\DIContainer();

try {
    
    // Obtenir le service WhatsApp
    $whatsappService = $container->get(\App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface::class);
    
    // Obtenir le repository d'utilisateurs
    $userRepository = $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class);
    
    // Récupérer l'utilisateur admin (ID 1)
    $user = $userRepository->find(1);
    
    if (!$user) {
        echo "Utilisateur admin non trouvé.\n";
        exit(1);
    }
    
    echo "Upload du fichier test...\n";
    $mediaId = $whatsappService->uploadMedia($user, $testImagePath, 'image/jpeg');
    
    echo "Succès! Media ID: " . $mediaId . "\n";
    
} catch (\Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} finally {
    // Nettoyer
    if (file_exists($testImagePath)) {
        unlink($testImagePath);
    }
}