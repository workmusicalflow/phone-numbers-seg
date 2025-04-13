<?php

namespace App\Repositories;

use App\Models\ContactGroup;
use App\Repositories\Interfaces\RepositoryInterface;
use PDO;
use Exception;

class ContactGroupRepository implements RepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getEntityClassName(): string
    {
        return ContactGroup::class;
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
        $stmt = $this->pdo->prepare("
            SELECT * FROM contact_groups
            ORDER BY name ASC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $groups = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $groups[] = ContactGroup::fromPDO($this->pdo, $row);
        }

        return $groups;
    }

    public function findById(int $id): ?ContactGroup
    {
        $stmt = $this->pdo->prepare("SELECT * FROM contact_groups WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return ContactGroup::fromPDO($this->pdo, $row);
    }

    /**
     * Finds multiple contact groups by their IDs, ensuring they belong to the specified user.
     *
     * @param array<int> $ids Array of group IDs.
     * @param int $userId The ID of the user to filter by.
     * @return array<ContactGroup> Array of found ContactGroup objects.
     */
    public function findByIds(array $ids, int $userId): array
    {
        if (empty($ids)) {
            return [];
        }

        // Create placeholders for the IN clause
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $stmt = $this->pdo->prepare("
            SELECT * FROM contact_groups
            WHERE id IN ($placeholders) AND user_id = ?
        ");

        // Bind the IDs and the user ID
        $params = array_merge($ids, [$userId]);
        $stmt->execute($params);

        $groups = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $groups[] = ContactGroup::fromPDO($this->pdo, $row);
        }

        return $groups;
    }


    public function findByUserId(int $userId, int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM contact_groups
            WHERE user_id = :user_id
            ORDER BY name ASC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $groups = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $groups[] = ContactGroup::fromPDO($this->pdo, $row);
        }

        return $groups;
    }

    public function search(string $query, ?array $fields = null, ?int $limit = null, ?int $offset = null): array
    {
        $searchTerm = "%$query%";

        // Déterminer les champs à rechercher
        $searchFields = $fields ?? ['name', 'description'];

        // Construire la clause WHERE pour la recherche
        $whereConditions = [];
        foreach ($searchFields as $field) {
            $whereConditions[] = "$field LIKE :search_term";
        }

        $whereClause = count($whereConditions) > 0
            ? "WHERE " . implode(" OR ", $whereConditions)
            : "";

        $limitClause = $limit !== null ? "LIMIT :limit" : "";
        $offsetClause = $offset !== null ? "OFFSET :offset" : "";

        $sql = "
            SELECT * FROM contact_groups
            $whereClause
            ORDER BY name ASC
            $limitClause $offsetClause
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':search_term', $searchTerm, PDO::PARAM_STR);

        if ($limit !== null) {
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        }

        if ($offset !== null) {
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        }

        $stmt->execute();

        $groups = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $groups[] = ContactGroup::fromPDO($this->pdo, $row);
        }

        return $groups;
    }

    // Méthode spécifique pour la recherche avec userId
    public function searchByUserId(string $query, int $userId, int $limit = 100, int $offset = 0): array
    {
        $searchTerm = "%$query%";
        $stmt = $this->pdo->prepare("
            SELECT * FROM contact_groups
            WHERE user_id = :user_id
            AND (name LIKE :search_term OR description LIKE :search_term)
            ORDER BY name ASC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':search_term', $searchTerm, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $groups = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $groups[] = ContactGroup::fromPDO($this->pdo, $row);
        }

        return $groups;
    }

    public function create(ContactGroup $group): ContactGroup
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO contact_groups (user_id, name, description, created_at, updated_at)
            VALUES (:user_id, :name, :description, :created_at, :updated_at)
        ");

        $userId = $group->getUserId();
        $name = $group->getName();
        $description = $group->getDescription();
        $createdAt = $group->getCreatedAt();
        $updatedAt = $group->getUpdatedAt();

        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':created_at', $createdAt, PDO::PARAM_STR);
        $stmt->bindParam(':updated_at', $updatedAt, PDO::PARAM_STR);

        $stmt->execute();
        $id = (int)$this->pdo->lastInsertId();

        return $this->findById($id);
    }

    public function update(ContactGroup $group): ContactGroup
    {
        $stmt = $this->pdo->prepare("
            UPDATE contact_groups
            SET name = :name,
                description = :description,
                updated_at = :updated_at
            WHERE id = :id
        ");

        $id = $group->getId();
        $name = $group->getName();
        $description = $group->getDescription();
        $updatedAt = date('Y-m-d H:i:s');

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':updated_at', $updatedAt, PDO::PARAM_STR);

        $stmt->execute();

        return $this->findById($id);
    }

    public function save($entity)
    {
        if ($entity instanceof ContactGroup) {
            if ($entity->getId() === 0) {
                return $this->create($entity);
            } else {
                return $this->update($entity);
            }
        }

        throw new Exception("Entity must be an instance of ContactGroup");
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
            // First delete any group memberships
            $stmt = $this->pdo->prepare("DELETE FROM contact_group_memberships WHERE group_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            // Then delete the group
            $stmt = $this->pdo->prepare("DELETE FROM contact_groups WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    public function delete($entity): bool
    {
        if ($entity instanceof ContactGroup) {
            return $this->deleteById($entity->getId());
        } elseif (is_numeric($entity)) {
            return $this->deleteById((int)$entity);
        }

        throw new Exception("Entity must be an instance of ContactGroup or an ID");
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
        } else {
            $orderByClause = "ORDER BY name ASC";
        }

        $limitClause = $limit !== null ? "LIMIT :limit" : "";
        $offsetClause = $offset !== null ? "OFFSET :offset" : "";

        $sql = "SELECT * FROM contact_groups $whereClause $orderByClause $limitClause $offsetClause";
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

        $groups = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $groups[] = ContactGroup::fromPDO($this->pdo, $row);
        }

        return $groups;
    }

    public function findOneBy(array $criteria, ?array $orderBy = null)
    {
        $results = $this->findBy($criteria, $orderBy, 1, 0);

        return count($results) > 0 ? $results[0] : null;
    }

    public function count(int $userId = null): int
    {
        if ($userId) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM contact_groups WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        } else {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM contact_groups");
        }

        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function getContactsInGroup(int $groupId, int $limit = 100, int $offset = 0): array
    {
        $contactRepo = new ContactRepository($this->pdo);
        return $contactRepo->findByGroupId($groupId, $limit, $offset);
    }

    public function addContactToGroup(int $contactId, int $groupId): bool
    {
        try {
            // Vérifier si l'association existe déjà
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM contact_group_memberships 
                WHERE contact_id = :contact_id AND group_id = :group_id
            ");
            $stmt->bindParam(':contact_id', $contactId, PDO::PARAM_INT);
            $stmt->bindParam(':group_id', $groupId, PDO::PARAM_INT);
            $stmt->execute();

            if ((int)$stmt->fetchColumn() > 0) {
                // L'association existe déjà
                return true;
            }

            // Créer l'association
            $stmt = $this->pdo->prepare("
                INSERT INTO contact_group_memberships (contact_id, group_id, created_at)
                VALUES (:contact_id, :group_id, :created_at)
            ");

            $createdAt = date('Y-m-d H:i:s');

            $stmt->bindParam(':contact_id', $contactId, PDO::PARAM_INT);
            $stmt->bindParam(':group_id', $groupId, PDO::PARAM_INT);
            $stmt->bindParam(':created_at', $createdAt, PDO::PARAM_STR);

            $stmt->execute();

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function removeContactFromGroup(int $contactId, int $groupId): bool
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
}
