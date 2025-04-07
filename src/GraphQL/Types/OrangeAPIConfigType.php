<?php

namespace App\GraphQL\Types;

use App\Models\OrangeAPIConfig;
use App\Repositories\UserRepository;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * Type GraphQL pour les configurations de l'API Orange
 * 
 * @Type
 */
class OrangeAPIConfigType
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @Field
     */
    public function id(OrangeAPIConfig $orangeAPIConfig): int
    {
        return $orangeAPIConfig->getId();
    }

    /**
     * @Field
     */
    public function userId(OrangeAPIConfig $orangeAPIConfig): ?int
    {
        return $orangeAPIConfig->getUserId();
    }

    /**
     * @Field
     */
    public function clientId(OrangeAPIConfig $orangeAPIConfig): string
    {
        return $orangeAPIConfig->getClientId();
    }

    /**
     * @Field
     */
    public function isAdminConfig(OrangeAPIConfig $orangeAPIConfig): bool
    {
        return $orangeAPIConfig->isAdminConfig();
    }

    /**
     * @Field
     */
    public function createdAt(OrangeAPIConfig $orangeAPIConfig): string
    {
        return $orangeAPIConfig->getCreatedAt();
    }

    /**
     * @Field
     */
    public function updatedAt(OrangeAPIConfig $orangeAPIConfig): ?string
    {
        return $orangeAPIConfig->getUpdatedAt();
    }

    /**
     * @Field
     */
    public function user(OrangeAPIConfig $orangeAPIConfig): ?\App\Models\User
    {
        $userId = $orangeAPIConfig->getUserId();
        if ($userId === null) {
            return null;
        }
        return $this->userRepository->findById($userId);
    }

    /**
     * @Field
     */
    public function maskedClientSecret(OrangeAPIConfig $orangeAPIConfig): string
    {
        $secret = $orangeAPIConfig->getClientSecret();
        if (strlen($secret) <= 8) {
            return str_repeat('*', strlen($secret));
        }
        return substr($secret, 0, 4) . str_repeat('*', strlen($secret) - 8) . substr($secret, -4);
    }
}
