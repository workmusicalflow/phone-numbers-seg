<?php

namespace App\Repositories\Doctrine;

use App\Entities\PhoneNumber;
use App\Repositories\Interfaces\PhoneNumberRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * PhoneNumber repository using Doctrine ORM
 */
class PhoneNumberRepository extends BaseRepository implements PhoneNumberRepositoryInterface
{
    /**
     * @var \App\Repositories\Interfaces\SegmentRepositoryInterface|null
     */
    private $technicalSegmentRepository;

    /**
     * @var \App\Repositories\Interfaces\CustomSegmentRepositoryInterface|null
     */
    private $customSegmentRepository;

    /**
     * Constructor
     * 
     * @param EntityManagerInterface $entityManager The entity manager
     * @param \App\Repositories\Interfaces\SegmentRepositoryInterface|null $technicalSegmentRepository
     * @param \App\Repositories\Interfaces\CustomSegmentRepositoryInterface|null $customSegmentRepository
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ?\App\Repositories\Interfaces\SegmentRepositoryInterface $technicalSegmentRepository = null,
        ?\App\Repositories\Interfaces\CustomSegmentRepositoryInterface $customSegmentRepository = null
    ) {
        parent::__construct($entityManager, PhoneNumber::class);
        $this->technicalSegmentRepository = $technicalSegmentRepository;
        $this->customSegmentRepository = $customSegmentRepository;
    }

    /**
     * Find a phone number by number
     * 
     * @param string $number The phone number
     * @return PhoneNumber|null The phone number or null if not found
     */
    public function findByNumber(string $number): ?PhoneNumber
    {
        // Create a temporary PhoneNumber object to normalize the number
        $tempPhone = new PhoneNumber();
        $tempPhone->setNumber($number);
        $normalizedNumber = $tempPhone->getNumber();

        $phoneNumber = $this->findOneBy(['number' => $normalizedNumber]);

        return $phoneNumber;
    }

    /**
     * Find phone numbers by custom segment
     * 
     * @param int $segmentId The segment ID
     * @param int $limit Maximum number of phone numbers to return
     * @param int $offset Number of phone numbers to skip
     * @return array The phone numbers
     */
    public function findByCustomSegment(int $segmentId, int $limit = 100, int $offset = 0): array
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('p')
            ->from(PhoneNumber::class, 'p')
            ->innerJoin('App\Entities\PhoneNumberSegment', 'pns', 'WITH', 'p.id = pns.phoneNumberId')
            ->where('pns.customSegmentId = :segmentId')
            ->setParameter('segmentId', $segmentId)
            ->orderBy('p.dateAdded', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        $phoneNumbers = $queryBuilder->getQuery()->getResult();


        return $phoneNumbers;
    }

    /**
     * Count phone numbers by custom segment
     * 
     * @param int $segmentId The segment ID
     * @return int The number of phone numbers
     */
    public function countByCustomSegment(int $segmentId): int
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('COUNT(p.id)')
            ->from(PhoneNumber::class, 'p')
            ->innerJoin('App\Entities\PhoneNumberSegment', 'pns', 'WITH', 'p.id = pns.phoneNumberId')
            ->where('pns.customSegmentId = :segmentId')
            ->setParameter('segmentId', $segmentId);

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * Search phone numbers
     * 
     * @param string $query The search query
     * @param int $limit Maximum number of phone numbers to return
     * @param int $offset Number of phone numbers to skip
     * @return array The phone numbers
     */
    public function search(string $query, int $limit = 100, int $offset = 0): array
    {
        $searchQuery = '%' . $query . '%';

        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('p')
            ->from(PhoneNumber::class, 'p')
            ->where('p.number LIKE :query')
            ->orWhere('p.civility LIKE :query')
            ->orWhere('p.firstName LIKE :query')
            ->orWhere('p.name LIKE :query')
            ->orWhere('p.company LIKE :query')
            ->orWhere('p.sector LIKE :query')
            ->orWhere('p.notes LIKE :query')
            ->setParameter('query', $searchQuery)
            ->orderBy('p.dateAdded', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        $phoneNumbers = $queryBuilder->getQuery()->getResult();


        return $phoneNumbers;
    }

    /**
     * Find phone numbers by advanced filters
     * 
     * @param array $filters Filters to apply (operator, country, dateFrom, dateTo, segment)
     * @param int $limit Maximum number of phone numbers to return
     * @param int $offset Number of phone numbers to skip
     * @return array The phone numbers
     */
    public function findByFilters(array $filters, int $limit = 100, int $offset = 0): array
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('DISTINCT p')
            ->from(PhoneNumber::class, 'p');

        // Join with technical_segments if filtering by operator or country
        if (isset($filters['operator']) || isset($filters['country'])) {
            $queryBuilder->leftJoin('App\Entities\Segment', 'ts', 'WITH', 'p.id = ts.phoneNumberId');
        }

        // Join with phone_number_segments and custom_segments if filtering by segment
        if (isset($filters['segment'])) {
            $queryBuilder->leftJoin('App\Entities\PhoneNumberSegment', 'pns', 'WITH', 'p.id = pns.phoneNumberId')
                ->leftJoin('App\Entities\CustomSegment', 'cs', 'WITH', 'pns.customSegmentId = cs.id');
        }

        // Filter by operator
        if (isset($filters['operator'])) {
            $queryBuilder->andWhere('ts.type = :operatorType AND ts.value = :operator')
                ->setParameter('operatorType', 'operator')
                ->setParameter('operator', $filters['operator']);
        }

        // Filter by country
        if (isset($filters['country'])) {
            $queryBuilder->andWhere('ts.type = :countryType AND ts.value = :country')
                ->setParameter('countryType', 'country')
                ->setParameter('country', $filters['country']);
        }

        // Filter by date range
        if (isset($filters['dateFrom'])) {
            $queryBuilder->andWhere('p.dateAdded >= :dateFrom')
                ->setParameter('dateFrom', new \DateTime($filters['dateFrom']));
        }

        if (isset($filters['dateTo'])) {
            $queryBuilder->andWhere('p.dateAdded <= :dateTo')
                ->setParameter('dateTo', new \DateTime($filters['dateTo']));
        }

        // Filter by segment (custom segment)
        if (isset($filters['segment'])) {
            $queryBuilder->andWhere('cs.id = :segment')
                ->setParameter('segment', $filters['segment']);
        }

        // Add ORDER BY, LIMIT and OFFSET
        $queryBuilder->orderBy('p.dateAdded', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        $phoneNumbers = $queryBuilder->getQuery()->getResult();


        return $phoneNumbers;
    }

    /**
     * Create a new phone number
     * 
     * @param string $number The phone number
     * @param string|null $civility The civility
     * @param string|null $firstName The first name
     * @param string|null $name The last name
     * @param string|null $company The company
     * @param string|null $sector The sector
     * @param string|null $notes The notes
     * @return PhoneNumber The created phone number
     */
    public function create(
        string $number,
        ?string $civility = null,
        ?string $firstName = null,
        ?string $name = null,
        ?string $company = null,
        ?string $sector = null,
        ?string $notes = null
    ): PhoneNumber {
        $phoneNumber = new PhoneNumber();
        $phoneNumber->setNumber($number);
        $phoneNumber->setCivility($civility);
        $phoneNumber->setFirstName($firstName);
        $phoneNumber->setName($name);
        $phoneNumber->setCompany($company);
        $phoneNumber->setSector($sector);
        $phoneNumber->setNotes($notes);

        return $this->save($phoneNumber);
    }

    /**
     * Save a phone number
     * 
     * @param object $entity The phone number to save
     * @return object The saved phone number
     */
    public function save($entity)
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();

        // Save technical segments if repository is available
        if ($this->technicalSegmentRepository !== null && $entity->getId() !== null) {
            // Delete existing segments
            $this->technicalSegmentRepository->deleteByPhoneNumberId($entity->getId());

            // Save new segments
            foreach ($entity->getTechnicalSegments() as $segment) {
                $segment->setPhoneNumber($entity);
                $this->technicalSegmentRepository->save($segment);
            }
        }

        // Save custom segments if repository is available
        if ($this->customSegmentRepository !== null && $entity->getId() !== null) {
            // Get existing segments
            $existingSegments = $this->customSegmentRepository->findByPhoneNumberId($entity->getId());
            $existingSegmentIds = array_map(function ($segment) {
                return $segment->getId();
            }, $existingSegments);

            // Get new segment IDs
            $newSegmentIds = array_map(function ($segment) {
                return $segment->getId();
            }, $entity->getCustomSegments());

            // Remove segments that are no longer associated
            foreach ($existingSegmentIds as $segmentId) {
                if (!in_array($segmentId, $newSegmentIds)) {
                    $this->customSegmentRepository->removePhoneNumberFromSegment($entity->getId(), $segmentId);
                }
            }

            // Add new segments
            foreach ($newSegmentIds as $segmentId) {
                if (!in_array($segmentId, $existingSegmentIds)) {
                    $this->customSegmentRepository->addPhoneNumberToSegment($entity->getId(), $segmentId);
                }
            }
        }

        return $entity;
    }

}
