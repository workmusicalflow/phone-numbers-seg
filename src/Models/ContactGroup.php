<?php

namespace App\Models;

use PDO;

class ContactGroup
{
    private int $id;
    private int $userId;
    private string $name;
    private ?string $description;
    private string $createdAt;
    private string $updatedAt;

    public function __construct(
        int $id,
        int $userId,
        string $name,
        ?string $description = null,
        string $createdAt = '',
        string $updatedAt = ''
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->name = $name;
        $this->description = $description;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
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
            'description' => $this->description,
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
            $data['description'] ?? null,
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
            $data['description'],
            $data['created_at'],
            $data['updated_at']
        );
    }
}
