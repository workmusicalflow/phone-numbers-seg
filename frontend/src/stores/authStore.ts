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
    # login mutation now returns User type directly
    login(username: $username, password: $password) { 
      id # Request fields directly on the returned User
      username
        email
        smsCredit
        smsLimit
        isAdmin
        createdAt
        updatedAt
    } 
  }
`;

const LOGOUT = `
  mutation Logout {
    logout # Returns Boolean! directly, no sub-fields
  }
`;

// const CHECK_AUTH = ` // Removed query as it doesn't exist in schema
//   query CheckAuth {
//     checkAuth {
//       authenticated
//       user {
//         id
//         username
//         email
//         smsCredit
//         smsLimit
//         isAdmin
//         createdAt
//         updatedAt
//       }
//     }
//   }
// `;

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
      // Consider login successful if no error and login data (which is the user) is returned
      const loggedInUser = result.data.login; 
      isAuthenticated.value = !!loggedInUser; 
      isAdmin.value = loggedInUser?.isAdmin ?? false;
      
      // Mettre à jour l'utilisateur courant
      userStore.currentUser = loggedInUser;
      
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
      
      // Réinitialiser l'état (toujours faire même si l'appel échoue côté serveur,
      // car l'intention est de se déconnecter)
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
  
  // Removed checkAuth function as the query doesn't exist
  // async function checkAuth(): Promise<boolean> { ... } 
  
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
      
      // resetPassword mutation returns Boolean! directly
      if (!result.data.resetPassword) { 
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
    // await checkAuth(); // Removed as checkAuth function was removed
    // We could potentially try a 'me' query here if a session cookie might exist,
    // but for now, let the navigation guard handle redirection if needed.
    console.log('Auth store initialized.');
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
    // checkAuth removed
    requestPasswordReset,
    resetPassword,
    init
  };
});
