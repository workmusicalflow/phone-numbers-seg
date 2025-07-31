<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/bootstrap-doctrine.php';

use App\GraphQL\Resolvers\ContactResolver;
use App\Services\AuthService;
use App\GraphQL\Formatters\GraphQLFormatterService;
use Psr\Log\LoggerInterface;
use Psr\Log\AbstractLogger;
use DI\ContainerBuilder;

// Create a simple logger
class SimpleLogger extends AbstractLogger
{
    public function log($level, $message, array $context = []): void
    {
        echo "[$level] $message\n";
    }
}

$logger = new SimpleLogger();

// Get the container definitions
$definitions = require __DIR__ . '/../src/config/di.php';

// Build the container
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions($definitions);
$container = $containerBuilder->build();

// Get the necessary services from the container
$contactRepository = $container->get(App\Repositories\Interfaces\ContactRepositoryInterface::class);
$groupRepository = $container->get(App\Repositories\Interfaces\ContactGroupRepositoryInterface::class);
$membershipRepository = $container->get(App\Repositories\Interfaces\ContactGroupMembershipRepositoryInterface::class);
$authService = $container->get(App\Services\Interfaces\AuthServiceInterface::class);
$formatter = $container->get(App\GraphQL\Formatters\GraphQLFormatterInterface::class);

// Create the ContactResolver
$contactResolver = new ContactResolver(
    $contactRepository,
    $groupRepository,
    $membershipRepository,
    $authService,
    $formatter,
    $logger
);

// Test the resolver
try {
    echo "Testing ContactResolver...\n";

    // Set up a mock session to simulate an authenticated user
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'Admin';
    $_SESSION['is_admin'] = true;
    $_SESSION['auth_time'] = time();

    // Context is not used directly since AuthService will check $_SESSION
    $context = [];

    // Test resolveContacts
    $contacts = $contactResolver->resolveContacts([], $context);
    echo "resolveContacts: " . count($contacts) . " contacts found\n";

    // Test resolveContactsCount
    $count = $contactResolver->resolveContactsCount([], $context);
    echo "resolveContactsCount: " . $count . " contacts\n";

    // Test creating a contact
    echo "\nTesting contact creation...\n";
    $createArgs = [
        'name' => 'Test Contact ' . date('Y-m-d H:i:s'),
        'phoneNumber' => '+22507' . rand(10000000, 99999999),
        'email' => 'test' . rand(1000, 9999) . '@example.com',
        'notes' => 'Created by test script'
    ];

    $newContact = $contactResolver->mutateCreateContact($createArgs, $context);
    echo "Contact created with ID: " . $newContact['id'] . "\n";
    echo "Name: " . $newContact['name'] . "\n";
    echo "Phone: " . $newContact['phoneNumber'] . "\n";

    // Test updating the contact
    echo "\nTesting contact update...\n";
    $updateArgs = [
        'id' => $newContact['id'],
        'name' => $newContact['name'] . ' (Updated)',
        'notes' => 'Updated by test script'
    ];

    $updatedContact = $contactResolver->mutateUpdateContact($updateArgs, $context);
    echo "Contact updated. New name: " . $updatedContact['name'] . "\n";

    // Test deleting the contact
    echo "\nTesting contact deletion...\n";
    $deleteArgs = [
        'id' => $newContact['id']
    ];

    $deleteResult = $contactResolver->mutateDeleteContact($deleteArgs, $context);
    echo "Contact deletion result: " . ($deleteResult ? "Success" : "Failed") . "\n";

    echo "\nAll tests passed successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
