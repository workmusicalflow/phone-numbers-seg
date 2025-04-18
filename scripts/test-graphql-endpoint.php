<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables from .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..'); // Specify the directory containing .env
$dotenv->load();

// Start the session before any output or session access
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to make a GraphQL request
function makeGraphQLRequest($query, $variables = [])
{
    $url = 'http://localhost:8000/graphql.php'; // Adjust the URL as needed

    $data = [
        'query' => $query,
        'variables' => $variables
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($data)
        ]
    ];

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    if ($result === FALSE) {
        return ['error' => 'Failed to make request'];
    }

    return json_decode($result, true);
}

// Test the login mutation
echo "Testing login mutation...\n";
$loginQuery = <<<'GRAPHQL'
mutation Login($username: String!, $password: String!) {
  login(username: $username, password: $password)
}
GRAPHQL;

$loginVariables = [
    'username' => 'admin',
    'password' => 'password'
];

$loginResult = makeGraphQLRequest($loginQuery, $loginVariables);
echo "Login result: " . json_encode($loginResult, JSON_PRETTY_PRINT) . "\n\n";

// Test the me query
echo "Testing me query...\n";
$meQuery = <<<'GRAPHQL'
query {
  me {
    id
    username
    email
    smsCredit
    isAdmin
    createdAt
  }
}
GRAPHQL;

$meResult = makeGraphQLRequest($meQuery);
echo "Me query result: " . json_encode($meResult, JSON_PRETTY_PRINT) . "\n\n";

// Test a more complex query that might involve the GraphQLFormatterService
echo "Testing a more complex query...\n";
$complexQuery = <<<'GRAPHQL'
query {
  smsHistory(limit: 5) {
    id
    phoneNumber
    message
    status
    createdAt
    segment {
      id
      name
      description
    }
  }
}
GRAPHQL;

$complexResult = makeGraphQLRequest($complexQuery);
echo "Complex query result: " . json_encode($complexResult, JSON_PRETTY_PRINT) . "\n\n";

// Try to directly instantiate the GraphQLFormatterService
echo "Trying to directly instantiate the GraphQLFormatterService...\n";
try {
    // Create DI container
    $container = new \App\GraphQL\DIContainer();

    // Get the required dependencies
    $customSegmentRepository = $container->get(\App\Repositories\Interfaces\CustomSegmentRepositoryInterface::class);
    $logger = $container->get(\Psr\Log\LoggerInterface::class);
    $senderNameService = $container->get(\App\Services\SenderNameService::class);
    $orangeAPIConfigService = $container->get(\App\Services\OrangeAPIConfigService::class);

    // Create the formatter
    $formatter = new \App\GraphQL\Formatters\GraphQLFormatterService(
        $customSegmentRepository,
        $logger,
        $senderNameService,
        $orangeAPIConfigService
    );

    echo "Successfully created GraphQLFormatterService directly.\n";

    // Try to use the formatter
    $user = $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class)->findById(1);
    if ($user) {
        $formattedUser = $formatter->formatUser($user);
        echo "Formatted user: " . json_encode($formattedUser, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "User not found.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

// Try to debug the GraphQL endpoint by simulating what happens in graphql.php
echo "\nSimulating graphql.php execution...\n";
try {
    // Create DI container
    $container = new \App\GraphQL\DIContainer();

    // Get the AuthResolver from the container
    $authResolver = $container->get(\App\GraphQL\Resolvers\AuthResolver::class);
    echo "AuthResolver retrieved successfully.\n";

    // Get the SMSResolver from the container
    $smsResolver = $container->get(\App\GraphQL\Resolvers\SMSResolver::class);
    echo "SMSResolver retrieved successfully.\n";

    // Get the UserResolver from the container
    $userResolver = $container->get(\App\GraphQL\Resolvers\UserResolver::class);
    echo "UserResolver retrieved successfully.\n";

    // Get the ContactResolver from the container
    $contactResolver = $container->get(\App\GraphQL\Resolvers\ContactResolver::class);
    echo "ContactResolver retrieved successfully.\n";

    // Get the ContactGroupResolver from the container
    $contactGroupResolver = $container->get(\App\GraphQL\Resolvers\ContactGroupResolver::class);
    echo "ContactGroupResolver retrieved successfully.\n";

    echo "All resolvers retrieved successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
