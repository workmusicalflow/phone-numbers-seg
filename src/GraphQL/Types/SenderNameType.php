<?php

namespace App\GraphQL\Types;

use App\Models\SenderName;
use App\Repositories\UserRepository;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * Type GraphQL pour les noms d'expéditeur
 * 
 * @Type
 */
class SenderNameType
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @Field
     */
    public function id(SenderName $senderName): int
    {
        return $senderName->getId();
    }

    /**
     * @Field
     */
    public function userId(SenderName $senderName): int
    {
        return $senderName->getUserId();
    }

    /**
     * @Field
     */
    public function name(SenderName $senderName): string
    {
        return $senderName->getName();
    }

    /**
     * @Field
     */
    public function status(SenderName $senderName): string
    {
        return $senderName->getStatus();
    }

    /**
     * @Field
     */
    public function createdAt(SenderName $senderName): string
    {
        return $senderName->getCreatedAt();
    }

    /**
     * @Field
     */
    public function updatedAt(SenderName $senderName): ?string
    {
        return $senderName->getUpdatedAt();
    }

    /**
     * @Field
     */
    public function user(SenderName $senderName): ?\App\Models\User
    {
        return $this->userRepository->findById($senderName->getUserId());
    }

    /**
     * @Field
     */
    public function isPending(SenderName $senderName): bool
    {
        return $senderName->getStatus() === SenderName::STATUS_PENDING;
    }

    /**
     * @Field
     */
    public function isApproved(SenderName $senderName): bool
    {
        return $senderName->getStatus() === SenderName::STATUS_APPROVED;
    }

    /**
     * @Field
     */
    public function isRejected(SenderName $senderName): bool
    {
        return $senderName->getStatus() === SenderName::STATUS_REJECTED;
    }
}
