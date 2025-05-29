<?php

declare(strict_types=1);

namespace App\Entities\WhatsApp;

use App\Entities\User;
use App\Entities\Contact;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "whatsapp_message_history")]
#[ORM\HasLifecycleCallbacks]
class WhatsAppMessageHistory
{
    // Direction Constants
    public const DIRECTION_OUTBOUND = 'OUTGOING'; // Maintenu pour correspondre à l'usage existant dans WhatsAppService
    public const DIRECTION_INBOUND = 'INCOMING'; // Maintenu pour correspondre à l'usage existant

    // Type Constants
    public const TYPE_TEXT = 'text';
    public const TYPE_TEMPLATE = 'template';
    public const TYPE_IMAGE = 'image';
    public const TYPE_VIDEO = 'video';
    public const TYPE_AUDIO = 'audio';
    public const TYPE_DOCUMENT = 'document';
    public const TYPE_INTERACTIVE = 'interactive';
    // Potentiellement d'autres types si nécessaire: location, contacts, sticker, system, ephemeral, unsupported, etc.

    // Status Constants
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_READ = 'read';
    public const STATUS_FAILED = 'failed';
    public const STATUS_RECEIVED = 'received'; // Pour les messages entrants
    public const STATUS_PENDING = 'pending'; // Si on a une file d'attente avant l'envoi réel
    public const STATUS_DELETED = 'deleted'; // Si les messages peuvent être supprimés
    public const STATUS_UNKNOWN = 'unknown'; // Statut par défaut ou en cas d'erreur

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(name: "wabaMessageId", type: "string", nullable: true)]
    private ?string $wabaMessageId = null;

    #[ORM\Column(name: "phoneNumber", type: "string")]
    private string $phoneNumber;

    #[ORM\Column(type: "string")]
    private string $direction;

    #[ORM\Column(type: "string")]
    private string $type;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $content = null;

    #[ORM\Column(type: "string")]
    private string $status;

    #[ORM\Column(type: "datetime")]
    private \DateTime $timestamp;

    #[ORM\Column(name: "errorCode", type: "integer", nullable: true)]
    private ?int $errorCode = null;

    #[ORM\Column(name: "errorMessage", type: "text", nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(name: "conversationId", type: "string", nullable: true)]
    private ?string $conversationId = null;

    #[ORM\Column(name: "pricingCategory", type: "string", nullable: true)]
    private ?string $pricingCategory = null;

    #[ORM\Column(name: "mediaId", type: "string", nullable: true)]
    private ?string $mediaId = null;

    #[ORM\Column(name: "templateName", type: "string", nullable: true)]
    private ?string $templateName = null;

    #[ORM\Column(name: "templateLanguage", type: "string", nullable: true)]
    private ?string $templateLanguage = null;

    #[ORM\Column(name: "contextData", type: "text", nullable: true)]
    private ?string $contextData = null;

    #[ORM\Column(name: "createdAt", type: "datetime")]
    private \DateTime $createdAt;

    #[ORM\Column(name: "updatedAt", type: "datetime", nullable: true)]
    private ?\DateTime $updatedAt = null;

    #[ORM\Column(type: "json", nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: "json", nullable: true)]
    private ?array $errors = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "oracle_user_id", nullable: false)]
    private User $oracleUser;

    #[ORM\ManyToOne(targetEntity: Contact::class)]
    #[ORM\JoinColumn(name: "contact_id", nullable: true)]
    private ?Contact $contact = null;

    public function __construct()
    {
        $this->timestamp = new \DateTime();
        $this->createdAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    // Getters et setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWabaMessageId(): ?string
    {
        return $this->wabaMessageId;
    }

    public function setWabaMessageId(?string $wabaMessageId): self
    {
        $this->wabaMessageId = $wabaMessageId;
        return $this;
    }

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function setDirection(string $direction): self
    {
        $this->direction = $direction;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }

    public function setTimestamp(\DateTime $timestamp): self
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    public function getErrorCode(): ?int
    {
        return $this->errorCode;
    }

    public function setErrorCode(?int $errorCode): self
    {
        $this->errorCode = $errorCode;
        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    public function getConversationId(): ?string
    {
        return $this->conversationId;
    }

    public function setConversationId(?string $conversationId): self
    {
        $this->conversationId = $conversationId;
        return $this;
    }

    public function getPricingCategory(): ?string
    {
        return $this->pricingCategory;
    }

    public function setPricingCategory(?string $pricingCategory): self
    {
        $this->pricingCategory = $pricingCategory;
        return $this;
    }

    public function getMediaId(): ?string
    {
        return $this->mediaId;
    }

    public function setMediaId(?string $mediaId): self
    {
        $this->mediaId = $mediaId;
        return $this;
    }

    public function getTemplateName(): ?string
    {
        return $this->templateName;
    }

    public function setTemplateName(?string $templateName): self
    {
        $this->templateName = $templateName;
        return $this;
    }

    public function getTemplateLanguage(): ?string
    {
        return $this->templateLanguage;
    }

    public function setTemplateLanguage(?string $templateLanguage): self
    {
        $this->templateLanguage = $templateLanguage;
        return $this;
    }

    public function getContextData(): ?string
    {
        return $this->contextData;
    }

    public function setContextData(?string $contextData): self
    {
        $this->contextData = $contextData;
        return $this;
    }

    public function getContextDataAsArray(): array
    {
        return $this->contextData ? json_decode($this->contextData, true) ?? [] : [];
    }

    public function setContextDataFromArray(array $data): self
    {
        $this->contextData = json_encode($data);
        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function getOracleUser(): User
    {
        return $this->oracleUser;
    }

    public function setOracleUser(User $user): self
    {
        $this->oracleUser = $user;
        return $this;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function setContact(?Contact $contact): self
    {
        $this->contact = $contact;
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getErrors(): ?array
    {
        return $this->errors;
    }

    public function setErrors(?array $errors): self
    {
        $this->errors = $errors;
        return $this;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
