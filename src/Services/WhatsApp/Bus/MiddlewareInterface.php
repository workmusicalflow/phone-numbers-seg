<?php

namespace App\Services\WhatsApp\Bus;

use App\Services\WhatsApp\Commands\CommandInterface;
use App\Services\WhatsApp\Commands\CommandResult;

/**
 * Interface pour les middlewares du Command Bus
 * 
 * Les middlewares permettent d'intercepter les commandes
 * avant et après leur exécution
 */
interface MiddlewareInterface
{
    /**
     * Exécuté avant la commande
     * 
     * @param CommandInterface $command
     * @return bool True pour continuer, false pour arrêter
     */
    public function before(CommandInterface $command): bool;
    
    /**
     * Exécuté après la commande
     * 
     * @param CommandInterface $command
     * @param CommandResult $result
     */
    public function after(CommandInterface $command, CommandResult $result): void;
}