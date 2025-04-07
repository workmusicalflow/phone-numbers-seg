<?php

namespace App\Services;

use App\Services\Interfaces\ObserverInterface;
use App\Services\Interfaces\SubjectInterface;

/**
 * Gestionnaire d'événements implémentant le pattern Observer
 * 
 * Cette classe permet de gérer les événements du système et de notifier
 * les observateurs intéressés.
 */
class EventManager implements SubjectInterface
{
    /**
     * @var array<string, ObserverInterface[]> Liste des observateurs par type d'événement
     */
    private $observers = [];

    /**
     * {@inheritdoc}
     */
    public function attach(ObserverInterface $observer): void
    {
        $className = get_class($observer);

        // Ajouter l'observateur à tous les types d'événements
        foreach (array_keys($this->observers) as $eventType) {
            // Vérifier si l'observateur n'est pas déjà attaché
            if (!$this->isAttached($observer, $eventType)) {
                $this->observers[$eventType][] = $observer;
            }
        }
    }

    /**
     * Attache un observateur pour un type d'événement spécifique
     * 
     * @param ObserverInterface $observer L'observateur à attacher
     * @param string $eventType Type d'événement
     * @return void
     */
    public function attachForEvent(ObserverInterface $observer, string $eventType): void
    {
        // Créer le type d'événement s'il n'existe pas
        if (!isset($this->observers[$eventType])) {
            $this->observers[$eventType] = [];
        }

        // Vérifier si l'observateur n'est pas déjà attaché
        if (!$this->isAttached($observer, $eventType)) {
            $this->observers[$eventType][] = $observer;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function detach(ObserverInterface $observer): void
    {
        // Détacher l'observateur de tous les types d'événements
        foreach (array_keys($this->observers) as $eventType) {
            $this->detachFromEvent($observer, $eventType);
        }
    }

    /**
     * Détache un observateur d'un type d'événement spécifique
     * 
     * @param ObserverInterface $observer L'observateur à détacher
     * @param string $eventType Type d'événement
     * @return void
     */
    public function detachFromEvent(ObserverInterface $observer, string $eventType): void
    {
        if (!isset($this->observers[$eventType])) {
            return;
        }

        $key = array_search($observer, $this->observers[$eventType], true);

        if ($key !== false) {
            unset($this->observers[$eventType][$key]);
            // Réindexer le tableau
            $this->observers[$eventType] = array_values($this->observers[$eventType]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function notify(string $eventType, array $data = []): void
    {
        if (!isset($this->observers[$eventType])) {
            return;
        }

        foreach ($this->observers[$eventType] as $observer) {
            $observer->update($eventType, $data);
        }
    }

    /**
     * Vérifie si un observateur est déjà attaché à un type d'événement
     * 
     * @param ObserverInterface $observer L'observateur à vérifier
     * @param string $eventType Type d'événement
     * @return bool
     */
    private function isAttached(ObserverInterface $observer, string $eventType): bool
    {
        if (!isset($this->observers[$eventType])) {
            return false;
        }

        return in_array($observer, $this->observers[$eventType], true);
    }
}
