<?php

namespace App\Repositories;

use App\Models\Segment;
use PDO;

/**
 * TechnicalSegmentRepository
 * 
 * Repository for technical segment operations
 */
class TechnicalSegmentRepository
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
     * Find all segments
     * 
     * @return array
     */
    public function findAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM technical_segments');
        $segments = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $segments[] = $this->createFromRow($row);
        }

        return $segments;
    }

    /**
     * Find a segment by ID
     * 
     * @param int $id
     * @return Segment|null
     */
    public function findById(int $id): ?Segment
    {
        $stmt = $this->db->prepare('SELECT * FROM technical_segments WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return $this->createFromRow($row);
    }

    /**
     * Find segments by phone number ID
     * 
     * @param int $phoneNumberId
     * @return array
     */
    public function findByPhoneNumberId(int $phoneNumberId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM technical_segments WHERE phone_number_id = :phone_number_id');
        $stmt->bindParam(':phone_number_id', $phoneNumberId, PDO::PARAM_INT);
        $stmt->execute();

        $segments = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $segments[] = $this->createFromRow($row);
        }

        return $segments;
    }

    /**
     * Find segments by type
     * 
     * @param string $segmentType
     * @return array
     */
    public function findByType(string $segmentType): array
    {
        $stmt = $this->db->prepare('SELECT * FROM technical_segments WHERE segment_type = :segment_type');
        $stmt->bindParam(':segment_type', $segmentType, PDO::PARAM_STR);
        $stmt->execute();

        $segments = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $segments[] = $this->createFromRow($row);
        }

        return $segments;
    }

    /**
     * Save a segment
     * 
     * @param Segment $segment
     * @return Segment
     */
    public function save(Segment $segment): Segment
    {
        if ($segment->getId() === null) {
            // Insert new segment
            $stmt = $this->db->prepare('
                INSERT INTO technical_segments (phone_number_id, segment_type, value)
                VALUES (:phone_number_id, :segment_type, :value)
            ');
            $phoneNumberId = $segment->getPhoneNumberId();
            $segmentType = $segment->getSegmentType();
            $value = $segment->getValue();
            $stmt->bindParam(':phone_number_id', $phoneNumberId, PDO::PARAM_INT);
            $stmt->bindParam(':segment_type', $segmentType, PDO::PARAM_STR);
            $stmt->bindParam(':value', $value, PDO::PARAM_STR);
            $stmt->execute();

            $segment->setId((int) $this->db->lastInsertId());
        } else {
            // Update existing segment
            $stmt = $this->db->prepare('
                UPDATE technical_segments
                SET phone_number_id = :phone_number_id, segment_type = :segment_type, value = :value
                WHERE id = :id
            ');
            $id = $segment->getId();
            $phoneNumberId = $segment->getPhoneNumberId();
            $segmentType = $segment->getSegmentType();
            $value = $segment->getValue();
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':phone_number_id', $phoneNumberId, PDO::PARAM_INT);
            $stmt->bindParam(':segment_type', $segmentType, PDO::PARAM_STR);
            $stmt->bindParam(':value', $value, PDO::PARAM_STR);
            $stmt->execute();
        }

        return $segment;
    }

    /**
     * Delete a segment
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM technical_segments WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Delete segments by phone number ID
     * 
     * @param int $phoneNumberId
     * @return bool
     */
    public function deleteByPhoneNumberId(int $phoneNumberId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM technical_segments WHERE phone_number_id = :phone_number_id');
        $stmt->bindParam(':phone_number_id', $phoneNumberId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Create a Segment object from a database row
     * 
     * @param array $row
     * @return Segment
     */
    private function createFromRow(array $row): Segment
    {
        return new Segment(
            $row['segment_type'],
            $row['value'],
            $row['phone_number_id'],
            $row['id']
        );
    }
}
