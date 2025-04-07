<?php

namespace App\GraphQL\Types;

use App\Models\CustomSegment;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * @Type(class=CustomSegment::class)
 */
class CustomSegmentType
{
    /**
     * @Field
     */
    public function id(CustomSegment $segment): ?int
    {
        return $segment->getId();
    }

    /**
     * @Field
     */
    public function name(CustomSegment $segment): string
    {
        return $segment->getName();
    }

    /**
     * @Field
     */
    public function description(CustomSegment $segment): ?string
    {
        return $segment->getDescription();
    }

    /**
     * @Field
     */
    public function pattern(CustomSegment $segment): ?string
    {
        return $segment->getPattern();
    }

    /**
     * @Field
     */
    public function phoneCount(CustomSegment $segment): int
    {
        return count($segment->getPhoneNumbers());
    }
}
