<?php

namespace Tests\GraphQL\Resolvers;

use App\GraphQL\Resolvers\ContactResolver;
use App\Repositories\Interfaces\ContactRepositoryInterface;
use App\Repositories\Interfaces\ContactGroupRepositoryInterface;
use App\Repositories\Interfaces\ContactGroupMembershipRepositoryInterface;
use App\Services\Interfaces\AuthServiceInterface;
use App\GraphQL\Formatters\GraphQLFormatterInterface;
use App\Entities\User;
use App\Entities\Contact;
use App\Entities\ContactGroupMembership; // Import for mocking
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy; // Import ObjectProphecy
use Psr\Log\LoggerInterface;
use Exception;

/**
 * Test class for ContactResolver
 *
 * @covers \App\GraphQL\Resolvers\ContactResolver
 */
class ContactResolverTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $contactRepository;
    private ObjectProphecy $groupRepository;
    private ObjectProphecy $membershipRepository;
    private ObjectProphecy $authService;
    private ObjectProphecy $formatter;
    private ObjectProphecy $logger;
    private ContactResolver $resolver;
    private ObjectProphecy $userProphecy; // To store user mock

    protected function setUp(): void
    {
        $this->contactRepository = $this->prophesize(ContactRepositoryInterface::class);
        $this->groupRepository = $this->prophesize(ContactGroupRepositoryInterface::class);
        $this->membershipRepository = $this->prophesize(ContactGroupMembershipRepositoryInterface::class);
        $this->authService = $this->prophesize(AuthServiceInterface::class);
        $this->formatter = $this->prophesize(GraphQLFormatterInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);

        // Mock user
        $this->userProphecy = $this->prophesize(User::class);
        $this->userProphecy->getId()->willReturn(1); // Example user ID
        $this->userProphecy->isAdmin()->willReturn(false); // Default to non-admin

        // Default auth service behavior
        $this->authService->getCurrentUser()->willReturn($this->userProphecy->reveal());

        // Default formatter behavior - return a simple array structure
        $this->formatter->formatContact(Argument::type(Contact::class))->will(function ($args) {
            $contact = $args[0];
            return [
                'id' => $contact->getId() ?? 'mock_contact_id_' . rand(), // Ensure unique mock IDs
                'phoneNumber' => '+225' . rand(100000000, 999999999), // Mock phone number
                // Add other fields if needed by tests
            ];
        });
        // Mock formatter for array of contacts
        $this->formatter->formatContacts(Argument::type('array'))->will(function ($args) {
            $contacts = $args[0];
            $formatted = [];
            foreach ($contacts as $contact) {
                $formatted[] = [
                    'id' => $contact->getId() ?? 'mock_contact_id_' . rand(),
                    'phoneNumber' => '+225' . rand(100000000, 999999999),
                ];
            }
            return $formatted;
        });


        $this->resolver = new ContactResolver(
            $this->contactRepository->reveal(),
            $this->groupRepository->reveal(),
            $this->membershipRepository->reveal(),
            $this->authService->reveal(),
            $this->formatter->reveal(),
            $this->logger->reveal()
        );
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
     * Test resolveContacts with basic arguments (limit, offset).
     * @test
     */
    public function resolveContactsBasic(): void
    {
        $userId = 1;
        $limit = 10;
        $offset = 0;
        $args = ['limit' => $limit, 'offset' => $offset];
        $expectedCriteria = ['userId' => $userId]; // Always filters by current user
        $mockContacts = [$this->createMockContact(301, $userId)];
        $expectedFormattedResult = [['id' => 301, 'phoneNumber' => '+225...']]; // Example

        $this->userProphecy->getId()->shouldBeCalled()->willReturn($userId); // Ensure getId is called

        $this->contactRepository->findByCriteria($expectedCriteria, $limit, $offset)
            ->shouldBeCalledOnce()
            ->willReturn($mockContacts);

        // Expect formatter to be called with the mock contacts
        $this->formatter->formatContacts($mockContacts)
            ->shouldBeCalledOnce()
            ->willReturn($expectedFormattedResult);


        $result = $this->resolver->resolveContacts($args, null);
        $this->assertSame($expectedFormattedResult, $result);
    }

    /**
     * Test resolveContacts with search filter.
     * @test
     */
    public function resolveContactsWithSearchFilter(): void
    {
        $userId = 1;
        $searchTerm = 'John';
        $args = ['search' => $searchTerm];
        $expectedCriteria = ['userId' => $userId, 'search' => $searchTerm];
        $mockContacts = []; // Assume no results for simplicity
        $expectedFormattedResult = [];

        $this->userProphecy->getId()->shouldBeCalled()->willReturn($userId);

        $this->contactRepository->findByCriteria($expectedCriteria, 100, 0) // Default limit/offset
            ->shouldBeCalledOnce()
            ->willReturn($mockContacts);

        $this->formatter->formatContacts($mockContacts)
            ->shouldBeCalledOnce()
            ->willReturn($expectedFormattedResult);

        $result = $this->resolver->resolveContacts($args, null);
        $this->assertSame($expectedFormattedResult, $result);
    }

    /**
     * Test resolveContacts with groupId filter.
     * @test
     */
    public function resolveContactsWithGroupIdFilter(): void
    {
        $userId = 1;
        $groupId = 3;
        $args = ['groupId' => $groupId];
        $expectedCriteria = ['userId' => $userId, 'groupId' => $groupId];
        $mockContacts = [];
        $expectedFormattedResult = [];

        $this->userProphecy->getId()->shouldBeCalled()->willReturn($userId);

        $this->contactRepository->findByCriteria($expectedCriteria, 100, 0)
            ->shouldBeCalledOnce()
            ->willReturn($mockContacts);

        $this->formatter->formatContacts($mockContacts)
            ->shouldBeCalledOnce()
            ->willReturn($expectedFormattedResult);

        $result = $this->resolver->resolveContacts($args, null);
        $this->assertSame($expectedFormattedResult, $result);
    }

    /**
     * Test resolveContacts with all filters combined.
     * @test
     */
    public function resolveContactsWithAllFilters(): void
    {
        $userId = 1;
        $limit = 15;
        $offset = 5;
        $searchTerm = 'Doe';
        $groupId = 2;
        $args = [
            'limit' => $limit,
            'offset' => $offset,
            'search' => $searchTerm,
            'groupId' => $groupId
        ];
        $expectedCriteria = [
            'userId' => $userId,
            'search' => $searchTerm,
            'groupId' => $groupId
        ];
        $mockContacts = [$this->createMockContact(302, $userId)];
        $expectedFormattedResult = [['id' => 302, 'phoneNumber' => '+225...']];

        $this->userProphecy->getId()->shouldBeCalled()->willReturn($userId);

        $this->contactRepository->findByCriteria($expectedCriteria, $limit, $offset)
            ->shouldBeCalledOnce()
            ->willReturn($mockContacts);

        $this->formatter->formatContacts($mockContacts)
            ->shouldBeCalledOnce()
            ->willReturn($expectedFormattedResult);

        $result = $this->resolver->resolveContacts($args, null);
        $this->assertSame($expectedFormattedResult, $result);
    }

    /**
     * Test resolveContacts throws exception when not authenticated.
     * @test
     */
    public function resolveContactsThrowsIfNotAuthenticated(): void
    {
        $this->authService->getCurrentUser()->willReturn(null); // Simulate not logged in

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("User not authenticated");

        $this->resolver->resolveContacts([], null);

        // Verify repository was not called
        $this->contactRepository->findByCriteria(Argument::any(), Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
    }

    /**
     * Test resolveContacts as admin (should not automatically filter by userId).
     * @test
     */
    public function resolveContactsAsAdmin(): void
    {
        $limit = 25;
        $offset = 10;
        $args = ['limit' => $limit, 'offset' => $offset];
        // No userId expected in criteria when admin doesn't specify it
        $expectedCriteria = [];
        $mockContacts = [$this->createMockContact(303, 5), $this->createMockContact(304, 6)]; // Contacts from different users
        $expectedFormattedResult = [['id' => 303, '...'], ['id' => 304, '...']];

        // Mock admin user
        $this->userProphecy->isAdmin()->willReturn(true);
        $this->authService->getCurrentUser()->willReturn($this->userProphecy->reveal());
        // getId should NOT be called when admin doesn't provide userId arg
        $this->userProphecy->getId()->shouldNotBeCalled();

        $this->contactRepository->findByCriteria($expectedCriteria, $limit, $offset)
            ->shouldBeCalledOnce()
            ->willReturn($mockContacts);

        $this->formatter->formatContacts($mockContacts)
            ->shouldBeCalledOnce()
            ->willReturn($expectedFormattedResult);

        $result = $this->resolver->resolveContacts($args, null);
        $this->assertSame($expectedFormattedResult, $result);
    }

    /**
     * Test resolveContacts as admin WITH userId filter.
     * @test
     */
    public function resolveContactsAsAdminWithUserIdFilter(): void
    {
        $targetUserId = 5;
        $limit = 25;
        $offset = 10;
        $args = ['limit' => $limit, 'offset' => $offset, 'userId' => $targetUserId];
        // userId IS expected in criteria when admin specifies it
        $expectedCriteria = ['userId' => $targetUserId];
        $mockContacts = [$this->createMockContact(305, $targetUserId)];
        $expectedFormattedResult = [['id' => 305, '...']];

        // Mock admin user
        $this->userProphecy->isAdmin()->willReturn(true);
        $this->authService->getCurrentUser()->willReturn($this->userProphecy->reveal());
        // getId should NOT be called
        $this->userProphecy->getId()->shouldNotBeCalled();

        $this->contactRepository->findByCriteria($expectedCriteria, $limit, $offset)
            ->shouldBeCalledOnce()
            ->willReturn($mockContacts);

        $this->formatter->formatContacts($mockContacts)
            ->shouldBeCalledOnce()
            ->willReturn($expectedFormattedResult);

        $result = $this->resolver->resolveContacts($args, null);
        $this->assertSame($expectedFormattedResult, $result);
    }

    // ==================================
    // Tests for resolveContact
    // ==================================

    /**
     * @test
     */
    public function resolveContactSuccessfully(): void
    {
        $userId = 1;
        $contactId = 101;
        $args = ['id' => $contactId];
        $mockContact = $this->createMockContact($contactId, $userId);
        $expectedFormattedResult = ['id' => $contactId, 'phoneNumber' => '+225...'];

        $this->userProphecy->getId()->shouldBeCalled()->willReturn($userId);
        $this->contactRepository->findById($contactId)->shouldBeCalledOnce()->willReturn($mockContact);
        // Need to mock getUserId on the mockContact itself for the authorization check
        // Prophecy automatically handles getter calls on mocked objects if they exist
        // $mockContact->getUserId()->shouldBeCalled()->willReturn($userId); // No need to explicitly mock if getter exists
        $this->formatter->formatContact($mockContact)->shouldBeCalledOnce()->willReturn($expectedFormattedResult);

        $result = $this->resolver->resolveContact($args, null);
        $this->assertSame($expectedFormattedResult, $result);
    }

    /**
     * @test
     */
    public function resolveContactReturnsNullIfNotFound(): void
    {
        $userId = 1;
        $contactId = 999;
        $args = ['id' => $contactId];

        $this->userProphecy->getId()->shouldBeCalled()->willReturn($userId);
        $this->contactRepository->findById($contactId)->shouldBeCalledOnce()->willReturn(null);
        $this->formatter->formatContact(Argument::any())->shouldNotBeCalled();

        $result = $this->resolver->resolveContact($args, null);
        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function resolveContactReturnsNullIfNotAuthorized(): void
    {
        $userId = 1; // Current user
        $otherUserId = 2; // Contact owner
        $contactId = 102;
        $args = ['id' => $contactId];
        $mockContact = $this->createMockContact($contactId, $otherUserId); // Contact belongs to user 2

        $this->userProphecy->getId()->shouldBeCalled()->willReturn($userId);
        $this->contactRepository->findById($contactId)->shouldBeCalledOnce()->willReturn($mockContact);
        // getUserId will be called implicitly by the resolver logic on the revealed mock
        $this->formatter->formatContact(Argument::any())->shouldNotBeCalled();
        $this->logger->warning(Argument::containingString('attempted to access contact'))->shouldBeCalled();


        $result = $this->resolver->resolveContact($args, null);
        $this->assertNull($result); // Should return null as per implementation
    }

    /**
     * @test
     */
    public function resolveContactThrowsIfNotAuthenticated(): void
    {
        $args = ['id' => 101];
        $this->authService->getCurrentUser()->willReturn(null);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("User not authenticated");

        $this->resolver->resolveContact($args, null);
        $this->contactRepository->findById(Argument::any())->shouldNotHaveBeenCalled();
    }

    // ==================================
    // Tests for resolveSearchContacts
    // ==================================
    /**
     * @test
     */
    public function resolveSearchContactsSuccessfully(): void
    {
        $userId = 1;
        $query = "search term";
        $limit = 20;
        $offset = 5;
        $args = ['query' => $query, 'limit' => $limit, 'offset' => $offset];
        $mockContacts = [$this->createMockContact(310, $userId)];
        $expectedFormattedResult = [['id' => 310, '...']];

        $this->userProphecy->getId()->shouldBeCalled()->willReturn($userId);
        $this->contactRepository->searchByUserId($query, $userId, $limit, $offset)
            ->shouldBeCalledOnce()
            ->willReturn($mockContacts);
        $this->formatter->formatContacts($mockContacts)
            ->shouldBeCalledOnce()
            ->willReturn($expectedFormattedResult);

        $result = $this->resolver->resolveSearchContacts($args, null);
        $this->assertSame($expectedFormattedResult, $result);
    }

    /**
     * @test
     */
    public function resolveSearchContactsReturnsEmptyForEmptyQuery(): void
    {
        $args = ['query' => ''];
        $this->userProphecy->getId()->shouldBeCalled()->willReturn(1); // Still need user context

        $result = $this->resolver->resolveSearchContacts($args, null);
        $this->assertSame([], $result);
        $this->contactRepository->searchByUserId(Argument::any(), Argument::any(), Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
        $this->logger->warning(Argument::containingString('Empty query provided'))->shouldBeCalled();
    }

    /**
     * @test
     */
    public function resolveSearchContactsThrowsIfNotAuthenticated(): void
    {
        $args = ['query' => 'test'];
        $this->authService->getCurrentUser()->willReturn(null);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("User not authenticated");

        $this->resolver->resolveSearchContacts($args, null);
        $this->contactRepository->searchByUserId(Argument::any(), Argument::any(), Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
    }


    // ==================================
    // Tests for resolveContactsCount
    // ==================================

    /**
     * @test
     */
    public function resolveContactsCountSuccessfully(): void
    {
        $userId = 1;
        $expectedCount = 42;

        $this->userProphecy->getId()->shouldBeCalled()->willReturn($userId);
        $this->contactRepository->countByUserId($userId)
            ->shouldBeCalledOnce()
            ->willReturn($expectedCount);

        $result = $this->resolver->resolveContactsCount([], null);
        $this->assertSame($expectedCount, $result);
    }

    /**
     * @test
     */
    public function resolveContactsCountThrowsIfNotAuthenticated(): void
    {
        $this->authService->getCurrentUser()->willReturn(null);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("User not authenticated");

        $this->resolver->resolveContactsCount([], null);
        $this->contactRepository->countByUserId(Argument::any())->shouldNotHaveBeenCalled();
    }

    // ==================================
    // Tests for resolveContactGroups (Field Resolver)
    // ==================================
    // Note: Field resolvers receive the parent object ($contact array in this case) as the first argument.

    /**
     * @test
     */
    public function resolveContactGroupsSuccessfully(): void
    {
        $userId = 1;
        $contactId = 101;
        $parentContactArray = ['id' => $contactId]; // Simulate parent data from GraphQL execution
        $mockContact = $this->createMockContact($contactId, $userId); // For ownership check

        // Mock memberships
        $membership1 = $this->prophesize(ContactGroupMembership::class);
        $membership1->getGroupId()->willReturn(5);
        $membership2 = $this->prophesize(ContactGroupMembership::class);
        $membership2->getGroupId()->willReturn(6);
        $memberships = [$membership1->reveal(), $membership2->reveal()];
        $groupIds = [5, 6];

        // Mock groups
        $group5 = $this->prophesize(\App\Entities\ContactGroup::class); // Use correct entity
        $group5->getId()->willReturn(5);
        $group6 = $this->prophesize(\App\Entities\ContactGroup::class);
        $group6->getId()->willReturn(6);
        $groups = [$group5->reveal(), $group6->reveal()];

        $expectedFormattedResult = [['id' => 5, 'name' => 'Group 5'], ['id' => 6, 'name' => 'Group 6']];

        // Auth check
        $this->userProphecy->getId()->shouldBeCalled()->willReturn($userId);
        // Ownership check
        $this->contactRepository->findById($contactId)->shouldBeCalledOnce()->willReturn($mockContact);
        // getUserId will be called implicitly
        // Fetch memberships
        $this->membershipRepository->findByContactId($contactId)->shouldBeCalledOnce()->willReturn($memberships);
        // Fetch groups
        $this->groupRepository->findByIds($groupIds, $userId)->shouldBeCalledOnce()->willReturn($groups);
        // Fetch contact counts for formatting (assuming formatter needs it)
        $this->groupRepository->getContactsInGroup(5, 1000, 0)->shouldBeCalled()->willReturn([/* contacts */]);
        $this->groupRepository->getContactsInGroup(6, 1000, 0)->shouldBeCalled()->willReturn([/* contacts */]);
        // Format groups
        $this->formatter->formatContactGroup($group5->reveal(), Argument::type('integer'))->shouldBeCalled()->willReturn($expectedFormattedResult[0]);
        $this->formatter->formatContactGroup($group6->reveal(), Argument::type('integer'))->shouldBeCalled()->willReturn($expectedFormattedResult[1]);


        $result = $this->resolver->resolveContactGroups($parentContactArray, [], null);
        $this->assertSame($expectedFormattedResult, $result);
    }

    /**
     * @test
     */
    public function resolveContactGroupsReturnsEmptyIfNotAuthorized(): void
    {
        $userId = 1; // Current user
        $otherUserId = 2; // Contact owner
        $contactId = 102;
        $parentContactArray = ['id' => $contactId];
        $mockContact = $this->createMockContact($contactId, $otherUserId); // Contact belongs to user 2

        $this->userProphecy->getId()->shouldBeCalled()->willReturn($userId);
        $this->contactRepository->findById($contactId)->shouldBeCalledOnce()->willReturn($mockContact);
        // getUserId will be called implicitly

        $result = $this->resolver->resolveContactGroups($parentContactArray, [], null);
        $this->assertSame([], $result); // Expect empty array

        // Ensure further repo calls were not made
        $this->membershipRepository->findByContactId(Argument::any())->shouldNotHaveBeenCalled();
        $this->groupRepository->findByIds(Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     */
    public function resolveContactGroupsThrowsIfNotAuthenticated(): void
    {
        $parentContactArray = ['id' => 101];
        $this->authService->getCurrentUser()->willReturn(null);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("User not authenticated");

        $this->resolver->resolveContactGroups($parentContactArray, [], null);
    }

    // ==================================
    // Tests for resolveGroupsForContact (Query)
    // ==================================
    /**
     * @test
     */
    public function resolveGroupsForContactSuccessfully(): void
    {
        $userId = 1;
        $contactId = 101;
        $args = ['contactId' => $contactId];
        $mockContact = $this->createMockContact($contactId, $userId); // For ownership check

        // Mock memberships
        $membership1 = $this->prophesize(ContactGroupMembership::class);
        $membership1->getGroupId()->willReturn(5);
        $memberships = [$membership1->reveal()];
        $groupIds = [5];

        // Mock groups
        $group5 = $this->prophesize(\App\Entities\ContactGroup::class);
        $group5->getId()->willReturn(5);
        $groups = [$group5->reveal()];

        $expectedFormattedResult = [['id' => 5, 'name' => 'Group 5']];

        // Auth check
        $this->userProphecy->getId()->shouldBeCalled()->willReturn($userId);
        // Ownership check
        $this->contactRepository->findById($contactId)->shouldBeCalledOnce()->willReturn($mockContact);
        // getUserId will be called implicitly
        // Fetch memberships
        $this->membershipRepository->findByContactId($contactId)->shouldBeCalledOnce()->willReturn($memberships);
        // Fetch groups
        $this->groupRepository->findByIds($groupIds, $userId)->shouldBeCalledOnce()->willReturn($groups);
        // Fetch contact counts for formatting
        $this->groupRepository->getContactsInGroup(5, 1000, 0)->shouldBeCalled()->willReturn([/* contacts */]);
        // Format groups
        $this->formatter->formatContactGroup($group5->reveal(), Argument::type('integer'))->shouldBeCalled()->willReturn($expectedFormattedResult[0]);


        $result = $this->resolver->resolveGroupsForContact($args, null);
        $this->assertSame($expectedFormattedResult, $result);
    }

    /**
     * @test
     */
    public function resolveGroupsForContactReturnsEmptyIfNotAuthorized(): void
    {
        $userId = 1; // Current user
        $otherUserId = 2; // Contact owner
        $contactId = 102;
        $args = ['contactId' => $contactId];
        $mockContact = $this->createMockContact($contactId, $otherUserId); // Contact belongs to user 2

        $this->userProphecy->getId()->shouldBeCalled()->willReturn($userId);
        $this->contactRepository->findById($contactId)->shouldBeCalledOnce()->willReturn($mockContact);
        // getUserId will be called implicitly

        $result = $this->resolver->resolveGroupsForContact($args, null);
        $this->assertSame([], $result); // Expect empty array

        // Ensure further repo calls were not made
        $this->membershipRepository->findByContactId(Argument::any())->shouldNotHaveBeenCalled();
        $this->groupRepository->findByIds(Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     */
    public function resolveGroupsForContactThrowsIfNotAuthenticated(): void
    {
        $args = ['contactId' => 101];
        $this->authService->getCurrentUser()->willReturn(null);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("User not authenticated");

        $this->resolver->resolveGroupsForContact($args, null);
    }

    // ==================================
    // Tests for mutateCreateContact
    // ==================================

    /**
     * @test
     */
    public function mutateCreateContactSuccessfully(): void
    {
        $userId = 1;
        $args = [
            'name' => 'New Contact',
            'phoneNumber' => '+2250909090909',
            'email' => 'new@contact.com',
            'notes' => 'Some notes',
            'groupIds' => [5, 6] // Example group IDs
        ];
        $mockSavedContact = $this->createMockContact(401, $userId); // Mock the saved contact
        $expectedFormattedResult = ['id' => 401, 'name' => 'New Contact', /* ... */];

        // Auth check
        $this->userProphecy->getId()->shouldBeCalled()->willReturn($userId);

        // Expect repository save call (matching properties)
        $this->contactRepository->save(Argument::that(function (Contact $contact) use ($userId, $args) {
            return $contact->getUserId() === $userId &&
                $contact->getName() === $args['name'] &&
                $contact->getPhoneNumber() === $args['phoneNumber'] &&
                $contact->getEmail() === $args['email'] &&
                $contact->getNotes() === $args['notes'];
        }))->shouldBeCalledOnce()->willReturn($mockSavedContact);

        // Mock group validation and membership updates (simplified)
        $group5 = $this->prophesize(\App\Entities\ContactGroup::class);
        $group5->getUserId()->willReturn($userId); // Belongs to user
        $group6 = $this->prophesize(\App\Entities\ContactGroup::class);
        $group6->getUserId()->willReturn($userId); // Belongs to user
        $this->groupRepository->findById(5)->shouldBeCalled()->willReturn($group5->reveal());
        $this->groupRepository->findById(6)->shouldBeCalled()->willReturn($group6->reveal());
        $this->membershipRepository->findByContactId(401)->shouldBeCalled()->willReturn([]); // No existing memberships
        $this->membershipRepository->addContactToGroup(401, 5)->shouldBeCalled();
        $this->membershipRepository->addContactToGroup(401, 6)->shouldBeCalled();

        // Expect refetch after potential membership updates
        $this->contactRepository->findById(401)->shouldBeCalled()->willReturn($mockSavedContact);

        // Expect formatter call
        $this->formatter->formatContact($mockSavedContact)->shouldBeCalledOnce()->willReturn($expectedFormattedResult);

        // Act
        $result = $this->resolver->mutateCreateContact($args, null);

        // Assert
        $this->assertSame($expectedFormattedResult, $result);
    }

    /**
     * @test
     */
    public function mutateCreateContactThrowsIfNameMissing(): void
    {
        $args = ['phoneNumber' => '+2250909090909']; // Name missing
        $this->userProphecy->getId()->shouldBeCalled()->willReturn(1);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Nom et numéro de téléphone requis.");

        $this->resolver->mutateCreateContact($args, null);
        $this->contactRepository->save(Argument::any())->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     */
    public function mutateCreateContactThrowsIfPhoneNumberMissing(): void
    {
        $args = ['name' => 'Test Contact']; // Phone number missing
        $this->userProphecy->getId()->shouldBeCalled()->willReturn(1);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Nom et numéro de téléphone requis.");

        $this->resolver->mutateCreateContact($args, null);
        $this->contactRepository->save(Argument::any())->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     */
    public function mutateCreateContactThrowsIfNotAuthenticated(): void
    {
        $args = ['name' => 'Test', 'phoneNumber' => '+2250101010101'];
        $this->authService->getCurrentUser()->willReturn(null);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("User not authenticated");

        $this->resolver->mutateCreateContact($args, null);
        $this->contactRepository->save(Argument::any())->shouldNotHaveBeenCalled();
    }

    // ==================================
    // Tests for mutateUpdateContact
    // ==================================

    /**
     * @test
     */
    public function mutateUpdateContactSuccessfully(): void
    {
        $userId = 1;
        $contactId = 101;
        $args = [
            'id' => $contactId,
            'name' => 'Updated Name',
            'email' => 'updated@contact.com',
            'groupIds' => [6] // Update groups: remove from 5 (implicitly), keep 6
        ];
        $mockExistingContact = $this->createMockContact($contactId, $userId);
        $mockSavedContact = $this->createMockContact($contactId, $userId); // Assume save returns updated obj
        $expectedFormattedResult = ['id' => $contactId, 'name' => 'Updated Name', /* ... */];

        // Auth check
        $this->userProphecy->getId()->shouldBeCalled()->willReturn($userId);

        // Fetch existing contact & ownership check
        $this->contactRepository->findById($contactId)->shouldBeCalledTimes(2)->willReturn($mockExistingContact, $mockSavedContact); // Called once for check, once for refetch
        // getUserId called implicitly

        // Expect setters to be called on the existing contact mock
        $mockExistingContact->setName('Updated Name')->shouldBeCalled();
        $mockExistingContact->setEmail('updated@contact.com')->shouldBeCalled();
        // phoneNumber and notes should not be called as they are not in args

        // Expect save call
        $this->contactRepository->save($mockExistingContact)->shouldBeCalledOnce()->willReturn($mockSavedContact);

        // Mock group validation and membership updates
        $membership5 = $this->prophesize(ContactGroupMembership::class);
        $membership5->getGroupId()->willReturn(5);
        $membership6 = $this->prophesize(ContactGroupMembership::class);
        $membership6->getGroupId()->willReturn(6);
        $this->membershipRepository->findByContactId($contactId)->shouldBeCalled()->willReturn([$membership5->reveal(), $membership6->reveal()]); // Currently in 5 & 6
        $this->membershipRepository->removeContactFromGroup($contactId, 5)->shouldBeCalled(); // Remove from 5
        $this->membershipRepository->addContactToGroup(Argument::any(), Argument::any())->shouldNotBeCalled(); // No adds needed

        // Expect formatter call with refetched contact
        $this->formatter->formatContact($mockSavedContact)->shouldBeCalledOnce()->willReturn($expectedFormattedResult);

        // Act
        $result = $this->resolver->mutateUpdateContact($args, null);

        // Assert
        $this->assertSame($expectedFormattedResult, $result);
    }

    /**
     * @test
     */
    public function mutateUpdateContactThrowsIfNotFound(): void
    {
        $userId = 1;
        $contactId = 999;
        $args = ['id' => $contactId, 'name' => 'Update Fail'];

        $this->userProphecy->getId()->shouldBeCalled()->willReturn($userId);
        $this->contactRepository->findById($contactId)->shouldBeCalledOnce()->willReturn(null);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Contact non trouvé");

        $this->resolver->mutateUpdateContact($args, null);
        $this->contactRepository->save(Argument::any())->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     */
    public function mutateUpdateContactThrowsIfNotAuthorized(): void
    {
        $userId = 1; // Current user
        $otherUserId = 2; // Contact owner
        $contactId = 102;
        $args = ['id' => $contactId, 'name' => 'Update Fail'];
        $mockExistingContact = $this->createMockContact($contactId, $otherUserId);

        $this->userProphecy->getId()->shouldBeCalled()->willReturn($userId);
        $this->contactRepository->findById($contactId)->shouldBeCalledOnce()->willReturn($mockExistingContact);
        // getUserId called implicitly

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Contact non trouvé"); // Treats as not found

        $this->resolver->mutateUpdateContact($args, null);
        $this->contactRepository->save(Argument::any())->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     */
    public function mutateUpdateContactThrowsIfNotAuthenticated(): void
    {
        $args = ['id' => 101, 'name' => 'Update Fail'];
        $this->authService->getCurrentUser()->willReturn(null);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("User not authenticated");

        $this->resolver->mutateUpdateContact($args, null);
        $this->contactRepository->findById(Argument::any())->shouldNotHaveBeenCalled();
    }

    // ==================================
    // Tests for mutateDeleteContact
    // ==================================

    /**
     * @test
     */
    public function mutateDeleteContactSuccessfully(): void
    {
        $userId = 1;
        $contactId = 101;
        $args = ['id' => $contactId];
        $mockExistingContact = $this->createMockContact($contactId, $userId);

        // Auth check
        $this->userProphecy->getId()->shouldBeCalled()->willReturn($userId);
        // Fetch and ownership check
        $this->contactRepository->findById($contactId)->shouldBeCalledOnce()->willReturn($mockExistingContact);
        // getUserId called implicitly
        // Delete call
        $this->contactRepository->delete($mockExistingContact)->shouldBeCalledOnce()->willReturn(true);

        // Act
        $result = $this->resolver->mutateDeleteContact($args, null);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function mutateDeleteContactReturnsFalseIfNotFound(): void
    {
        $userId = 1;
        $contactId = 999;
        $args = ['id' => $contactId];

        $this->userProphecy->getId()->shouldBeCalled()->willReturn($userId);
        $this->contactRepository->findById($contactId)->shouldBeCalledOnce()->willReturn(null);

        // Act
        $result = $this->resolver->mutateDeleteContact($args, null);

        // Assert
        $this->assertFalse($result);
        $this->contactRepository->delete(Argument::any())->shouldNotHaveBeenCalled();
        $this->logger->warning(Argument::containingString('Attempted to delete non-existent contact'))->shouldBeCalled();
    }

    /**
     * @test
     */
    public function mutateDeleteContactReturnsFalseIfNotAuthorized(): void
    {
        $userId = 1; // Current user
        $otherUserId = 2; // Contact owner
        $contactId = 102;
        $args = ['id' => $contactId];
        $mockExistingContact = $this->createMockContact($contactId, $otherUserId);

        $this->userProphecy->getId()->shouldBeCalled()->willReturn($userId);
        $this->contactRepository->findById($contactId)->shouldBeCalledOnce()->willReturn($mockExistingContact);
        // getUserId called implicitly

        // Act
        $result = $this->resolver->mutateDeleteContact($args, null);

        // Assert
        $this->assertFalse($result);
        $this->contactRepository->delete(Argument::any())->shouldNotHaveBeenCalled();
        $this->logger->warning(Argument::containingString('attempted to delete contact'))->shouldBeCalled();
    }

    /**
     * @test
     */
    public function mutateDeleteContactThrowsIfNotAuthenticated(): void
    {
        $args = ['id' => 101];
        $this->authService->getCurrentUser()->willReturn(null);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("User not authenticated");

        $this->resolver->mutateDeleteContact($args, null);
        $this->contactRepository->findById(Argument::any())->shouldNotHaveBeenCalled();
    }
}
