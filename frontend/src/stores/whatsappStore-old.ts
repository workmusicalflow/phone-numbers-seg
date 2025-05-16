import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { apolloClient, gql } from '@/services/api';

// Types pour l'historique des messages WhatsApp
export interface WhatsAppMessageHistory {
  id: string;
  wabaMessageId: string;
  phoneNumber: string;
  direction: 'INCOMING' | 'OUTGOING';
  type: string;
  content: string | null;
  status: string;
  timestamp: string;
  errorCode: number | null;
  errorMessage: string | null;
  conversationId: string | null;
  pricingCategory: string | null;
  mediaId: string | null;
  templateName: string | null;
  templateLanguage: string | null;
  contextData: string | null;
  createdAt: string;
  updatedAt: string | null;
}

// Types pour l'envoi de messages
export interface WhatsAppMessageInput {
  recipient: string;
  type: 'text' | 'image' | 'video' | 'audio' | 'document';
  content: string | null;
  mediaUrl?: string | null;
}

export interface WhatsAppTemplateSendInput {
  recipient: string;
  templateName: string;
  languageCode: string;
  headerImageUrl?: string | null;
  body1Param?: string | null;
  body2Param?: string | null;
  body3Param?: string | null;
}

// Store WhatsApp
export const useWhatsAppStore = defineStore('whatsapp', () => {
  // State
  const messages = ref<WhatsAppMessageHistory[]>([]);
  const isLoading = ref(false);
  const error = ref<string | null>(null);
  const totalCount = ref(0);
  const currentPage = ref(1);
  const pageSize = ref(50);
  const filterPhoneNumber = ref('');
  const filterStatus = ref('');

  // Getters
  const sortedMessages = computed(() => {
    return [...messages.value].sort((a, b) => 
      new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime()
    );
  });

  const filteredMessages = computed(() => {
    let filtered = sortedMessages.value;
    
    if (filterPhoneNumber.value) {
      filtered = filtered.filter(msg => 
        msg.phoneNumber.includes(filterPhoneNumber.value)
      );
    }
    
    if (filterStatus.value) {
      filtered = filtered.filter(msg => msg.status === filterStatus.value);
    }
    
    return filtered;
  });

  const paginatedMessages = computed(() => {
    const start = (currentPage.value - 1) * pageSize.value;
    const end = start + pageSize.value;
    return filteredMessages.value.slice(start, end);
  });

  const totalPages = computed(() => {
    return Math.ceil(filteredMessages.value.length / pageSize.value);
  });

  // Actions
  async function fetchMessageHistory(
    phoneNumber: string | null = null,
    status: string | null = null,
    limit: number = 100,
    offset: number = 0
  ) {
    isLoading.value = true;
    error.value = null;
    
    try {
      const query = `
        query WhatsAppHistory($limit: Int!, $offset: Int!, $phoneNumber: String, $status: String) {
          whatsAppHistory(limit: $limit, offset: $offset, phoneNumber: $phoneNumber, status: $status) {
            id
            wabaMessageId
            phoneNumber
            direction
            type
            content
            status
            timestamp
            errorCode
            errorMessage
            conversationId
            pricingCategory
            mediaId
            templateName
            templateLanguage
            contextData
            createdAt
            updatedAt
          }
        }
      `;
      
      const variables = {
        limit,
        offset,
        phoneNumber,
        status
      };
      
      const response = await graphql(query, variables);
      
      if (response.whatsAppHistory) {
        messages.value = response.whatsAppHistory;
        
        // Obtenir le nombre total de messages
        const countResponse = await fetchMessageCount(phoneNumber, status);
        totalCount.value = countResponse;
      }
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Une erreur est survenue';
      console.error('Erreur lors de la récupération de l\'historique WhatsApp:', err);
    } finally {
      isLoading.value = false;
    }
  }

  async function fetchMessageCount(
    phoneNumber: string | null = null,
    status: string | null = null,
    direction: string | null = null
  ): Promise<number> {
    try {
      const query = `
        query WhatsAppMessageCount($status: String, $direction: String) {
          whatsAppMessageCount(status: $status, direction: $direction)
        }
      `;
      
      const variables = {
        status,
        direction
      };
      
      const response = await graphql(query, variables);
      return response.whatsAppMessageCount || 0;
    } catch (err) {
      console.error('Erreur lors du comptage des messages:', err);
      return 0;
    }
  }

  async function sendMessage(message: WhatsAppMessageInput): Promise<WhatsAppMessageHistory | null> {
    isLoading.value = true;
    error.value = null;
    
    try {
      const query = `
        mutation SendWhatsAppMessage($message: WhatsAppMessageInput!) {
          sendWhatsAppMessage(message: $message) {
            id
            wabaMessageId
            phoneNumber
            direction
            type
            content
            status
            timestamp
            createdAt
          }
        }
      `;
      
      const response = await graphql(query, { message });
      
      if (response.sendWhatsAppMessage) {
        // Ajouter le message à la liste
        messages.value.unshift(response.sendWhatsAppMessage);
        return response.sendWhatsAppMessage;
      }
      
      throw new Error('Réponse invalide du serveur');
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Une erreur est survenue';
      console.error('Erreur lors de l\'envoi du message WhatsApp:', err);
      return null;
    } finally {
      isLoading.value = false;
    }
  }

  async function sendTemplateMessage(template: WhatsAppTemplateSendInput): Promise<WhatsAppMessageHistory | null> {
    isLoading.value = true;
    error.value = null;
    
    try {
      const query = `
        mutation SendWhatsAppTemplate($template: WhatsAppTemplateSendInput!) {
          sendWhatsAppTemplate(template: $template) {
            id
            wabaMessageId
            phoneNumber
            direction
            type
            templateName
            templateLanguage
            status
            timestamp
            createdAt
          }
        }
      `;
      
      const response = await graphql(query, { template });
      
      if (response.sendWhatsAppTemplate) {
        // Ajouter le message à la liste
        messages.value.unshift(response.sendWhatsAppTemplate);
        return response.sendWhatsAppTemplate;
      }
      
      throw new Error('Réponse invalide du serveur');
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Une erreur est survenue';
      console.error('Erreur lors de l\'envoi du template WhatsApp:', err);
      return null;
    } finally {
      isLoading.value = false;
    }
  }

  // Actions de pagination et filtrage
  function setCurrentPage(page: number) {
    currentPage.value = page;
  }

  function setPageSize(size: number) {
    pageSize.value = size;
    currentPage.value = 1;
  }

  function setFilters(phoneNumber: string = '', status: string = '') {
    filterPhoneNumber.value = phoneNumber;
    filterStatus.value = status;
    currentPage.value = 1;
  }

  function clearFilters() {
    filterPhoneNumber.value = '';
    filterStatus.value = '';
    currentPage.value = 1;
  }

  // Action pour rafraîchir les messages
  async function refreshMessages() {
    await fetchMessageHistory(
      filterPhoneNumber.value || null,
      filterStatus.value || null
    );
  }

  // Action pour charger un message spécifique
  async function fetchMessage(id: number) {
    try {
      const query = `
        query WhatsAppMessage($id: Int!) {
          whatsAppMessage(id: $id) {
            id
            wabaMessageId
            phoneNumber
            direction
            type
            content
            status
            timestamp
            errorCode
            errorMessage
            conversationId
            pricingCategory
            mediaId
            templateName
            templateLanguage
            contextData
            createdAt
            updatedAt
          }
        }
      `;
      
      const response = await graphql(query, { id });
      return response.whatsAppMessage;
    } catch (err) {
      console.error('Erreur lors de la récupération du message:', err);
      return null;
    }
  }

  return {
    // State
    messages,
    isLoading,
    error,
    totalCount,
    currentPage,
    pageSize,
    filterPhoneNumber,
    filterStatus,
    
    // Getters
    sortedMessages,
    filteredMessages,
    paginatedMessages,
    totalPages,
    
    // Actions
    fetchMessageHistory,
    fetchMessageCount,
    sendMessage,
    sendTemplateMessage,
    setCurrentPage,
    setPageSize,
    setFilters,
    clearFilters,
    refreshMessages,
    fetchMessage
  };
});