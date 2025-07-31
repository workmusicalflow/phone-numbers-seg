<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Events;

use App\Entities\User;

/**
 * Événement émis au début d'un envoi en masse
 */
class BulkSendStartedEvent extends AbstractEvent
{
    public function __construct(
        private readonly User $user,
        private readonly string $templateName,
        private readonly int $totalRecipients
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

    public function getTotalRecipients(): int
    {
        return $this->totalRecipients;
    }

    public function getName(): string
    {
        return 'bulk_send.started';
    }

    public function getData(): array
    {
        return [
            'userId' => $this->user->getId(),
            'templateName' => $this->templateName,
            'totalRecipients' => $this->totalRecipients,
            'timestamp' => $this->getOccurredAt()->format('c')
        ];
    }
}