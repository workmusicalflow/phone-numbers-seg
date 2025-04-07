<?php

namespace App\Services\Interfaces;

/**
 * Interface pour le pattern Observer
 * 
 * Cette interface définit le contrat pour les observateurs qui souhaitent
 * être notifiés des événements du système.
 */
interface ObserverInterface
{
    /**
     * Méthode appelée lorsqu'un événement se produit
     * 
     * @param string $eventType Type d'événement
     * @param array $data Données associées à l'événement
     * @return void
     */
    public function update(string $eventType, array $data): void;
}
