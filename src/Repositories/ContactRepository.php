<?php

namespace App\Repositories;

use App\Models\Contact;
use App\Repositories\Interfaces\RepositoryInterface;
use PDO;
use Exception;

class ContactRepository implements RepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getEntityClassName(): string
    {
        return Contact::class;
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
        // Use default values if null is provided
        $limitValue = $limit ?? 1000; // Default to 1000 if null
        $offsetValue = $offset ?? 0;  // Default to 0 if null

        $stmt = $this->pdo->prepare("
            SELECT * FROM contacts
            ORDER BY name ASC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindParam(':limit', $limitValue, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offsetValue, PDO::PARAM_INT);
        $stmt->execute();

        $contacts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $contacts[] = Contact::fromPDO($this->pdo, $row);
        }

        return $contacts;
    }

    public function findById(int $id): ?Contact
    {
        $stmt = $this->pdo->prepare("SELECT * FROM contacts WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return Contact::fromPDO($this->pdo, $row);
    }

    public function findByUserId(int $userId, int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM contacts
            WHERE user_id = :user_id
            ORDER BY name ASC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $contacts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $contacts[] = Contact::fromPDO($this->pdo, $row);
        }

        return $contacts;
    }

    public function findByGroupId(int $groupId, int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.* FROM contacts c
            JOIN contact_group_memberships cgm ON c.id = cgm.contact_id
            WHERE cgm.group_id = :group_id
            ORDER BY c.name ASC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindParam(':group_id', $groupId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $contacts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $contacts[] = Contact::fromPDO($this->pdo, $row);
        }

        return $contacts;
    }

    public function search(string $query, ?array $fields = null, ?int $limit = null, ?int $offset = null): array
    {
        $searchTerm = "%$query%";

        // Déterminer les champs à rechercher
        $searchFields = $fields ?? ['name', 'phone_number', 'email'];

        // Construire la clause WHERE pour la recherche
        $whereConditions = [];
        foreach ($searchFields as $field) {
            $whereConditions[] = "$field LIKE :search_term";
        }

        $whereClause = count($whereConditions) > 0
            ? "WHERE " . implode(" OR ", $whereConditions)
            : "";

        // Use default values for limit and offset
        $limitValue = $limit ?? 1000; // Default to 1000 if null
        $offsetValue = $offset ?? 0;  // Default to 0 if null

        $sql = "
            SELECT * FROM contacts
            $whereClause
            ORDER BY name ASC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':search_term', $searchTerm, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $limitValue, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offsetValue, PDO::PARAM_INT);

        $stmt->execute();

        $contacts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $contacts[] = Contact::fromPDO($this->pdo, $row);
        }

        return $contacts;
    }

    // Méthode spécifique pour la recherche avec userId
    public function searchByUserId(string $query, int $userId, int $limit = 100, int $offset = 0): array
    {
        $searchTerm = "%$query%";
        $stmt = $this->pdo->prepare("
            SELECT * FROM contacts
            WHERE user_id = :user_id
            AND (name LIKE :search_term OR phone_number LIKE :search_term OR email LIKE :search_term)
            ORDER BY name ASC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':search_term', $searchTerm, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $contacts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $contacts[] = Contact::fromPDO($this->pdo, $row);
        }

        return $contacts;
    }

    public function create(Contact $contact): Contact
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO contacts (user_id, name, phone_number, email, notes, created_at, updated_at)
            VALUES (:user_id, :name, :phone_number, :email, :notes, :created_at, :updated_at)
        ");

        $userId = $contact->getUserId();
        $name = $contact->getName();
        $phoneNumber = $contact->getPhoneNumber();
        $email = $contact->getEmail();
        $notes = $contact->getNotes();
        $createdAt = $contact->getCreatedAt();
        $updatedAt = $contact->getUpdatedAt();

        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':phone_number', $phoneNumber, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
        $stmt->bindParam(':created_at', $createdAt, PDO::PARAM_STR);
        $stmt->bindParam(':updated_at', $updatedAt, PDO::PARAM_STR);

        $stmt->execute();
        $id = (int)$this->pdo->lastInsertId();

        return $this->findById($id);
    }

    public function update(Contact $contact): Contact
    {
        $stmt = $this->pdo->prepare("
            UPDATE contacts
            SET name = :name,
                phone_number = :phone_number,
                email = :email,
                notes = :notes,
                updated_at = :updated_at
            WHERE id = :id
        ");

        $id = $contact->getId();
        $name = $contact->getName();
        $phoneNumber = $contact->getPhoneNumber();
        $email = $contact->getEmail();
        $notes = $contact->getNotes();
        $updatedAt = date('Y-m-d H:i:s');

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':phone_number', $phoneNumber, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
        $stmt->bindParam(':updated_at', $updatedAt, PDO::PARAM_STR);

        $stmt->execute();

        return $this->findById($id);
    }

    public function save($entity)
    {
        if ($entity instanceof Contact) {
            if ($entity->getId() === 0) {
                return $this->create($entity);
            } else {
                return $this->update($entity);
            }
        }

        throw new Exception("Entity must be an instance of Contact");
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
            $stmt = $this->pdo->prepare("DELETE FROM contact_group_memberships WHERE contact_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            // Then delete the contact
            $stmt = $this->pdo->prepare("DELETE FROM contacts WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    public function delete($entity): bool
    {
        if ($entity instanceof Contact) {
            return $this->deleteById($entity->getId());
        } elseif (is_numeric($entity)) {
            return $this->deleteById((int)$entity);
        }

        throw new Exception("Entity must be an instance of Contact or an ID");
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

        // Use default values for limit and offset
        $limitValue = $limit ?? 1000; // Default to 1000 if null
        $offsetValue = $offset ?? 0;  // Default to 0 if null

        $sql = "SELECT * FROM contacts $whereClause $orderByClause LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }

        $stmt->bindValue(':limit', $limitValue, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offsetValue, PDO::PARAM_INT);

        $stmt->execute();

        $contacts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $contacts[] = Contact::fromPDO($this->pdo, $row);
        }

        return $contacts;
    }

    public function findOneBy(array $criteria, ?array $orderBy = null)
    {
        $results = $this->findBy($criteria, $orderBy, 1, 0);

        return count($results) > 0 ? $results[0] : null;
    }

    public function count(int $userId = null): int
    {
        if ($userId) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM contacts WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        } else {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM contacts");
        }

        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function bulkCreate(array $contacts, int $userId): array
    {
        $this->pdo->beginTransaction();

        try {
            $createdContacts = [];

            foreach ($contacts as $contactData) {
                // Create a temporary Contact object with ID 0
                $contact = new Contact(
                    0,
                    $userId,
                    $contactData['name'],
                    $contactData['phoneNumber'],
                    $contactData['email'] ?? null,
                    $contactData['notes'] ?? null
                );

                $createdContacts[] = $this->create($contact);
            }

            $this->pdo->commit();
            return $createdContacts;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
