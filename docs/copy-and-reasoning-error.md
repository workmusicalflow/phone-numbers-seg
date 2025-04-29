contactStore.ts:341 Erreur lors de la récupération des contacts: Error: La réponse du serveur ne contient pas les données attendues
at fetchContacts (contactStore.ts:295:15)
fetchContacts @ contactStore.ts:341
await in fetchContacts
setSorting @ contactStore.ts:588
onRequest @ Contacts.vue:234
set @ ContactTable.vue:171
\_createVNode.onUpdate:pagination.\_cache.<computed>.\_cache.<computed> @ ContactTable.vue:13

contactStore.ts:294 Invalid GraphQL response format:
{data: undefined, loading: false, networkStatus: 8, errors: Array(1)}
fetchContacts @ contactStore.ts:294
await in fetchContacts
(anonymous) @ Contacts.vue:363

contactStore.ts:294 Invalid GraphQL response format:
{data: undefined, loading: false, networkStatus: 8, errors: Array(1)}

contactStore.ts:341 Erreur lors de la récupération des contacts: Error: La réponse du serveur ne contient pas les données attendues
at Proxy.fetchContacts (contactStore.ts:295:15)
at async Contacts.vue:363:3
fetchContacts @ contactStore.ts:341
await in fetchContacts
(anonymous) @ Contacts.vue:363

[29-Apr-2025 03:03:31 UTC] PHP Deprecated: Optional parameter $phoneNumberRepository declared before required parameter $logger is implicitly treated as a required parameter in /Users/ns2poportable/Desktop/phone-numbers-seg/src/Services/SMSService.php on line 46
[29-Apr-2025 03:03:31 UTC] PHP Stack trace:
[29-Apr-2025 03:03:31 UTC] PHP 1. {main}() /Users/ns2poportable/Desktop/phone-numbers-seg/public/graphql.php:0
[29-Apr-2025 03:03:31 UTC] PHP 2. App\GraphQL\DIContainer->get($id = 'App\\GraphQL\\Resolvers\\SMSResolver') /Users/ns2poportable/Desktop/phone-numbers-seg/public/graphql.php:170
[29-Apr-2025 03:03:31 UTC] PHP 3. DI\Container->get($id = 'App\\GraphQL\\Resolvers\\SMSResolver') /Users/ns2poportable/Desktop/phone-numbers-seg/src/GraphQL/DIContainer.php:54
fetchContacts @ contactStore.ts:294
await in fetchContacts
(anonymous) @ Contacts.vue:363

The user feedback indicates the error Cannot return null for non-nullable field "Contact.smsTotalCount" still occurs, despite the previous fixes in the resolver. The added logging in ContactResolver::resolveSmsTotalCount should now provide more insight.

I need to examine the application logs to see the output of the new logging statements, specifically the raw value returned by the repository and the final value returned by the resolver just before the error happens. This will help determine if the issue lies in the value returned by the repository or if something happens after the resolver returns the value.

The next step is to check the logs for the detailed messages I added in the previous step. These logs should show the contact ID, phone number, the raw result from the repository, and the final count returned by the resolveSmsTotalCount method, or any Throwable caught.

This indicates that the logger configuration might be filtering out DEBUG level messages, or there's another issue preventing these specific logs from being written. The PHP Deprecated warning seen earlier might also hint at configuration issues, although it seemed unrelated.

Since the logs aren't providing the necessary details, and the code seems logically correct, the next most likely culprit is an interaction issue within the GraphQL library (webonyx/graphql-php) or Doctrine, possibly triggered by the specific way data is fetched or processed for the contacts list.

The error trace consistently points to ReferenceExecutor::completeValue failing when processing smsTotalCount. This happens after the field resolver (resolveSmsTotalCount) is called. The resolver returns a value (which we've tried hard to ensure is an int), but somewhere between that return and the final assembly of the response, the value becomes null for the executor.

One possibility is related to how GraphQL handles field resolution for lists. If any field resolver within the Contact type throws an uncaught error or returns an invalid value for one contact in the list, it might cause the entire parent field (contacts) or parts of it to become null, potentially leading to this error if the schema expects non-nullable items.

Let's examine the other field resolvers within ContactResolver that are called for each contact:

resolveContactGroups: Uses DataLoader, seems okay, returns [] on error.
resolveSmsHistory: Fetches history, returns [] on error.
resolveSmsTotalCount: We've modified this extensively, returns 0 on error.
resolveSmsSentCount: Returns 0 on error.
resolveSmsFailedCount: Returns 0 on error.
resolveSmsScore: Returns 0.0 on error.
All these resolvers seem to have basic error handling that returns a default value compatible with the schema type (or an empty array).

Hypothesis: Could the issue be related to the formatter? The resolveContacts method uses $this->formatter->formatContact($contact) to convert the Contact entity into an array before GraphQL processes its fields. If formatContact somehow returns an array where phoneNumber is missing or null for a specific contact, the resolveSmsTotalCount would correctly return 0 based on the empty() check, but maybe the issue lies in how the GraphQL executor handles the overall formatted array structure later.

Let's check the formatter implementation: src/GraphQL/Formatters/GraphQLFormatterService.php. I need to see how formatContact works and if it could potentially omit phoneNumber.
