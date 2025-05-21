<?php

// CORS Headers
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Max-Age: 3600");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit();
}

// Set content type to JSON and disable caching
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Require autoloader from project root
require_once dirname(dirname(dirname(dirname(__DIR__)))) . '/vendor/autoload.php';

// Get the container
$container = new \App\GraphQL\DIContainer();

try {
    // Get controllers from container
    $whatsAppController = $container->get(\App\Controllers\WhatsAppController::class);
    
    // Récupérer l'utilisateur actuel
    function localGetCurrentUser()
    {
        // Démarrer la session si elle n'est pas déjà démarrée
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Vérifier si l'utilisateur est dans la session
        if (isset($_SESSION['user_id'])) {
            $container = new \App\GraphQL\DIContainer();
            $userRepository = $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class);
            return $userRepository->findOneById($_SESSION['user_id']);
        }
        
        // Essayer avec la méthode des tokens
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        // Vérifier si le header est au format "Bearer <token>"
        if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            $token = $matches[1];
            
            $container = new \App\GraphQL\DIContainer();
            $authService = $container->get(\App\Services\Interfaces\Auth\AuthServiceInterface::class);
            
            // Vérifier le token et obtenir l'utilisateur correspondant
            return $authService->getUserFromToken($token);
        }
        
        // Sinon, utiliser un utilisateur administrateur par défaut pour le développement/test
        // NE JAMAIS FAIRE ÇA EN PRODUCTION
        $container = new \App\GraphQL\DIContainer();
        $userRepository = $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class);
        return $userRepository->findOneByUsername('admin');
    }
    
    $user = localGetCurrentUser();
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Utilisateur non authentifié']);
        exit;
    }
    
    // Ajouter des logs pour le débogage
    error_log('DEBUG - Approved Templates API - User: ' . $user->getUsername());
    error_log('DEBUG - Approved Templates API - Params: ' . json_encode($_GET));
    
    // Traiter la requête GET pour les templates approuvés
    $result = $whatsAppController->getApprovedTemplates($user, $_GET);
    
    // Ajouter des logs pour le débogage du résultat
    error_log('DEBUG - Approved Templates API - Result stats: ' . 
        (isset($result['templates']) ? count($result['templates']) : 0) . ' templates, ' .
        'Status: ' . ($result['status'] ?? 'unknown') . ', ' .
        'Source: ' . ($result['meta']['source'] ?? 'unknown')
    );
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}