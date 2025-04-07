import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { useUserStore } from './userStore';
import notification from '../services/NotificationService';

// Types
export interface AuthState {
  isAuthenticated: boolean;
  isAdmin: boolean;
  loading: boolean;
  error: string | null;
}

// GraphQL queries and mutations
const LOGIN = `
  mutation Login($username: String!, $password: String!) {
    login(username: $username, password: $password) {
      token
      user {
        id
        username
        email
        smsCredit
        smsLimit
        isAdmin
        createdAt
        updatedAt
      }
    }
  }
`;

const LOGOUT = `
  mutation Logout {
    logout {
      success
    }
  }
`;

const CHECK_AUTH = `
  query CheckAuth {
    checkAuth {
      authenticated
      user {
        id
        username
        email
        smsCredit
        smsLimit
        isAdmin
        createdAt
        updatedAt
      }
    }
  }
`;

const REQUEST_PASSWORD_RESET = `
  mutation RequestPasswordReset($email: String!) {
    requestPasswordReset(email: $email)
  }
`;

const RESET_PASSWORD = `
  mutation ResetPassword($token: String!, $newPassword: String!) {
    resetPassword(token: $token, newPassword: $newPassword)
  }
`;

// Store definition
export const useAuthStore = defineStore('auth', () => {
  // State
  const isAuthenticated = ref<boolean>(false);
  const isAdmin = ref<boolean>(false);
  const loading = ref<boolean>(false);
  const error = ref<string | null>(null);
  
  // User store
  const userStore = useUserStore();
  
  // Getters
  const getIsAuthenticated = computed(() => isAuthenticated.value);
  const getIsAdmin = computed(() => isAdmin.value);
  
  // Actions
  async function login(username: string, password: string): Promise<boolean> {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          query: LOGIN,
          variables: { username, password }
        }),
        credentials: 'include' // Important pour inclure les cookies
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      // Mettre à jour l'état
      isAuthenticated.value = !!result.data.login.token;
      isAdmin.value = result.data.login.user.isAdmin;
      
      // Mettre à jour l'utilisateur courant
      userStore.currentUser = result.data.login.user;
      
      notification.success('Connexion réussie');
      return true;
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Une erreur est survenue lors de la connexion';
      notification.error(error.value);
      return false;
    } finally {
      loading.value = false;
    }
  }
  
  async function logout(): Promise<boolean> {
    loading.value = true;
    error.value = null;
    
    try {
      // Appeler la mutation de déconnexion
      await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          query: LOGOUT
        }),
        credentials: 'include' // Important pour inclure les cookies
      });
      
      // Réinitialiser l'état
      isAuthenticated.value = false;
      isAdmin.value = false;
      
      // Réinitialiser l'utilisateur courant
      userStore.currentUser = null;
      
      notification.success('Déconnexion réussie');
      return true;
    } catch (err) {
      // Même en cas d'erreur, on déconnecte l'utilisateur localement
      isAuthenticated.value = false;
      isAdmin.value = false;
      userStore.currentUser = null;
      
      return true;
    } finally {
      loading.value = false;
    }
  }
  
  async function checkAuth(): Promise<boolean> {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          query: CHECK_AUTH
        }),
        credentials: 'include' // Important pour inclure les cookies
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      // Mettre à jour l'état
      isAuthenticated.value = result.data.checkAuth.authenticated;
      
      if (isAuthenticated.value && result.data.checkAuth.user) {
        isAdmin.value = result.data.checkAuth.user.isAdmin;
        userStore.currentUser = result.data.checkAuth.user;
      } else {
        isAdmin.value = false;
        userStore.currentUser = null;
      }
      
      return isAuthenticated.value;
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Une erreur est survenue lors de la vérification de l\'authentification';
      
      // En cas d'erreur, considérer l'utilisateur comme non authentifié
      isAuthenticated.value = false;
      isAdmin.value = false;
      userStore.currentUser = null;
      
      return false;
    } finally {
      loading.value = false;
    }
  }
  
  async function requestPasswordReset(email: string): Promise<boolean> {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          query: REQUEST_PASSWORD_RESET,
          variables: { email }
        }),
        credentials: 'include' // Important pour inclure les cookies
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      notification.success('Un email de réinitialisation a été envoyé si l\'adresse existe dans notre système');
      return true;
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Une erreur est survenue lors de la demande de réinitialisation';
      notification.error(error.value);
      return false;
    } finally {
      loading.value = false;
    }
  }
  
  async function resetPassword(token: string, newPassword: string): Promise<boolean> {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          query: RESET_PASSWORD,
          variables: { token, newPassword }
        }),
        credentials: 'include' // Important pour inclure les cookies
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      if (!result.data.resetPassword.success) {
        throw new Error('La réinitialisation du mot de passe a échoué');
      }
      
      notification.success('Votre mot de passe a été réinitialisé avec succès');
      return true;
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Une erreur est survenue lors de la réinitialisation du mot de passe';
      notification.error(error.value);
      return false;
    } finally {
      loading.value = false;
    }
  }
  
  // Initialiser l'authentification au chargement de l'application
  async function init(): Promise<void> {
    await checkAuth();
  }
  
  return {
    // State
    isAuthenticated,
    isAdmin,
    loading,
    error,
    
    // Getters
    getIsAuthenticated,
    getIsAdmin,
    
    // Actions
    login,
    logout,
    checkAuth,
    requestPasswordReset,
    resetPassword,
    init
  };
});
