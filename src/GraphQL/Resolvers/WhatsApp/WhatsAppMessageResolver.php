<?php

namespace App\GraphQL\Resolvers\WhatsApp;

use App\Entities\WhatsApp\WhatsAppMessage;
use App\GraphQL\Context\GraphQLContext;
use App\GraphQL\Types\WhatsApp\WhatsAppMessageInputType;
use App\GraphQL\Types\WhatsApp\WhatsAppTemplateSendInput;
use App\Services\Interfaces\WhatsApp\WhatsAppApiClientInterface;
use App\Services\Interfaces\WhatsApp\WhatsAppMessageServiceInterface;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Security;
use TheCodingMachine\GraphQLite\Annotations\Logged;

/**
 * Resolver GraphQL pour les messages WhatsApp
 */
class WhatsAppMessageResolver
{
    /**
     * @var WhatsAppMessageServiceInterface
     */
    private WhatsAppMessageServiceInterface $messageService;

    /**
     * @var WhatsAppApiClientInterface
     */
    private WhatsAppApiClientInterface $apiClient;

    /**
     * Constructeur
     *
     * @param WhatsAppMessageServiceInterface $messageService
     * @param WhatsAppApiClientInterface $apiClient
     */
    public function __construct(
        WhatsAppMessageServiceInterface $messageService,
        WhatsAppApiClientInterface $apiClient
    ) {
        $this->messageService = $messageService;
        $this->apiClient = $apiClient;
    }

    /**
     * Récupère les messages WhatsApp par expéditeur
     *
     * @Query
     * @Logged
     * @Security("is_granted('ROLE_USER')")
     * @param string $sender
     * @param int $limit
     * @param int $offset
     * @return WhatsAppMessage[]
     */
    public function getWhatsAppMessagesBySender(string $sender, int $limit = 50, int $offset = 0): array
    {
        return $this->messageService->getMessagesBySender($sender, $limit, $offset);
    }

    /**
     * Récupère les messages WhatsApp par destinataire
     *
     * @Query
     * @Logged
     * @Security("is_granted('ROLE_USER')")
     * @param string $recipient
     * @param int $limit
     * @param int $offset
     * @return WhatsAppMessage[]
     */
    public function getWhatsAppMessagesByRecipient(string $recipient, int $limit = 50, int $offset = 0): array
    {
        return $this->messageService->getMessagesByRecipient($recipient, $limit, $offset);
    }

    /**
     * Récupère un message WhatsApp par son identifiant
     *
     * @Query
     * @Logged
     * @Security("is_granted('ROLE_USER')")
     * @param string $messageId
     * @return WhatsAppMessage|null
     */
    public function getWhatsAppMessageById(string $messageId): ?WhatsAppMessage
    {
        return $this->messageService->getMessageById($messageId);
    }

    /**
     * Récupère les messages WhatsApp par type
     *
     * @Query
     * @Logged
     * @Security("is_granted('ROLE_USER')")
     * @param string $type
     * @param int $limit
     * @param int $offset
     * @return WhatsAppMessage[]
     */
    public function getWhatsAppMessagesByType(string $type, int $limit = 50, int $offset = 0): array
    {
        return $this->messageService->getMessagesByType($type, $limit, $offset);
    }

    /**
     * Envoie un message texte WhatsApp
     *
     * @Mutation
     * @Logged
     * @Security("is_granted('ROLE_USER')")
     * @param string $recipient
     * @param string $message
     * @param GraphQLContext $context
     * @return array
     */
    public function sendWhatsAppTextMessage(string $recipient, string $message, GraphQLContext $context): array
    {
        // Vérifier si l'utilisateur a des crédits suffisants
        // À implémenter en fonction du système de crédits existant
        
        // Normaliser le numéro de téléphone si nécessaire
        $normalizedRecipient = $this->normalizePhoneNumber($recipient);
        
        // Appeler l'API pour envoyer le message
        $result = $this->apiClient->sendTextMessage($normalizedRecipient, $message);
        
        return [
            'success' => isset($result['messages']) && !empty($result['messages']),
            'messageId' => $result['messages'][0]['id'] ?? null,
            'error' => $result['error']['message'] ?? null
        ];
    }

    /**
     * Envoie un message template WhatsApp
     *
     * @Mutation
     * @Logged
     * @Security("is_granted('ROLE_USER')")
     * @param WhatsAppTemplateSendInput $input
     * @param GraphQLContext $context
     * @return array
     */
    public function sendWhatsAppTemplateMessage(WhatsAppTemplateSendInput $input, GraphQLContext $context): array
    {
        // Construire les paramètres du template
        $components = [];
        
        // Paramètre d'en-tête (image)
        if ($input->headerImageUrl) {
            $components[] = [
                'type' => 'header',
                'parameters' => [
                    [
                        'type' => 'image',
                        'image' => [
                            'link' => $input->headerImageUrl
                        ]
                    ]
                ]
            ];
        }
        
        // Paramètres de corps si fournis
        $bodyParams = [];
        if ($input->body1Param) {
            $bodyParams[] = [
                'type' => 'text',
                'text' => $input->body1Param
            ];
        }
        if ($input->body2Param) {
            $bodyParams[] = [
                'type' => 'text',
                'text' => $input->body2Param
            ];
        }
        if ($input->body3Param) {
            $bodyParams[] = [
                'type' => 'text',
                'text' => $input->body3Param
            ];
        }
        
        if (!empty($bodyParams)) {
            $components[] = [
                'type' => 'body',
                'parameters' => $bodyParams
            ];
        }
        
        // Normaliser le numéro de téléphone
        $normalizedRecipient = $this->normalizePhoneNumber($input->recipient);
        
        // Appeler l'API pour envoyer le template
        $result = $this->apiClient->sendTemplateMessage(
            $normalizedRecipient,
            $input->templateName,
            $input->languageCode,
            $components
        );
        
        return [
            'success' => isset($result['messages']) && !empty($result['messages']),
            'messageId' => $result['messages'][0]['id'] ?? null,
            'error' => $result['error']['message'] ?? null
        ];
    }

    /**
     * Normalise un numéro de téléphone
     *
     * @param string $phoneNumber
     * @return string
     */
    private function normalizePhoneNumber(string $phoneNumber): string
    {
        // Supprimer tous les caractères non numériques
        $number = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // S'assurer que le numéro commence par le code pays (225 pour la Côte d'Ivoire)
        if (substr($number, 0, 3) !== '225') {
            $number = '225' . $number;
        }
        
        return $number;
    }
}