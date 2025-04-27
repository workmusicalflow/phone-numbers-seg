<?php

namespace Tests\GraphQL\Controllers;

use App\GraphQL\Controllers\ContactGroupController;
use App\Repositories\Interfaces\ContactGroupRepositoryInterface;
use App\Entities\User;
use App\Entities\ContactGroup;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * Test class for ContactGroupController
 *
 * @covers \App\GraphQL\Controllers\ContactGroupController
 */
class ContactGroupControllerTest extends TestCase
{
    use ProphecyTrait;

    private $contactGroupRepository;
    private $logger;
    private $controller;
    private $userProphecy;

    protected function setUp(): void
    {
        $this->contactGroupRepository = $this->prophesize(ContactGroupRepositoryInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);

        // Mock user
        $this->userProphecy = $this->prophesize(User::class);
        $this->userProphecy->getId()->willReturn(1); // Example user ID

        $this->controller = new ContactGroupController(
            $this->contactGroupRepository->reveal(),
            $this->logger->reveal()
        );
    }

    /**
     * Test fetching contact groups for a user.
     * @test
     */
    public function contactGroupsFetchesSuccessfully(): void
    {
        $limit = 10;
        $offset = 0;
        $userId = 1;
        $user = $this->userProphecy->reveal();

        $this->contactGroupRepository->findByUserId($userId, $limit, $offset)
            ->shouldBeCalledOnce()
            ->willReturn([]); // Return empty array for simplicity

        $result = $this->controller->contactGroups($limit, $offset, $user);
        $this->assertIsArray($result);
    }

    /**
     * Test fetching a single contact group successfully.
     * @test
     */
    public function contactGroupFetchesSuccessfully(): void
    {
        $groupId = 5;
        $userId = 1;
        $user = $this->userProphecy->reveal();

        $groupProphecy = $this->prophesize(ContactGroup::class);
        $groupProphecy->getUserId()->willReturn($userId); // Group belongs to the user

        $this->contactGroupRepository->findById($groupId)
            ->shouldBeCalledOnce()
            ->willReturn($groupProphecy->reveal());

        $result = $this->controller->contactGroup($groupId, $user);
        $this->assertInstanceOf(ContactGroup::class, $result);
    }

    /**
     * Test fetching a contact group belonging to another user returns null.
     * @test
     */
    public function contactGroupReturnsNullForOtherUser(): void
    {
        $groupId = 5;
        $userId = 1; // Current user ID
        $otherUserId = 2; // Group owner ID
        $user = $this->userProphecy->reveal();

        $groupProphecy = $this->prophesize(ContactGroup::class);
        $groupProphecy->getUserId()->willReturn($otherUserId); // Group belongs to another user

        $this->contactGroupRepository->findById($groupId)
            ->shouldBeCalledOnce()
            ->willReturn($groupProphecy->reveal());

        $result = $this->controller->contactGroup($groupId, $user);
        $this->assertNull($result);
    }

    /**
     * Test creating a contact group successfully.
     * @test
     */
    public function createContactGroupSuccessfully(): void
    {
        $name = 'Test Group';
        $description = 'Test Description';
        $user = $this->userProphecy->reveal();

        $savedGroupProphecy = $this->prophesize(ContactGroup::class);

        $this->contactGroupRepository->save(Argument::type(ContactGroup::class))
            ->shouldBeCalledOnce()
            ->will(function ($args) use ($savedGroupProphecy, $name, $description) {
                $group = $args[0];
                $this->assertEquals($name, $group->getName());
                $this->assertEquals($description, $group->getDescription());
                $this->assertEquals(1, $group->getUserId()); // Assuming user ID 1 from mock
                return $savedGroupProphecy->reveal();
            });

        $result = $this->controller->createContactGroup($name, $description, $user);
        $this->assertInstanceOf(ContactGroup::class, $result);
    }

    /**
     * Test updating a contact group successfully.
     * @test
     */
    public function updateContactGroupSuccessfully(): void
    {
        $groupId = 5;
        $newName = 'Updated Group';
        $newDescription = 'Updated Desc';
        $userId = 1;
        $user = $this->userProphecy->reveal();

        $existingGroupProphecy = $this->prophesize(ContactGroup::class);
        $existingGroupProphecy->getUserId()->willReturn($userId);
        $existingGroupProphecy->setName($newName)->shouldBeCalled();
        $existingGroupProphecy->setDescription($newDescription)->shouldBeCalled();

        $this->contactGroupRepository->findById($groupId)
            ->shouldBeCalledOnce()
            ->willReturn($existingGroupProphecy->reveal());

        $this->contactGroupRepository->save($existingGroupProphecy->reveal())
            ->shouldBeCalledOnce()
            ->willReturn($existingGroupProphecy->reveal());

        $result = $this->controller->updateContactGroup($groupId, $newName, $newDescription, $user);
        $this->assertInstanceOf(ContactGroup::class, $result);
    }

    /**
     * Test deleting a contact group successfully.
     * @test
     */
    public function deleteContactGroupSuccessfully(): void
    {
        $groupId = 5;
        $userId = 1;
        $user = $this->userProphecy->reveal();

        $existingGroupProphecy = $this->prophesize(ContactGroup::class);
        $existingGroupProphecy->getUserId()->willReturn($userId);

        $this->contactGroupRepository->findById($groupId)
            ->shouldBeCalledOnce()
            ->willReturn($existingGroupProphecy->reveal());

        $this->contactGroupRepository->delete($existingGroupProphecy->reveal())
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $result = $this->controller->deleteContactGroup($groupId, $user);
        $this->assertTrue($result);
    }

    // Add tests for addContactToGroup, removeContactFromGroup, admin queries etc.
}
