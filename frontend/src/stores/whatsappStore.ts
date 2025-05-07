import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { api } from '@/services/api';
import { graphql } from '@/services/graphql';

export interface WhatsAppMessage {
  id: string;
  messageId: string;
  sender: string;
  recipient: string | null;
  timestamp: number;
  type: string;
  content: string | null;
  mediaUrl: string | null;
  mediaType: string | null;
  status: string | null;
  createdAt: number;
  formattedTimestamp: string;
  formattedCreatedAt: string;
}

export interface WhatsAppMessageResponse {
  success: boolean;
  messageId: string | null;
  error: string | null;
}

export interface SendTemplateInput {
  recipient: string;
  templateName: string;
  languageCode: string;
  headerImageUrl?: string;
  body1Param?: string;
  body2Param?: string;
  body3Param?: string;
}

export const useWhatsAppStore = defineStore('whatsappStore', () => {
  // State
  const messages = ref<WhatsAppMessage[]>([]);
  const isLoading = ref(false);
  const error = ref<string | null>(null);
  const totalCount = ref(0);
  const currentPage = ref(1);
  const pageSize = ref(10);
  const filterSender = ref('');
  const filterType = ref('');

  // Getters
  const sortedMessages = computed(() => {
    return [...messages.value].sort((a, b) => b.timestamp - a.timestamp);
  });

  const filteredMessages = computed(() => {
    let filtered = sortedMessages.value;
    
    if (filterSender.value) {
      filtered = filtered.filter(msg => 
        msg.sender.includes(filterSender.value) || 
        (msg.recipient && msg.recipient.includes(filterSender.value))
      );
    }
    
    if (filterType.value) {
      filtered = filtered.filter(msg => msg.type === filterType.value);
    }
    
    return filtered;
  });

  // Actions
  async function fetchMessages(limit = pageSize.value, offset = (currentPage.value - 1) * pageSize.value) {
    isLoading.value = true;
    error.value = null;
    
    try {
      let query = '';
      let variables = {};
      
      // Construire la requête en fonction des filtres
      if (filterSender.value) {
        query = `
          query GetMessagesBySender($sender: String!, $limit: Int!, $offset: Int!) {
            getWhatsAppMessagesBySender(sender: $sender, limit: $limit, offset: $offset) {
              id
              messageId
              sender
              recipient
              timestamp
              type
              content
              mediaUrl
              mediaType
              status
              createdAt
              formattedTimestamp
              formattedCreatedAt
            }
          }
        `;
        variables = { sender: filterSender.value, limit, offset };
      } else if (filterType.value) {
        query = `
          query GetMessagesByType($type: String!, $limit: Int!, $offset: Int!) {
            getWhatsAppMessagesByType(type: $type, limit: $limit, offset: $offset) {
              id
              messageId
              sender
              recipient
              timestamp
              type
              content
              mediaUrl
              mediaType
              status
              createdAt
              formattedTimestamp
              formattedCreatedAt
            }
          }
        `;
        variables = { type: filterType.value, limit, offset };
      } else {
        // Par défaut, récupérer tous les messages
        // Astuce: utiliser les paramètres d'une des requêtes précédentes
        // car GraphQL n'a pas de requête pour tous les messages directement
        query = `
          query GetMessagesByType($type: String!, $limit: Int!, $offset: Int!) {
            getWhatsAppMessagesByType(type: $type, limit: $limit, offset: $offset) {
              id
              messageId
              sender
              recipient
              timestamp
              type
              content
              mediaUrl
              mediaType
              status
              createdAt
              formattedTimestamp
              formattedCreatedAt
            }
          }
        `;
        // Un type vide ou "%" ne fonctionnera pas, utiliser "text" par défaut
        variables = { type: "text", limit, offset };
      }
      
      const response = await graphql(query, variables);
      
      // Déterminer quelle propriété contient les résultats
      const resultKey = Object.keys(response).find(key => 
        key.startsWith('getWhatsAppMessages')
      );
      
      if (resultKey && Array.isArray(response[resultKey])) {
        messages.value = response[resultKey];
        // Idéalement, on aurait une requête pour compter le total
        // En l'absence, on utilise la longueur de la liste
        totalCount.value = response[resultKey].length;
      }
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Une erreur est survenue';
      console.error('Erreur lors de la récupération des messages WhatsApp:', err);
    } finally {
      isLoading.value = false;
    }
  }

  async function sendTextMessage(recipient: string, message: string): Promise<WhatsAppMessageResponse> {
    isLoading.value = true;
    error.value = null;
    
    try {
      const query = `
        mutation SendWhatsAppTextMessage($recipient: String!, $message: String!) {
          sendWhatsAppTextMessage(recipient: $recipient, message: $message) {
            success
            messageId
            error
          }
        }
      `;
      
      const response = await graphql(query, { recipient, message });
      
      if (response.sendWhatsAppTextMessage) {
        // Si l'envoi est réussi, actualiser la liste des messages
        if (response.sendWhatsAppTextMessage.success) {
          await fetchMessages();
        }
        
        return response.sendWhatsAppTextMessage;
      }
      
      throw new Error('Réponse invalide du serveur');
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Une erreur est survenue';
      console.error('Erreur lors de l\'envoi du message WhatsApp:', err);
      return {
        success: false,
        messageId: null,
        error: error.value
      };
    } finally {
      isLoading.value = false;
    }
  }

  async function sendTemplateMessage(input: SendTemplateInput): Promise<WhatsAppMessageResponse> {
    isLoading.value = true;
    error.value = null;
    
    try {
      const query = `
        mutation SendWhatsAppTemplateMessage($input: WhatsAppTemplateSendInput!) {
          sendWhatsAppTemplateMessage(input: $input) {
            success
            messageId
            error
          }
        }
      `;
      
      const response = await graphql(query, { input });
      
      if (response.sendWhatsAppTemplateMessage) {
        // Si l'envoi est réussi, actualiser la liste des messages
        if (response.sendWhatsAppTemplateMessage.success) {
          await fetchMessages();
        }
        
        return response.sendWhatsAppTemplateMessage;
      }
      
      throw new Error('Réponse invalide du serveur');
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Une erreur est survenue';
      console.error('Erreur lors de l\'envoi du template WhatsApp:', err);
      return {
        success: false,
        messageId: null,
        error: error.value
      };
    } finally {
      isLoading.value = false;
    }
  }

  function setCurrentPage(page: number) {
    currentPage.value = page;
    fetchMessages();
  }

  function setPageSize(size: number) {
    pageSize.value = size;
    currentPage.value = 1;
    fetchMessages();
  }

  function setFilter(sender: string = '', type: string = '') {
    filterSender.value = sender;
    filterType.value = type;
    currentPage.value = 1;
    fetchMessages();
  }

  return {
    messages,
    sortedMessages,
    filteredMessages,
    isLoading,
    error,
    totalCount,
    currentPage,
    pageSize,
    filterSender,
    filterType,
    fetchMessages,
    sendTextMessage,
    sendTemplateMessage,
    setCurrentPage,
    setPageSize,
    setFilter
  };
});