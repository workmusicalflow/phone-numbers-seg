<?php

namespace App\Models;

/**
 * Classe représentant un enregistrement d'historique SMS
 */
class SMSHistory
{
    /**
     * @var int|null ID de l'enregistrement
     */
    private ?int $id;

    /**
     * @var int|null ID du numéro de téléphone associé
     */
    private ?int $phoneNumberId;

    /**
     * @var string Numéro de téléphone
     */
    private string $phoneNumber;

    /**
     * @var string Message envoyé
     */
    private string $message;

    /**
     * @var string Statut de l'envoi (SENT, FAILED, PENDING)
     */
    private string $status;

    /**
     * @var string|null ID du message retourné par l'API
     */
    private ?string $messageId;

    /**
     * @var string|null Message d'erreur en cas d'échec
     */
    private ?string $errorMessage;

    /**
     * @var string Adresse de l'expéditeur
     */
    private string $senderAddress;

    /**
     * @var string Nom de l'expéditeur
     */
    private string $senderName;

    /**
     * @var int|null ID du segment associé (si envoi à un segment)
     */
    private ?int $segmentId;

    /**
     * @var int|null ID de l'utilisateur qui a envoyé le SMS
     */
    private ?int $userId;

    /**
     * @var string Date de création
     */
    private string $createdAt;

    /**
     * Constructeur
     *
     * @param int|null $id ID de l'enregistrement
     * @param string $phoneNumber Numéro de téléphone
     * @param string $message Message envoyé
     * @param string $status Statut de l'envoi
     * @param string $senderAddress Adresse de l'expéditeur
     * @param string $senderName Nom de l'expéditeur
     * @param int|null $phoneNumberId ID du numéro de téléphone associé
     * @param string|null $messageId ID du message retourné par l'API
     * @param string|null $errorMessage Message d'erreur en cas d'échec
     * @param int|null $segmentId ID du segment associé
     * @param int|null $userId ID de l'utilisateur qui a envoyé le SMS
     * @param string|null $createdAt Date de création
     */
    public function __construct(
        ?int $id,
        string $phoneNumber,
        string $message,
        string $status,
        string $senderAddress,
        string $senderName,
        ?int $phoneNumberId = null,
        ?string $messageId = null,
        ?string $errorMessage = null,
        ?int $segmentId = null,
        ?int $userId = null,
        ?string $createdAt = null
    ) {
        $this->id = $id;
        $this->phoneNumber = $phoneNumber;
        $this->message = $message;
        $this->status = $status;
        $this->senderAddress = $senderAddress;
        $this->senderName = $senderName;
        $this->phoneNumberId = $phoneNumberId;
        $this->messageId = $messageId;
        $this->errorMessage = $errorMessage;
        $this->segmentId = $segmentId;
        $this->userId = $userId;
        $this->createdAt = $createdAt ?? date('Y-m-d H:i:s');
    }

    /**
     * Obtenir l'ID de l'enregistrement
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Définir l'ID de l'enregistrement
     *
     * @param int|null $id
     * @return self
     */
    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Obtenir l'ID du numéro de téléphone associé
     *
     * @return int|null
     */
    public function getPhoneNumberId(): ?int
    {
        return $this->phoneNumberId;
    }

    /**
     * Définir l'ID du numéro de téléphone associé
     *
     * @param int|null $phoneNumberId
     * @return self
     */
    public function setPhoneNumberId(?int $phoneNumberId): self
    {
        $this->phoneNumberId = $phoneNumberId;
        return $this;
    }

    /**
     * Obtenir le numéro de téléphone
     *
     * @return string
     */
    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    /**
     * Définir le numéro de téléphone
     *
     * @param string $phoneNumber
     * @return self
     */
    public function setPhoneNumber(string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    /**
     * Obtenir le message envoyé
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Définir le message envoyé
     *
     * @param string $message
     * @return self
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Obtenir le statut de l'envoi
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Définir le statut de l'envoi
     *
     * @param string $status
     * @return self
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Obtenir l'ID du message retourné par l'API
     *
     * @return string|null
     */
    public function getMessageId(): ?string
    {
        return $this->messageId;
    }

    /**
     * Définir l'ID du message retourné par l'API
     *
     * @param string|null $messageId
     * @return self
     */
    public function setMessageId(?string $messageId): self
    {
        $this->messageId = $messageId;
        return $this;
    }

    /**
     * Obtenir le message d'erreur en cas d'échec
     *
     * @return string|null
     */
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /**
     * Définir le message d'erreur en cas d'échec
     *
     * @param string|null $errorMessage
     * @return self
     */
    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    /**
     * Obtenir l'adresse de l'expéditeur
     *
     * @return string
     */
    public function getSenderAddress(): string
    {
        return $this->senderAddress;
    }

    /**
     * Définir l'adresse de l'expéditeur
     *
     * @param string $senderAddress
     * @return self
     */
    public function setSenderAddress(string $senderAddress): self
    {
        $this->senderAddress = $senderAddress;
        return $this;
    }

    /**
     * Obtenir le nom de l'expéditeur
     *
     * @return string
     */
    public function getSenderName(): string
    {
        return $this->senderName;
    }

    /**
     * Définir le nom de l'expéditeur
     *
     * @param string $senderName
     * @return self
     */
    public function setSenderName(string $senderName): self
    {
        $this->senderName = $senderName;
        return $this;
    }

    /**
     * Obtenir l'ID du segment associé
     *
     * @return int|null
     */
    public function getSegmentId(): ?int
    {
        return $this->segmentId;
    }

    /**
     * Définir l'ID du segment associé
     *
     * @param int|null $segmentId
     * @return self
     */
    public function setSegmentId(?int $segmentId): self
    {
        $this->segmentId = $segmentId;
        return $this;
    }

    /**
     * Obtenir la date de création
     *
     * @return string
     */
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /**
     * Définir la date de création
     *
     * @param string $createdAt
     * @return self
     */
    public function setCreatedAt(string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Obtenir l'ID de l'utilisateur qui a envoyé le SMS
     *
     * @return int|null
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * Définir l'ID de l'utilisateur qui a envoyé le SMS
     *
     * @param int|null $userId
     * @return self
     */
    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }
}
