<?php

/**
 * Test simple du format des paramètres WhatsApp Bulk
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Charger le container DI et l'EntityManager via bootstrap
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

// Charger bootstrap pour avoir le container configuré
$bootstrapPath = __DIR__ . '/../public/graphql.php';
$savedRequestMethod = $_SERVER['REQUEST_METHOD'] ?? null;
$_SERVER['REQUEST_METHOD'] = 'GET'; // Pour éviter les erreurs de méthode
ob_start();
require $bootstrapPath;
ob_end_clean();
$_SERVER['REQUEST_METHOD'] = $savedRequestMethod;

echo "=== Test Format Paramètres WhatsApp Bulk ===\n\n";

try {
    // Récupérer le resolver
    $resolver = $container->get(\App\GraphQL\Resolvers\WhatsAppBulkResolver::class);
    
    // Récupérer l'utilisateur admin
    $userRepository = $entityManager->getRepository(\App\Entities\User::class);
    $adminUser = $userRepository->findOneBy(['username' => 'admin']);
    
    if (!$adminUser) {
        throw new Exception("Utilisateur admin non trouvé");
    }
    
    echo "✅ Utilisateur admin trouvé\n";
    
    // Paramètres de test au format components
    $parameters = [
        'components' => [
            [
                'type' => 'header',
                'parameters' => [
                    [
                        'type' => 'image',
                        'image' => [
                            'link' => 'https://example.com/test-image.jpg'
                        ]
                    ]
                ]
            ]
        ]
    ];
    
    echo "\n📋 Paramètres envoyés:\n";
    echo json_encode($parameters, JSON_PRETTY_PRINT) . "\n";
    
    // Tester la préparation avec un template existant
    try {
        $result = $resolver->prepareWhatsAppBulkSend(
            $adminUser,
            [],  // contactIds
            [],  // groupIds  
            [],  // segmentIds
            ['+22507000001'], // phoneNumbers directs
            '1', // templateId (à ajuster selon votre DB)
            $parameters,
            5    // priority
        );
        
        echo "\n✅ Résultat de la préparation:\n";
        echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
        
    } catch (\Exception $e) {
        echo "\n❌ Erreur lors de la préparation: " . $e->getMessage() . "\n";
        
        // Si c'est une erreur de template, lister les templates disponibles
        if (strpos($e->getMessage(), 'Template') !== false) {
            echo "\n📋 Templates disponibles:\n";
            $templates = $entityManager->getRepository(\App\Entities\WhatsApp\WhatsAppTemplate::class)->findAll();
            foreach ($templates as $template) {
                echo "   - ID: " . $template->getId() . ", Name: " . $template->getName() . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "\n❌ Erreur: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}