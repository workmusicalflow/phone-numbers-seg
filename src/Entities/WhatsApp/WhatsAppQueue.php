<?php

declare(strict_types=1);

namespace App\Entities\WhatsApp;

use App\Entities\User;
use Doctrine\ORM\Mapping as ORM;
use DateTime;

#[ORM\Entity]
#[ORM\Table(name: "whatsapp_queue")]
#[ORM\HasLifecycleCallbacks]
class WhatsAppQueue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;
    
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "oracle_user_id", referencedColumnName: "id", nullable: false)]
    private User $oracleUser;
    
    #[ORM\Column(name: "recipient_phone", type: "string", length: 255)]
    private string $recipientPhone;
    
    #[ORM\Column(name: "message_type", type: "string", length: 255)]
    private string $messageType;
    
    #[ORM\Column(name: "message_content", type: "text")]
    private string $messageContent;
    
    #[ORM\Column(name: "templateName", type: "string", length: 255, nullable: true)]
    private ?string $templateName = null;
    
    #[ORM\Column(name: "templateLanguage", type: "string", length: 255, nullable: true)]
    private ?string $templateLanguage = null;
    
    #[ORM\Column(type: "string", length: 255)]
    private string $status = 'PENDING';
    
    #[ORM\Column(type: "integer")]
    private int $priority = 5;
    
    #[ORM\Column(type: "integer")]
    private int $attempts = 0;
    
    #[ORM\Column(name: "maxAttempts", type: "integer")]
    private int $maxAttempts = 3;
    
    #[ORM\Column(name: "errorMessage", type: "text", nullable: true)]
    private ?string $errorMessage = null;
    
    #[ORM\Column(name: "wabaMessageId", type: "string", length: 255, nullable: true)]
    private ?string $wabaMessageId = null;
    
    #[ORM\Column(name: "scheduled_at", type: "datetime")]
    private DateTime $scheduledAt;
    
    #[ORM\Column(name: "sentAt", type: "datetime", nullable: true)]
    private ?DateTime $sentAt = null;
    
    #[ORM\Column(name: "createdAt", type: "datetime")]
    private DateTime $createdAt;
    
    #[ORM\Column(name: "updatedAt", type: "datetime", nullable: true)]
    private ?DateTime $updatedAt = null;
    
    #[ORM\Column(type: "text", nullable: true)]
    private ?string $metadata = null;
    
    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->scheduledAt = new DateTime();
    }
    
    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTime();
    }
    
    // Getters and setters
    public function getId(): int
    {
        return $this->id;
    }
    
    public function getOracleUser(): User
    {
        return $this->oracleUser;
    }
    
    public function setOracleUser(User $oracleUser): void
    {
        $this->oracleUser = $oracleUser;
    }
    
    public function getRecipientPhone(): string
    {
        return $this->recipientPhone;
    }
    
    public function setRecipientPhone(string $recipientPhone): void
    {
        $this->recipientPhone = $recipientPhone;
    }
    
    public function getMessageType(): string
    {
        return $this->messageType;
    }
    
    public function setMessageType(string $messageType): void
    {
        $this->messageType = $messageType;
    }
    
    public function getMessageContent(): string
    {
        return $this->messageContent;
    }
    
    public function setMessageContent(string $messageContent): void
    {
        $this->messageContent = $messageContent;
    }
    
    public function getTemplateName(): ?string
    {
        return $this->templateName;
    }
    
    public function setTemplateName(?string $templateName): void
    {
        $this->templateName = $templateName;
    }
    
    public function getTemplateLanguage(): ?string
    {
        return $this->templateLanguage;
    }
    
    public function setTemplateLanguage(?string $templateLanguage): void
    {
        $this->templateLanguage = $templateLanguage;
    }
    
    public function getStatus(): string
    {
        return $this->status;
    }
    
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }
    
    public function getPriority(): int
    {
        return $this->priority;
    }
    
    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }
    
    public function getAttempts(): int
    {
        return $this->attempts;
    }
    
    public function setAttempts(int $attempts): void
    {
        $this->attempts = $attempts;
    }
    
    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }
    
    public function setMaxAttempts(int $maxAttempts): void
    {
        $this->maxAttempts = $maxAttempts;
    }
    
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }
    
    public function setErrorMessage(?string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
    }
    
    public function getWabaMessageId(): ?string
    {
        return $this->wabaMessageId;
    }
    
    public function setWabaMessageId(?string $wabaMessageId): void
    {
        $this->wabaMessageId = $wabaMessageId;
    }
    
    public function getScheduledAt(): DateTime
    {
        return $this->scheduledAt;
    }
    
    public function setScheduledAt(DateTime $scheduledAt): void
    {
        $this->scheduledAt = $scheduledAt;
    }
    
    public function getSentAt(): ?DateTime
    {
        return $this->sentAt;
    }
    
    public function setSentAt(?DateTime $sentAt): void
    {
        $this->sentAt = $sentAt;
    }
    
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }
    
    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }
    
    public function getMetadata(): ?string
    {
        return $this->metadata;
    }
    
    public function setMetadata(?string $metadata): void
    {
        $this->metadata = $metadata;
    }
}