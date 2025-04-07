import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import notification from '../services/NotificationService';
import { SenderName } from './userStore';

// GraphQL queries and mutations
const GET_SENDER_NAMES = `
  query GetSenderNames {
    senderNames {
      id
      userId
      name
      status
      createdAt
      updatedAt
    }
  }
`;

const GET_SENDER_NAMES_BY_USER = `
  query GetSenderNamesByUser($userId: ID!) {
    senderNamesByUser(userId: $userId) {
      id
      userId
      name
      status
      createdAt
      updatedAt
    }
  }
`;

const GET_SENDER_NAME = `
  query GetSenderName($id: ID!) {
    senderName(id: $id) {
      id
      userId
      name
      status
      createdAt
      updatedAt
    }
  }
`;

const CREATE_SENDER_NAME = `
  mutation CreateSenderName($userId: ID!, $name: String!) {
    createSenderName(userId: $userId, name: $name) {
      id
      userId
      name
      status
      createdAt
      updatedAt
    }
  }
`;

const UPDATE_SENDER_NAME_STATUS = `
  mutation UpdateSenderNameStatus($id: ID!, $status: String!) {
    updateSenderNameStatus(id: $id, status: $status) {
      id
      userId
      name
      status
      createdAt
      updatedAt
    }
  }
`;

const DELETE_SENDER_NAME = `
  mutation DeleteSenderName($id: ID!) {
    deleteSenderName(id: $id)
  }
`;

// Store definition
export const useSenderNameStore = defineStore('senderName', () => {
  // State
  const senderNames = ref<SenderName[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);
  const currentSenderName = ref<SenderName | null>(null);
  
  // Getters
  const getSenderNameById = computed(() => {
    return (id: number) => senderNames.value.find(senderName => senderName.id === id);
  });
  
  const getSenderNamesByUser = computed(() => {
    return (userId: number) => senderNames.value.filter(senderName => senderName.userId === userId);
  });
  
  const getSenderNamesByStatus = computed(() => {
    return (status: 'pending' | 'approved' | 'rejected') => 
      senderNames.value.filter(senderName => senderName.status === status);
  });
  
  const pendingSenderNames = computed(() => {
    return senderNames.value.filter(senderName => senderName.status === 'pending');
  });
  
  const approvedSenderNames = computed(() => {
    return senderNames.value.filter(senderName => senderName.status === 'approved');
  });
  
  const rejectedSenderNames = computed(() => {
    return senderNames.value.filter(senderName => senderName.status === 'rejected');
  });
  
  // Actions
  async function fetchSenderNames() {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          query: GET_SENDER_NAMES
        }),
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      senderNames.value = result.data.senderNames;
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Une erreur est survenue lors de la récupération des noms d\'expéditeur';
      notification.error(error.value);
    } finally {
      loading.value = false;
    }
  }
  
  async function fetchSenderNamesByUser(userId: number) {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          query: GET_SENDER_NAMES_BY_USER,
          variables: { userId }
        }),
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      return result.data.senderNamesByUser;
    } catch (err) {
      error.value = err instanceof Error ? err.message : `Une erreur est survenue lors de la récupération des noms d'expéditeur pour l'utilisateur #${userId}`;
      notification.error(error.value);
      return [];
    } finally {
      loading.value = false;
    }
  }
  
  async function fetchSenderName(id: number) {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          query: GET_SENDER_NAME,
          variables: { id }
        }),
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      currentSenderName.value = result.data.senderName;
      return result.data.senderName;
    } catch (err) {
      error.value = err instanceof Error ? err.message : `Une erreur est survenue lors de la récupération du nom d'expéditeur #${id}`;
      notification.error(error.value);
      return null;
    } finally {
      loading.value = false;
    }
  }
  
  async function createSenderName(userId: number, name: string) {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          query: CREATE_SENDER_NAME,
          variables: { userId, name }
        }),
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      const newSenderName = result.data.createSenderName;
      senderNames.value.push(newSenderName);
      notification.success(`Le nom d'expéditeur "${name}" a été créé avec succès`);
      return newSenderName;
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Une erreur est survenue lors de la création du nom d\'expéditeur';
      notification.error(error.value);
      return null;
    } finally {
      loading.value = false;
    }
  }
  
  async function updateSenderNameStatus(id: number, status: 'pending' | 'approved' | 'rejected') {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          query: UPDATE_SENDER_NAME_STATUS,
          variables: { id, status }
        }),
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      const updatedSenderName = result.data.updateSenderNameStatus;
      const index = senderNames.value.findIndex(senderName => senderName.id === id);
      
      if (index !== -1) {
        senderNames.value[index] = updatedSenderName;
      }
      
      const statusText = status === 'approved' ? 'approuvé' : status === 'rejected' ? 'rejeté' : 'en attente';
      notification.success(`Le statut du nom d'expéditeur a été mis à jour à "${statusText}"`);
      return updatedSenderName;
    } catch (err) {
      error.value = err instanceof Error ? err.message : `Une erreur est survenue lors de la mise à jour du statut du nom d'expéditeur #${id}`;
      notification.error(error.value);
      return null;
    } finally {
      loading.value = false;
    }
  }
  
  async function deleteSenderName(id: number) {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          query: DELETE_SENDER_NAME,
          variables: { id }
        }),
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      const success = result.data.deleteSenderName;
      
      if (success) {
        senderNames.value = senderNames.value.filter(senderName => senderName.id !== id);
        notification.success(`Le nom d'expéditeur a été supprimé avec succès`);
      } else {
        throw new Error('La suppression a échoué');
      }
      
      return success;
    } catch (err) {
      error.value = err instanceof Error ? err.message : `Une erreur est survenue lors de la suppression du nom d'expéditeur #${id}`;
      notification.error(error.value);
      return false;
    } finally {
      loading.value = false;
    }
  }
  
  // Nouvelles méthodes pour approveSenderName et rejectSenderName
  async function approveSenderName(id: number) {
    return updateSenderNameStatus(id, 'approved');
  }
  
  async function rejectSenderName(id: number) {
    return updateSenderNameStatus(id, 'rejected');
  }
  
  return {
    // State
    senderNames,
    loading,
    error,
    currentSenderName,
    
    // Getters
    getSenderNameById,
    getSenderNamesByUser,
    getSenderNamesByStatus,
    pendingSenderNames,
    approvedSenderNames,
    rejectedSenderNames,
    
    // Actions
    fetchSenderNames,
    fetchSenderNamesByUser,
    fetchSenderName,
    createSenderName,
    updateSenderNameStatus,
    deleteSenderName,
    
    // Nouvelles méthodes
    approveSenderName,
    rejectSenderName
  };
});
