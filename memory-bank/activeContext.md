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
- Update Memory Bank files (`systemPatterns.md`, `progress.md`) to reflect Phase 1 completion.
