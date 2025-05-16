<?php

namespace App\Entities\WhatsApp;

use Doctrine\ORM\Mapping as ORM;

#[ORM\HasLifecycleCallbacks]
class WhatsAppMessageExisting
{
    #[ORM\Column()]
    private int $id;
    
    #[ORM\Column()]
    private string $messageId;
    
    #[ORM\Column()]
    private string $sender;
    
    #[ORM\Column()]
    private ?string $recipient = null;
    
    #[ORM\Column()]
    private int $timestamp;
    
    #[ORM\Column()]
    private string $type;
    
    #[ORM\Column()]
    private ?string $content = null;
    
    #[ORM\Column()]
    private string $rawData;
    
    #[ORM\Column()]
    private int $createdAt;
    
    #[ORM\Column()]
    private ?string $mediaUrl = null;
    
    #[ORM\Column()]
    private ?string $mediaType = null;
    
    #[ORM\Column()]
    private ?string $status = null;
    
    public function __construct()
    {
        $this->createdAt = time();
        $this->timestamp = time();
    }
    
    // Getters et setters
    
    public function getId(): int
    {
        return $this->id;
    }
    
    public function getMessageId(): string
    {
        return $this->messageId;
    }
    
    public function setMessageId(string $messageId): self
    {
        $this->messageId = $messageId;
        return $this;
    }
    
    public function getSender(): string
    {
        return $this->sender;
    }
    
    public function setSender(string $sender): self
    {
        $this->sender = $sender;
        return $this;
    }
    
    public function getRecipient(): ?string
    {
        return $this->recipient;
    }
    
    public function setRecipient(?string $recipient): self
    {
        $this->recipient = $recipient;
        return $this;
    }
    
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }
    
    public function setTimestamp(int $timestamp): self
    {
        $this->timestamp = $timestamp;
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
    
    public function getRawData(): string
    {
        return $this->rawData;
    }
    
    public function setRawData(string $rawData): self
    {
        $this->rawData = $rawData;
        return $this;
    }
    
    public function getRawDataAsArray(): array
    {
        return json_decode($this->rawData, true) ?? [];
    }
    
    public function setRawDataFromArray(array $data): self
    {
        $this->rawData = json_encode($data);
        return $this;
    }
    
    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }
    
    public function getMediaUrl(): ?string
    {
        return $this->mediaUrl;
    }
    
    public function setMediaUrl(?string $mediaUrl): self
    {
        $this->mediaUrl = $mediaUrl;
        return $this;
    }
    
    public function getMediaType(): ?string
    {
        return $this->mediaType;
    }
    
    public function setMediaType(?string $mediaType): self
    {
        $this->mediaType = $mediaType;
        return $this;
    }
    
    public function getStatus(): ?string
    {
        return $this->status;
    }
    
    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }
}