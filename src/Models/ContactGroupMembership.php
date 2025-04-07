<?php

namespace App\Models;

use PDO;

class ContactGroupMembership
{
    private int $id;
    private int $contactId;
    private int $groupId;
    private string $createdAt;

    public function __construct(
        int $id,
        int $contactId,
        int $groupId,
        string $createdAt = ''
    ) {
        $this->id = $id;
        $this->contactId = $contactId;
        $this->groupId = $groupId;
        $this->createdAt = $createdAt ?: date('Y-m-d H:i:s');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getContactId(): int
    {
        return $this->contactId;
    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'contactId' => $this->contactId,
            'groupId' => $this->groupId,
            'createdAt' => $this->createdAt
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['contactId'],
            $data['groupId'],
            $data['createdAt'] ?? ''
        );
    }

    public static function fromPDO(PDO $pdo, array $data): self
    {
        return new self(
            (int)$data['id'],
            (int)$data['contact_id'],
            (int)$data['group_id'],
            $data['created_at']
        );
    }
}
