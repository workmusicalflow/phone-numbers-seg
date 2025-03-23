<?php

namespace App\Repositories;

use App\Models\Segment;
use PDO;

/**
 * SegmentRepository
 * 
 * Repository for segment data access
 */
class SegmentRepository
{
    /**
     * @var PDO The database connection
     */
    private PDO $db;

    /**
     * Constructor
     * 
     * @param PDO $db The database connection
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Find a segment by ID
     * 
     * @param int $id
     * @return Segment|null
     */
    public function findById(int $id): ?Segment
    {
        $stmt = $this->db->prepare('SELECT * FROM segments WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return new Segment($row['segment_type'], $row['value'], $row['phone_number_id'], $row['id']);
    }

    /**
     * Find segments by phone number ID
     * 
     * @param int $phoneNumberId
     * @return array
     */
    public function findByPhoneNumberId(int $phoneNumberId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM segments WHERE phone_number_id = :phone_number_id');
        $stmt->bindParam(':phone_number_id', $phoneNumberId, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $segments = [];

        foreach ($rows as $row) {
            $segments[] = new Segment($row['segment_type'], $row['value'], $row['phone_number_id'], $row['id']);
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
                INSERT INTO segments (phone_number_id, segment_type, value) 
                VALUES (:phone_number_id, :segment_type, :value)
            ');
            $phoneNumberId = $segment->getPhoneNumberId();
            $segmentType = $segment->getSegmentType();
            $value = $segment->getValue();
            $stmt->bindParam(':phone_number_id', $phoneNumberId, PDO::PARAM_INT);
            $stmt->bindParam(':segment_type', $segmentType, PDO::PARAM_STR);
            $stmt->bindParam(':value', $value, PDO::PARAM_STR);
            $stmt->execute();

            $id = (int) $this->db->lastInsertId();
            $segment->setId($id);
        } else {
            // Update existing segment
            $stmt = $this->db->prepare('
                UPDATE segments 
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
        $stmt = $this->db->prepare('DELETE FROM segments WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Delete all segments for a phone number
     * 
     * @param int $phoneNumberId
     * @return bool
     */
    public function deleteByPhoneNumberId(int $phoneNumberId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM segments WHERE phone_number_id = :phone_number_id');
        $stmt->bindParam(':phone_number_id', $phoneNumberId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
