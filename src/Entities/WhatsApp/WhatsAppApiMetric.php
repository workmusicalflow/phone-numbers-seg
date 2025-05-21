<?php

declare(strict_types=1);

namespace App\Entities\WhatsApp;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="whatsapp_api_metrics")
 */
class WhatsAppApiMetric
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;
    
    /**
     * @ORM\Column(type="integer")
     */
    private int $userId;
    
    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $operation;
    
    /**
     * @ORM\Column(type="float")
     */
    private float $duration;
    
    /**
     * @ORM\Column(type="boolean")
     */
    private bool $success;
    
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $errorMessage = null;
    
    /**
     * @ORM\Column(type="datetime")
     */
    private \DateTime $createdAt;
    
    /**
     * Getter pour l'ID
     */
    public function getId(): ?int
    {
        return $this->id;
    }
    
    /**
     * Getter pour l'ID utilisateur
     */
    public function getUserId(): int
    {
        return $this->userId;
    }
    
    /**
     * Setter pour l'ID utilisateur
     */
    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }
    
    /**
     * Getter pour l'opération
     */
    public function getOperation(): string
    {
        return $this->operation;
    }
    
    /**
     * Setter pour l'opération
     */
    public function setOperation(string $operation): self
    {
        $this->operation = $operation;
        return $this;
    }
    
    /**
     * Getter pour la durée
     */
    public function getDuration(): float
    {
        return $this->duration;
    }
    
    /**
     * Setter pour la durée
     */
    public function setDuration(float $duration): self
    {
        $this->duration = $duration;
        return $this;
    }
    
    /**
     * Getter pour le succès
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }
    
    /**
     * Setter pour le succès
     */
    public function setSuccess(bool $success): self
    {
        $this->success = $success;
        return $this;
    }
    
    /**
     * Getter pour le message d'erreur
     */
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }
    
    /**
     * Setter pour le message d'erreur
     */
    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }
    
    /**
     * Getter pour la date de création
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }
    
    /**
     * Setter pour la date de création
     */
    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}