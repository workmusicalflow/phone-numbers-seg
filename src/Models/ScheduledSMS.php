<?php

namespace App\Models;

use DateTime;
use JsonException;
use PDO;

class ScheduledSMS
{
    private int $id;
    private int $userId;
    private string $name;
    private string $message;
    private int $senderNameId;
    private string $scheduledDate;
    private string $status;
    private bool $isRecurring;
    private ?string $recurrencePattern;
    private ?string $recurrenceConfig;
    private string $recipientsType;
    private string $recipientsData;
    private string $createdAt;
    private string $updatedAt;
    private ?string $lastRunAt;
    private ?string $nextRunAt;

    public function __construct(
        int $id,
        int $userId,
        string $name,
        string $message,
        int $senderNameId,
        string $scheduledDate,
        string $status = 'pending',
        bool $isRecurring = false,
        ?string $recurrencePattern = null,
        ?string $recurrenceConfig = null,
        string $recipientsType = 'numbers',
        string $recipientsData = '[]',
        ?string $createdAt = null,
        ?string $updatedAt = null,
        ?string $lastRunAt = null,
        ?string $nextRunAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->name = $name;
        $this->message = $message;
        $this->senderNameId = $senderNameId;
        $this->scheduledDate = $scheduledDate;
        $this->status = $status;
        $this->isRecurring = $isRecurring;
        $this->recurrencePattern = $recurrencePattern;
        $this->recurrenceConfig = $recurrenceConfig;
        $this->recipientsType = $recipientsType;
        $this->recipientsData = $recipientsData;

        $now = date('Y-m-d H:i:s');
        $this->createdAt = $createdAt ?? $now;
        $this->updatedAt = $updatedAt ?? $now;
        $this->lastRunAt = $lastRunAt;
        $this->nextRunAt = $nextRunAt ?? $scheduledDate;
    }

    public static function fromPDO(PDO $pdo, array $data): self
    {
        return new self(
            (int)$data['id'],
            (int)$data['user_id'],
            $data['name'],
            $data['message'],
            (int)$data['sender_name_id'],
            $data['scheduled_date'],
            $data['status'],
            (bool)$data['is_recurring'],
            $data['recurrence_pattern'],
            $data['recurrence_config'],
            $data['recipients_type'],
            $data['recipients_data'],
            $data['created_at'],
            $data['updated_at'],
            $data['last_run_at'],
            $data['next_run_at']
        );
    }

    // Getters
    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getSenderNameId(): int
    {
        return $this->senderNameId;
    }

    public function getScheduledDate(): string
    {
        return $this->scheduledDate;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isRecurring(): bool
    {
        return $this->isRecurring;
    }

    public function getRecurrencePattern(): ?string
    {
        return $this->recurrencePattern;
    }

    public function getRecurrenceConfig(): ?string
    {
        return $this->recurrenceConfig;
    }

    public function getRecipientsType(): string
    {
        return $this->recipientsType;
    }

    public function getRecipientsData(): string
    {
        return $this->recipientsData;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function getLastRunAt(): ?string
    {
        return $this->lastRunAt;
    }

    public function getNextRunAt(): ?string
    {
        return $this->nextRunAt;
    }

    // Setters
    public function setName(string $name): void
    {
        $this->name = $name;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function setSenderNameId(int $senderNameId): void
    {
        $this->senderNameId = $senderNameId;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function setScheduledDate(string $scheduledDate): void
    {
        $this->scheduledDate = $scheduledDate;

        // Si ce n'est pas récurrent, la prochaine exécution est la date planifiée
        if (!$this->isRecurring) {
            $this->nextRunAt = $scheduledDate;
        }

        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function setIsRecurring(bool $isRecurring): void
    {
        $this->isRecurring = $isRecurring;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function setRecurrencePattern(?string $recurrencePattern): void
    {
        $this->recurrencePattern = $recurrencePattern;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function setRecurrenceConfig(?string $recurrenceConfig): void
    {
        $this->recurrenceConfig = $recurrenceConfig;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function setRecipientsType(string $recipientsType): void
    {
        $this->recipientsType = $recipientsType;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function setRecipientsData(string $recipientsData): void
    {
        $this->recipientsData = $recipientsData;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function setLastRunAt(?string $lastRunAt): void
    {
        $this->lastRunAt = $lastRunAt;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function setNextRunAt(?string $nextRunAt): void
    {
        $this->nextRunAt = $nextRunAt;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    // Méthodes utilitaires
    public function getRecipientsDataAsArray(): array
    {
        try {
            return json_decode($this->recipientsData, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return [];
        }
    }

    public function getRecurrenceConfigAsArray(): ?array
    {
        if (!$this->recurrenceConfig) {
            return null;
        }

        try {
            return json_decode($this->recurrenceConfig, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return null;
        }
    }

    public function calculateNextRunDate(): ?string
    {
        if (!$this->isRecurring || !$this->recurrencePattern || !$this->recurrenceConfig) {
            return null;
        }

        $config = $this->getRecurrenceConfigAsArray();
        if (!$config) {
            return null;
        }

        $lastRun = $this->lastRunAt ? new DateTime($this->lastRunAt) : new DateTime($this->scheduledDate);
        $nextRun = clone $lastRun;

        switch ($this->recurrencePattern) {
            case 'daily':
                $interval = $config['interval'] ?? 1;
                $nextRun->modify("+{$interval} day");
                break;
            case 'weekly':
                $interval = $config['interval'] ?? 1;
                $nextRun->modify("+{$interval} week");
                break;
            case 'monthly':
                $interval = $config['interval'] ?? 1;
                $nextRun->modify("+{$interval} month");
                break;
            case 'custom':
                // Pour les configurations personnalisées, on utilise l'expression cron
                if (isset($config['cron'])) {
                    // Implémentation simplifiée - dans un cas réel, on utiliserait une bibliothèque cron
                    // comme cron-expression-parser
                    $nextRun->modify("+1 day"); // Exemple simplifié
                }
                break;
        }

        return $nextRun->format('Y-m-d H:i:s');
    }

    public function isDue(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $now = new DateTime();
        $nextRun = new DateTime($this->nextRunAt);

        return $now >= $nextRun;
    }
}
