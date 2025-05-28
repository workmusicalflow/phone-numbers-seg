<?php
/**
 * OBSOLÈTE - NE PAS UTILISER
 * 
 * Cet endpoint est conservé pour référence historique uniquement.
 * Utiliser send-template-v2.php à la place.
 * 
 * @deprecated Depuis mai 2025
 * @see send-template-v2.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Services\WhatsApp\WhatsAppService;
use App\Services\WhatsApp\WhatsAppTemplateService;
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
if (!$input || !isset($input['to']) || !isset($input['template']) || !isset($input['template']['name'])) {
    JsonResponse::error("Données invalides", 400);
    exit;
}

try {
    // Récupérer les services via le conteneur DI
    $container = require __DIR__ . '/../../src/bootstrap.php';
    $whatsappService = $container->get(WhatsAppService::class);
    
    // Vérifier et formater le numéro de téléphone
    $phoneNumber = $input['to'];
    if (!preg_match('/^\+[0-9]{10,15}$/', $phoneNumber)) {
        // Tenter de formater le numéro
        if (strpos($phoneNumber, '+') !== 0) {
            $phoneNumber = '+' . preg_replace('/[^0-9]/', '', $phoneNumber);
        }
        
        // Vérifier à nouveau
        if (!preg_match('/^\+[0-9]{10,15}$/', $phoneNumber)) {
            JsonResponse::error("Numéro de téléphone invalide: $phoneNumber", 400);
            exit;
        }
    }
    
    // Préparer les données pour l'API WhatsApp
    $templateName = $input['template']['name'];
    $languageCode = $input['template']['language']['code'] ?? 'fr';
    $components = $input['template']['components'] ?? [];
    
    // Log pour le débogage
    error_log("Envoi de template WhatsApp: $templateName à $phoneNumber");
    error_log("Composants: " . json_encode($components));
    
    // Envoyer le message via le service WhatsApp
    $result = $whatsappService->sendTemplateMessage(
        $phoneNumber,
        $templateName,
        $languageCode,
        $components
    );
    
    // Traiter la réponse
    if ($result && isset($result['messages']) && !empty($result['messages'])) {
        // Préparer les données de succès
        $messageId = $result['messages'][0]['id'] ?? null;
        
        // Enregistrer l'historique d'utilisation
        $whatsappTemplateService = $container->get(WhatsAppTemplateService::class);
        $whatsappTemplateService->recordTemplateUsage(
            $templateName,
            $phoneNumber,
            [
                'messageId' => $messageId,
                'components' => $components
            ]
        );
        
        // Renvoyer une réponse de succès
        JsonResponse::success([
            'messageId' => $messageId,
            'status' => 'sent',
            'timestamp' => date('c')
        ]);
    } else {
        // Erreur lors de l'envoi
        $error = isset($result['error']) ? $result['error']['message'] : 'Échec de l\'envoi du message';
        JsonResponse::error($error, 500);
    }
} catch (Exception $e) {
    error_log("Exception lors de l'envoi du template WhatsApp: " . $e->getMessage());
    JsonResponse::error("Erreur lors de l'envoi du message: " . $e->getMessage(), 500);
}