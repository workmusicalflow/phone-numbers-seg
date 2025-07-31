<?php

namespace App\Repositories\Doctrine;

use App\Entities\Segment;
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
        return $this->findBy(['phoneNumberId' => $phoneNumberId]);
    }

    /**
     * Delete all segments for a phone number
     * 
     * @param int $phoneNumberId The phone number ID
     * @return bool True if successful
     */
    public function deleteByPhoneNumberId(int $phoneNumberId): bool
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->delete($this->entityClass, 's')
            ->where('s.phoneNumberId = :phoneNumberId')
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
        $segment = new Segment();
        $segment->setSegmentType($segmentType);
        $segment->setValue($value);
        $segment->setPhoneNumberId($phoneNumberId);

        return $this->save($segment);
    }
}
