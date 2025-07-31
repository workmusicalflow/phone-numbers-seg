<?php

declare(strict_types=1);

namespace Tests\Services\WhatsApp;

use PHPUnit\Framework\TestCase;
use App\Services\WhatsApp\Handlers\BulkSendHandler;
use App\Services\WhatsApp\Commands\BulkSendTemplateCommand;
use App\Services\WhatsApp\Commands\BulkSendResult;
use App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface;
use App\Services\WhatsApp\Events\EventDispatcher;
use App\Services\WhatsApp\Events\BulkSendStartedEvent;
use App\Services\WhatsApp\Events\BulkSendCompletedEvent;
use App\Entities\User;
use App\Entities\WhatsApp\WhatsAppMessageHistory;
use Psr\Log\LoggerInterface;

class BulkSendHandlerTest extends TestCase
{
    private BulkSendHandler $handler;
    private WhatsAppServiceInterface $whatsAppService;
    private EventDispatcher $eventDispatcher;
    private LoggerInterface $logger;
    private User $user;

    protected function setUp(): void
    {
        $this->whatsAppService = $this->createMock(WhatsAppServiceInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcher::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->handler = new BulkSendHandler(
            $this->whatsAppService,
            $this->eventDispatcher,
            $this->logger
        );
        
        $this->user = $this->createMock(User::class);
        $this->user->method('getId')->willReturn(123);
        $this->user->method('getSmsCredit')->willReturn(100);
    }

    public function testSupportsReturnsTrueForBulkSendTemplateCommand(): void
    {
        $command = new BulkSendTemplateCommand(
            $this->user,
            ['+22501234567'],
            'hello_world',
            []
        );
        
        $this->assertTrue($this->handler->supports($command));
    }

    public function testHandleSuccessfulBulkSend(): void
    {
        // Préparer les données
        $recipients = ['+22501234567', '+22507654321', '+22509876543'];
        $templateName = 'hello_world';
        
        $command = new BulkSendTemplateCommand(
            $this->user,
            $recipients,
            $templateName,
            ['param1' => 'value1']
        );
        
        // Configurer les mocks
        $messageHistory = $this->createMock(WhatsAppMessageHistory::class);
        $messageHistory->method('getWabaMessageId')->willReturn('msg_123');
        
        $this->whatsAppService
            ->expects($this->exactly(3))
            ->method('sendTemplateMessage')
            ->with(
                $this->user,
                $this->anything(), // recipient
                $templateName,
                'fr', // langue
                null, // headerImageUrl
                [] // bodyParams
            )
            ->willReturn($messageHistory);
        
        // Vérifier les événements émis
        $events = [];
        $this->eventDispatcher
            ->expects($this->atLeastOnce())
            ->method('dispatch')
            ->willReturnCallback(function($event) use (&$events) {
                $events[] = $event;
            });
        
        // Exécuter
        $result = $this->handler->handle($command);
        
        // Assertions
        $this->assertInstanceOf(BulkSendResult::class, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertEquals(3, $result->getTotalSent());
        $this->assertEquals(0, $result->getTotalFailed());
        $this->assertEquals(100.0, $result->getSuccessRate());
        
        // Vérifier les événements
        $this->assertGreaterThan(0, count($events));
        $this->assertInstanceOf(BulkSendStartedEvent::class, $events[0]);
        $this->assertInstanceOf(BulkSendCompletedEvent::class, end($events));
    }

    public function testHandlePartialFailure(): void
    {
        $recipients = ['+22501234567', '+22507654321'];
        $command = new BulkSendTemplateCommand(
            $this->user,
            $recipients,
            'hello_world',
            []
        );
        
        // Premier envoi réussit, deuxième échoue
        $messageHistory = $this->createMock(WhatsAppMessageHistory::class);
        $messageHistory->method('getWabaMessageId')->willReturn('msg_123');
        
        $this->whatsAppService
            ->expects($this->exactly(2))
            ->method('sendTemplateMessage')
            ->willReturnOnConsecutiveCalls(
                $messageHistory,
                $this->throwException(new \Exception('Invalid phone number'))
            );
        
        $result = $this->handler->handle($command);
        
        $this->assertInstanceOf(BulkSendResult::class, $result);
        $this->assertTrue($result->isSuccess()); // Succès partiel
        $this->assertEquals(1, $result->getTotalSent());
        $this->assertEquals(1, $result->getTotalFailed());
        $this->assertEquals(50.0, $result->getSuccessRate());
        
        // Vérifier l'erreur
        $error = $result->getErrorForRecipient('+22507654321');
        $this->assertNotNull($error);
        $this->assertEquals('Invalid phone number', $error['error']);
    }

    public function testHandleWithBatches(): void
    {
        // Créer 150 destinataires pour tester le batching
        $recipients = [];
        for ($i = 1; $i <= 150; $i++) {
            $recipients[] = sprintf('+22501%06d', $i);
        }
        
        $command = new BulkSendTemplateCommand(
            $this->user,
            $recipients,
            'hello_world',
            [],
            [],
            ['batchSize' => 50] // 3 batches de 50
        );
        
        $messageHistory = $this->createMock(WhatsAppMessageHistory::class);
        $this->whatsAppService
            ->expects($this->exactly(150))
            ->method('sendTemplateMessage')
            ->willReturn($messageHistory);
        
        // Vérifier que BatchProcessedEvent est émis 3 fois
        $batchEvents = 0;
        $this->eventDispatcher
            ->expects($this->atLeastOnce())
            ->method('dispatch')
            ->willReturnCallback(function($event) use (&$batchEvents) {
                if (strpos(get_class($event), 'BatchProcessedEvent') !== false) {
                    $batchEvents++;
                }
            });
        
        $result = $this->handler->handle($command);
        
        $this->assertEquals(150, $result->getTotalSent());
        $this->assertEquals(3, $batchEvents); // 3 batches
    }

    public function testHandleStopsOnError(): void
    {
        $recipients = ['+22501234567', '+22507654321', '+22509876543'];
        
        $command = new BulkSendTemplateCommand(
            $this->user,
            $recipients,
            'hello_world',
            [],
            [],
            ['stopOnError' => true, 'batchSize' => 1] // Un par batch pour tester
        );
        
        // Premier réussit, deuxième échoue
        $messageHistory = $this->createMock(WhatsAppMessageHistory::class);
        
        $this->whatsAppService
            ->expects($this->exactly(2)) // Seulement 2 appels car arrêt après erreur
            ->method('sendTemplateMessage')
            ->willReturnOnConsecutiveCalls(
                $messageHistory,
                $this->throwException(new \Exception('Error'))
            );
        
        $result = $this->handler->handle($command);
        
        $this->assertEquals(1, $result->getTotalSent());
        $this->assertEquals(1, $result->getTotalFailed());
        $this->assertEquals(2, $result->getTotalAttempted()); // Pas 3
    }

    public function testGetParametersForRecipient(): void
    {
        $defaultParams = ['name' => 'Default'];
        $recipientParams = [
            '+22501234567' => ['name' => 'John'],
            '+22507654321' => ['name' => 'Jane']
        ];
        
        $command = new BulkSendTemplateCommand(
            $this->user,
            ['+22501234567', '+22507654321', '+22509876543'],
            'hello_world',
            $defaultParams,
            $recipientParams
        );
        
        // Paramètres spécifiques au destinataire
        $this->assertEquals(
            ['name' => 'John'],
            $command->getParametersForRecipient('+22501234567')
        );
        
        // Paramètres par défaut si pas de paramètres spécifiques
        $this->assertEquals(
            ['name' => 'Default'],
            $command->getParametersForRecipient('+22509876543')
        );
    }
}