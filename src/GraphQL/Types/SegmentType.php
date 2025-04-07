<?php

namespace App\GraphQL\Types;

use App\Models\Segment;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * @Type(class=Segment::class)
 */
class SegmentType
{
    /**
     * @Field
     */
    public function id(Segment $segment): ?int
    {
        return $segment->getId();
    }

    /**
     * @Field
     */
    public function phoneNumberId(Segment $segment): ?int
    {
        return $segment->getPhoneNumberId();
    }

    /**
     * @Field
     */
    public function segmentType(Segment $segment): string
    {
        return $segment->getSegmentType();
    }

    /**
     * @Field
     */
    public function value(Segment $segment): string
    {
        return $segment->getValue();
    }
}
