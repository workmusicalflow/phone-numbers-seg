<?php

namespace App\Tests\GraphQL\DataLoaders;

use App\Entities\ContactGroup;
use App\Entities\ContactGroupMembership;
use App\GraphQL\DataLoaders\ContactGroupDataLoader;
use App\GraphQL\Formatters\GraphQLFormatterInterface;
use App\Repositories\Interfaces\ContactGroupMembershipRepositoryInterface;
use App\Repositories\Interfaces\ContactGroupRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\AbstractQuery;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ContactGroupDataLoaderTest extends TestCase
{
    private $membershipRepository;
    private $groupRepository;
    private $formatter;
    private $logger;
    private $dataLoader;
    private $entityManager;
    private $queryBuilder;
    private $query;

    protected function setUp(): void
    {
        $this->membershipRepository = $this->createMock(ContactGroupMembershipRepositoryInterface::class);
        $this->groupRepository = $this->createMock(ContactGroupRepositoryInterface::class);
        $this->formatter = $this->createMock(GraphQLFormatterInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        // Mock entity manager and query builder for testing the custom query
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(AbstractQuery::class);
        
        // Set up entity manager mock to return query builder
        $this->membershipRepository->method('getEntityManager')
            ->willReturn($this->entityManager);
        
        $this->entityManager->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);
            
        $this->queryBuilder->method('select')->willReturnSelf();
        $this->queryBuilder->method('from')->willReturnSelf();
        $this->queryBuilder->method('where')->willReturnSelf();
        $this->queryBuilder->method('setParameter')->willReturnSelf();
        $this->queryBuilder->method('expr')->willReturnSelf();
        $this->queryBuilder->method('in')->willReturn('condition');
        $this->queryBuilder->method('getQuery')->willReturn($this->query);
        
        $this->dataLoader = new ContactGroupDataLoader(
            $this->membershipRepository,
            $this->groupRepository,
            $this->formatter,
            $this->logger
        );
        
        // Set a user ID for security filtering
        $this->dataLoader->setUserId(1);
    }

    public function testBatchLoadContactGroups(): void
    {
        $contactIds = [1, 2, 3];
        
        // Set up memberships
        $membership1 = new ContactGroupMembership();
        $membership1->setContactId(1);
        $membership1->setGroupId(101);
        
        $membership2 = new ContactGroupMembership();
        $membership2->setContactId(1);
        $membership2->setGroupId(102);
        
        $membership3 = new ContactGroupMembership();
        $membership3->setContactId(2);
        $membership3->setGroupId(101);
        
        $memberships = [$membership1, $membership2, $membership3];
        
        // Set up groups
        $group1 = new ContactGroup();
        $group1->setId(101);
        $group1->setName('Group 101');
        $group1->setUserId(1);
        
        $group2 = new ContactGroup();
        $group2->setId(102);
        $group2->setName('Group 102');
        $group2->setUserId(1);
        
        $groups = [$group1, $group2];
        
        // Set up formatted groups
        $formattedGroup1 = [
            'id' => 101,
            'name' => 'Group 101',
            'contactCount' => 2
        ];
        
        $formattedGroup2 = [
            'id' => 102,
            'name' => 'Group 102',
            'contactCount' => 1
        ];
        
        // Configure mocks
        // Mock the new findByContactIds method to return grouped memberships
        $membershipsByContactId = [
            1 => [$membership1, $membership2],
            2 => [$membership3],
            3 => []
        ];
        $this->membershipRepository->method('findByContactIds')
            ->with($contactIds)
            ->willReturn($membershipsByContactId);
            
        // Still need to configure getResult for backward compatibility
        $this->query->method('getResult')->willReturn($memberships);
        
        $this->groupRepository->method('findByIds')
            ->with([101, 102], 1)
            ->willReturn($groups);
            
        $this->membershipRepository->method('countByGroupId')
            ->willReturnMap([
                [101, 2],
                [102, 1]
            ]);
            
        $this->formatter->method('formatContactGroup')
            ->willReturnMap([
                [$group1, 2, $formattedGroup1],
                [$group2, 1, $formattedGroup2]
            ]);
        
        // Execute the dataloader batch function
        $results = $this->dataLoader->batchLoadContactGroups($contactIds);
        
        // Contact 1 should have both groups
        $this->assertCount(3, $results);
        $this->assertCount(2, $results[0]);
        $this->assertEquals($formattedGroup1, $results[0][0]);
        $this->assertEquals($formattedGroup2, $results[0][1]);
        
        // Contact 2 should have one group
        $this->assertCount(1, $results[1]);
        $this->assertEquals($formattedGroup1, $results[1][0]);
        
        // Contact 3 should have no groups
        $this->assertCount(0, $results[2]);
    }

    public function testEmptyBatchLoad(): void
    {
        $contactIds = [5, 6];
        
        // Configure the mock to return empty results
        $this->membershipRepository->method('findByContactIds')
            ->with($contactIds)
            ->willReturn([5 => [], 6 => []]);
            
        $this->query->method('getResult')->willReturn([]);
        
        $results = $this->dataLoader->batchLoadContactGroups($contactIds);
        
        // Should return empty arrays for both contacts
        $this->assertCount(2, $results);
        $this->assertCount(0, $results[0]);
        $this->assertCount(0, $results[1]);
    }

    public function testExceptionHandling(): void
    {
        $contactIds = [1, 2];
        
        // Configure the mock to throw an exception
        $this->membershipRepository->method('findByContactIds')
            ->with($contactIds)
            ->willThrowException(new \Exception('Database error'));
        
        $results = $this->dataLoader->batchLoadContactGroups($contactIds);
        
        // Should return empty arrays for both contacts on error
        $this->assertCount(2, $results);
        $this->assertCount(0, $results[0]);
        $this->assertCount(0, $results[1]);
    }
}