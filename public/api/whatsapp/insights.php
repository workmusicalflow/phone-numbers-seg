<?php

declare(strict_types=1);

/**
 * API REST pour les insights WhatsApp
 * 
 * Endpoints:
 * GET /api/whatsapp/insights/{contactId} - Insights pour un contact
 * POST /api/whatsapp/insights/summary - Résumé pour plusieurs contacts
 */

// CORS Headers
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

require_once __DIR__ . '/../../../src/bootstrap-doctrine-simple.php';

use App\Repositories\Doctrine\ContactRepository;
use App\Repositories\Doctrine\UserRepository;
use App\Repositories\Doctrine\WhatsApp\WhatsAppMessageHistoryRepository;

try {

    // Vérification de la session
    session_start();
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentification requise']);
        exit;
    }

    // Récupération de l'utilisateur depuis la session
    $userId = $_SESSION['user_id'];
    $userRepository = new UserRepository($entityManager, \App\Entities\User::class);
    $user = $userRepository->findById($userId);
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Utilisateur non trouvé']);
        exit;
    }

    // Initialisation directe des repositories
    $contactRepository = new ContactRepository($entityManager, \App\Entities\Contact::class);
    $whatsAppRepository = new WhatsAppMessageHistoryRepository($entityManager, \App\Entities\WhatsApp\WhatsAppMessageHistory::class);

    // Routing simplifié - traiter les paramètres GET pour contact ID
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET' && isset($_GET['contactId'])) {
        // GET /api/whatsapp/insights.php?contactId=9
        $contactId = $_GET['contactId'];
        
        // Récupération et validation du contact
        $contact = $contactRepository->findById($contactId);
        if (!$contact) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Contact non trouvé']);
            exit;
        }

        // Vérification des autorisations
        if ($contact->getUserId() !== $user->getId()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Accès non autorisé à ce contact']);
            exit;
        }

        // Récupération des insights
        $insights = $whatsAppRepository->getContactInsights($contact, $user);
        
        if ($insights === null) {
            echo json_encode(['success' => false, 'error' => 'Aucun insight trouvé pour ce contact']);
        } else {
            echo json_encode(['success' => true, 'data' => $insights]);
        }
    } 
    elseif ($method === 'POST') {
        // POST /api/whatsapp/insights.php avec body JSON
        $input = json_decode(file_get_contents('php://input'), true);
        if ($input === null || !isset($input['contactIds'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'JSON invalide ou contactIds manquant']);
            exit;
        }
        
        $contactIds = $input['contactIds'];
        $contacts = [];
        
        foreach ($contactIds as $contactId) {
            $contact = $contactRepository->findById($contactId);
            if ($contact && $contact->getUserId() === $user->getId()) {
                $contacts[] = $contact;
            }
        }

        if (empty($contacts)) {
            echo json_encode(['success' => true, 'data' => []]);
        } else {
            $summary = $whatsAppRepository->getContactsInsightsSummary($contacts, $user);
            echo json_encode(['success' => true, 'data' => $summary]);
        }
    }
    else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    }

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur interne du serveur',
        'message' => $e->getMessage()
    ]);
}
?>