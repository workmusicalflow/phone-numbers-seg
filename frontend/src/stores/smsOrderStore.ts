import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { SMSOrder } from './userStore';

// GraphQL queries and mutations
const GET_SMS_ORDERS = `
  query GetSMSOrders {
    smsOrders {
      id
      userId
      quantity
      status
      createdAt
      updatedAt
    }
  }
`;

const GET_SMS_ORDERS_BY_USER = `
  query GetSMSOrdersByUser($userId: ID!) {
    smsOrdersByUser(userId: $userId) {
      id
      userId
      quantity
      status
      createdAt
      updatedAt
    }
  }
`;

const GET_SMS_ORDER = `
  query GetSMSOrder($id: ID!) {
    smsOrder(id: $id) {
      id
      userId
      quantity
      status
      createdAt
      updatedAt
    }
  }
`;

const CREATE_SMS_ORDER = `
  mutation CreateSMSOrder($userId: ID!, $quantity: Int!) {
    createSMSOrder(userId: $userId, quantity: $quantity) {
      id
      userId
      quantity
      status
      createdAt
      updatedAt
    }
  }
`;

const UPDATE_SMS_ORDER_STATUS = `
  mutation UpdateSMSOrderStatus($id: ID!, $status: String!) {
    updateSMSOrderStatus(id: $id, status: $status) {
      id
      userId
      quantity
      status
      createdAt
      updatedAt
    }
  }
`;

const DELETE_SMS_ORDER = `
  mutation DeleteSMSOrder($id: ID!) {
    deleteSMSOrder(id: $id)
  }
`;

// Store definition
export const useSMSOrderStore = defineStore('smsOrder', () => {
  // State
  const smsOrders = ref<SMSOrder[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);
  const currentSMSOrder = ref<SMSOrder | null>(null);
  
  // Getters
  const getSMSOrderById = computed(() => {
    return (id: number) => smsOrders.value.find(smsOrder => smsOrder.id === id);
  });
  
  const getSMSOrdersByUser = computed(() => {
    return (userId: number) => smsOrders.value.filter(smsOrder => smsOrder.userId === userId);
  });
  
  const getSMSOrdersByStatus = computed(() => {
    return (status: 'pending' | 'completed') => 
      smsOrders.value.filter(smsOrder => smsOrder.status === status);
  });
  
  const pendingSMSOrders = computed(() => {
    return smsOrders.value.filter(smsOrder => smsOrder.status === 'pending');
  });
  
  const completedSMSOrders = computed(() => {
    return smsOrders.value.filter(smsOrder => smsOrder.status === 'completed');
  });
  
  const totalOrderedQuantity = computed(() => {
    return smsOrders.value.reduce((total, order) => total + order.quantity, 0);
  });
  
  const totalPendingQuantity = computed(() => {
    return pendingSMSOrders.value.reduce((total, order) => total + order.quantity, 0);
  });
  
  const totalCompletedQuantity = computed(() => {
    return completedSMSOrders.value.reduce((total, order) => total + order.quantity, 0);
  });
  
  // Actions
  async function fetchSMSOrders() {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          query: GET_SMS_ORDERS
        }),
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      smsOrders.value = result.data.smsOrders;
      return true;
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Une erreur est survenue lors de la récupération des commandes SMS';
      console.error(error.value);
      return false;
    } finally {
      loading.value = false;
    }
  }
  
  async function fetchSMSOrdersByUser(userId: number) {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          query: GET_SMS_ORDERS_BY_USER,
          variables: { userId }
        }),
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      return result.data.smsOrdersByUser;
    } catch (err) {
      error.value = err instanceof Error ? err.message : `Une erreur est survenue lors de la récupération des commandes SMS pour l'utilisateur #${userId}`;
      console.error(error.value);
      return [];
    } finally {
      loading.value = false;
    }
  }
  
  async function fetchSMSOrder(id: number) {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          query: GET_SMS_ORDER,
          variables: { id }
        }),
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      currentSMSOrder.value = result.data.smsOrder;
      return result.data.smsOrder;
    } catch (err) {
      error.value = err instanceof Error ? err.message : `Une erreur est survenue lors de la récupération de la commande SMS #${id}`;
      console.error(error.value);
      return null;
    } finally {
      loading.value = false;
    }
  }
  
  async function createSMSOrder(userId: number, quantity: number) {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          query: CREATE_SMS_ORDER,
          variables: { userId, quantity }
        }),
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      const newSMSOrder = result.data.createSMSOrder;
      smsOrders.value.push(newSMSOrder);
      return newSMSOrder;
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Une erreur est survenue lors de la création de la commande SMS';
      console.error(error.value);
      return null;
    } finally {
      loading.value = false;
    }
  }
  
  async function updateSMSOrderStatus(id: number, status: 'pending' | 'completed') {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          query: UPDATE_SMS_ORDER_STATUS,
          variables: { id, status }
        }),
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      const updatedSMSOrder = result.data.updateSMSOrderStatus;
      const index = smsOrders.value.findIndex(smsOrder => smsOrder.id === id);
      
      if (index !== -1) {
        smsOrders.value[index] = updatedSMSOrder;
      }
      
      return updatedSMSOrder;
    } catch (err) {
      error.value = err instanceof Error ? err.message : `Une erreur est survenue lors de la mise à jour du statut de la commande SMS #${id}`;
      console.error(error.value);
      return null;
    } finally {
      loading.value = false;
    }
  }
  
  async function deleteSMSOrder(id: number) {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          query: DELETE_SMS_ORDER,
          variables: { id }
        }),
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      const success = result.data.deleteSMSOrder;
      
      if (success) {
        smsOrders.value = smsOrders.value.filter(smsOrder => smsOrder.id !== id);
      } else {
        throw new Error('La suppression a échoué');
      }
      
      return success;
    } catch (err) {
      error.value = err instanceof Error ? err.message : `Une erreur est survenue lors de la suppression de la commande SMS #${id}`;
      console.error(error.value);
      return false;
    } finally {
      loading.value = false;
    }
  }
  
  // Nouvelle méthode pour completeOrder
  async function completeOrder(id: number) {
    return updateSMSOrderStatus(id, 'completed');
  }
  
  return {
    // State
    smsOrders,
    loading,
    error,
    currentSMSOrder,
    
    // Getters
    getSMSOrderById,
    getSMSOrdersByUser,
    getSMSOrdersByStatus,
    pendingSMSOrders,
    completedSMSOrders,
    totalOrderedQuantity,
    totalPendingQuantity,
    totalCompletedQuantity,
    
    // Actions
    fetchSMSOrders,
    fetchSMSOrdersByUser,
    fetchSMSOrder,
    createSMSOrder,
    updateSMSOrderStatus,
    deleteSMSOrder,
    
    // Nouvelle méthode
    completeOrder
  };
});
