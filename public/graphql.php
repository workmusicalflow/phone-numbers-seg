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

    // Get repositories from container
    $smsHistoryRepository = $container->get(\App\Repositories\SMSHistoryRepository::class);
    $userRepository = $container->get(\App\Repositories\UserRepository::class);
    $customSegmentRepository = $container->get(\App\Repositories\CustomSegmentRepository::class);
    $smsService = $container->get(\App\Services\SMSService::class);

    // Load schema from file
    $schemaString = file_get_contents(__DIR__ . '/../src/GraphQL/schema.graphql');
    $schema = BuildSchema::build($schemaString);

    // Get Logger from container
    $logger = $container->get(LoggerInterface::class);
    $logger->info('GraphQL endpoint request received.');

    // Get Resolver instances from container
    $userResolver = $container->get(UserResolver::class);
    $contactResolver = $container->get(ContactResolver::class);
    $smsResolver = $container->get(SMSResolver::class);
    $authResolver = $container->get(AuthResolver::class);
    $logger->info('Resolver instances obtained from DI container.');

    // --- Field Resolver Mapping ---
    // This function tells GraphQL how to find the correct resolver method
    // for each field in the Query or Mutation type.
    $fieldResolver = function ($source, $args, $context, ResolveInfo $info) use (
        $userResolver,
        $contactResolver,
        $smsResolver,
        $authResolver,
        $logger,
        $container // Add container here
    ) {
        $fieldName = $info->fieldName;
        $parentTypeName = $info->parentType->name; // Query or Mutation

        $logger->debug("Resolving field: {$parentTypeName}.{$fieldName}");

        // Map Query fields to resolver methods
        if ($parentTypeName === 'Query') {
            switch ($fieldName) {
                // User Queries
                case 'users':
                    return $userResolver->resolveUsers(); // No args needed here
                case 'user':
                    return $userResolver->resolveUser($args);
                case 'userByUsername':
                    return $userResolver->resolveUserByUsername($args);
                case 'me':
                    // 'me' needs the authenticated user, handle directly or via resolver
                    // For now, let's assume it might be in UserResolver or AuthResolver
                    // This highlights the need for Phase 2 (Auth Improvement)
                    $userId = $_SESSION['user_id'] ?? null;
                    if (!$userId) return null;
                    return $userResolver->resolveUser(['id' => $userId]); // Example call
                case 'verifyToken':
                    return $authResolver->resolveVerifyToken($args, $context); // Placeholder

                    // Contact Queries
                case 'contacts':
                    return $contactResolver->resolveContacts($args, $context);
                case 'contact':
                    return $contactResolver->resolveContact($args, $context);
                case 'searchContacts':
                    return $contactResolver->resolveSearchContacts($args, $context);

                    // SMS Queries
                case 'smsHistory':
                    return $smsResolver->resolveSmsHistory($args, $context);
                case 'smsHistoryCount':
                    return $smsResolver->resolveSmsHistoryCount($args, $context);
                case 'segmentsForSMS':
                    return $smsResolver->resolveSegmentsForSMS($args, $context);

                    // Other Queries (Keep existing simple ones or move them)
                case 'test':
                    return "GraphQL is working via Resolver!";
                case 'hello':
                    return "Hello, world via Resolver!";
                case 'dashboardStats':
                    // Placeholder - move to a dedicated resolver if complex
                    return [
                        'usersCount' => $container->get(\App\Repositories\UserRepository::class)->count(),
                        'totalSmsCredits' => 0, // Implement logic
                        'lastUpdated' => date('Y-m-d H:i:s')
                    ];

                default:
                    $logger->warning("No Query resolver found for field: {$fieldName}");
                    return null; // Or throw error
            }
        }
        // Map Mutation fields to resolver methods
        elseif ($parentTypeName === 'Mutation') {
            switch ($fieldName) {
                // User Mutations
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

                    // Auth Mutations
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

                    // Contact Mutations
                case 'createContact':
                    return $contactResolver->mutateCreateContact($args, $context);
                case 'updateContact':
                    return $contactResolver->mutateUpdateContact($args, $context);
                case 'deleteContact':
                    return $contactResolver->mutateDeleteContact($args, $context);

                    // SMS Mutations
                case 'sendSms':
                    return $smsResolver->mutateSendSms($args, $context);
                case 'sendBulkSms':
                    return $smsResolver->mutateSendBulkSms($args, $context);
                case 'sendSmsToSegment':
                    return $smsResolver->mutateSendSmsToSegment($args, $context);
                case 'retrySms':
                    return $smsResolver->mutateRetrySms($args, $context);

                default:
                    $logger->warning("No Mutation resolver found for field: {$fieldName}");
                    return null; // Or throw error
            }
        }
        // Handle resolvers for fields within Types if needed (e.g., Contact.groups)
        // else if ($parentTypeName === 'Contact') { ... }

        $logger->error("Unhandled field resolution request.", ['parentType' => $parentTypeName, 'field' => $fieldName]);
        return null; // Should not happen if schema is covered
    };
    // --- End Field Resolver Mapping ---


    // Execute the query using the field resolver mapping
    // We no longer need the large $rootValue array
    Executor::setDefaultFieldResolver($fieldResolver);
    $logger->info('Default field resolver set.');

    $result = GraphQL::executeQuery(
        $schema,
        $input['query'] ?? '',
        null, // rootValue is now handled by the field resolver
        null, // context can be used to pass request-specific data (like authenticated user)
        $input['variables'] ?? []
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
