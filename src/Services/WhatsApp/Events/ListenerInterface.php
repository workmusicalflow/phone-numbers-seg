<?php

namespace App\Services\WhatsApp\Events;

/**
 * Interface pour tous les listeners d'événements
 * 
 * Les listeners réagissent aux événements dispatchés dans le système
 */
interface ListenerInterface
{
    /**
     * Gère l'événement
     * 
     * @param EventInterface $event L'événement à traiter
     */
    public function handle(EventInterface $event): void;
    
    /**
     * Indique si le listener supporte l'exécution asynchrone
     * 
     * @return bool
     */
    public function supportsAsync(): bool;
    
    /**
     * Récupère le nom du listener pour le logging
     * 
     * @return string
     */
    public function getName(): string;
}