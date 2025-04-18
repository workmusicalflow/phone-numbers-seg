# URL Constants Usage Guide

## Overview

This document describes the URL constants system implemented in the Oracle application. The system centralizes all URLs used throughout the application, making it easier to maintain and update them.

## Why Use URL Constants?

- **Maintainability**: When URLs need to change, they can be updated in a single location
- **Consistency**: Ensures the same URL is used everywhere it's needed
- **Environment Awareness**: URLs can adapt to different environments (development, production, etc.)
- **Type Safety**: In TypeScript, provides autocompletion and type checking
- **Documentation**: URLs are documented in the code, making it clear what each one is for

## Backend Usage (PHP)

### Configuration

URLs for the backend are defined in `src/config/urls.php` in the `App\Config\UrlConfig` class.

### Basic Usage

```php
use App\Config\UrlConfig;

// Get the GraphQL endpoint
$graphqlEndpoint = UrlConfig::getGraphqlEndpoint();

// Get the Orange API URL
$orangeApiUrl = UrlConfig::getOrangeApiUrl();
```

### Environment Variables

Backend URLs can be configured using environment variables in the `.env` file:

```
API_BASE_URL=https://api.example.com
GRAPHQL_ENDPOINT=/graphql
ORANGE_API_URL=https://api.orange.com
```

If an environment variable is not set, a default value will be used.

## Frontend Usage (TypeScript)

### Configuration

URLs for the frontend are defined in `frontend/src/config/urls.ts` and are organized into three categories:

- `API`: API endpoints
- `ROUTES`: Frontend routes
- `EXTERNAL`: External service URLs

### Basic Usage

```typescript
import { API, ROUTES, EXTERNAL } from "@/config/urls";

// API endpoints
const graphqlEndpoint = API.GRAPHQL;
const sendSmsEndpoint = API.SMS.SEND();

// Frontend routes
const dashboardRoute = ROUTES.DASHBOARD;
const userDetailsRoute = ROUTES.USER_DETAILS("123");

// External URLs
const orangeApiUrl = EXTERNAL.ORANGE_API;
```

### Environment Variables

Frontend URLs can be configured using environment variables in the `.env` file:

```
VITE_API_BASE_URL=https://api.example.com
VITE_GRAPHQL_ENDPOINT=/graphql
VITE_ORANGE_API_URL=https://api.orange.com
```

These variables must be prefixed with `VITE_` to be accessible in the frontend code.

## Adding New URLs

### Backend

1. Add a new method to the `UrlConfig` class in `src/config/urls.php`:

```php
/**
 * Get the new endpoint URL
 *
 * @return string The new endpoint URL
 */
public static function getNewEndpointUrl(): string
{
    return $_ENV['NEW_ENDPOINT_URL'] ?? 'http://default-url.com';
}
```

2. Use the new method where needed:

```php
$newEndpointUrl = UrlConfig::getNewEndpointUrl();
```

### Frontend

1. Add a new constant to the appropriate object in `frontend/src/config/urls.ts`:

```typescript
export const API = {
  // Existing constants...

  /**
   * New endpoint
   */
  NEW_ENDPOINT: getEnvironmentVariable(
    "NEW_ENDPOINT_URL",
    "http://default-url.com"
  ),
};
```

2. Use the new constant where needed:

```typescript
import { API } from "@/config/urls";

const newEndpointUrl = API.NEW_ENDPOINT;
```

## Best Practices

1. **Always use constants**: Never hardcode URLs in your code
2. **Document constants**: Add JSDoc or PHPDoc comments to explain what each URL is for
3. **Use environment variables**: Configure URLs using environment variables for different environments
4. **Group related URLs**: Organize URLs into logical groups
5. **Use functions for dynamic URLs**: For URLs that include parameters, use functions that accept those parameters

## Migration Guide

When migrating existing code to use URL constants:

1. Identify hardcoded URLs in the codebase
2. Check if a constant already exists for that URL
3. If not, add a new constant to the appropriate file
4. Replace the hardcoded URL with the constant
5. Test the application to ensure the URL still works

## Troubleshooting

### URL is undefined or empty

- Check if the environment variable is set correctly
- Verify that the constant is imported and used correctly
- Check for typos in the constant name

### URL is incorrect

- Check the definition of the constant in the configuration file
- Verify that the environment variable has the correct value
- Ensure that any parameters are passed correctly to URL functions
