<?php

declare(strict_types=1);

namespace App\GraphQL\Resolvers;

use App\GraphQL\Types\WhatsAppContactInsightsType;
use App\Repositories\Doctrine\WhatsApp\WhatsAppMessageHistoryRepository;
use App\Repositories\Interfaces\ContactRepositoryInterface;
use App\Services\Interfaces\AuthServiceInterface;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use Psr\Log\LoggerInterface;

/**
 * Resolver GraphQL pour les insights WhatsApp des contacts
 */
class WhatsAppContactInsightsResolver
{
    public function __construct(
        private WhatsAppMessageHistoryRepository $whatsAppRepository,
        private ContactRepositoryInterface $contactRepository,
        private AuthServiceInterface $authService,
        private LoggerInterface $logger
    ) {}

    /**
     * Obtenir les insights WhatsApp pour un contact spécifique
     */
    public function getContactWhatsAppInsights(string $contactId): ?WhatsAppContactInsightsType
    {
        try {
            error_log('[WhatsAppContactInsightsResolver] Début getContactWhatsAppInsights pour contact ID: ' . $contactId);
            
            // Authentification
            $user = $this->authService->getCurrentUser();
            if (!$user) {
                error_log('[WhatsAppContactInsightsResolver] Erreur: Utilisateur non authentifié');
                throw new \Exception('Utilisateur non authentifié');
            }
            error_log('[WhatsAppContactInsightsResolver] Utilisateur authentifié: ' . $user->getUsername() . ' (ID: ' . $user->getId() . ')');

            // Récupérer le contact
            $contact = $this->contactRepository->findById($contactId);
            if (!$contact) {
                error_log('[WhatsAppContactInsightsResolver] Erreur: Contact non trouvé pour ID: ' . $contactId);
                throw new \Exception('Contact non trouvé');
            }
            error_log('[WhatsAppContactInsightsResolver] Contact trouvé: ' . $contact->getName() . ' (Phone: ' . $contact->getPhoneNumber() . ')');

            // Vérifier que le contact appartient à l'utilisateur
            if ($contact->getUserId() !== $user->getId()) {
                error_log('[WhatsAppContactInsightsResolver] Erreur: Contact appartient à l\'utilisateur ' . $contact->getUserId() . ' mais utilisateur courant est ' . $user->getId());
                throw new \Exception('Accès non autorisé à ce contact');
            }
            error_log('[WhatsAppContactInsightsResolver] Autorisation OK');

            // Récupérer les insights WhatsApp
            error_log('[WhatsAppContactInsightsResolver] Appel getContactInsights...');
            $insights = $this->whatsAppRepository->getContactInsights($contact, $user);
            error_log('[WhatsAppContactInsightsResolver] Insights reçus: ' . json_encode($insights));

            // Créer et retourner le type GraphQL
            error_log('[WhatsAppContactInsightsResolver] Création du WhatsAppContactInsightsType...');
            $result = new WhatsAppContactInsightsType(
                totalMessages: $insights['totalMessages'],
                outgoingMessages: $insights['outgoingMessages'],
                incomingMessages: $insights['incomingMessages'],
                deliveredMessages: $insights['deliveredMessages'],
                readMessages: $insights['readMessages'],
                failedMessages: $insights['failedMessages'],
                lastMessageDate: $insights['lastMessageDate'],
                lastMessageType: $insights['lastMessageType'],
                lastMessageContent: $insights['lastMessageContent'],
                templatesUsed: $insights['templatesUsed'],
                conversationCount: $insights['conversationCount'],
                messagesByType: $insights['messagesByType'],
                messagesByStatus: $insights['messagesByStatus'],
                messagesByMonth: $insights['messagesByMonth'],
                deliveryRate: $insights['deliveryRate'],
                readRate: $insights['readRate']
            );
            
            error_log('[WhatsAppContactInsightsResolver] WhatsAppContactInsightsType créé avec succès');
            return $result;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des insights WhatsApp', [
                'contactId' => $contactId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Obtenir un résumé rapide des insights WhatsApp pour plusieurs contacts
     */
    #[Query]
    #[Logged]
    public function getContactsWhatsAppSummary(array $contactIds): array
    {
        try {
            // Authentification
            $user = $this->authService->getCurrentUser();
            if (!$user) {
                throw new \Exception('Utilisateur non authentifié');
            }

            // Récupérer les contacts
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

            // Récupérer le résumé des insights
            $summary = $this->whatsAppRepository->getContactsInsightsSummary($contacts, $user);

            // Reformater pour inclure les IDs des contacts
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

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération du résumé WhatsApp', [
                'contactIds' => $contactIds,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
}