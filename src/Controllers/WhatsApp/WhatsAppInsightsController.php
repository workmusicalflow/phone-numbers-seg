<?php

declare(strict_types=1);

namespace App\Controllers\WhatsApp;

use App\Services\WhatsApp\WhatsAppInsightsService;
use App\Utils\JsonResponse;
use Psr\Log\LoggerInterface;

/**
 * Controller REST pour les insights WhatsApp
 * 
 * Responsabilité unique : API REST pour les insights WhatsApp
 * Suit les principes RESTful et Clean Architecture
 */
class WhatsAppInsightsController
{
    public function __construct(
        private WhatsAppInsightsService $insightsService,
        private LoggerInterface $logger
    ) {}

    /**
     * GET /api/whatsapp/contacts/{contactId}/insights
     * 
     * @param string $contactId
     * @return array
     */
    public function getContactInsights(string $contactId): array
    {
        try {
            $this->logger->info('Récupération des insights WhatsApp', [
                'contactId' => $contactId,
                'method' => 'REST'
            ]);

            $insights = $this->insightsService->getContactInsights($contactId);
            
            if ($insights === null) {
                return JsonResponse::error('Aucun insight trouvé pour ce contact', 404);
            }

            return JsonResponse::success($insights, 'Insights récupérés avec succès');

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des insights WhatsApp via REST', [
                'contactId' => $contactId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return JsonResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * POST /api/whatsapp/contacts/insights/summary
     * Body: {"contactIds": ["1", "2", "3"]}
     * 
     * @param array $requestBody
     * @return array
     */
    public function getContactsSummary(array $requestBody): array
    {
        try {
            $contactIds = $requestBody['contactIds'] ?? [];
            
            if (empty($contactIds) || !is_array($contactIds)) {
                return JsonResponse::error('contactIds requis et doit être un tableau', 400);
            }

            $this->logger->info('Récupération du résumé des insights WhatsApp', [
                'contactIds' => $contactIds,
                'count' => count($contactIds),
                'method' => 'REST'
            ]);

            $summary = $this->insightsService->getContactsSummary($contactIds);

            return JsonResponse::success($summary, 'Résumé des insights récupéré avec succès');

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération du résumé des insights WhatsApp', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return JsonResponse::error($e->getMessage(), 500);
        }
    }
}