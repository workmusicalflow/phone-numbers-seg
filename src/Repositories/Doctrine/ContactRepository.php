<?php

namespace App\Repositories\Doctrine;

use App\Entities\Contact;
use App\Repositories\Interfaces\ContactRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

/**
 * Contact repository using Doctrine ORM
 * 
 * This repository provides methods to access and manipulate Contact entities.
 */
class ContactRepository extends BaseRepository implements ContactRepositoryInterface
{
    /**
     * Constructor
     * 
     * @param EntityManagerInterface $entityManager The entity manager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager, Contact::class);
    }

    /**
     * Find contacts by user ID
     * 
     * @param int $userId The user ID
     * @param int|null $limit Maximum number of entities to return
     * @param int|null $offset Number of entities to skip
     * @return array The contacts
     */
    public function findByUserId(int $userId, ?int $limit = null, ?int $offset = null): array
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('c')
            ->from(Contact::class, 'c')
            ->where('c.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('c.name', 'ASC');

        if ($limit !== null) {
            $queryBuilder->setMaxResults($limit);
        }

        if ($offset !== null) {
            $queryBuilder->setFirstResult($offset);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Find contacts by group ID
     * 
     * @param int $groupId The group ID
     * @param int|null $limit Maximum number of entities to return
     * @param int|null $offset Number of entities to skip
     * @return array The contacts
     */
    public function findByGroupId(int $groupId, ?int $limit = null, ?int $offset = null): array
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('c')
            ->from(Contact::class, 'c')
            ->join('App\Entities\ContactGroupMembership', 'cgm', 'WITH', 'c.id = cgm.contactId')
            ->where('cgm.groupId = :groupId')
            ->setParameter('groupId', $groupId)
            ->orderBy('c.name', 'ASC');

        if ($limit !== null) {
            $queryBuilder->setMaxResults($limit);
        }

        if ($offset !== null) {
            $queryBuilder->setFirstResult($offset);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Search contacts
     * 
     * @param string $query The search query
     * @param array|null $fields The fields to search in
     * @param int|null $limit Maximum number of entities to return
     * @param int|null $offset Number of entities to skip
     * @return array The contacts
     */
    public function search(string $query, ?array $fields = null, ?int $limit = null, ?int $offset = null): array
    {
        $searchFields = $fields ?? ['name', 'phoneNumber', 'email'];
        $searchTerm = "%$query%";

        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('c')
            ->from(Contact::class, 'c')
            ->orderBy('c.name', 'ASC');

        $orExpressions = $queryBuilder->expr()->orX();
        foreach ($searchFields as $field) {
            $orExpressions->add($queryBuilder->expr()->like('c.' . $field, ':searchTerm'));
        }

        $queryBuilder->where($orExpressions)
            ->setParameter('searchTerm', $searchTerm);

        if ($limit !== null) {
            $queryBuilder->setMaxResults($limit);
        }

        if ($offset !== null) {
            $queryBuilder->setFirstResult($offset);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Search contacts by user ID
     * 
     * @param string $query The search query
     * @param int $userId The user ID
     * @param int|null $limit Maximum number of entities to return
     * @param int|null $offset Number of entities to skip
     * @return array The contacts
     */
    public function searchByUserId(string $query, int $userId, ?int $limit = null, ?int $offset = null): array
    {
        $searchTerm = "%$query%";

        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('c')
            ->from(Contact::class, 'c')
            ->where('c.userId = :userId')
            ->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->like('c.name', ':searchTerm'),
                    $queryBuilder->expr()->like('c.phoneNumber', ':searchTerm'),
                    $queryBuilder->expr()->like('c.email', ':searchTerm')
                )
            )
            ->setParameter('userId', $userId)
            ->setParameter('searchTerm', $searchTerm)
            ->orderBy('c.name', 'ASC');

        if ($limit !== null) {
            $queryBuilder->setMaxResults($limit);
        }

        if ($offset !== null) {
            $queryBuilder->setFirstResult($offset);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Count contacts with optional criteria
     * 
     * @param array $criteria Optional criteria to filter entities
     * @return int The number of contacts
     */
    public function count(array $criteria = []): int
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('COUNT(c.id)')
            ->from(Contact::class, 'c');

        foreach ($criteria as $field => $value) {
            $queryBuilder->andWhere("c.$field = :$field")
                ->setParameter($field, $value);
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * Count contacts by user ID
     * 
     * @param int|null $userId The user ID (optional)
     * @return int The number of contacts
     */
    public function countByUserId(?int $userId = null): int
    {
        if ($userId === null) {
            return $this->count([]);
        }

        return $this->count(['userId' => $userId]);
    }

    /**
     * Find a contact by phone number
     * 
     * @param string $phoneNumber The phone number to search for
     * @return Contact|null The contact if found, null otherwise
     */
    public function findByPhoneNumber(string $phoneNumber): ?Contact
    {
        return $this->findOneBy(['phoneNumber' => $phoneNumber]);
    }

    /**
     * Bulk create contacts
     * 
     * @param array $contacts Array of contact data
     * @param int $userId The user ID
     * @return array The created contacts
     * @throws Exception If an error occurs
     */
    public function bulkCreate(array $contacts, int $userId): array
    {
        $entityManager = $this->getEntityManager();
        $entityManager->beginTransaction();

        try {
            $createdContacts = [];

            foreach ($contacts as $contactData) {
                $contact = new Contact();
                $contact->setUserId($userId);
                $contact->setName($contactData['name']);
                $contact->setPhoneNumber($contactData['phoneNumber']);

                if (isset($contactData['email'])) {
                    $contact->setEmail($contactData['email']);
                }

                if (isset($contactData['notes'])) {
                    $contact->setNotes($contactData['notes']);
                }

                $entityManager->persist($contact);
                $createdContacts[] = $contact;
            }

            $entityManager->flush();
            $entityManager->commit();

            return $createdContacts;
        } catch (Exception $e) {
            $entityManager->rollback();
            throw $e;
        }
    }
}
