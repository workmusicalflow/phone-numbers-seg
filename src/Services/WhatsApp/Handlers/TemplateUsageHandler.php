<?php

namespace App\Services\WhatsApp\Handlers;

use App\Entities\User;
use App\Entities\WhatsApp\WhatsAppMessageHistory;
use App\Entities\WhatsApp\WhatsAppTemplateHistory;
use App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateHistoryRepositoryInterface;
use App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateRepositoryInterface;
use DateTime;
use Psr\Log\LoggerInterface;

/**
 * Gestionnaire pour l'enregistrement de l'utilisation des templates
 * 
 * Cette classe encapsule la logique d'enregistrement de l'utilisation
 * des templates WhatsApp dans l'historique
 */
class TemplateUsageHandler
{
    private WhatsAppTemplateRepositoryInterface $templateRepository;
    private ?WhatsAppTemplateHistoryRepositoryInterface $templateHistoryRepository;
    private LoggerInterface $logger;

    public function __construct(
        WhatsAppTemplateRepositoryInterface $templateRepository,
        ?WhatsAppTemplateHistoryRepositoryInterface $templateHistoryRepository,
        LoggerInterface $logger
    ) {
        $this->templateRepository = $templateRepository;
        $this->templateHistoryRepository = $templateHistoryRepository;
        $this->logger = $logger;
    }

    /**
     * Enregistre l'utilisation d'un template
     */
    public function recordUsage(
        User $user,
        string $templateName,
        string $recipient,
        string $languageCode,
        array $bodyParams = [],
        ?string $headerImageUrl = null,
        WhatsAppMessageHistory $messageHistory = null
    ): void {
        // Si pas de repository d'historique, on ne fait rien
        if ($this->templateHistoryRepository === null) {
            return;
        }

        try {
            // Récupérer la catégorie du template
            $category = $this->getTemplateCategory($templateName);

            // Créer l'enregistrement d'historique
            $templateHistory = $this->createTemplateHistory(
                $user,
                $templateName,
                $recipient,
                $languageCode,
                $category,
                $bodyParams,
                $headerImageUrl,
                $messageHistory
            );

            // Sauvegarder
            $this->templateHistoryRepository->save($templateHistory);

        } catch (\Exception $e) {
            // Logger l'erreur mais ne pas faire échouer l'envoi du message
            $this->logger->warning('Impossible d\'enregistrer l\'utilisation du template', [
                'template' => $templateName,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Récupère la catégorie d'un template
     */
    private function getTemplateCategory(string $templateName): string
    {
        try {
            $templates = $this->templateRepository->findByAdvancedCriteria(
                ['name' => $templateName],
                [],
                1
            );

            if (!empty($templates)) {
                return $templates[0]->getCategory() ?? 'UTILITY';
            }
        } catch (\Exception $e) {
            $this->logger->warning('Impossible de récupérer la catégorie du template', [
                'template' => $templateName,
                'error' => $e->getMessage()
            ]);
        }

        return 'UTILITY'; // Valeur par défaut
    }

    /**
     * Crée un objet WhatsAppTemplateHistory
     */
    private function createTemplateHistory(
        User $user,
        string $templateName,
        string $recipient,
        string $languageCode,
        string $category,
        array $bodyParams,
        ?string $headerImageUrl,
        ?WhatsAppMessageHistory $messageHistory
    ): WhatsAppTemplateHistory {
        $templateHistory = new WhatsAppTemplateHistory();
        
        $templateHistory->setOracleUser($user);
        $templateHistory->setTemplateName($templateName);
        $templateHistory->setTemplateId($templateName); // Utiliser le nom comme ID
        $templateHistory->setTemplateLanguage($languageCode);
        $templateHistory->setTemplateCategory($category);
        $templateHistory->setRecipientPhoneNumber($recipient);
        $templateHistory->setSentAt(new DateTime());
        
        // Ajouter les paramètres si présents
        if (!empty($bodyParams)) {
            $templateHistory->setBodyVariables($bodyParams);
        }
        
        // Ajouter l'URL de l'image si présente
        if ($headerImageUrl !== null) {
            $templateHistory->setHeaderMediaType('url');
            $templateHistory->setHeaderMediaUrl($headerImageUrl);
        }
        
        // Lier à l'historique du message si fourni
        if ($messageHistory !== null) {
            $templateHistory->setMessageHistory($messageHistory);
        }

        return $templateHistory;
    }
}