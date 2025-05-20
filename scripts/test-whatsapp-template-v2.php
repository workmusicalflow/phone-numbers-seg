<?php

use App\GraphQL\Context\GraphQLContext;
use App\GraphQL\Resolvers\WhatsApp\WhatsAppResolver;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\WhatsApp\WhatsAppMessageHistoryRepositoryInterface;
use App\Services\Interfaces\AuthServiceInterface;
use App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface;
use Psr\Log\LoggerInterface;

// Require the bootstrap file that includes DI container
require_once __DIR__ . '/../vendor/autoload.php';

// Create a PHP-DI container with our config
$builder = new \DI\ContainerBuilder();
$builder->addDefinitions(__DIR__ . '/../src/config/di.php');
$container = $builder->build();

// Get necessary services
$entityManager = $container->get(\Doctrine\ORM\EntityManagerInterface::class);
$userRepository = $container->get(UserRepositoryInterface::class);
$whatsAppService = $container->get(WhatsAppServiceInterface::class);
$messageRepository = $container->get(WhatsAppMessageHistoryRepositoryInterface::class);
$logger = $container->get(LoggerInterface::class);
$authService = $container->get(AuthServiceInterface::class);

// Create WhatsAppResolver manually
$whatsappResolver = new WhatsAppResolver($whatsAppService, $messageRepository, $logger);

// Get an admin user for testing
$user = $userRepository->findOneBy(['username' => 'admin']);

if (!$user) {
    die("Admin user not found. Please create one first.");
}

// Create a GraphQL context with the auth service
$context = new GraphQLContext($authService);

// Manually set the current user (using reflection since there's no public setter)
$reflection = new ReflectionClass(GraphQLContext::class);
$property = $reflection->getProperty('currentUser');
$property->setAccessible(true);
$property->setValue($context, $user);

// Test input data
$input = [
    'recipientPhoneNumber' => '2250712345678', // Replace with a valid number
    'templateName' => 'hello_world',
    'templateLanguage' => 'fr',
    'bodyVariables' => ['Test User'],
    'headerMediaUrl' => null
];

try {
    echo "Testing sendWhatsAppTemplateV2 mutation...\n";
    $result = $whatsappResolver->sendWhatsAppTemplateV2($input, $context);
    
    echo "Result:\n";
    print_r($result);
    
    echo "\nMutation executed successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}