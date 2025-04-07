<?php

namespace App\GraphQL\Types;

use App\Models\PhoneNumber;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * @Type(class=PhoneNumber::class)
 */
class PhoneNumberType
{
    /**
     * @Field
     */
    public function id(PhoneNumber $phoneNumber): ?int
    {
        return $phoneNumber->getId();
    }

    /**
     * @Field
     */
    public function number(PhoneNumber $phoneNumber): string
    {
        return $phoneNumber->getNumber();
    }

    /**
     * @Field
     */
    public function civility(PhoneNumber $phoneNumber): ?string
    {
        return $phoneNumber->getCivility();
    }

    /**
     * @Field
     */
    public function firstName(PhoneNumber $phoneNumber): ?string
    {
        return $phoneNumber->getFirstName();
    }

    /**
     * @Field
     */
    public function name(PhoneNumber $phoneNumber): ?string
    {
        return $phoneNumber->getName();
    }

    /**
     * @Field
     */
    public function company(PhoneNumber $phoneNumber): ?string
    {
        return $phoneNumber->getCompany();
    }

    /**
     * @Field
     */
    public function sector(PhoneNumber $phoneNumber): ?string
    {
        return $phoneNumber->getSector();
    }

    /**
     * @Field
     */
    public function notes(PhoneNumber $phoneNumber): ?string
    {
        return $phoneNumber->getNotes();
    }

    /**
     * @Field
     */
    public function dateAdded(PhoneNumber $phoneNumber): ?string
    {
        return $phoneNumber->getDateAdded();
    }

    /**
     * @Field
     */
    public function technicalSegments(PhoneNumber $phoneNumber): array
    {
        return $phoneNumber->getTechnicalSegments();
    }

    /**
     * @Field
     */
    public function customSegments(PhoneNumber $phoneNumber): array
    {
        return $phoneNumber->getCustomSegments();
    }
}
