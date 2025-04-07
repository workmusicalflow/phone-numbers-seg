<?php

namespace App\GraphQL\Types;

use App\Models\SMSOrder;
use App\Repositories\UserRepository;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * Type GraphQL pour les commandes de crédits SMS
 * 
 * @Type
 */
class SMSOrderType
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @Field
     */
    public function id(SMSOrder $smsOrder): int
    {
        return $smsOrder->getId();
    }

    /**
     * @Field
     */
    public function userId(SMSOrder $smsOrder): int
    {
        return $smsOrder->getUserId();
    }

    /**
     * @Field
     */
    public function quantity(SMSOrder $smsOrder): int
    {
        return $smsOrder->getQuantity();
    }

    /**
     * @Field
     */
    public function status(SMSOrder $smsOrder): string
    {
        return $smsOrder->getStatus();
    }

    /**
     * @Field
     */
    public function createdAt(SMSOrder $smsOrder): string
    {
        return $smsOrder->getCreatedAt();
    }

    /**
     * @Field
     */
    public function updatedAt(SMSOrder $smsOrder): ?string
    {
        return $smsOrder->getUpdatedAt();
    }

    /**
     * @Field
     */
    public function user(SMSOrder $smsOrder): ?\App\Models\User
    {
        return $this->userRepository->findById($smsOrder->getUserId());
    }

    /**
     * @Field
     */
    public function isPending(SMSOrder $smsOrder): bool
    {
        return $smsOrder->getStatus() === SMSOrder::STATUS_PENDING;
    }

    /**
     * @Field
     */
    public function isCompleted(SMSOrder $smsOrder): bool
    {
        return $smsOrder->getStatus() === SMSOrder::STATUS_COMPLETED;
    }

    /**
     * @Field
     */
    public function formattedStatus(SMSOrder $smsOrder): string
    {
        switch ($smsOrder->getStatus()) {
            case SMSOrder::STATUS_PENDING:
                return 'En attente';
            case SMSOrder::STATUS_COMPLETED:
                return 'Complétée';
            default:
                return $smsOrder->getStatus();
        }
    }
}
