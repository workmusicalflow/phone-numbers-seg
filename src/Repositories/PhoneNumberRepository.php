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
     * Constructor
     * 
     * @param PDO $db The database connection
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
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

        return new PhoneNumber($row['number'], $row['id'], $row['date_added']);
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

        return new PhoneNumber($row['number'], $row['id'], $row['date_added']);
    }

    /**
     * Find all phone numbers
     * 
     * @return array
     */
    public function findAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM phone_numbers ORDER BY date_added DESC');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $phoneNumbers = [];
        foreach ($rows as $row) {
            $phoneNumbers[] = new PhoneNumber($row['number'], $row['id'], $row['date_added']);
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
            $stmt = $this->db->prepare('INSERT INTO phone_numbers (number) VALUES (:number)');
            $number = $phoneNumber->getNumber();
            $stmt->bindParam(':number', $number, PDO::PARAM_STR);
            $stmt->execute();

            $id = (int) $this->db->lastInsertId();
            $phoneNumber->setId($id);
        } else {
            // Update existing phone number
            $stmt = $this->db->prepare('UPDATE phone_numbers SET number = :number WHERE id = :id');
            $id = $phoneNumber->getId();
            $number = $phoneNumber->getNumber();
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':number', $number, PDO::PARAM_STR);
            $stmt->execute();
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
}
