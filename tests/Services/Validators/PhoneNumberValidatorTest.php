<?php

namespace Tests\Services\Validators;

use App\Exceptions\ValidationException;
use App\Models\PhoneNumber;
use App\Repositories\PhoneNumberRepository;
use App\Services\Validators\PhoneNumberValidator;
use PHPUnit\Framework\TestCase;

class PhoneNumberValidatorTest extends TestCase
{
    private $phoneNumberRepository;
    private $phoneNumberValidator;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un mock du repository
        $this->phoneNumberRepository = $this->createMock(PhoneNumberRepository::class);

        // Créer l'instance du validateur avec le mock du repository
        $this->phoneNumberValidator = new PhoneNumberValidator($this->phoneNumberRepository);
    }

    public function testValidateCreateWithValidData()
    {
        // Configurer le mock pour simuler un numéro qui n'existe pas encore
        $this->phoneNumberRepository->method('findByNumber')
            ->willReturn(null);

        // Données valides
        $number = '+2250707070707';
        $civility = 'M.';
        $firstName = 'Jean';
        $name = 'Dupont';
        $company = 'ACME Inc.';
        $sector = 'Technologie';
        $notes = 'Notes de test';

        // Exécuter la méthode à tester
        $result = $this->phoneNumberValidator->validateCreate(
            $number,
            $civility,
            $firstName,
            $name,
            $company,
            $sector,
            $notes
        );

        // Vérifier que le résultat est correct
        $this->assertEquals([
            'number' => $number,
            'civility' => $civility,
            'firstName' => $firstName,
            'name' => $name,
            'company' => $company,
            'sector' => $sector,
            'notes' => $notes
        ], $result);
    }

    public function testValidateCreateWithInvalidNumber()
    {
        // Données avec un numéro invalide
        $number = 'not-a-number';

        // Vérifier qu'une exception est levée
        $this->expectException(ValidationException::class);

        // Exécuter la méthode à tester
        $this->phoneNumberValidator->validateCreate($number);
    }

    public function testValidateCreateWithExistingNumber()
    {
        // Créer un mock de PhoneNumber
        $existingPhoneNumber = $this->createMock(PhoneNumber::class);

        // Configurer le mock pour simuler un numéro qui existe déjà
        $this->phoneNumberRepository->method('findByNumber')
            ->willReturn($existingPhoneNumber);

        // Données avec un numéro qui existe déjà
        $number = '+2250707070707';

        // Vérifier qu'une exception est levée
        $this->expectException(ValidationException::class);

        // Exécuter la méthode à tester
        $this->phoneNumberValidator->validateCreate($number);
    }

    public function testValidateCreateWithInvalidCivility()
    {
        // Configurer le mock pour simuler un numéro qui n'existe pas encore
        $this->phoneNumberRepository->method('findByNumber')
            ->willReturn(null);

        // Données avec une civilité invalide
        $number = '+2250707070707';
        $civility = 'Invalid';

        // Vérifier qu'une exception est levée
        $this->expectException(ValidationException::class);

        // Exécuter la méthode à tester
        $this->phoneNumberValidator->validateCreate($number, $civility);
    }

    public function testValidateUpdateWithValidData()
    {
        // Créer un mock de PhoneNumber
        $existingPhoneNumber = $this->createMock(PhoneNumber::class);

        // Configurer le mock pour simuler un numéro qui existe
        $this->phoneNumberRepository->method('findById')
            ->willReturn($existingPhoneNumber);

        // Données valides
        $id = 1;
        $civility = 'M.';
        $firstName = 'Jean';
        $name = 'Dupont';
        $company = 'ACME Inc.';
        $sector = 'Technologie';
        $notes = 'Notes de test';

        // Exécuter la méthode à tester
        $result = $this->phoneNumberValidator->validateUpdate(
            $id,
            $civility,
            $firstName,
            $name,
            $company,
            $sector,
            $notes
        );

        // Vérifier que le résultat est correct
        $this->assertEquals([
            'id' => $id,
            'civility' => $civility,
            'firstName' => $firstName,
            'name' => $name,
            'company' => $company,
            'sector' => $sector,
            'notes' => $notes
        ], $result);
    }

    public function testValidateUpdateWithNonExistingId()
    {
        // Configurer le mock pour simuler un numéro qui n'existe pas
        $this->phoneNumberRepository->method('findById')
            ->willReturn(null);

        // Données avec un ID qui n'existe pas
        $id = 999;

        // Vérifier qu'une exception est levée
        $this->expectException(ValidationException::class);

        // Exécuter la méthode à tester
        $this->phoneNumberValidator->validateUpdate($id);
    }

    public function testValidateDeleteWithValidId()
    {
        // Créer un mock de PhoneNumber
        $existingPhoneNumber = $this->createMock(PhoneNumber::class);

        // Configurer le mock pour simuler un numéro qui existe
        $this->phoneNumberRepository->method('findById')
            ->willReturn($existingPhoneNumber);

        // Données valides
        $id = 1;

        // Exécuter la méthode à tester
        $result = $this->phoneNumberValidator->validateDelete($id);

        // Vérifier que le résultat est correct
        $this->assertEquals(['id' => $id], $result);
    }

    public function testValidateDeleteWithNonExistingId()
    {
        // Configurer le mock pour simuler un numéro qui n'existe pas
        $this->phoneNumberRepository->method('findById')
            ->willReturn(null);

        // Données avec un ID qui n'existe pas
        $id = 999;

        // Vérifier qu'une exception est levée
        $this->expectException(ValidationException::class);

        // Exécuter la méthode à tester
        $this->phoneNumberValidator->validateDelete($id);
    }
}
