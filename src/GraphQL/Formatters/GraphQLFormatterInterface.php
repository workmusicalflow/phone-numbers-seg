<?php

namespace App\GraphQL\Formatters;

use App\Models\User;
use App\Models\Contact;
use App\Models\SMSHistory;
use App\Models\CustomSegment; // Use CustomSegment specifically if that's what's formatted
use App\Models\ContactGroup;
use App\Models\ContactGroupMembership;

/**
 * Interface for formatting data models into arrays suitable for GraphQL responses.
 */
interface GraphQLFormatterInterface
{
    /**
     * Formats a User object.
     *
     * @param User $user
     * @return array<string, mixed>
     */
    public function formatUser(User $user): array;

    /**
     * Formats a Contact object.
     *
     * @param Contact $contact
     * @return array<string, mixed>
     */
    public function formatContact(Contact $contact): array;

    /**
     * Formats an SMSHistory object.
     *
     * @param SMSHistory $history
     * @return array<string, mixed>
     */
    public function formatSmsHistory(SMSHistory $history): array;

    /**
     * Formats a CustomSegment object.
     * Add other necessary model formatters here.
     *
     * @param CustomSegment $segment
     * @param int|null $phoneNumberCount Optional count of phone numbers in the segment
     * @return array<string, mixed>
     */
    public function formatCustomSegment(CustomSegment $segment, ?int $phoneNumberCount = null): array;

    /**
     * Formats a ContactGroup model into an array for GraphQL.
     *
     * @param ContactGroup $group The contact group model instance.
     * @param int|null $contactCount Optional count of contacts in the group.
     * @return array<string, mixed> The formatted contact group data.
     */
    public function formatContactGroup(ContactGroup $group, ?int $contactCount = null): array;

    /**
     * Formats a ContactGroupMembership model into an array for GraphQL.
     *
     * @param ContactGroupMembership $membership The membership model instance.
     * @param Contact $contact The associated contact model.
     * @param ContactGroup $group The associated group model.
     * @return array<string, mixed> The formatted membership data.
     */
    public function formatContactGroupMembership(ContactGroupMembership $membership, Contact $contact, ContactGroup $group): array;

    // Add other format methods as needed for other models (SMSResult, BulkSMSResult, etc.)
}
