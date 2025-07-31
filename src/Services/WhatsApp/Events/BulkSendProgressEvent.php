<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Events;

use App\Entities\User;

/**
 * Événement émis pour indiquer la progression d'un envoi en masse
 */
class BulkSendProgressEvent extends AbstractEvent
{
    public function __construct(
        private readonly User $user,
        private readonly string $templateName,
        private readonly int $processed,
        private readonly int $total,
        private readonly int $successful,
        private readonly int $failed
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

    public function getProcessed(): int
    {
        return $this->processed;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getSuccessful(): int
    {
        return $this->successful;
    }

    public function getFailed(): int
    {
        return $this->failed;
    }

    public function getProgress(): float
    {
        if ($this->total === 0) {
            return 0.0;
        }
        
        return ($this->processed / $this->total) * 100;
    }

    public function getName(): string
    {
        return 'bulk_send.progress';
    }

    public function getData(): array
    {
        return [
            'userId' => $this->user->getId(),
            'templateName' => $this->templateName,
            'processed' => $this->processed,
            'total' => $this->total,
            'successful' => $this->successful,
            'failed' => $this->failed,
            'progress' => round($this->getProgress(), 2),
            'timestamp' => $this->getOccurredAt()->format('c')
        ];
    }
}