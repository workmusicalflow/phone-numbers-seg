<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Events;

/**
 * Événement émis après le traitement d'un batch dans un envoi en masse
 */
class BatchProcessedEvent extends AbstractEvent
{
    public function __construct(
        private readonly int $batchNumber,
        private readonly int $totalBatches,
        private readonly int $successful,
        private readonly int $failed
    ) {
        parent::__construct();
    }

    public function getBatchNumber(): int
    {
        return $this->batchNumber;
    }

    public function getTotalBatches(): int
    {
        return $this->totalBatches;
    }

    public function getSuccessful(): int
    {
        return $this->successful;
    }

    public function getFailed(): int
    {
        return $this->failed;
    }

    public function getBatchSize(): int
    {
        return $this->successful + $this->failed;
    }

    public function isLastBatch(): bool
    {
        return $this->batchNumber === $this->totalBatches;
    }

    public function getName(): string
    {
        return 'bulk_send.batch_processed';
    }

    public function getData(): array
    {
        return [
            'batchNumber' => $this->batchNumber,
            'totalBatches' => $this->totalBatches,
            'successful' => $this->successful,
            'failed' => $this->failed,
            'batchSize' => $this->getBatchSize(),
            'isLastBatch' => $this->isLastBatch(),
            'timestamp' => $this->getOccurredAt()->format('c')
        ];
    }
}