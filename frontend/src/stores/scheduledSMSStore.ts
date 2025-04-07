import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { useNotification } from '../components/NotificationService';
import api from '../services/api';

export const useScheduledSMSStore = defineStore('scheduledSMS', () => {
  const notification = useNotification();
  
  // État
  const scheduledSMSList = ref<any[]>([]);
  const loading = ref(false);
  const totalCount = ref(0);
  const currentPage = ref(1);
  const pageSize = ref(10);
  const searchQuery = ref('');
  const selectedScheduledSMS = ref<any>(null);
  const scheduledSMSLogs = ref<any[]>([]);
  const logsLoading = ref(false);
  const logsTotalCount = ref(0);
  const logsCurrentPage = ref(1);
  const logsPageSize = ref(5);
  
  // Getters
  const paginatedScheduledSMS = computed(() => scheduledSMSList.value);
  const hasScheduledSMS = computed(() => scheduledSMSList.value.length > 0);
  const totalPages = computed(() => Math.ceil(totalCount.value / pageSize.value));
  const logsTotalPages = computed(() => Math.ceil(logsTotalCount.value / logsPageSize.value));
  
  // Actions
  async function fetchScheduledSMS() {
    loading.value = true;
    try {
      const offset = (currentPage.value - 1) * pageSize.value;
      
      let query = `
        query GetScheduledSMS($limit: Int, $offset: Int) {
          scheduledSMS(limit: $limit, offset: $offset) {
            id
            name
            message
            senderNameId
            scheduledDate
            status
            isRecurring
            recurrencePattern
            formattedRecurrenceConfig
            recipientsType
            recipientsCount
            createdAt
            lastRunAt
            nextRunAt
          }
          scheduledSMSCount
        }
      `;
      
      if (searchQuery.value) {
        query = `
          query SearchScheduledSMS($query: String!, $limit: Int, $offset: Int) {
            searchScheduledSMS(query: $query, limit: $limit, offset: $offset) {
              id
              name
              message
              senderNameId
              scheduledDate
              status
              isRecurring
              recurrencePattern
              formattedRecurrenceConfig
              recipientsType
              recipientsCount
              createdAt
              lastRunAt
              nextRunAt
            }
            scheduledSMSCount
          }
        `;
      }
      
      const variables = searchQuery.value
        ? { query: searchQuery.value, limit: pageSize.value, offset }
        : { limit: pageSize.value, offset };
      
      const response = await api.graphql(query, variables);
      
      if (searchQuery.value) {
        scheduledSMSList.value = response.data.searchScheduledSMS;
      } else {
        scheduledSMSList.value = response.data.scheduledSMS;
      }
      
      totalCount.value = response.data.scheduledSMSCount;
    } catch (error) {
      console.error('Error fetching scheduled SMS:', error);
      notification.error('Erreur lors du chargement des SMS planifiés', '');
    } finally {
      loading.value = false;
    }
  }
  
  async function fetchScheduledSMSById(id: number) {
    loading.value = true;
    try {
      const query = `
        query GetScheduledSMSById($id: Int!) {
          scheduledSMSById(id: $id) {
            id
            name
            message
            senderNameId
            scheduledDate
            status
            isRecurring
            recurrencePattern
            recurrenceConfig
            recipientsType
            recipientsData
            createdAt
            updatedAt
            lastRunAt
            nextRunAt
          }
        }
      `;
      
      const response = await api.graphql(query, { id });
      selectedScheduledSMS.value = response.data.scheduledSMSById;
      return selectedScheduledSMS.value;
    } catch (error) {
      console.error('Error fetching scheduled SMS by ID:', error);
      notification.error('Erreur lors du chargement du SMS planifié', '');
      return null;
    } finally {
      loading.value = false;
    }
  }
  
  async function fetchScheduledSMSLogs(scheduledSmsId: number) {
    logsLoading.value = true;
    try {
      const offset = (logsCurrentPage.value - 1) * logsPageSize.value;
      
      const query = `
        query GetScheduledSMSLogs($scheduledSmsId: Int!, $limit: Int, $offset: Int) {
          scheduledSMSLogs(scheduledSmsId: $scheduledSmsId, limit: $limit, offset: $offset) {
            id
            executionDate
            status
            statusLabel
            statusColor
            totalRecipients
            successfulSends
            failedSends
            successRate
            errorDetails
            createdAt
          }
          scheduledSMSLogsCount(scheduledSmsId: $scheduledSmsId)
        }
      `;
      
      const response = await api.graphql(query, {
        scheduledSmsId,
        limit: logsPageSize.value,
        offset
      });
      
      scheduledSMSLogs.value = response.data.scheduledSMSLogs;
      logsTotalCount.value = response.data.scheduledSMSLogsCount;
    } catch (error) {
      console.error('Error fetching scheduled SMS logs:', error);
      notification.error('Erreur lors du chargement des logs de SMS planifiés', '');
    } finally {
      logsLoading.value = false;
    }
  }
  
  async function createScheduledSMS(scheduledSMSData: any) {
    loading.value = true;
    try {
      const query = `
        mutation CreateScheduledSMS(
          $name: String!,
          $message: String!,
          $senderNameId: Int!,
          $scheduledDate: String!,
          $recipientsType: String!,
          $recipientsData: String!,
          $isRecurring: Boolean,
          $recurrencePattern: String,
          $recurrenceConfig: String
        ) {
          createScheduledSMS(
            name: $name,
            message: $message,
            senderNameId: $senderNameId,
            scheduledDate: $scheduledDate,
            recipientsType: $recipientsType,
            recipientsData: $recipientsData,
            isRecurring: $isRecurring,
            recurrencePattern: $recurrencePattern,
            recurrenceConfig: $recurrenceConfig
          ) {
            id
            name
            status
          }
        }
      `;
      
      const response = await api.graphql(query, scheduledSMSData);
      
      if (response.data.createScheduledSMS) {
        notification.success('SMS planifié créé avec succès', '');
        await fetchScheduledSMS();
        return response.data.createScheduledSMS;
      }
      return null;
    } catch (error) {
      console.error('Error creating scheduled SMS:', error);
      notification.error('Erreur lors de la création du SMS planifié', '');
      throw error;
    } finally {
      loading.value = false;
    }
  }
  
  async function updateScheduledSMS(scheduledSMSData: any) {
    loading.value = true;
    try {
      const query = `
        mutation UpdateScheduledSMS(
          $id: Int!,
          $name: String!,
          $message: String!,
          $senderNameId: Int!,
          $scheduledDate: String!,
          $recipientsType: String!,
          $recipientsData: String!,
          $isRecurring: Boolean,
          $recurrencePattern: String,
          $recurrenceConfig: String
        ) {
          updateScheduledSMS(
            id: $id,
            name: $name,
            message: $message,
            senderNameId: $senderNameId,
            scheduledDate: $scheduledDate,
            recipientsType: $recipientsType,
            recipientsData: $recipientsData,
            isRecurring: $isRecurring,
            recurrencePattern: $recurrencePattern,
            recurrenceConfig: $recurrenceConfig
          ) {
            id
            name
            status
          }
        }
      `;
      
      const response = await api.graphql(query, scheduledSMSData);
      
      if (response.data.updateScheduledSMS) {
        notification.success('SMS planifié mis à jour avec succès', '');
        await fetchScheduledSMS();
        return response.data.updateScheduledSMS;
      }
      return null;
    } catch (error) {
      console.error('Error updating scheduled SMS:', error);
      notification.error('Erreur lors de la mise à jour du SMS planifié', '');
      throw error;
    } finally {
      loading.value = false;
    }
  }
  
  async function cancelScheduledSMS(id: number) {
    loading.value = true;
    try {
      const query = `
        mutation CancelScheduledSMS($id: Int!) {
          cancelScheduledSMS(id: $id)
        }
      `;
      
      const response = await api.graphql(query, { id });
      
      if (response.data.cancelScheduledSMS) {
        notification.success('SMS planifié annulé avec succès', '');
        await fetchScheduledSMS();
        return true;
      }
      return false;
    } catch (error) {
      console.error('Error cancelling scheduled SMS:', error);
      notification.error('Erreur lors de l\'annulation du SMS planifié', '');
      return false;
    } finally {
      loading.value = false;
    }
  }
  
  async function deleteScheduledSMS(id: number) {
    loading.value = true;
    try {
      const query = `
        mutation DeleteScheduledSMS($id: Int!) {
          deleteScheduledSMS(id: $id)
        }
      `;
      
      const response = await api.graphql(query, { id });
      
      if (response.data.deleteScheduledSMS) {
        notification.success('SMS planifié supprimé avec succès', '');
        await fetchScheduledSMS();
        return true;
      }
      return false;
    } catch (error) {
      console.error('Error deleting scheduled SMS:', error);
      notification.error('Erreur lors de la suppression du SMS planifié', '');
      return false;
    } finally {
      loading.value = false;
    }
  }
  
  function setPage(page: number) {
    currentPage.value = page;
    fetchScheduledSMS();
  }
  
  function setLogsPage(page: number) {
    logsCurrentPage.value = page;
    if (selectedScheduledSMS.value) {
      fetchScheduledSMSLogs(selectedScheduledSMS.value.id);
    }
  }
  
  function setSearch(query: string) {
    searchQuery.value = query;
    currentPage.value = 1;
    fetchScheduledSMS();
  }
  
  function clearSearch() {
    searchQuery.value = '';
    fetchScheduledSMS();
  }
  
  function resetSelectedScheduledSMS() {
    selectedScheduledSMS.value = null;
    scheduledSMSLogs.value = [];
    logsTotalCount.value = 0;
    logsCurrentPage.value = 1;
  }
  
  return {
    // État
    scheduledSMSList,
    loading,
    totalCount,
    currentPage,
    pageSize,
    searchQuery,
    selectedScheduledSMS,
    scheduledSMSLogs,
    logsLoading,
    logsTotalCount,
    logsCurrentPage,
    logsPageSize,
    
    // Getters
    paginatedScheduledSMS,
    hasScheduledSMS,
    totalPages,
    logsTotalPages,
    
    // Actions
    fetchScheduledSMS,
    fetchScheduledSMSById,
    fetchScheduledSMSLogs,
    createScheduledSMS,
    updateScheduledSMS,
    cancelScheduledSMS,
    deleteScheduledSMS,
    setPage,
    setLogsPage,
    setSearch,
    clearSearch,
    resetSelectedScheduledSMS
  };
});
