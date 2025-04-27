<?php

namespace Tests\Services;

use App\Services\CSVImportService;
use App\Repositories\Interfaces\ContactRepositoryInterface;
use App\Repositories\Interfaces\PhoneNumberRepositoryInterface; // Correct dependency
use App\Services\Interfaces\PhoneSegmentationServiceInterface; // Correct dependency
use App\Entities\User; // Still needed for userId context
use App\Entities\Contact;
use App\Entities\PhoneNumber; // Added missing entity import
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Exception;
use SplFileObject; // For mocking file reading

/**
 * Test class for CSVImportService
 *
 * @covers \App\Services\CSVImportService
 */
class CSVImportServiceTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $contactRepository;
    private ObjectProphecy $phoneNumberRepository; // Corrected property
    private ObjectProphecy $segmentationService; // Corrected property
    private ObjectProphecy $logger;
    private CSVImportService $service;
    // No userProphecy needed here as UserRepository is not used by the service

    protected function setUp(): void
    {
        $this->contactRepository = $this->prophesize(ContactRepositoryInterface::class);
        $this->phoneNumberRepository = $this->prophesize(PhoneNumberRepositoryInterface::class); // Mock correct repo
        $this->segmentationService = $this->prophesize(PhoneSegmentationServiceInterface::class); // Mock correct service
        $this->logger = $this->prophesize(LoggerInterface::class);

        // No need to mock User entity directly here unless needed for return values

        $this->service = new CSVImportService(
            $this->phoneNumberRepository->reveal(), // Pass correct repo
            $this->segmentationService->reveal(),   // Pass correct service
            $this->contactRepository->reveal(),
            $this->logger->reveal()
        );
    }

    /**
     * Test importing contacts from a valid CSV file successfully.
     * @test
     */
    public function importContactsFromCSVSuccessfully(): void
    {
        $userId = 1;
        $csvData = [
            ['phoneNumber', 'name', 'email'], // Header row
            ['+2250101010101', 'John Doe', 'john@example.com'],
            ['+2250202020202', 'Jane Smith', 'jane@example.com'],
            ['+2250303030303', 'Invalid Number', ''], // Test invalid number handling
            ['+2250101010101', 'John Doe Duplicate', ''], // Test duplicate handling
        ];
        $filePath = $this->createTempCsvFile($csvData);
        // Add userId to options
        $options = [
            'delimiter' => ',',
            'enclosure' => '"',
            'escape' => '\\',
            'hasHeader' => true,
            'userId' => $userId, // Pass userId via options
            'createContacts' => true // Ensure contact creation is enabled
        ];

        // No need to mock UserRepository

        // Mock phone number normalization (part of the service, not external validator)
        // We test the outcome, not the internal normalization call directly

        // Mock contact repository checks and saves
        // Check for existing contact (first valid number) - assume not found
        $this->contactRepository->findByCriteria(['phoneNumber' => '+2250101010101', 'userId' => $userId], 1)->shouldBeCalledOnce()->willReturn([]);
        // Save first contact
        $this->contactRepository->save(Argument::that(function (Contact $contact) use ($userId, $csvData) {
            return $contact->getUserId() === $userId && // Check userId is set correctly
                $contact->getPhoneNumber() === $csvData[1][0] &&
                $contact->getName() === $csvData[1][1] &&
                $contact->getEmail() === $csvData[1][2];
        }))->shouldBeCalledOnce();

        // Check for existing contact (second valid number) - assume not found
        $this->contactRepository->findByPhoneNumberAndUserId('+2250202020202', $userId)->shouldBeCalledOnce()->willReturn(null);
        // Save second contact
        $this->contactRepository->save(Argument::that(function (Contact $contact) use ($userId, $csvData) {
            return $contact->getUserId() === $userId &&
                $contact->getPhoneNumber() === $csvData[2][0] &&
                $contact->getName() === $csvData[2][1] &&
                $contact->getEmail() === $csvData[2][2];
        }))->shouldBeCalledOnce();

        // Check for existing contact (duplicate valid number) - assume found (the one just saved)
        $existingContact = $this->prophesize(Contact::class);
        $this->contactRepository->findByPhoneNumberAndUserId('+2250101010101', $userId)->shouldBeCalledOnce()->willReturn($existingContact->reveal());
        // Save should NOT be called for the duplicate
        // Mock findByNumber for the duplicate check within processBatch
        $existingPhoneNumber = $this->prophesize(PhoneNumber::class);
        $this->phoneNumberRepository->findByNumber('+2250101010101')->shouldBeCalled()->willReturn($existingPhoneNumber->reveal());
        $this->phoneNumberRepository->findByNumber('+2250202020202')->shouldBeCalled()->willReturn(null); // Assume 2nd doesn't exist in phone_numbers
        $this->phoneNumberRepository->findByNumber('+2250303030303')->shouldBeCalled()->willReturn(null); // Assume 3rd doesn't exist

        // Mock save for the new PhoneNumber entities (2nd and 3rd number)
        $this->phoneNumberRepository->save(Argument::type(PhoneNumber::class))->shouldBeCalledTimes(2);


        // Act
        $result = $this->service->importFromFile($filePath, $options); // Corrected arguments

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals(4, $result['stats']['total']); // Check stats array
        $this->assertEquals(2, $result['stats']['contactsCreated']); // 2 contacts created
        $this->assertEquals(1, $result['stats']['contactsDuplicates']); // 1 duplicate contact check
        $this->assertEquals(1, $result['stats']['invalid']); // 1 invalid number format/empty
        $this->assertEquals(1, $result['stats']['duplicates']); // 1 duplicate number in DB/batch
        $this->assertCount(2, $result['detailedErrors']); // 1 invalid + 1 duplicate
        $this->assertStringContainsString('Format de numéro invalide', $result['detailedErrors'][0]['message']); // Check error message
        $this->assertStringContainsString('+2250303030303', $result['detailedErrors'][0]['value']);
        $this->assertStringContainsString('Numéro déjà existant', $result['detailedErrors'][1]['message']); // Check duplicate error
        $this->assertStringContainsString('+2250101010101', $result['detailedErrors'][1]['value']);


        // Verify contact save was called exactly twice
        $this->contactRepository->save(Argument::type(Contact::class))->shouldHaveBeenCalledTimes(2);

        // Clean up temp file
        unlink($filePath);
    }

    /**
     * Test importing from a non-existent file.
     * @test
     */
    public function importFromFileNotFound(): void
    {
        $userId = 1;
        $filePath = '/non/existent/file.csv';
        $options = ['userId' => $userId];

        // Act
        $result = $this->service->importFromFile($filePath, $options);

        // Assert
        $this->assertEquals('error', $result['status']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('Fichier non trouvé', $result['errors'][0]);
        $this->assertEquals(0, $result['stats']['total']);
    }

    /**
     * Test importing a file with an invalid extension.
     * @test
     */
    public function importFromFileInvalidExtension(): void
    {
        $userId = 1;
        // Create a dummy file with a wrong extension
        $filePath = tempnam(sys_get_temp_dir(), 'csvtest');
        rename($filePath, $filePath . '.txt'); // Rename to .txt
        $filePath .= '.txt';
        file_put_contents($filePath, "test"); // Add some content

        $options = ['userId' => $userId];

        // Act
        $result = $this->service->importFromFile($filePath, $options);

        // Assert
        $this->assertEquals('error', $result['status']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('Format de fichier invalide', $result['errors'][0]);
        $this->assertStringContainsString('txt', $result['errors'][0]); // Check if it mentions the extension
        $this->assertEquals(0, $result['stats']['total']);

        // Clean up
        unlink($filePath);
    }

    /**
     * Test importing with an invalid phone column index.
     * @test
     */
    public function importFromFileInvalidPhoneColumnIndex(): void
    {
        $userId = 1;
        $csvData = [
            ['Name', 'Number'], // Only 2 columns
            ['John Doe', '+2250101010101']
        ];
        $filePath = $this->createTempCsvFile($csvData);
        $options = [
            'userId' => $userId,
            'hasHeader' => true,
            'phoneColumn' => 2 // Invalid index (should be 0 or 1)
        ];

        // Act
        $result = $this->service->importFromFile($filePath, $options);

        // Assert
        $this->assertEquals('success', $result['status']); // Service continues but skips row
        $this->assertEquals(1, $result['stats']['total']);
        $this->assertEquals(1, $result['stats']['invalid']);
        $this->assertEquals(0, $result['stats']['contactsCreated']); // No contacts created
        $this->assertCount(1, $result['detailedErrors']);
        $this->assertStringContainsString('Index de colonne téléphone invalide', $result['detailedErrors'][0]['message']);

        // Clean up
        unlink($filePath);
    }

    /**
     * Test importing with different delimiter and no header.
     * @test
     */
    public function importFromFileDifferentDelimiterNoHeader(): void
    {
        $userId = 1;
        $csvData = [
            '+2250505050505;Alice', // Semicolon delimiter, no header
            '+2250606060606;Bob'
        ];
        // Create file manually with semicolon delimiter
        $filePath = tempnam(sys_get_temp_dir(), 'csvtest');
        file_put_contents($filePath, implode("\n", $csvData));

        $options = [
            'delimiter' => ';',
            'hasHeader' => false,
            'phoneColumn' => 0,
            'nameColumn' => 1, // Map name column
            'userId' => $userId,
            'createContacts' => true
        ];

        // Mock repository calls
        $this->contactRepository->findByPhoneNumberAndUserId('+2250505050505', $userId)->shouldBeCalled()->willReturn(null);
        $this->contactRepository->findByPhoneNumberAndUserId('+2250606060606', $userId)->shouldBeCalled()->willReturn(null);
        $this->phoneNumberRepository->findByNumber(Argument::any())->shouldBeCalled()->willReturn(null); // Assume numbers don't exist in phone_numbers
        $this->phoneNumberRepository->save(Argument::type(PhoneNumber::class))->shouldBeCalledTimes(2);
        $this->contactRepository->save(Argument::that(function (Contact $c) {
            return $c->getName() === 'Alice';
        }))->shouldBeCalledOnce();
        $this->contactRepository->save(Argument::that(function (Contact $c) {
            return $c->getName() === 'Bob';
        }))->shouldBeCalledOnce();

        // Act
        $result = $this->service->importFromFile($filePath, $options);

        // Assert
        $this->assertEquals('success', $result['status']);
        $this->assertEquals(2, $result['stats']['total']);
        $this->assertEquals(2, $result['stats']['contactsCreated']);
        $this->assertEquals(0, $result['stats']['invalid']);
        $this->assertEquals(0, $result['stats']['duplicates']);
        $this->assertCount(0, $result['errors']);
        $this->assertCount(0, $result['detailedErrors']);

        // Clean up
        unlink($filePath);
    }

    /**
     * Test importing when createContacts option is false.
     * @test
     */
    public function importFromFileCreateContactsFalse(): void
    {
        $userId = 1;
        $csvData = [
            ['phoneNumber', 'name'],
            ['+2250707070707', 'Test NoContact']
        ];
        $filePath = $this->createTempCsvFile($csvData);
        $options = [
            'userId' => $userId,
            'hasHeader' => true,
            'createContacts' => false // Disable contact creation
        ];

        // Mock repository calls - only phone number repo should be involved
        $this->phoneNumberRepository->findByNumber('+2250707070707')->shouldBeCalledOnce()->willReturn(null);
        $this->phoneNumberRepository->save(Argument::type(PhoneNumber::class))->shouldBeCalledOnce();
        $this->contactRepository->findByPhoneNumberAndUserId(Argument::any(), Argument::any())->shouldNotBeCalled();
        $this->contactRepository->save(Argument::any())->shouldNotBeCalled();

        // Act
        $result = $this->service->importFromFile($filePath, $options);

        // Assert
        $this->assertEquals('success', $result['status']);
        $this->assertEquals(1, $result['stats']['total']);
        $this->assertEquals(0, $result['stats']['contactsCreated']); // Verify no contacts created
        $this->assertEquals(0, $result['stats']['invalid']);
        $this->assertEquals(0, $result['stats']['duplicates']);
        $this->assertCount(0, $result['errors']);

        // Clean up
        unlink($filePath);
    }

    /**
     * Test importing when segmentImmediately option is false.
     * @test
     */
    public function importFromFileSegmentImmediatelyFalse(): void
    {
        $userId = 1;
        $csvData = [
            ['phoneNumber'],
            ['+2250808080808']
        ];
        $filePath = $this->createTempCsvFile($csvData);
        $options = [
            'userId' => $userId,
            'hasHeader' => true,
            'segmentImmediately' => false // Disable immediate segmentation
        ];

        // Mock repository calls
        $this->phoneNumberRepository->findByNumber('+2250808080808')->shouldBeCalledOnce()->willReturn(null);
        $this->phoneNumberRepository->save(Argument::type(PhoneNumber::class))->shouldBeCalledOnce();
        // Ensure segmentation service is NOT called
        $this->segmentationService->segmentPhoneNumber(Argument::any())->shouldNotBeCalled();
        // Contact creation should still happen if enabled (default)
        $this->contactRepository->findByPhoneNumberAndUserId('+2250808080808', $userId)->shouldBeCalledOnce()->willReturn(null);
        $this->contactRepository->save(Argument::type(Contact::class))->shouldBeCalledOnce();


        // Act
        $result = $this->service->importFromFile($filePath, $options);

        // Assert
        $this->assertEquals('success', $result['status']);
        $this->assertEquals(1, $result['stats']['total']);
        $this->assertEquals(1, $result['stats']['contactsCreated']);
        $this->assertEquals(0, $result['stats']['invalid']);

        // Clean up
        unlink($filePath);
    }

    /**
     * Test importing an empty CSV file.
     * @test
     */
    public function importFromEmptyFile(): void
    {
        $userId = 1;
        $csvData = []; // Empty data
        $filePath = $this->createTempCsvFile($csvData);
        $options = ['userId' => $userId];

        // Mock logger expectation for empty file info
        $this->logger->info('Importation CSV terminée.', Argument::type('array'))->shouldBeCalled();

        // Act
        $result = $this->service->importFromFile($filePath, $options);

        // Assert
        $this->assertEquals('success', $result['status']);
        $this->assertEquals(0, $result['stats']['total']);
        $this->assertEquals(0, $result['stats']['processed']);
        $this->assertEquals(0, $result['stats']['contactsCreated']);
        $this->assertEquals(0, $result['stats']['invalid']);
        $this->assertEquals(0, $result['stats']['duplicates']);
        $this->assertCount(0, $result['errors']);
        $this->assertCount(0, $result['detailedErrors']);

        // Ensure no repository interactions happened
        $this->phoneNumberRepository->findByNumber(Argument::any())->shouldNotHaveBeenCalled();
        $this->phoneNumberRepository->save(Argument::any())->shouldNotHaveBeenCalled();
        $this->contactRepository->findByPhoneNumberAndUserId(Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
        $this->contactRepository->save(Argument::any())->shouldNotHaveBeenCalled();
        $this->segmentationService->segmentPhoneNumber(Argument::any())->shouldNotHaveBeenCalled();


        // Clean up
        unlink($filePath);
    }

    /**
     * Test importing a CSV file containing only the header row.
     * @test
     */
    public function importFromFileWithOnlyHeader(): void
    {
        $userId = 1;
        $csvData = [
            ['phoneNumber', 'name', 'email'] // Only header
        ];
        $filePath = $this->createTempCsvFile($csvData);
        $options = ['userId' => $userId, 'hasHeader' => true];

        // Mock logger expectation for completion
        $this->logger->info('Importation CSV terminée.', Argument::type('array'))->shouldBeCalled();

        // Act
        $result = $this->service->importFromFile($filePath, $options);

        // Assert
        $this->assertEquals('success', $result['status']);
        $this->assertEquals(0, $result['stats']['total']); // Header is not counted in total rows processed for data
        $this->assertEquals(0, $result['stats']['processed']);
        $this->assertEquals(0, $result['stats']['contactsCreated']);
        $this->assertEquals(0, $result['stats']['invalid']);
        $this->assertEquals(0, $result['stats']['duplicates']);
        $this->assertCount(0, $result['errors']);
        $this->assertCount(0, $result['detailedErrors']);

        // Ensure no repository interactions happened
        $this->phoneNumberRepository->findByNumber(Argument::any())->shouldNotHaveBeenCalled();
        $this->phoneNumberRepository->save(Argument::any())->shouldNotHaveBeenCalled();
        $this->contactRepository->findByPhoneNumberAndUserId(Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
        $this->contactRepository->save(Argument::any())->shouldNotHaveBeenCalled();
        $this->segmentationService->segmentPhoneNumber(Argument::any())->shouldNotHaveBeenCalled();

        // Clean up
        unlink($filePath);
    }

    /**
     * Test importing with complex column mapping and varied phone formats.
     * @test
     */
    public function importWithComplexMappingAndVariedFormats(): void
    {
        $userId = 1;
        $csvData = [
            // Header row with more columns
            ['Civilité', 'Prénom', 'Nom', 'Numéro', 'Société', 'Secteur', 'Notes', 'Email'],
            // Data rows with varied formats and data
            ['M.', 'Jean', 'Dupont', '0101010101', 'Acme Corp', 'Tech', 'Note 1', 'jean@acme.com'], // Local format
            ['Mme', 'Alice', 'Martin', '+2250202020202', 'Globex', 'Finance', '', 'alice@globex.com'], // International format
            ['', '', '', 'invalid-number', '', '', '', ''], // Invalid number
            ['M.', 'Bob', 'L\'éponge', '002250303030303', 'Krusty Krab', 'Food', 'Note 3', 'bob@krusty.com'], // 00 format
        ];
        $filePath = $this->createTempCsvFile($csvData);
        $options = [
            'delimiter' => ',',
            'hasHeader' => true,
            'userId' => $userId,
            'createContacts' => true,
            'segmentImmediately' => true, // Enable segmentation for this test
            // Map columns by index
            'phoneColumn' => 3,
            'civilityColumn' => 0,
            'firstNameColumn' => 1,
            'nameColumn' => 2,
            'companyColumn' => 4,
            'sectorColumn' => 5,
            'notesColumn' => 6,
            'emailColumn' => 7,
        ];

        // Expected normalized numbers
        $normalizedNum1 = '+2250101010101';
        $normalizedNum2 = '+2250202020202';
        $normalizedNum3 = '+2250303030303';

        // Mock repository calls (assuming no existing contacts/numbers)
        $this->contactRepository->findByPhoneNumberAndUserId($normalizedNum1, $userId)->shouldBeCalledOnce()->willReturn(null);
        $this->contactRepository->findByPhoneNumberAndUserId($normalizedNum2, $userId)->shouldBeCalledOnce()->willReturn(null);
        $this->contactRepository->findByPhoneNumberAndUserId($normalizedNum3, $userId)->shouldBeCalledOnce()->willReturn(null);

        $this->phoneNumberRepository->findByNumber($normalizedNum1)->shouldBeCalledOnce()->willReturn(null);
        $this->phoneNumberRepository->findByNumber($normalizedNum2)->shouldBeCalledOnce()->willReturn(null);
        $this->phoneNumberRepository->findByNumber($normalizedNum3)->shouldBeCalledOnce()->willReturn(null);

        // Mock segmentation calls for valid numbers
        $segmentationResult1 = ['countryCode' => '225', 'operator' => 'Orange'];
        $segmentationResult2 = ['countryCode' => '225', 'operator' => 'MTN'];
        $segmentationResult3 = ['countryCode' => '225', 'operator' => 'Moov'];
        $this->segmentationService->segmentPhoneNumber($normalizedNum1)->shouldBeCalledOnce()->willReturn($segmentationResult1);
        $this->segmentationService->segmentPhoneNumber($normalizedNum2)->shouldBeCalledOnce()->willReturn($segmentationResult2);
        $this->segmentationService->segmentPhoneNumber($normalizedNum3)->shouldBeCalledOnce()->willReturn($segmentationResult3);

        // Mock save calls for PhoneNumber (3 valid numbers)
        // We verify the properties set on the PhoneNumber entity itself
        $this->phoneNumberRepository->save(Argument::that(function (PhoneNumber $pn) use ($normalizedNum1, $csvData) {
            return $pn->getNumber() === $normalizedNum1 &&
                $pn->getCivility() === $csvData[1][0] &&
                $pn->getFirstName() === $csvData[1][1] &&
                $pn->getName() === $csvData[1][2] &&
                $pn->getCompany() === $csvData[1][4] &&
                $pn->getSector() === $csvData[1][5] &&
                $pn->getNotes() === $csvData[1][6];
            // Segmentation result is handled internally by the service calling addTechnicalSegment
            // We trust the segmentation mock ensures the correct data was passed to addTechnicalSegment
        }))->shouldBeCalledOnce();
        $this->phoneNumberRepository->save(Argument::that(function (PhoneNumber $pn) use ($normalizedNum2, $csvData) {
            return $pn->getNumber() === $normalizedNum2 &&
                $pn->getCivility() === $csvData[2][0] &&
                $pn->getFirstName() === $csvData[2][1] &&
                $pn->getName() === $csvData[2][2] &&
                $pn->getCompany() === $csvData[2][4] &&
                $pn->getSector() === $csvData[2][5] &&
                $pn->getNotes() === $csvData[2][6];
        }))->shouldBeCalledOnce();
        $this->phoneNumberRepository->save(Argument::that(function (PhoneNumber $pn) use ($normalizedNum3, $csvData) {
            return $pn->getNumber() === $normalizedNum3 &&
                $pn->getCivility() === $csvData[4][0] &&
                $pn->getFirstName() === $csvData[4][1] &&
                $pn->getName() === $csvData[4][2] &&
                $pn->getCompany() === $csvData[4][4] &&
                $pn->getSector() === $csvData[4][5] &&
                $pn->getNotes() === $csvData[4][6];
        }))->shouldBeCalledOnce();

        // Mock save calls for Contact (3 valid numbers)
        // Contact only stores userId, name, phoneNumber, email, notes
        $this->contactRepository->save(Argument::that(function (Contact $c) use ($userId, $normalizedNum1, $csvData) {
            return $c->getUserId() === $userId &&
                $c->getPhoneNumber() === $normalizedNum1 &&
                $c->getName() === $csvData[1][2] && // Name from CSV
                $c->getEmail() === $csvData[1][7] &&
                $c->getNotes() === $csvData[1][6]; // Notes from CSV
        }))->shouldBeCalledOnce();
        $this->contactRepository->save(Argument::that(function (Contact $c) use ($userId, $normalizedNum2, $csvData) {
            return $c->getUserId() === $userId &&
                $c->getPhoneNumber() === $normalizedNum2 &&
                $c->getName() === $csvData[2][2] &&
                $c->getEmail() === $csvData[2][7] &&
                $c->getNotes() === $csvData[2][6];
        }))->shouldBeCalledOnce();
        $this->contactRepository->save(Argument::that(function (Contact $c) use ($userId, $normalizedNum3, $csvData) {
            return $c->getUserId() === $userId &&
                $c->getPhoneNumber() === $normalizedNum3 &&
                $c->getName() === $csvData[4][2] &&
                $c->getEmail() === $csvData[4][7] &&
                $c->getNotes() === $csvData[4][6];
        }))->shouldBeCalledOnce();

        // Mock logger calls (1 warning for invalid number)
        $this->logger->warning(Argument::containingString('Format de numéro invalide'), Argument::type('array'))->shouldBeCalledOnce();
        $this->logger->info('Importation CSV terminée.', Argument::type('array'))->shouldBeCalled();


        // Act
        $result = $this->service->importFromFile($filePath, $options);

        // Assert
        $this->assertEquals('success', $result['status']);
        $this->assertEquals(4, $result['stats']['total']);
        $this->assertEquals(3, $result['stats']['processed']);
        $this->assertEquals(3, $result['stats']['contactsCreated']);
        $this->assertEquals(1, $result['stats']['invalid']);
        $this->assertEquals(0, $result['stats']['duplicates']);
        $this->assertCount(0, $result['errors']);
        $this->assertCount(1, $result['detailedErrors']);
        $this->assertStringContainsString('Format de numéro invalide', $result['detailedErrors'][0]['message']);
        $this->assertStringContainsString('invalid-number', $result['detailedErrors'][0]['value']);

        // Verify saves
        $this->phoneNumberRepository->save(Argument::type(PhoneNumber::class))->shouldHaveBeenCalledTimes(3);
        $this->contactRepository->save(Argument::type(Contact::class))->shouldHaveBeenCalledTimes(3);

        // Clean up
        unlink($filePath);
    }

    /**
     * Test importing when a repository save operation fails.
     * @test
     */
    public function importHandlesRepositorySaveException(): void
    {
        $userId = 1;
        $csvData = [
            ['phoneNumber', 'name'],
            ['+2250909090909', 'Save Fail'], // This one will fail
            ['+2251010101010', 'Save Success'], // This one should still process
        ];
        $filePath = $this->createTempCsvFile($csvData);
        $options = [
            'userId' => $userId,
            'hasHeader' => true,
            'createContacts' => true
        ];

        $exceptionMessage = 'Database error during save';

        // Mock repository calls
        // First number: Assume not found, but save fails
        $this->contactRepository->findByPhoneNumberAndUserId('+2250909090909', $userId)->shouldBeCalledOnce()->willReturn(null);
        $this->phoneNumberRepository->findByNumber('+2250909090909')->shouldBeCalledOnce()->willReturn(null);
        $this->phoneNumberRepository->save(Argument::that(fn(PhoneNumber $pn) => $pn->getNumber() === '+2250909090909'))
            ->shouldBeCalledOnce()
            ->willThrow(new Exception($exceptionMessage)); // Throw exception on save

        // Second number: Assume not found, save succeeds
        $this->contactRepository->findByPhoneNumberAndUserId('+2251010101010', $userId)->shouldBeCalledOnce()->willReturn(null);
        $this->phoneNumberRepository->findByNumber('+2251010101010')->shouldBeCalledOnce()->willReturn(null);
        $this->phoneNumberRepository->save(Argument::that(fn(PhoneNumber $pn) => $pn->getNumber() === '+2251010101010'))->shouldBeCalledOnce(); // No exception
        $this->contactRepository->save(Argument::that(fn(Contact $c) => $c->getPhoneNumber() === '+2251010101010'))->shouldBeCalledOnce(); // Contact save succeeds

        // Mock logger calls (1 error for the exception, 1 info for completion)
        $this->logger->error(
            Argument::containingString('Erreur lors du traitement de la ligne'),
            Argument::that(function (array $context) use ($exceptionMessage) {
                return isset($context['exception']) &&
                    $context['exception'] instanceof Exception &&
                    $context['exception']->getMessage() === $exceptionMessage &&
                    isset($context['rowNumber']) && $context['rowNumber'] === 2 && // Row 2 (index 1 in data)
                    isset($context['rowData']);
            })
        )->shouldBeCalledOnce();
        $this->logger->info('Importation CSV terminée.', Argument::type('array'))->shouldBeCalled();


        // Act
        $result = $this->service->importFromFile($filePath, $options);

        // Assert
        $this->assertEquals('success', $result['status']); // Overall status is success, but with errors
        $this->assertEquals(2, $result['stats']['total']);
        $this->assertEquals(1, $result['stats']['processed']); // Only the second one was fully processed
        $this->assertEquals(1, $result['stats']['contactsCreated']); // Only the second contact
        $this->assertEquals(1, $result['stats']['errors']); // The save exception counts as an error
        $this->assertEquals(0, $result['stats']['invalid']); // Not invalid format, but save error
        $this->assertEquals(0, $result['stats']['duplicates']);
        $this->assertCount(1, $result['detailedErrors']); // One detailed error for the exception
        $this->assertStringContainsString('Erreur interne', $result['detailedErrors'][0]['message']); // Generic error message shown
        $this->assertStringContainsString('+2250909090909', $result['detailedErrors'][0]['value']);

        // Verify saves
        $this->phoneNumberRepository->save(Argument::type(PhoneNumber::class))->shouldHaveBeenCalledTimes(2); // Both attempted
        $this->contactRepository->save(Argument::type(Contact::class))->shouldHaveBeenCalledTimes(1); // Only the successful one

        // Clean up
        unlink($filePath);
    }


    /**
     * Helper to create a temporary CSV file for testing.
     * Returns the path to the created file.
     */
    private function createTempCsvFile(array $data): string
    {
        $filePath = tempnam(sys_get_temp_dir(), 'csvtest');
        if ($filePath === false) {
            $this->fail('Failed to create temporary file for CSV test.');
        }
        $file = new SplFileObject($filePath, 'w');
        foreach ($data as $row) {
            $file->fputcsv($row);
        }
        return $filePath;
    }
}
