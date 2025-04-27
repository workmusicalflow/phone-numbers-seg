<?php

namespace App\Repositories\Doctrine;

use App\Entities\AdminContact;
use App\Entities\CustomSegment;
use App\Repositories\Interfaces\AdminContactRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use RuntimeException;

/**
 * Doctrine implementation of AdminContactRepositoryInterface
 */
class AdminContactRepository extends BaseRepository implements AdminContactRepositoryInterface
{
    private EntityRepository $entityRepository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager, AdminContact::class);
        $this->entityRepository = $entityManager->getRepository(AdminContact::class);
    }

    /**
     * @inheritDoc
     */
    public function findById($id): ?AdminContact
    {
        return $this->entityRepository->find($id);
    }

    /**
     * @inheritDoc
     */
    public function findByPhoneNumber(string $phoneNumber): ?AdminContact
    {
        // Normalization should ideally happen before calling the repository,
        // or be handled consistently (e.g., in the entity setter or a dedicated service).
        // Assuming the input $phoneNumber is already normalized or the entity handles it.
        return $this->entityRepository->findOneBy(['phoneNumber' => $phoneNumber]);
    }

    /**
     * @inheritDoc
     */
    public function findAll(?int $limit = 100, ?int $offset = 0): array
    {
        return $this->entityRepository->findBy([], ['name' => 'ASC', 'createdAt' => 'DESC'], $limit, $offset);
    }

    /**
     * @inheritDoc
     */
    public function findBySegmentId(int $segmentId, int $limit = 100, int $offset = 0): array
    {
        return $this->entityRepository->findBy(['segment' => $segmentId], ['name' => 'ASC', 'createdAt' => 'DESC'], $limit, $offset);
    }

    /**
     * @inheritDoc
     */
    public function countAll(): int
    {
        return $this->entityRepository->count([]);
    }

    /**
     * @inheritDoc
     */
    public function countBySegmentId(int $segmentId): int
    {
        return $this->entityRepository->count(['segment' => $segmentId]);
    }

    /**
     * @inheritDoc
     */
    public function save($contact) // Parameter type hint should be AdminContact
    {
        if (!$contact instanceof AdminContact) {
            throw new \InvalidArgumentException('Entity must be an instance of AdminContact.');
        }

        $entityManager = $this->getEntityManager();

        // Check for existing contact with the same phone number
        $existingContact = $this->findByPhoneNumber($contact->getPhoneNumber());

        if ($existingContact !== null) {
            if ($contact->getId() === null) {
                // Trying to insert a new contact with an existing number. Update instead.
                // Merge properties from the new contact into the existing one.
                $existingContact->setName($contact->getName());
                $existingContact->setSegment($contact->getSegment());
                $existingContact->setUpdatedAt(new \DateTimeImmutable());
                $entityManager->persist($existingContact);
                $entityManager->flush();
                return $existingContact; // Return the updated existing contact
            } elseif ($contact->getId() !== $existingContact->getId()) {
                // Trying to update a contact to a number that already belongs to another contact.
                throw new RuntimeException("Phone number {$contact->getPhoneNumber()} already exists for another admin contact (ID: {$existingContact->getId()}).");
            }
        }

        // If it's an update or a new contact with a unique number, persist it.
        if ($contact->getId() !== null) {
            $contact->setUpdatedAt(new \DateTimeImmutable());
        }
        $entityManager->persist($contact);
        $entityManager->flush();

        return $contact;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($id): bool
    {
        $entity = $this->findById($id);
        if ($entity) {
            $this->getEntityManager()->remove($entity);
            $this->getEntityManager()->flush();
            return true;
        }
        return false;
    }

    // Implement methods required by BaseRepository/Interfaces

    /**
     * @inheritDoc
     */
    public function getEntityClassName(): string
    {
        return AdminContact::class;
    }

    /**
     * @inheritDoc
     */
    public function saveMany(array $entities): array
    {
        $entityManager = $this->getEntityManager();
        $savedEntities = [];
        foreach ($entities as $entity) {
            // Apply save logic (including uniqueness check) for each entity?
            // For simplicity, just persist for now. Consider refining batch save logic.
            if ($entity instanceof AdminContact) {
                $entityManager->persist($entity);
                $savedEntities[] = $entity;
            }
        }
        $entityManager->flush();
        return $savedEntities; // Return the entities that were actually saved
    }

    /**
     * @inheritDoc
     */
    public function deleteMany(array $entities): bool
    {
        $entityManager = $this->getEntityManager();
        foreach ($entities as $entity) {
            if ($entity instanceof AdminContact) {
                $entityManager->remove($entity);
            }
        }
        $entityManager->flush();
        return true; // Assuming flush is successful if no exception is thrown
    }

    /**
     * @inheritDoc
     */
    public function search(string $query, ?array $fields = null, ?int $limit = null, ?int $offset = null): array
    {
        $qb = $this->entityRepository->createQueryBuilder('ac'); // Alias 'ac' for AdminContact
        $orX = $qb->expr()->orX();

        if (empty($fields)) {
            // Default fields to search if none are provided
            $fields = ['name', 'phoneNumber']; // Example fields for AdminContact
        }

        foreach ($fields as $field) {
            $orX->add($qb->expr()->like('ac.' . $field, ':query'));
        }

        $qb->where($orX)
            ->setParameter('query', '%' . $query . '%');

        // Default order
        $qb->orderBy('ac.name', 'ASC')
            ->addOrderBy('ac.createdAt', 'DESC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    // Implement methods from ReadRepositoryInterface if needed, or rely on BaseRepository
    // findBy and findOneBy are usually provided by Doctrine's EntityRepository
}
