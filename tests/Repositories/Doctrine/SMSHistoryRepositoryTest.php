<?php

namespace Tests\Repositories\Doctrine;

use App\Entities\SMSHistory;
use App\Entities\User; // Import User entity for association
use App\Repositories\Doctrine\SMSHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Mapping\ClassMetadata; // Import ClassMetadata
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy; // Import ObjectProphecy

/**
 * Test class for SMSHistoryRepository
 *
 * @covers \App\Repositories\Doctrine\SMSHistoryRepository
 */
class SMSHistoryRepositoryTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $entityManager; // Use ObjectProphecy type hint
    private ObjectProphecy $queryBuilder;
    private ObjectProphecy $query;
    private SMSHistoryRepository $repository;

    protected function setUp(): void
    {
        // Mock the EntityManagerInterface
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);

        // Mock ClassMetadata for the repository constructor
        $metadata = $this->prophesize(ClassMetadata::class);
        $this->entityManager->getClassMetadata(SMSHistory::class)->willReturn($metadata->reveal());

        // Mock the QueryBuilder and AbstractQuery
        $this->query = $this->prophesize(AbstractQuery::class);
        $this->queryBuilder = $this->prophesize(QueryBuilder::class);

        // Basic chainable methods - return the builder prophecy
        $this->queryBuilder->select(Argument::any())->willReturn($this->queryBuilder);
        $this->queryBuilder->from(Argument::any(), Argument::any())->willReturn($this->queryBuilder);
        $this->queryBuilder->leftJoin(Argument::any(), Argument::any(), Argument::any(), Argument::any())->willReturn($this->queryBuilder); // Add leftJoin mock
        $this->queryBuilder->andWhere(Argument::type('string'))->willReturn($this->queryBuilder);
        $this->queryBuilder->setParameter(Argument::type('string'), Argument::any())->willReturn($this->queryBuilder);
        $this->queryBuilder->orderBy(Argument::any(), Argument::any())->willReturn($this->queryBuilder);
        $this->queryBuilder->setMaxResults(Argument::any())->willReturn($this->queryBuilder);
        $this->queryBuilder->setFirstResult(Argument::any())->willReturn($this->queryBuilder);
        $this->queryBuilder->getQuery()->willReturn($this->query->reveal());

        // Make createQueryBuilder return our mock
        $this->entityManager->createQueryBuilder()->willReturn($this->queryBuilder->reveal());

        // Instantiate the repository with the mocked EntityManager
        // The constructor expects EntityManager and ClassMetadata
        $this->repository = new SMSHistoryRepository($this->entityManager->reveal(), $metadata->reveal());
    }

    /**
     * Helper to create a mock SMSHistory entity.
     */
    private function createMockSmsHistory(int $id, int $userId): SMSHistory
    {
        $user = $this->prophesize(User::class);
        $user->getId()->willReturn($userId);

        $history = $this->prophesize(SMSHistory::class);
        $history->getId()->willReturn($id);
        $history->getUser()->willReturn($user->reveal()); // Link to mocked user
        // Add other relevant getters if needed for assertions
        return $history->reveal();
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
        $expectedResult = [$this->createMockSmsHistory(101, $userId)];

        // --- Expectations ---
        // Expect the correct 'from' clause
        $this->queryBuilder->from(SMSHistory::class, 'h')->shouldBeCalled()->willReturn($this->queryBuilder);
        // Expect the 'userId' condition
        $this->queryBuilder->andWhere('h.user = :userId')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setParameter('userId', $userId)->shouldBeCalled()->willReturn($this->queryBuilder);
        // Expect default ordering
        $this->queryBuilder->orderBy('h.createdAt', 'DESC')->shouldBeCalled()->willReturn($this->queryBuilder);
        // Expect pagination
        $this->queryBuilder->setMaxResults($limit)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setFirstResult($offset)->shouldBeCalled()->willReturn($this->queryBuilder);
        // Expect query execution and mock result
        $this->query->getResult()->shouldBeCalled()->willReturn($expectedResult);
        // --- End Expectations ---

        $result = $this->repository->findByCriteria($criteria, $limit, $offset);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Test finding records by criteria: status only.
     * @test
     */
    public function findByCriteriaStatusOnly(): void
    {
        $status = 'SENT';
        $criteria = ['status' => $status];
        $expectedResult = [$this->createMockSmsHistory(102, 5)]; // Example result

        // --- Expectations ---
        $this->queryBuilder->from(SMSHistory::class, 'h')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->andWhere('h.status = :status')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setParameter('status', $status)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->orderBy('h.createdAt', 'DESC')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setMaxResults(50)->shouldBeCalled()->willReturn($this->queryBuilder); // Default limit
        $this->queryBuilder->setFirstResult(0)->shouldBeCalled()->willReturn($this->queryBuilder); // Default offset
        $this->query->getResult()->shouldBeCalled()->willReturn($expectedResult);
        // --- End Expectations ---

        $result = $this->repository->findByCriteria($criteria);
        $this->assertSame($expectedResult, $result);
    }

    /**
     * Test finding records by criteria: search term only (phone number).
     * @test
     */
    public function findByCriteriaSearchPhoneNumberOnly(): void
    {
        $searchTerm = '12345';
        $criteria = ['search' => $searchTerm];
        $expectedResult = [$this->createMockSmsHistory(103, 6)];

        // --- Expectations ---
        $this->queryBuilder->from(SMSHistory::class, 'h')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->andWhere('h.phoneNumber LIKE :search')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setParameter('search', '%' . $searchTerm . '%')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->orderBy('h.createdAt', 'DESC')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setMaxResults(50)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setFirstResult(0)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->query->getResult()->shouldBeCalled()->willReturn($expectedResult);
        // --- End Expectations ---

        $result = $this->repository->findByCriteria($criteria);
        $this->assertSame($expectedResult, $result);
    }

    /**
     * Test finding records by criteria: search term only (message content).
     * @test
     */
    public function findByCriteriaSearchMessageOnly(): void
    {
        $searchTerm = 'urgent';
        $criteria = ['search' => $searchTerm];
        $expectedResult = [$this->createMockSmsHistory(104, 7)];

        // --- Expectations ---
        $this->queryBuilder->from(SMSHistory::class, 'h')->shouldBeCalled()->willReturn($this->queryBuilder);
        // Expect search on phone number OR message
        $this->queryBuilder->andWhere('(h.phoneNumber LIKE :search OR h.message LIKE :search)')
            ->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setParameter('search', '%' . $searchTerm . '%')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->orderBy('h.createdAt', 'DESC')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setMaxResults(50)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setFirstResult(0)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->query->getResult()->shouldBeCalled()->willReturn($expectedResult);
        // --- End Expectations ---

        // Re-instantiate repository for this specific test to adjust QB mock
        // This is needed because the 'search' logic changed in the implementation
        $this->queryBuilder = $this->prophesize(QueryBuilder::class);
        $this->queryBuilder->select(Argument::any())->willReturn($this->queryBuilder);
        $this->queryBuilder->from(SMSHistory::class, 'h')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->andWhere('(h.phoneNumber LIKE :search OR h.message LIKE :search)') // Expect OR condition
            ->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setParameter('search', '%' . $searchTerm . '%')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->orderBy('h.createdAt', 'DESC')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setMaxResults(50)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setFirstResult(0)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->getQuery()->willReturn($this->query->reveal());
        $this->entityManager->createQueryBuilder()->willReturn($this->queryBuilder->reveal());
        $metadata = $this->prophesize(ClassMetadata::class); // Need metadata again
        $this->entityManager->getClassMetadata(SMSHistory::class)->willReturn($metadata->reveal());
        $this->repository = new SMSHistoryRepository($this->entityManager->reveal(), $metadata->reveal());


        $this->query->getResult()->shouldBeCalled()->willReturn($expectedResult); // Set expectation on the original query mock

        $result = $this->repository->findByCriteria($criteria);
        $this->assertSame($expectedResult, $result);
    }


    /**
     * Test finding records by criteria: segmentId only.
     * @test
     * @group todo
     * @markTestSkipped This test needs adjustment based on how segmentId filtering is actually implemented (e.g., requires joins).
     */
    public function findByCriteriaSegmentIdOnly(): void
    {
        $this->markTestSkipped('Segment ID filtering test needs implementation details.');
        // $criteria = ['segmentId' => 10];
        // $expectedResult = [$this->createMockSmsHistory(105, 8)];

        // // --- Expectations ---
        // // This likely requires joins, adjust the query builder mock accordingly
        // // e.g., $this->queryBuilder->leftJoin(...)
        // // e.g., $this->queryBuilder->andWhere('s.id = :segmentId')
        // $this->queryBuilder->from(SMSHistory::class, 'h')->shouldBeCalled()->willReturn($this->queryBuilder);
        // // Add join and where clause expectations here
        // $this->queryBuilder->setParameter('segmentId', $criteria['segmentId'])->shouldBeCalled()->willReturn($this->queryBuilder);
        // $this->queryBuilder->orderBy('h.createdAt', 'DESC')->shouldBeCalled()->willReturn($this->queryBuilder);
        // $this->queryBuilder->setMaxResults(50)->shouldBeCalled()->willReturn($this->queryBuilder);
        // $this->queryBuilder->setFirstResult(0)->shouldBeCalled()->willReturn($this->queryBuilder);
        // $this->query->getResult()->shouldBeCalled()->willReturn($expectedResult);
        // // --- End Expectations ---

        // $result = $this->repository->findByCriteria($criteria);
        // $this->assertSame($expectedResult, $result);
    }

    /**
     * Test finding records by criteria: combination of userId and status.
     * @test
     */
    public function findByCriteriaUserIdAndStatus(): void
    {
        $userId = 2;
        $status = 'FAILED';
        $criteria = ['userId' => $userId, 'status' => $status];
        $expectedResult = [$this->createMockSmsHistory(106, $userId)];

        // --- Expectations ---
        $this->queryBuilder->from(SMSHistory::class, 'h')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->andWhere('h.user = :userId')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setParameter('userId', $userId)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->andWhere('h.status = :status')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setParameter('status', $status)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->orderBy('h.createdAt', 'DESC')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setMaxResults(50)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setFirstResult(0)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->query->getResult()->shouldBeCalled()->willReturn($expectedResult);
        // --- End Expectations ---

        $result = $this->repository->findByCriteria($criteria);
        $this->assertSame($expectedResult, $result);
    }

    /**
     * Test finding records by criteria: combination of search and status.
     * @test
     */
    public function findByCriteriaSearchAndStatus(): void
    {
        $searchTerm = '9876';
        $status = 'SENT';
        $criteria = ['search' => $searchTerm, 'status' => $status];
        $expectedResult = [$this->createMockSmsHistory(107, 9)];

        // --- Expectations ---
        $this->queryBuilder->from(SMSHistory::class, 'h')->shouldBeCalled()->willReturn($this->queryBuilder);
        // Expect search on phone number OR message
        $this->queryBuilder->andWhere('(h.phoneNumber LIKE :search OR h.message LIKE :search)')
            ->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setParameter('search', '%' . $searchTerm . '%')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->andWhere('h.status = :status')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setParameter('status', $status)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->orderBy('h.createdAt', 'DESC')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setMaxResults(50)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setFirstResult(0)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->query->getResult()->shouldBeCalled()->willReturn($expectedResult);
        // --- End Expectations ---

        // Re-instantiate repository for this specific test to adjust QB mock
        $this->queryBuilder = $this->prophesize(QueryBuilder::class);
        $this->queryBuilder->select(Argument::any())->willReturn($this->queryBuilder);
        $this->queryBuilder->from(SMSHistory::class, 'h')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->andWhere('(h.phoneNumber LIKE :search OR h.message LIKE :search)') // Expect OR condition
            ->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setParameter('search', '%' . $searchTerm . '%')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->andWhere('h.status = :status')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setParameter('status', $status)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->orderBy('h.createdAt', 'DESC')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setMaxResults(50)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setFirstResult(0)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->getQuery()->willReturn($this->query->reveal());
        $this->entityManager->createQueryBuilder()->willReturn($this->queryBuilder->reveal());
        $metadata = $this->prophesize(ClassMetadata::class); // Need metadata again
        $this->entityManager->getClassMetadata(SMSHistory::class)->willReturn($metadata->reveal());
        $this->repository = new SMSHistoryRepository($this->entityManager->reveal(), $metadata->reveal());

        $this->query->getResult()->shouldBeCalled()->willReturn($expectedResult); // Set expectation on the original query mock


        $result = $this->repository->findByCriteria($criteria);
        $this->assertSame($expectedResult, $result);
    }

    /**
     * Test finding records by criteria: all filters combined (excluding segmentId for now).
     * @test
     */
    public function findByCriteriaMultipleFilters(): void
    {
        $userId = 3;
        $status = 'SENT';
        $searchTerm = '555';
        // $segmentId = 8; // Skipping segmentId for now
        $limit = 20;
        $offset = 10;
        $criteria = [
            'userId' => $userId,
            'status' => $status,
            'search' => $searchTerm,
            // 'segmentId' => $segmentId
        ];
        $expectedResult = [$this->createMockSmsHistory(108, $userId)];

        // --- Expectations ---
        $this->queryBuilder->from(SMSHistory::class, 'h')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->andWhere('h.user = :userId')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setParameter('userId', $userId)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->andWhere('h.status = :status')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setParameter('status', $status)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->andWhere('(h.phoneNumber LIKE :search OR h.message LIKE :search)')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setParameter('search', '%' . $searchTerm . '%')->shouldBeCalled()->willReturn($this->queryBuilder);
        // Add segmentId join/where expectations here when implemented
        $this->queryBuilder->orderBy('h.createdAt', 'DESC')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setMaxResults($limit)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setFirstResult($offset)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->query->getResult()->shouldBeCalled()->willReturn($expectedResult);
        // --- End Expectations ---

        // Re-instantiate repository for this specific test to adjust QB mock
        $this->queryBuilder = $this->prophesize(QueryBuilder::class);
        $this->queryBuilder->select(Argument::any())->willReturn($this->queryBuilder);
        $this->queryBuilder->from(SMSHistory::class, 'h')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->andWhere('h.user = :userId')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setParameter('userId', $userId)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->andWhere('h.status = :status')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setParameter('status', $status)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->andWhere('(h.phoneNumber LIKE :search OR h.message LIKE :search)')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setParameter('search', '%' . $searchTerm . '%')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->orderBy('h.createdAt', 'DESC')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setMaxResults($limit)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setFirstResult($offset)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->getQuery()->willReturn($this->query->reveal());
        $this->entityManager->createQueryBuilder()->willReturn($this->queryBuilder->reveal());
        $metadata = $this->prophesize(ClassMetadata::class); // Need metadata again
        $this->entityManager->getClassMetadata(SMSHistory::class)->willReturn($metadata->reveal());
        $this->repository = new SMSHistoryRepository($this->entityManager->reveal(), $metadata->reveal());

        $this->query->getResult()->shouldBeCalled()->willReturn($expectedResult); // Set expectation on the original query mock


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
        $expectedResult = [$this->createMockSmsHistory(109, 10)];

        // --- Expectations ---
        $this->queryBuilder->from(SMSHistory::class, 'h')->shouldBeCalled()->willReturn($this->queryBuilder);
        // No 'andWhere' or 'setParameter' calls expected for empty criteria
        $this->queryBuilder->andWhere(Argument::type('string'))->shouldNotBeCalled();
        $this->queryBuilder->setParameter(Argument::type('string'), Argument::any())->shouldNotBeCalled();
        $this->queryBuilder->orderBy('h.createdAt', 'DESC')->shouldBeCalled()->willReturn($this->queryBuilder);
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
        $criteria = ['userId' => $userId, 'status' => null, 'search' => null, 'segmentId' => null];
        $expectedResult = [$this->createMockSmsHistory(110, $userId)];

        // --- Expectations ---
        $this->queryBuilder->from(SMSHistory::class, 'h')->shouldBeCalled()->willReturn($this->queryBuilder);
        // Only expect userId condition
        $this->queryBuilder->andWhere('h.user = :userId')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setParameter('userId', $userId)->shouldBeCalled()->willReturn($this->queryBuilder);
        // Ensure other conditions are NOT added for null values
        $this->queryBuilder->andWhere(Argument::containingString(':status'))->shouldNotBeCalled();
        $this->queryBuilder->andWhere(Argument::containingString(':search'))->shouldNotBeCalled();
        // Add segmentId check when implemented
        $this->queryBuilder->orderBy('h.createdAt', 'DESC')->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setMaxResults(50)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->queryBuilder->setFirstResult(0)->shouldBeCalled()->willReturn($this->queryBuilder);
        $this->query->getResult()->shouldBeCalled()->willReturn($expectedResult);
        // --- End Expectations ---

        $result = $this->repository->findByCriteria($criteria);
        $this->assertSame($expectedResult, $result);
    }
}
