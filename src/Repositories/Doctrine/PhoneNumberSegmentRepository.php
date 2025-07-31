<?php

namespace App\Repositories\Doctrine;

use App\Entities\PhoneNumberSegment;
use App\Repositories\Interfaces\PhoneNumberSegmentRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * PhoneNumberSegment repository using Doctrine ORM
 */
class PhoneNumberSegmentRepository extends BaseRepository implements PhoneNumberSegmentRepositoryInterface
{
    /**
     * Constructor
     * 
     * @param EntityManagerInterface $entityManager The entity manager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager, PhoneNumberSegment::class);
    }

    /**
     * Find phone number segments by phone number ID
     * 
     * @param int $phoneNumberId The phone number ID
     * @return array The phone number segments
     */
    public function findByPhoneNumberId(int $phoneNumberId): array
    {
        return $this->findBy(['phoneNumberId' => $phoneNumberId]);
    }

    /**
     * Find phone number segments by custom segment ID
     * 
     * @param int $customSegmentId The custom segment ID
     * @return array The phone number segments
     */
    public function findByCustomSegmentId(int $customSegmentId): array
    {
        return $this->findBy(['customSegmentId' => $customSegmentId]);
    }

    /**
     * Delete phone number segments by phone number ID
     * 
     * @param int $phoneNumberId The phone number ID
     * @return bool True if successful
     */
    public function deleteByPhoneNumberId(int $phoneNumberId): bool
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->delete($this->entityClass, 'p')
            ->where('p.phoneNumberId = :phoneNumberId')
            ->setParameter('phoneNumberId', $phoneNumberId);

        $result = $queryBuilder->getQuery()->execute();

        return $result > 0;
    }

    /**
     * Delete phone number segments by custom segment ID
     * 
     * @param int $customSegmentId The custom segment ID
     * @return bool True if successful
     */
    public function deleteByCustomSegmentId(int $customSegmentId): bool
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->delete($this->entityClass, 'p')
            ->where('p.customSegmentId = :customSegmentId')
            ->setParameter('customSegmentId', $customSegmentId);

        $result = $queryBuilder->getQuery()->execute();

        return $result > 0;
    }

    /**
     * Create a new phone number segment
     * 
     * @param int $phoneNumberId The phone number ID
     * @param int $customSegmentId The custom segment ID
     * @return PhoneNumberSegment The created phone number segment
     */
    public function create(int $phoneNumberId, int $customSegmentId): PhoneNumberSegment
    {
        $phoneNumberSegment = new PhoneNumberSegment();
        $phoneNumberSegment->setPhoneNumberId($phoneNumberId);
        $phoneNumberSegment->setCustomSegmentId($customSegmentId);

        return $this->save($phoneNumberSegment);
    }

    /**
     * Add a phone number to a custom segment
     * 
     * @param int $phoneNumberId The phone number ID
     * @param int $customSegmentId The custom segment ID
     * @return bool True if successful
     */
    public function addPhoneNumberToSegment(int $phoneNumberId, int $customSegmentId): bool
    {
        // Check if the relationship already exists
        $existing = $this->findOneBy([
            'phoneNumberId' => $phoneNumberId,
            'customSegmentId' => $customSegmentId
        ]);

        if ($existing !== null) {
            return true; // Already exists
        }

        // Create a new relationship
        $this->create($phoneNumberId, $customSegmentId);

        return true;
    }

    /**
     * Remove a phone number from a custom segment
     * 
     * @param int $phoneNumberId The phone number ID
     * @param int $customSegmentId The custom segment ID
     * @return bool True if successful
     */
    public function removePhoneNumberFromSegment(int $phoneNumberId, int $customSegmentId): bool
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->delete($this->entityClass, 'p')
            ->where('p.phoneNumberId = :phoneNumberId')
            ->andWhere('p.customSegmentId = :customSegmentId')
            ->setParameter('phoneNumberId', $phoneNumberId)
            ->setParameter('customSegmentId', $customSegmentId);

        $result = $queryBuilder->getQuery()->execute();

        return $result > 0;
    }
}
