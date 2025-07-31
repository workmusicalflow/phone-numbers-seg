<?php

namespace Tests\Repositories\Doctrine;

use App\Entities\Contact;
use App\Entities\User; // Import User for association
use App\Entities\ContactGroupMembership; // Import for join
use App\Repositories\Doctrine\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Mapping\ClassMetadata; // Import ClassMetadata
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy; // Import ObjectProphecy

/**
 * Test class for ContactRepository
 *
 * @covers \App\Repositories\Doctrine\ContactRepository
 */
class ContactRepositoryTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $entityManager;
    private ObjectProphecy $queryBuilder;
    private ObjectProphecy $query;
    private ObjectProphecy $expr;
    private ContactRepository $repository;

    protected function setUp(): void
    {
        // Mock the EntityManagerInterface
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);

        // Mock ClassMetadata for the repository constructor
        $metadata = $this->prophesize(ClassMetadata::class);
        $this->entityManager->getClassMetadata(Contact::class)->willReturn($metadata->reveal());

        // Mock the QueryBuilder, AbstractQuery, and Expr
        $this->query = $this->prophesize(AbstractQuery::class);
        $this->queryBuilder = $this->prophesize(QueryBuilder::class);
        $this->expr = $this->prophesize(Expr::class);

        // Mock Expr methods used in findByCriteria
        // Need to return a specific string or another prophecy if needed
        $likeExpr = 'dummy_like_expression';
        $this->expr->like('c.phoneNumber', ':searchTerm')->willReturn($likeExpr);
        $this->expr->like('c.firstName', ':searchTerm')->willReturn($likeExpr);
        $this->expr->like('c.lastName', ':searchTerm')->willReturn($likeExpr);
        $this->expr->like('c.email', ':searchTerm')->willReturn($likeExpr);
        $this->expr->orX($likeExpr, $likeExpr, $likeExpr, $likeExpr)->willReturn('dummy_orx_expression'); // Mock orX with specific args

        // Chainable methods for QueryBuilder
        $this->queryBuilder->select('c')->willReturn($this->queryBuilder); // Expect select 'c'
        $this->queryBuilder->from(Contact::class, 'c')->willReturn($this->queryBuilder); // Expect from Contact 'c'
        $this->queryBuilder->leftJoin(Argument::type('string'), Argument::type('string'), Argument::any(), Argument::any())->willReturn($this->queryBuilder); // Allow leftJoin
        $this->queryBuilder->andWhere(Argument::type('string'))->willReturn($this->queryBuilder);
        $this->queryBuilder->setParameter(Argument::type('string'), Argument::any())->willReturn($this->queryBuilder);
        $this->queryBuilder->orderBy(Argument::any(), Argument::any())->willReturn($this->queryBuilder);
        $this->queryBuilder->setMaxResults(Argument::any())->willReturn($this->queryBuilder);
        $this->queryBuilder->setFirstResult(Argument::any())->willReturn($this->queryBuilder);
        $this->queryBuilder->expr()->willReturn($this->expr->reveal()); // Return mocked Expr
        $this->queryBuilder->getQuery()->willReturn($this->query->reveal());

        // Make createQueryBuilder return our mock
        $this->entityManager->createQueryBuilder()->willReturn($this->queryBuilder->reveal());

        // Instantiate the repository with the mocked EntityManager and ClassMetadata
        $this->repository = new ContactRepository($this->entityManager->reveal(), $metadata->reveal());
    }

    /**
     * Helper to create a mock Contact entity.
     */
    private function createMockContact(int $id, int $userId): Contact
    {
        $user = $this->prophesize(User::class);
        $user->getId()->willReturn($userId);

        $contact = $this->prophesize(Contact::class);
        $contact->getId()->willReturn($id);
        $contact->getUser()->willReturn($user->reveal());
        // Add other relevant getters if needed
        return $contact->reveal();
    }

    /**
     * Test finding records by criteria: userId only.
     * @test
     */
    public function findByCriteriaUserIdOnly(): void
    {
        $userId = 1;
        $limit = 50;
        $offset = 0;
        $criteria = ['userId' => $userId];
        $expectedResult = [$this->createMockContact(201, $userId)];

        // --- Expectations ---
        $this->queryBuilder->from(Contact::class, 'c')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->andWhere('c.user = :userId')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setParameter('userId', $userId)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->orderBy('c.lastName', 'ASC')->shouldBeCalled()->willReturn($this->queryBuilder); // Default order
        $this->queryBuilder->addOrderBy('c.firstName', 'ASC')->shouldBeCalled()->willReturn($this->queryBuilder); // Default order
        $this->queryBuilder->setMaxResults($limit)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setFirstResult($offset)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->query->getResult()->shouldBeCalled()->willReturn($expectedResult);
        // --- End Expectations ---

        $result = $this->repository->findByCriteria($criteria, $limit, $offset);
        $this->assertSame($expectedResult, $result);
    }

    /**
     * Test finding records by criteria: search term only.
     * @test
     */
    public function findByCriteriaSearchOnly(): void
    {
        $searchTerm = 'test';
        $criteria = ['search' => $searchTerm];
        $expectedResult = [$this->createMockContact(202, 5)];

        // --- Expectations ---
        $this->queryBuilder->from(Contact::class, 'c')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->expr()->shouldBeCalled()->willReturn($this->expr->reveal()); // Expect expr() call
        $this->expr->orX(
            'dummy_like_expression', // c.phoneNumber
            'dummy_like_expression', // c.firstName
            'dummy_like_expression', // c.lastName
            'dummy_like_expression'  // c.email
        )->shouldBeCalled()->willReturn('dummy_orx_expression');
        $this->queryBuilder->andWhere('dummy_orx_expression')->shouldBeCalled()->willReturn($this->queryBuilder); // Expect the result of orX
        $this->queryBuilder->setParameter('searchTerm', '%' . $searchTerm . '%')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->orderBy('c.lastName', 'ASC')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->addOrderBy('c.firstName', 'ASC')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setMaxResults(50)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setFirstResult(0)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->query->getResult()->shouldBeCalled()->willReturn($expectedResult);
        // --- End Expectations ---

        $result = $this->repository->findByCriteria($criteria);
        $this->assertSame($expectedResult, $result);
    }

    /**
     * Test finding records by criteria: groupId only.
     * @test
     */
    public function findByCriteriaGroupIdOnly(): void
    {
        $groupId = 5;
        $criteria = ['groupId' => $groupId];
        $expectedResult = [$this->createMockContact(203, 6)];

        // --- Expectations ---
        $this->queryBuilder->from(Contact::class, 'c')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->leftJoin(ContactGroupMembership::class, 'cgm', 'WITH', 'cgm.contact = c.id')
            ->shouldBeCalled()->willReturn($this->queryBuilder); // Expect join
        $this->queryBuilder->andWhere('cgm.contactGroup = :groupId')->shouldBeCalled()->willReturn($this->queryBuilder); // Expect where on join alias
        $this->queryBuilder->setParameter('groupId', $groupId)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->orderBy('c.lastName', 'ASC')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->addOrderBy('c.firstName', 'ASC')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setMaxResults(50)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setFirstResult(0)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->query->getResult()->shouldBeCalled()->willReturn($expectedResult);
        // --- End Expectations ---

        $result = $this->repository->findByCriteria($criteria);
        $this->assertSame($expectedResult, $result);
    }

    /**
     * Test finding records by criteria: combination of userId and search.
     * @test
     */
    public function findByCriteriaUserIdAndSearch(): void
    {
        $userId = 2;
        $searchTerm = 'Doe';
        $criteria = ['userId' => $userId, 'search' => $searchTerm];
        $expectedResult = [$this->createMockContact(204, $userId)];

        // --- Expectations ---
        $this->queryBuilder->from(Contact::class, 'c')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->andWhere('c.user = :userId')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setParameter('userId', $userId)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->expr()->shouldBeCalled()->willReturn($this->expr->reveal());
        $this->expr->orX(Argument::cetera())->shouldBeCalled()->willReturn('dummy_orx_expression'); // Use cetera() for variable args
        $this->queryBuilder->andWhere('dummy_orx_expression')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setParameter('searchTerm', '%' . $searchTerm . '%')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->orderBy('c.lastName', 'ASC')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->addOrderBy('c.firstName', 'ASC')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setMaxResults(50)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setFirstResult(0)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->query->getResult()->shouldBeCalled()->willReturn($expectedResult);
        // --- End Expectations ---

        $result = $this->repository->findByCriteria($criteria);
        $this->assertSame($expectedResult, $result);
    }

    /**
     * Test finding records by criteria: combination of userId and groupId.
     * @test
     */
    public function findByCriteriaUserIdAndGroupId(): void
    {
        $userId = 3;
        $groupId = 1;
        $criteria = ['userId' => $userId, 'groupId' => $groupId];
        $expectedResult = [$this->createMockContact(205, $userId)];

        // --- Expectations ---
        $this->queryBuilder->from(Contact::class, 'c')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->andWhere('c.user = :userId')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setParameter('userId', $userId)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->leftJoin(Argument::any(), Argument::any(), Argument::any(), Argument::any())->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->andWhere('cgm.contactGroup = :groupId')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setParameter('groupId', $groupId)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->orderBy('c.lastName', 'ASC')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->addOrderBy('c.firstName', 'ASC')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setMaxResults(50)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setFirstResult(0)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->query->getResult()->shouldBeCalled()->willReturn($expectedResult);
        // --- End Expectations ---

        $result = $this->repository->findByCriteria($criteria);
        $this->assertSame($expectedResult, $result);
    }

    /**
     * Test finding records by criteria: all filters combined.
     * @test
     */
    public function findByCriteriaAllFilters(): void
    {
        $userId = 4;
        $searchTerm = 'Example';
        $groupId = 2;
        $limit = 10;
        $offset = 5;
        $criteria = [
            'userId' => $userId,
            'search' => $searchTerm,
            'groupId' => $groupId
        ];
        $expectedResult = [$this->createMockContact(206, $userId)];

        // --- Expectations ---
        $this->queryBuilder->from(Contact::class, 'c')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->andWhere('c.user = :userId')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setParameter('userId', $userId)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->leftJoin(Argument::any(), Argument::any(), Argument::any(), Argument::any())->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->andWhere('cgm.contactGroup = :groupId')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setParameter('groupId', $groupId)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->expr()->shouldBeCalled()->willReturn($this->expr->reveal());
        $this->expr->orX(Argument::cetera())->shouldBeCalled()->willReturn('dummy_orx_expression');
        $this->queryBuilder->andWhere('dummy_orx_expression')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setParameter('searchTerm', '%' . $searchTerm . '%')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->orderBy('c.lastName', 'ASC')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->addOrderBy('c.firstName', 'ASC')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setMaxResults($limit)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setFirstResult($offset)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->query->getResult()->shouldBeCalled()->willReturn($expectedResult);
        // --- End Expectations ---

        $result = $this->repository->findByCriteria($criteria, $limit, $offset);
        $this->assertSame($expectedResult, $result);
    }

    /**
     * Test finding records by criteria: empty criteria.
     * @test
     */
    public function findByCriteriaEmpty(): void
    {
        $criteria = [];
        $expectedResult = [$this->createMockContact(207, 10)];

        // --- Expectations ---
        $this->queryBuilder->from(Contact::class, 'c')->shouldBeCalled()->willReturn($this->queryBuilder);
        // Should not have called andWhere for specific criteria
        $this->queryBuilder->andWhere(Argument::type('string'))->shouldNotBeCalled();
        $this->queryBuilder->setParameter(Argument::type('string'), Argument::any())->shouldNotBeCalled();
        $this->queryBuilder->leftJoin(Argument::any(), Argument::any(), Argument::any(), Argument::any())->shouldNotBeCalled(); // No join needed
        $this->queryBuilder->orderBy('c.lastName', 'ASC')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->addOrderBy('c.firstName', 'ASC')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setMaxResults(50)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setFirstResult(0)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->query->getResult()->shouldBeCalled()->willReturn($expectedResult);
        // --- End Expectations ---

        $result = $this->repository->findByCriteria($criteria);
        $this->assertSame($expectedResult, $result);
    }

    /**
     * Test finding records by criteria: null values in criteria are ignored.
     * @test
     */
    public function findByCriteriaWithNullsIgnored(): void
    {
        $userId = 1;
        $criteria = ['userId' => $userId, 'search' => null, 'groupId' => null];
        $expectedResult = [$this->createMockContact(208, $userId)];

        // --- Expectations ---
        $this->queryBuilder->from(Contact::class, 'c')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->andWhere('c.user = :userId')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setParameter('userId', $userId)->shouldBeCalled()->willReturn($this->queryBuilder);
        // Should not have called join or search conditions
        $this->queryBuilder->leftJoin(Argument::any(), Argument::any(), Argument::any(), Argument::any())->shouldNotBeCalled();
        $this->queryBuilder->andWhere(Argument::containingString('LIKE'))->shouldNotBeCalled(); // Check no LIKE condition
        $this->queryBuilder->andWhere(Argument::containingString('groupId'))->shouldNotBeCalled(); // Check no groupId condition
        $this->queryBuilder->orderBy('c.lastName', 'ASC')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->addOrderBy('c.firstName', 'ASC')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setMaxResults(50)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setFirstResult(0)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->query->getResult()->shouldBeCalled()->willReturn($expectedResult);
        // --- End Expectations ---

        $result = $this->repository->findByCriteria($criteria);
        $this->assertSame($expectedResult, $result);
    }
}
