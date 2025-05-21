<?php

declare(strict_types=1);

namespace App\GraphQL\Resolvers\WhatsApp;

use App\Entities\WhatsApp\WhatsAppTemplateHistory;
use App\GraphQL\Context\GraphQLContext;
use App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateHistoryRepositoryInterface;
use App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateRepositoryInterface;
use App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface;
use Psr\Log\LoggerInterface;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Mutation;

/**
 * Resolver GraphQL pour l'historique des templates WhatsApp
 */
class WhatsAppTemplateHistoryResolver
{
    public function __construct(
        private WhatsAppTemplateHistoryRepositoryInterface $templateHistoryRepository,
        private WhatsAppTemplateRepositoryInterface $templateRepository,
        private WhatsAppServiceInterface $whatsappService,
        private LoggerInterface $logger
    ) {}

    /**
     * Récupère l'historique des templates WhatsApp utilisés
     * 
     * @Query
     * @Logged
     * @return array{items: WhatsAppTemplateHistory[], totalCount: int, hasMore: bool}
     */
    public function getWhatsAppTemplateUsageHistory(
        ?int $limit = 100,
        ?int $offset = 0,
        ?string $templateId = null,
        ?string $phoneNumber = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?GraphQLContext $context = null
    ): array {
        $user = $context->getCurrentUser();
        if (!$user) {
            throw new \Exception("L'utilisateur doit être authentifié.");
        }

        $criteria = [];
        
        if ($templateId) {
            $criteria['templateId'] = $templateId;
        }
        
        if ($phoneNumber) {
            $criteria['phoneNumber'] = $phoneNumber;
        }

        // Convertir les dates en objets DateTime
        $dateFilters = [];
        if ($startDate) {
            $startDateTime = new \DateTime($startDate);
            $criteria['usedAt'] = ['>=', $startDateTime];
        }
        
        if ($endDate) {
            $endDateTime = new \DateTime($endDate);
            $endDateTime->setTime(23, 59, 59);
            
            if (isset($criteria['usedAt'])) {
                // On a déjà un filtre de date de début
                $criteria['usedAt'] = ['between', $criteria['usedAt'][1], $endDateTime];
            } else {
                $criteria['usedAt'] = ['<=', $endDateTime];
            }
        }

        // Récupérer les données
        $history = $this->templateHistoryRepository->findByUserWithFilters(
            $user,
            $criteria,
            ['usedAt' => 'DESC'],
            $limit,
            $offset
        );
        
        // Compter le nombre total d'entrées
        $totalCount = $this->templateHistoryRepository->countByUser($user);
        
        return [
            'items' => $history,
            'totalCount' => $totalCount,
            'hasMore' => ($offset + $limit) < $totalCount
        ];
    }

    /**
     * Récupère l'historique pour un template spécifique
     * 
     * @Query
     * @Logged
     */
    public function getTemplateHistoryByTemplateId(
        string $templateId,
        ?int $limit = 10,
        ?GraphQLContext $context = null
    ): array {
        $user = $context->getCurrentUser();
        if (!$user) {
            throw new \Exception("L'utilisateur doit être authentifié.");
        }
        
        return $this->templateHistoryRepository->findByTemplateId($templateId, $user, $limit);
    }

    /**
     * Récupère les templates les plus utilisés
     * 
     * @Query
     * @Logged
     */
    public function getMostUsedTemplateIds(
        ?int $limit = 5,
        ?GraphQLContext $context = null
    ): array {
        $user = $context->getCurrentUser();
        if (!$user) {
            throw new \Exception("L'utilisateur doit être authentifié.");
        }
        
        return $this->templateHistoryRepository->getMostUsedTemplates($user, $limit);
    }

    /**
     * Récupère les valeurs de paramètres communes pour un template
     * 
     * @Query
     * @Logged
     */
    public function getCommonParameterValues(
        string $templateId,
        ?GraphQLContext $context = null
    ): array {
        $user = $context->getCurrentUser();
        if (!$user) {
            throw new \Exception("L'utilisateur doit être authentifié.");
        }
        
        return $this->templateHistoryRepository->getCommonParameterValues($templateId, $user);
    }

    /**
     * Enregistre l'utilisation d'un template
     * 
     * @Mutation
     * @Logged
     */
    public function recordTemplateUsage(
        string $templateId,
        string $templateName,
        string $language,
        string $category,
        string $phoneNumber,
        ?array $parameters = null,
        ?string $headerMediaType = null,
        ?string $headerMediaUrl = null,
        ?string $headerMediaId = null,
        ?array $buttonValues = null,
        ?string $wabaMessageId = null,
        ?GraphQLContext $context = null
    ): WhatsAppTemplateHistory {
        $user = $context->getCurrentUser();
        if (!$user) {
            throw new \Exception("L'utilisateur doit être authentifié.");
        }
        
        try {
            // Créer une nouvelle entrée d'historique
            $history = new WhatsAppTemplateHistory();
            $history->setOracleUser($user)
                ->setTemplateId($templateId)
                ->setTemplateName($templateName)
                ->setLanguage($language)
                ->setCategory($category)
                ->setPhoneNumber($phoneNumber)
                ->setParameters($parameters)
                ->setHeaderMediaType($headerMediaType)
                ->setHeaderMediaUrl($headerMediaUrl)
                ->setHeaderMediaId($headerMediaId)
                ->setButtonValues($buttonValues)
                ->setWabaMessageId($wabaMessageId)
                ->setStatus('sent')
                ->setUsedAt(new \DateTime());
            
            // Rechercher le template associé
            $template = $this->templateRepository->findOneBy(['template_id' => $templateId]);
            if ($template) {
                $history->setTemplate($template);
            }
            
            // Sauvegarder l'entrée
            $this->templateHistoryRepository->save($history);
            
            return $history;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'enregistrement de l\'utilisation du template', [
                'error' => $e->getMessage(),
                'templateId' => $templateId
            ]);
            
            throw new \Exception('Erreur lors de l\'enregistrement de l\'utilisation du template: ' . $e->getMessage());
        }
    }
}