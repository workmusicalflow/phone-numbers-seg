<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Handlers;

use App\Services\WhatsApp\Commands\CommandInterface;
use App\Services\WhatsApp\Commands\CommandResult;

/**
 * Interface pour les handlers de commandes
 */
interface HandlerInterface
{
    /**
     * Vérifie si le handler supporte la commande donnée
     */
    public function supports(CommandInterface $command): bool;

    /**
     * Traite la commande et retourne le résultat
     */
    public function handle(CommandInterface $command): CommandResult;
}