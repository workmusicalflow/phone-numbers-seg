<?php

declare(strict_types=1);

namespace App\Entities\WhatsApp;

use App\Entities\User;
use App\Entities\Contact;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "whatsapp_template_history")]
#[ORM\HasLifecycleCallbacks]
class WhatsAppTemplateHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string")]
    private string $templateId;

    #[ORM\Column(type: "string")]
    private string $templateName;

    #[ORM\Column(type: "string")]
    private string $language;

    #[ORM\Column(type: "string")]
    private string $category;

    #[ORM\Column(type: "string")]
    private string $phoneNumber;

    #[ORM\Column(type: "json", nullable: true)]
    private ?array $parameters = null;

    #[ORM\Column(type: "string", nullable: true)]
    private ?string $headerMediaType = null;

    #[ORM\Column(type: "string", nullable: true)]
    private ?string $headerMediaUrl = null;

    #[ORM\Column(type: "string", nullable: true)]
    private ?string $headerMediaId = null;

    #[ORM\Column(type: "json", nullable: true)]
    private ?array $buttonValues = null;

    #[ORM\Column(type: "string", nullable: true)]
    private ?string $wabaMessageId = null;

    #[ORM\Column(type: "string")]
    private string $status;

    #[ORM\Column(type: "datetime")]
    private \DateTime $usedAt;

    #[ORM\Column(type: "datetime")]
    private \DateTime $createdAt;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTime $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "oracle_user_id", nullable: false)]
    private User $oracleUser;

    #[ORM\ManyToOne(targetEntity: Contact::class)]
    #[ORM\JoinColumn(name: "contact_id", nullable: true)]
    private ?Contact $contact = null;

    #[ORM\ManyToOne(targetEntity: WhatsAppTemplate::class)]
    #[ORM\JoinColumn(name: "template_entity_id", nullable: true)]
    private ?WhatsAppTemplate $template = null;

    #[ORM\ManyToOne(targetEntity: WhatsAppMessageHistory::class)]
    #[ORM\JoinColumn(name: "message_history_id", nullable: true)]
    private ?WhatsAppMessageHistory $messageHistory = null;

    public function __construct()
    {
        $this->usedAt = new \DateTime();
        $this->createdAt = new \DateTime();
        $this->status = 'sent';
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

    public function getTemplateId(): string
    {
        return $this->templateId;
    }

    public function setTemplateId(string $templateId): self
    {
        $this->templateId = $templateId;
        return $this;
    }

    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    public function setTemplateName(string $templateName): self
    {
        $this->templateName = $templateName;
        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): self
    {
        $this->language = $language;
        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;
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

    public function getParameters(): ?array
    {
        return $this->parameters;
    }

    public function setParameters(?array $parameters): self
    {
        $this->parameters = $parameters;
        return $this;
    }

    public function getHeaderMediaType(): ?string
    {
        return $this->headerMediaType;
    }

    public function setHeaderMediaType(?string $headerMediaType): self
    {
        $this->headerMediaType = $headerMediaType;
        return $this;
    }

    public function getHeaderMediaUrl(): ?string
    {
        return $this->headerMediaUrl;
    }

    public function setHeaderMediaUrl(?string $headerMediaUrl): self
    {
        $this->headerMediaUrl = $headerMediaUrl;
        return $this;
    }

    public function getHeaderMediaId(): ?string
    {
        return $this->headerMediaId;
    }

    public function setHeaderMediaId(?string $headerMediaId): self
    {
        $this->headerMediaId = $headerMediaId;
        return $this;
    }

    public function getButtonValues(): ?array
    {
        return $this->buttonValues;
    }

    public function setButtonValues(?array $buttonValues): self
    {
        $this->buttonValues = $buttonValues;
        return $this;
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getUsedAt(): \DateTime
    {
        return $this->usedAt;
    }

    public function setUsedAt(\DateTime $usedAt): self
    {
        $this->usedAt = $usedAt;
        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getOracleUser(): User
    {
        return $this->oracleUser;
    }

    public function setOracleUser(User $oracleUser): self
    {
        $this->oracleUser = $oracleUser;
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

    public function getTemplate(): ?WhatsAppTemplate
    {
        return $this->template;
    }

    public function setTemplate(?WhatsAppTemplate $template): self
    {
        $this->template = $template;
        return $this;
    }

    public function getMessageHistory(): ?WhatsAppMessageHistory
    {
        return $this->messageHistory;
    }

    public function setMessageHistory(?WhatsAppMessageHistory $messageHistory): self
    {
        $this->messageHistory = $messageHistory;
        return $this;
    }
}