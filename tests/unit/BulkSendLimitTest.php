<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\WhatsApp\Commands\BulkSendTemplateCommand;
use App\Services\WhatsApp\Handlers\BulkSendHandler;
use App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface;
use App\Services\WhatsApp\Events\EventDispatcher;
use App\Entities\User;
use Psr\Log\LoggerInterface;

/**
 * Test spécifique pour vérifier la limite de 500 destinataires
 */
class BulkSendLimitTest extends TestCase
{
    public function testRejectsMoreThan500Recipients(): void
    {
        // Créer les mocks
        $whatsAppService = $this->createMock(WhatsAppServiceInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $logger = $this->createMock(LoggerInterface::class);
        
        $handler = new BulkSendHandler($whatsAppService, $eventDispatcher, $logger);
        
        // Créer un utilisateur mock
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $user->method('getSmsCredit')->willReturn(1000);
        
        // Créer 501 destinataires
        $recipients = [];
        for ($i = 1; $i <= 501; $i++) {
            $recipients[] = sprintf('+22501%06d', $i);
        }
        
        // Créer la commande avec trop de destinataires
        $command = new BulkSendTemplateCommand(
            $user,
            $recipients,
            'hello_world',
            []
        );
        
        // Vérifier que ça lance une exception
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('dépasse la limite autorisée (500)');
        
        $handler->handle($command);
    }
    
    public function testAccepts500Recipients(): void
    {
        // Créer les mocks
        $whatsAppService = $this->createMock(WhatsAppServiceInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $logger = $this->createMock(LoggerInterface::class);
        
        $handler = new BulkSendHandler($whatsAppService, $eventDispatcher, $logger);
        
        // Créer un utilisateur mock
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $user->method('getSmsCredit')->willReturn(1000);
        
        // Créer exactement 500 destinataires
        $recipients = [];
        for ($i = 1; $i <= 500; $i++) {
            $recipients[] = sprintf('+22501%06d', $i);
        }
        
        // Créer la commande avec 500 destinataires
        $command = new BulkSendTemplateCommand(
            $user,
            $recipients,
            'hello_world',
            []
        );
        
        // Le service WhatsApp devrait être appelé
        $whatsAppService->expects($this->atLeastOnce())
            ->method('sendTemplateMessage');
        
        // Pas d'exception attendue
        $handler->handle($command);
        
        $this->assertTrue(true); // Si on arrive ici, le test passe
    }
}