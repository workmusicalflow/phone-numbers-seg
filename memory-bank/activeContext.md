# Active Context - Refactoring GraphQL Backend

**Current Focus:** Refactoring `public/graphql.php` to improve maintainability and organization, following recommendations from `scripts/utils/graphql_php_revue.md`.

**Phase 1: Resolver Extraction (Completed)**

- **Goal:** Move resolver logic from the large `$rootValue` array in `graphql.php` into dedicated resolver classes.
- **Actions Taken:**
  - Created directory `src/GraphQL/Resolvers/`.
  - Created classes: `UserResolver`, `ContactResolver`, `SMSResolver`, `AuthResolver`.
  - Moved corresponding resolver logic from `graphql.php` into methods within these classes.
  - Injected dependencies (Repositories, Logger, Services) into resolvers via constructors using the existing `DIContainer`.
  - Modified `graphql.php` to:
    - Instantiate resolvers via the DI container.
    - Use `Executor::setDefaultFieldResolver` with a mapping function to delegate field resolution to the appropriate resolver methods.
    - Removed the old `$rootValue` array.
- **Outcome:** `graphql.php` is significantly smaller. Resolver logic is now organized by domain/feature in separate classes.

**Next Steps:**

- Proceed to **Phase 2: Improve Dependency Injection and Authentication Handling**. This involves:

  - Creating/using an `AuthService`.
  - Removing direct `$_SESSION` access within resolvers, using the `AuthService` instead.
  - Centralizing authentication and authorization checks.
    **Phase 2: Improve Dependency Injection and Authentication Handling (Completed)**

- **Goal:** Remove direct `$_SESSION` access from resolvers and use a dedicated `AuthService`.
- **Actions Taken:**
  - Verified existence and functionality of `AuthServiceInterface` and `AuthService`.
  - Injected `AuthServiceInterface` into the constructors of `UserResolver`, `ContactResolver`, and `SMSResolver`.
  - Modified methods within these resolvers to use `$this->authService->getCurrentUser()` or `$this->authService->isAuthenticated()` instead of accessing `$_SESSION` directly.
  - Verified that `AuthResolver` already used `AuthService`.
  - Confirmed that the DI container (`src/config/di.php`) correctly configures `AuthService` and that autowiring handles resolver instantiation.
- **Outcome:** Direct dependency on `$_SESSION` has been removed from GraphQL resolvers, centralizing authentication logic within `AuthService`. This improves testability and separation of concerns.

**Next Steps:**

- Proceed to **Phase 3: Centralize Object-to-Array Conversion**. This involves:
  - Creating dedicated formatter/transformer classes or methods (`src/GraphQL/Formatters/GraphQLFormatterInterface.php`, `src/GraphQL/Formatters/GraphQLFormatterService.php`).
  - Removing the `format*` helper methods from individual resolvers (`UserResolver`, `ContactResolver`, `SMSResolver`).
  - Using the centralized formatter within resolvers to prepare data for GraphQL responses (`$this->formatter->formatUser()`, etc.).
- Update Memory Bank files (`systemPatterns.md`, `progress.md`) to reflect Phase 2 completion.

**Phase 3: Centralize Object-to-Array Conversion (Completed)**

- **Goal:** Remove `format*` helper methods from resolvers and use a dedicated service for converting models to arrays for GraphQL.
- **Actions Taken:**
  - Created `src/GraphQL/Formatters/GraphQLFormatterInterface.php` defining methods like `formatUser`, `formatContact`, `formatSmsHistory`, `formatCustomSegment`.
  - Created `src/GraphQL/Formatters/GraphQLFormatterService.php` implementing the interface and consolidating the formatting logic from the resolvers.
  - Updated `src/config/di.php` to register the new formatter service and interface.
  - Injected `GraphQLFormatterInterface` into `UserResolver`, `ContactResolver`, and `SMSResolver`.
  - Replaced calls to local `format*` methods with calls to the injected formatter service (e.g., `$this->formatter->formatUser($user)`).
  - Removed the private `format*` methods from `UserResolver`, `ContactResolver`, and `SMSResolver`.
- **Outcome:** Object-to-array conversion logic is now centralized in `GraphQLFormatterService`, making resolvers cleaner and the formatting logic reusable and easier to maintain.

**Next Steps:**

- Proceed to **Phase 4: Externalize Configuration**. This involves:
  - Moving hardcoded values (like API keys, default sender names/numbers in `SMSService` or `di.php`) into environment variables or a configuration file.
  - Injecting configuration values where needed instead of hardcoding them.
- Update Memory Bank files (`systemPatterns.md`, `progress.md`) to reflect Phase 3 completion.
