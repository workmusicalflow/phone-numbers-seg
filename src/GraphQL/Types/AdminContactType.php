<?php

namespace App\GraphQL\Types;

use App\Models\AdminContact;
use App\Repositories\CustomSegmentRepository;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * Type GraphQL pour les contacts administrateur
 * 
 * @Type
 */
class AdminContactType
{
    private $customSegmentRepository;

    public function __construct(CustomSegmentRepository $customSegmentRepository)
    {
        $this->customSegmentRepository = $customSegmentRepository;
    }

    /**
     * @Field
     */
    public function id(AdminContact $adminContact): int
    {
        return $adminContact->getId();
    }

    /**
     * @Field
     */
    public function segmentId(AdminContact $adminContact): ?int
    {
        return $adminContact->getSegmentId();
    }

    /**
     * @Field
     */
    public function phoneNumber(AdminContact $adminContact): string
    {
        return $adminContact->getPhoneNumber();
    }

    /**
     * @Field
     */
    public function name(AdminContact $adminContact): ?string
    {
        return $adminContact->getName();
    }

    /**
     * @Field
     */
    public function createdAt(AdminContact $adminContact): string
    {
        return $adminContact->getCreatedAt();
    }

    /**
     * @Field
     */
    public function updatedAt(AdminContact $adminContact): ?string
    {
        return $adminContact->getUpdatedAt();
    }

    /**
     * @Field
     */
    public function segment(AdminContact $adminContact): ?\App\Models\CustomSegment
    {
        $segmentId = $adminContact->getSegmentId();
        if ($segmentId === null) {
            return null;
        }
        return $this->customSegmentRepository->findById($segmentId);
    }

    /**
     * @Field
     */
    public function displayName(AdminContact $adminContact): string
    {
        $name = $adminContact->getName();
        if ($name) {
            return $name;
        }
        return $adminContact->getPhoneNumber();
    }

    /**
     * @Field
     */
    public function formattedPhoneNumber(AdminContact $adminContact): string
    {
        $phoneNumber = $adminContact->getPhoneNumber();

        // Format international: +XXX XX XXX XX XX
        if (strlen($phoneNumber) >= 10) {
            $countryCode = substr($phoneNumber, 0, strlen($phoneNumber) - 9);
            $number = substr($phoneNumber, -9);

            $formatted = $countryCode . ' ';
            $formatted .= substr($number, 0, 2) . ' ';
            $formatted .= substr($number, 2, 3) . ' ';
            $formatted .= substr($number, 5, 2) . ' ';
            $formatted .= substr($number, 7, 2);

            return $formatted;
        }

        return $phoneNumber;
    }
}
