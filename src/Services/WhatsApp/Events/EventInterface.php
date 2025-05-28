<?php

namespace App\Services\WhatsApp\Events;

/**
 * Interface pour tous les événements WhatsApp
 * 
 * Le pattern Observer permet de notifier plusieurs objets
 * lors de changements d'état dans le système
 */
interface EventInterface
{
    /**
     * Récupère le nom de l'événement
     * 
     * @return string
     */
    public function getName(): string;
    
    /**
     * Récupère les données de l'événement
     * 
     * @return array
     */
    public function getData(): array;
    
    /**
     * Récupère le timestamp de l'événement
     * 
     * @return \DateTimeInterface
     */
    public function getOccurredAt(): \DateTimeInterface;
    
    /**
     * Indique si l'événement doit être propagé
     * 
     * @return bool
     */
    public function shouldPropagate(): bool;
    
    /**
     * Arrête la propagation de l'événement
     */
    public function stopPropagation(): void;
}