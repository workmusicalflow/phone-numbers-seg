import { ref, computed } from 'vue';
import { useQuasar } from 'quasar';
import { apolloClient, gql } from '../services/api'; // Import directly from api service
import { useUserStore } from '../stores/userStore';

// --- Define Interfaces ---
interface HistoryItem {
  id: string;
  phoneNumber: string;
  message: string;
  status: 'SENT' | 'FAILED' | 'PENDING'; // Backend status for history items
  createdAt: string;
}

interface Segment {
  id: number;
  name: string;
  description?: string;
  phoneNumberCount: number;
}

interface SmsResultSummary {
  total: number;
  successful: number;
  failed: number;
}

// Frontend status types for consistent UI handling
type FrontendStatus = 'success' | 'warning' | 'error';

interface BaseSmsResult {
  status: FrontendStatus; // Standardized frontend status
  originalStatus?: string; // Original backend status if needed
  message?: string; // Backend message or derived message
}

interface SingleSmsResult extends BaseSmsResult {
  id?: string;
  phoneNumber?: string;
  createdAt?: string;
}

interface BulkSmsResult extends BaseSmsResult {
  summary: SmsResultSummary;
  results?: { phoneNumber: string; status: string; message?: string }[]; // Individual results if provided by backend
}

interface SegmentSmsResult extends BulkSmsResult {
  segment?: { id: number; name: string };
}

// Combined type for the reactive ref
type CombinedSmsResult = SingleSmsResult | BulkSmsResult | SegmentSmsResult | null;

// Interfaces for raw backend mutation responses (useful for typing data before mapping)
interface RawSingleSmsResponse {
    id: string;
    status: 'SENT' | 'FAILED' | string; // Allow other backend statuses
}

interface RawBulkSmsResponse {
    status: string;
    message?: string;
    summary: SmsResultSummary;
    results?: { phoneNumber: string; status: string; message?: string }[];
}

interface RawSegmentSmsResponse extends RawBulkSmsResponse {
    segment?: { id: number; name: string };
}

// --- GraphQL Operations ---
const GET_SMS_HISTORY = gql`
  query GetSmsHistory($userId: ID) {
    smsHistory(userId: $userId, limit: 10, offset: 0) {
      id
      phoneNumber
      message
      status
      createdAt
    }
  }
`;

const GET_SEGMENTS_FOR_SMS = gql`
  query GetSegmentsForSMS {
    segmentsForSMS {
      id
      name
      description
      phoneNumberCount
    }
  }
`;

const SEND_SINGLE_SMS = gql`
  mutation SendSms($phoneNumber: String!, $message: String!, $userId: ID = null) {
    sendSms(phoneNumber: $phoneNumber, message: $message, userId: $userId) {
      id
      status # Expect 'SENT' or 'FAILED' primarily
    }
  }
`;

const SEND_BULK_SMS = gql`
  mutation SendBulkSms($phoneNumbers: [String!]!, $message: String!, $userId: ID = null) {
    sendBulkSms(phoneNumbers: $phoneNumbers, message: $message, userId: $userId) {
      status # e.g., 'SENT', 'PARTIAL', 'FAILED', 'ERROR'
      message
      summary { total successful failed }
      # results { phoneNumber status message } # Optionally include if needed
    }
  }
`;

const SEND_SMS_TO_SEGMENT = gql`
  mutation SendSmsToSegment($segmentId: ID!, $message: String!, $userId: ID = null) {
    sendSmsToSegment(segmentId: $segmentId, message: $message, userId: $userId) {
      status # e.g., 'SENT', 'PARTIAL', 'FAILED', 'ERROR'
      message
      segment { id name }
      summary { total successful failed }
      # results { phoneNumber status message } # Optionally include if needed
    }
  }
`;

const SEND_SMS_TO_ALL_CONTACTS = gql`
  mutation SendSmsToAllContacts($message: String!) {
    sendSmsToAllContacts(message: $message) {
      status # e.g., 'SENT', 'PARTIAL', 'FAILED', 'ERROR'
      message
      summary { total successful failed }
      # results { phoneNumber status message } # Optionally include if needed
    }
  }
`;


// --- Composable ---
export function useSmsSender() {
  const $q = useQuasar();
  const userStore = useUserStore();

  // --- State ---
  const loading = ref(false);
  const loadingHistory = ref(false);
  const loadingSegments = ref(false);
  const smsHistory = ref<HistoryItem[]>([]);
  const segments = ref<Segment[]>([]);
  const smsResult = ref<CombinedSmsResult>(null); // Holds the mapped result

  const getUserId = (): number | undefined => userStore.currentUser?.id;
  const getUserIdForGraphQL = (): string | null => getUserId()?.toString() ?? null;

  // --- Computed ---
  const hasInsufficientCredits = computed(() => userStore.currentUser ? userStore.currentUser.smsCredit <= 0 : true);

  // --- Methods ---
  const notify = (type: 'positive' | 'negative' | 'warning', message: string) => {
    console.log(`[${type}] ${message}`);
    if ($q?.notify) {
      try {
        $q.notify({ 
          type, 
          message, 
          multiLine: message.length > 50, // Example: Use multiLine for longer messages
          html: true // Allow basic HTML if needed for formatting
        });
      } catch (error) {
        console.error('Error showing Quasar notification:', error);
      }
    }
  };

  const handleError = (error: unknown, contextMessage: string) => {
    console.error(`${contextMessage}:`, error);
    let specificMessage = contextMessage;
    let isCreditError = false;

    // Try to get a more specific message from GraphQL errors
    if (error && typeof error === 'object') {
        if ('graphQLErrors' in error && Array.isArray(error.graphQLErrors) && error.graphQLErrors.length > 0) {
            // Use the first GraphQL error message
            specificMessage = error.graphQLErrors[0]?.message || specificMessage;
        } else if ('networkError' in error && error.networkError) {
             specificMessage = (error.networkError as Error).message || 'Erreur réseau';
        } else if ('message' in error) {
            specificMessage = (error as Error).message || specificMessage;
        }
    } else {
        specificMessage = String(error) || specificMessage;
    }


    isCreditError = specificMessage.includes('Crédits SMS insuffisants');
    const displayMessage = isCreditError ? 'Crédits SMS insuffisants.' : specificMessage;

    smsResult.value = {
      status: 'error',
      originalStatus: 'ERROR', // Indicate it came from a catch block
      message: displayMessage,
    };

    notify('negative', displayMessage);

    if (isCreditError) {
      const userId = getUserId();
      if (userId !== undefined) {
        userStore.fetchUser(userId).catch(e => console.error("Failed to refresh user after credit error:", e));
      }
    }
  };

  const fetchSmsHistory = async () => {
    const userId = getUserIdForGraphQL();
    if (!userId) return; // No need to fetch if no user logged in

    loadingHistory.value = true;
    try {
      const { data } = await apolloClient.query<{ smsHistory: HistoryItem[] }>({
        query: GET_SMS_HISTORY,
        variables: { userId },
        fetchPolicy: "network-only",
      });
      smsHistory.value = data?.smsHistory || [];
    } catch (error) {
      console.error("Error fetching SMS history:", error);
    } finally {
      loadingHistory.value = false;
    }
  };

  const fetchSegments = async () => {
    loadingSegments.value = true;
    try {
      const { data } = await apolloClient.query<{ segmentsForSMS: Segment[] }>({
        query: GET_SEGMENTS_FOR_SMS,
        fetchPolicy: "network-only",
      });
      segments.value = data?.segmentsForSMS || [];
    } catch (error) {
      console.error("Error fetching segments:", error);
    } finally {
      loadingSegments.value = false;
    }
  };

  // --- Send Actions ---

  // Helper to refresh user credits and history after successful/partial sends
  const refreshUserDataAndHistory = async () => {
    const userId = getUserId();
    const promises = [];
    if (userId !== undefined) {
        promises.push(userStore.fetchUser(userId).catch(e => console.error("Failed to refresh user:", e)));
    }
    promises.push(fetchSmsHistory().catch(e => console.error("Failed to refresh history:", e)));
    await Promise.all(promises);
  };

  const sendSingleSms = async (payload: { phoneNumber: string; message: string }): Promise<SingleSmsResult | null> => {
    loading.value = true;
    smsResult.value = null;

    try {
      const { data } = await apolloClient.mutate<{ sendSms: RawSingleSmsResponse }>({
        mutation: SEND_SINGLE_SMS,
        variables: { ...payload, userId: getUserIdForGraphQL() },
      });

      if (!data?.sendSms) throw new Error("Aucune donnée retournée par le serveur pour sendSms");

      const resultData = data.sendSms;
      const isSuccess = resultData.status === 'SENT';

      const mappedResult: SingleSmsResult = {
        status: isSuccess ? 'success' : 'error',
        originalStatus: resultData.status,
        message: isSuccess ? 'SMS envoyé avec succès.' : 'Échec de l\'envoi du SMS.',
        id: resultData.id,
        phoneNumber: payload.phoneNumber, // Include context
        createdAt: new Date().toISOString() // Add timestamp of operation completion
      };
      smsResult.value = mappedResult;

      notify(isSuccess ? 'positive' : 'negative', mappedResult.message || 'Opération terminée');

      // Refresh only if successful, as failure might not consume credits
      if (isSuccess) {
        await refreshUserDataAndHistory();
      } else {
          // Optionally still refresh history even on failure?
          await fetchSmsHistory();
      }


      return mappedResult; // Return the mapped result
    } catch (error) {
      handleError(error, "Erreur lors de l'envoi du SMS");
      return null;
    } finally {
      loading.value = false;
    }
  };

  // Common logic for processing bulk/segment results
  const processBulkResult = (
    resultData: RawBulkSmsResponse | RawSegmentSmsResponse,
    successMessagePrefix: string,
    warningMessagePrefix: string,
    errorMessagePrefix: string
  ): BulkSmsResult | SegmentSmsResult => { // Return type depends on input but structure is compatible
      let frontendStatus: FrontendStatus;
      let notificationType: 'positive' | 'warning' | 'negative';
      let notificationMessage: string;
      let resultMessage = resultData.message ?? ''; // Base message from backend

      const summary = resultData.summary;
      const isSegment = 'segment' in resultData && resultData.segment;
      const segmentName = isSegment ? ` au segment ${resultData.segment?.name ?? ''}` : '';

      if (resultData.status === 'ERROR' || (summary.failed === summary.total && summary.total > 0)) {
          frontendStatus = 'error';
          notificationType = 'negative';
          // Prioritize credit error message if applicable
          if (resultMessage.includes('Crédits SMS insuffisants')) {
              notificationMessage = 'Crédits SMS insuffisants.';
          } else {
            notificationMessage = `${errorMessagePrefix}${segmentName}. ${resultMessage}`;
          }
      } else if (summary.failed > 0) {
          frontendStatus = 'warning';
          notificationType = 'warning';
          notificationMessage = `${warningMessagePrefix}${segmentName}: ${summary.successful} succès, ${summary.failed} échec(s) sur ${summary.total}. ${resultMessage}`;
      } else if (summary.successful === summary.total && summary.total > 0) {
          frontendStatus = 'success';
          notificationType = 'positive';
          notificationMessage = `${successMessagePrefix}${segmentName} (${summary.successful}/${summary.total}). ${resultMessage}`;
      } else if (summary.total === 0) {
          frontendStatus = 'warning'; // Or 'success'? debatable. Let's use warning.
          notificationType = 'warning';
          notificationMessage = `Aucun SMS n'a été envoyé${segmentName} (0 destinataire). ${resultMessage}`;
      }
      else { // Should not happen if backend status is reliable, but handle as warning
          frontendStatus = 'warning';
          notificationType = 'warning';
          notificationMessage = `Statut d'envoi indéterminé${segmentName}. ${resultMessage}`;
      }

      // Construct the final mapped result object
      const mappedResult = {
          ...resultData, // Spread original data (summary, segment?, results?)
          status: frontendStatus,
          originalStatus: resultData.status,
          message: resultMessage || notificationMessage, // Use backend message or generated one
      };

      // Show the derived notification
      notify(notificationType, notificationMessage);

      return mappedResult;
  };


  const sendBulkSms = async (payload: { phoneNumbers: string[]; message: string }): Promise<BulkSmsResult | null> => {
    loading.value = true;
    smsResult.value = null;

    try {
      const { data } = await apolloClient.mutate<{ sendBulkSms: RawBulkSmsResponse }>({
        mutation: SEND_BULK_SMS,
        variables: { ...payload, userId: getUserIdForGraphQL() },
      });

      if (!data?.sendBulkSms) throw new Error("Aucune donnée retournée par le serveur pour sendBulkSms");

      const mappedResult = processBulkResult(
        data.sendBulkSms,
        "SMS envoyés avec succès",
        "Envoi en masse terminé avec des échecs",
        "Échec de l'envoi en masse"
      ) as BulkSmsResult; // Cast to specific type

      smsResult.value = mappedResult;

      // Refresh user/history regardless of partial success/failure for bulk sends
      await refreshUserDataAndHistory();

      return mappedResult; // Return the mapped result
    } catch (error) {
      handleError(error, "Erreur lors de l'envoi des SMS en masse");
      return null;
    } finally {
      loading.value = false;
    }
  };

  const sendSegmentSms = async (payload: { segmentId: number; message: string }): Promise<SegmentSmsResult | null> => {
    loading.value = true;
    smsResult.value = null;

    try {
      const { data } = await apolloClient.mutate<{ sendSmsToSegment: RawSegmentSmsResponse }>({
        mutation: SEND_SMS_TO_SEGMENT,
        variables: {
          segmentId: payload.segmentId.toString(), // Ensure string for ID type
          message: payload.message,
          userId: getUserIdForGraphQL()
        },
      });

      if (!data?.sendSmsToSegment) throw new Error("Aucune donnée retournée par le serveur pour sendSmsToSegment");

      // Use the common processing logic
      const mappedResult = processBulkResult(
        data.sendSmsToSegment,
         "SMS envoyés avec succès", // Prefix will be augmented with segment name
         "Envoi terminé avec des échecs",
         "Échec de l'envoi"
      ) as SegmentSmsResult; // Cast to specific type

      smsResult.value = mappedResult;

      // Refresh user/history regardless of partial success/failure for segment sends
      await refreshUserDataAndHistory();

      return mappedResult; // Return the mapped result
    } catch (error) {
      handleError(error, "Erreur lors de l'envoi des SMS au segment");
      return null;
    } finally {
      loading.value = false;
    }
  };

  const sendSmsToAllContacts = async (payload: { message: string }): Promise<BulkSmsResult | null> => {
    loading.value = true;
    smsResult.value = null;

    try {
      // Note: Assuming sendSmsToAllContacts implicitly uses the logged-in user context on the backend
      const { data } = await apolloClient.mutate<{ sendSmsToAllContacts: RawBulkSmsResponse }>({
        mutation: SEND_SMS_TO_ALL_CONTACTS,
        variables: payload,
      });

      if (!data?.sendSmsToAllContacts) throw new Error("Aucune donnée retournée par le serveur pour sendSmsToAllContacts");

      // Use the common processing logic
       const mappedResult = processBulkResult(
        data.sendSmsToAllContacts,
        "SMS envoyés avec succès à tous les contacts",
        "Envoi à tous les contacts terminé avec des échecs",
        "Échec de l'envoi à tous les contacts"
      ) as BulkSmsResult; // No segment info here

      smsResult.value = mappedResult;

      // Refresh user/history regardless of partial success/failure
      await refreshUserDataAndHistory();

      return mappedResult; // Return the mapped result
    } catch (error) {
      handleError(error, "Erreur lors de l'envoi à tous les contacts");
      return null;
    } finally {
      loading.value = false;
    }
  };

  // --- Exposed Data and Methods ---
  return {
    loading: computed(() => loading.value), // Expose as readonly computed if preferred
    loadingHistory: computed(() => loadingHistory.value),
    loadingSegments: computed(() => loadingSegments.value),
    smsHistory,
    segments,
    smsResult, // Consumers read this reactive state
    hasInsufficientCredits,

    fetchSmsHistory,
    fetchSegments,
    sendSingleSms,
    sendBulkSms,
    sendSegmentSms,
    sendSmsToAllContacts,
  };
}
