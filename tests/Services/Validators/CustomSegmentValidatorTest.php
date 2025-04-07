<?php

namespace Tests\Services\Validators;

use PHPUnit\Framework\TestCase;
use App\Services\Validators\CustomSegmentValidator;
use App\Repositories\CustomSegmentRepository;
use App\Services\Interfaces\RegexValidatorInterface;
use App\Models\CustomSegment;
use App\Exceptions\ValidationException;

class CustomSegmentValidatorTest extends TestCase
{
    /**
     * @var CustomSegmentRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $customSegmentRepository;

    /**
     * @var RegexValidatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $regexValidator;

    /**
     * @var CustomSegmentValidator
     */
    private $validator;

    protected function setUp(): void
    {
        $this->customSegmentRepository = $this->createMock(CustomSegmentRepository::class);
        $this->regexValidator = $this->createMock(RegexValidatorInterface::class);
        $this->validator = new CustomSegmentValidator(
            $this->customSegmentRepository,
            $this->regexValidator
        );
    }

    public function testValidateCreateWithValidData()
    {
        // Arrange
        $name = "Test Segment";
        $pattern = "^225\\d+$";
        $description = "Test description";

        $this->customSegmentRepository->method('findByName')
            ->with($name)
            ->willReturn(null);

        $this->regexValidator->method('isValid')
            ->with($pattern)
            ->willReturn(true);

        // Act
        $result = $this->validator->validateCreate([
            'name' => $name,
            'pattern' => $pattern,
            'description' => $description
        ]);

        // Assert
        $this->assertEquals([
            'name' => $name,
            'pattern' => $pattern,
            'description' => $description
        ], $result);
    }

    public function testValidateCreateWithMissingName()
    {
        // Arrange
        $pattern = "^225\\d+$";
        $description = "Test description";

        $this->regexValidator->method('isValid')
            ->with($pattern)
            ->willReturn(true);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validateCreate([
            'pattern' => $pattern,
            'description' => $description
        ]);
    }

    public function testValidateCreateWithMissingPattern()
    {
        // Arrange
        $name = "Test Segment";
        $description = "Test description";

        $this->customSegmentRepository->method('findByName')
            ->with($name)
            ->willReturn(null);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validateCreate([
            'name' => $name,
            'description' => $description
        ]);
    }

    public function testValidateCreateWithInvalidPattern()
    {
        // Arrange
        $name = "Test Segment";
        $pattern = "^225\\d+$["; // Invalid regex pattern
        $description = "Test description";

        $this->customSegmentRepository->method('findByName')
            ->with($name)
            ->willReturn(null);

        $this->regexValidator->method('isValid')
            ->with($pattern)
            ->willReturn(false);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validateCreate([
            'name' => $name,
            'pattern' => $pattern,
            'description' => $description
        ]);
    }

    public function testValidateCreateWithDuplicateName()
    {
        // Arrange
        $name = "Test Segment";
        $pattern = "^225\\d+$";
        $description = "Test description";

        $existingSegment = new CustomSegment($name, $pattern, $description);

        $this->customSegmentRepository->method('findByName')
            ->with($name)
            ->willReturn($existingSegment);

        $this->regexValidator->method('isValid')
            ->with($pattern)
            ->willReturn(true);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validateCreate([
            'name' => $name,
            'pattern' => $pattern,
            'description' => $description
        ]);
    }

    public function testValidateUpdateWithValidData()
    {
        // Arrange
        $id = 1;
        $name = "Updated Segment";
        $pattern = "^225\\d+$";
        $description = "Updated description";

        $segment = new CustomSegment("Test Segment", "^\\d+$", "Old description");
        $segment->setId($id);

        $this->customSegmentRepository->method('findById')
            ->with($id)
            ->willReturn($segment);

        $this->customSegmentRepository->method('findByName')
            ->with($name)
            ->willReturn(null);

        $this->regexValidator->method('isValid')
            ->with($pattern)
            ->willReturn(true);

        // Act
        $result = $this->validator->validateUpdate($id, [
            'name' => $name,
            'pattern' => $pattern,
            'description' => $description
        ]);

        // Assert
        $this->assertEquals([
            'id' => $id,
            'name' => $name,
            'pattern' => $pattern,
            'description' => $description
        ], $result);
    }

    public function testValidateUpdateWithNonExistentSegment()
    {
        // Arrange
        $id = 999;
        $name = "Updated Segment";
        $pattern = "^225\\d+$";
        $description = "Updated description";

        $this->customSegmentRepository->method('findById')
            ->with($id)
            ->willReturn(null);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validateUpdate($id, [
            'name' => $name,
            'pattern' => $pattern,
            'description' => $description
        ]);
    }

    public function testValidateUpdateWithInvalidPattern()
    {
        // Arrange
        $id = 1;
        $name = "Updated Segment";
        $pattern = "^225\\d+$["; // Invalid regex pattern
        $description = "Updated description";

        $segment = new CustomSegment("Test Segment", "^\\d+$", "Old description");
        $segment->setId($id);

        $this->customSegmentRepository->method('findById')
            ->with($id)
            ->willReturn($segment);

        $this->customSegmentRepository->method('findByName')
            ->with($name)
            ->willReturn(null);

        $this->regexValidator->method('isValid')
            ->with($pattern)
            ->willReturn(false);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validateUpdate($id, [
            'name' => $name,
            'pattern' => $pattern,
            'description' => $description
        ]);
    }

    public function testValidateUpdateWithDuplicateName()
    {
        // Arrange
        $id = 1;
        $name = "Duplicate Segment";
        $pattern = "^225\\d+$";
        $description = "Updated description";

        $segment = new CustomSegment("Test Segment", "^\\d+$", "Old description");
        $segment->setId($id);

        $existingSegment = new CustomSegment($name, "^\\d+$", "Another description");
        $existingSegment->setId(2);

        $this->customSegmentRepository->method('findById')
            ->with($id)
            ->willReturn($segment);

        $this->customSegmentRepository->method('findByName')
            ->with($name)
            ->willReturn($existingSegment);

        $this->regexValidator->method('isValid')
            ->with($pattern)
            ->willReturn(true);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validateUpdate($id, [
            'name' => $name,
            'pattern' => $pattern,
            'description' => $description
        ]);
    }

    public function testValidateDeleteWithValidId()
    {
        // Arrange
        $id = 1;

        $segment = new CustomSegment("Test Segment", "^\\d+$", "Description");
        $segment->setId($id);

        $this->customSegmentRepository->method('findById')
            ->with($id)
            ->willReturn($segment);

        // Act
        $result = $this->validator->validateDelete($id);

        // Assert
        $this->assertEquals(['id' => $id], $result);
    }

    public function testValidateDeleteWithNonExistentSegment()
    {
        // Arrange
        $id = 999;

        $this->customSegmentRepository->method('findById')
            ->with($id)
            ->willReturn(null);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validateDelete($id);
    }

    public function testValidateCreateWithPatternWithValidData()
    {
        // Arrange
        $name = "Test Segment";
        $pattern = "^225\\d+$";
        $description = "Test description";

        $this->customSegmentRepository->method('findByName')
            ->with($name)
            ->willReturn(null);

        $this->regexValidator->method('isValid')
            ->with($pattern)
            ->willReturn(true);

        // Act
        $result = $this->validator->validateCreateWithPattern($name, $pattern, $description);

        // Assert
        $this->assertEquals([
            'name' => $name,
            'pattern' => $pattern,
            'description' => $description
        ], $result);
    }

    public function testValidateCreateWithPatternWithInvalidPattern()
    {
        // Arrange
        $name = "Test Segment";
        $pattern = "^225\\d+$["; // Invalid regex pattern
        $description = "Test description";

        $this->customSegmentRepository->method('findByName')
            ->with($name)
            ->willReturn(null);

        $this->regexValidator->method('isValid')
            ->with($pattern)
            ->willReturn(false);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validateCreateWithPattern($name, $pattern, $description);
    }

    public function testValidateRegexWithValidPattern()
    {
        // Arrange
        $pattern = "^225\\d+$";

        $this->regexValidator->method('isValid')
            ->with($pattern)
            ->willReturn(true);

        // Act
        $result = $this->validator->validateRegex($pattern);

        // Assert
        $this->assertTrue($result);
    }

    public function testValidateRegexWithInvalidPattern()
    {
        // Arrange
        $pattern = "^225\\d+$["; // Invalid regex pattern

        $this->regexValidator->method('isValid')
            ->with($pattern)
            ->willReturn(false);

        // Act
        $result = $this->validator->validateRegex($pattern);

        // Assert
        $this->assertFalse($result);
    }
}
