<?php

namespace Tests\Services\Validators;

use PHPUnit\Framework\TestCase;
use App\Services\Validators\SenderNameValidator;
use App\Repositories\SenderNameRepository;
use App\Repositories\UserRepository;
use App\Models\SenderName;
use App\Models\User;
use App\Exceptions\ValidationException;

class SenderNameValidatorTest extends TestCase
{
    /**
     * @var SenderNameRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $senderNameRepository;

    /**
     * @var UserRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $userRepository;

    /**
     * @var SenderNameValidator
     */
    private $validator;

    protected function setUp(): void
    {
        $this->senderNameRepository = $this->createMock(SenderNameRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->validator = new SenderNameValidator(
            $this->senderNameRepository,
            $this->userRepository
        );
    }

    public function testValidateCreateWithValidData()
    {
        // Arrange
        $userId = 1;
        $name = "Qualitas CI";
        $status = "pending";

        $user = new User(1, "testuser", "password", "test@example.com", 10);

        $this->userRepository->method('findById')
            ->with($userId)
            ->willReturn($user);

        $this->senderNameRepository->method('findByUserIdAndName')
            ->with($userId, $name)
            ->willReturn(null);

        // Act
        $result = $this->validator->validateCreate([
            'userId' => $userId,
            'name' => $name,
            'status' => $status
        ]);

        // Assert
        $this->assertEquals([
            'userId' => $userId,
            'name' => $name,
            'status' => $status
        ], $result);
    }

    public function testValidateCreateWithMissingUserId()
    {
        // Arrange
        $name = "Qualitas CI";
        $status = "pending";

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validateCreate([
            'name' => $name,
            'status' => $status
        ]);
    }

    public function testValidateCreateWithMissingName()
    {
        // Arrange
        $userId = 1;
        $status = "pending";

        $user = new User(1, "testuser", "password", "test@example.com", 10);

        $this->userRepository->method('findById')
            ->with($userId)
            ->willReturn($user);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validateCreate([
            'userId' => $userId,
            'status' => $status
        ]);
    }

    public function testValidateCreateWithInvalidStatus()
    {
        // Arrange
        $userId = 1;
        $name = "Qualitas CI";
        $status = "invalid_status";

        $user = new User(1, "testuser", "password", "test@example.com", 10);

        $this->userRepository->method('findById')
            ->with($userId)
            ->willReturn($user);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validateCreate([
            'userId' => $userId,
            'name' => $name,
            'status' => $status
        ]);
    }

    public function testValidateCreateWithNonExistentUser()
    {
        // Arrange
        $userId = 999;
        $name = "Qualitas CI";
        $status = "pending";

        $this->userRepository->method('findById')
            ->with($userId)
            ->willReturn(null);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validateCreate([
            'userId' => $userId,
            'name' => $name,
            'status' => $status
        ]);
    }

    public function testValidateCreateWithDuplicateName()
    {
        // Arrange
        $userId = 1;
        $name = "Qualitas CI";
        $status = "pending";

        $user = new User(1, "testuser", "password", "test@example.com", 10);
        $existingSenderName = new SenderName($userId, $name, $status);

        $this->userRepository->method('findById')
            ->with($userId)
            ->willReturn($user);

        $this->senderNameRepository->method('findByUserIdAndName')
            ->with($userId, $name)
            ->willReturn($existingSenderName);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validateCreate([
            'userId' => $userId,
            'name' => $name,
            'status' => $status
        ]);
    }

    public function testValidateUpdateWithValidData()
    {
        // Arrange
        $id = 1;
        $name = "New Name";
        $status = "approved";

        $senderName = new SenderName(1, "Old Name", "pending");
        $senderName->setId($id);

        $this->senderNameRepository->method('findById')
            ->with($id)
            ->willReturn($senderName);

        $this->senderNameRepository->method('findByUserIdAndName')
            ->with(1, $name)
            ->willReturn(null);

        // Act
        $result = $this->validator->validateUpdate($id, [
            'name' => $name,
            'status' => $status
        ]);

        // Assert
        $this->assertEquals([
            'id' => $id,
            'name' => $name,
            'status' => $status
        ], $result);
    }

    public function testValidateUpdateWithNonExistentSenderName()
    {
        // Arrange
        $id = 999;
        $name = "New Name";
        $status = "approved";

        $this->senderNameRepository->method('findById')
            ->with($id)
            ->willReturn(null);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validateUpdate($id, [
            'name' => $name,
            'status' => $status
        ]);
    }

    public function testValidateUpdateWithInvalidStatus()
    {
        // Arrange
        $id = 1;
        $name = "New Name";
        $status = "invalid_status";

        $senderName = new SenderName(1, "Old Name", "pending");
        $senderName->setId($id);

        $this->senderNameRepository->method('findById')
            ->with($id)
            ->willReturn($senderName);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validateUpdate($id, [
            'name' => $name,
            'status' => $status
        ]);
    }

    public function testValidateUpdateWithDuplicateName()
    {
        // Arrange
        $id = 1;
        $userId = 1;
        $name = "Duplicate Name";
        $status = "approved";

        $senderName = new SenderName($userId, "Old Name", "pending");
        $senderName->setId($id);

        $existingSenderName = new SenderName($userId, $name, "pending");
        $existingSenderName->setId(2);

        $this->senderNameRepository->method('findById')
            ->with($id)
            ->willReturn($senderName);

        $this->senderNameRepository->method('findByUserIdAndName')
            ->with($userId, $name)
            ->willReturn($existingSenderName);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validateUpdate($id, [
            'name' => $name,
            'status' => $status
        ]);
    }

    public function testValidateDeleteWithValidId()
    {
        // Arrange
        $id = 1;

        $senderName = new SenderName(1, "Test Name", "pending");
        $senderName->setId($id);

        $this->senderNameRepository->method('findById')
            ->with($id)
            ->willReturn($senderName);

        // Act
        $result = $this->validator->validateDelete($id);

        // Assert
        $this->assertEquals(['id' => $id], $result);
    }

    public function testValidateDeleteWithNonExistentSenderName()
    {
        // Arrange
        $id = 999;

        $this->senderNameRepository->method('findById')
            ->with($id)
            ->willReturn(null);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validateDelete($id);
    }

    public function testValidateRequestWithValidData()
    {
        // Arrange
        $userId = 1;
        $name = "Qualitas CI";

        $user = new User(1, "testuser", "password", "test@example.com", 10);

        $this->userRepository->method('findById')
            ->with($userId)
            ->willReturn($user);

        $this->senderNameRepository->method('findByUserIdAndName')
            ->with($userId, $name)
            ->willReturn(null);

        // Act
        $result = $this->validator->validateRequest($userId, $name);

        // Assert
        $this->assertEquals([
            'userId' => $userId,
            'name' => $name
        ], $result);
    }

    public function testValidateApprovalWithValidId()
    {
        // Arrange
        $id = 1;

        $senderName = new SenderName(1, "Test Name", "pending");
        $senderName->setId($id);

        $this->senderNameRepository->method('findById')
            ->with($id)
            ->willReturn($senderName);

        // Act
        $result = $this->validator->validateApproval($id);

        // Assert
        $this->assertEquals(['id' => $id], $result);
    }

    public function testValidateRejectionWithValidId()
    {
        // Arrange
        $id = 1;

        $senderName = new SenderName(1, "Test Name", "pending");
        $senderName->setId($id);

        $this->senderNameRepository->method('findById')
            ->with($id)
            ->willReturn($senderName);

        // Act
        $result = $this->validator->validateRejection($id);

        // Assert
        $this->assertEquals(['id' => $id], $result);
    }
}
