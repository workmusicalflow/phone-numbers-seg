<?php

namespace Tests\Services\Observers;

use PHPUnit\Framework\TestCase;
use App\Services\Observers\SMSHistoryObserver;
use App\Repositories\SMSHistoryRepository;
use App\Models\SMSHistory;

class SMSHistoryObserverTest extends TestCase
{
    private $smsHistoryRepository;
    private $smsHistoryObserver;

    protected function setUp(): void
    {
        $this->smsHistoryRepository = $this->createMock(SMSHistoryRepository::class);
        $this->smsHistoryObserver = new SMSHistoryObserver($this->smsHistoryRepository);
    }

    public function testUpdateWithSentEvent(): void
    {
        // Configurer les attentes pour le repository
        $this->smsHistoryRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($smsHistory) {
                return $smsHistory instanceof SMSHistory
                    && $smsHistory->getPhoneNumber() === '+2250707070707'
                    && $smsHistory->getMessage() === 'Test message'
                    && $smsHistory->getStatus() === 'sent'
                    && $smsHistory->getSenderName() === 'TestSender'
                    && $smsHistory->getSenderAddress() === 'test-address';
            }));

        // Appeler la méthode update avec un événement d'envoi réussi
        $this->smsHistoryObserver->update('sms.sent', [
            'phoneNumber' => '+2250707070707',
            'message' => 'Test message',
            'senderName' => 'TestSender',
            'senderAddress' => 'test-address',
            'messageId' => 'msg-123'
        ]);
    }

    public function testUpdateWithFailedEvent(): void
    {
        // Configurer les attentes pour le repository
        $this->smsHistoryRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($smsHistory) {
                return $smsHistory instanceof SMSHistory
                    && $smsHistory->getPhoneNumber() === '+2250707070707'
                    && $smsHistory->getMessage() === 'Test message'
                    && $smsHistory->getStatus() === 'failed'
                    && $smsHistory->getErrorMessage() === 'Error sending SMS';
            }));

        // Appeler la méthode update avec un événement d'envoi échoué
        $this->smsHistoryObserver->update('sms.failed', [
            'phoneNumber' => '+2250707070707',
            'message' => 'Test message',
            'error' => 'Error sending SMS'
        ]);
    }

    public function testUpdateWithUnrelatedEvent(): void
    {
        // Configurer les attentes pour le repository (ne devrait pas être appelé)
        $this->smsHistoryRepository->expects($this->never())
            ->method('save');

        // Appeler la méthode update avec un événement non lié
        $this->smsHistoryObserver->update('user.created', [
            'userId' => 123,
            'username' => 'testuser'
        ]);
    }

    public function testUpdateWithMissingRequiredData(): void
    {
        // Configurer les attentes pour le repository (ne devrait pas être appelé)
        $this->smsHistoryRepository->expects($this->never())
            ->method('save');

        // Appeler la méthode update avec des données incomplètes
        $this->smsHistoryObserver->update('sms.sent', [
            'phoneNumber' => '+2250707070707'
            // Message manquant
        ]);

        // Appeler la méthode update avec des données incomplètes
        $this->smsHistoryObserver->update('sms.sent', [
            'message' => 'Test message'
            // Numéro de téléphone manquant
        ]);
    }

    public function testUpdateWithOptionalData(): void
    {
        // Configurer les attentes pour le repository
        $this->smsHistoryRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($smsHistory) {
                return $smsHistory instanceof SMSHistory
                    && $smsHistory->getPhoneNumber() === '+2250707070707'
                    && $smsHistory->getMessage() === 'Test message'
                    && $smsHistory->getStatus() === 'sent'
                    && $smsHistory->getPhoneNumberId() === 123
                    && $smsHistory->getSegmentId() === 456;
            }));

        // Appeler la méthode update avec des données optionnelles
        $this->smsHistoryObserver->update('sms.sent', [
            'phoneNumber' => '+2250707070707',
            'message' => 'Test message',
            'phoneNumberId' => 123,
            'segmentId' => 456
        ]);
    }

    public function testUpdateWithDefaultValues(): void
    {
        // Configurer les attentes pour le repository
        $this->smsHistoryRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($smsHistory) {
                return $smsHistory instanceof SMSHistory
                    && $smsHistory->getPhoneNumber() === '+2250707070707'
                    && $smsHistory->getMessage() === 'Test message'
                    && $smsHistory->getStatus() === 'sent'
                    && $smsHistory->getSenderName() === 'System'
                    && $smsHistory->getSenderAddress() === 'system';
            }));

        // Appeler la méthode update avec seulement les données requises
        $this->smsHistoryObserver->update('sms.sent', [
            'phoneNumber' => '+2250707070707',
            'message' => 'Test message'
        ]);
    }
}
