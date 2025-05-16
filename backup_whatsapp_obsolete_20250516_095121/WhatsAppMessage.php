<?php

namespace App\Entities\WhatsApp;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;

#[Entity]
#[Table(name: "whatsapp_messages")]
class WhatsAppMessage
{
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue]
    private int $id;

    #[Column(type: "string", unique: true)]
    private string $messageId;

    #[Column(type: "string")]
    private string $sender;

    #[Column(type: "string", nullable: true)]
    private ?string $recipient;

    #[Column(type: "integer")]
    private int $timestamp;

    #[Column(type: "string")]
    private string $type;

    #[Column(type: "text", nullable: true)]
    private ?string $content;

    #[Column(type: "text")]
    private string $rawData;

    #[Column(type: "integer")]
    private int $createdAt;

    #[Column(type: "string", nullable: true)]
    private ?string $mediaUrl;

    #[Column(type: "string", nullable: true)]
    private ?string $mediaType;

    #[Column(type: "string", nullable: true)]
    private ?string $status;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->createdAt = time();
    }

    /**
     * Obtenir l'identifiant
     * 
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Définir l'identifiant du message WhatsApp
     * 
     * @param string $messageId
     * @return self
     */
    public function setMessageId(string $messageId): self
    {
        $this->messageId = $messageId;
        return $this;
    }

    /**
     * Obtenir l'identifiant du message WhatsApp
     * 
     * @return string
     */
    public function getMessageId(): string
    {
        return $this->messageId;
    }

    /**
     * Définir l'expéditeur
     * 
     * @param string $sender
     * @return self
     */
    public function setSender(string $sender): self
    {
        $this->sender = $sender;
        return $this;
    }

    /**
     * Obtenir l'expéditeur
     * 
     * @return string
     */
    public function getSender(): string
    {
        return $this->sender;
    }

    /**
     * Définir le destinataire
     * 
     * @param string|null $recipient
     * @return self
     */
    public function setRecipient(?string $recipient): self
    {
        $this->recipient = $recipient;
        return $this;
    }

    /**
     * Obtenir le destinataire
     * 
     * @return string|null
     */
    public function getRecipient(): ?string
    {
        return $this->recipient;
    }

    /**
     * Définir le timestamp
     * 
     * @param int $timestamp
     * @return self
     */
    public function setTimestamp(int $timestamp): self
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    /**
     * Obtenir le timestamp
     * 
     * @return int
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * Définir le type de message
     * 
     * @param string $type
     * @return self
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Obtenir le type de message
     * 
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Définir le contenu du message
     * 
     * @param string|null $content
     * @return self
     */
    public function setContent(?string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Obtenir le contenu du message
     * 
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * Définir les données brutes
     * 
     * @param string $rawData
     * @return self
     */
    public function setRawData(string $rawData): self
    {
        $this->rawData = $rawData;
        return $this;
    }

    /**
     * Obtenir les données brutes
     * 
     * @return string
     */
    public function getRawData(): string
    {
        return $this->rawData;
    }

    /**
     * Définir l'URL du média
     * 
     * @param string|null $mediaUrl
     * @return self
     */
    public function setMediaUrl(?string $mediaUrl): self
    {
        $this->mediaUrl = $mediaUrl;
        return $this;
    }

    /**
     * Obtenir l'URL du média
     * 
     * @return string|null
     */
    public function getMediaUrl(): ?string
    {
        return $this->mediaUrl;
    }

    /**
     * Définir le type de média
     * 
     * @param string|null $mediaType
     * @return self
     */
    public function setMediaType(?string $mediaType): self
    {
        $this->mediaType = $mediaType;
        return $this;
    }

    /**
     * Obtenir le type de média
     * 
     * @return string|null
     */
    public function getMediaType(): ?string
    {
        return $this->mediaType;
    }

    /**
     * Définir le statut du message
     * 
     * @param string|null $status
     * @return self
     */
    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Obtenir le statut du message
     * 
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * Obtenir la date de création
     * 
     * @return int
     */
    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }
}