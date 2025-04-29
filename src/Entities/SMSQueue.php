<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="sms_queue", indexes={
 *     @ORM\Index(name="idx_sms_queue_status", columns={"status"}),
 *     @ORM\Index(name="idx_sms_queue_next_attempt", columns={"next_attempt_at"}),
 *     @ORM\Index(name="idx_sms_queue_user_id", columns={"user_id"}),
 *     @ORM\Index(name="idx_sms_queue_segment_id", columns={"segment_id"})
 * })
 */
class SMSQueue
{
    /**
     * Status constants
     */
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_PROCESSING = 'PROCESSING';
    public const STATUS_SENT = 'SENT';
    public const STATUS_FAILED = 'FAILED';
    public const STATUS_CANCELLED = 'CANCELLED';

    /**
     * Priority constants
     */
    public const PRIORITY_HIGH = 10;
    public const PRIORITY_NORMAL = 5;
    public const PRIORITY_LOW = 0;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $phoneNumber;

    /**
     * @ORM\Column(type="text")
     */
    private $message;

    /**
     * @ORM\Column(name="user_id", type="integer", nullable=true)
     */
    private $userId;

    /**
     * @ORM\Column(name="segment_id", type="integer", nullable=true)
     */
    private $segmentId;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $status = self::STATUS_PENDING;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(name="last_attempt_at", type="datetime", nullable=true)
     */
    private $lastAttemptAt;

    /**
     * @ORM\Column(name="next_attempt_at", type="datetime", nullable=true)
     */
    private $nextAttemptAt;

    /**
     * @ORM\Column(type="integer")
     */
    private $attempts = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $priority = self::PRIORITY_NORMAL;

    /**
     * @ORM\Column(name="error_message", type="text", nullable=true)
     */
    private $errorMessage;

    /**
     * @ORM\Column(name="message_id", type="string", length=255, nullable=true)
     */
    private $messageId;

    /**
     * @ORM\Column(name="sender_name", type="string", length=255, nullable=true)
     */
    private $senderName;

    /**
     * @ORM\Column(name="sender_address", type="string", length=255, nullable=true)
     */
    private $senderAddress;

    /**
     * @ORM\Column(name="batch_id", type="string", length=255, nullable=true)
     */
    private $batchId;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->nextAttemptAt = new \DateTime(); // Default to now
    }

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }
    
    /**
     * Set ID
     *
     * @param int $id
     * @return self
     */
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get phone number
     *
     * @return string
     */
    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    /**
     * Set phone number
     *
     * @param string $phoneNumber
     * @return $this
     */
    public function setPhoneNumber(string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return $this
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Get user ID
     *
     * @return int|null
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * Set user ID
     *
     * @param int|null $userId
     * @return $this
     */
    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * Get segment ID
     *
     * @return int|null
     */
    public function getSegmentId(): ?int
    {
        return $this->segmentId;
    }

    /**
     * Set segment ID
     *
     * @param int|null $segmentId
     * @return $this
     */
    public function setSegmentId(?int $segmentId): self
    {
        $this->segmentId = $segmentId;
        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Get created at
     *
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * Set created at
     *
     * @param \DateTime $createdAt
     * @return $this
     */
    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Get last attempt at
     *
     * @return \DateTime|null
     */
    public function getLastAttemptAt(): ?\DateTime
    {
        return $this->lastAttemptAt;
    }

    /**
     * Set last attempt at
     *
     * @param \DateTime|null $lastAttemptAt
     * @return $this
     */
    public function setLastAttemptAt(?\DateTime $lastAttemptAt): self
    {
        $this->lastAttemptAt = $lastAttemptAt;
        return $this;
    }

    /**
     * Get next attempt at
     *
     * @return \DateTime|null
     */
    public function getNextAttemptAt(): ?\DateTime
    {
        return $this->nextAttemptAt;
    }

    /**
     * Set next attempt at
     *
     * @param \DateTime|null $nextAttemptAt
     * @return $this
     */
    public function setNextAttemptAt(?\DateTime $nextAttemptAt): self
    {
        $this->nextAttemptAt = $nextAttemptAt;
        return $this;
    }

    /**
     * Get attempts
     *
     * @return int
     */
    public function getAttempts(): int
    {
        return $this->attempts;
    }

    /**
     * Set attempts
     *
     * @param int $attempts
     * @return $this
     */
    public function setAttempts(int $attempts): self
    {
        $this->attempts = $attempts;
        return $this;
    }

    /**
     * Increment attempts
     *
     * @return $this
     */
    public function incrementAttempts(): self
    {
        $this->attempts++;
        return $this;
    }

    /**
     * Get priority
     *
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Set priority
     *
     * @param int $priority
     * @return $this
     */
    public function setPriority(int $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * Get error message
     *
     * @return string|null
     */
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /**
     * Set error message
     *
     * @param string|null $errorMessage
     * @return $this
     */
    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    /**
     * Get message ID
     *
     * @return string|null
     */
    public function getMessageId(): ?string
    {
        return $this->messageId;
    }

    /**
     * Set message ID
     *
     * @param string|null $messageId
     * @return $this
     */
    public function setMessageId(?string $messageId): self
    {
        $this->messageId = $messageId;
        return $this;
    }

    /**
     * Get sender name
     *
     * @return string|null
     */
    public function getSenderName(): ?string
    {
        return $this->senderName;
    }

    /**
     * Set sender name
     *
     * @param string|null $senderName
     * @return $this
     */
    public function setSenderName(?string $senderName): self
    {
        $this->senderName = $senderName;
        return $this;
    }

    /**
     * Get sender address
     *
     * @return string|null
     */
    public function getSenderAddress(): ?string
    {
        return $this->senderAddress;
    }

    /**
     * Set sender address
     *
     * @param string|null $senderAddress
     * @return $this
     */
    public function setSenderAddress(?string $senderAddress): self
    {
        $this->senderAddress = $senderAddress;
        return $this;
    }

    /**
     * Get batch ID
     *
     * @return string|null
     */
    public function getBatchId(): ?string
    {
        return $this->batchId;
    }

    /**
     * Set batch ID
     *
     * @param string|null $batchId
     * @return $this
     */
    public function setBatchId(?string $batchId): self
    {
        $this->batchId = $batchId;
        return $this;
    }

    /**
     * Calculate next attempt time based on exponential backoff
     * 
     * @param int $maxAttempts
     * @return \DateTime|null
     */
    public function calculateNextAttemptTime(int $maxAttempts = 5): ?\DateTime
    {
        if ($this->attempts >= $maxAttempts) {
            return null; // No more attempts
        }

        // Exponential backoff: 1min, 5min, 15min, 30min, 60min
        $delayMinutes = min(pow(3, $this->attempts), 60);
        
        $nextAttempt = new \DateTime();
        $nextAttempt->modify("+{$delayMinutes} minutes");
        
        return $nextAttempt;
    }

    /**
     * Mark as processing
     * 
     * @return $this
     */
    public function markAsProcessing(): self
    {
        $this->lastAttemptAt = new \DateTime();
        $this->status = self::STATUS_PROCESSING;
        return $this;
    }

    /**
     * Mark as sent
     * 
     * @param string|null $messageId
     * @return $this
     */
    public function markAsSent(?string $messageId = null): self
    {
        $this->lastAttemptAt = new \DateTime();
        $this->status = self::STATUS_SENT;
        if ($messageId !== null) {
            $this->messageId = $messageId;
        }
        return $this;
    }

    /**
     * Mark as failed
     * 
     * @param string|null $errorMessage
     * @param int $maxAttempts
     * @return $this
     */
    public function markAsFailed(?string $errorMessage = null, int $maxAttempts = 5): self
    {
        $this->lastAttemptAt = new \DateTime();
        $this->incrementAttempts();
        $this->nextAttemptAt = $this->calculateNextAttemptTime($maxAttempts);
        
        if ($errorMessage !== null) {
            $this->errorMessage = $errorMessage;
        }
        
        if ($this->nextAttemptAt === null) {
            $this->status = self::STATUS_FAILED; // Permanently failed
        } else {
            $this->status = self::STATUS_PENDING; // Will retry
        }
        
        return $this;
    }

    /**
     * Mark as cancelled
     * 
     * @param string|null $reason
     * @return $this
     */
    public function markAsCancelled(?string $reason = null): self
    {
        $this->status = self::STATUS_CANCELLED;
        if ($reason !== null) {
            $this->errorMessage = $reason;
        }
        return $this;
    }
}