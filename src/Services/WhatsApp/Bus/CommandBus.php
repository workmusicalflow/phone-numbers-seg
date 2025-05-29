<?php

namespace App\Services\WhatsApp\Bus;

use App\Services\WhatsApp\Commands\CommandInterface;
use App\Services\WhatsApp\Commands\CommandResult;
use App\Services\WhatsApp\Handlers\HandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Bus de commandes pour orchestrer l'exécution
 * 
 * Le Command Bus est responsable de :
 * - Router les commandes vers les handlers appropriés
 * - Gérer les middlewares (logging, validation, etc.)
 * - Permettre l'exécution asynchrone future
 */
class CommandBus
{
    private LoggerInterface $logger;
    private array $middleware = [];
    private array $commandStats = [];
    
    /**
     * @var array<HandlerInterface>
     */
    private array $handlers = [];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Ajoute un middleware au bus
     */
    public function addMiddleware(MiddlewareInterface $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    /**
     * Enregistre un handler pour traiter des commandes
     */
    public function registerHandler(HandlerInterface $handler): void
    {
        $this->handlers[] = $handler;
    }

    /**
     * Exécute une commande
     */
    public function handle(CommandInterface $command): CommandResult
    {
        $commandName = $command->getName();
        $startTime = microtime(true);
        
        $this->logger->info('Command bus handling command', [
            'command' => $commandName,
            'metadata' => $command->getMetadata()
        ]);

        try {
            // Exécuter les middlewares avant
            foreach ($this->middleware as $middleware) {
                if (!$middleware->before($command)) {
                    return CommandResult::failure(
                        'Command rejected by middleware: ' . get_class($middleware)
                    );
                }
            }

            // Trouver un handler pour la commande ou exécuter directement
            $handler = $this->findHandler($command);
            
            if ($handler !== null) {
                $result = $handler->handle($command);
            } else {
                // Fallback : exécuter la commande directement
                $result = $command->execute();
            }

            // Exécuter les middlewares après
            foreach (array_reverse($this->middleware) as $middleware) {
                $middleware->after($command, $result);
            }

            // Enregistrer les statistiques
            $this->recordStats($commandName, true, microtime(true) - $startTime);

            $this->logger->info('Command executed successfully', [
                'command' => $commandName,
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->logger->error('Command execution failed', [
                'command' => $commandName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Enregistrer les statistiques d'échec
            $this->recordStats($commandName, false, microtime(true) - $startTime);

            return CommandResult::failure(
                'Command execution failed: ' . $e->getMessage(),
                ['exception' => get_class($e)]
            );
        }
    }

    /**
     * Exécute plusieurs commandes en parallèle
     */
    public function handleBatch(array $commands): array
    {
        $results = [];
        
        foreach ($commands as $key => $command) {
            if (!$command instanceof CommandInterface) {
                $results[$key] = CommandResult::failure('Invalid command');
                continue;
            }
            
            $results[$key] = $this->handle($command);
        }
        
        return $results;
    }

    /**
     * Enregistre les statistiques d'exécution
     */
    private function recordStats(string $commandName, bool $success, float $duration): void
    {
        if (!isset($this->commandStats[$commandName])) {
            $this->commandStats[$commandName] = [
                'total' => 0,
                'success' => 0,
                'failure' => 0,
                'total_duration' => 0,
                'avg_duration' => 0
            ];
        }

        $stats = &$this->commandStats[$commandName];
        $stats['total']++;
        $stats[$success ? 'success' : 'failure']++;
        $stats['total_duration'] += $duration;
        $stats['avg_duration'] = $stats['total_duration'] / $stats['total'];
    }

    /**
     * Récupère les statistiques des commandes
     */
    public function getStatistics(): array
    {
        return $this->commandStats;
    }

    /**
     * Réinitialise les statistiques
     */
    public function resetStatistics(): void
    {
        $this->commandStats = [];
    }

    /**
     * Trouve un handler pour une commande donnée
     */
    private function findHandler(CommandInterface $command): ?HandlerInterface
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($command)) {
                return $handler;
            }
        }
        
        return null;
    }
}