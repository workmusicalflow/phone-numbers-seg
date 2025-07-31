<?php

namespace App\Services\WhatsApp;

use App\Entities\WhatsApp\WhatsAppMessage;
use App\Repositories\Interfaces\WhatsApp\WhatsAppMessageRepositoryInterface;
use App\Services\Interfaces\WhatsApp\WhatsAppApiClientInterface;
use App\Services\Interfaces\WhatsApp\WhatsAppMessageServiceInterface;
use Psr\Log\LoggerInterface;

/**
 * Service pour la gestion des messages WhatsApp
 */
class WhatsAppMessageService implements WhatsAppMessageServiceInterface
{
    /**
     * @var WhatsAppMessageRepositoryInterface
     */
    private WhatsAppMessageRepositoryInterface $messageRepository;

    /**
     * @var WhatsAppApiClientInterface
     */
    private WhatsAppApiClientInterface $apiClient;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Constructeur
     *
     * @param WhatsAppMessageRepositoryInterface $messageRepository
     * @param WhatsAppApiClientInterface $apiClient
     * @param LoggerInterface $logger
     */
    public function __construct(
        WhatsAppMessageRepositoryInterface $messageRepository,
        WhatsAppApiClientInterface $apiClient,
        LoggerInterface $logger
    ) {
        $this->messageRepository = $messageRepository;
        $this->apiClient = $apiClient;
        $this->logger = $logger;
    }

    /**
     * Traite un message WhatsApp entrant
     *
     * @param array $message Les données du message
     * @param array $metadata Les métadonnées associées
     * @return WhatsAppMessage
     */
    public function processIncomingMessage(array $message, array $metadata): WhatsAppMessage
    {
        $this->logger->info('Traitement d\'un message WhatsApp entrant', [
            'message_id' => $message['id'] ?? 'inconnu',
            'type' => $message['type'] ?? 'inconnu'
        ]);

        // Vérifier si le message existe déjà
        $existingMessage = $this->messageRepository->findByMessageId($message['id']);
        if ($existingMessage) {
            $this->logger->info('Message WhatsApp déjà traité, ignoré', [
                'message_id' => $message['id']
            ]);
            return $existingMessage;
        }

        // Créer une nouvelle entité WhatsAppMessage
        $whatsAppMessage = new WhatsAppMessage();
        $whatsAppMessage->setMessageId($message['id']);
        $whatsAppMessage->setSender($this->extractSender($message));
        $whatsAppMessage->setTimestamp($this->extractTimestamp($message));
        $whatsAppMessage->setType($message['type']);
        $whatsAppMessage->setRawData(json_encode($message));

        // Extraire le contenu selon le type de message
        $this->extractMessageContent($whatsAppMessage, $message);

        // Définir le destinataire si disponible
        if (isset($metadata['to'])) {
            $whatsAppMessage->setRecipient($metadata['to']);
        }

        // Marquer le message comme lu (optionnel)
        try {
            $this->apiClient->markMessageAsRead($message['id']);
        } catch (\Exception $e) {
            $this->logger->warning('Impossible de marquer le message comme lu', [
                'message_id' => $message['id'],
                'error' => $e->getMessage()
            ]);
        }

        // Enregistrer le message
        return $this->messageRepository->save($whatsAppMessage);
    }

    /**
     * Traite un statut de message WhatsApp
     *
     * @param array $status Les données du statut
     * @param array $metadata Les métadonnées associées
     * @return bool
     */
    public function processMessageStatus(array $status, array $metadata): bool
    {
        $this->logger->info('Traitement d\'un statut de message WhatsApp', [
            'message_id' => $status['id'] ?? 'inconnu',
            'status' => $status['status'] ?? 'inconnu'
        ]);

        // Rechercher le message correspondant
        $message = $this->messageRepository->findByMessageId($status['id']);
        if (!$message) {
            $this->logger->warning('Statut reçu pour un message inconnu', [
                'message_id' => $status['id'],
                'status' => $status['status']
            ]);
            
            // Créer un nouvel enregistrement pour le statut si le message n'existe pas
            $message = new WhatsAppMessage();
            $message->setMessageId($status['id']);
            $message->setType('status');
            $message->setTimestamp($status['timestamp'] ?? time());
            $message->setRawData(json_encode($status));
            
            if (isset($status['recipient_id'])) {
                $message->setSender($status['recipient_id']);
            }
            
            if (isset($metadata['from'])) {
                $message->setRecipient($metadata['from']);
            }
        }

        // Mettre à jour le statut
        $message->setStatus($status['status']);
        $this->messageRepository->save($message);

        return true;
    }

    /**
     * Récupère les messages d'un expéditeur
     *
     * @param string $sender
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getMessagesBySender(string $sender, int $limit = 50, int $offset = 0): array
    {
        return $this->messageRepository->findBySender($sender, $limit, $offset);
    }

    /**
     * Récupère les messages d'un destinataire
     *
     * @param string $recipient
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getMessagesByRecipient(string $recipient, int $limit = 50, int $offset = 0): array
    {
        return $this->messageRepository->findByRecipient($recipient, $limit, $offset);
    }

    /**
     * Récupère un message par son ID
     *
     * @param string $messageId
     * @return WhatsAppMessage|null
     */
    public function getMessageById(string $messageId): ?WhatsAppMessage
    {
        return $this->messageRepository->findByMessageId($messageId);
    }

    /**
     * Récupère les messages par type
     *
     * @param string $type
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getMessagesByType(string $type, int $limit = 50, int $offset = 0): array
    {
        return $this->messageRepository->findByType($type, $limit, $offset);
    }

    /**
     * Extrait l'expéditeur du message
     *
     * @param array $message
     * @return string
     */
    private function extractSender(array $message): string
    {
        if (isset($message['from'])) {
            return $message['from'];
        }
        
        return 'unknown';
    }

    /**
     * Extrait le timestamp du message
     *
     * @param array $message
     * @return int
     */
    private function extractTimestamp(array $message): int
    {
        if (isset($message['timestamp'])) {
            return (int) $message['timestamp'];
        }
        
        return time();
    }

    /**
     * Extrait le contenu du message selon son type
     *
     * @param WhatsAppMessage $whatsAppMessage
     * @param array $messageData
     * @return void
     */
    private function extractMessageContent(WhatsAppMessage $whatsAppMessage, array $messageData): void
    {
        switch ($messageData['type']) {
            case 'text':
                if (isset($messageData['text']['body'])) {
                    $whatsAppMessage->setContent($messageData['text']['body']);
                }
                break;
                
            case 'image':
                if (isset($messageData['image'])) {
                    $whatsAppMessage->setMediaType('image');
                    $whatsAppMessage->setMediaUrl($messageData['image']['id'] ?? null);
                    $whatsAppMessage->setContent($messageData['image']['caption'] ?? null);
                }
                break;
                
            case 'audio':
                if (isset($messageData['audio'])) {
                    $whatsAppMessage->setMediaType('audio');
                    $whatsAppMessage->setMediaUrl($messageData['audio']['id'] ?? null);
                }
                break;
                
            case 'video':
                if (isset($messageData['video'])) {
                    $whatsAppMessage->setMediaType('video');
                    $whatsAppMessage->setMediaUrl($messageData['video']['id'] ?? null);
                    $whatsAppMessage->setContent($messageData['video']['caption'] ?? null);
                }
                break;
                
            case 'document':
                if (isset($messageData['document'])) {
                    $whatsAppMessage->setMediaType('document');
                    $whatsAppMessage->setMediaUrl($messageData['document']['id'] ?? null);
                    $whatsAppMessage->setContent($messageData['document']['filename'] ?? null);
                }
                break;
                
            case 'location':
                if (isset($messageData['location'])) {
                    $locationData = $messageData['location'];
                    $content = json_encode([
                        'latitude' => $locationData['latitude'] ?? null,
                        'longitude' => $locationData['longitude'] ?? null,
                        'name' => $locationData['name'] ?? null,
                        'address' => $locationData['address'] ?? null
                    ]);
                    $whatsAppMessage->setContent($content);
                }
                break;
                
            case 'contacts':
                if (isset($messageData['contacts'])) {
                    $whatsAppMessage->setContent(json_encode($messageData['contacts']));
                }
                break;
                
            default:
                $whatsAppMessage->setContent('Contenu non pris en charge de type: ' . $messageData['type']);
                break;
        }
    }
}