<?php

namespace Tests\Services;

use App\Entities\PhoneNumber;
use App\Entities\CustomSegment;
use App\Repositories\Interfaces\CustomSegmentRepositoryInterface;
use App\Services\Interfaces\RegexValidatorInterface;
use App\Services\CustomSegmentMatcher;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Test class for CustomSegmentMatcher
 *
 * @covers \App\Services\CustomSegmentMatcher
 */
class CustomSegmentMatcherTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $customSegmentRepositoryProphecy;
    private ObjectProphecy $regexValidatorProphecy;
    private CustomSegmentMatcher $service;

    protected function setUp(): void
    {
        $this->customSegmentRepositoryProphecy = $this->prophesize(CustomSegmentRepositoryInterface::class);
        $this->regexValidatorProphecy = $this->prophesize(RegexValidatorInterface::class);

        $this->service = new CustomSegmentMatcher(
            $this->customSegmentRepositoryProphecy->reveal(),
            $this->regexValidatorProphecy->reveal()
        );
    }

    // Helper to create a mock PhoneNumber
    private function createMockPhoneNumber(int $id, string $number): PhoneNumber
    {
        $phone = new PhoneNumber();
        $phone->setId($id); // Need ID for repo lookups
        $phone->setNumber($number);
        return $phone;
    }

    // Helper to create a mock CustomSegment
    private function createMockCustomSegment(int $id, string $name, ?string $pattern): CustomSegment
    {
        $segment = new CustomSegment();
        $segment->setId($id);
        $segment->setName($name);
        if ($pattern !== null) {
            $segment->setPattern($pattern);
        }
        return $segment;
    }

    /**
     * @test
     */
    public function matchesReturnsTrueForMatchingPattern(): void
    {
        $phoneNumber = $this->createMockPhoneNumber(1, '+2250712345678'); // Orange number
        $segment = $this->createMockCustomSegment(10, 'Orange Numbers', '/^\+22507/'); // Pattern for Orange

        $this->regexValidatorProphecy->test('/^\+22507/', '+2250712345678')->shouldBeCalledOnce()->willReturn(true);

        $result = $this->service->matches($phoneNumber, $segment);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function matchesReturnsFalseForNonMatchingPattern(): void
    {
        $phoneNumber = $this->createMockPhoneNumber(1, '+2250512345678'); // MTN number
        $segment = $this->createMockCustomSegment(10, 'Orange Numbers', '/^\+22507/'); // Pattern for Orange

        $this->regexValidatorProphecy->test('/^\+22507/', '+2250512345678')->shouldBeCalledOnce()->willReturn(false);

        $result = $this->service->matches($phoneNumber, $segment);

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function matchesReturnsFalseForEmptyPattern(): void
    {
        $phoneNumber = $this->createMockPhoneNumber(1, '+2250712345678');
        $segment = $this->createMockCustomSegment(10, 'Segment Without Pattern', null); // Empty pattern

        // Validator should not be called if pattern is empty
        $this->regexValidatorProphecy->test(Argument::any(), Argument::any())->shouldNotBeCalled();

        $result = $this->service->matches($phoneNumber, $segment);

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function findMatchingSegmentsReturnsCorrectSegments(): void
    {
        $phoneNumber = $this->createMockPhoneNumber(1, '+2250712345678');
        $segment1 = $this->createMockCustomSegment(10, 'Orange Numbers', '/^\+22507/'); // Matches
        $segment2 = $this->createMockCustomSegment(11, 'MTN Numbers', '/^\+22505/');    // Doesn't match
        $segment3 = $this->createMockCustomSegment(12, 'All CI Numbers', '/^\+225/');  // Matches
        $segment4 = $this->createMockCustomSegment(13, 'No Pattern', null);           // No pattern

        $allSegments = [$segment1, $segment2, $segment3, $segment4];

        $this->customSegmentRepositoryProphecy->findAll()->shouldBeCalledOnce()->willReturn($allSegments);

        // Mock regex validator calls
        $this->regexValidatorProphecy->test('/^\+22507/', '+2250712345678')->shouldBeCalledOnce()->willReturn(true);
        $this->regexValidatorProphecy->test('/^\+22505/', '+2250712345678')->shouldBeCalledOnce()->willReturn(false);
        $this->regexValidatorProphecy->test('/^\+225/', '+2250712345678')->shouldBeCalledOnce()->willReturn(true);
        // Validator not called for segment4 (no pattern)

        $result = $this->service->findMatchingSegments($phoneNumber);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertSame($segment1, $result[0]);
        $this->assertSame($segment3, $result[1]);
    }

    /**
     * @test
     */
    public function findMatchingSegmentsReturnsEmptyWhenNoMatches(): void
    {
        $phoneNumber = $this->createMockPhoneNumber(1, '+33612345678'); // French number
        $segment1 = $this->createMockCustomSegment(10, 'Orange CI', '/^\+22507/');
        $segment2 = $this->createMockCustomSegment(11, 'MTN CI', '/^\+22505/');

        $allSegments = [$segment1, $segment2];

        $this->customSegmentRepositoryProphecy->findAll()->shouldBeCalledOnce()->willReturn($allSegments);

        $this->regexValidatorProphecy->test('/^\+22507/', '+33612345678')->shouldBeCalledOnce()->willReturn(false);
        $this->regexValidatorProphecy->test('/^\+22505/', '+33612345678')->shouldBeCalledOnce()->willReturn(false);

        $result = $this->service->findMatchingSegments($phoneNumber);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    /**
     * @test
     */
    public function findMatchingSegmentsReturnsEmptyWhenNoSegmentsInRepo(): void
    {
        $phoneNumber = $this->createMockPhoneNumber(1, '+2250712345678');
        $allSegments = []; // No segments defined

        $this->customSegmentRepositoryProphecy->findAll()->shouldBeCalledOnce()->willReturn($allSegments);
        $this->regexValidatorProphecy->test(Argument::any(), Argument::any())->shouldNotBeCalled();

        $result = $this->service->findMatchingSegments($phoneNumber);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    /**
     * @test
     */
    public function autoAssignSegmentsAssignsNewSegments(): void
    {
        $phoneNumber = $this->createMockPhoneNumber(1, '+2250712345678');
        $segment1 = $this->createMockCustomSegment(10, 'Orange Numbers', '/^\+22507/'); // Matches
        $segment2 = $this->createMockCustomSegment(11, 'All CI Numbers', '/^\+225/');  // Matches

        $allSegments = [$segment1, $segment2];
        $matchingSegments = [$segment1, $segment2]; // Both should match

        // Mock finding all segments
        $this->customSegmentRepositoryProphecy->findAll()->shouldBeCalledOnce()->willReturn($allSegments);
        // Mock regex checks
        $this->regexValidatorProphecy->test('/^\+22507/', '+2250712345678')->shouldBeCalledOnce()->willReturn(true);
        $this->regexValidatorProphecy->test('/^\+225/', '+2250712345678')->shouldBeCalledOnce()->willReturn(true);

        // Mock checking existing segments for the phone number (returns empty)
        $this->customSegmentRepositoryProphecy->findByPhoneNumberId(1)->shouldBeCalledOnce()->willReturn([]);

        // Expect adding the phone number to both segments
        $this->customSegmentRepositoryProphecy->addPhoneNumberToSegment(1, 10)->shouldBeCalledOnce();
        $this->customSegmentRepositoryProphecy->addPhoneNumberToSegment(1, 11)->shouldBeCalledOnce();

        $count = $this->service->autoAssignSegments($phoneNumber);

        $this->assertEquals(2, $count);
    }

    /**
     * @test
     */
    public function autoAssignSegmentsSkipsExistingSegments(): void
    {
        $phoneNumber = $this->createMockPhoneNumber(1, '+2250712345678');
        $segment1 = $this->createMockCustomSegment(10, 'Orange Numbers', '/^\+22507/'); // Matches, already assigned
        $segment2 = $this->createMockCustomSegment(11, 'All CI Numbers', '/^\+225/');  // Matches, new

        $allSegments = [$segment1, $segment2];
        $matchingSegments = [$segment1, $segment2];

        // Mock finding all segments
        $this->customSegmentRepositoryProphecy->findAll()->shouldBeCalledOnce()->willReturn($allSegments);
        // Mock regex checks
        $this->regexValidatorProphecy->test('/^\+22507/', '+2250712345678')->shouldBeCalledOnce()->willReturn(true);
        $this->regexValidatorProphecy->test('/^\+225/', '+2250712345678')->shouldBeCalledOnce()->willReturn(true);

        // Mock checking existing segments (returns segment1)
        $this->customSegmentRepositoryProphecy->findByPhoneNumberId(1)->shouldBeCalledOnce()->willReturn([$segment1]);

        // Expect adding the phone number only to segment2
        $this->customSegmentRepositoryProphecy->addPhoneNumberToSegment(1, 10)->shouldNotBeCalled();
        $this->customSegmentRepositoryProphecy->addPhoneNumberToSegment(1, 11)->shouldBeCalledOnce();

        $count = $this->service->autoAssignSegments($phoneNumber);

        $this->assertEquals(1, $count); // Only segment2 was newly assigned
    }

    /**
     * @test
     */
    public function autoAssignSegmentsReturnsZeroWhenNoMatches(): void
    {
        $phoneNumber = $this->createMockPhoneNumber(1, '+33612345678'); // French number
        $segment1 = $this->createMockCustomSegment(10, 'Orange CI', '/^\+22507/');
        $segment2 = $this->createMockCustomSegment(11, 'MTN CI', '/^\+22505/');

        $allSegments = [$segment1, $segment2];

        // Mock finding all segments
        $this->customSegmentRepositoryProphecy->findAll()->shouldBeCalledOnce()->willReturn($allSegments);
        // Mock regex checks (no matches)
        $this->regexValidatorProphecy->test('/^\+22507/', '+33612345678')->shouldBeCalledOnce()->willReturn(false);
        $this->regexValidatorProphecy->test('/^\+22505/', '+33612345678')->shouldBeCalledOnce()->willReturn(false);

        // findByPhoneNumberId should not be called if no segments match
        $this->customSegmentRepositoryProphecy->findByPhoneNumberId(Argument::any())->shouldNotBeCalled();
        // addPhoneNumberToSegment should not be called
        $this->customSegmentRepositoryProphecy->addPhoneNumberToSegment(Argument::any(), Argument::any())->shouldNotBeCalled();

        $count = $this->service->autoAssignSegments($phoneNumber);

        $this->assertEquals(0, $count);
    }

    /**
     * @test
     */
    public function batchAutoAssignSegmentsCallsAutoAssignForEachNumber(): void
    {
        $phone1 = $this->createMockPhoneNumber(1, '+2250711111111'); // Matches seg10, seg11
        $phone2 = $this->createMockPhoneNumber(2, '+2250522222222'); // Matches seg11
        $phone3 = $this->createMockPhoneNumber(3, '+33633333333'); // Matches none

        $segment10 = $this->createMockCustomSegment(10, 'Orange', '/^\+22507/');
        $segment11 = $this->createMockCustomSegment(11, 'CI', '/^\+225/');

        $allSegments = [$segment10, $segment11];

        // Mock findAll once for the batch
        $this->customSegmentRepositoryProphecy->findAll()->shouldBeCalledOnce()->willReturn($allSegments);

        // --- Expectations for Phone 1 ---
        $this->regexValidatorProphecy->test('/^\+22507/', '+2250711111111')->shouldBeCalled()->willReturn(true);
        $this->regexValidatorProphecy->test('/^\+225/', '+2250711111111')->shouldBeCalled()->willReturn(true);
        $this->customSegmentRepositoryProphecy->findByPhoneNumberId(1)->shouldBeCalledOnce()->willReturn([]); // Assume no existing
        $this->customSegmentRepositoryProphecy->addPhoneNumberToSegment(1, 10)->shouldBeCalledOnce();
        $this->customSegmentRepositoryProphecy->addPhoneNumberToSegment(1, 11)->shouldBeCalledOnce();

        // --- Expectations for Phone 2 ---
        $this->regexValidatorProphecy->test('/^\+22507/', '+2250522222222')->shouldBeCalled()->willReturn(false);
        $this->regexValidatorProphecy->test('/^\+225/', '+2250522222222')->shouldBeCalled()->willReturn(true);
        $this->customSegmentRepositoryProphecy->findByPhoneNumberId(2)->shouldBeCalledOnce()->willReturn([$segment11]); // Assume already in seg11
        $this->customSegmentRepositoryProphecy->addPhoneNumberToSegment(2, 10)->shouldNotBeCalled();
        $this->customSegmentRepositoryProphecy->addPhoneNumberToSegment(2, 11)->shouldNotBeCalled(); // Already assigned

        // --- Expectations for Phone 3 ---
        $this->regexValidatorProphecy->test('/^\+22507/', '+33633333333')->shouldBeCalled()->willReturn(false);
        $this->regexValidatorProphecy->test('/^\+225/', '+33633333333')->shouldBeCalled()->willReturn(false);
        $this->customSegmentRepositoryProphecy->findByPhoneNumberId(3)->shouldNotBeCalled(); // No matches, no check needed
        $this->customSegmentRepositoryProphecy->addPhoneNumberToSegment(3, Argument::any())->shouldNotBeCalled();


        // Act
        $results = $this->service->batchAutoAssignSegments([$phone1, $phone2, $phone3]);

        // Assert
        $this->assertIsArray($results);
        $this->assertCount(3, $results);
        $this->assertEquals(2, $results[1]); // Phone 1 assigned 2 new segments
        $this->assertEquals(0, $results[2]); // Phone 2 assigned 0 new segments (already had the matching one)
        $this->assertEquals(0, $results[3]); // Phone 3 assigned 0 new segments (no matches)
    }
}
