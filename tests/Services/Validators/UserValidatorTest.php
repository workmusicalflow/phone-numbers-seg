<?php

namespace Tests\Services\Validators;

use App\Exceptions\ValidationException;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Validators\UserValidator;
use PHPUnit\Framework\TestCase;

class UserValidatorTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject&\App\Repositories\UserRepository $userRepositoryMock;
    private $userValidator;

    protected function setUp(): void
    {
        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        $this->userValidator = new UserValidator($this->userRepositoryMock);
    }

    public function testValidateCreateWithValidData()
    {
        // Arrange
        $username = 'testuser';
        $password = 'Password123';
        $email = 'test@example.com';
        $smsCredit = 10;
        $smsLimit = 100;

        // Le repository doit retourner null pour indiquer que l'utilisateur n'existe pas
        $this->userRepositoryMock->method('findByUsername')
            ->with($username)
            ->willReturn(null);

        // Act
        $result = $this->userValidator->validateCreate([
            'username' => $username,
            'password' => $password,
            'email' => $email,
            'smsCredit' => $smsCredit,
            'smsLimit' => $smsLimit
        ]);

        // Assert
        $this->assertEquals($username, $result['username']);
        $this->assertEquals($password, $result['password']);
        $this->assertEquals($email, $result['email']);
        $this->assertEquals($smsCredit, $result['smsCredit']);
        $this->assertEquals($smsLimit, $result['smsLimit']);
    }

    public function testValidateCreateWithExistingUsername()
    {
        // Arrange
        $username = 'existinguser';
        $password = 'Password123';
        $email = 'test@example.com';
        $smsCredit = 10;
        $smsLimit = 100;

        // Créer un mock d'utilisateur existant
        $existingUser = $this->createMock(User::class);

        // Le repository doit retourner un utilisateur pour indiquer que l'utilisateur existe déjà
        $this->userRepositoryMock->method('findByUsername')
            ->with($username)
            ->willReturn($existingUser);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->userValidator->validateCreate([
            'username' => $username,
            'password' => $password,
            'email' => $email,
            'smsCredit' => $smsCredit,
            'smsLimit' => $smsLimit
        ]);
    }

    public function testValidateCreateWithInvalidUsername()
    {
        // Arrange
        $username = 'u$'; // Trop court et caractères spéciaux
        $password = 'Password123';
        $email = 'test@example.com';
        $smsCredit = 10;
        $smsLimit = 100;

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->userValidator->validateCreate([
            'username' => $username,
            'password' => $password,
            'email' => $email,
            'smsCredit' => $smsCredit,
            'smsLimit' => $smsLimit
        ]);
    }

    public function testValidateCreateWithInvalidPassword()
    {
        // Arrange
        $username = 'testuser';
        $password = 'pass'; // Trop court et ne contient pas de majuscule ni de chiffre
        $email = 'test@example.com';
        $smsCredit = 10;
        $smsLimit = 100;

        // Le repository doit retourner null pour indiquer que l'utilisateur n'existe pas
        $this->userRepositoryMock->method('findByUsername')
            ->with($username)
            ->willReturn(null);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->userValidator->validateCreate([
            'username' => $username,
            'password' => $password,
            'email' => $email,
            'smsCredit' => $smsCredit,
            'smsLimit' => $smsLimit
        ]);
    }

    public function testValidateCreateWithInvalidEmail()
    {
        // Arrange
        $username = 'testuser';
        $password = 'Password123';
        $email = 'invalid-email'; // Format d'email invalide
        $smsCredit = 10;
        $smsLimit = 100;

        // Le repository doit retourner null pour indiquer que l'utilisateur n'existe pas
        $this->userRepositoryMock->method('findByUsername')
            ->with($username)
            ->willReturn(null);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->userValidator->validateCreate([
            'username' => $username,
            'password' => $password,
            'email' => $email,
            'smsCredit' => $smsCredit,
            'smsLimit' => $smsLimit
        ]);
    }

    public function testValidateCreateWithNegativeSmsCredit()
    {
        // Arrange
        $username = 'testuser';
        $password = 'Password123';
        $email = 'test@example.com';
        $smsCredit = -10; // Crédit négatif
        $smsLimit = 100;

        // Le repository doit retourner null pour indiquer que l'utilisateur n'existe pas
        $this->userRepositoryMock->method('findByUsername')
            ->with($username)
            ->willReturn(null);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->userValidator->validateCreate([
            'username' => $username,
            'password' => $password,
            'email' => $email,
            'smsCredit' => $smsCredit,
            'smsLimit' => $smsLimit
        ]);
    }

    public function testValidateCreateWithNegativeSmsLimit()
    {
        // Arrange
        $username = 'testuser';
        $password = 'Password123';
        $email = 'test@example.com';
        $smsCredit = 10;
        $smsLimit = -100; // Limite négative

        // Le repository doit retourner null pour indiquer que l'utilisateur n'existe pas
        $this->userRepositoryMock->method('findByUsername')
            ->with($username)
            ->willReturn(null);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->userValidator->validateCreate([
            'username' => $username,
            'password' => $password,
            'email' => $email,
            'smsCredit' => $smsCredit,
            'smsLimit' => $smsLimit
        ]);
    }

    public function testValidateUpdateWithValidData()
    {
        // Arrange
        $id = 1;
        $email = 'test@example.com';
        $smsLimit = 100;

        // Créer un mock d'utilisateur existant
        $existingUser = $this->createMock(User::class);

        // Le repository doit retourner un utilisateur pour indiquer que l'utilisateur existe
        $this->userRepositoryMock->method('findById')
            ->with($id)
            ->willReturn($existingUser);

        // Act
        $result = $this->userValidator->validateUpdate($id, [
            'email' => $email,
            'smsLimit' => $smsLimit
        ]);

        // Assert
        $this->assertEquals($id, $result['id']);
        $this->assertEquals($email, $result['email']);
        $this->assertEquals($smsLimit, $result['smsLimit']);
    }

    public function testValidateUpdateWithNonExistentUser()
    {
        // Arrange
        $id = 999; // ID d'un utilisateur qui n'existe pas
        $email = 'test@example.com';
        $smsLimit = 100;

        // Le repository doit retourner null pour indiquer que l'utilisateur n'existe pas
        $this->userRepositoryMock->method('findById')
            ->with($id)
            ->willReturn(null);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->userValidator->validateUpdate($id, [
            'email' => $email,
            'smsLimit' => $smsLimit
        ]);
    }

    public function testValidateUpdateWithInvalidEmail()
    {
        // Arrange
        $id = 1;
        $email = 'invalid-email'; // Format d'email invalide
        $smsLimit = 100;

        // Créer un mock d'utilisateur existant
        $existingUser = $this->createMock(User::class);

        // Le repository doit retourner un utilisateur pour indiquer que l'utilisateur existe
        $this->userRepositoryMock->method('findById')
            ->with($id)
            ->willReturn($existingUser);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->userValidator->validateUpdate($id, [
            'email' => $email,
            'smsLimit' => $smsLimit
        ]);
    }

    public function testValidateUpdateWithNegativeSmsLimit()
    {
        // Arrange
        $id = 1;
        $email = 'test@example.com';
        $smsLimit = -100; // Limite négative

        // Créer un mock d'utilisateur existant
        $existingUser = $this->createMock(User::class);

        // Le repository doit retourner un utilisateur pour indiquer que l'utilisateur existe
        $this->userRepositoryMock->method('findById')
            ->with($id)
            ->willReturn($existingUser);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->userValidator->validateUpdate($id, [
            'email' => $email,
            'smsLimit' => $smsLimit
        ]);
    }

    public function testValidatePasswordChangeWithValidData()
    {
        // Arrange
        $id = 1;
        $newPassword = 'NewPassword123';

        // Créer un mock d'utilisateur existant
        $existingUser = $this->createMock(User::class);

        // Le repository doit retourner un utilisateur pour indiquer que l'utilisateur existe
        $this->userRepositoryMock->method('findById')
            ->with($id)
            ->willReturn($existingUser);

        // Act
        $result = $this->userValidator->validatePasswordChange($id, [
            'newPassword' => $newPassword
        ]);

        // Assert
        $this->assertEquals($id, $result['id']);
        $this->assertEquals($newPassword, $result['newPassword']);
    }

    public function testValidatePasswordChangeWithNonExistentUser()
    {
        // Arrange
        $id = 999; // ID d'un utilisateur qui n'existe pas
        $newPassword = 'NewPassword123';

        // Le repository doit retourner null pour indiquer que l'utilisateur n'existe pas
        $this->userRepositoryMock->method('findById')
            ->with($id)
            ->willReturn(null);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->userValidator->validatePasswordChange($id, [
            'newPassword' => $newPassword
        ]);
    }

    public function testValidatePasswordChangeWithInvalidPassword()
    {
        // Arrange
        $id = 1;
        $newPassword = 'pass'; // Trop court et ne contient pas de majuscule ni de chiffre

        // Créer un mock d'utilisateur existant
        $existingUser = $this->createMock(User::class);

        // Le repository doit retourner un utilisateur pour indiquer que l'utilisateur existe
        $this->userRepositoryMock->method('findById')
            ->with($id)
            ->willReturn($existingUser);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->userValidator->validatePasswordChange($id, [
            'newPassword' => $newPassword
        ]);
    }

    public function testValidateAddCreditsWithValidData()
    {
        // Arrange
        $id = 1;
        $amount = 100;

        // Créer un mock d'utilisateur existant
        $existingUser = $this->createMock(User::class);

        // Le repository doit retourner un utilisateur pour indiquer que l'utilisateur existe
        $this->userRepositoryMock->method('findById')
            ->with($id)
            ->willReturn($existingUser);

        // Act
        $result = $this->userValidator->validateAddCredits($id, [
            'amount' => $amount
        ]);

        // Assert
        $this->assertEquals($id, $result['id']);
        $this->assertEquals($amount, $result['amount']);
    }

    public function testValidateAddCreditsWithNonExistentUser()
    {
        // Arrange
        $id = 999; // ID d'un utilisateur qui n'existe pas
        $amount = 100;

        // Le repository doit retourner null pour indiquer que l'utilisateur n'existe pas
        $this->userRepositoryMock->method('findById')
            ->with($id)
            ->willReturn(null);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->userValidator->validateAddCredits($id, [
            'amount' => $amount
        ]);
    }

    public function testValidateAddCreditsWithInvalidAmount()
    {
        // Arrange
        $id = 1;
        $amount = 0; // Montant invalide (doit être > 0)

        // Créer un mock d'utilisateur existant
        $existingUser = $this->createMock(User::class);

        // Le repository doit retourner un utilisateur pour indiquer que l'utilisateur existe
        $this->userRepositoryMock->method('findById')
            ->with($id)
            ->willReturn($existingUser);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->userValidator->validateAddCredits($id, [
            'amount' => $amount
        ]);
    }

    public function testValidateDeleteWithValidData()
    {
        // Arrange
        $id = 1;

        // Créer un mock d'utilisateur existant
        $existingUser = $this->createMock(User::class);

        // Le repository doit retourner un utilisateur pour indiquer que l'utilisateur existe
        $this->userRepositoryMock->method('findById')
            ->with($id)
            ->willReturn($existingUser);

        // Act
        $result = $this->userValidator->validateDelete($id);

        // Assert
        $this->assertEquals($id, $result['id']);
    }

    public function testValidateDeleteWithNonExistentUser()
    {
        // Arrange
        $id = 999; // ID d'un utilisateur qui n'existe pas

        // Le repository doit retourner null pour indiquer que l'utilisateur n'existe pas
        $this->userRepositoryMock->method('findById')
            ->with($id)
            ->willReturn(null);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->userValidator->validateDelete($id);
    }
}
