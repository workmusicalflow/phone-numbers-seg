 import { defineStore } from 'pinia';
import { ref, computed, watch } from 'vue'; // Added watch
// Notification logic moved to components
import { useDashboardStore } from './dashboardStore';
import { apolloClient, gql } from '../services/api'; // Ensure apolloClient and gql are imported

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
} // Added missing closing brace
// GraphQL queries and mutations - Updated GET_USERS
const GET_USERS = `
  query GetUsers($limit: Int, $offset: Int, $search: String) {
    users(limit: $limit, offset: $offset, search: $search) {
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
  query GetUser($id: ID!) {
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
  mutation UpdateUser($id: ID!, $email: String, $smsLimit: Int, $isAdmin: Boolean) {
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
  mutation ChangePassword($id: ID!, $newPassword: String!) {
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
  mutation DeleteUser($id: ID!) {
    deleteUser(id: $id)
  }
`;

// Store definition
export const useUserStore = defineStore('user', () => {
  // State
  const users = ref<User[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);
  const currentUser = ref<User | null>(null);
  const totalCount = ref(0); // Added for pagination
  const currentPage = ref(1); // Added for pagination
  const itemsPerPage = ref(10); // Added for pagination
  const searchTerm = ref(''); // Added for search

// Getters
const getUserById = computed(() => {
  return (id: number) => users.value.find(user => user.id === id);
});

const totalUsers = computed(() => users.value.length);

const totalSmsCredits = computed(() => {
  return users.value.reduce((total, user) => total + user.smsCredit, 0);
}); // Added missing closing brace

const isAdmin = computed(() => {
  return currentUser.value?.isAdmin === true;
}); // Added missing closing brace
  // Actions - Modified fetchUsers
  async function fetchUsers() {
    loading.value = true;
    error.value = null;

    try {
      const offset = (currentPage.value - 1) * itemsPerPage.value;
      const variables: { limit: number; offset: number; search?: string } = {
        limit: itemsPerPage.value,
        offset: offset,
      };
      if (searchTerm.value) {
        variables.search = searchTerm.value;
      }

      console.log('Fetching users with variables:', variables);
      // Use apolloClient for consistency
      const { data, errors } = await apolloClient.query({
        query: gql(GET_USERS), // Use updated GET_USERS query
        variables: variables,
        fetchPolicy: 'network-only' // Ensure fresh data
      });

      if (errors) {
        throw new Error(errors[0].message);
      }

      users.value = data.users;
      // Assuming the backend doesn't yet return total count for users query
      // We might need another query or modify the existing one later
      // For now, let's estimate totalCount based on whether a full page was returned
      // TODO: Implement backend usersCount(search: String) query for accurate total count.
      // For now, the pagination component should rely on the number of items returned
      // to determine if it's on the last page (if results < itemsPerPage).
      // We only set totalCount definitively if we are on the first page and get less than itemsPerPage.
      if (currentPage.value === 1 && users.value.length < itemsPerPage.value) {
        totalCount.value = users.value.length;
      } else {
        // Otherwise, we don't have an accurate total count.
        // Set a placeholder or let the component handle it.
        // Setting a large placeholder allows pagination controls to appear,
        // but the "last page" logic relies on the component seeing fewer results.
        totalCount.value = (currentPage.value -1) * itemsPerPage.value + users.value.length + (users.value.length === itemsPerPage.value ? 1 : 0); // Estimate slightly higher if full page returned
      }


      console.log('Users fetched:', users.value.length, 'Estimated Total Count:', totalCount.value);

    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Une erreur est survenue lors de la récupération des utilisateurs';
      console.error('Error fetching users:', error.value);
    } finally {
      loading.value = false;
    }
  }

  // Removed placeholder fetchUsersCount function

  async function fetchUser(id: number) {
    loading.value = true;
    error.value = null;
    
    try {
      // Use apolloClient instead of fetch
      const { data, errors } = await apolloClient.query({
        query: gql(GET_USER), // Use gql tag
        variables: { id: id.toString() }, // Convert number to string for ID! type
        fetchPolicy: 'network-only' // Ensure fresh data is fetched
      });

      if (errors) {
        throw new Error(errors[0].message);
      }
      
      currentUser.value = data.user;
      return data.user;
    } catch (err) {
      error.value = err instanceof Error ? err.message : `Une erreur est survenue lors de la récupération de l'utilisateur #${id}`;
      // notifyError(error.value); // Removed
      console.error(error.value); // Log error instead
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
      // notifySuccess(`L'utilisateur ${username} a été créé avec succès`); // Removed
      
      // Refresh dashboard data after user creation
      const dashboardStore = useDashboardStore();
      await Promise.all([
        dashboardStore.fetchDashboardStats(),
        dashboardStore.fetchRecentActivity()
      ]);
      
      return newUser;
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Une erreur est survenue lors de la création de l\'utilisateur';
      // notifyError(error.value); // Removed
      console.error(error.value); // Log error instead
      throw err; // Re-throw error for component to handle
      // return null;
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
            id: id.toString(), // Convert to string for ID! type
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
      
      // notifySuccess(`L'utilisateur a été mis à jour avec succès`); // Removed
      return updatedUser;
    } catch (err) {
      error.value = err instanceof Error ? err.message : `Une erreur est survenue lors de la mise à jour de l'utilisateur #${id}`;
      // notifyError(error.value); // Removed
      console.error(error.value); // Log error instead
      throw err; // Re-throw error
      // return null;
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
          variables: { id: id.toString(), newPassword }
        }),
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      // notifySuccess(`Le mot de passe a été changé avec succès`); // Removed
      return true;
    } catch (err) {
      error.value = err instanceof Error ? err.message : `Une erreur est survenue lors du changement de mot de passe pour l'utilisateur #${id}`;
      // notifyError(error.value); // Removed
      console.error(error.value); // Log error instead
      throw err; // Re-throw error
      // return false;
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
        variables: { id: id.toString(), amount }
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
      
      // notifySuccess(`${amount} crédits SMS ont été ajoutés à l'utilisateur`); // Removed
      return updatedUser;
    } catch (err) {
      console.error('Erreur lors de l\'ajout de crédits:', err);
      error.value = err instanceof Error ? err.message : `Une erreur est survenue lors de l'ajout de crédits à l'utilisateur #${id}`;
      // notifyError(error.value); // Removed
      console.error(error.value); // Log error instead
      throw err; // Re-throw error
      // return null;
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
          variables: { id: id.toString() }
        }),
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      const success = result.data.deleteUser;
      
      if (success) {
        users.value = users.value.filter(user => user.id !== id);
        // notifySuccess(`L'utilisateur a été supprimé avec succès`); // Removed
      } else {
        throw new Error('La suppression a échoué');
      }
      
      return success;
    } catch (err) {
      error.value = err instanceof Error ? err.message : `Une erreur est survenue lors de la suppression de l'utilisateur #${id}`;
      // notifyError(error.value); // Removed
      console.error(error.value); // Log error instead
      throw err; // Re-throw error
      // return false;
    } finally {
      loading.value = false;
    }
  }
  
  // Nouvelle méthode pour updateUserLimit
  async function updateUserLimit(id: number, smsLimit: number | undefined) {
    return updateUser(id, undefined, smsLimit);
  }

  // --- Pagination & Search Actions ---

  function setPage(page: number) {
    if (page > 0) {
      currentPage.value = page;
      fetchUsers(); // Refetch users for the new page
    }
  }

  function setItemsPerPage(count: number) {
    if (count > 0) {
      itemsPerPage.value = count;
      currentPage.value = 1; // Reset to first page when changing items per page
      fetchUsers(); // Refetch users with new limit
    }
  }

  // Debounced search function
  let searchTimeout: ReturnType<typeof setTimeout> | null = null;
  function searchUsers(term: string) {
    if (searchTimeout) {
      clearTimeout(searchTimeout);
    }
    searchTimeout = setTimeout(() => {
      searchTerm.value = term || ''; // Ensure empty string if null/undefined
      currentPage.value = 1; // Reset to first page on new search
      fetchUsers(); // Fetch users with the new search term
    }, 300); // 300ms debounce
  }

  // Watch for changes that require refetching - uncomment if needed after testing
  // watch([currentPage, itemsPerPage], () => fetchUsers());
  // watch(searchTerm, () => { currentPage.value = 1; fetchUsers(); });


  return {
    // State
    users,
    loading,
    error,
    currentUser,
    totalCount,     // Added
    currentPage,    // Added
    itemsPerPage,   // Added
    searchTerm,     // Added

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
    updateUserLimit,

    // New Pagination/Search Actions
    setPage,
    setItemsPerPage,
    searchUsers
  };
});
