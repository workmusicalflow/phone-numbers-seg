<?php

namespace App\Services\Interfaces;

/**
 * Interface pour le pattern Observer (Sujet)
 * 
 * Cette interface définit le contrat pour les sujets qui souhaitent
 * notifier des observateurs lorsque leur état change.
 */
interface SubjectInterface
{
    /**
     * Attache un observateur au sujet
     * 
     * @param ObserverInterface $observer L'observateur à attacher
     * @return void
     */
    public function attach(ObserverInterface $observer): void;

    /**
     * Détache un observateur du sujet
     * 
     * @param ObserverInterface $observer L'observateur à détacher
     * @return void
     */
    public function detach(ObserverInterface $observer): void;

    /**
     * Attache un observateur pour un type d'événement spécifique
     * 
     * @param ObserverInterface $observer L'observateur à attacher
     * @param string $eventType Type d'événement
     * @return void
     */
    public function attachForEvent(ObserverInterface $observer, string $eventType): void;

    /**
     * Détache un observateur d'un type d'événement spécifique
     * 
     * @param ObserverInterface $observer L'observateur à détacher
     * @param string $eventType Type d'événement
     * @return void
     */
    public function detachFromEvent(ObserverInterface $observer, string $eventType): void;

    /**
     * Notifie tous les observateurs attachés
     * 
     * @param string $eventType Type d'événement
     * @param array $data Données associées à l'événement
     * @return void
     */
    public function notify(string $eventType, array $data = []): void;
}
