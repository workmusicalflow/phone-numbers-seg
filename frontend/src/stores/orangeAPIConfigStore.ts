import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import notification from '../services/NotificationService';
import { OrangeAPIConfig } from './userStore';

// Type definition for the config parameter
interface OrangeAPIConfigParams {
  id: number;
  userId: number;
  clientId: string;
  clientSecret: string;
  isAdmin: boolean;
  createdAt: string;
  updatedAt: string;
}

// GraphQL queries and mutations
const GET_ORANGE_API_CONFIGS = `
  query GetOrangeAPIConfigs {
    orangeAPIConfigs {
      id
      userId
      clientId
      clientSecret
      isAdmin
      createdAt
      updatedAt
    }
  }
`;

const GET_ORANGE_API_CONFIGS_BY_USER = `
  query GetOrangeAPIConfigsByUser($userId: Int!) {
    orangeAPIConfigsByUser(userId: $userId) {
      id
      userId
      clientId
      clientSecret
      isAdmin
      createdAt
      updatedAt
    }
  }
`;

const GET_ORANGE_API_CONFIG = `
  query GetOrangeAPIConfig($id: Int!) {
    orangeAPIConfig(id: $id) {
      id
      userId
      clientId
      clientSecret
      isAdmin
      createdAt
      updatedAt
    }
  }
`;

const GET_ADMIN_ORANGE_API_CONFIG = `
  query GetAdminOrangeAPIConfig {
    adminOrangeAPIConfig {
      id
      userId
      clientId
      clientSecret
      isAdmin
      createdAt
      updatedAt
    }
  }
`;

const CREATE_ORANGE_API_CONFIG = `
  mutation CreateOrangeAPIConfig($userId: Int!, $clientId: String!, $clientSecret: String!, $isAdmin: Boolean) {
    createOrangeAPIConfig(userId: $userId, clientId: $clientId, clientSecret: $clientSecret, isAdmin: $isAdmin) {
      id
      userId
      clientId
      clientSecret
      isAdmin
      createdAt
      updatedAt
    }
  }
`;

const UPDATE_ORANGE_API_CONFIG = `
  mutation UpdateOrangeAPIConfig($id: Int!, $clientId: String!, $clientSecret: String!) {
    updateOrangeAPIConfig(id: $id, clientId: $clientId, clientSecret: $clientSecret) {
      id
      userId
      clientId
      clientSecret
      isAdmin
      createdAt
      updatedAt
    }
  }
`;

const DELETE_ORANGE_API_CONFIG = `
  mutation DeleteOrangeAPIConfig($id: Int!) {
    deleteOrangeAPIConfig(id: $id)
  }
`;

// Store definition
export const useOrangeAPIConfigStore = defineStore('orangeAPIConfig', () => {
  // State
  const orangeAPIConfigs = ref<OrangeAPIConfig[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);
  const currentOrangeAPIConfig = ref<OrangeAPIConfig | null>(null);
  
  // Getters
  const getOrangeAPIConfigById = computed(() => {
    return (id: number) => orangeAPIConfigs.value.find(config => config.id === id);
  });
  
  const getOrangeAPIConfigsByUser = computed(() => {
    return (userId: number) => orangeAPIConfigs.value.filter(config => config.userId === userId);
  });
  
  const adminOrangeAPIConfig = computed(() => {
    return orangeAPIConfigs.value.find(config => config.isAdmin === true) || null;
  });
  
  const userOrangeAPIConfigs = computed(() => {
    return orangeAPIConfigs.value.filter(config => config.isAdmin === false);
  });
  
  // Actions
  async function fetchOrangeAPIConfigs() {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          query: GET_ORANGE_API_CONFIGS
        }),
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      orangeAPIConfigs.value = result.data.orangeAPIConfigs;
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Une erreur est survenue lors de la récupération des configurations API Orange';
      notification.error(error.value);
    } finally {
      loading.value = false;
    }
  }
  
  async function fetchOrangeAPIConfigsByUser(userId: number) {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          query: GET_ORANGE_API_CONFIGS_BY_USER,
          variables: { userId }
        }),
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      return result.data.orangeAPIConfigsByUser;
    } catch (err) {
      error.value = err instanceof Error ? err.message : `Une erreur est survenue lors de la récupération des configurations API Orange pour l'utilisateur #${userId}`;
      notification.error(error.value);
      return [];
    } finally {
      loading.value = false;
    }
  }
  
  async function fetchOrangeAPIConfig(id: number) {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          query: GET_ORANGE_API_CONFIG,
          variables: { id }
        }),
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      currentOrangeAPIConfig.value = result.data.orangeAPIConfig;
      return result.data.orangeAPIConfig;
    } catch (err) {
      error.value = err instanceof Error ? err.message : `Une erreur est survenue lors de la récupération de la configuration API Orange #${id}`;
      notification.error(error.value);
      return null;
    } finally {
      loading.value = false;
    }
  }
  
  async function fetchAdminOrangeAPIConfig() {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          query: GET_ADMIN_ORANGE_API_CONFIG
        }),
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      return result.data.adminOrangeAPIConfig;
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Une erreur est survenue lors de la récupération de la configuration API Orange administrateur';
      notification.error(error.value);
      return null;
    } finally {
      loading.value = false;
    }
  }
  
  async function createOrangeAPIConfig(userId: number, clientId: string, clientSecret: string, isAdmin: boolean = false) {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          query: CREATE_ORANGE_API_CONFIG,
          variables: { userId, clientId, clientSecret, isAdmin }
        }),
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      const newOrangeAPIConfig = result.data.createOrangeAPIConfig;
      orangeAPIConfigs.value.push(newOrangeAPIConfig);
      notification.success(`La configuration API Orange a été créée avec succès`);
      return newOrangeAPIConfig;
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Une erreur est survenue lors de la création de la configuration API Orange';
      notification.error(error.value);
      return null;
    } finally {
      loading.value = false;
    }
  }
  
  async function updateOrangeAPIConfig(id: number, clientId: string, clientSecret: string) {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          query: UPDATE_ORANGE_API_CONFIG,
          variables: { id, clientId, clientSecret }
        }),
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      const updatedOrangeAPIConfig = result.data.updateOrangeAPIConfig;
      const index = orangeAPIConfigs.value.findIndex(config => config.id === id);
      
      if (index !== -1) {
        orangeAPIConfigs.value[index] = updatedOrangeAPIConfig;
      }
      
      notification.success(`La configuration API Orange a été mise à jour avec succès`);
      return updatedOrangeAPIConfig;
    } catch (err) {
      error.value = err instanceof Error ? err.message : `Une erreur est survenue lors de la mise à jour de la configuration API Orange #${id}`;
      notification.error(error.value);
      return null;
    } finally {
      loading.value = false;
    }
  }
  
  async function deleteOrangeAPIConfig(id: number) {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          query: DELETE_ORANGE_API_CONFIG,
          variables: { id }
        }),
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      const success = result.data.deleteOrangeAPIConfig;
      
      if (success) {
        orangeAPIConfigs.value = orangeAPIConfigs.value.filter(config => config.id !== id);
        notification.success(`La configuration API Orange a été supprimée avec succès`);
      } else {
        throw new Error('La suppression a échoué');
      }
      
      return success;
    } catch (err) {
      error.value = err instanceof Error ? err.message : `Une erreur est survenue lors de la suppression de la configuration API Orange #${id}`;
      notification.error(error.value);
      return false;
    } finally {
      loading.value = false;
    }
  }
  
  // Helper function to mask sensitive data
  function maskClientSecret(secret: string): string {
    if (!secret) return '';
    if (secret.length <= 8) return '*'.repeat(secret.length);
    return secret.substring(0, 4) + '*'.repeat(secret.length - 8) + secret.substring(secret.length - 4);
  }
  
  return {
    // State
    orangeAPIConfigs,
    loading,
    error,
    currentOrangeAPIConfig,
    
    // Getters
    getOrangeAPIConfigById,
    getOrangeAPIConfigsByUser,
    adminOrangeAPIConfig,
    userOrangeAPIConfigs,
    
    // Actions
    fetchOrangeAPIConfigs,
    fetchOrangeAPIConfigsByUser,
    fetchOrangeAPIConfig,
    fetchAdminOrangeAPIConfig,
    createOrangeAPIConfig,
    updateOrangeAPIConfig,
    deleteOrangeAPIConfig,
    
    // Helpers
    maskClientSecret
  };
});
