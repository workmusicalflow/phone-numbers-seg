import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import notification from '../services/NotificationService';
import { useDashboardStore } from './dashboardStore';

// Types
export interface User {
  id: number;
  username: string;
  email: string | null;
  smsCredit: number;
  smsLimit: number | null;
  isAdmin: boolean;
  createdAt: string;
  updatedAt: string;
}

export interface SenderName {
  id: number;
  userId: number;
  name: string;
  status: 'pending' | 'approved' | 'rejected';
  createdAt: string;
  updatedAt: string;
}

export interface SMSOrder {
  id: number;
  userId: number;
  quantity: number;
  status: 'pending' | 'completed';
  createdAt: string;
  updatedAt: string;
}

export interface OrangeAPIConfig {
  id: number;
  userId: number;
  clientId: string;
  clientSecret: string;
  isAdmin: boolean;
  createdAt: string;
  updatedAt: string;
}

// GraphQL queries and mutations
const GET_USERS = `
  query GetUsers {
    users {
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

const GET_USER = `
  query GetUser($id: Int!) {
    user(id: $id) {
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

const CREATE_USER = `
  mutation CreateUser($username: String!, $password: String!, $email: String, $smsCredit: Int, $smsLimit: Int, $isAdmin: Boolean) {
    createUser(username: $username, password: $password, email: $email, smsCredit: $smsCredit, smsLimit: $smsLimit, isAdmin: $isAdmin) {
      id
      username
      email
      smsCredit
      smsLimit
      createdAt
      updatedAt
      isAdmin
    }
  }
`;

const UPDATE_USER = `
  mutation UpdateUser($id: Int!, $email: String, $smsLimit: Int, $isAdmin: Boolean) {
    updateUser(id: $id, email: $email, smsLimit: $smsLimit, isAdmin: $isAdmin) {
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

const CHANGE_PASSWORD = `
  mutation ChangePassword($id: Int!, $newPassword: String!) {
    changePassword(id: $id, newPassword: $newPassword) {
      id
      username
    }
  }
`;

const ADD_CREDITS = `
  mutation AddCredits($id: ID!, $amount: Int!) {
    addCredits(id: $id, amount: $amount) {
      id
      username
      smsCredit
    }
  }
`;

const DELETE_USER = `
  mutation DeleteUser($id: Int!) {
    deleteUser(id: $id)
  }
`;

// Store definition
export const useUserStore = defineStore('user', () => {
  // notification est déjà importé en haut du fichier
  
  // State
  const users = ref<User[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);
  const currentUser = ref<User | null>(null);
  
// Getters
const getUserById = computed(() => {
  return (id: number) => users.value.find(user => user.id === id);
});

const totalUsers = computed(() => users.value.length);

const totalSmsCredits = computed(() => {
  return users.value.reduce((total, user) => total + user.smsCredit, 0);
});

const isAdmin = computed(() => {
  return currentUser.value?.isAdmin === true;
});
  
  // Actions
  async function fetchUsers() {
    loading.value = true;
    error.value = null;
    
    try {
      console.log('Fetching users...');
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          query: GET_USERS
        }),
      });
      
      const result = await response.json();
      console.log('GraphQL response:', result);
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      users.value = result.data.users;
      console.log('Users fetched:', users.value);
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Une erreur est survenue lors de la récupération des utilisateurs';
      notification.error(error.value);
    } finally {
      loading.value = false;
    }
  }
  
  async function fetchUser(id: number) {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          query: GET_USER,
          variables: { id }
        }),
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      currentUser.value = result.data.user;
      return result.data.user;
    } catch (err) {
      error.value = err instanceof Error ? err.message : `Une erreur est survenue lors de la récupération de l'utilisateur #${id}`;
      notification.error(error.value);
      return null;
    } finally {
      loading.value = false;
    }
  }
  
  async function createUser(username: string, password: string, email?: string, smsCredit?: number, smsLimit?: number, isAdmin?: boolean) {
    loading.value = true;
    error.value = null;
    
    try {
      console.log('Creating user:', { username, email, smsCredit, smsLimit, isAdmin });
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          query: CREATE_USER,
          variables: { 
            username, 
            password, 
            email: email || null, 
            smsCredit: smsCredit || 10, 
            smsLimit: smsLimit || null,
            isAdmin: isAdmin || false
          }
        }),
      });
      
      const result = await response.json();
      console.log('Create user response:', result);
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      const newUser = result.data.createUser;
      console.log('New user created:', newUser);
      users.value.push(newUser);
      console.log('Updated users array:', users.value);
      notification.success(`L'utilisateur ${username} a été créé avec succès`);
      
      // Refresh dashboard data after user creation
      const dashboardStore = useDashboardStore();
      await Promise.all([
        dashboardStore.fetchDashboardStats(),
        dashboardStore.fetchRecentActivity()
      ]);
      
      return newUser;
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Une erreur est survenue lors de la création de l\'utilisateur';
      notification.error(error.value);
      return null;
    } finally {
      loading.value = false;
    }
  }
  
  async function updateUser(id: number, email?: string, smsLimit?: number, isAdmin?: boolean) {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          query: UPDATE_USER,
          variables: { 
            id, 
            email: email || null, 
            smsLimit: smsLimit || null,
            isAdmin: isAdmin
          }
        }),
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      const updatedUser = result.data.updateUser;
      const index = users.value.findIndex(user => user.id === id);
      
      if (index !== -1) {
        users.value[index] = updatedUser;
      }
      
      notification.success(`L'utilisateur a été mis à jour avec succès`);
      return updatedUser;
    } catch (err) {
      error.value = err instanceof Error ? err.message : `Une erreur est survenue lors de la mise à jour de l'utilisateur #${id}`;
      notification.error(error.value);
      return null;
    } finally {
      loading.value = false;
    }
  }
  
  async function changePassword(id: number, newPassword: string) {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          query: CHANGE_PASSWORD,
          variables: { id, newPassword }
        }),
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      notification.success(`Le mot de passe a été changé avec succès`);
      return true;
    } catch (err) {
      error.value = err instanceof Error ? err.message : `Une erreur est survenue lors du changement de mot de passe pour l'utilisateur #${id}`;
      notification.error(error.value);
      return false;
    } finally {
      loading.value = false;
    }
  }
  
  async function addCredits(id: number, amount: number) {
    loading.value = true;
    error.value = null;
    
    console.log(`Ajout de ${amount} crédits à l'utilisateur #${id}`);
    
    try {
      const requestBody = JSON.stringify({
        query: ADD_CREDITS,
        variables: { id, amount }
      });
      
      console.log('Requête GraphQL:', requestBody);
      
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: requestBody,
      });
      
      const result = await response.json();
      console.log('Réponse GraphQL:', result);
      
      if (result.errors) {
        console.error('Erreurs GraphQL:', result.errors);
        throw new Error(result.errors[0].message);
      }
      
      const updatedUser = result.data.addCredits;
      console.log('Utilisateur mis à jour:', updatedUser);
      
      const index = users.value.findIndex(user => user.id === id);
      console.log(`Index de l'utilisateur dans le tableau: ${index}`);
      
      if (index !== -1) {
        const oldCredit = users.value[index].smsCredit;
        users.value[index].smsCredit = updatedUser.smsCredit;
        console.log(`Crédit mis à jour: ${oldCredit} -> ${updatedUser.smsCredit}`);
        
        // Forcer la mise à jour de l'interface
        users.value = [...users.value];
      } else {
        console.warn(`Utilisateur #${id} non trouvé dans le tableau des utilisateurs`);
      }
      
      // Mettre à jour l'utilisateur courant si c'est le même
      if (currentUser.value && currentUser.value.id === id) {
        currentUser.value.smsCredit = updatedUser.smsCredit;
        console.log(`Utilisateur courant mis à jour: ${updatedUser.smsCredit} crédits`);
      }
      
      notification.success(`${amount} crédits SMS ont été ajoutés à l'utilisateur`);
      return updatedUser;
    } catch (err) {
      console.error('Erreur lors de l\'ajout de crédits:', err);
      error.value = err instanceof Error ? err.message : `Une erreur est survenue lors de l'ajout de crédits à l'utilisateur #${id}`;
      notification.error(error.value);
      return null;
    } finally {
      loading.value = false;
    }
  }
  
  async function deleteUser(id: number) {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          query: DELETE_USER,
          variables: { id }
        }),
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      const success = result.data.deleteUser;
      
      if (success) {
        users.value = users.value.filter(user => user.id !== id);
        notification.success(`L'utilisateur a été supprimé avec succès`);
      } else {
        throw new Error('La suppression a échoué');
      }
      
      return success;
    } catch (err) {
      error.value = err instanceof Error ? err.message : `Une erreur est survenue lors de la suppression de l'utilisateur #${id}`;
      notification.error(error.value);
      return false;
    } finally {
      loading.value = false;
    }
  }
  
  // Nouvelle méthode pour updateUserLimit
  async function updateUserLimit(id: number, smsLimit: number | undefined) {
    return updateUser(id, undefined, smsLimit);
  }
  
  return {
    // State
    users,
    loading,
    error,
    currentUser,
    
    // Getters
    getUserById,
    totalUsers,
    totalSmsCredits,
    isAdmin,
    
    // Actions
    fetchUsers,
    fetchUser,
    createUser,
    updateUser,
    changePassword,
    addCredits,
    deleteUser,
    
    // Nouvelle méthode
    updateUserLimit
  };
});
