<?php

namespace App\GraphQL\Types;

use App\Models\ScheduledSMS;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * @Type(class=ScheduledSMS::class)
 */
class ScheduledSMSType
{
    /**
     * @Field
     */
    public function getId(ScheduledSMS $scheduledSMS): ID
    {
        return new ID($scheduledSMS->getId());
    }

    /**
     * @Field
     */
    public function getUserId(ScheduledSMS $scheduledSMS): ID
    {
        return new ID($scheduledSMS->getUserId());
    }

    /**
     * @Field
     */
    public function getName(ScheduledSMS $scheduledSMS): string
    {
        return $scheduledSMS->getName();
    }

    /**
     * @Field
     */
    public function getMessage(ScheduledSMS $scheduledSMS): string
    {
        return $scheduledSMS->getMessage();
    }

    /**
     * @Field
     */
    public function getSenderNameId(ScheduledSMS $scheduledSMS): ID
    {
        return new ID($scheduledSMS->getSenderNameId());
    }

    /**
     * @Field
     */
    public function getScheduledDate(ScheduledSMS $scheduledSMS): string
    {
        return $scheduledSMS->getScheduledDate();
    }

    /**
     * @Field
     */
    public function getStatus(ScheduledSMS $scheduledSMS): string
    {
        return $scheduledSMS->getStatus();
    }

    /**
     * @Field
     */
    public function isRecurring(ScheduledSMS $scheduledSMS): bool
    {
        return $scheduledSMS->isRecurring();
    }

    /**
     * @Field
     */
    public function getRecurrencePattern(ScheduledSMS $scheduledSMS): ?string
    {
        return $scheduledSMS->getRecurrencePattern();
    }

    /**
     * @Field
     */
    public function getRecurrenceConfig(ScheduledSMS $scheduledSMS): ?string
    {
        return $scheduledSMS->getRecurrenceConfig();
    }

    /**
     * @Field
     */
    public function getRecipientsType(ScheduledSMS $scheduledSMS): string
    {
        return $scheduledSMS->getRecipientsType();
    }

    /**
     * @Field
     */
    public function getRecipientsData(ScheduledSMS $scheduledSMS): string
    {
        return $scheduledSMS->getRecipientsData();
    }

    /**
     * @Field
     */
    public function getCreatedAt(ScheduledSMS $scheduledSMS): string
    {
        return $scheduledSMS->getCreatedAt();
    }

    /**
     * @Field
     */
    public function getUpdatedAt(ScheduledSMS $scheduledSMS): string
    {
        return $scheduledSMS->getUpdatedAt();
    }

    /**
     * @Field
     */
    public function getLastRunAt(ScheduledSMS $scheduledSMS): ?string
    {
        return $scheduledSMS->getLastRunAt();
    }

    /**
     * @Field
     */
    public function getNextRunAt(ScheduledSMS $scheduledSMS): ?string
    {
        return $scheduledSMS->getNextRunAt();
    }

    /**
     * @Field
     */
    public function getRecipientsCount(ScheduledSMS $scheduledSMS): int
    {
        $recipientsData = $scheduledSMS->getRecipientsDataAsArray();
        return count($recipientsData);
    }

    /**
     * @Field
     */
    public function getFormattedRecurrenceConfig(ScheduledSMS $scheduledSMS): ?string
    {
        if (!$scheduledSMS->isRecurring() || !$scheduledSMS->getRecurrencePattern()) {
            return null;
        }

        $pattern = $scheduledSMS->getRecurrencePattern();
        $config = $scheduledSMS->getRecurrenceConfigAsArray();

        if (!$config) {
            return null;
        }

        switch ($pattern) {
            case 'daily':
                $interval = $config['interval'] ?? 1;
                return $interval === 1 ? 'Tous les jours' : "Tous les $interval jours";
            case 'weekly':
                $interval = $config['interval'] ?? 1;
                return $interval === 1 ? 'Toutes les semaines' : "Toutes les $interval semaines";
            case 'monthly':
                $interval = $config['interval'] ?? 1;
                return $interval === 1 ? 'Tous les mois' : "Tous les $interval mois";
            case 'custom':
                return 'Configuration personnalisée';
            default:
                return null;
        }
    }
}
