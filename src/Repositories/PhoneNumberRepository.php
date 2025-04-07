<?php

namespace App\Repositories;

use App\Models\PhoneNumber;
use PDO;

/**
 * PhoneNumberRepository
 * 
 * Repository for phone number data access
 */
class PhoneNumberRepository
{
    /**
     * @var PDO The database connection
     */
    private PDO $db;

    /**
     * @var TechnicalSegmentRepository|null
     */
    private ?TechnicalSegmentRepository $technicalSegmentRepository;

    /**
     * @var CustomSegmentRepository|null
     */
    private ?CustomSegmentRepository $customSegmentRepository;

    /**
     * Constructor
     * 
     * @param PDO $db The database connection
     * @param TechnicalSegmentRepository|null $technicalSegmentRepository
     * @param CustomSegmentRepository|null $customSegmentRepository
     */
    public function __construct(
        PDO $db,
        ?TechnicalSegmentRepository $technicalSegmentRepository = null,
        ?CustomSegmentRepository $customSegmentRepository = null
    ) {
        $this->db = $db;
        $this->technicalSegmentRepository = $technicalSegmentRepository;
        $this->customSegmentRepository = $customSegmentRepository;
    }

    /**
     * Find a phone number by ID
     * 
     * @param int $id
     * @return PhoneNumber|null
     */
    public function findById(int $id): ?PhoneNumber
    {
        $stmt = $this->db->prepare('SELECT * FROM phone_numbers WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        $phoneNumber = $this->createFromRow($row);

        // Load technical segments if repository is available
        if ($this->technicalSegmentRepository !== null) {
            $segments = $this->technicalSegmentRepository->findByPhoneNumberId($id);
            $phoneNumber->setTechnicalSegments($segments);
        }

        // Load custom segments if repository is available
        if ($this->customSegmentRepository !== null) {
            $segments = $this->customSegmentRepository->findByPhoneNumberId($id);
            $phoneNumber->setCustomSegments($segments);
        }

        return $phoneNumber;
    }

    /**
     * Find a phone number by number
     * 
     * @param string $number
     * @return PhoneNumber|null
     */
    public function findByNumber(string $number): ?PhoneNumber
    {
        // Create a temporary PhoneNumber object to normalize the number
        $tempPhone = new PhoneNumber($number);
        $normalizedNumber = $tempPhone->getNumber();

        $stmt = $this->db->prepare('SELECT * FROM phone_numbers WHERE number = :number');
        $stmt->bindParam(':number', $normalizedNumber, PDO::PARAM_STR);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        $phoneNumber = $this->createFromRow($row);

        // Load technical segments if repository is available
        if ($this->technicalSegmentRepository !== null) {
            $segments = $this->technicalSegmentRepository->findByPhoneNumberId($phoneNumber->getId());
            $phoneNumber->setTechnicalSegments($segments);
        }

        // Load custom segments if repository is available
        if ($this->customSegmentRepository !== null) {
            $segments = $this->customSegmentRepository->findByPhoneNumberId($phoneNumber->getId());
            $phoneNumber->setCustomSegments($segments);
        }

        return $phoneNumber;
    }

    /**
     * Find all phone numbers
     * 
     * @param int $limit Limit the number of results
     * @param int $offset Offset for pagination
     * @return array
     */
    public function findAll(int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare('
            SELECT * FROM phone_numbers 
            ORDER BY date_added DESC
            LIMIT :limit OFFSET :offset
        ');
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $phoneNumbers = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $phoneNumber = $this->createFromRow($row);

            // Load technical segments if repository is available
            if ($this->technicalSegmentRepository !== null) {
                $segments = $this->technicalSegmentRepository->findByPhoneNumberId($phoneNumber->getId());
                $phoneNumber->setTechnicalSegments($segments);
            }

            // Load custom segments if repository is available
            if ($this->customSegmentRepository !== null) {
                $segments = $this->customSegmentRepository->findByPhoneNumberId($phoneNumber->getId());
                $phoneNumber->setCustomSegments($segments);
            }

            $phoneNumbers[] = $phoneNumber;
        }

        return $phoneNumbers;
    }

    /**
     * Count all phone numbers
     * 
     * @return int
     */
    public function countAll(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM phone_numbers');
        return (int) $stmt->fetchColumn();
    }

    /**
     * Find phone numbers by custom segment
     * 
     * @param int $segmentId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function findByCustomSegment(int $segmentId, int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare('
            SELECT p.* FROM phone_numbers p
            JOIN phone_number_segments pns ON p.id = pns.phone_number_id
            WHERE pns.custom_segment_id = :segment_id
            ORDER BY p.date_added DESC
            LIMIT :limit OFFSET :offset
        ');
        $stmt->bindParam(':segment_id', $segmentId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $phoneNumbers = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $phoneNumber = $this->createFromRow($row);

            // Load technical segments if repository is available
            if ($this->technicalSegmentRepository !== null) {
                $segments = $this->technicalSegmentRepository->findByPhoneNumberId($phoneNumber->getId());
                $phoneNumber->setTechnicalSegments($segments);
            }

            // Load custom segments if repository is available
            if ($this->customSegmentRepository !== null) {
                $segments = $this->customSegmentRepository->findByPhoneNumberId($phoneNumber->getId());
                $phoneNumber->setCustomSegments($segments);
            }

            $phoneNumbers[] = $phoneNumber;
        }

        return $phoneNumbers;
    }

    /**
     * Count phone numbers by custom segment
     * 
     * @param int $segmentId
     * @return int
     */
    public function countByCustomSegment(int $segmentId): int
    {
        $stmt = $this->db->prepare('
            SELECT COUNT(*) FROM phone_numbers p
            JOIN phone_number_segments pns ON p.id = pns.phone_number_id
            WHERE pns.custom_segment_id = :segment_id
        ');
        $stmt->bindParam(':segment_id', $segmentId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Search phone numbers
     * 
     * @param string $query
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function search(string $query, int $limit = 100, int $offset = 0): array
    {
        $searchQuery = '%' . $query . '%';

        $stmt = $this->db->prepare('
            SELECT * FROM phone_numbers 
            WHERE number LIKE :query 
               OR civility LIKE :query
               OR firstName LIKE :query
               OR name LIKE :query 
               OR company LIKE :query 
               OR sector LIKE :query
               OR notes LIKE :query
            ORDER BY date_added DESC
            LIMIT :limit OFFSET :offset
        ');
        $stmt->bindParam(':query', $searchQuery, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $phoneNumbers = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $phoneNumber = $this->createFromRow($row);

            // Load technical segments if repository is available
            if ($this->technicalSegmentRepository !== null) {
                $segments = $this->technicalSegmentRepository->findByPhoneNumberId($phoneNumber->getId());
                $phoneNumber->setTechnicalSegments($segments);
            }

            // Load custom segments if repository is available
            if ($this->customSegmentRepository !== null) {
                $segments = $this->customSegmentRepository->findByPhoneNumberId($phoneNumber->getId());
                $phoneNumber->setCustomSegments($segments);
            }

            $phoneNumbers[] = $phoneNumber;
        }

        return $phoneNumbers;
    }

    /**
     * Find phone numbers by advanced filters
     * 
     * @param array $filters Filters to apply (operator, country, dateFrom, dateTo, segment)
     * @param int $limit Maximum number of records to return
     * @param int $offset Offset for pagination
     * @return array Array of PhoneNumber objects
     */
    public function findByFilters(array $filters, int $limit = 100, int $offset = 0): array
    {
        // Start building the query
        $sql = 'SELECT DISTINCT p.* FROM phone_numbers p';
        $params = [];
        $whereConditions = [];

        // Join with technical_segments if filtering by operator or country
        if (isset($filters['operator']) || isset($filters['country'])) {
            $sql .= ' LEFT JOIN technical_segments ts ON p.id = ts.phone_number_id';
        }

        // Join with phone_number_segments and custom_segments if filtering by segment
        if (isset($filters['segment'])) {
            $sql .= ' LEFT JOIN phone_number_segments pns ON p.id = pns.phone_number_id';
            $sql .= ' LEFT JOIN custom_segments cs ON pns.custom_segment_id = cs.id';
        }

        // Filter by operator
        if (isset($filters['operator'])) {
            $whereConditions[] = '(ts.type = "operator" AND ts.value = :operator)';
            $params[':operator'] = $filters['operator'];
        }

        // Filter by country
        if (isset($filters['country'])) {
            $whereConditions[] = '(ts.type = "country" AND ts.value = :country)';
            $params[':country'] = $filters['country'];
        }

        // Filter by date range
        if (isset($filters['dateFrom'])) {
            $whereConditions[] = 'p.date_added >= :dateFrom';
            $params[':dateFrom'] = $filters['dateFrom'];
        }

        if (isset($filters['dateTo'])) {
            $whereConditions[] = 'p.date_added <= :dateTo';
            $params[':dateTo'] = $filters['dateTo'];
        }

        // Filter by segment (custom segment)
        if (isset($filters['segment'])) {
            $whereConditions[] = 'cs.id = :segment';
            $params[':segment'] = $filters['segment'];
        }

        // Add WHERE clause if there are conditions
        if (!empty($whereConditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $whereConditions);
        }

        // Add ORDER BY, LIMIT and OFFSET
        $sql .= ' ORDER BY p.date_added DESC LIMIT :limit OFFSET :offset';
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        // Prepare and execute the query
        $stmt = $this->db->prepare($sql);

        // Bind parameters
        foreach ($params as $param => $value) {
            if (is_int($value)) {
                $stmt->bindValue($param, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($param, $value, PDO::PARAM_STR);
            }
        }

        $stmt->execute();

        $phoneNumbers = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $phoneNumber = $this->createFromRow($row);

            // Load technical segments if repository is available
            if ($this->technicalSegmentRepository !== null) {
                $segments = $this->technicalSegmentRepository->findByPhoneNumberId($phoneNumber->getId());
                $phoneNumber->setTechnicalSegments($segments);
            }

            // Load custom segments if repository is available
            if ($this->customSegmentRepository !== null) {
                $segments = $this->customSegmentRepository->findByPhoneNumberId($phoneNumber->getId());
                $phoneNumber->setCustomSegments($segments);
            }

            $phoneNumbers[] = $phoneNumber;
        }

        return $phoneNumbers;
    }

    /**
     * Save a phone number
     * 
     * @param PhoneNumber $phoneNumber
     * @return PhoneNumber
     */
    public function save(PhoneNumber $phoneNumber): PhoneNumber
    {
        if ($phoneNumber->getId() === null) {
            // Insert new phone number
            $stmt = $this->db->prepare('
                INSERT INTO phone_numbers (number, civility, firstName, name, company, sector, notes)
                VALUES (:number, :civility, :firstName, :name, :company, :sector, :notes)
            ');
            $number = $phoneNumber->getNumber();
            $civility = $phoneNumber->getCivility();
            $firstName = $phoneNumber->getFirstName();
            $name = $phoneNumber->getName();
            $company = $phoneNumber->getCompany();
            $sector = $phoneNumber->getSector();
            $notes = $phoneNumber->getNotes();

            $stmt->bindParam(':number', $number, PDO::PARAM_STR);
            $stmt->bindParam(':civility', $civility, PDO::PARAM_STR);
            $stmt->bindParam(':firstName', $firstName, PDO::PARAM_STR);
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':company', $company, PDO::PARAM_STR);
            $stmt->bindParam(':sector', $sector, PDO::PARAM_STR);
            $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
            $stmt->execute();

            $id = (int) $this->db->lastInsertId();
            $phoneNumber->setId($id);
        } else {
            // Update existing phone number
            $stmt = $this->db->prepare('
                UPDATE phone_numbers 
                SET number = :number, civility = :civility, firstName = :firstName, name = :name, company = :company, sector = :sector, notes = :notes
                WHERE id = :id
            ');
            $id = $phoneNumber->getId();
            $number = $phoneNumber->getNumber();
            $civility = $phoneNumber->getCivility();
            $firstName = $phoneNumber->getFirstName();
            $name = $phoneNumber->getName();
            $company = $phoneNumber->getCompany();
            $sector = $phoneNumber->getSector();
            $notes = $phoneNumber->getNotes();

            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':number', $number, PDO::PARAM_STR);
            $stmt->bindParam(':civility', $civility, PDO::PARAM_STR);
            $stmt->bindParam(':firstName', $firstName, PDO::PARAM_STR);
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':company', $company, PDO::PARAM_STR);
            $stmt->bindParam(':sector', $sector, PDO::PARAM_STR);
            $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
            $stmt->execute();
        }

        // Save technical segments if repository is available
        if ($this->technicalSegmentRepository !== null && $phoneNumber->getId() !== null) {
            // Delete existing segments
            $this->technicalSegmentRepository->deleteByPhoneNumberId($phoneNumber->getId());

            // Save new segments
            foreach ($phoneNumber->getTechnicalSegments() as $segment) {
                $segment->setPhoneNumberId($phoneNumber->getId());
                $this->technicalSegmentRepository->save($segment);
            }
        }

        // Save custom segments if repository is available
        if ($this->customSegmentRepository !== null && $phoneNumber->getId() !== null) {
            // Get existing segments
            $existingSegments = $this->customSegmentRepository->findByPhoneNumberId($phoneNumber->getId());
            $existingSegmentIds = array_map(function ($segment) {
                return $segment->getId();
            }, $existingSegments);

            // Get new segment IDs
            $newSegmentIds = array_map(function ($segment) {
                return $segment->getId();
            }, $phoneNumber->getCustomSegments());

            // Remove segments that are no longer associated
            foreach ($existingSegmentIds as $segmentId) {
                if (!in_array($segmentId, $newSegmentIds)) {
                    $this->customSegmentRepository->removePhoneNumberFromSegment($phoneNumber->getId(), $segmentId);
                }
            }

            // Add new segments
            foreach ($newSegmentIds as $segmentId) {
                if (!in_array($segmentId, $existingSegmentIds)) {
                    $this->customSegmentRepository->addPhoneNumberToSegment($phoneNumber->getId(), $segmentId);
                }
            }
        }

        return $phoneNumber;
    }

    /**
     * Delete a phone number
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM phone_numbers WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Create a PhoneNumber object from a database row
     * 
     * @param array $row
     * @return PhoneNumber
     */
    private function createFromRow(array $row): PhoneNumber
    {
        return new PhoneNumber(
            $row['number'],
            $row['id'],
            $row['civility'] ?? null,
            $row['firstName'] ?? null,
            $row['name'] ?? null,
            $row['company'] ?? null,
            $row['sector'] ?? null,
            $row['notes'] ?? null,
            $row['date_added'] // Assumes column name is 'date_added'
        );
    }
}
