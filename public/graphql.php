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

/*
    // OLD $rootValue definition - REMOVED

    $rootValue = [
        // User resolvers
        'users' => function () use ($userRepository) {
            error_log('Executing users resolver');
            try {
                $users = $userRepository->findAll();
                error_log('Found ' . count($users) . ' users');

                // Convert User objects to arrays
                $result = [];
                foreach ($users as $user) {
                    $result[] = [
                        'id' => $user->getId(),
                        'username' => $user->getUsername(),
                        'email' => $user->getEmail(),
                        'smsCredit' => $user->getSmsCredit(),
                        'smsLimit' => $user->getSmsLimit(),
                        'isAdmin' => $user->isAdmin(),
                        'createdAt' => $user->getCreatedAt(),
                        'updatedAt' => $user->getUpdatedAt()
                    ];
                }
                error_log('Converted users to arrays: ' . json_encode($result));
                return $result;
            } catch (\Exception $e) {
                error_log('Error in users resolver: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
                throw $e;
            }
        },
        'user' => function ($rootValue, $args) use ($userRepository) {
            error_log('Executing user resolver for ID: ' . $args['id']);
            try {
                $user = $userRepository->findById((int)$args['id']);
                if (!$user) {
                    return null;
                }

                // Convert User object to array
                $result = [
                    'id' => $user->getId(),
                    'username' => $user->getUsername(),
                    'email' => $user->getEmail(),
                    'smsCredit' => $user->getSmsCredit(),
                    'smsLimit' => $user->getSmsLimit(),
                    'isAdmin' => $user->isAdmin(),
                    'createdAt' => $user->getCreatedAt(),
                    'updatedAt' => $user->getUpdatedAt()
                ];
                return $result;
            } catch (\Exception $e) {
                error_log('Error in user resolver: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
                throw $e;
            }
        },
        'userByUsername' => function ($rootValue, $args) use ($userRepository) {
            error_log('Executing userByUsername resolver for username: ' . $args['username']);
            try {
                $user = $userRepository->findByUsername($args['username']);
                if (!$user) {
                    return null;
                }

                // Convert User object to array
                $result = [
                    'id' => $user->getId(),
                    'username' => $user->getUsername(),
                    'email' => $user->getEmail(),
                    'smsCredit' => $user->getSmsCredit(),
                    'smsLimit' => $user->getSmsLimit(),
                    'isAdmin' => $user->isAdmin(),
                    'createdAt' => $user->getCreatedAt(),
                    'updatedAt' => $user->getUpdatedAt()
                ];
                return $result;
            } catch (\Exception $e) {
                error_log('Error in userByUsername resolver: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
                throw $e;
            }
        },
        'createUser' => function ($rootValue, $args) use ($userRepository) {
            error_log('Executing createUser resolver for username: ' . $args['username']);
            try {
                // Vérifier si l'utilisateur existe déjà
                $existingUser = $userRepository->findByUsername($args['username']);
                if ($existingUser) {
                    throw new \Exception("Un utilisateur avec ce nom d'utilisateur existe déjà");
                }

                // Hacher le mot de passe
                $hashedPassword = password_hash($args['password'], PASSWORD_DEFAULT);

                // Créer l'utilisateur
                $smsCredit = isset($args['smsCredit']) ? (int)$args['smsCredit'] : 10;
                $smsLimit = isset($args['smsLimit']) ? (int)$args['smsLimit'] : null;
                $user = new \App\Models\User($args['username'], $hashedPassword, null, $args['email'] ?? null, $smsCredit, $smsLimit);

                // Sauvegarder l'utilisateur
                $user = $userRepository->save($user);

                // Convert User object to array
                $result = [
                    'id' => $user->getId(),
                    'username' => $user->getUsername(),
                    'email' => $user->getEmail(),
                    'smsCredit' => $user->getSmsCredit(),
                    'smsLimit' => $user->getSmsLimit(),
                    'isAdmin' => $user->isAdmin(),
                    'createdAt' => $user->getCreatedAt(),
                    'updatedAt' => $user->getUpdatedAt()
                ];
                return $result;
            } catch (\Exception $e) {
                error_log('Error in createUser resolver: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
                throw $e;
            }
        },
        'updateUser' => function ($rootValue, $args) use ($userRepository) {
            error_log('Executing updateUser resolver for ID: ' . $args['id']);
            try {
                // Récupérer l'utilisateur
                $user = $userRepository->findById((int)$args['id']);
                if (!$user) {
                    throw new \Exception("Utilisateur non trouvé");
                }

                // Mettre à jour les champs
                if (isset($args['email'])) {
                    $user->setEmail($args['email']);
                }

                if (isset($args['smsLimit'])) {
                    $user->setSmsLimit((int)$args['smsLimit']);
                }

                // Sauvegarder l'utilisateur
                $user = $userRepository->save($user);

                // Convert User object to array
                $result = [
                    'id' => $user->getId(),
                    'username' => $user->getUsername(),
                    'email' => $user->getEmail(),
                    'smsCredit' => $user->getSmsCredit(),
                    'smsLimit' => $user->getSmsLimit(),
                    'isAdmin' => $user->isAdmin(),
                    'createdAt' => $user->getCreatedAt(),
                    'updatedAt' => $user->getUpdatedAt()
                ];
                return $result;
            } catch (\Exception $e) {
                error_log('Error in updateUser resolver: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
                throw $e;
            }
        },
        'changePassword' => function ($rootValue, $args) use ($userRepository) {
            error_log('Executing changePassword resolver for ID: ' . $args['id']);
            try {
                // Récupérer l'utilisateur
                $user = $userRepository->findById((int)$args['id']);
                if (!$user) {
                    throw new \Exception("Utilisateur non trouvé");
                }

                // Hacher le nouveau mot de passe
                $hashedPassword = password_hash($args['newPassword'], PASSWORD_DEFAULT);
                $user->setPassword($hashedPassword);

                // Sauvegarder l'utilisateur
                $user = $userRepository->save($user);

                // Convert User object to array
                $result = [
                    'id' => $user->getId(),
                    'username' => $user->getUsername(),
                    'email' => $user->getEmail(),
                    'smsCredit' => $user->getSmsCredit(),
                    'smsLimit' => $user->getSmsLimit(),
                    'isAdmin' => $user->isAdmin(),
                    'createdAt' => $user->getCreatedAt(),
                    'updatedAt' => $user->getUpdatedAt()
                ];
                return $result;
            } catch (\Exception $e) {
                error_log('Error in changePassword resolver: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
                throw $e;
            }
        },
        'addCredits' => function ($rootValue, $args) use ($userRepository) {
            error_log('Executing addCredits resolver for ID: ' . $args['id'] . ', amount: ' . $args['amount']);
            try {
                // Récupérer l'utilisateur
                $user = $userRepository->findById((int)$args['id']);
                if (!$user) {
                    throw new \Exception("Utilisateur non trouvé");
                }

                // Ajouter les crédits
                $currentCredits = $user->getSmsCredit();
                $user->setSmsCredit($currentCredits + (int)$args['amount']);

                // Sauvegarder l'utilisateur
                $user = $userRepository->save($user);

                // Convert User object to array
                $result = [
                    'id' => $user->getId(),
                    'username' => $user->getUsername(),
                    'email' => $user->getEmail(),
                    'smsCredit' => $user->getSmsCredit(),
                    'smsLimit' => $user->getSmsLimit(),
                    'createdAt' => $user->getCreatedAt(),
                    'updatedAt' => $user->getUpdatedAt()
                ];
                return $result;
            } catch (\Exception $e) {
                error_log('Error in addCredits resolver: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
                throw $e;
            }
        },
        'deleteUser' => function ($rootValue, $args) use ($userRepository) {
            // Récupérer l'utilisateur
            $user = $userRepository->findById((int)$args['id']);
            if (!$user) {
                throw new \Exception("Utilisateur non trouvé");
            }

            // Supprimer l'utilisateur
            return $userRepository->delete((int)$args['id']);
        },
        'retrySms' => function ($rootValue, $args) use ($smsService, $smsHistoryRepository) {
            $id = $args['id'];
            $userId = isset($args['userId']) ? (int)$args['userId'] : null;

            try {
                // Get the SMS history record
                $smsHistory = $smsHistoryRepository->findById((int)$id);
                if (!$smsHistory) {
                    return [
                        'id' => $id,
                        'phoneNumber' => '',
                        'message' => '',
                        'status' => 'FAILED',
                        'createdAt' => date('Y-m-d H:i:s')
                    ];
                }

                // Retry sending the SMS
                $result = $smsService->sendSMS($smsHistory->getPhoneNumber(), $smsHistory->getMessage(), $userId);

                // Create a new SMS history record for the retry
                $status = isset($result['outboundSMSMessageRequest']) ? 'SENT' : 'FAILED';
                $newSmsHistory = $smsHistoryRepository->create(
                    $smsHistory->getPhoneNumber(),
                    $smsHistory->getMessage(),
                    $status,
                    isset($result['outboundSMSMessageRequest']['resourceURL']) ? basename($result['outboundSMSMessageRequest']['resourceURL']) : null,
                    $status === 'FAILED' ? 'Retry failed: ' . json_encode($result) : null,
                    $smsHistory->getSenderAddress(),
                    $smsHistory->getSenderName(),
                    $smsHistory->getSegmentId(),
                    null,
                    $userId ?? $smsHistory->getUserId()
                );

                return [
                    'id' => $newSmsHistory->getId(),
                    'phoneNumber' => $newSmsHistory->getPhoneNumber(),
                    'message' => $newSmsHistory->getMessage(),
                    'status' => $newSmsHistory->getStatus(),
                    'createdAt' => $newSmsHistory->getCreatedAt()
                ];
            } catch (\Exception $e) {
                error_log('Error in retrySms: ' . $e->getMessage());
                return [
                    'id' => $id,
                    'phoneNumber' => '',
                    'message' => '',
                    'status' => 'FAILED',
                    'createdAt' => date('Y-m-d H:i:s')
                ];
            }
        },

        'smsHistory' => function ($rootValue, $args) use ($smsHistoryRepository, $customSegmentRepository) {
            $limit = isset($args['limit']) ? $args['limit'] : 100;
            $offset = isset($args['offset']) ? $args['offset'] : 0;
            $userId = isset($args['userId']) ? (int)$args['userId'] : null;

            // Si un userId est fourni, filtrer par utilisateur
            $history = $userId !== null
                ? $smsHistoryRepository->findByUserId($userId, $limit, $offset)
                : $smsHistoryRepository->findAll($limit, $offset);
            $result = [];

            foreach ($history as $item) {
                $smsData = [
                    'id' => $item->getId(),
                    'phoneNumber' => $item->getPhoneNumber(),
                    'message' => $item->getMessage(),
                    'status' => $item->getStatus(),
                    'messageId' => $item->getMessageId(),
                    'errorMessage' => $item->getErrorMessage(),
                    'senderAddress' => $item->getSenderAddress(),
                    'senderName' => $item->getSenderName(),
                    'createdAt' => $item->getCreatedAt(),
                    'userId' => $item->getUserId()
                ];

                // Add segment information if available
                if ($item->getSegmentId()) {
                    try {
                        $segment = $customSegmentRepository->findById($item->getSegmentId());
                        if ($segment) {
                            $smsData['segment'] = [
                                'id' => $segment->getId(),
                                'name' => $segment->getName()
                            ];
                        }
                    } catch (\Exception $e) {
                        // Ignore segment errors
                    }
                }

                $result[] = $smsData;
            }

            return $result;
        },

        'smsHistoryCount' => function ($rootValue, $args) use ($smsHistoryRepository) {
            $userId = isset($args['userId']) ? (int)$args['userId'] : null;

            return $userId !== null
                ? $smsHistoryRepository->countByUserId($userId)
                : $smsHistoryRepository->count();
        },
        'segmentsForSMS' => function () use ($customSegmentRepository) {
            $segments = $customSegmentRepository->findAll();
            $result = [];

            foreach ($segments as $segment) {
                $result[] = [
                    'id' => $segment->getId(),
                    'name' => $segment->getName(),
                    'description' => $segment->getDescription(),
                    'phoneNumberCount' => 5 // Placeholder count
                ];
            }

            return $result;
        },
        'test' => function () {
            return "GraphQL is working!";
        },
        'hello' => function () {
            return "Hello, world!";
        },
        'sendSms' => function ($rootValue, $args) use ($smsService) {
            $phoneNumber = $args['phoneNumber'];
            $message = $args['message'];
            $userId = isset($args['userId']) ? (int)$args['userId'] : null;

            try {
                $result = $smsService->sendSMS($phoneNumber, $message, $userId);

                return [
                    'id' => uniqid(),
                    'phoneNumber' => $phoneNumber,
                    'message' => $message,
                    'status' => isset($result['outboundSMSMessageRequest']) ? 'SENT' : 'FAILED',
                    'createdAt' => date('Y-m-d H:i:s')
                ];
            } catch (\Exception $e) {
                error_log('Error in sendSms: ' . $e->getMessage());
                return [
                    'id' => uniqid(),
                    'phoneNumber' => $phoneNumber,
                    'message' => $message,
                    'status' => 'FAILED',
                    'createdAt' => date('Y-m-d H:i:s')
                ];
            }
        },
        'sendBulkSms' => function ($rootValue, $args) use ($smsService) {
            $phoneNumbers = $args['phoneNumbers'];
            $message = $args['message'];
            $userId = isset($args['userId']) ? (int)$args['userId'] : null;

            try {
                $results = $smsService->sendBulkSMS($phoneNumbers, $message, $userId);

                // Count successful and failed sends
                $successful = 0;
                $failed = 0;
                $formattedResults = [];

                foreach ($results as $number => $result) {
                    if ($result['status'] === 'success') {
                        $successful++;
                    } else {
                        $failed++;
                    }

                    $formattedResults[] = [
                        'phoneNumber' => $number,
                        'status' => $result['status'] === 'success' ? 'success' : 'error',
                        'message' => $result['status'] === 'success' ? 'SMS envoyé avec succès' : $result['message']
                    ];
                }

                return [
                    'status' => 'success',
                    'message' => 'Envoi en masse terminé',
                    'summary' => [
                        'total' => count($phoneNumbers),
                        'successful' => $successful,
                        'failed' => $failed
                    ],
                    'results' => $formattedResults
                ];
            } catch (\Exception $e) {
                error_log('Error in sendBulkSms: ' . $e->getMessage());
                return [
                    'status' => 'error',
                    'message' => 'Erreur lors de l\'envoi en masse: ' . $e->getMessage(),
                    'summary' => [
                        'total' => count($phoneNumbers),
                        'successful' => 0,
                        'failed' => count($phoneNumbers)
                    ],
                    'results' => []
                ];
            }
        },
        'login' => function ($rootValue, $args) use ($container) {
            $username = $args['username'];
            $password = $args['password'];

            try {
                $authService = $container->get(\App\Services\Interfaces\AuthServiceInterface::class);
                $user = $authService->authenticate($username, $password);

                if (!$user) {
                    throw new \Exception("Nom d'utilisateur ou mot de passe incorrect");
                }

                // Générer un token JWT (ou tout autre type de token)
                $token = bin2hex(random_bytes(32)); // Génération simple pour l'exemple

                // Retourner les informations de l'utilisateur
                return [
                    'token' => $token,
                    'user' => [
                        'id' => $user->getId(),
                        'username' => $user->getUsername(),
                        'email' => $user->getEmail(),
                        'smsCredit' => $user->getSmsCredit(),
                        'smsLimit' => $user->getSmsLimit(),
                        'isAdmin' => $user->isAdmin(),
                        'createdAt' => $user->getCreatedAt(),
                        'updatedAt' => $user->getUpdatedAt()
                    ]
                ];
            } catch (\Exception $e) {
                error_log('Error in login resolver: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
                throw $e;
            }
        },

        // Contact resolvers
        'contacts' => function ($rootValue, $args) use ($contactRepository) {
            error_log('Executing contacts resolver');
            try {
                // Get the current user ID from the session
                $userId = $_SESSION['user_id'] ?? null;
                if (!$userId) {
                    throw new \Exception("User not authenticated");
                }

                $limit = isset($args['limit']) ? $args['limit'] : 100;
                $offset = isset($args['offset']) ? $args['offset'] : 0;

                $contacts = $contactRepository->findByUserId($userId, $limit, $offset);
                error_log('Found ' . count($contacts) . ' contacts for user ' . $userId);

                // Convert Contact objects to arrays
                $result = [];
                foreach ($contacts as $contact) {
                    $result[] = [
                        'id' => $contact->getId(),
                        'name' => $contact->getName(),
                        'phoneNumber' => $contact->getPhoneNumber(),
                        'email' => $contact->getEmail(),
                        'notes' => $contact->getNotes(),
                        'createdAt' => $contact->getCreatedAt(),
                        'updatedAt' => $contact->getUpdatedAt()
                    ];
                }
                error_log('Converted contacts to arrays: ' . json_encode($result));
                return $result;
            } catch (\Exception $e) {
                error_log('Error in contacts resolver: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
                throw $e;
            }
        },
        'contact' => function ($rootValue, $args) use ($contactRepository) {
            error_log('Executing contact resolver for ID: ' . $args['id']);
            try {
                // Get the current user ID from the session
                $userId = $_SESSION['user_id'] ?? null;
                if (!$userId) {
                    throw new \Exception("User not authenticated");
                }

                $contact = $contactRepository->findById((int)$args['id']);
                if (!$contact) {
                    return null;
                }

                // Check if the contact belongs to the current user
                if ($contact->getUserId() !== $userId) {
                    return null;
                }

                // Convert Contact object to array
                $result = [
                    'id' => $contact->getId(),
                    'name' => $contact->getName(),
                    'phoneNumber' => $contact->getPhoneNumber(),
                    'email' => $contact->getEmail(),
                    'notes' => $contact->getNotes(),
                    'createdAt' => $contact->getCreatedAt(),
                    'updatedAt' => $contact->getUpdatedAt()
                ];
                return $result;
            } catch (\Exception $e) {
                error_log('Error in contact resolver: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
                throw $e;
            }
        },
        'searchContacts' => function ($rootValue, $args) use ($contactRepository) {
            error_log('Executing searchContacts resolver for query: ' . $args['query']);
            try {
                // Get the current user ID from the session
                $userId = $_SESSION['user_id'] ?? null;
                if (!$userId) {
                    throw new \Exception("User not authenticated");
                }

                $limit = isset($args['limit']) ? $args['limit'] : 100;
                $offset = isset($args['offset']) ? $args['offset'] : 0;

                $contacts = $contactRepository->searchByUserId($args['query'], $userId, $limit, $offset);
                error_log('Found ' . count($contacts) . ' contacts for query ' . $args['query']);

                // Convert Contact objects to arrays
                $result = [];
                foreach ($contacts as $contact) {
                    $result[] = [
                        'id' => $contact->getId(),
                        'name' => $contact->getName(),
                        'phoneNumber' => $contact->getPhoneNumber(),
                        'email' => $contact->getEmail(),
                        'notes' => $contact->getNotes(),
                        'createdAt' => $contact->getCreatedAt(),
                        'updatedAt' => $contact->getUpdatedAt()
                    ];
                }
                error_log('Converted contacts to arrays: ' . json_encode($result));
                return $result;
            } catch (\Exception $e) {
                error_log('Error in searchContacts resolver: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
                throw $e;
            }
        },
        'createContact' => function ($rootValue, $args) use ($contactRepository) {
            error_log('Executing createContact resolver for name: ' . $args['name']);
            try {
                // Get the current user ID from the session
                $userId = $_SESSION['user_id'] ?? null;
                if (!$userId) {
                    throw new \Exception("User not authenticated");
                }

                // Create a new contact
                $contact = new \App\Models\Contact(
                    0, // ID will be generated by the database
                    $userId,
                    $args['name'],
                    $args['phoneNumber'],
                    $args['email'] ?? null,
                    $args['notes'] ?? null
                );

                // Save the contact
                $contact = $contactRepository->create($contact);

                // Convert Contact object to array
                $result = [
                    'id' => $contact->getId(),
                    'name' => $contact->getName(),
                    'phoneNumber' => $contact->getPhoneNumber(),
                    'email' => $contact->getEmail(),
                    'notes' => $contact->getNotes(),
                    'createdAt' => $contact->getCreatedAt(),
                    'updatedAt' => $contact->getUpdatedAt()
                ];
                return $result;
            } catch (\Exception $e) {
                error_log('Error in createContact resolver: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
                throw $e;
            }
        },
        'updateContact' => function ($rootValue, $args) use ($contactRepository) {
            error_log('Executing updateContact resolver for ID: ' . $args['id']);
            try {
                // Get the current user ID from the session
                $userId = $_SESSION['user_id'] ?? null;
                if (!$userId) {
                    throw new \Exception("User not authenticated");
                }

                // Get the existing contact
                $contact = $contactRepository->findById((int)$args['id']);
                if (!$contact) {
                    throw new \Exception("Contact not found");
                }

                // Check if the contact belongs to the current user
                if ($contact->getUserId() !== $userId) {
                    throw new \Exception("Contact not found");
                }

                // Update the contact
                $updatedContact = new \App\Models\Contact(
                    (int)$args['id'],
                    $userId,
                    $args['name'],
                    $args['phoneNumber'],
                    $args['email'] ?? null,
                    $args['notes'] ?? null,
                    $contact->getCreatedAt()
                );

                // Save the contact
                $contact = $contactRepository->update($updatedContact);

                // Convert Contact object to array
                $result = [
                    'id' => $contact->getId(),
                    'name' => $contact->getName(),
                    'phoneNumber' => $contact->getPhoneNumber(),
                    'email' => $contact->getEmail(),
                    'notes' => $contact->getNotes(),
                    'createdAt' => $contact->getCreatedAt(),
                    'updatedAt' => $contact->getUpdatedAt()
                ];
                return $result;
            } catch (\Exception $e) {
                error_log('Error in updateContact resolver: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
                throw $e;
            }
        },
        'deleteContact' => function ($rootValue, $args) use ($contactRepository) {
            error_log('Executing deleteContact resolver for ID: ' . $args['id']);
            try {
                // Get the current user ID from the session
                $userId = $_SESSION['user_id'] ?? null;
                if (!$userId) {
                    throw new \Exception("User not authenticated");
                }

                // Get the existing contact
                $contact = $contactRepository->findById((int)$args['id']);
                if (!$contact) {
                    return false;
                }

                // Check if the contact belongs to the current user
                if ($contact->getUserId() !== $userId) {
                    return false;
                }

                // Delete the contact
                return $contactRepository->delete($contact);
            } catch (\Exception $e) {
                error_log('Error in deleteContact resolver: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
                return false;
            }
        },
        'sendSmsToSegment' => function ($rootValue, $args) use ($smsService, $customSegmentRepository) {
            $segmentId = $args['segmentId'];
            $message = $args['message'];
            $userId = isset($args['userId']) ? (int)$args['userId'] : null;

            try {
                // Check if the segment exists
                $segment = $customSegmentRepository->findById((int)$segmentId);
                if (!$segment) {
                    return [
                        'status' => 'error',
                        'message' => 'Segment non trouvé',
                        'segment' => null,
                        'summary' => [
                            'total' => 0,
                            'successful' => 0,
                            'failed' => 0
                        ],
                        'results' => []
                    ];
                }

                // Send the SMS
                $results = $smsService->sendSMSToSegment((int)$segmentId, $message, $userId);

                // Count successful and failed sends
                $successful = 0;
                $failed = 0;
                $formattedResults = [];

                foreach ($results as $number => $result) {
                    if ($result['status'] === 'success') {
                        $successful++;
                    } else {
                        $failed++;
                    }

                    $formattedResults[] = [
                        'phoneNumber' => $number,
                        'status' => $result['status'] === 'success' ? 'success' : 'error',
                        'message' => $result['status'] === 'success' ? 'SMS envoyé avec succès' : $result['message']
                    ];
                }

                return [
                    'status' => 'success',
                    'message' => 'Envoi au segment terminé',
                    'segment' => [
                        'id' => $segment->getId(),
                        'name' => $segment->getName()
                    ],
                    'summary' => [
                        'total' => count($results),
                        'successful' => $successful,
                        'failed' => $failed
                    ],
                    'results' => $formattedResults
                ];
            } catch (\Exception $e) {
                error_log('Error in sendSmsToSegment: ' . $e->getMessage());
                return [
                    'status' => 'error',
                    'message' => 'Erreur lors de l\'envoi au segment: ' . $e->getMessage(),
                    'segment' => null,
                    'summary' => [
                        'total' => 0,
                        'successful' => 0,
                        'failed' => 0
                    ],
                    'results' => []
                ];
            }
        }
    ];

    // Execute the query
    $result = GraphQL::executeQuery(
        $schema,
        $input['query'] ?? '',
        $rootValue,
        null,
        $input['variables'] ?? []
    );

    // Return the result
    header('Content-Type: application/json');
    echo json_encode($result->toArray());
} catch (Exception $e) {
    // Handle exceptions
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
}