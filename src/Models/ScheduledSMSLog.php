<?php

namespace App\Models;

use PDO;

class ScheduledSMSLog
{
    private int $id;
    private int $scheduledSmsId;
    private string $executionDate;
    private string $status;
    private int $totalRecipients;
    private int $successfulSends;
    private int $failedSends;
    private ?string $errorDetails;
    private string $createdAt;

    public function __construct(
        int $id,
        int $scheduledSmsId,
        string $executionDate,
        string $status,
        int $totalRecipients,
        int $successfulSends,
        int $failedSends,
        ?string $errorDetails = null,
        ?string $createdAt = null
    ) {
        $this->id = $id;
        $this->scheduledSmsId = $scheduledSmsId;
        $this->executionDate = $executionDate;
        $this->status = $status;
        $this->totalRecipients = $totalRecipients;
        $this->successfulSends = $successfulSends;
        $this->failedSends = $failedSends;
        $this->errorDetails = $errorDetails;
        $this->createdAt = $createdAt ?? date('Y-m-d H:i:s');
    }

    public static function fromPDO(PDO $pdo, array $data): self
    {
        return new self(
            (int)$data['id'],
            (int)$data['scheduled_sms_id'],
            $data['execution_date'],
            $data['status'],
            (int)$data['total_recipients'],
            (int)$data['successful_sends'],
            (int)$data['failed_sends'],
            $data['error_details'],
            $data['created_at']
        );
    }

    // Getters
    public function getId(): int
    {
        return $this->id;
    }

    public function getScheduledSmsId(): int
    {
        return $this->scheduledSmsId;
    }

    public function getExecutionDate(): string
    {
        return $this->executionDate;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getTotalRecipients(): int
    {
        return $this->totalRecipients;
    }

    public function getSuccessfulSends(): int
    {
        return $this->successfulSends;
    }

    public function getFailedSends(): int
    {
        return $this->failedSends;
    }

    public function getErrorDetails(): ?string
    {
        return $this->errorDetails;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    // Méthodes utilitaires
    public function getSuccessRate(): float
    {
        if ($this->totalRecipients === 0) {
            return 0.0;
        }

        return ($this->successfulSends / $this->totalRecipients) * 100;
    }

    public function isFullySuccessful(): bool
    {
        return $this->status === 'success' && $this->failedSends === 0;
    }

    public function isPartiallySuccessful(): bool
    {
        return $this->status === 'partial_success' && $this->successfulSends > 0 && $this->failedSends > 0;
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed' || $this->successfulSends === 0;
    }
}
