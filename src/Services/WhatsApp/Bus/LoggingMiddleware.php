<?php

namespace App\Services\WhatsApp\Bus;

use App\Services\WhatsApp\Commands\CommandInterface;
use App\Services\WhatsApp\Commands\CommandResult;
use Psr\Log\LoggerInterface;

/**
 * Middleware qui log toutes les commandes
 */
class LoggingMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;
    private array $currentExecution = [];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function before(CommandInterface $command): bool
    {
        $commandId = spl_object_id($command);
        $this->currentExecution[$commandId] = [
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true)
        ];

        $this->logger->info('Command execution started', [
            'command' => $command->getName(),
            'metadata' => $command->getMetadata(),
            'can_execute' => $command->canExecute()
        ]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function after(CommandInterface $command, CommandResult $result): void
    {
        $commandId = spl_object_id($command);
        
        if (!isset($this->currentExecution[$commandId])) {
            return;
        }

        $execution = $this->currentExecution[$commandId];
        $duration = microtime(true) - $execution['start_time'];
        $memoryUsed = memory_get_usage(true) - $execution['start_memory'];

        $logData = [
            'command' => $command->getName(),
            'success' => $result->isSuccess(),
            'duration_ms' => round($duration * 1000, 2),
            'memory_used_mb' => round($memoryUsed / 1024 / 1024, 2),
            'result_message' => $result->getMessage()
        ];

        if ($result->isSuccess()) {
            $this->logger->info('Command execution completed', $logData);
        } else {
            $logData['errors'] = $result->getErrors();
            $this->logger->error('Command execution failed', $logData);
        }

        // Nettoyer la mémoire
        unset($this->currentExecution[$commandId]);
    }
}