import { ref, computed } from 'vue';
import { useQuasar } from 'quasar';
import { useApolloClient } from '@vue/apollo-composable';
import { gql } from '@apollo/client/core';
import { useUserStore, type User } from '../stores/userStore'; // Assuming User type is exported
import { useAuthStore } from '../stores/authStore'; // Needed? Maybe just userStore is enough

// --- Define Interfaces (Consider moving to a types file) ---

interface HistoryItem {
  id: string;
  phoneNumber: string;
  message: string;
  status: 'SENT' | 'FAILED' | 'PENDING'; // Adjust as per actual status values
  createdAt: string;
  // Add other fields if needed by the history table
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

interface BaseSmsResult {
  status: string; // e.g., 'COMPLETED', 'PARTIAL', 'ERROR', 'SENT', 'FAILED'
  message?: string;
}

interface SingleSmsResult extends BaseSmsResult {
  id?: string; // History ID
  phoneNumber?: string;
  createdAt?: string;
}

interface BulkSmsResult extends BaseSmsResult {
  summary: SmsResultSummary;
  results?: { phoneNumber: string; status: string; message?: string }[];
}

interface SegmentSmsResult extends BulkSmsResult {
  segment?: { id: number; name: string };
}

// Type for the smsResult ref
type CombinedSmsResult = SingleSmsResult | BulkSmsResult | SegmentSmsResult | null;


// --- GraphQL Operations ---

const GET_SMS_HISTORY = gql`
  query GetSmsHistory($userId: ID) {
    smsHistory(userId: $userId, limit: 10, offset: 0) { # Fetch more for initial display?
      id
      phoneNumber
      message
      status
      createdAt
      # Add other fields displayed in the table
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
  mutation SendSms($phoneNumber: String!, $message: String!, $userId: ID) {
    sendSms(phoneNumber: $phoneNumber, message: $message, userId: $userId) {
      id
      status # Only need status to determine success/failure message
      # Potentially add error message field if schema supports it
    }
  }
`;

const SEND_BULK_SMS = gql`
  mutation SendBulkSms($phoneNumbers: [String!]!, $message: String!, $userId: ID) {
    sendBulkSms(phoneNumbers: $phoneNumbers, message: $message, userId: $userId) {
      status
      message
      summary { total successful failed }
      # results not needed for summary notification
    }
  }
`;

const SEND_SMS_TO_SEGMENT = gql`
  mutation SendSmsToSegment($segmentId: ID!, $message: String!, $userId: ID) {
    sendSmsToSegment(segmentId: $segmentId, message: $message, userId: $userId) {
      status
      message
      segment { id name }
      summary { total successful failed }
      # results not needed for summary notification
    }
  }
`;

const SEND_SMS_TO_ALL_CONTACTS = gql`
  mutation SendSmsToAllContacts($message: String!) {
    sendSmsToAllContacts(message: $message) {
      status
      message
      summary { total successful failed }
      # results not needed for summary notification
    }
  }
`;


// --- Composable ---

export function useSmsSender() {
  const $q = useQuasar();
  const { client: apolloClient } = useApolloClient();
  const userStore = useUserStore();
  // const authStore = useAuthStore(); // Probably not needed if userStore has currentUser

  // --- State ---
  const loading = ref(false); // Global loading state for any send operation
  const loadingHistory = ref(false);
  const loadingSegments = ref(false);
  const smsHistory = ref<HistoryItem[]>([]);
  const segments = ref<Segment[]>([]);
  const smsResult = ref<CombinedSmsResult>(null); // Stores the result of the last operation

  // --- Computed ---
  const hasInsufficientCredits = computed(() => {
    // Provide a default check, component might override with specific amount check
    return userStore.currentUser ? userStore.currentUser.smsCredit <= 0 : true;
  });

  // --- Methods ---

  // Error Handling Helper
  const handleError = (error: unknown, contextMessage: string) => {
    console.error(`${contextMessage}:`, error);
    const errorMessage = error instanceof Error ? error.message : String(error);
    const isCreditError = errorMessage.includes('Crédits SMS insuffisants');
    
    // Update smsResult to show error state
    smsResult.value = { 
      status: 'ERROR', // Generic error status
      message: isCreditError ? 'Crédits SMS insuffisants.' : contextMessage 
    };

    $q.notify({ type: 'negative', message: smsResult.value.message });

    // Refresh user credits if it was a credit error
    if (isCreditError && userStore.currentUser) {
      // Use fetchUser from userStore (already uses apolloClient)
      userStore.fetchUser(userStore.currentUser.id); 
    }
  };

  // History Fetching
  const fetchSmsHistory = async () => {
    if (!userStore.currentUser?.id) return; // Don't fetch if no user
    loadingHistory.value = true;
    try {
      const { data, errors } = await apolloClient.query({
        query: GET_SMS_HISTORY,
        variables: { userId: userStore.currentUser.id },
        fetchPolicy: "network-only",
      });
      if (errors) throw errors;
      smsHistory.value = data.smsHistory;
    } catch (error) {
      handleError(error, "Erreur lors du chargement de l'historique");
    } finally {
      loadingHistory.value = false;
    }
  };

  // Segments Fetching
  const fetchSegments = async () => {
    loadingSegments.value = true;
    try {
      const { data, errors } = await apolloClient.query({
        query: GET_SEGMENTS_FOR_SMS,
        fetchPolicy: "network-only",
      });
       if (errors) throw errors;
      segments.value = data.segmentsForSMS;
    } catch (error) {
      handleError(error, "Erreur lors du chargement des segments");
    } finally {
      loadingSegments.value = false;
    }
  };

  // Generic Mutation Executor (Optional but good for DRY)
  // This is a more advanced pattern, let's stick to individual functions for now
  // for clarity, mirroring the original structure.

  // --- Send Actions ---

  const sendSingleSms = async (payload: { phoneNumber: string; message: string }): Promise<SingleSmsResult | null> => {
    loading.value = true;
    smsResult.value = null;
    try {
      const { data, errors } = await apolloClient.mutate({
        mutation: SEND_SINGLE_SMS,
        variables: { ...payload, userId: userStore.currentUser?.id },
        refetchQueries: [{ query: GET_SMS_HISTORY, variables: { userId: userStore.currentUser?.id }, fetchPolicy: "network-only" }],
        awaitRefetchQueries: true,
      });

      if (errors) throw errors;

      const resultData = data.sendSms;
      const isSuccess = resultData.status === 'SENT';
      smsResult.value = { // Store simplified result for display
          status: isSuccess ? 'success' : 'error', // Map backend status to simple display status
          message: isSuccess ? 'SMS envoyé avec succès' : 'Échec de l\'envoi',
          id: resultData.id // Pass along history ID if needed
      };
      $q.notify({ type: isSuccess ? 'positive' : 'negative', message: smsResult.value.message });

      if (isSuccess && userStore.currentUser) {
          userStore.fetchUser(userStore.currentUser.id); // Refresh credits
      }
      // fetchSmsHistory(); // Already refetched by refetchQueries

      return resultData; // Return raw mutation result

    } catch (error) {
      handleError(error, "Erreur lors de l'envoi du SMS");
      return null;
    } finally {
      loading.value = false;
    }
  };

  const sendBulkSms = async (payload: { phoneNumbers: string[]; message: string }): Promise<BulkSmsResult | null> => {
    loading.value = true;
    smsResult.value = null;
     try {
      const { data, errors } = await apolloClient.mutate({
        mutation: SEND_BULK_SMS,
        variables: { ...payload, userId: userStore.currentUser?.id },
        refetchQueries: [{ query: GET_SMS_HISTORY, variables: { userId: userStore.currentUser?.id }, fetchPolicy: "network-only" }],
        awaitRefetchQueries: true,
      });

      if (errors) throw errors;

      const resultData = data.sendBulkSms;
      smsResult.value = resultData; // Store full bulk result

      if (resultData.status === 'ERROR' || resultData.summary.failed > 0) {
         $q.notify({ type: 'warning', message: `Envoi terminé avec ${resultData.summary.failed} échec(s). ${resultData.message || ''}` });
      } else {
         $q.notify({ type: 'positive', message: `SMS envoyés avec succès (${resultData.summary.successful}/${resultData.summary.total})` });
      }

      if (userStore.currentUser) {
          userStore.fetchUser(userStore.currentUser.id); // Refresh credits
      }
      // fetchSmsHistory(); // Already refetched

      return resultData;

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
      const { data, errors } = await apolloClient.mutate({
        mutation: SEND_SMS_TO_SEGMENT,
        variables: { ...payload, userId: userStore.currentUser?.id },
         refetchQueries: [{ query: GET_SMS_HISTORY, variables: { userId: userStore.currentUser?.id }, fetchPolicy: "network-only" }],
         awaitRefetchQueries: true,
      });

       if (errors) throw errors;

      const resultData = data.sendSmsToSegment;
      smsResult.value = resultData;

       if (resultData.status === 'ERROR' || resultData.summary.failed > 0) {
         $q.notify({ type: 'warning', message: `Envoi au segment ${resultData.segment?.name || ''} terminé avec ${resultData.summary.failed} échec(s). ${resultData.message || ''}` });
      } else {
         $q.notify({ type: 'positive', message: `SMS envoyés avec succès au segment ${resultData.segment?.name || ''} (${resultData.summary.successful}/${resultData.summary.total})` });
      }

       if (userStore.currentUser) {
          userStore.fetchUser(userStore.currentUser.id); // Refresh credits
      }
      // fetchSmsHistory(); // Already refetched

      return resultData;

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
      const { data, errors } = await apolloClient.mutate({
        mutation: SEND_SMS_TO_ALL_CONTACTS,
        variables: { ...payload, userId: userStore.currentUser?.id }, // userId might not be needed if backend uses session
         refetchQueries: [{ query: GET_SMS_HISTORY, variables: { userId: userStore.currentUser?.id }, fetchPolicy: "network-only" }],
         awaitRefetchQueries: true,
      });

       if (errors) throw errors;

      const resultData = data.sendSmsToAllContacts;
      smsResult.value = resultData;

       if (resultData.status === 'ERROR' || resultData.summary.failed > 0) {
         $q.notify({ type: 'warning', message: `Envoi terminé avec ${resultData.summary.failed} échec(s). ${resultData.message || ''}` });
      } else {
         $q.notify({ type: 'positive', message: `SMS envoyés avec succès à ${resultData.summary.successful} contacts.` });
      }

       if (userStore.currentUser) {
          userStore.fetchUser(userStore.currentUser.id); // Refresh credits
      }
      // fetchSmsHistory(); // Already refetched

      return resultData;

    } catch (error) {
      handleError(error, "Erreur lors de l'envoi à tous les contacts");
      return null;
    } finally {
      loading.value = false;
    }
  };


  // --- Exposed Data and Methods ---
  return {
    loading,
    loadingHistory,
    loadingSegments,
    smsHistory,
    segments,
    smsResult,
    hasInsufficientCredits,

    fetchSmsHistory,
    fetchSegments,
    sendSingleSms,
    sendBulkSms,
    sendSegmentSms,
    sendSmsToAllContacts,
  };
}
