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
  contextData: any;
  createdAt: string;
  updatedAt: string;
}

// Input pour l'envoi de messages
export interface WhatsAppMessageInput {
  phoneNumber: string;
  type: 'text' | 'image' | 'video' | 'audio' | 'document' | 'template';
  content?: string;
  mediaUrl?: string;
  templateName?: string;
  templateLanguage?: string;
  templateParams?: Record<string, any>;
  contextData?: any;
}

// Input pour l'envoi de templates
export interface WhatsAppTemplateSendInput {
  phoneNumber: string;
  templateName: string;
  templateLanguage: string;
  headerParams?: string[];
  bodyParams?: string[];
  buttonParams?: string[];
}

export const useWhatsAppStore = defineStore('whatsapp', () => {
  // État
  const messages = ref<WhatsAppMessageHistory[]>([]);
  const isLoading = ref(false);
  const error = ref<string | null>(null);
  const totalCount = ref(0);
  const currentPage = ref(1);
  const pageSize = ref(20);
  
  // Filtres
  const filterPhoneNumber = ref('');
  const filterStatus = ref('');
  
  // Getters
  const sortedMessages = computed(() => {
    return [...messages.value].sort((a, b) => {
      return new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime();
    });
  });
  
  const filteredMessages = computed(() => {
    let filtered = sortedMessages.value;
    
    if (filterPhoneNumber.value) {
      filtered = filtered.filter(msg => 
        msg.phoneNumber.includes(filterPhoneNumber.value)
      );
    }
    
    if (filterStatus.value) {
      filtered = filtered.filter(msg => 
        msg.status === filterStatus.value
      );
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
  async function fetchMessages() {
    isLoading.value = true;
    error.value = null;
    
    try {
      const result = await apolloClient.query({
        query: gql`
          query GetWhatsAppMessages($limit: Int, $offset: Int) {
            getWhatsAppMessages(limit: $limit, offset: $offset) {
              messages {
                id
                wabaMessageId
                phoneNumber
                direction
                type
                content
                status
                errorCode
                errorMessage
                timestamp
                conversationId
                pricingCategory
                mediaId
                templateName
                templateLanguage
                contextData
                createdAt
                updatedAt
              }
              totalCount
              hasMore
            }
          }
        `,
        variables: {
          limit: 100,
          offset: 0
        },
        fetchPolicy: 'network-only'
      });
      
      if (result && result.data && result.data.getWhatsAppMessages) {
        messages.value = result.data.getWhatsAppMessages.messages || [];
        totalCount.value = result.data.getWhatsAppMessages.totalCount || 0;
      } else {
        console.warn('Aucune donnée WhatsApp reçue');
        messages.value = [];
        totalCount.value = 0;
      }
    } catch (err: any) {
      error.value = err.message || 'Une erreur est survenue';
      console.error('Erreur lors de la récupération des messages:', err);
    } finally {
      isLoading.value = false;
    }
  }

  async function sendMessage(message: any) {
    isLoading.value = true;
    error.value = null;
    
    try {
      const result = await apolloClient.mutate({
        mutation: gql`
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
              errorCode
              errorMessage
              createdAt
              updatedAt
            }
          }
        `,
        variables: { message }
      });
      
      if (result && result.data && result.data.sendWhatsAppMessage) {
        // Ensure the new message is a plain, extensible object
        const newMessage = { ...result.data.sendWhatsAppMessage };
        // Créer un nouveau tableau avec le nouveau message en premier
        messages.value = [newMessage, ...messages.value];
        return newMessage;
      }
      
      throw new Error('Réponse invalide du serveur');
    } catch (err: any) {
      error.value = err.message || 'Une erreur est survenue';
      console.error('Erreur lors de l\'envoi du message:', err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  }

  async function sendTemplate(template: WhatsAppTemplateSendInput) {
    isLoading.value = true;
    error.value = null;
    
    try {
      const result = await apolloClient.mutate({
        mutation: gql`
          mutation SendWhatsAppTemplate($message: WhatsAppMessageInput!) {
            sendWhatsAppMessage(message: $message) {
              id
              wabaMessageId
              phoneNumber
              direction
              type
              status
              createdAt
            }
          }
        `,
        variables: { 
          message: {
            recipient: template.phoneNumber,
            type: 'template',
            templateName: template.templateName,
            languageCode: template.templateLanguage,
            components: template.bodyParams ? [{
              type: 'body',
              parameters: template.bodyParams.map(param => ({ type: 'text', text: param }))
            }] : undefined
          }
        }
      });
      
      if (result && result.data && result.data.sendWhatsAppMessage) {
        // Ensure the new message is a plain, extensible object
        const newMessage = { ...result.data.sendWhatsAppMessage };
        // Créer un nouveau tableau avec le nouveau message en premier
        messages.value = [newMessage, ...messages.value];
        return newMessage;
      }
      
      throw new Error('Réponse invalide du serveur');
    } catch (err: any) {
      error.value = err.message || 'Une erreur est survenue';
      console.error('Erreur lors de l\'envoi du template:', err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  }

  async function loadUserTemplates() {
    try {
      const { data } = await apolloClient.query({
        query: gql`
          query GetUserTemplates {
            getWhatsAppUserTemplates {
              id
              template_id
              name
              language
              status
            }
          }
        `,
        fetchPolicy: 'network-only'
      });
      
      return data.getWhatsAppUserTemplates || [];
    } catch (err: any) {
      console.error('Erreur lors du chargement des templates:', err);
      return [];
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
    await fetchMessages();
  }
  
  // Alias pour compatibilité
  async function fetchMessageHistory() {
    await fetchMessages();
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
    fetchMessages,
    fetchMessageHistory,
    sendMessage,
    sendTemplate,
    loadUserTemplates,
    setCurrentPage,
    setPageSize,
    setFilters,
    clearFilters,
    refreshMessages
  };
});
