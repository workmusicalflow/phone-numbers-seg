<?php

namespace App\GraphQL\Types;

use App\Models\ContactGroup;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * @Type(class=ContactGroup::class)
 */
class ContactGroupType
{
    /**
     * @Field
     */
    public function getId(ContactGroup $group): ID
    {
        return new ID($group->getId());
    }

    /**
     * @Field
     */
    public function getUserId(ContactGroup $group): ID
    {
        return new ID($group->getUserId());
    }

    /**
     * @Field
     */
    public function getName(ContactGroup $group): string
    {
        return $group->getName();
    }

    /**
     * @Field
     */
    public function getDescription(ContactGroup $group): ?string
    {
        return $group->getDescription();
    }

    /**
     * @Field
     */
    public function getCreatedAt(ContactGroup $group): string
    {
        return $group->getCreatedAt();
    }

    /**
     * @Field
     */
    public function getUpdatedAt(ContactGroup $group): string
    {
        return $group->getUpdatedAt();
    }

    /**
     * @Field
     */
    public function getContactCount(ContactGroup $group): int
    {
        // Cette méthode nécessite d'injecter le repository des memberships
        // Pour l'instant, on retourne 0
        // Dans une implémentation complète, on utiliserait le DI container
        return 0;
    }

    /**
     * @Field
     */
    public function getContacts(ContactGroup $group): array
    {
        // Cette méthode nécessite d'injecter le repository des contacts
        // Pour l'instant, on retourne un tableau vide
        // Dans une implémentation complète, on utiliserait le DI container
        return [];
    }
}
