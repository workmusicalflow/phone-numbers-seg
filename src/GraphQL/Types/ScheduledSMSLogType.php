<?php

namespace App\GraphQL\Types;

use App\Models\ScheduledSMSLog;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * @Type(class=ScheduledSMSLog::class)
 */
class ScheduledSMSLogType
{
    /**
     * @Field
     */
    public function getId(ScheduledSMSLog $log): ID
    {
        return new ID($log->getId());
    }

    /**
     * @Field
     */
    public function getScheduledSmsId(ScheduledSMSLog $log): ID
    {
        return new ID($log->getScheduledSmsId());
    }

    /**
     * @Field
     */
    public function getExecutionDate(ScheduledSMSLog $log): string
    {
        return $log->getExecutionDate();
    }

    /**
     * @Field
     */
    public function getStatus(ScheduledSMSLog $log): string
    {
        return $log->getStatus();
    }

    /**
     * @Field
     */
    public function getTotalRecipients(ScheduledSMSLog $log): int
    {
        return $log->getTotalRecipients();
    }

    /**
     * @Field
     */
    public function getSuccessfulSends(ScheduledSMSLog $log): int
    {
        return $log->getSuccessfulSends();
    }

    /**
     * @Field
     */
    public function getFailedSends(ScheduledSMSLog $log): int
    {
        return $log->getFailedSends();
    }

    /**
     * @Field
     */
    public function getErrorDetails(ScheduledSMSLog $log): ?string
    {
        return $log->getErrorDetails();
    }

    /**
     * @Field
     */
    public function getCreatedAt(ScheduledSMSLog $log): string
    {
        return $log->getCreatedAt();
    }

    /**
     * @Field
     */
    public function getSuccessRate(ScheduledSMSLog $log): float
    {
        return $log->getSuccessRate();
    }

    /**
     * @Field
     */
    public function isFullySuccessful(ScheduledSMSLog $log): bool
    {
        return $log->isFullySuccessful();
    }

    /**
     * @Field
     */
    public function isPartiallySuccessful(ScheduledSMSLog $log): bool
    {
        return $log->isPartiallySuccessful();
    }

    /**
     * @Field
     */
    public function isFailed(ScheduledSMSLog $log): bool
    {
        return $log->isFailed();
    }

    /**
     * @Field
     */
    public function getStatusLabel(ScheduledSMSLog $log): string
    {
        if ($log->isFullySuccessful()) {
            return 'Succès';
        } elseif ($log->isPartiallySuccessful()) {
            return 'Succès partiel';
        } else {
            return 'Échec';
        }
    }

    /**
     * @Field
     */
    public function getStatusColor(ScheduledSMSLog $log): string
    {
        if ($log->isFullySuccessful()) {
            return 'positive';
        } elseif ($log->isPartiallySuccessful()) {
            return 'warning';
        } else {
            return 'negative';
        }
    }
}
