<?php

// CORS Headers
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Max-Age: 3600");


// Désactiver l'affichage direct des erreurs PHP (elles sont toujours journalisées)
ini_set('display_errors', 0);
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
    // Log the exception details before sending response
    error_log("Unhandled Exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\nStack trace:\n" . $e->getTraceAsString());

    // Check if headers already sent to avoid warning
    if (!headers_sent()) {
        header('Content-Type: application/json');
        header('HTTP/1.1 500 Internal Server Error');
    }
    echo json_encode([
        'errors' => [
            [
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                // Avoid sending trace in production for security
                // 'location' => $e->getFile() . ':' . $e->getLine(),
                // 'trace' => explode("\n", $e->getTraceAsString())
            ]
        ]
    ]);
    exit;
});


require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables from .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..'); // Specify the directory containing .env
try {
    // Load and validate required environment variables in one go
    $dotenv->load();
    $dotenv->required(['ORANGE_API_CLIENT_ID', 'ORANGE_API_CLIENT_SECRET'])->notEmpty();
    // Add other critical vars if needed, e.g., ->required(['DB_HOST', 'DB_NAME', ...])

} catch (\Dotenv\Exception\InvalidPathException $e) {
    error_log("Could not find .env file: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: application/json');
    echo json_encode(['errors' => [['message' => 'Application configuration error: .env file not found.']]]);
    exit;
} catch (\Dotenv\Exception\ValidationException $e) {
    // Handle missing or empty required variables
    error_log("Environment variable validation failed: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: application/json');
    echo json_encode([
        'errors' => [
            [
                'message' => 'Application configuration error: ' . $e->getMessage()
            ]
        ]
    ]);
    exit;
}


// Start the session before any output or session access
if (session_status() === PHP_SESSION_NONE) {
    // Configure session cookie parameters for local development (cross-port)
    // Ensure these are set *before* session_start()
    // For production with HTTPS, SameSite=None; Secure=true would be better.
    // For local HTTP, SameSite=Lax is a common compromise.
    // Domain is omitted to default to the exact hostname (localhost), path is root.
    session_set_cookie_params([
        'lifetime' => 0, // Session cookie
        'path' => '/',
        'domain' => '', // Defaults to the host name of the server which issued the cookie
        'secure' => false, // Set to true if backend is HTTPS
        'httponly' => true,
        'samesite' => 'Lax' // Lax allows cookies with top-level navigations and GET requests
    ]);
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
use App\GraphQL\Resolvers\ContactGroupResolver;
use App\GraphQL\Resolvers\ContactSMSResolver;
use App\GraphQL\Resolvers\WhatsApp\WhatsAppResolver;
use App\GraphQL\Resolvers\WhatsAppContactInsightsResolver;
use App\GraphQL\Types\DateTimeType;
use App\GraphQL\SchemaSetup;
use Psr\Log\LoggerInterface;
use GraphQL\Error\DebugFlag;

// Enable CORS for GraphQL endpoint
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS'); // Added GET for potential simple checks
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With'); // Added common headers
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Respond to preflight request with allowed methods and headers
    header('HTTP/1.1 204 No Content'); // 204 is often preferred for OPTIONS
    exit;
}

// Only accept POST requests for GraphQL mutations/queries
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

// --- Main Execution Block ---
try {
    // Create DI container
    $container = new \App\GraphQL\DIContainer();

    // Get Logger from container
    $logger = $container->get(LoggerInterface::class);
    $logger->info('GraphQL endpoint request received.');

    // Create GraphQL context using the factory
    $contextFactory = $container->get(\App\GraphQL\Context\GraphQLContextFactory::class);
    $graphQLContext = $contextFactory->create();
    $logger->info('GraphQL context created.');

    // Charger uniquement le schéma principal pour le moment
    $schemaString = file_get_contents(__DIR__ . '/../src/GraphQL/schema.graphql');
    if ($schemaString === false) {
        throw new \RuntimeException("Failed to load GraphQL schema file.");
    }
    
    // Construire le schéma
    $schema = BuildSchema::build($schemaString);
    
    // Ajouter manuellement les types nécessaires
    $schema->getTypeMap()['DateTime'] = new DateTimeType();

    // Get Resolver instances from container
    $userResolver = $container->get(UserResolver::class);
    $contactResolver = $container->get(ContactResolver::class);
    $smsResolver = $container->get(SMSResolver::class);
    $authResolver = $container->get(AuthResolver::class);
    $contactGroupResolver = $container->get(ContactGroupResolver::class);
    $contactSmsResolver = $container->get(ContactSMSResolver::class);
    $whatsAppResolver = $container->get(WhatsAppResolver::class);
    $whatsAppContactInsightsResolver = $container->get('App\\GraphQL\\Resolvers\\WhatsAppContactInsightsResolver');
    $logger->info('Resolver instances obtained from DI container.');

    // --- Field Resolver Mapping ---
    $fieldResolver = function ($source, $args, $context, ResolveInfo $info) use (
        $userResolver,
        $contactResolver,
        $smsResolver,
        $authResolver,
        $contactGroupResolver,
        $contactSmsResolver,
        $whatsAppResolver,
        $whatsAppContactInsightsResolver,
        $logger,
        $container,
        $graphQLContext // Add the GraphQL context
    ) {
        $fieldName = $info->fieldName;
        $parentTypeName = $info->parentType->name;

        $logger->debug("Attempting to resolve field: {$parentTypeName}.{$fieldName}");

        // Always use our GraphQLContext for consistent access to DataLoaders
        // If $context is not already our GraphQLContext, use the one we created
        if (!($context instanceof \App\GraphQL\Context\GraphQLContext)) {
            $context = $graphQLContext;
            $logger->debug("Using global GraphQLContext for field: {$parentTypeName}.{$fieldName}");
        }

        try { // Add try-catch within the resolver for more granular error logging
            // Handle top-level Query fields
            if ($parentTypeName === 'Query') {
                $logger->debug("Handling Query field: {$fieldName}");
                switch ($fieldName) {
                    case 'users':
                        return $userResolver->resolveUsers($args); // Pass $args here
                    case 'user':
                        return $userResolver->resolveUser($args);
                    case 'userByUsername':
                        return $userResolver->resolveUserByUsername($args);
                    case 'me':
                        return $authResolver->resolveMe($args, $context); // Corrected to use AuthResolver
                    case 'verifyToken':
                        return $authResolver->resolveVerifyToken($args, $context);
                    case 'contacts':
                        return $contactResolver->resolveContacts($args, $context);
                    case 'contact':
                        return $contactResolver->resolveContact($args, $context);
                    case 'searchContacts':
                        return $contactResolver->resolveSearchContacts($args, $context);
                    case 'contactsCount':
                        return $contactResolver->resolveContactsCount($args, $context);
                    case 'contactGroups':
                        return $contactGroupResolver->resolveContactGroups($args, $context);
                    case 'contactGroup':
                        return $contactGroupResolver->resolveContactGroup($args, $context);
                    case 'contactGroupsCount':
                        return $contactGroupResolver->resolveContactGroupsCount($args, $context);
                    case 'contactsInGroup':
                        return $contactGroupResolver->resolveContactsInGroup($args, $context);
                    case 'contactsInGroupCount':
                        return $contactGroupResolver->resolveContactsInGroupCount($args, $context);
                    case 'groupsForContact':
                        return $contactResolver->resolveGroupsForContact($args, $context);
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
                        return [
                            'usersCount' => $container->get(\App\Repositories\UserRepository::class)->count(),
                            'totalSmsCredits' => 0,
                            'lastUpdated' => date('Y-m-d H:i:s')
                        ];
                    case 'getWhatsAppMessages':
                        return $whatsAppResolver->getWhatsAppMessages(
                            $args['limit'] ?? null,
                            $args['offset'] ?? null,
                            $args['phoneNumber'] ?? null,
                            $args['status'] ?? null,
                            $args['type'] ?? null,
                            $args['direction'] ?? null,
                            $args['startDate'] ?? null,
                            $args['endDate'] ?? null,
                            $context
                        );
                    case 'getWhatsAppUserTemplates':
                        return $whatsAppResolver->getWhatsAppUserTemplates($context);
                    case 'getContactWhatsAppInsights':
                        return $whatsAppContactInsightsResolver->getContactWhatsAppInsights($args['contactId']);
                    case 'getContactsWhatsAppSummary':
                        return $whatsAppContactInsightsResolver->getContactsWhatsAppSummary($args['contactIds']);
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
                        return $authResolver->mutateRefreshToken($args, $context);
                    case 'logout':
                        return $authResolver->mutateLogout($args, $context);
                    case 'requestPasswordReset':
                        return $authResolver->mutateRequestPasswordReset($args, $context);
                    case 'resetPassword':
                        return $authResolver->mutateResetPassword($args, $context);
                    case 'createContact':
                        return $contactResolver->mutateCreateContact($args, $context);
                    case 'updateContact':
                        return $contactResolver->mutateUpdateContact($args, $context);
                    case 'deleteContact':
                        return $contactResolver->mutateDeleteContact($args, $context);
                    case 'createContactGroup':
                        return $contactGroupResolver->mutateCreateContactGroup($args, $context);
                    case 'updateContactGroup':
                        return $contactGroupResolver->mutateUpdateContactGroup($args, $context);
                    case 'deleteContactGroup':
                        return $contactGroupResolver->mutateDeleteContactGroup($args, $context);
                    case 'addContactToGroup':
                        return $contactGroupResolver->mutateAddContactToGroup($args, $context);
                    case 'removeContactFromGroup':
                        return $contactGroupResolver->mutateRemoveContactFromGroup($args, $context);
                    case 'addContactsToGroup':
                        return $contactGroupResolver->mutateAddContactsToGroup($args, $context);
                    case 'sendSms':
                        return $smsResolver->mutateSendSms($args, $context);
                    case 'sendBulkSms':
                        return $smsResolver->mutateSendBulkSms($args, $context);
                    case 'sendSmsToSegment':
                        return $smsResolver->mutateSendSmsToSegment($args, $context);
                    case 'sendSmsToAllContacts':
                        return $smsResolver->mutateSendSmsToAllContacts($args, $context);
                    case 'retrySms':
                        return $smsResolver->mutateRetrySms($args, $context);
                    case 'sendWhatsAppMessage':
                        return $whatsAppResolver->sendWhatsAppMessage($args['message'], $context);
                    case 'sendWhatsAppTemplate':
                        return $whatsAppResolver->sendWhatsAppTemplate($args['template'], $context);
                    case 'sendWhatsAppTemplateV2':
                        // Utiliser notre nouveau contrôleur dédié pour cette mutation
                        $whatsappTemplateController = $container->get('App\\GraphQL\\Controllers\\WhatsApp\\WhatsAppTemplateController');
                        $logger->info("Routing sendWhatsAppTemplateV2 to dedicated controller");
                        
                        // Créer l'objet SendTemplateInput à partir des arguments
                        $input = new \App\GraphQL\Types\WhatsApp\SendTemplateInput(
                            $args['input']['recipientPhoneNumber'],
                            $args['input']['templateName'],
                            $args['input']['templateLanguage'],
                            $args['input']['templateComponentsJsonString'] ?? null,
                            $args['input']['headerMediaUrl'] ?? null,
                            $args['input']['bodyVariables'] ?? [],
                            $args['input']['buttonVariables'] ?? []
                        );
                        
                        // Appeler le contrôleur directement
                        $result = $whatsappTemplateController->sendWhatsAppTemplateV2($input, $context);
                        
                        // Convertir l'objet en tableau associatif pour une meilleure compatibilité avec GraphQL
                        if (is_object($result) && method_exists($result, 'getSuccess')) {
                            return [
                                'success' => $result->getSuccess() ?? false,
                                'messageId' => $result->getMessageId(),
                                'error' => $result->getError()
                            ];
                        }
                        
                        return $result;
                    case 'sendWhatsAppMediaMessage':
                        return $whatsAppResolver->sendWhatsAppMediaMessage(
                            $args['recipient'],
                            $args['type'],
                            $args['mediaIdOrUrl'],
                            $args['caption'] ?? null,
                            $context
                        );
                    case 'importPhoneNumbers':
                        $importExportController = $container->get(\App\GraphQL\Controllers\ImportExportController::class);
                        return $importExportController->importPhoneNumbers(
                            $args['numbers'],
                            $args['skipInvalid'] ?? true,
                            $args['segmentImmediately'] ?? true,
                            $args['groupIds'] ?? null,
                            $args['userId'] ?? null
                        );
                    case 'importPhoneNumbersWithData':
                        $importExportController = $container->get(\App\GraphQL\Controllers\ImportExportController::class);
                        return $importExportController->importPhoneNumbersWithData(
                            $args['phoneData'],
                            $args['skipInvalid'] ?? true,
                            $args['segmentImmediately'] ?? true,
                            $args['groupIds'] ?? null,
                            $args['userId'] ?? null
                        );
                }
            }

            // Handle WhatsAppMessageHistory fields
            if ($parentTypeName === 'WhatsAppMessageHistory') {
                $logger->debug("Resolving WhatsAppMessageHistory field: {$fieldName}");

                // Pour les types personnalisés, on utilise notre Type GraphQL
                $whatsappType = $container->get(\App\GraphQL\Types\WhatsApp\WhatsAppMessageHistoryType::class);

                // Appeler la méthode correspondante sur le type
                $methodName = 'get' . ucfirst($fieldName);
                if (method_exists($whatsappType, $methodName)) {
                    return $whatsappType->$methodName($source);
                }

                // Fallback to default resolver si la méthode n'existe pas
                return Executor::defaultFieldResolver($source, $args, $context, $info);
            }

            // Handle nested field resolvers
            if ($parentTypeName === 'Contact') {
                $logger->debug("Resolving Contact field: {$fieldName}");

                // Groups field handling
                if ($fieldName === 'groups') {
                    $logger->debug("Resolving Contact.groups field");

                    // Capture all contact IDs for batch processing
                    static $isFirstContact = true;
                    static $allContactIds = [];
                    static $contactGroupsCache = [];
                    static $batchProcessed = false;

                    $contactId = (int)($source['id'] ?? 0);
                    if ($contactId <= 0) {
                        return [];
                    }

                    // If we've already processed contacts in batch and have this result cached
                    if ($batchProcessed && isset($contactGroupsCache[$contactId])) {
                        $logger->debug("Returning cached groups for contact ID: $contactId from batch");
                        return $contactGroupsCache[$contactId];
                    }

                    // For the first contact, get ALL contacts and process them
                    if ($isFirstContact) {
                        $isFirstContact = false;

                        // Get all contact IDs from the source data
                        if (isset($info) && isset($info->operation) && isset($info->operation->selectionSet)) {
                            $selections = $info->operation->selectionSet->selections;
                            foreach ($selections as $selection) {
                                if ($selection->name->value === 'contacts' && isset($selection->selectionSet)) {
                                    // Find the 'contacts' field and extract all contact IDs
                                    // Safely access path information with null coalescing operator
                                    $rootValue = $info->path ?? [];

                                    // Debug info
                                    $logger->debug('GraphQL path info: ' . json_encode($rootValue));

                                    // Check if we have a contacts field in the root value
                                    if (isset($rootValue[0]) && $rootValue[0] === 'contacts' && isset($info->rootValue['contacts'])) {
                                        $contacts = $info->rootValue['contacts'];
                                        foreach ($contacts as $contact) {
                                            if (isset($contact['id'])) {
                                                $allContactIds[] = (int)$contact['id'];
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        // If we couldn't get all contacts, at least add this one
                        if (empty($allContactIds)) {
                            $allContactIds[] = $contactId;
                        }

                        $uniqueContactIds = array_values(array_unique($allContactIds));
                        $logger->info("SUPER BATCH OPTIMIZATION: Pre-loading ALL " . count($uniqueContactIds) . " contacts' groups in one batch");

                        // Get the context-scoped DataLoader
                        if (isset($context) && method_exists($context, 'getDataLoader')) {
                            $dataLoader = $context->getDataLoader('contactGroups');
                            if ($dataLoader) {
                                // Load all contact groups at once
                                $results = $dataLoader->loadMany($uniqueContactIds);
                                $batchProcessed = true;

                                // Map results to contact IDs
                                foreach ($uniqueContactIds as $index => $cId) {
                                    $contactGroupsCache[$cId] = $results[$index] ?? [];
                                }
                            }
                        }
                    }

                    // Always collect this contact ID for potential future batching
                    $allContactIds[] = $contactId;

                    // If we have already processed in batch, return from cache
                    if ($batchProcessed && isset($contactGroupsCache[$contactId])) {
                        return $contactGroupsCache[$contactId];
                    }

                    // If not batched yet, process normally
                    return $contactResolver->resolveContactGroups($source, $args, $context);
                }

                // SMS fields - use our dedicated resolver
                if ($fieldName === 'smsHistory') {
                    return $contactSmsResolver->resolveSmsHistory($source, $args);
                } else if ($fieldName === 'smsTotalCount') {
                    return $contactSmsResolver->resolveSmsTotalCount($source);
                } else if ($fieldName === 'smsSentCount') {
                    return $contactSmsResolver->resolveSmsSentCount($source);
                } else if ($fieldName === 'smsFailedCount') {
                    return $contactSmsResolver->resolveSmsFailedCount($source);
                } else if ($fieldName === 'smsScore') {
                    return $contactSmsResolver->resolveSmsScore($source);
                }
            }

            // Fallback to default resolver
            $logger->debug("Falling back to default resolver for {$parentTypeName}.{$fieldName}");
            return Executor::defaultFieldResolver($source, $args, $context, $info);
        } catch (\Throwable $e) {
            // Log error originating from within a specific resolver method
            $logger->error("Error resolving field {$parentTypeName}.{$fieldName}: " . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString() // Log trace for debugging
            ]);
            // Re-throw the exception to be caught by the outer handler and formatted for GraphQL response
            throw $e;
        }
    };
    // --- End Field Resolver Mapping ---

    // Execute the query using the custom field resolver and context
    $result = GraphQL::executeQuery(
        $schema,
        $input['query'] ?? '',
        null, // rootValue
        $graphQLContext, // Use our GraphQL context
        $input['variables'] ?? [],
        null, // operationName
        $fieldResolver // Use the custom field resolver
    );
    $logger->info('GraphQL query executed with context.');

    // Ensure any pending DataLoader batches are dispatched
    if (isset($graphQLContext) && method_exists($graphQLContext, 'getDataLoader')) {
        // Dispatch ContactGroups DataLoader
        $contactGroupsLoader = $graphQLContext->getDataLoader('contactGroups');
        if ($contactGroupsLoader && method_exists($contactGroupsLoader, 'dispatchQueue')) {
            $contactGroupsLoader->dispatchQueue();
            $logger->debug('Final ContactGroups DataLoader batch dispatched');
        }

        // Dispatch SMSHistory DataLoader
        $smsHistoryLoader = $graphQLContext->getDataLoader('smsHistory');
        if ($smsHistoryLoader && method_exists($smsHistoryLoader, 'dispatchQueue')) {
            $smsHistoryLoader->dispatchQueue();
            $logger->debug('Final SMSHistory DataLoader batch dispatched');
        }
    }

    // Return the result
    header('Content-Type: application/json');
    // Add Debug flags for more detailed errors in the response (useful for debugging)
    $debugFlags = DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE;
    $output = $result->toArray($debugFlags);
    echo json_encode($output);
    $logger->info('GraphQL response sent.');
} catch (\Throwable $e) { // Catch any other Throwable during setup or execution
    // Log critical error if logger is available
    if (isset($logger)) {
        $logger->critical('GraphQL endpoint critical error: ' . $e->getMessage(), [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
    } else {
        // Fallback to PHP error log if logger failed to initialize
        error_log('GraphQL endpoint critical error (Logger unavailable): ' . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    }

    // Format error for GraphQL response
    $error = [
        'message' => 'Internal server error: ' . $e->getMessage(),
        'extensions' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => explode("\n", $e->getTraceAsString()) // Provide trace for debugging
        ]
    ];
    // Check if headers already sent
    if (!headers_sent()) {
        header('HTTP/1.1 500 Internal Server Error');
        header('Content-Type: application/json');
    }
    echo json_encode(['errors' => [$error]]);
    exit;
}
