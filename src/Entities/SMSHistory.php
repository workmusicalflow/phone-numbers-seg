<?php

namespace App\Entities;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

/**
 * SMSHistory entity
 * 
 * This entity represents an SMS history record in the system.
 */
#[Entity(repositoryClass: "App\Repositories\Doctrine\SMSHistoryRepository")]
#[Table(name: "sms_history")]
class SMSHistory
{
    #[Id]
    #[GeneratedValue]
    #[Column(type: "integer")]
    private ?int $id = null;

    #[Column(name: "phone_number_id", type: "integer", nullable: true)]
    private ?int $phoneNumberId = null;

    #[Column(name: "phone_number", type: "string", length: 255)]
    private string $phoneNumber;

    #[Column(type: "text")]
    private string $message;

    #[Column(type: "string", length: 50)]
    private string $status;

    #[Column(name: "message_id", type: "string", length: 255, nullable: true)]
    private ?string $messageId = null;

    #[Column(name: "error_message", type: "text", nullable: true)]
    private ?string $errorMessage = null;

    #[Column(name: "sender_address", type: "string", length: 255)]
    private string $senderAddress;

    #[Column(name: "sender_name", type: "string", length: 255)]
    private string $senderName;

    #[Column(name: "segment_id", type: "integer", nullable: true)]
    private ?int $segmentId = null;

    #[Column(name: "user_id", type: "integer", nullable: true)]
    private ?int $userId = null;

    #[Column(name: "created_at", type: "datetime")]
    private \DateTime $createdAt;

    #[Column(name: "sent_at", type: "datetime", nullable: true)]
    private ?\DateTime $sentAt = null;

    #[Column(name: "delivered_at", type: "datetime", nullable: true)]
    private ?\DateTime $deliveredAt = null;

    #[Column(name: "failed_at", type: "datetime", nullable: true)]
    private ?\DateTime $failedAt = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * Get the ID
     * 
     * @return int|null The ID
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set the ID
     * 
     * @param int|null $id The ID
     * @return self
     */
    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get the phone number ID
     * 
     * @return int|null The phone number ID
     */
    public function getPhoneNumberId(): ?int
    {
        return $this->phoneNumberId;
    }

    /**
     * Set the phone number ID
     * 
     * @param int|null $phoneNumberId The phone number ID
     * @return self
     */
    public function setPhoneNumberId(?int $phoneNumberId): self
    {
        $this->phoneNumberId = $phoneNumberId;
        return $this;
    }

    /**
     * Get the phone number
     * 
     * @return string The phone number
     */
    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    /**
     * Set the phone number
     * 
     * @param string $phoneNumber The phone number
     * @return self
     */
    public function setPhoneNumber(string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    /**
     * Get the message
     * 
     * @return string The message
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Set the message
     * 
     * @param string $message The message
     * @return self
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Get the status
     * 
     * @return string The status
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Set the status
     * 
     * @param string $status The status
     * @return self
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Get the message ID
     * 
     * @return string|null The message ID
     */
    public function getMessageId(): ?string
    {
        return $this->messageId;
    }

    /**
     * Set the message ID
     * 
     * @param string|null $messageId The message ID
     * @return self
     */
    public function setMessageId(?string $messageId): self
    {
        $this->messageId = $messageId;
        return $this;
    }

    /**
     * Get the error message
     * 
     * @return string|null The error message
     */
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /**
     * Set the error message
     * 
     * @param string|null $errorMessage The error message
     * @return self
     */
    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    /**
     * Get the sender address
     * 
     * @return string The sender address
     */
    public function getSenderAddress(): string
    {
        return $this->senderAddress;
    }

    /**
     * Set the sender address
     * 
     * @param string $senderAddress The sender address
     * @return self
     */
    public function setSenderAddress(string $senderAddress): self
    {
        $this->senderAddress = $senderAddress;
        return $this;
    }

    /**
     * Get the sender name
     * 
     * @return string The sender name
     */
    public function getSenderName(): string
    {
        return $this->senderName;
    }

    /**
     * Set the sender name
     * 
     * @param string $senderName The sender name
     * @return self
     */
    public function setSenderName(string $senderName): self
    {
        $this->senderName = $senderName;
        return $this;
    }

    /**
     * Get the segment ID
     * 
     * @return int|null The segment ID
     */
    public function getSegmentId(): ?int
    {
        return $this->segmentId;
    }

    /**
     * Set the segment ID
     * 
     * @param int|null $segmentId The segment ID
     * @return self
     */
    public function setSegmentId(?int $segmentId): self
    {
        $this->segmentId = $segmentId;
        return $this;
    }

    /**
     * Get the user ID
     * 
     * @return int|null The user ID
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * Set the user ID
     * 
     * @param int|null $userId The user ID
     * @return self
     */
    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * Get the created at date
     * 
     * @return \DateTime The created at date
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * Set the created at date
     * 
     * @param \DateTime $createdAt The created at date
     * @return self
     */
    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Get the sent at date
     * 
     * @return \DateTime|null The sent at date
     */
    public function getSentAt(): ?\DateTime
    {
        return $this->sentAt;
    }

    /**
     * Set the sent at date
     * 
     * @param \DateTime|null $sentAt The sent at date
     * @return self
     */
    public function setSentAt(?\DateTime $sentAt): self
    {
        $this->sentAt = $sentAt;
        return $this;
    }

    /**
     * Get the delivered at date
     * 
     * @return \DateTime|null The delivered at date
     */
    public function getDeliveredAt(): ?\DateTime
    {
        return $this->deliveredAt;
    }

    /**
     * Set the delivered at date
     * 
     * @param \DateTime|null $deliveredAt The delivered at date
     * @return self
     */
    public function setDeliveredAt(?\DateTime $deliveredAt): self
    {
        $this->deliveredAt = $deliveredAt;
        return $this;
    }

    /**
     * Get the failed at date
     * 
     * @return \DateTime|null The failed at date
     */
    public function getFailedAt(): ?\DateTime
    {
        return $this->failedAt;
    }

    /**
     * Set the failed at date
     * 
     * @param \DateTime|null $failedAt The failed at date
     * @return self
     */
    public function setFailedAt(?\DateTime $failedAt): self
    {
        $this->failedAt = $failedAt;
        return $this;
    }

    /**
     * Convert the entity to an array
     * 
     * @return array The entity as an array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'phoneNumberId' => $this->phoneNumberId,
            'phoneNumber' => $this->phoneNumber,
            'message' => $this->message,
            'status' => $this->status,
            'messageId' => $this->messageId,
            'errorMessage' => $this->errorMessage,
            'senderAddress' => $this->senderAddress,
            'senderName' => $this->senderName,
            'segmentId' => $this->segmentId,
            'userId' => $this->userId,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'sentAt' => $this->sentAt ? $this->sentAt->format('Y-m-d H:i:s') : null,
            'deliveredAt' => $this->deliveredAt ? $this->deliveredAt->format('Y-m-d H:i:s') : null,
            'failedAt' => $this->failedAt ? $this->failedAt->format('Y-m-d H:i:s') : null
        ];
    }
}
