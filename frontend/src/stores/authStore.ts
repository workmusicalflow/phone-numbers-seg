import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { useUserStore } from './userStore';
// Notification logic moved to components

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
    # login mutation now returns Boolean!
    login(username: $username, password: $password) 
  }
`;

const ME_QUERY = `
  query Me {
    me {
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
      console.log('Attempting login for user:', username);
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
      
      if (!response.ok) {
        console.error('Network response was not ok:', response.status, response.statusText);
        throw new Error(`Network error: ${response.status} ${response.statusText}`);
      }
      
      const result = await response.json();
      console.log('Login response:', result);
      
      if (result.errors) {
        console.error('GraphQL errors:', result.errors);
        throw new Error(result.errors[0].message);
      }
      
      // Mettre à jour l'état basé sur le booléen retourné
      const loginSuccess = result.data.login; 
      isAuthenticated.value = loginSuccess; 
      
      if (loginSuccess) {
        console.log('Login successful, fetching user info');
        // Si le login réussit, récupérer les infos utilisateur avec une query 'me'
        try {
          const meResponse = await fetch('/graphql.php', {
             method: 'POST',
             headers: { 'Content-Type': 'application/json' },
             body: JSON.stringify({ query: ME_QUERY }),
             credentials: 'include' 
          });
          
          if (!meResponse.ok) {
            console.error('Network response for me query was not ok:', meResponse.status, meResponse.statusText);
            throw new Error(`Network error: ${meResponse.status} ${meResponse.statusText}`);
          }
          
          const meResult = await meResponse.json();
          console.log('Me query response:', meResult);
          
          if (meResult.errors) {
             console.error('GraphQL errors in me query:', meResult.errors);
             throw new Error(meResult.errors[0].message);
          }
          
          if (meResult.data.me) {
             userStore.currentUser = meResult.data.me;
             isAdmin.value = meResult.data.me.isAdmin;
             console.log('User info retrieved successfully:', meResult.data.me);
             // notification.success('Connexion réussie'); // Removed
             return true;
          } else {
             // Should not happen if login succeeded and session is set
             console.error('Me query returned null user after successful login');
             throw new Error("Impossible de récupérer les informations utilisateur après connexion.");
          }
        } catch (meErr) {
           // Échec de la récupération des infos utilisateur, annuler le login
           console.error('Error fetching user info after login:', meErr);
           isAuthenticated.value = false;
           isAdmin.value = false;
           userStore.currentUser = null;
           error.value = meErr instanceof Error ? meErr.message : 'Erreur post-connexion';
           // notification.error(error.value); // Removed
           // Re-throw the error so the component knows login failed at this stage
           throw new Error(error.value); 
           // return false; // No longer needed
        }
      } else {
         // Login a retourné false (échec d'authentification)
         console.error('Login returned false (authentication failed)');
         throw new Error("Nom d'utilisateur ou mot de passe incorrect");
      }
    } catch (err) {
      console.error('Login error:', err);
      error.value = err instanceof Error ? err.message : 'Une erreur est survenue lors de la connexion';
      // notification.error(error.value); // Removed
      // Let the component handle displaying the error based on the thrown exception
      throw err; // Re-throw the error
      // return false; // No longer needed
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
      
      // notification.success('Déconnexion réussie'); // Removed - component can show this if needed
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
  
  // Check authentication using the 'me' query
  async function checkAuth(): Promise<boolean> {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ query: ME_QUERY }),
        credentials: 'include'
      });
      
      if (!response.ok) {
        throw new Error(`Network error: ${response.status}`);
      }
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      if (result.data.me) {
        userStore.currentUser = result.data.me;
        isAuthenticated.value = true;
        isAdmin.value = result.data.me.isAdmin;
        return true;
      }
      
      isAuthenticated.value = false;
      isAdmin.value = false;
      userStore.currentUser = null;
      return false;
    } catch (err) {
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
      
      // notification.success('Un email de réinitialisation a été envoyé...'); // Removed
      return true;
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Une erreur est survenue lors de la demande de réinitialisation';
      // notification.error(error.value); // Removed
      throw err; // Re-throw
      // return false; // No longer needed
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
      
      // notification.success('Votre mot de passe a été réinitialisé avec succès'); // Removed
      return true;
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Une erreur est survenue lors de la réinitialisation du mot de passe';
      // notification.error(error.value); // Removed
      throw err; // Re-throw
      // return false; // No longer needed
    } finally {
      loading.value = false;
    }
  }
  
  // Initialiser l'authentification au chargement de l'application
  async function init(): Promise<void> {
    await checkAuth(); // Call checkAuth to verify session on app load
    console.log('Auth store initialized and checkAuth attempted.');
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
