<?php

namespace App\Services\WhatsApp\Commands;

/**
 * Interface pour toutes les commandes WhatsApp
 * 
 * Le pattern Command encapsule une requête comme un objet,
 * permettant de paramétrer, mettre en queue, logger et annuler des opérations.
 */
interface CommandInterface
{
    /**
     * Exécute la commande
     * 
     * @return CommandResult Le résultat de l'exécution
     */
    public function execute(): CommandResult;
    
    /**
     * Vérifie si la commande peut être exécutée
     * 
     * @return bool
     */
    public function canExecute(): bool;
    
    /**
     * Récupère le nom de la commande pour le logging
     * 
     * @return string
     */
    public function getName(): string;
    
    /**
     * Récupère les métadonnées de la commande
     * 
     * @return array
     */
    public function getMetadata(): array;
}