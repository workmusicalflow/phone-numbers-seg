<?php

// Activer l'affichage des erreurs PHP pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Activer la journalisation des erreurs
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php-error.log');

// Créer le répertoire de logs s'il n'existe pas
if (!is_dir(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0777, true);
}

// Fonction pour capturer les erreurs et les renvoyer en JSON
function handleFatalErrors()
{
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        header('Content-Type: application/json');
        echo json_encode([
            'errors' => [
                [
                    'message' => 'Fatal error: ' . $error['message'],
                    'location' => $error['file'] . ':' . $error['line']
                ]
            ]
        ]);
    }
}
register_shutdown_function('handleFatalErrors');

// Gestionnaire d'exceptions personnalisé
set_exception_handler(function ($e) {
    header('Content-Type: application/json');
    echo json_encode([
        'errors' => [
            [
                'message' => $e->getMessage(),
                'location' => $e->getFile() . ':' . $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]
        ]
    ]);
    exit;
});

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables from .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..'); // Specify the directory containing .env
$dotenv->load();

// Start the session before any output or session access
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Server\StandardServer;
use GraphQL\Executor\Executor;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Utils\BuildSchema;
use App\GraphQL\Resolvers\UserResolver;
use App\GraphQL\Resolvers\ContactResolver;
use App\GraphQL\Resolvers\SMSResolver;
use App\GraphQL\Resolvers\AuthResolver;
use Psr\Log\LoggerInterface; // For logging

// Enable CORS for GraphQL endpoint
// header('Access-Control-Allow-Origin: *'); // Incorrect with credentials
header('Access-Control-Allow-Origin: http://localhost:5173'); // Correct origin
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    header('Allow: POST, OPTIONS');
    header('Content-Type: application/json');
    echo json_encode(['errors' => [['message' => 'Method not allowed. Use POST for queries and mutations.']]]);
    exit;
}

// Get the request content
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (empty($input)) {
    header('Content-Type: application/json');
    echo json_encode([
        'errors' => [
            [
                'message' => 'No query provided or invalid JSON',
                'received' => substr($rawInput, 0, 100) . (strlen($rawInput) > 100 ? '...' : '')
            ]
        ]
    ]);
    exit;
}

try {
    // Create DI container
    $container = new \App\GraphQL\DIContainer();

    // Get Logger from container (moved before other operations that might fail)
    $logger = $container->get(LoggerInterface::class);

    // Get repositories from container
    $smsHistoryRepository = $container->get(\App\Repositories\SMSHistoryRepository::class);
    $userRepository = $container->get(\App\Repositories\UserRepository::class);
    $customSegmentRepository = $container->get(\App\Repositories\CustomSegmentRepository::class);
    $smsService = $container->get(\App\Services\SMSService::class);

    // Load schema from file
    $schemaString = file_get_contents(__DIR__ . '/../src/GraphQL/schema.graphql');
    $schema = BuildSchema::build($schemaString);

    // Log request received after logger is confirmed available
    $logger->info('GraphQL endpoint request received.');

    // Get Resolver instances from container
    $userResolver = $container->get(UserResolver::class);
    $contactResolver = $container->get(ContactResolver::class);
    $smsResolver = $container->get(SMSResolver::class);
    $authResolver = $container->get(AuthResolver::class);
    $logger->info('Resolver instances obtained from DI container.');

    // --- Field Resolver Mapping ---
    // This function maps top-level Query/Mutation fields to our resolver methods.
    // For nested fields, it falls back to the default resolver.
    $fieldResolver = function ($source, $args, $context, ResolveInfo $info) use (
        $userResolver,
        $contactResolver,
        $smsResolver,
        $authResolver,
        $logger,
        $container
    ) {
        $fieldName = $info->fieldName;
        $parentTypeName = $info->parentType->name;

        $logger->debug("Attempting to resolve field: {$parentTypeName}.{$fieldName}");

        // Handle top-level Query fields
        if ($parentTypeName === 'Query') {
            $logger->debug("Handling Query field: {$fieldName}");
            switch ($fieldName) {
                case 'users':
                    return $userResolver->resolveUsers();
                case 'user':
                    return $userResolver->resolveUser($args);
                case 'userByUsername':
                    return $userResolver->resolveUserByUsername($args);
                case 'me':
                    return $userResolver->resolveMe(); // Use the dedicated 'me' resolver
                case 'verifyToken':
                    return $authResolver->resolveVerifyToken($args, $context); // Placeholder
                case 'contacts':
                    return $contactResolver->resolveContacts($args, $context);
                case 'contact':
                    return $contactResolver->resolveContact($args, $context);
                case 'searchContacts':
                    return $contactResolver->resolveSearchContacts($args, $context);
                case 'smsHistory':
                    return $smsResolver->resolveSmsHistory($args, $context);
                case 'smsHistoryCount':
                    return $smsResolver->resolveSmsHistoryCount($args, $context);
                case 'segmentsForSMS':
                    return $smsResolver->resolveSegmentsForSMS($args, $context);
                case 'test':
                    return "GraphQL is working via FieldResolver!";
                case 'hello':
                    return "Hello, world via FieldResolver!";
                case 'dashboardStats':
                    return [ // Keep simple logic here for now
                        'usersCount' => $container->get(\App\Repositories\UserRepository::class)->count(),
                        'totalSmsCredits' => 0, // Implement logic
                        'lastUpdated' => date('Y-m-d H:i:s')
                    ];
                    // No default case needed, fall through to default resolver
            }
        }
        // Handle top-level Mutation fields
        elseif ($parentTypeName === 'Mutation') {
            $logger->debug("Handling Mutation field: {$fieldName}");
            switch ($fieldName) {
                case 'createUser':
                    return $userResolver->mutateCreateUser($args);
                case 'updateUser':
                    return $userResolver->mutateUpdateUser($args);
                case 'changePassword':
                    return $userResolver->mutateChangePassword($args);
                case 'addCredits':
                    return $userResolver->mutateAddCredits($args);
                case 'deleteUser':
                    return $userResolver->mutateDeleteUser($args);
                case 'login':
                    return $authResolver->mutateLogin($args, $context);
                case 'refreshToken':
                    return $authResolver->mutateRefreshToken($args, $context); // Placeholder
                case 'logout':
                    return $authResolver->mutateLogout($args, $context);
                case 'requestPasswordReset':
                    return $authResolver->mutateRequestPasswordReset($args, $context); // Placeholder
                case 'resetPassword':
                    return $authResolver->mutateResetPassword($args, $context); // Placeholder
                case 'createContact':
                    return $contactResolver->mutateCreateContact($args, $context);
                case 'updateContact':
                    return $contactResolver->mutateUpdateContact($args, $context);
                case 'deleteContact':
                    return $contactResolver->mutateDeleteContact($args, $context);
                case 'sendSms':
                    return $smsResolver->mutateSendSms($args, $context);
                case 'sendBulkSms':
                    return $smsResolver->mutateSendBulkSms($args, $context);
                case 'sendSmsToSegment':
                    return $smsResolver->mutateSendSmsToSegment($args, $context);
                case 'sendSmsToAllContacts': // Add mapping for new mutation
                    return $smsResolver->mutateSendSmsToAllContacts($args, $context);
                case 'retrySms':
                    return $smsResolver->mutateRetrySms($args, $context);
                    // No default case needed, fall through to default resolver
            }
        }

        // If not a top-level Query or Mutation field handled above,
        // use the default field resolver (handles properties/getters on objects).
        $logger->debug("Falling back to default resolver for {$parentTypeName}.{$fieldName}");
        return Executor::defaultFieldResolver($source, $args, $context, $info);
    };
    // --- End Field Resolver Mapping ---


    // Execute the query using the custom field resolver
    $result = GraphQL::executeQuery(
        $schema,
        $input['query'] ?? '',
        null, // rootValue (logic is now entirely in fieldResolver)
        null, // context
        $input['variables'] ?? [],
        null, // operationName
        $fieldResolver // Use the custom field resolver
    );
    $logger->info('GraphQL query executed.');


    // Return the result
    header('Content-Type: application/json');
    $output = $result->toArray();
    echo json_encode($output);
    $logger->info('GraphQL response sent.');
} catch (Exception $e) {
    // Handle exceptions
    $logger->critical('GraphQL execution error: ' . $e->getMessage(), [
        'exception' => $e,
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString() // Be careful logging full trace in production
    ]);
    header('Content-Type: application/json');
    // Format error for GraphQL response
    echo json_encode([
        'errors' => [
            [
                'message' => $e->getMessage(),
                // Optionally add more details like 'extensions' => ['code' => 'INTERNAL_SERVER_ERROR']
                // Avoid exposing file/line/trace in production responses
            ]
        ]
    ]);
}
