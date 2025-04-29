# GraphQL Implementation Plan: SMS History and Score per Contact

## Issue

Currently, the GraphQL API allows fetching the global SMS history (`smsHistory` query) and contact information (`contacts` query). However, it is not possible to directly obtain the history of SMS sent to a specific contact, nor to get aggregated statistics (total count, successful, failed) or a "quality" score for that contact based on these statistics via a single GraphQL query on the `Contact` type.

This limitation makes it difficult to display relevant information about a contact's SMS activity in the user interface or to integrate with other systems requiring this data at the contact level.

## Proposed Solution

To address this issue, we will enhance the `Contact` type in the GraphQL schema by adding new fields that will allow direct access to the SMS history related to that contact, as well as the associated statistics and score.

## Detailed Implementation Plan

### Step 1: Modify the GraphQL Schema (`src/GraphQL/schema.graphql`)

We will add the following fields to the `Contact` type:

```graphql
type Contact {
  id: ID!
  name: String!
  phoneNumber: String!
  email: String
  notes: String
  createdAt: String!
  updatedAt: String!
  groups: [ContactGroup!] # Existing field
  # New fields for SMS history and score
  smsHistory: [SMSHistory!] # List of SMS sent to this number
  smsTotalCount: Int! # Total number of SMS sent to this number
  smsSentCount: Int! # Number of SENT SMS to this number
  smsFailedCount: Int! # Number of FAILED SMS to this number
  smsScore: Float! # Score based on the SENT / Total ratio
}
```

### Step 2: Update the SMS History Repository (`src/Repositories/Interfaces/SMSHistoryRepositoryInterface.php` and `src/Repositories/Doctrine/SMSHistoryRepository.php`)

To allow the contact resolver to efficiently access SMS history data filtered by phone number, we will add new methods to the `SMSHistoryRepository`.

In `src/Repositories/Interfaces/SMSHistoryRepositoryInterface.php`, add the declarations:

```php
// ... other methods

/**
 * Finds SMS history entries by phone number.
 *
 * @param string $phoneNumber The phone number to filter by.
 * @return SMSHistory[]
 */
public function findByPhoneNumber(string $phoneNumber): array;

/**
 * Counts SMS history entries by phone number.
 *
 * @param string $phoneNumber The phone number to filter by.
 * @return int
 */
public function countByPhoneNumber(string $phoneNumber): int;

/**
 * Counts SMS history entries by phone number and status.
 *
 * @param string $phoneNumber The phone number to filter by.
 * @param string $status The status to filter by (e.g., 'SENT', 'FAILED').
 * @return int
 */
public function countByPhoneNumberAndStatus(string $phoneNumber, string $status): int;
```

In `src/Repositories/Doctrine/SMSHistoryRepository.php`, implement these methods using Doctrine ORM to query the `sms_history` database, filtering by the `phoneNumber` column.

### Step 3: Update the Contact Resolver (`src/GraphQL/Resolvers/ContactResolver.php`)

We will add resolver methods for the new fields of the `Contact` type. These methods will be responsible for retrieving the necessary data using the injected `SMSHistoryRepository`.

```php
<?php

namespace App\GraphQL\Resolvers;

use App\Entities\Contact;
use App\Repositories\Interfaces\SMSHistoryRepositoryInterface;
use GraphQL\Type\Definition\ResolveInfo;

class ContactResolver
{
    private SMSHistoryRepositoryInterface $smsHistoryRepository;

    public function __construct(SMSHistoryRepositoryInterface $smsHistoryRepository)
    {
        $this->smsHistoryRepository = $smsHistoryRepository;
    }

    // ... other resolver methods for Contact

    /**
     * Resolver for the smsHistory field on the Contact type.
     *
     * @param Contact $contact The parent Contact object.
     * @param array $args Arguments passed to the field (none expected here).
     * @param mixed $context The context.
     * @param ResolveInfo $info The resolve info.
     * @return array
     */
    public function resolveSmsHistory(Contact $contact, array $args, $context, ResolveInfo $info): array
    {
        // Assuming the SMSHistory entity has a 'phoneNumber' property matching the Contact's phone number
        return $this->smsHistoryRepository->findByPhoneNumber($contact->getPhoneNumber());
    }

    /**
     * Resolver for the smsTotalCount field on the Contact type.
     *
     * @param Contact $contact The parent Contact object.
     * @return int
     */
    public function resolveSmsTotalCount(Contact $contact): int
    {
        return $this->smsHistoryRepository->countByPhoneNumber($contact->getPhoneNumber());
    }

    /**
     * Resolver for the smsSentCount field on the Contact type.
     *
     * @param Contact $contact The parent Contact object.
     * @return int
     */
    public function resolveSmsSentCount(Contact $contact): int
    {
        // Assuming 'SENT' is the status string for successfully sent messages
        return $this->smsHistoryRepository->countByPhoneNumberAndStatus($contact->getPhoneNumber(), 'SENT');
    }

    /**
     * Resolver for the smsFailedCount field on the Contact type.
     *
     * @param Contact $contact The parent Contact object.
     * @return int
     */
    public function resolveSmsFailedCount(Contact $contact): int
    {
        // Assuming 'FAILED' is the status string for failed messages
        return $this->smsHistoryRepository->countByPhoneNumberAndStatus($contact->getPhoneNumber(), 'FAILED');
    }

    /**
     * Resolver for the smsScore field on the Contact type.
     *
     * @param Contact $contact The parent Contact object.
     * @return float
     */
    public function resolveSmsScore(Contact $contact): float
    {
        $total = $this->smsHistoryRepository->countByPhoneNumber($contact->getPhoneNumber());
        $sent = $this->smsHistoryRepository->countByPhoneNumberAndStatus($contact->getPhoneNumber(), 'SENT');

        if ($total === 0) {
            return 0.0; // Avoid division by zero if no SMS were sent
        }

        // Calculate score as SENT / Total, rounded to two decimal places
        return round($sent / $total, 2);
    }
}
```

### Step 4: Update Dependency Injection (DI) Configuration

In the DI configuration file (likely `src/config/di/graphql.php` or `src/config/di.php`), ensure that `SMSHistoryRepositoryInterface` is correctly defined and injected into the `ContactResolver`'s constructor.

```php
// Example DI configuration (adapt according to the actual structure)
use App\GraphQL\Resolvers\ContactResolver;
use App\Repositories\Interfaces\SMSHistoryRepositoryInterface;
use App\Repositories\Doctrine\SMSHistoryRepository; // If using Doctrine

return [
    // ... other definitions

    ContactResolver::class => factory(function (\DI\ContainerInterface $container) {
        return new ContactResolver(
            $container->get(SMSHistoryRepositoryInterface::class)
            // ... other dependencies if needed
        );
    }),

    SMSHistoryRepositoryInterface::class => \DI\autowire(SMSHistoryRepository::class), // If using Doctrine
    // Or if using the specific PDO repository for SMS history:
    // SMSHistoryRepositoryInterface::class => \DI\autowire(\App\Repositories\SMSHistoryRepository::class), // Adapt namespace if necessary
];
```

### Step 5: Testing

- Write or update unit tests for the `ContactResolver` to verify that the new methods correctly call the repository and calculate the score.
- Write GraphQL integration tests to ensure that queries on the `Contact` type with the new fields work as expected and return the correct data.

## Score Calculation

The score is defined as the ratio of the number of successfully delivered SMS (`SENT`) to the total number of SMS sent to that contact.

Formula: `Score = (Number of SENT SMS) / (Total Number of SMS)`

The score will be a decimal number between 0.0 and 1.0. A score of 1.0 indicates that all SMS sent to this contact were successful, while a score of 0.0 indicates that no SMS were successful (or that no SMS were sent).

This score can serve as an indicator of the "quality" of the phone number, particularly for identifying malformed or invalid numbers that lead to sending failures.

This document describes the detailed plan for implementing the requested features. The implementation will follow these steps.
