<?php

namespace App\Models;

use PDO;

class Contact
{
    private int $id;
    private int $userId;
    private string $name;
    private string $phoneNumber;
    private ?string $email;
    private ?string $notes;
    private string $createdAt;
    private string $updatedAt;

    public function __construct(
        int $id,
        int $userId,
        string $name,
        string $phoneNumber,
        ?string $email = null,
        ?string $notes = null,
        string $createdAt = '',
        string $updatedAt = ''
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->name = $name;
        $this->phoneNumber = $phoneNumber;
        $this->email = $email;
        $this->notes = $notes;
        $this->createdAt = $createdAt ?: date('Y-m-d H:i:s');
        $this->updatedAt = $updatedAt ?: date('Y-m-d H:i:s');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'name' => $this->name,
            'phoneNumber' => $this->phoneNumber,
            'email' => $this->email,
            'notes' => $this->notes,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['userId'],
            $data['name'],
            $data['phoneNumber'],
            $data['email'] ?? null,
            $data['notes'] ?? null,
            $data['createdAt'] ?? '',
            $data['updatedAt'] ?? ''
        );
    }

    public static function fromPDO(PDO $pdo, array $data): self
    {
        return new self(
            (int)$data['id'],
            (int)$data['user_id'],
            $data['name'],
            $data['phone_number'],
            $data['email'],
            $data['notes'],
            $data['created_at'],
            $data['updated_at']
        );
    }
}
