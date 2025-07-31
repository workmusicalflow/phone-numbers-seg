<?php

namespace App\Repositories\Doctrine;

use App\Entities\Segment;
use App\Repositories\Interfaces\TechnicalSegmentRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * TechnicalSegment repository using Doctrine ORM
 */
class TechnicalSegmentRepository extends BaseRepository implements TechnicalSegmentRepositoryInterface
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
     * @param int $phoneNumberId
     * @return array
     */
    public function findByPhoneNumberId(int $phoneNumberId): array
    {
        return $this->findBy(['phoneNumberId' => $phoneNumberId]);
    }

    /**
     * Find segments by type
     * 
     * @param string $segmentType
     * @return array
     */
    public function findByType(string $segmentType): array
    {
        return $this->findBy(['segmentType' => $segmentType]);
    }

    /**
     * Delete segments by phone number ID
     * 
     * @param int $phoneNumberId
     * @return bool
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
     * @param string $segmentType
     * @param string $value
     * @param int $phoneNumberId
     * @return Segment
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
