<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

use App\Services\WhatsApp\Commands\BulkSendTemplateCommand;
use App\Utils\CorsHelper;

// Configuration CORS avec support des credentials
CorsHelper::enableCors('http://localhost:5173');

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Démarrer la session pour récupérer l'utilisateur authentifié
session_start();

// Initialisation de l'application
$container = require __DIR__ . '/../../../src/bootstrap-rest.php';

try {
    // Vérifier l'authentification via GraphQL Context
    $contextFactory = $container->get(\App\GraphQL\Context\GraphQLContextFactory::class);
    $context = $contextFactory->create();
    $user = $context->getCurrentUser();
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Non authentifié']);
        exit;
    }
    
    // Récupérer les données de la requête
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Données invalides']);
        exit;
    }
    
    // Valider les données requises
    if (empty($data['recipients']) || !is_array($data['recipients'])) {
        http_response_code(400);
        echo json_encode(['error' => 'La liste des destinataires est requise']);
        exit;
    }
    
    if (empty($data['templateName'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Le nom du template est requis']);
        exit;
    }
    
    // Créer la commande d'envoi en masse
    $command = new BulkSendTemplateCommand(
        $user,
        $data['recipients'],
        $data['templateName'],
        $data['templateLanguage'] ?? 'fr',
        $data['bodyVariables'] ?? [],
        $data['headerVariables'] ?? [],
        $data['headerMediaUrl'] ?? null,
        $data['headerMediaId'] ?? null,
        $data['defaultParameters'] ?? [],
        $data['recipientParameters'] ?? [],
        $data['options'] ?? []
    );
    
    // Vérifier que l'utilisateur a assez de crédits
    if (!$command->canExecute()) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Crédits insuffisants',
            'required' => count($data['recipients']),
            'available' => $user->getSmsCredit()
        ]);
        exit;
    }
    
    // Obtenir le CommandBus avec le BulkSendHandler
    $commandBus = $container->get('whatsapp.command_bus.bulk');
    
    // Exécuter la commande
    $result = $commandBus->handle($command);
    
    // Préparer la réponse
    $response = [
        'success' => $result->isSuccess(),
        'message' => $result->getMessage(),
        'data' => [
            'totalSent' => $result->getTotalSent(),
            'totalFailed' => $result->getTotalFailed(),
            'totalAttempted' => $result->getTotalAttempted(),
            'successRate' => $result->getSuccessRate(),
            'errorSummary' => $result->getErrorSummary()
        ]
    ];
    
    // Ajouter les détails des erreurs si demandé
    if (!empty($data['includeDetails']) && $result->getTotalFailed() > 0) {
        $response['data']['failedRecipients'] = $result->getFailedSends();
    }
    
    // Retourner la réponse
    http_response_code($result->isSuccess() ? 200 : 207); // 207 = Multi-Status
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (\Exception $e) {
    error_log('Erreur envoi en masse WhatsApp: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur serveur',
        'message' => $e->getMessage()
    ]);
}