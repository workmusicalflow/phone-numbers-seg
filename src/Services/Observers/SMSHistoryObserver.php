<?php

namespace App\Services\Observers;

use App\Services\Interfaces\ObserverInterface;
use App\Repositories\SMSHistoryRepository;
use App\Models\SMSHistory;

/**
 * Observateur pour l'historique des SMS
 * 
 * Cet observateur est notifié lorsqu'un SMS est envoyé et enregistre
 * l'événement dans l'historique.
 */
class SMSHistoryObserver implements ObserverInterface
{
    /**
     * @var \App\Repositories\Interfaces\SMSHistoryRepositoryInterface
     */
    private $smsHistoryRepository;

    /**
     * Constructeur
     * 
     * @param SMSHistoryRepository $smsHistoryRepository
     */
    public function __construct(\App\Repositories\Interfaces\SMSHistoryRepositoryInterface $smsHistoryRepository)
    {
        $this->smsHistoryRepository = $smsHistoryRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $eventType, array $data): void
    {
        // Vérifier si c'est un événement d'envoi de SMS
        if ($eventType !== 'sms.sent' && $eventType !== 'sms.failed') {
            return;
        }

        // Vérifier que les données nécessaires sont présentes
        if (!isset($data['phoneNumber']) || !isset($data['message'])) {
            return;
        }

        // Préparer les données pour l'historique
        $status = $eventType === 'sms.sent' ? 'sent' : 'failed';
        $errorMessage = isset($data['error']) ? $data['error'] : null;
        $messageId = isset($data['messageId']) ? $data['messageId'] : null;
        $senderAddress = isset($data['senderAddress']) ? $data['senderAddress'] : 'system';
        $senderName = isset($data['senderName']) ? $data['senderName'] : 'System';
        $phoneNumberId = isset($data['phoneNumberId']) ? $data['phoneNumberId'] : null;
        $segmentId = isset($data['segmentId']) ? $data['segmentId'] : null;
        $userId = isset($data['userId']) ? $data['userId'] : null;
        $batchId = isset($data['batchId']) ? $data['batchId'] : null;
        $queueId = isset($data['queueId']) ? $data['queueId'] : null;

        // Créer un nouvel enregistrement d'historique
        $smsHistory = new \App\Entities\SMSHistory();
        $smsHistory->setPhoneNumber($data['phoneNumber']);
        $smsHistory->setMessage($data['message']);
        $smsHistory->setStatus($status);
        $smsHistory->setSenderAddress($senderAddress);
        $smsHistory->setSenderName($senderName);
        $smsHistory->setPhoneNumberId($phoneNumberId);
        $smsHistory->setMessageId($messageId);
        $smsHistory->setErrorMessage($errorMessage);
        $smsHistory->setSegmentId($segmentId);
        $smsHistory->setUserId($userId);
        $smsHistory->setBatchId($batchId);
        $smsHistory->setCreatedAt(new \DateTime());

        // Enregistrer l'historique
        $this->smsHistoryRepository->save($smsHistory);
    }
}
