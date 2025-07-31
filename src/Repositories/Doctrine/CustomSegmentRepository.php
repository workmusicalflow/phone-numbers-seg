<?php

namespace App\Repositories\Doctrine;

use App\Entities\CustomSegment;
use App\Repositories\Interfaces\CustomSegmentRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * CustomSegment repository using Doctrine ORM
 * 
 * This repository provides methods to access and manipulate CustomSegment entities.
 */
class CustomSegmentRepository extends BaseRepository implements CustomSegmentRepositoryInterface
{
    /**
     * Constructor
     * 
     * @param EntityManagerInterface $entityManager The entity manager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager, CustomSegment::class);
    }

    /**
     * Find a custom segment by name
     * 
     * @param string $name The name
     * @return CustomSegment|null The custom segment or null if not found
     */
    public function findByName(string $name): ?CustomSegment
    {
        return $this->findOneBy(['name' => $name]);
    }

    /**
     * Find phone numbers associated with a custom segment
     * 
     * @param int $segmentId The segment ID
     * @return array The phone numbers
     */
    public function findPhoneNumbersBySegmentId(int $segmentId): array
    {
        // Using DQL (Doctrine Query Language) instead of raw SQL
        $dql = "
            SELECT p
            FROM App\Entities\PhoneNumber p
            JOIN App\Entities\PhoneNumberSegment pns WITH pns.phoneNumberId = p.id
            WHERE pns.customSegmentId = :segmentId
            ORDER BY p.id DESC # Changed from p.createdAt to p.id
        ";

        $query = $this->getEntityManager()->createQuery($dql);
        $query->setParameter('segmentId', $segmentId);

        return $query->getResult();
    }

    /**
     * Find custom segments associated with a phone number
     * 
     * @param int $phoneNumberId The phone number ID
     * @return array The custom segments
     */
    public function findByPhoneNumberId(int $phoneNumberId): array
    {
        // Using DQL (Doctrine Query Language) instead of raw SQL
        $dql = "
            SELECT cs
            FROM App\Entities\CustomSegment cs
            JOIN App\Entities\PhoneNumberSegment pns WITH pns.customSegmentId = cs.id
            WHERE pns.phoneNumberId = :phoneNumberId
            ORDER BY cs.name
        ";

        $query = $this->getEntityManager()->createQuery($dql);
        $query->setParameter('phoneNumberId', $phoneNumberId);

        return $query->getResult();
    }

    /**
     * Associate a phone number with a custom segment
     * 
     * @param int $phoneNumberId The phone number ID
     * @param int $segmentId The segment ID
     * @return bool True if successful
     */
    public function addPhoneNumberToSegment(int $phoneNumberId, int $segmentId): bool
    {
        // Check if the association already exists using QueryBuilder
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('COUNT(pns.id)')
            ->from('App\Entities\PhoneNumberSegment', 'pns')
            ->where('pns.phoneNumberId = :phoneNumberId')
            ->andWhere('pns.customSegmentId = :segmentId')
            ->setParameter('phoneNumberId', $phoneNumberId)
            ->setParameter('segmentId', $segmentId);

        $count = $queryBuilder->getQuery()->getSingleScalarResult();

        if ($count > 0) {
            // Association already exists
            return true;
        }

        // Create the association using the entity
        $phoneNumberSegment = new \App\Entities\PhoneNumberSegment();
        $phoneNumberSegment->setPhoneNumberId($phoneNumberId);
        $phoneNumberSegment->setCustomSegmentId($segmentId);

        $this->getEntityManager()->persist($phoneNumberSegment);
        $this->getEntityManager()->flush();

        return true;
    }

    /**
     * Remove a phone number from a custom segment
     * 
     * @param int $phoneNumberId The phone number ID
     * @param int $segmentId The segment ID
     * @return bool True if successful
     */
    public function removePhoneNumberFromSegment(int $phoneNumberId, int $segmentId): bool
    {
        // Using QueryBuilder for delete operation
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->delete('App\Entities\PhoneNumberSegment', 'pns')
            ->where('pns.phoneNumberId = :phoneNumberId')
            ->andWhere('pns.customSegmentId = :segmentId')
            ->setParameter('phoneNumberId', $phoneNumberId)
            ->setParameter('segmentId', $segmentId);

        $result = $queryBuilder->getQuery()->execute();

        return $result > 0;
    }

    /**
     * Create a new custom segment
     * 
     * @param string $name The name
     * @param string|null $description The description
     * @param string|null $pattern The pattern
     * @return CustomSegment The created custom segment
     */
    public function create(string $name, ?string $description = null, ?string $pattern = null): CustomSegment
    {
        $segment = new CustomSegment();
        $segment->setName($name);
        $segment->setDescription($description);
        $segment->setPattern($pattern);

        return $this->save($segment);
    }
}
