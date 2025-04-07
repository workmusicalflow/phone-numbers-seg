<?php

namespace App\GraphQL\Types;

use App\Models\Contact;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * @Type(class=Contact::class)
 */
class ContactType
{
    /**
     * @Field
     */
    public function getId(Contact $contact): ID
    {
        return new ID($contact->getId());
    }

    /**
     * @Field
     */
    public function getUserId(Contact $contact): ID
    {
        return new ID($contact->getUserId());
    }

    /**
     * @Field
     */
    public function getName(Contact $contact): string
    {
        return $contact->getName();
    }

    /**
     * @Field
     */
    public function getPhoneNumber(Contact $contact): string
    {
        return $contact->getPhoneNumber();
    }

    /**
     * @Field
     */
    public function getEmail(Contact $contact): ?string
    {
        return $contact->getEmail();
    }

    /**
     * @Field
     */
    public function getNotes(Contact $contact): ?string
    {
        return $contact->getNotes();
    }

    /**
     * @Field
     */
    public function getCreatedAt(Contact $contact): string
    {
        return $contact->getCreatedAt();
    }

    /**
     * @Field
     */
    public function getUpdatedAt(Contact $contact): string
    {
        return $contact->getUpdatedAt();
    }

    /**
     * @Field
     */
    public function getGroups(Contact $contact): array
    {
        // Cette méthode nécessite d'injecter le repository des groupes
        // Pour l'instant, on retourne un tableau vide
        // Dans une implémentation complète, on utiliserait le DI container
        return [];
    }
}
