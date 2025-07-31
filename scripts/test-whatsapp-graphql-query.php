<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\Interfaces\AuthServiceInterface;
use App\GraphQL\Context\GraphQLContext;
use App\GraphQL\Resolvers\WhatsApp\WhatsAppResolver;

try {
    // Charger le conteneur DI
    $container = require __DIR__ . '/../src/config/di.php';
    
    // Créer un contexte GraphQL avec l'utilisateur admin
    $authService = $container->get(AuthServiceInterface::class);
    
    // Simuler une session avec l'utilisateur admin
    $_SESSION['user_id'] = 1; // ID de l'utilisateur admin
    
    // Créer le contexte
    $contextFactory = $container->get(\App\GraphQL\Context\GraphQLContextFactory::class);
    $httpRequest = new \Symfony\Component\HttpFoundation\Request();
    $context = $contextFactory->create($httpRequest, []);
    
    echo "Contexte créé. Utilisateur actuel: ";
    $currentUser = $context->getCurrentUser();
    if ($currentUser) {
        echo "ID=" . $currentUser->getId() . ", Email=" . $currentUser->getEmail() . "\n";
    } else {
        echo "Aucun utilisateur\n";
    }
    
    // Créer le resolver et tester la requête
    $resolver = $container->get(WhatsAppResolver::class);
    
    echo "\nTest de la requête getWhatsAppMessages...\n";
    $result = $resolver->getWhatsAppMessages(
        limit: 20,
        offset: 0,
        phoneNumber: null,
        status: null,
        type: null,
        direction: null,
        context: $context
    );
    
    echo "Résultat:\n";
    echo "- Total messages: " . $result['totalCount'] . "\n";
    echo "- Nombre de messages récupérés: " . count($result['messages']) . "\n";
    echo "- Has more: " . ($result['hasMore'] ? 'Oui' : 'Non') . "\n";
    
    if (!empty($result['messages'])) {
        echo "\nPremiers messages:\n";
        foreach (array_slice($result['messages'], 0, 3) as $message) {
            echo sprintf(
                "  - ID: %d, Numéro: %s, Direction: %s, Type: %s\n",
                $message->getId(),
                $message->getPhoneNumber(),
                $message->getDirection(),
                $message->getType()
            );
        }
    } else {
        echo "\nAucun message trouvé.\n";
    }
    
} catch (\Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}