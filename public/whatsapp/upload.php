<?php

// CORS Headers
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Max-Age: 3600");


declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use DI\Container;
use App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface;
use App\GraphQL\Context\GraphQLContextFactory;

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Charger le conteneur DI
$container = require __DIR__ . '/../../src/config/di.php';

// CORS Headers
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Gérer les requêtes OPTIONS pour CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Seules les requêtes POST sont acceptées
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit();
}

try {
    // Démarrer la session pour récupérer l'utilisateur authentifié
    session_start();

    // Vérifier l'authentification
    $contextFactory = $container->get(GraphQLContextFactory::class);
    $context = $contextFactory->createContext();
    $user = $context->getCurrentUser();

    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Utilisateur non authentifié']);
        exit();
    }

    // Vérifier qu'un fichier a été uploadé
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'Aucun fichier uploadé ou erreur lors de l\'upload']);
        exit();
    }

    $uploadedFile = $_FILES['file'];
    $filePath = $uploadedFile['tmp_name'];
    $mimeType = $uploadedFile['type'];
    $fileName = $uploadedFile['name'];

    // Vérifier le type MIME
    $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'video/mp4',
        'video/3gp',
        'audio/aac',
        'audio/mp4',
        'audio/mpeg',
        'audio/amr',
        'audio/ogg',
        'application/pdf',
        'application/vnd.ms-powerpoint',
        'application/msword',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain'
    ];

    if (!in_array($mimeType, $allowedMimeTypes)) {
        http_response_code(400);
        echo json_encode(['error' => 'Type de fichier non supporté']);
        exit();
    }

    // Vérifier les limites de taille
    $maxSizes = [
        'image' => 5 * 1024 * 1024, // 5MB
        'video' => 16 * 1024 * 1024, // 16MB
        'audio' => 16 * 1024 * 1024, // 16MB
        'document' => 100 * 1024 * 1024, // 100MB
        'sticker' => 500 * 1024 // 500KB
    ];

    $fileSize = filesize($filePath);
    $mediaType = explode('/', $mimeType)[0];
    
    if ($mimeType === 'image/webp') {
        $mediaType = 'sticker';
    } elseif (strpos($mimeType, 'application/') === 0 || $mimeType === 'text/plain') {
        $mediaType = 'document';
    }

    if (isset($maxSizes[$mediaType]) && $fileSize > $maxSizes[$mediaType]) {
        http_response_code(400);
        echo json_encode(['error' => 'Fichier trop volumineux pour ce type de média']);
        exit();
    }

    // Utiliser le service WhatsApp pour uploader le fichier
    $whatsappService = $container->get(WhatsAppServiceInterface::class);
    $mediaId = $whatsappService->uploadMedia($user, $filePath, $mimeType);

    // Retourner la réponse JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'mediaId' => $mediaId,
        'fileName' => $fileName,
        'mimeType' => $mimeType,
        'mediaType' => $mediaType
    ]);

} catch (\Exception $e) {
    error_log("Erreur upload WhatsApp: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}