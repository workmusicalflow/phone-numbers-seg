<?php

namespace App\GraphQL\Types;

use App\Models\User;
use App\Repositories\SenderNameRepository;
use App\Repositories\SMSOrderRepository;
use App\Repositories\OrangeAPIConfigRepository;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * Type GraphQL pour les utilisateurs
 * 
 * @Type
 */
class UserType
{
    private $senderNameRepository;
    private $smsOrderRepository;
    private $orangeAPIConfigRepository;

    public function __construct(
        SenderNameRepository $senderNameRepository,
        SMSOrderRepository $smsOrderRepository,
        OrangeAPIConfigRepository $orangeAPIConfigRepository
    ) {
        $this->senderNameRepository = $senderNameRepository;
        $this->smsOrderRepository = $smsOrderRepository;
        $this->orangeAPIConfigRepository = $orangeAPIConfigRepository;
    }

    /**
     * @Field
     */
    public function id(User $user): int
    {
        return $user->getId();
    }

    /**
     * @Field
     */
    public function username(User $user): string
    {
        return $user->getUsername();
    }

    /**
     * @Field
     */
    public function email(User $user): ?string
    {
        return $user->getEmail();
    }

    /**
     * @Field
     */
    public function smsCredit(User $user): int
    {
        return $user->getSmsCredit();
    }

    /**
     * @Field
     */
    public function smsLimit(User $user): ?int
    {
        return $user->getSmsLimit();
    }

    /**
     * @Field
     */
    public function createdAt(User $user): string
    {
        return $user->getCreatedAt();
    }

    /**
     * @Field
     */
    public function updatedAt(User $user): ?string
    {
        return $user->getUpdatedAt();
    }

    /**
     * @Field
     */
    public function isAdmin(User $user): bool
    {
        return $user->isAdmin();
    }
    
    /**
     * @Field
     */
    public function apiKey(User $user): ?string
    {
        return $user->getApiKey();
    }
    
    /**
     * @Field
     * @return \App\Models\SenderName[]
     */
    public function senderNames(User $user): array
    {
        return $this->senderNameRepository->findByUserId($user->getId());
    }

    /**
     * @Field
     * @return \App\Models\SMSOrder[]
     */
    public function smsOrders(User $user): array
    {
        return $this->smsOrderRepository->findByUserId($user->getId());
    }

    /**
     * @Field
     */
    public function orangeAPIConfig(User $user): ?\App\Models\OrangeAPIConfig
    {
        return $this->orangeAPIConfigRepository->findByUserId($user->getId());
    }
}