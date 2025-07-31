<?php
/**
 * Endpoint pour l'envoi de messages WhatsApp utilisant des templates - Version 2
 * 
 * Cette version offre plus de flexibilité et de robustesse dans la configuration
 * des templates WhatsApp, notamment pour l'utilisation des médias et des composants.
 * 
 * @package Oracle
 * @subpackage WhatsApp
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use App\Services\WhatsApp\WhatsAppService;
use App\Utils\CorsHelper;
use App\Utils\JsonResponse;

// Activer CORS pour permettre les requêtes depuis le frontend
CorsHelper::enableCors();

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    JsonResponse::error("Méthode non autorisée", 405);
    exit;
}

// Récupérer les données JSON du corps de la requête
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// Vérifier que les données sont valides
if (!$input || !isset($input['templateName']) || !isset($input['recipientPhoneNumber']) || !isset($input['templateLanguage'])) {
    JsonResponse::error("Données invalides ou incomplètes", 400);
    exit;
}

try {
    // Définir un fichier de log dédié
    $logFile = __DIR__ . '/../../../logs/whatsapp-rest-debug.log';
    
    // Fonction d'aide pour le logging
    $logMessage = function($message) use ($logFile) {
        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = "[$timestamp] $message" . PHP_EOL;
        file_put_contents($logFile, $formattedMessage, FILE_APPEND);
    };
    
    $logMessage("Démarrage de l'endpoint send-template-v2.php");
    $logMessage("Input reçu: " . json_encode($input, JSON_UNESCAPED_UNICODE));
    
    // Récupérer le service WhatsApp via le conteneur DI
    $logMessage("Chargement du bootstrap-rest.php - version simplifiée");
    $container = require __DIR__ . '/../../../src/bootstrap-rest.php';
    
    $logMessage("Récupération du service WhatsApp");
    $whatsappService = $container->get(WhatsAppService::class);
    
    // Journal pour le debugging
    $logMessage("Préparation de l'envoi du template WhatsApp: {$input['templateName']} à {$input['recipientPhoneNumber']}");
    
    // Récupérer l'utilisateur actuel - pour cet endpoint REST, utiliser un utilisateur système
    $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
    $userRepo = $entityManager->getRepository(\App\Entities\User::class);
    $systemUser = $userRepo->findOneBy(['username' => 'system']) ?: $userRepo->findOneBy([], ['id' => 'ASC']);
    
    if (!$systemUser) {
        JsonResponse::error("Utilisateur système non trouvé", 500);
        exit;
    }
    
    // Préparation des composants si fournis en JSON
    $components = [];
    if (isset($input['templateComponentsJsonString']) && !empty($input['templateComponentsJsonString'])) {
        $components = json_decode($input['templateComponentsJsonString'], true) ?: [];
    }
    
    // Préparation des paramètres du corps
    $bodyVariables = isset($input['bodyVariables']) ? $input['bodyVariables'] : [];
    
    // Préparation du média d'en-tête
    $headerMediaUrl = isset($input['headerMediaUrl']) ? $input['headerMediaUrl'] : null;
    $headerMediaId = isset($input['headerMediaId']) ? $input['headerMediaId'] : null;
    
    // Log détaillé pour le debugging
    error_log("[API REST] Paramètres: " . json_encode([
        'components' => $components,
        'bodyVariables' => $bodyVariables,
        'headerMediaUrl' => $headerMediaUrl,
        'headerMediaId' => $headerMediaId
    ], JSON_UNESCAPED_UNICODE));
    
    // Envoyer le message via le service avec composants - Version 2
    $result = null;
    if ($headerMediaId) {
        // Avec Media ID
        error_log("[API REST] Utilisation du Media ID: $headerMediaId");
        $result = $whatsappService->sendTemplateMessageWithComponents(
            $systemUser,
            $input['recipientPhoneNumber'],
            $input['templateName'],
            $input['templateLanguage'],
            $components,
            $headerMediaId
        );
    } else {
        // Avec URL d'image ou sans média
        error_log("[API REST] Utilisation de l'URL média ou sans média");
        $result = $whatsappService->sendTemplateMessage(
            $systemUser,
            $input['recipientPhoneNumber'],
            $input['templateName'],
            $input['templateLanguage'],
            $headerMediaUrl,
            $bodyVariables
        );
    }
    
    // Si le résultat est un objet WhatsAppMessageHistory, le convertir en tableau
    if ($result instanceof \App\Entities\WhatsApp\WhatsAppMessageHistory) {
        $result = [
            'wabaMessageId' => $result->getWabaMessageId(),
            'status' => $result->getStatus()
        ];
    }
    
    // Renvoyer une réponse de succès
    JsonResponse::success([
        'success' => true,
        'messageId' => $result['wabaMessageId'] ?? ($result['messages'][0]['id'] ?? null),
        'timestamp' => date('c')
    ]);
} catch (\Exception $e) {
    $logMessage("EXCEPTION lors de l'envoi du template: " . $e->getMessage());
    $logMessage("Trace: " . $e->getTraceAsString());
    JsonResponse::error("Erreur lors de l'envoi du message: " . $e->getMessage(), 500);
} catch (\Error $e) {
    $logMessage("ERREUR PHP: " . $e->getMessage());
    $logMessage("Trace: " . $e->getTraceAsString());
    JsonResponse::error("Erreur PHP: " . $e->getMessage(), 500);
} catch (\Throwable $e) {
    $logMessage("THROWABLE: " . $e->getMessage());
    $logMessage("Trace: " . $e->getTraceAsString());
    JsonResponse::error("Exception non gérée: " . $e->getMessage(), 500);
}