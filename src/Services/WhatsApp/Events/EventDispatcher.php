<?php

namespace App\Services\WhatsApp\Events;

use Psr\Log\LoggerInterface;

/**
 * Dispatcher d'événements (Pattern Observer)
 * 
 * Gère l'enregistrement des listeners et la propagation des événements
 */
class EventDispatcher
{
    /**
     * @var array<string, array<ListenerInterface>>
     */
    private array $listeners = [];
    
    private LoggerInterface $logger;
    private bool $asyncEnabled;
    
    public function __construct(LoggerInterface $logger, bool $asyncEnabled = false)
    {
        $this->logger = $logger;
        $this->asyncEnabled = $asyncEnabled;
    }
    
    /**
     * Enregistre un listener pour un événement
     * 
     * @param string $eventName Le nom de l'événement
     * @param ListenerInterface $listener Le listener à enregistrer
     * @param int $priority Priorité (plus élevé = exécuté en premier)
     */
    public function addListener(string $eventName, ListenerInterface $listener, int $priority = 0): void
    {
        if (!isset($this->listeners[$eventName])) {
            $this->listeners[$eventName] = [];
        }
        
        $this->listeners[$eventName][] = [
            'listener' => $listener,
            'priority' => $priority
        ];
        
        // Trier par priorité décroissante
        usort($this->listeners[$eventName], function ($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });
        
        $this->logger->debug('Listener registered', [
            'event' => $eventName,
            'listener' => get_class($listener),
            'priority' => $priority
        ]);
    }
    
    /**
     * Supprime un listener
     */
    public function removeListener(string $eventName, ListenerInterface $listener): void
    {
        if (!isset($this->listeners[$eventName])) {
            return;
        }
        
        $this->listeners[$eventName] = array_filter(
            $this->listeners[$eventName],
            fn($item) => $item['listener'] !== $listener
        );
    }
    
    /**
     * Dispatch un événement à tous les listeners enregistrés
     */
    public function dispatch(EventInterface $event): void
    {
        $eventName = $event->getName();
        
        $this->logger->info('Dispatching event', [
            'event' => $eventName,
            'data' => $event->getData()
        ]);
        
        if (!isset($this->listeners[$eventName])) {
            $this->logger->debug('No listeners for event', ['event' => $eventName]);
            return;
        }
        
        foreach ($this->listeners[$eventName] as $item) {
            if (!$event->shouldPropagate()) {
                $this->logger->debug('Event propagation stopped', ['event' => $eventName]);
                break;
            }
            
            try {
                $listener = $item['listener'];
                
                if ($this->asyncEnabled && $listener->supportsAsync()) {
                    $this->dispatchAsync($listener, $event);
                } else {
                    $this->dispatchSync($listener, $event);
                }
                
            } catch (\Exception $e) {
                $this->logger->error('Listener execution failed', [
                    'event' => $eventName,
                    'listener' => get_class($item['listener']),
                    'error' => $e->getMessage()
                ]);
                
                // Continue avec les autres listeners
                continue;
            }
        }
    }
    
    /**
     * Dispatch synchrone d'un événement
     */
    private function dispatchSync(ListenerInterface $listener, EventInterface $event): void
    {
        $startTime = microtime(true);
        
        $listener->handle($event);
        
        $duration = microtime(true) - $startTime;
        $this->logger->debug('Listener executed', [
            'listener' => get_class($listener),
            'event' => $event->getName(),
            'duration_ms' => round($duration * 1000, 2)
        ]);
    }
    
    /**
     * Dispatch asynchrone d'un événement (à implémenter avec un système de queue)
     */
    private function dispatchAsync(ListenerInterface $listener, EventInterface $event): void
    {
        // TODO: Implémenter avec RabbitMQ, Redis ou autre système de queue
        $this->logger->info('Async dispatch queued', [
            'listener' => get_class($listener),
            'event' => $event->getName()
        ]);
        
        // Pour l'instant, on exécute en synchrone
        $this->dispatchSync($listener, $event);
    }
    
    /**
     * Récupère tous les listeners pour un événement
     */
    public function getListeners(string $eventName): array
    {
        return array_map(
            fn($item) => $item['listener'],
            $this->listeners[$eventName] ?? []
        );
    }
    
    /**
     * Vérifie si un événement a des listeners
     */
    public function hasListeners(string $eventName): bool
    {
        return !empty($this->listeners[$eventName]);
    }
}