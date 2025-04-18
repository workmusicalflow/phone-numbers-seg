<?php

namespace App\GraphQL\Formatters;

use App\Models\User;
use App\Models\Contact;
use App\Models\SMSHistory;
use App\Models\Segment;
use App\Models\CustomSegment as ModelsCustomSegment;
use App\Entities\CustomSegment as EntitiesCustomSegment;
use App\Models\ContactGroup; // Add ContactGroup model
use App\Models\ContactGroupMembership; // Add ContactGroupMembership model
use App\Repositories\CustomSegmentRepository;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * Service for formatting data models into arrays suitable for GraphQL responses.
 */
class GraphQLFormatterService implements GraphQLFormatterInterface
{
    private CustomSegmentRepository $customSegmentRepository;
    private LoggerInterface $logger;

    public function __construct(
        CustomSegmentRepository $customSegmentRepository, // Inject dependencies needed for formatting
        LoggerInterface $logger
    ) {
        $this->customSegmentRepository = $customSegmentRepository;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function formatUser(User $user): array
    {
        // Logic copied from UserResolver::formatUser
        return [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'smsCredit' => $user->getSmsCredit(),
            'smsLimit' => $user->getSmsLimit(),
            'isAdmin' => $user->isAdmin(),
            'createdAt' => $user->getCreatedAt(), // Ensure format is correct (e.g., ISO 8601)
            'updatedAt' => $user->getUpdatedAt(), // Ensure format is correct
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function formatContact(Contact $contact): array
    {
        // Logic copied from ContactResolver::formatContact
        return [
            'id' => $contact->getId(), // Ensure ID is returned as string if schema expects ID!
            'name' => $contact->getName(),
            'phoneNumber' => $contact->getPhoneNumber(),
            'email' => $contact->getEmail(),
            'notes' => $contact->getNotes(),
            'createdAt' => $contact->getCreatedAt(), // Ensure format is correct (e.g., ISO 8601)
            'updatedAt' => $contact->getUpdatedAt(), // Ensure format is correct
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function formatSmsHistory(SMSHistory $item): array
    {
        // Logic copied from SMSResolver::formatSmsHistory
        $smsData = [
            'id' => $item->getId(),
            'phoneNumber' => $item->getPhoneNumber(),
            'message' => $item->getMessage(),
            'status' => $item->getStatus(),
            'messageId' => $item->getMessageId(),
            'errorMessage' => $item->getErrorMessage(),
            'senderAddress' => $item->getSenderAddress(),
            'senderName' => $item->getSenderName(),
            'createdAt' => $item->getCreatedAt(), // Ensure format
            'userId' => $item->getUserId() // Include userId
        ];

        // Format segment info (copied logic)
        $smsData['segment'] = null; // Default to null
        if ($item->getSegmentId()) {
            try {
                // Use the injected repository
                $segment = $this->customSegmentRepository->findById($item->getSegmentId());
                if ($segment) {
                    // Use the formatCustomSegment method of this service
                    $smsData['segment'] = $this->formatCustomSegment($segment);
                }
            } catch (Exception $e) {
                $this->logger->warning('Could not fetch segment info for history item ' . $item->getId(), ['segmentId' => $item->getSegmentId(), 'error' => $e->getMessage()]);
                // Segment info remains null
            }
        }

        return $smsData;
    }

    /**
     * {@inheritdoc}
     */
    public function formatCustomSegment($segment, ?int $phoneNumberCount = null): array
    {
        // Logic adapted from SMSResolver::resolveSegmentsForSMS
        // Note: Changed type hint to CustomSegment to match interface
        // If other Segment types exist, add checks or separate methods.

        $formattedSegment = [
            'id' => $segment->getId(),
            'name' => $segment->getName(),
            // Add other common segment fields if applicable
        ];

        // Add description field if available
        $formattedSegment['description'] = $segment->getDescription();

        // Include phone number count if provided
        if ($phoneNumberCount !== null) {
            $formattedSegment['phoneNumberCount'] = $phoneNumberCount;
        }

        return $formattedSegment;
    }

    /**
     * {@inheritdoc}
     */
    public function formatContactGroup(ContactGroup $group, ?int $contactCount = null): array
    {
        $formattedGroup = [
            'id' => $group->getId(),
            'userId' => $group->getUserId(),
            'name' => $group->getName(),
            'description' => $group->getDescription(),
            'createdAt' => $group->getCreatedAt(),
            'updatedAt' => $group->getUpdatedAt(),
        ];

        if ($contactCount !== null) {
            $formattedGroup['contactCount'] = $contactCount;
        }

        return $formattedGroup;
    }

    /**
     * {@inheritdoc}
     */
    public function formatContactGroupMembership(ContactGroupMembership $membership, Contact $contact, ContactGroup $group): array
    {
        return [
            'id' => $membership->getId(),
            'contact' => $this->formatContact($contact),
            'group' => $this->formatContactGroup($group), // Reuse formatContactGroup
            'createdAt' => $membership->getCreatedAt(),
        ];
    }

    // Implement other format methods as needed...
}
