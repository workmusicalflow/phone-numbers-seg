<?php

namespace App\Repositories\Doctrine;

use App\Entities\Segment;
use App\Entities\PhoneNumber;
use App\Repositories\Interfaces\SegmentRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Segment repository using Doctrine ORM
 * 
 * This repository provides methods to access and manipulate Segment entities.
 */
class SegmentRepository extends BaseRepository implements SegmentRepositoryInterface
{
    /**
     * Constructor
     * 
     * @param EntityManagerInterface $entityManager The entity manager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager, Segment::class);
    }

    /**
     * Find segments by phone number ID
     * 
     * @param int $phoneNumberId The phone number ID
     * @return array The segments
     */
    public function findByPhoneNumberId(int $phoneNumberId): array
    {
        return $this->findBy(['phoneNumber' => $phoneNumberId]);
    }

    /**
     * Delete all segments for a phone number
     * 
     * @param int $phoneNumberId The phone number ID
     * @return bool True if successful
     */
    public function deleteByPhoneNumberId(int $phoneNumberId): bool
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->delete(Segment::class, 's')
            ->where('s.phoneNumber = :phoneNumberId')
            ->setParameter('phoneNumberId', $phoneNumberId);

        $result = $queryBuilder->getQuery()->execute();

        return $result > 0;
    }

    /**
     * Create a new segment
     * 
     * @param string $segmentType The segment type
     * @param string $value The value
     * @param int $phoneNumberId The phone number ID
     * @return Segment The created segment
     */
    public function create(string $segmentType, string $value, int $phoneNumberId): Segment
    {
        // Load the PhoneNumber entity
        $phoneNumber = $this->getEntityManager()->find(PhoneNumber::class, $phoneNumberId);
        if (!$phoneNumber) {
            throw new \InvalidArgumentException("PhoneNumber with ID $phoneNumberId not found");
        }

        $segment = new Segment();
        $segment->setSegmentType($segmentType);
        $segment->setValue($value);
        $segment->setPhoneNumber($phoneNumber);

        return $this->save($segment);
    }
}
