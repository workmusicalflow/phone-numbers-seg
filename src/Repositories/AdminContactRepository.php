<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\AdminContact;
use App\Models\CustomSegment; // Optional, if loading segment details
use PDO;

/**
 * AdminContactRepository
 * 
 * Repository for administrator contact data access operations.
 */
class AdminContactRepository
{
    private PDO $db;
    private ?CustomSegmentRepository $customSegmentRepository; // Optional

    public function __construct(PDO $db, ?CustomSegmentRepository $customSegmentRepository = null)
    {
        $this->db = $db;
        $this->customSegmentRepository = $customSegmentRepository;
    }

    /**
     * Find an admin contact by its ID.
     */
    public function findById(int $id): ?AdminContact
    {
        $stmt = $this->db->prepare('SELECT * FROM admin_contacts WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        return $this->createFromRow($row);
    }

    /**
     * Find an admin contact by phone number.
     */
    public function findByPhoneNumber(string $phoneNumber): ?AdminContact
    {
        // Normalize the input number before searching
        $tempContact = new AdminContact(null, $phoneNumber);
        $normalizedNumber = $tempContact->getPhoneNumber();

        $stmt = $this->db->prepare('SELECT * FROM admin_contacts WHERE phone_number = :phone_number');
        $stmt->bindParam(':phone_number', $normalizedNumber, PDO::PARAM_STR);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        return $this->createFromRow($row);
    }

    /**
     * Find all admin contacts with pagination.
     */
    public function findAll(int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare('SELECT * FROM admin_contacts ORDER BY name ASC, created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $contacts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $contacts[] = $this->createFromRow($row);
        }
        return $contacts;
    }

    /**
     * Find all admin contacts belonging to a specific custom segment.
     */
    public function findBySegmentId(int $segmentId, int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare('SELECT * FROM admin_contacts WHERE segment_id = :segment_id ORDER BY name ASC, created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindParam(':segment_id', $segmentId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $contacts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $contacts[] = $this->createFromRow($row);
        }
        return $contacts;
    }

    /**
     * Count all admin contacts.
     */
    public function countAll(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM admin_contacts');
        return (int) $stmt->fetchColumn();
    }

    /**
     * Count admin contacts in a specific segment.
     */
    public function countBySegmentId(int $segmentId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM admin_contacts WHERE segment_id = :segment_id');
        $stmt->bindParam(':segment_id', $segmentId, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Save an admin contact (insert or update).
     */
    public function save(AdminContact $contact): AdminContact
    {
        // Check if contact with this phone number already exists to decide insert/update
        $existingContact = $this->findByPhoneNumber($contact->getPhoneNumber());

        if ($existingContact !== null && $contact->getId() === null) {
            // If inserting and number exists, update the existing one instead
            $contact->setId($existingContact->getId());
        } elseif ($existingContact !== null && $contact->getId() !== null && $existingContact->getId() !== $contact->getId()) {
            // If updating to a number that already exists for a *different* contact, throw error
            throw new \RuntimeException("Phone number {$contact->getPhoneNumber()} already exists for another admin contact.");
        }


        if ($contact->getId() === null) {
            // Insert new contact
            $stmt = $this->db->prepare('
                INSERT INTO admin_contacts (phone_number, name, segment_id, created_at) 
                VALUES (:phone_number, :name, :segment_id, :created_at)
            ');
            $phoneNumber = $contact->getPhoneNumber(); // Already normalized
            $name = $contact->getName();
            $segmentId = $contact->getSegmentId();
            $createdAt = $contact->getCreatedAt() ?? date('Y-m-d H:i:s');

            $stmt->bindParam(':phone_number', $phoneNumber, PDO::PARAM_STR);
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':segment_id', $segmentId, PDO::PARAM_INT);
            $stmt->bindParam(':created_at', $createdAt, PDO::PARAM_STR);

            $stmt->execute();
            $contact->setId((int) $this->db->lastInsertId());
        } else {
            // Update existing contact
            $stmt = $this->db->prepare('
                UPDATE admin_contacts SET 
                    phone_number = :phone_number, 
                    name = :name, 
                    segment_id = :segment_id
                    -- updated_at is handled by MySQL ON UPDATE CURRENT_TIMESTAMP
                WHERE id = :id
            ');
            $id = $contact->getId();
            $phoneNumber = $contact->getPhoneNumber(); // Already normalized
            $name = $contact->getName();
            $segmentId = $contact->getSegmentId();

            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':phone_number', $phoneNumber, PDO::PARAM_STR);
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':segment_id', $segmentId, PDO::PARAM_INT);

            $stmt->execute();
        }
        return $contact;
    }

    /**
     * Delete an admin contact by its ID.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM admin_contacts WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Create an AdminContact object from a database row, optionally loading the segment.
     */
    private function createFromRow(array $row): AdminContact
    {
        $contact = AdminContact::fromArray($row);

        // Optionally load the associated CustomSegment if the repository is available
        if ($this->customSegmentRepository !== null && $contact->getSegmentId() !== null) {
            $segment = $this->customSegmentRepository->findById($contact->getSegmentId());
            if ($segment) {
                $contact->setSegment($segment);
            }
        }

        return $contact;
    }
}
