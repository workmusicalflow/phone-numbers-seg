<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Entities\User;
use App\Entities\WhatsApp\WhatsAppMessageHistory;
use App\Repositories\Interfaces\WhatsApp\WhatsAppMessageHistoryRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\PhoneNumberNormalizerService;
use Psr\Log\LoggerInterface;
use DateTime;

/**
 * Service pour traiter les webhooks WhatsApp entrants
 */
class WhatsAppWebhookService
{
    private WhatsAppMessageHistoryRepositoryInterface $messageRepository;
    private UserRepositoryInterface $userRepository;
    private PhoneNumberNormalizerService $phoneNormalizer;
    private LoggerInterface $logger;
    private array $config;
    
    public function __construct(
        WhatsAppMessageHistoryRepositoryInterface $messageRepository,
        UserRepositoryInterface $userRepository,
        PhoneNumberNormalizerService $phoneNormalizer,
        LoggerInterface $logger,
        array $config
    ) {
        $this->messageRepository = $messageRepository;
        $this->userRepository = $userRepository;
        $this->phoneNormalizer = $phoneNormalizer;
        $this->logger = $logger;
        $this->config = $config;
    }
    
    /**
     * Vérifie la signature du webhook pour s'assurer qu'il vient de Meta
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $appSecret = $this->config['app_secret'] ?? null;
        if (!$appSecret) {
            $this->logger->warning('App secret not configured for webhook verification');
            return true; // En dev, on peut bypasser
        }
        
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $appSecret);
        return hash_equals($expectedSignature, $signature);
    }
    
    /**
     * Traite le payload du webhook
     */
    public function processWebhook(array $payload): void
    {
        if ($payload['object'] !== 'whatsapp_business_account') {
            $this->logger->warning('Received webhook for unknown object type', ['object' => $payload['object']]);
            return;
        }
        
        foreach ($payload['entry'] as $entry) {
            foreach ($entry['changes'] as $change) {
                if ($change['field'] !== 'messages') {
                    continue;
                }
                
                $value = $change['value'];
                
                // Traiter les messages entrants
                if (isset($value['messages'])) {
                    foreach ($value['messages'] as $message) {
                        $this->processIncomingMessage($message, $value['metadata']);
                    }
                }
                
                // Traiter les mises à jour de statut
                if (isset($value['statuses'])) {
                    foreach ($value['statuses'] as $status) {
                        $this->processStatusUpdate($status);
                    }
                }
            }
        }
    }
    
    /**
     * Traite un message entrant
     */
    private function processIncomingMessage(array $message, array $metadata): void
    {
        try {
            // Extraire les données du message
            $wabaMessageId = $message['id'];
            $fromNumber = $this->phoneNormalizer->normalize($message['from']);
            $type = $message['type'];
            $timestamp = new DateTime('@' . $message['timestamp']);
            
            // Extraire le contenu selon le type
            $content = $this->extractMessageContent($message);
            
            // Trouver l'utilisateur Oracle associé au numéro de téléphone business
            $businessPhoneId = $metadata['phone_number_id'];
            $oracleUser = $this->findUserByPhoneNumberId($businessPhoneId);
            
            if (!$oracleUser) {
                $this->logger->error('No Oracle user found for business phone', [
                    'phone_number_id' => $businessPhoneId
                ]);
                return;
            }
            
            // Vérifier si le message existe déjà
            $existingMessage = $this->messageRepository->findByWabaMessageId($wabaMessageId);
            if ($existingMessage) {
                $this->logger->info('Message already exists, skipping', ['waba_id' => $wabaMessageId]);
                return;
            }
            
            // Créer l'entité du message
            $messageHistory = new WhatsAppMessageHistory();
            $messageHistory->setWabaMessageId($wabaMessageId);
            $messageHistory->setPhoneNumber($fromNumber);
            $messageHistory->setOracleUser($oracleUser);
            $messageHistory->setType($type);
            $messageHistory->setDirection('INCOMING');
            $messageHistory->setStatus('received');
            $messageHistory->setContent($content);
            $messageHistory->setCreatedAt($timestamp);
            
            // Ajouter les métadonnées
            $messageHistory->setMetadata([
                'display_phone_number' => $metadata['display_phone_number'] ?? null,
                'profile_name' => $message['profile']['name'] ?? null,
                'context' => $message['context'] ?? null,
                'raw_message' => $message
            ]);
            
            // Sauvegarder le message
            $savedMessage = $this->messageRepository->save($messageHistory);
            
            $this->logger->info('Incoming WhatsApp message saved', [
                'id' => $savedMessage->getId(),
                'waba_id' => $wabaMessageId,
                'from' => $fromNumber,
                'type' => $type
            ]);
            
            // TODO: Déclencher des événements pour notifier le frontend
            // $this->eventDispatcher->dispatch(new WhatsAppMessageReceivedEvent($savedMessage));
            
        } catch (\Exception $e) {
            $this->logger->error('Error processing incoming message', [
                'error' => $e->getMessage(),
                'message_id' => $message['id'] ?? 'unknown'
            ]);
        }
    }
    
    /**
     * Traite une mise à jour de statut
     */
    private function processStatusUpdate(array $status): void
    {
        try {
            $wabaMessageId = $status['id'];
            $newStatus = $status['status'];
            $timestamp = new DateTime('@' . $status['timestamp']);
            
            // Trouver le message existant
            $message = $this->messageRepository->findByWabaMessageId($wabaMessageId);
            if (!$message) {
                $this->logger->warning('Status update for unknown message', ['waba_id' => $wabaMessageId]);
                return;
            }
            
            // Mettre à jour le statut
            $message->setStatus($newStatus);
            $message->setUpdatedAt($timestamp);
            
            // Ajouter les erreurs si présentes
            if (isset($status['errors'])) {
                $message->setErrors($status['errors']);
            }
            
            // Ajouter les informations de pricing si présentes
            if (isset($status['pricing'])) {
                $metadata = $message->getMetadata() ?? [];
                $metadata['pricing'] = $status['pricing'];
                $message->setMetadata($metadata);
            }
            
            // Sauvegarder la mise à jour
            $this->messageRepository->save($message);
            
            $this->logger->info('WhatsApp message status updated', [
                'id' => $message->getId(),
                'waba_id' => $wabaMessageId,
                'status' => $newStatus
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error processing status update', [
                'error' => $e->getMessage(),
                'message_id' => $status['id'] ?? 'unknown'
            ]);
        }
    }
    
    /**
     * Extrait le contenu du message selon son type
     */
    private function extractMessageContent(array $message): string
    {
        $type = $message['type'];
        
        switch ($type) {
            case 'text':
                return $message['text']['body'] ?? '';
                
            case 'image':
            case 'video':
            case 'audio':
            case 'document':
                $media = $message[$type];
                $content = [
                    'id' => $media['id'],
                    'mime_type' => $media['mime_type'] ?? null,
                    'sha256' => $media['sha256'] ?? null,
                    'caption' => $media['caption'] ?? null
                ];
                if ($type === 'document') {
                    $content['filename'] = $media['filename'] ?? null;
                }
                return json_encode($content);
                
            case 'location':
                return json_encode($message['location']);
                
            case 'contacts':
                return json_encode($message['contacts']);
                
            case 'interactive':
                $interactive = $message['interactive'];
                if ($interactive['type'] === 'button_reply') {
                    return json_encode([
                        'type' => 'button_reply',
                        'button_id' => $interactive['button_reply']['id'],
                        'button_title' => $interactive['button_reply']['title']
                    ]);
                } elseif ($interactive['type'] === 'list_reply') {
                    return json_encode([
                        'type' => 'list_reply',
                        'item_id' => $interactive['list_reply']['id'],
                        'item_title' => $interactive['list_reply']['title'],
                        'item_description' => $interactive['list_reply']['description'] ?? null
                    ]);
                }
                return json_encode($interactive);
                
            default:
                return json_encode($message);
        }
    }
    
    /**
     * Trouve l'utilisateur Oracle par l'ID du numéro de téléphone business
     */
    private function findUserByPhoneNumberId(string $phoneNumberId): ?User
    {
        // Pour l'instant, on utilise une approche simple
        // En production, il faudrait mapper phone_number_id à un utilisateur spécifique
        
        // Option 1: Utiliser un utilisateur par défaut (admin)
        $defaultUser = $this->userRepository->find(1);
        if ($defaultUser) {
            return $defaultUser;
        }
        
        // Option 2: Chercher par configuration
        // Cette logique pourrait être étendue pour supporter plusieurs comptes WhatsApp
        $users = $this->userRepository->findAll();
        foreach ($users as $user) {
            // Si on avait un champ whatsapp_phone_number_id dans User
            // if ($user->getWhatsAppPhoneNumberId() === $phoneNumberId) {
            //     return $user;
            // }
        }
        
        return null;
    }
}