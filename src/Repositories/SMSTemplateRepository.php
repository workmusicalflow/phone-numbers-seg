<?php

namespace App\Repositories;

use App\Models\SMSTemplate;
use App\Repositories\Interfaces\SMSTemplateRepositoryInterface;
use PDO;
use PDOException;

/**
 * Repository pour les modèles de SMS
 */
class SMSTemplateRepository implements SMSTemplateRepositoryInterface
{
    /**
     * Instance de PDO
     * 
     * @var PDO
     */
    private $pdo;

    /**
     * Constructeur
     * 
     * @param PDO $pdo Instance de PDO
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * {@inheritdoc}
     */
    public function findByUserId(int $userId, int $limit = 10, int $offset = 0): array
    {
        $query = "SELECT * FROM sms_templates WHERE user_id = :userId ORDER BY title ASC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $templates = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $templates[] = SMSTemplate::fromArray($row);
        }

        return $templates;
    }

    /**
     * {@inheritdoc}
     */
    public function countByUserId(int $userId): int
    {
        $query = "SELECT COUNT(*) FROM sms_templates WHERE user_id = :userId";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $query, ?array $fields = null, ?int $limit = null, ?int $offset = null): array
    {
        $searchFields = $fields ?? ['title', 'content', 'description'];
        $limit = $limit ?? 10;
        $offset = $offset ?? 0;

        $searchConditions = [];
        $params = [];

        foreach ($searchFields as $field) {
            $searchConditions[] = "$field LIKE :search_$field";
            $params["search_$field"] = "%$query%";
        }

        $searchSql = implode(' OR ', $searchConditions);
        $sql = "SELECT * FROM sms_templates WHERE $searchSql ORDER BY title ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value, PDO::PARAM_STR);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $templates = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $templates[] = SMSTemplate::fromArray($row);
        }

        return $templates;
    }

    /**
     * {@inheritdoc}
     */
    public function searchByUser(int $userId, string $search, int $limit = 10, int $offset = 0): array
    {
        $query = "SELECT * FROM sms_templates 
                 WHERE user_id = :userId 
                 AND (title LIKE :search OR content LIKE :search OR description LIKE :search)
                 ORDER BY title ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $templates = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $templates[] = SMSTemplate::fromArray($row);
        }

        return $templates;
    }

    /**
     * {@inheritdoc}
     */
    public function create(SMSTemplate $template): SMSTemplate
    {
        $query = "INSERT INTO sms_templates (user_id, title, content, description, created_at, updated_at) 
                 VALUES (:userId, :title, :content, :description, :createdAt, :updatedAt)";

        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':userId', $template->getUserId(), PDO::PARAM_INT);
        $stmt->bindValue(':title', $template->getTitle(), PDO::PARAM_STR);
        $stmt->bindValue(':content', $template->getContent(), PDO::PARAM_STR);
        $stmt->bindValue(':description', $template->getDescription(), PDO::PARAM_STR);
        $stmt->bindValue(':createdAt', $template->getCreatedAt(), PDO::PARAM_STR);
        $stmt->bindValue(':updatedAt', $template->getUpdatedAt(), PDO::PARAM_STR);

        $stmt->execute();

        $id = (int) $this->pdo->lastInsertId();
        $template->setId($id);

        return $template;
    }

    /**
     * {@inheritdoc}
     */
    public function update(SMSTemplate $template): bool
    {
        $query = "UPDATE sms_templates 
                 SET title = :title, 
                     content = :content, 
                     description = :description, 
                     updated_at = :updatedAt 
                 WHERE id = :id AND user_id = :userId";

        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':id', $template->getId(), PDO::PARAM_INT);
        $stmt->bindValue(':userId', $template->getUserId(), PDO::PARAM_INT);
        $stmt->bindValue(':title', $template->getTitle(), PDO::PARAM_STR);
        $stmt->bindValue(':content', $template->getContent(), PDO::PARAM_STR);
        $stmt->bindValue(':description', $template->getDescription(), PDO::PARAM_STR);
        $stmt->bindValue(':updatedAt', date('Y-m-d H:i:s'), PDO::PARAM_STR);

        return $stmt->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity): bool
    {
        if ($entity instanceof SMSTemplate) {
            return $this->deleteById($entity->getId());
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById(int $id): bool
    {
        $query = "DELETE FROM sms_templates WHERE id = :id";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMany(array $entities): bool
    {
        $this->beginTransaction();

        try {
            foreach ($entities as $entity) {
                if (!$this->delete($entity)) {
                    $this->rollback();
                    return false;
                }
            }

            $this->commit();
            return true;
        } catch (PDOException $e) {
            $this->rollback();
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(?int $limit = null, ?int $offset = null): array
    {
        $limit = $limit ?? 10;
        $offset = $offset ?? 0;

        $query = "SELECT * FROM sms_templates ORDER BY title ASC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $templates = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $templates[] = SMSTemplate::fromArray($row);
        }

        return $templates;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        $query = "SELECT COUNT(*) FROM sms_templates";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * {@inheritdoc}
     */
    public function save($entity)
    {
        if (!($entity instanceof SMSTemplate)) {
            throw new \InvalidArgumentException('Entity must be an instance of SMSTemplate');
        }

        if ($entity->getId() === 0) {
            return $this->create($entity);
        } else {
            $this->update($entity);
            return $entity;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function saveMany(array $entities): array
    {
        $this->beginTransaction();

        try {
            $savedEntities = [];
            foreach ($entities as $entity) {
                $savedEntities[] = $this->save($entity);
            }

            $this->commit();
            return $savedEntities;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): ?SMSTemplate
    {
        $query = "SELECT * FROM sms_templates WHERE id = :id";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return SMSTemplate::fromArray($row);
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        $conditions = [];
        $params = [];

        foreach ($criteria as $field => $value) {
            $conditions[] = "$field = :$field";
            $params[$field] = $value;
        }

        $sql = "SELECT * FROM sms_templates";

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        if ($orderBy !== null) {
            $orderClauses = [];
            foreach ($orderBy as $field => $direction) {
                $orderClauses[] = "$field $direction";
            }
            $sql .= " ORDER BY " . implode(', ', $orderClauses);
        } else {
            $sql .= " ORDER BY title ASC";
        }

        if ($limit !== null) {
            $sql .= " LIMIT :limit";
            if ($offset !== null) {
                $sql .= " OFFSET :offset";
            }
        }

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        if ($limit !== null) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            if ($offset !== null) {
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            }
        }

        $stmt->execute();

        $templates = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $templates[] = SMSTemplate::fromArray($row);
        }

        return $templates;
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(array $criteria, ?array $orderBy = null)
    {
        $results = $this->findBy($criteria, $orderBy, 1);
        return !empty($results) ? $results[0] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClassName(): string
    {
        return SMSTemplate::class;
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): void
    {
        $this->pdo->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function rollback(): void
    {
        $this->pdo->rollBack();
    }
}
