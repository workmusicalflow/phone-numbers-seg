<?php

namespace App\Repositories;

use App\Models\ContactGroupMembership;
use App\Repositories\Interfaces\RepositoryInterface;
use PDO;
use Exception;

class ContactGroupMembershipRepository implements RepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getEntityClassName(): string
    {
        return ContactGroupMembership::class;
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollback(): void
    {
        $this->pdo->rollBack();
    }

    public function findAll(?int $limit = null, ?int $offset = null): array
    {
        // Set default values if null
        $limitValue = $limit ?? 1000; // Default to 1000 if null
        $offsetValue = $offset ?? 0;  // Default to 0 if null

        $stmt = $this->pdo->prepare("
            SELECT * FROM contact_group_memberships
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', $limitValue, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offsetValue, PDO::PARAM_INT);
        $stmt->execute();

        $memberships = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $memberships[] = ContactGroupMembership::fromPDO($this->pdo, $row);
        }

        return $memberships;
    }

    public function findById(int $id): ?ContactGroupMembership
    {
        $stmt = $this->pdo->prepare("SELECT * FROM contact_group_memberships WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return ContactGroupMembership::fromPDO($this->pdo, $row);
    }

    public function findByContactId(int $contactId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM contact_group_memberships
            WHERE contact_id = :contact_id
        ");
        $stmt->bindParam(':contact_id', $contactId, PDO::PARAM_INT);
        $stmt->execute();

        $memberships = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $memberships[] = ContactGroupMembership::fromPDO($this->pdo, $row);
        }

        return $memberships;
    }

    public function findByGroupId(int $groupId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM contact_group_memberships
            WHERE group_id = :group_id
        ");
        $stmt->bindParam(':group_id', $groupId, PDO::PARAM_INT);
        $stmt->execute();

        $memberships = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $memberships[] = ContactGroupMembership::fromPDO($this->pdo, $row);
        }

        return $memberships;
    }

    public function search(string $query, ?array $fields = null, ?int $limit = null, ?int $offset = null): array
    {
        // Cette méthode n'est pas vraiment applicable pour les memberships
        // mais est requise par l'interface
        return $this->findAll($limit, $offset);
    }

    public function create(ContactGroupMembership $membership): ContactGroupMembership
    {
        // Vérifier si l'association existe déjà
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM contact_group_memberships 
            WHERE contact_id = :contact_id AND group_id = :group_id
        ");
        $contactId = $membership->getContactId();
        $groupId = $membership->getGroupId();

        $stmt->bindParam(':contact_id', $contactId, PDO::PARAM_INT);
        $stmt->bindParam(':group_id', $groupId, PDO::PARAM_INT);
        $stmt->execute();

        if ((int)$stmt->fetchColumn() > 0) {
            // L'association existe déjà, on la récupère
            $stmt = $this->pdo->prepare("
                SELECT * FROM contact_group_memberships 
                WHERE contact_id = :contact_id AND group_id = :group_id
                LIMIT 1
            ");
            $stmt->bindParam(':contact_id', $contactId, PDO::PARAM_INT);
            $stmt->bindParam(':group_id', $groupId, PDO::PARAM_INT);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return ContactGroupMembership::fromPDO($this->pdo, $row);
        }

        // Créer l'association
        $stmt = $this->pdo->prepare("
            INSERT INTO contact_group_memberships (contact_id, group_id, created_at)
            VALUES (:contact_id, :group_id, :created_at)
        ");

        $createdAt = $membership->getCreatedAt();

        $stmt->bindParam(':contact_id', $contactId, PDO::PARAM_INT);
        $stmt->bindParam(':group_id', $groupId, PDO::PARAM_INT);
        $stmt->bindParam(':created_at', $createdAt, PDO::PARAM_STR);

        $stmt->execute();
        $id = (int)$this->pdo->lastInsertId();

        return $this->findById($id);
    }

    public function update(ContactGroupMembership $membership): ContactGroupMembership
    {
        // Pour les memberships, il n'y a pas vraiment de mise à jour à faire
        // car ils ne contiennent que des clés étrangères et une date de création
        return $membership;
    }

    public function save($entity)
    {
        if ($entity instanceof ContactGroupMembership) {
            if ($entity->getId() === 0) {
                return $this->create($entity);
            } else {
                return $this->update($entity);
            }
        }

        throw new Exception("Entity must be an instance of ContactGroupMembership");
    }

    public function saveMany(array $entities): array
    {
        $savedEntities = [];

        $this->beginTransaction();

        try {
            foreach ($entities as $entity) {
                $savedEntities[] = $this->save($entity);
            }

            $this->commit();
            return $savedEntities;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function deleteById(int $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM contact_group_memberships WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    public function delete($entity): bool
    {
        if ($entity instanceof ContactGroupMembership) {
            return $this->deleteById($entity->getId());
        } elseif (is_numeric($entity)) {
            return $this->deleteById((int)$entity);
        }

        throw new Exception("Entity must be an instance of ContactGroupMembership or an ID");
    }

    public function deleteMany(array $entities): bool
    {
        $this->beginTransaction();

        try {
            $success = true;

            foreach ($entities as $entity) {
                $result = $this->delete($entity);
                if (!$result) {
                    $success = false;
                }
            }

            if ($success) {
                $this->commit();
            } else {
                $this->rollback();
            }

            return $success;
        } catch (Exception $e) {
            $this->rollback();
            return false;
        }
    }

    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        $conditions = [];
        $params = [];

        foreach ($criteria as $field => $value) {
            $conditions[] = "$field = :$field";
            $params[":$field"] = $value;
        }

        $whereClause = count($conditions) > 0 ? "WHERE " . implode(" AND ", $conditions) : "";

        $orderByClause = "";
        if ($orderBy) {
            $orderParts = [];
            foreach ($orderBy as $field => $direction) {
                $orderParts[] = "$field $direction";
            }
            $orderByClause = "ORDER BY " . implode(", ", $orderParts);
        }

        $limitClause = $limit !== null ? "LIMIT :limit" : "";
        $offsetClause = $offset !== null ? "OFFSET :offset" : "";

        $sql = "SELECT * FROM contact_group_memberships $whereClause $orderByClause $limitClause $offsetClause";
        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }

        if ($limit !== null) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }

        if ($offset !== null) {
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }

        $stmt->execute();

        $memberships = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $memberships[] = ContactGroupMembership::fromPDO($this->pdo, $row);
        }

        return $memberships;
    }

    public function findOneBy(array $criteria, ?array $orderBy = null)
    {
        $results = $this->findBy($criteria, $orderBy, 1, 0);

        return count($results) > 0 ? $results[0] : null;
    }

    public function count(): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM contact_group_memberships");
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function countByGroupId(int $groupId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM contact_group_memberships WHERE group_id = :group_id");
        $stmt->bindParam(':group_id', $groupId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function countByContactId(int $contactId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM contact_group_memberships WHERE contact_id = :contact_id");
        $stmt->bindParam(':contact_id', $contactId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function deleteByContactAndGroup(int $contactId, int $groupId): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM contact_group_memberships 
                WHERE contact_id = :contact_id AND group_id = :group_id
            ");

            $stmt->bindParam(':contact_id', $contactId, PDO::PARAM_INT);
            $stmt->bindParam(':group_id', $groupId, PDO::PARAM_INT);

            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    public function deleteByContactId(int $contactId): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM contact_group_memberships WHERE contact_id = :contact_id");
            $stmt->bindParam(':contact_id', $contactId, PDO::PARAM_INT);
            $stmt->execute();

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function deleteByGroupId(int $groupId): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM contact_group_memberships WHERE group_id = :group_id");
            $stmt->bindParam(':group_id', $groupId, PDO::PARAM_INT);
            $stmt->execute();

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Find membership by contact ID and group ID
     * 
     * @param int $contactId The contact ID
     * @param int $groupId The group ID
     * @return ContactGroupMembership|null The membership or null if not found
     */
    public function findByContactIdAndGroupId(int $contactId, int $groupId): ?ContactGroupMembership
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM contact_group_memberships
            WHERE contact_id = :contact_id AND group_id = :group_id
            LIMIT 1
        ");
        $stmt->bindParam(':contact_id', $contactId, PDO::PARAM_INT);
        $stmt->bindParam(':group_id', $groupId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return ContactGroupMembership::fromPDO($this->pdo, $row);
    }
}
