<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * Type GraphQL pour les insights WhatsApp d'un contact
 */
#[Type]
class WhatsAppContactInsightsType
{
    public function __construct(
        private int $totalMessages,
        private int $outgoingMessages,
        private int $incomingMessages,
        private int $deliveredMessages,
        private int $readMessages,
        private int $failedMessages,
        private ?string $lastMessageDate,
        private ?string $lastMessageType,
        private ?string $lastMessageContent,
        private array $templatesUsed,
        private int $conversationCount,
        private array $messagesByType,
        private array $messagesByStatus,
        private array $messagesByMonth,
        private float $deliveryRate,
        private float $readRate
    ) {}

    #[Field]
    public function getTotalMessages(): int
    {
        return $this->totalMessages;
    }

    #[Field]
    public function getOutgoingMessages(): int
    {
        return $this->outgoingMessages;
    }

    #[Field]
    public function getIncomingMessages(): int
    {
        return $this->incomingMessages;
    }

    #[Field]
    public function getDeliveredMessages(): int
    {
        return $this->deliveredMessages;
    }

    #[Field]
    public function getReadMessages(): int
    {
        return $this->readMessages;
    }

    #[Field]
    public function getFailedMessages(): int
    {
        return $this->failedMessages;
    }

    #[Field]
    public function getLastMessageDate(): ?string
    {
        return $this->lastMessageDate;
    }

    #[Field]
    public function getLastMessageType(): ?string
    {
        return $this->lastMessageType;
    }

    #[Field]
    public function getLastMessageContent(): ?string
    {
        return $this->lastMessageContent;
    }

    /**
     * @return string[]
     */
    #[Field]
    public function getTemplatesUsed(): array
    {
        return $this->templatesUsed;
    }

    #[Field]
    public function getConversationCount(): int
    {
        return $this->conversationCount;
    }

    /**
     * @return array<string, int>
     */
    #[Field]
    public function getMessagesByType(): array
    {
        return $this->messagesByType;
    }

    /**
     * @return array<string, int>
     */
    #[Field]
    public function getMessagesByStatus(): array
    {
        return $this->messagesByStatus;
    }

    /**
     * @return array<string, int>
     */
    #[Field]
    public function getMessagesByMonth(): array
    {
        return $this->messagesByMonth;
    }

    #[Field]
    public function getDeliveryRate(): float
    {
        return $this->deliveryRate;
    }

    #[Field]
    public function getReadRate(): float
    {
        return $this->readRate;
    }
}