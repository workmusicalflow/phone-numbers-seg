<?php

namespace Tests\Services\Validators;

use PHPUnit\Framework\TestCase;
use App\Services\Validators\SMSOrderValidator;
use App\Repositories\SMSOrderRepository;
use App\Repositories\UserRepository;
use App\Models\SMSOrder;
use App\Models\User;
use App\Exceptions\ValidationException;

class SMSOrderValidatorTest extends TestCase
{
    /**
     * @var SMSOrderRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $smsOrderRepository;

    /**
     * @var UserRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $userRepository;

    /**
     * @var SMSOrderValidator
     */
    private $validator;

    protected function setUp(): void
    {
        $this->smsOrderRepository = $this->createMock(SMSOrderRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->validator = new SMSOrderValidator(
            $this->smsOrderRepository,
            $this->userRepository
        );
    }

    public function testValidateCreateWithValidData()
    {
        // Arrange
        $userId = 1;
        $quantity = 100;
        $status = "pending";

        $user = new User(1, "testuser", "password", "test@example.com", 10);

        $this->userRepository->method('findById')
            ->with($userId)
            ->willReturn($user);

        // Act
        $result = $this->validator->validateCreate([
            'userId' => $userId,
            'quantity' => $quantity,
            'status' => $status
        ]);

        // Assert
        $this->assertEquals([
            'userId' => $userId,
            'quantity' => $quantity,
            'status' => $status
        ], $result);
    }

    public function testValidateCreateWithMissingUserId()
    {
        // Arrange
        $quantity = 100;
        $status = "pending";

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validateCreate([
            'quantity' => $quantity,
            'status' => $status
        ]);
    }

    public function testValidateCreateWithMissingQuantity()
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

    public function testValidateCreateWithInvalidQuantity()
    {
        // Arrange
        $userId = 1;
        $quantity = -10;
        $status = "pending";

        $user = new User(1, "testuser", "password", "test@example.com", 10);

        $this->userRepository->method('findById')
            ->with($userId)
            ->willReturn($user);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validateCreate([
            'userId' => $userId,
            'quantity' => $quantity,
            'status' => $status
        ]);
    }

    public function testValidateCreateWithInvalidStatus()
    {
        // Arrange
        $userId = 1;
        $quantity = 100;
        $status = "invalid_status";

        $user = new User(1, "testuser", "password", "test@example.com", 10);

        $this->userRepository->method('findById')
            ->with($userId)
            ->willReturn($user);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validateCreate([
            'userId' => $userId,
            'quantity' => $quantity,
            'status' => $status
        ]);
    }

    public function testValidateCreateWithNonExistentUser()
    {
        // Arrange
        $userId = 999;
        $quantity = 100;
        $status = "pending";

        $this->userRepository->method('findById')
            ->with($userId)
            ->willReturn(null);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validateCreate([
            'userId' => $userId,
            'quantity' => $quantity,
            'status' => $status
        ]);
    }

    public function testValidateUpdateWithValidData()
    {
        // Arrange
        $id = 1;
        $quantity = 200;
        $status = "completed";

        $smsOrder = new SMSOrder(1, 100, "pending");
        $smsOrder->setId($id);

        $this->smsOrderRepository->method('findById')
            ->with($id)
            ->willReturn($smsOrder);

        // Act
        $result = $this->validator->validateUpdate($id, [
            'quantity' => $quantity,
            'status' => $status
        ]);

        // Assert
        $this->assertEquals([
            'id' => $id,
            'quantity' => $quantity,
            'status' => $status
        ], $result);
    }

    public function testValidateUpdateWithNonExistentSMSOrder()
    {
        // Arrange
        $id = 999;
        $quantity = 200;
        $status = "completed";

        $this->smsOrderRepository->method('findById')
            ->with($id)
            ->willReturn(null);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validateUpdate($id, [
            'quantity' => $quantity,
            'status' => $status
        ]);
    }

    public function testValidateUpdateWithInvalidQuantity()
    {
        // Arrange
        $id = 1;
        $quantity = -10;
        $status = "completed";

        $smsOrder = new SMSOrder(1, 100, "pending");
        $smsOrder->setId($id);

        $this->smsOrderRepository->method('findById')
            ->with($id)
            ->willReturn($smsOrder);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validateUpdate($id, [
            'quantity' => $quantity,
            'status' => $status
        ]);
    }

    public function testValidateUpdateWithInvalidStatus()
    {
        // Arrange
        $id = 1;
        $quantity = 200;
        $status = "invalid_status";

        $smsOrder = new SMSOrder(1, 100, "pending");
        $smsOrder->setId($id);

        $this->smsOrderRepository->method('findById')
            ->with($id)
            ->willReturn($smsOrder);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validateUpdate($id, [
            'quantity' => $quantity,
            'status' => $status
        ]);
    }

    public function testValidateDeleteWithValidId()
    {
        // Arrange
        $id = 1;

        $smsOrder = new SMSOrder(1, 100, "pending");
        $smsOrder->setId($id);

        $this->smsOrderRepository->method('findById')
            ->with($id)
            ->willReturn($smsOrder);

        // Act
        $result = $this->validator->validateDelete($id);

        // Assert
        $this->assertEquals(['id' => $id], $result);
    }

    public function testValidateDeleteWithNonExistentSMSOrder()
    {
        // Arrange
        $id = 999;

        $this->smsOrderRepository->method('findById')
            ->with($id)
            ->willReturn(null);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validateDelete($id);
    }

    public function testValidateOrderCreationWithValidData()
    {
        // Arrange
        $userId = 1;
        $quantity = 100;

        $user = new User(1, "testuser", "password", "test@example.com", 10);

        $this->userRepository->method('findById')
            ->with($userId)
            ->willReturn($user);

        // Act
        $result = $this->validator->validateOrderCreation($userId, $quantity);

        // Assert
        $this->assertEquals([
            'userId' => $userId,
            'quantity' => $quantity
        ], $result);
    }

    public function testValidateOrderCreationWithInvalidQuantity()
    {
        // Arrange
        $userId = 1;
        $quantity = -10;

        $user = new User(1, "testuser", "password", "test@example.com", 10);

        $this->userRepository->method('findById')
            ->with($userId)
            ->willReturn($user);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validateOrderCreation($userId, $quantity);
    }

    public function testValidateOrderCreationWithNonExistentUser()
    {
        // Arrange
        $userId = 999;
        $quantity = 100;

        $this->userRepository->method('findById')
            ->with($userId)
            ->willReturn(null);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validateOrderCreation($userId, $quantity);
    }

    public function testValidateCompletionWithValidId()
    {
        // Arrange
        $id = 1;

        $smsOrder = new SMSOrder(1, 100, "pending");
        $smsOrder->setId($id);

        $this->smsOrderRepository->method('findById')
            ->with($id)
            ->willReturn($smsOrder);

        // Act
        $result = $this->validator->validateCompletion($id);

        // Assert
        $this->assertEquals(['id' => $id], $result);
    }

    public function testValidateCompletionWithNonExistentSMSOrder()
    {
        // Arrange
        $id = 999;

        $this->smsOrderRepository->method('findById')
            ->with($id)
            ->willReturn(null);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validateCompletion($id);
    }

    public function testValidateCompletionWithAlreadyCompletedOrder()
    {
        // Arrange
        $id = 1;

        $smsOrder = new SMSOrder(1, 100, "completed");
        $smsOrder->setId($id);

        $this->smsOrderRepository->method('findById')
            ->with($id)
            ->willReturn($smsOrder);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validateCompletion($id);
    }
}
