# User Management and API Configuration

This document describes the enhancements made to the user management system and API configuration.

## Database Schema Changes

The following changes have been made to the database schema:

1. Added `reset_token` column to the `users` table to support password reset functionality
2. Added `api_key` column to the `users` table to support API authentication
3. Added a unique constraint to the `orange_api_configs` table to ensure each user can have at most one configuration

## New Services

### SenderNameService

The `SenderNameService` provides functionality for managing sender names, including:

- Retrieving sender names for a user
- Requesting new sender names
- Approving or rejecting sender names
- Enforcing the limit of two approved sender names per user

#### Usage Example

```php
// Get an instance of the service from the DI container
$senderNameService = $container->get(\App\Services\SenderNameService::class);

// Check if a user can request a new sender name
if ($senderNameService->canRequestSenderName($userId)) {
    // Request a new sender name
    $senderName = $senderNameService->requestSenderName($userId, 'MyCompany');
}

// Get all sender names for a user
$senderNames = $senderNameService->getSenderNamesForUser($userId);

// Get approved sender names for a user
$approvedSenderNames = $senderNameService->getApprovedSenderNamesForUser($userId);

// Approve a sender name (admin only)
$senderNameService->approveSenderName($senderNameId);

// Reject a sender name (admin only)
$senderNameService->rejectSenderName($senderNameId);
```

### OrangeAPIConfigService

The `OrangeAPIConfigService` provides functionality for managing Orange API configurations, including:

- Retrieving configurations for a user
- Creating or updating configurations
- Enforcing that only admin users can modify configurations
- Ensuring each user can have at most one configuration

#### Usage Example

```php
// Get an instance of the service from the DI container
$orangeAPIConfigService = $container->get(\App\Services\OrangeAPIConfigService::class);

// Get the configuration for a user
$config = $orangeAPIConfigService->getConfigForUser($userId);

// Get the admin configuration
$adminConfig = $orangeAPIConfigService->getAdminConfig();

// Create or update a configuration for a user (admin only)
$config = $orangeAPIConfigService->createOrUpdateConfig(
    $userId,
    $clientId,
    $clientSecret,
    $currentUser // Must be an admin user
);

// Create or update the admin configuration (admin only)
$adminConfig = $orangeAPIConfigService->createOrUpdateAdminConfig(
    $clientId,
    $clientSecret,
    $currentUser // Must be an admin user
);

// Get the effective configuration for a user (user's config or admin config)
$effectiveConfig = $orangeAPIConfigService->getEffectiveConfigForUser($userId);
```

## GraphQL Formatters

The GraphQL formatter service has been updated to support formatting the new entities:

- `formatSenderName`: Formats a `SenderName` entity for GraphQL responses
- `formatOrangeAPIConfig`: Formats an `OrangeAPIConfig` entity for GraphQL responses

## Password Reset Flow

The password reset flow uses the `reset_token` column in the `users` table:

1. User requests a password reset by providing their email
2. System generates a reset token and stores it in the `reset_token` column
3. System sends an email to the user with a link containing the reset token
4. User clicks the link and is taken to a password reset form
5. User enters a new password
6. System verifies the reset token and updates the user's password
7. System clears the reset token

## API Authentication

The API authentication flow uses the `api_key` column in the `users` table:

1. User generates an API key through the user interface
2. System stores the API key in the `api_key` column
3. User includes the API key in API requests
4. System verifies the API key and authenticates the user
