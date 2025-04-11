# Active Context - Feature: Send SMS to All Contacts

**Current Focus:** Implement the "Send SMS to All User Contacts" feature as per the User Story defined in the previous planning phase.

**Previous Context:**

- Completed GraphQL backend refactoring (Phases 1-4 + improvements).
- Resolved persistent frontend login issue:
  - Identified cause: Frontend query (`LOGIN` in `authStore.ts`) was inconsistent with the backend schema (`login` mutation returning `User`). Frontend expected `login { user { ... } }` but backend returned `User` directly. Also, frontend called a non-existent `checkAuth` query.
  - Initial workaround: Modified `AuthResolver::mutateLogin` to return a formatted array.
  - Final Solution: Corrected the `LOGIN` query in `authStore.ts` to match the schema. Removed the `checkAuth` query/function calls from frontend. Reverted `AuthResolver::mutateLogin` to return the `User` object directly (type hint `?User`). Removed the unnecessary formatter injection from `AuthResolver`. Login and redirection now work correctly via the browser.
- Frontend login flow is now working.
- Fixed GraphQL type mismatch issues in userStore.ts:
  - Identified cause: GraphQL queries/mutations in userStore.ts were using `Int!` type for ID parameters, but the backend schema expected `ID!` type (which is treated as a string).
  - Solution: Changed all GraphQL queries/mutations in userStore.ts to use `ID!` instead of `Int!` for ID parameters, and modified all methods to convert numeric IDs to strings using `id.toString()`.
- Implemented proper notification service with separation of concerns:
  - Created a new `NotificationService.ts` file with a `useNotification` composable that returns functions like `showSuccess`, `showError`, etc.
  - Modified stores (`senderNameStore.ts`, `smsOrderStore.ts`) to remove notification calls and return clear results.
  - Updated components to handle UI concerns like showing notifications based on the results returned by stores.
  - This follows the best practice of separating responsibilities: stores handle state and business logic, components handle UI concerns.
- Fixed validation messages persisting after successful SMS submission:
  - Identified cause: Form validation messages were still displayed after successful form submission because the validation reset was happening before Vue had finished updating the DOM.
  - Solution: Modified the `reset()` method in all SMS form components (`SingleSmsForm.vue`, `BulkSmsForm.vue`, `SegmentSmsForm.vue`, `AllContactsSmsForm.vue`) to use Vue's `nextTick()` function to ensure proper sequencing:
    1. Reset form data values
    2. Wait for Vue to update the DOM using `await nextTick()`
    3. Reset form validation with `formRef.value?.resetValidation()`
  - This ensures that validation is reset only after Vue has processed the data changes, preventing validation messages from persisting.

**User Story Summary:** As a logged-in user, I want to send a single SMS message to all my contacts at once, after checking for sufficient credits.

**Implementation Plan:**

1.  **Backend:**
    - Define new GraphQL mutation `sendSmsToAllContacts(message: String!): BulkSMSResult!` in `schema.graphql`.
    - Implement the corresponding resolver method `mutateSendSmsToAllContacts` in `SMSResolver.php`.
    - Add a new method (e.g., `sendToAllContacts`) to `SMSService.php` (or potentially `SMSBusinessService.php` if more appropriate) that:
      - Gets the current user ID (via injected `AuthService`).
      - Retrieves all contacts for that user (`ContactRepository::findByUserId`).
      - Extracts valid, unique phone numbers from the contacts.
      - Checks user credits against the number of contacts (`UserRepository`).
      - Throws an exception if credits are insufficient.
      - Calls `SMSService::sendBulkSMS` (or iterates `sendSMS`) with the list of numbers and the message.
      - Deducts credits based on the number of attempted/successful sends.
      - Returns a result compatible with `BulkSMSResult`.
    - Ensure necessary dependencies (`ContactRepository`, `UserRepository`, `AuthService`) are injected into the service handling the logic. Update DI configuration (`di.php`) if needed.
2.  **Frontend:**
    - Update the SMS sending view (`SMS.vue` or similar) to add an option (e.g., checkbox, dropdown item) for "Send to all contacts".
    - When selected, disable manual number input and potentially display the contact count/estimated cost.
    - Define the new GraphQL mutation string in the relevant store (likely `smsStore.ts` or `contactStore.ts`).
    - Implement an action in the store to call the `sendSmsToAllContacts` mutation.
    - Update the component's submit logic to call this new action when the "all contacts" option is selected.
    - Display the summary result (success/failure count) from the mutation response.
3.  **Testing:**
    - Backend: Add unit/integration tests for the new service method. Test the GraphQL mutation via `curl`.
    - Frontend: Test the UI option and the end-to-end flow manually or with Playwright.
4.  **Documentation:** Update user guide and technical documentation.

**Current Step:** Start backend implementation - Define the new mutation in `schema.graphql`.
