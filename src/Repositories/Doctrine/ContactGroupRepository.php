<?php

namespace App\Repositories\Doctrine;

use App\Entities\ContactGroup;
use App\Repositories\Interfaces\ContactGroupRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

/**
 * ContactGroup repository using Doctrine ORM
 * 
 * This repository provides methods to access and manipulate ContactGroup entities.
 */
class ContactGroupRepository extends BaseRepository implements ContactGroupRepositoryInterface
{
    /**
     * Constructor
     * 
     * @param EntityManagerInterface $entityManager The entity manager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager, ContactGroup::class);
    }

    /**
     * Find contact groups by user ID
     * 
     * @param int $userId The user ID
     * @param int|null $limit Maximum number of entities to return
     * @param int|null $offset Number of entities to skip
     * @return array The contact groups
     */
    public function findByUserId(int $userId, ?int $limit = null, ?int $offset = null): array
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('g')
            ->from(ContactGroup::class, 'g')
            ->where('g.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('g.name', 'ASC');

        if ($limit !== null) {
            $queryBuilder->setMaxResults($limit);
        }

        if ($offset !== null) {
            $queryBuilder->setFirstResult($offset);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Find multiple contact groups by their IDs, ensuring they belong to the specified user
     * 
     * @param array $ids Array of group IDs
     * @param int $userId The user ID
     * @return array The contact groups
     */
    public function findByIds(array $ids, int $userId): array
    {
        if (empty($ids)) {
            return [];
        }

        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('g')
            ->from(ContactGroup::class, 'g')
            ->where('g.id IN (:ids)')
            ->andWhere('g.userId = :userId')
            ->setParameter('ids', $ids)
            ->setParameter('userId', $userId)
            ->orderBy('g.name', 'ASC');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Search contact groups
     * 
     * @param string $query The search query
     * @param array|null $fields The fields to search in
     * @param int|null $limit Maximum number of entities to return
     * @param int|null $offset Number of entities to skip
     * @return array The contact groups
     */
    public function search(string $query, ?array $fields = null, ?int $limit = null, ?int $offset = null): array
    {
        $searchFields = $fields ?? ['name', 'description'];
        $searchTerm = "%$query%";

        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('g')
            ->from(ContactGroup::class, 'g')
            ->orderBy('g.name', 'ASC');

        $orExpressions = $queryBuilder->expr()->orX();
        foreach ($searchFields as $field) {
            $orExpressions->add($queryBuilder->expr()->like('g.' . $field, ':searchTerm'));
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
     * Search contact groups by user ID
     * 
     * @param string $query The search query
     * @param int $userId The user ID
     * @param int|null $limit Maximum number of entities to return
     * @param int|null $offset Number of entities to skip
     * @return array The contact groups
     */
    public function searchByUserId(string $query, int $userId, ?int $limit = null, ?int $offset = null): array
    {
        $searchTerm = "%$query%";

        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('g')
            ->from(ContactGroup::class, 'g')
            ->where('g.userId = :userId')
            ->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->like('g.name', ':searchTerm'),
                    $queryBuilder->expr()->like('g.description', ':searchTerm')
                )
            )
            ->setParameter('userId', $userId)
            ->setParameter('searchTerm', $searchTerm)
            ->orderBy('g.name', 'ASC');

        if ($limit !== null) {
            $queryBuilder->setMaxResults($limit);
        }

        if ($offset !== null) {
            $queryBuilder->setFirstResult($offset);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Count contact groups by user ID
     * 
     * @param int|null $userId The user ID (optional)
     * @return int The number of contact groups
     */
    public function countByUserId(?int $userId = null): int
    {
        if ($userId === null) {
            return $this->count([]);
        }

        return $this->count(['userId' => $userId]);
    }

    /**
     * Get contacts in a group
     * 
     * @param int $groupId The group ID
     * @param int|null $limit Maximum number of entities to return
     * @param int|null $offset Number of entities to skip
     * @return array The contacts
     */
    public function getContactsInGroup(int $groupId, ?int $limit = null, ?int $offset = null): array
    {
        $contactRepository = new ContactRepository($this->getEntityManager());
        return $contactRepository->findByGroupId($groupId, $limit, $offset);
    }

    /**
     * Add a contact to a group
     * 
     * @param int $contactId The contact ID
     * @param int $groupId The group ID
     * @return bool True if the contact was added to the group
     */
    public function addContactToGroup(int $contactId, int $groupId): bool
    {
        $membershipRepository = new ContactGroupMembershipRepository($this->getEntityManager());
        return $membershipRepository->addContactToGroup($contactId, $groupId);
    }

    /**
     * Remove a contact from a group
     * 
     * @param int $contactId The contact ID
     * @param int $groupId The group ID
     * @return bool True if the contact was removed from the group
     */
    public function removeContactFromGroup(int $contactId, int $groupId): bool
    {
        $membershipRepository = new ContactGroupMembershipRepository($this->getEntityManager());
        return $membershipRepository->removeContactFromGroup($contactId, $groupId);
    }
}
