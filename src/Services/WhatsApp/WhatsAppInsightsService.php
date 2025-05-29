<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Entities\Contact;
use App\Entities\User;
use App\Repositories\Doctrine\WhatsApp\WhatsAppMessageHistoryRepository;
use App\Services\Interfaces\AuthServiceInterface;
use App\Repositories\Interfaces\ContactRepositoryInterface;

/**
 * Service dédié aux insights WhatsApp
 * 
 * Responsabilité unique : Gestion des insights WhatsApp pour les contacts
 * Respecte les principes SOLID et Clean Architecture
 */
class WhatsAppInsightsService
{
    public function __construct(
        private WhatsAppMessageHistoryRepository $whatsAppRepository,
        private ContactRepositoryInterface $contactRepository,
        private AuthServiceInterface $authService
    ) {}

    /**
     * Obtenir les insights WhatsApp pour un contact spécifique
     * 
     * @param string $contactId
     * @return array|null
     * @throws \Exception
     */
    public function getContactInsights(string $contactId): ?array
    {
        // 1. Authentification
        $user = $this->authService->getCurrentUser();
        if (!$user) {
            throw new \Exception('Utilisateur non authentifié');
        }

        // 2. Récupération et validation du contact
        $contact = $this->contactRepository->findById($contactId);
        if (!$contact) {
            throw new \Exception('Contact non trouvé');
        }

        // 3. Vérification des autorisations
        if ($contact->getUserId() !== $user->getId()) {
            throw new \Exception('Accès non autorisé à ce contact');
        }

        // 4. Récupération des insights
        $insights = $this->whatsAppRepository->getContactInsights($contact, $user);
        
        // 5. Transformation des données pour le frontend
        return $this->transformInsightsForFrontend($insights);
    }

    /**
     * Obtenir un résumé des insights pour plusieurs contacts
     * 
     * @param array $contactIds
     * @return array
     */
    public function getContactsSummary(array $contactIds): array
    {
        $user = $this->authService->getCurrentUser();
        if (!$user) {
            return [];
        }

        $contacts = [];
        foreach ($contactIds as $contactId) {
            $contact = $this->contactRepository->findById($contactId);
            if ($contact && $contact->getUserId() === $user->getId()) {
                $contacts[] = $contact;
            }
        }

        if (empty($contacts)) {
            return [];
        }

        $summary = $this->whatsAppRepository->getContactsInsightsSummary($contacts, $user);
        
        return $this->transformSummaryForFrontend($summary, $contacts);
    }

    /**
     * Transformer les insights pour le frontend
     * 
     * @param array $insights
     * @return array
     */
    private function transformInsightsForFrontend(array $insights): array
    {
        return [
            'totalMessages' => (int) $insights['totalMessages'],
            'outgoingMessages' => (int) $insights['outgoingMessages'],
            'incomingMessages' => (int) $insights['incomingMessages'],
            'deliveredMessages' => (int) $insights['deliveredMessages'],
            'readMessages' => (int) $insights['readMessages'],
            'failedMessages' => (int) $insights['failedMessages'],
            'lastMessageDate' => $insights['lastMessageDate'],
            'lastMessageType' => $insights['lastMessageType'],
            'lastMessageContent' => $insights['lastMessageContent'],
            'templatesUsed' => $insights['templatesUsed'] ?? [],
            'conversationCount' => (int) $insights['conversationCount'],
            'messagesByType' => $this->normalizeMessagesByType($insights['messagesByType'] ?? []),
            'messagesByStatus' => $this->normalizeMessagesByStatus($insights['messagesByStatus'] ?? []),
            'messagesByMonth' => $insights['messagesByMonth'] ?? [],
            'deliveryRate' => (float) $insights['deliveryRate'],
            'readRate' => (float) $insights['readRate']
        ];
    }

    /**
     * Transformer le résumé pour le frontend
     * 
     * @param array $summary
     * @param array $contacts
     * @return array
     */
    private function transformSummaryForFrontend(array $summary, array $contacts): array
    {
        $result = [];
        foreach ($contacts as $contact) {
            $phoneNumber = $contact->getPhoneNumber();
            $result[] = [
                'contactId' => $contact->getId(),
                'phoneNumber' => $phoneNumber,
                'totalMessages' => $summary[$phoneNumber]['totalMessages'] ?? 0,
                'lastMessageDate' => $summary[$phoneNumber]['lastMessageDate'] ?? null
            ];
        }
        return $result;
    }

    /**
     * Normaliser les messages par type pour garantir la cohérence
     * 
     * @param array $messagesByType
     * @return array
     */
    private function normalizeMessagesByType(array $messagesByType): array
    {
        $normalized = [
            'text' => 0,
            'image' => 0,
            'document' => 0,
            'video' => 0,
            'audio' => 0,
            'template' => 0
        ];

        foreach ($messagesByType as $type => $count) {
            if (isset($normalized[$type])) {
                $normalized[$type] = (int) $count;
            }
        }

        return $normalized;
    }

    /**
     * Normaliser les messages par statut pour garantir la cohérence
     * 
     * @param array $messagesByStatus
     * @return array
     */
    private function normalizeMessagesByStatus(array $messagesByStatus): array
    {
        $normalized = [
            'sent' => 0,
            'delivered' => 0,
            'read' => 0,
            'failed' => 0
        ];

        foreach ($messagesByStatus as $status => $count) {
            if (isset($normalized[$status])) {
                $normalized[$status] = (int) $count;
            }
        }

        return $normalized;
    }
}