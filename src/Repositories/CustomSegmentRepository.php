<?php

namespace App\Repositories;

use App\Models\CustomSegment;
use App\Models\PhoneNumber;
use PDO;

/**
 * CustomSegmentRepository
 * 
 * Repository for custom segment operations
 */
class CustomSegmentRepository
{
    /**
     * @var PDO
     */
    private PDO $db;

    /**
     * Constructor
     * 
     * @param PDO $db
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Find all custom segments
     * 
     * @return array
     */
    public function findAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM custom_segments ORDER BY name');
        $segments = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $segments[] = $this->createFromRow($row);
        }

        return $segments;
    }

    /**
     * Find a custom segment by ID
     * 
     * @param int $id
     * @return CustomSegment|null
     */
    public function findById(int $id): ?CustomSegment
    {
        $stmt = $this->db->prepare('SELECT * FROM custom_segments WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return $this->createFromRow($row);
    }

    /**
     * Find a custom segment by name
     * 
     * @param string $name
     * @return CustomSegment|null
     */
    public function findByName(string $name): ?CustomSegment
    {
        $stmt = $this->db->prepare('SELECT * FROM custom_segments WHERE name = :name');
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return $this->createFromRow($row);
    }

    /**
     * Find phone numbers associated with a custom segment
     * 
     * @param int $segmentId
     * @return array
     */
    public function findPhoneNumbersBySegmentId(int $segmentId): array
    {
        $stmt = $this->db->prepare('
            SELECT p.* FROM phone_numbers p
            JOIN phone_number_segments pns ON p.id = pns.phone_number_id
            WHERE pns.custom_segment_id = :segment_id
            ORDER BY p.date_added DESC
        ');
        $stmt->bindParam(':segment_id', $segmentId, PDO::PARAM_INT);
        $stmt->execute();

        $phoneNumbers = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $phoneNumbers[] = new PhoneNumber(
                $row['number'],
                $row['id'],
                $row['name'],
                $row['company'],
                $row['sector'],
                $row['notes'],
                $row['date_added']
            );
        }

        return $phoneNumbers;
    }

    /**
     * Find custom segments associated with a phone number
     * 
     * @param int $phoneNumberId
     * @return array
     */
    public function findByPhoneNumberId(int $phoneNumberId): array
    {
        $stmt = $this->db->prepare('
            SELECT cs.* FROM custom_segments cs
            JOIN phone_number_segments pns ON cs.id = pns.custom_segment_id
            WHERE pns.phone_number_id = :phone_number_id
            ORDER BY cs.name
        ');
        $stmt->bindParam(':phone_number_id', $phoneNumberId, PDO::PARAM_INT);
        $stmt->execute();

        $segments = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $segments[] = $this->createFromRow($row);
        }

        return $segments;
    }

    /**
     * Save a custom segment
     * 
     * @param CustomSegment $segment
     * @return CustomSegment
     */
    public function save(CustomSegment $segment): CustomSegment
    {
        if ($segment->getId() === null) {
            // Insert new segment
            $stmt = $this->db->prepare('
                INSERT INTO custom_segments (name, description, pattern)
                VALUES (:name, :description, :pattern)
            ');
            $name = $segment->getName();
            $description = $segment->getDescription();
            $pattern = $segment->getPattern();
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam(':pattern', $pattern, PDO::PARAM_STR);
            $stmt->execute();

            $segment->setId((int) $this->db->lastInsertId());
        } else {
            // Update existing segment
            $stmt = $this->db->prepare('
                UPDATE custom_segments
                SET name = :name, description = :description, pattern = :pattern
                WHERE id = :id
            ');
            $id = $segment->getId();
            $name = $segment->getName();
            $description = $segment->getDescription();
            $pattern = $segment->getPattern();
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam(':pattern', $pattern, PDO::PARAM_STR);
            $stmt->execute();
        }

        return $segment;
    }

    /**
     * Delete a custom segment
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        // Delete the segment (cascade will delete associations)
        $stmt = $this->db->prepare('DELETE FROM custom_segments WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Associate a phone number with a custom segment
     * 
     * @param int $phoneNumberId
     * @param int $segmentId
     * @return bool
     */
    public function addPhoneNumberToSegment(int $phoneNumberId, int $segmentId): bool
    {
        // Check if the association already exists
        $stmt = $this->db->prepare('
            SELECT COUNT(*) FROM phone_number_segments
            WHERE phone_number_id = :phone_number_id AND custom_segment_id = :segment_id
        ');
        $stmt->bindParam(':phone_number_id', $phoneNumberId, PDO::PARAM_INT);
        $stmt->bindParam(':segment_id', $segmentId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->fetchColumn() > 0) {
            // Association already exists
            return true;
        }

        // Create the association
        $stmt = $this->db->prepare('
            INSERT INTO phone_number_segments (phone_number_id, custom_segment_id)
            VALUES (:phone_number_id, :segment_id)
        ');
        $stmt->bindParam(':phone_number_id', $phoneNumberId, PDO::PARAM_INT);
        $stmt->bindParam(':segment_id', $segmentId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Remove a phone number from a custom segment
     * 
     * @param int $phoneNumberId
     * @param int $segmentId
     * @return bool
     */
    public function removePhoneNumberFromSegment(int $phoneNumberId, int $segmentId): bool
    {
        $stmt = $this->db->prepare('
            DELETE FROM phone_number_segments
            WHERE phone_number_id = :phone_number_id AND custom_segment_id = :segment_id
        ');
        $stmt->bindParam(':phone_number_id', $phoneNumberId, PDO::PARAM_INT);
        $stmt->bindParam(':segment_id', $segmentId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Create a CustomSegment object from a database row
     * 
     * @param array $row
     * @return CustomSegment
     */
    private function createFromRow(array $row): CustomSegment
    {
        return new CustomSegment(
            $row['name'],
            $row['description'],
            $row['pattern'] ?? null,
            $row['id']
        );
    }
}
