<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Events;

use App\Entities\User;
use App\Services\WhatsApp\Commands\BulkSendResult;

/**
 * Événement émis à la fin d'un envoi en masse
 */
class BulkSendCompletedEvent extends AbstractEvent
{
    public function __construct(
        private readonly User $user,
        private readonly string $templateName,
        private readonly BulkSendResult $result
    ) {
        parent::__construct();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    public function getResult(): BulkSendResult
    {
        return $this->result;
    }

    public function getName(): string
    {
        return 'bulk_send.completed';
    }

    public function getData(): array
    {
        return [
            'userId' => $this->user->getId(),
            'templateName' => $this->templateName,
            'totalSent' => $this->result->getTotalSent(),
            'totalFailed' => $this->result->getTotalFailed(),
            'totalAttempted' => $this->result->getTotalAttempted(),
            'successRate' => round($this->result->getSuccessRate(), 2),
            'errorSummary' => $this->result->getErrorSummary(),
            'duration' => $this->getDuration(),
            'timestamp' => $this->getOccurredAt()->format('c')
        ];
    }

    /**
     * Calcule la durée de l'envoi en secondes
     */
    private function getDuration(): float
    {
        // La durée sera calculée par le listener en comparant avec l'événement de démarrage
        // Pour l'instant, on retourne 0
        return 0.0;
    }
}