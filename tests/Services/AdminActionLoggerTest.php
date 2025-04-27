<?php

namespace Tests\Services;

use App\Entities\User;
use App\Repositories\Interfaces\AdminActionLogRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\AdminActionLogger;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class AdminActionLoggerTest extends TestCase
{
    private MockObject&AdminActionLogRepositoryInterface $adminActionLogRepositoryMock;
    private MockObject&UserRepositoryInterface $userRepositoryMock;
    private MockObject&LoggerInterface $loggerMock;
    private AdminActionLogger $adminActionLogger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminActionLogRepositoryMock = $this->createMock(AdminActionLogRepositoryInterface::class);
        $this->userRepositoryMock = $this->createMock(UserRepositoryInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        // Mock la configuration pour s'assurer que la journalisation est activée
        // Note: Ceci suppose que le fichier de config existe et est lisible.
        // Une meilleure approche serait d'injecter la config, mais pour ce test, on assume qu'elle est chargée.

        $this->adminActionLogger = new AdminActionLogger(
            $this->adminActionLogRepositoryMock,
            $this->userRepositoryMock,
            $this->loggerMock
        );
    }

    public function testLog(): void
    {
        // --- Arrange ---
        $adminId = 1;
        $actionType = 'test_action';
        $targetId = 123;
        $targetType = 'some_entity';
        $details = ['key' => 'value'];

        // Créer un mock de l'entité User
        $adminUserMock = $this->createMock(User::class);

        // Configurer le mock UserRepository pour retourner le mock User
        $this->userRepositoryMock->expects($this->once())
            ->method('findById')
            ->with($adminId)
            ->willReturn($adminUserMock);

        // Configurer le mock AdminActionLogRepository pour s'attendre à un appel à 'log'
        $this->adminActionLogRepositoryMock->expects($this->once())
            ->method('log')
            ->with(
                $this->identicalTo($adminUserMock), // Vérifie que c'est bien l'objet User mocké
                $this->equalTo($actionType),
                $this->equalTo($targetId),
                $this->equalTo($targetType),
                $this->equalTo($details)
            );
        // Pas de willReturn() nécessaire car la méthode log du repo ne retourne rien (void)

        // Configurer le mock Logger pour ne s'attendre à aucun appel d'erreur/warning
        $this->loggerMock->expects($this->never())->method('error');
        $this->loggerMock->expects($this->never())->method('warning');

        // --- Act ---
        $result = $this->adminActionLogger->log($adminId, $actionType, $targetId, $targetType, $details);

        // --- Assert ---
        $this->assertTrue($result, "La méthode log() devrait retourner true en cas de succès.");
        // Les expectations sur les mocks servent d'assertions implicites.
    }

    public function testLogFailsWhenUserNotFound(): void
    {
        // --- Arrange ---
        $adminId = 999; // ID non existant
        $actionType = 'test_action';

        // Configurer le mock UserRepository pour retourner null
        $this->userRepositoryMock->expects($this->once())
            ->method('findById')
            ->with($adminId)
            ->willReturn(null);

        // S'assurer que le repository de log n'est jamais appelé
        $this->adminActionLogRepositoryMock->expects($this->never())
            ->method('log');

        // S'attendre à un appel de warning sur le logger
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with($this->stringContains("non-existent admin ID: {$adminId}"));

        // --- Act ---
        $result = $this->adminActionLogger->log($adminId, $actionType);

        // --- Assert ---
        $this->assertFalse($result, "La méthode log() devrait retourner false si l'admin n'existe pas.");
    }

    public function testLogFailsWhenRepositoryThrowsException(): void
    {
        // --- Arrange ---
        $adminId = 1;
        $actionType = 'test_action';
        $exceptionMessage = 'Database error';

        // Créer un mock de l'entité User
        $adminUserMock = $this->createMock(User::class);

        // Configurer le mock UserRepository pour retourner le mock User
        $this->userRepositoryMock->expects($this->once())
            ->method('findById')
            ->with($adminId)
            ->willReturn($adminUserMock);

        // Configurer le mock AdminActionLogRepository pour lancer une exception
        $this->adminActionLogRepositoryMock->expects($this->once())
            ->method('log')
            ->willThrowException(new \Exception($exceptionMessage));

        // S'attendre à un appel d'erreur sur le logger
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains("Erreur lors de la journalisation"),
                $this->arrayHasKey('exception')
            );

        // --- Act ---
        $result = $this->adminActionLogger->log($adminId, $actionType);

        // --- Assert ---
        $this->assertFalse($result, "La méthode log() devrait retourner false si le repository échoue.");
    }

    public function testLogDoesNothingWhenLoggingDisabled(): void
    {
        // Note: This test requires modifying the service or injecting config to simulate disabled logging.
        // For now, we assume the config loading works and test the enabled path.
        // If config injection is implemented, this test can be properly written.
        $this->markTestSkipped('Requires config injection to test disabled state.');
    }
}
