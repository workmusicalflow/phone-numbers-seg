import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export interface DashboardStats {
  totalUsers: number;
  totalPhoneNumbers: number;
  totalSMSSent: number;
  totalCredits: number;
}

export interface Activity {
  type: 'sms' | 'user' | 'order' | 'senderName';
  description: string;
  date: string;
}

export interface PendingSenderName {
  id: number;
  name: string;
  username: string;
}

export interface PendingOrder {
  id: number;
  quantity: number;
  username: string;
}

export interface SMSChartData {
  labels: string[];
  data: number[];
}

export const useDashboardStore = defineStore('dashboard', () => {
  // État
  const loading = ref(false);
  const stats = ref<DashboardStats>({
    totalUsers: 0,
    totalPhoneNumbers: 0,
    totalSMSSent: 0,
    totalCredits: 0
  });
  const recentActivity = ref<Activity[]>([]);
  const pendingSenderNames = ref<PendingSenderName[]>([]);
  const pendingOrders = ref<PendingOrder[]>([]);
  const smsChartData = ref<SMSChartData>({
    labels: [],
    data: []
  });

  // Getters
  const hasPendingSenderNames = computed(() => pendingSenderNames.value.length > 0);
  const hasPendingOrders = computed(() => pendingOrders.value.length > 0);

  // Actions
  const fetchDashboardStats = async () => {
    loading.value = true;
    try {
      // Requête GraphQL pour récupérer les statistiques du tableau de bord
      const query = `
        query GetDashboardStats {
          dashboardStats {
            totalUsers
            totalPhoneNumbers
            totalSMSSent
            totalCredits
          }
        }
      `;

      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ query }),
      });

      const result = await response.json();
      
      if (result.data && result.data.dashboardStats) {
        stats.value = result.data.dashboardStats;
      } else {
        console.error('Erreur lors de la récupération des statistiques du tableau de bord:', result.errors);
        // Utiliser des données de démonstration en cas d'erreur
        stats.value = {
          totalUsers: 25,
          totalPhoneNumbers: 1250,
          totalSMSSent: 3750,
          totalCredits: 5000
        };
      }
    } catch (error) {
      console.error('Erreur lors de la récupération des statistiques du tableau de bord:', error);
      // Utiliser des données de démonstration en cas d'erreur
      stats.value = {
        totalUsers: 25,
        totalPhoneNumbers: 1250,
        totalSMSSent: 3750,
        totalCredits: 5000
      };
    } finally {
      loading.value = false;
    }
  };

  const fetchRecentActivity = async () => {
    loading.value = true;
    try {
      // Requête GraphQL pour récupérer l'activité récente
      const query = `
        query GetRecentActivity {
          recentActivity {
            type
            description
            date
          }
        }
      `;

      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ query }),
      });

      const result = await response.json();
      
      if (result.data && result.data.recentActivity) {
        recentActivity.value = result.data.recentActivity;
      } else {
        console.error('Erreur lors de la récupération de l\'activité récente:', result.errors);
        // Utiliser des données de démonstration en cas d'erreur
        recentActivity.value = [
          {
            type: 'user',
            description: 'Nouvel utilisateur créé: Jean Dupont',
            date: '2025-04-06T10:15:00'
          },
          {
            type: 'sms',
            description: '150 SMS envoyés par Marie Martin',
            date: '2025-04-06T09:30:00'
          },
          {
            type: 'order',
            description: 'Commande de 500 crédits par Pierre Durand',
            date: '2025-04-05T16:45:00'
          },
          {
            type: 'senderName',
            description: 'Nom d\'expéditeur "MarketingPro" approuvé',
            date: '2025-04-05T14:20:00'
          },
          {
            type: 'user',
            description: 'Nouvel utilisateur créé: Sophie Lefebvre',
            date: '2025-04-05T11:10:00'
          }
        ];
      }
    } catch (error) {
      console.error('Erreur lors de la récupération de l\'activité récente:', error);
      // Utiliser des données de démonstration en cas d'erreur
      recentActivity.value = [
        {
          type: 'user',
          description: 'Nouvel utilisateur créé: Jean Dupont',
          date: '2025-04-06T10:15:00'
        },
        {
          type: 'sms',
          description: '150 SMS envoyés par Marie Martin',
          date: '2025-04-06T09:30:00'
        },
        {
          type: 'order',
          description: 'Commande de 500 crédits par Pierre Durand',
          date: '2025-04-05T16:45:00'
        },
        {
          type: 'senderName',
          description: 'Nom d\'expéditeur "MarketingPro" approuvé',
          date: '2025-04-05T14:20:00'
        },
        {
          type: 'user',
          description: 'Nouvel utilisateur créé: Sophie Lefebvre',
          date: '2025-04-05T11:10:00'
        }
      ];
    } finally {
      loading.value = false;
    }
  };

  const fetchPendingSenderNames = async () => {
    loading.value = true;
    try {
      // Requête GraphQL pour récupérer les demandes de nom d'expéditeur en attente
      const query = `
        query GetPendingSenderNames {
          pendingSenderNames {
            id
            name
            username
          }
        }
      `;

      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ query }),
      });

      const result = await response.json();
      
      if (result.data && result.data.pendingSenderNames) {
        pendingSenderNames.value = result.data.pendingSenderNames;
      } else {
        console.error('Erreur lors de la récupération des demandes de nom d\'expéditeur en attente:', result.errors);
        // Utiliser des données de démonstration en cas d'erreur
        pendingSenderNames.value = [
          {
            id: 1,
            name: 'PromoShop',
            username: 'jean.dupont'
          },
          {
            id: 2,
            name: 'InfoAlert',
            username: 'marie.martin'
          },
          {
            id: 3,
            name: 'FastDelivery',
            username: 'pierre.durand'
          }
        ];
      }
    } catch (error) {
      console.error('Erreur lors de la récupération des demandes de nom d\'expéditeur en attente:', error);
      // Utiliser des données de démonstration en cas d'erreur
      pendingSenderNames.value = [
        {
          id: 1,
          name: 'PromoShop',
          username: 'jean.dupont'
        },
        {
          id: 2,
          name: 'InfoAlert',
          username: 'marie.martin'
        },
        {
          id: 3,
          name: 'FastDelivery',
          username: 'pierre.durand'
        }
      ];
    } finally {
      loading.value = false;
    }
  };

  const fetchPendingOrders = async () => {
    loading.value = true;
    try {
      // Requête GraphQL pour récupérer les commandes en attente
      const query = `
        query GetPendingOrders {
          pendingOrders {
            id
            quantity
            username
          }
        }
      `;

      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ query }),
      });

      const result = await response.json();
      
      if (result.data && result.data.pendingOrders) {
        pendingOrders.value = result.data.pendingOrders;
      } else {
        console.error('Erreur lors de la récupération des commandes en attente:', result.errors);
        // Utiliser des données de démonstration en cas d'erreur
        pendingOrders.value = [
          {
            id: 1,
            quantity: 500,
            username: 'jean.dupont'
          },
          {
            id: 2,
            quantity: 1000,
            username: 'marie.martin'
          },
          {
            id: 3,
            quantity: 250,
            username: 'sophie.lefebvre'
          }
        ];
      }
    } catch (error) {
      console.error('Erreur lors de la récupération des commandes en attente:', error);
      // Utiliser des données de démonstration en cas d'erreur
      pendingOrders.value = [
        {
          id: 1,
          quantity: 500,
          username: 'jean.dupont'
        },
        {
          id: 2,
          quantity: 1000,
          username: 'marie.martin'
        },
        {
          id: 3,
          quantity: 250,
          username: 'sophie.lefebvre'
        }
      ];
    } finally {
      loading.value = false;
    }
  };

  const fetchSMSChartData = async () => {
    loading.value = true;
    try {
      // Requête GraphQL pour récupérer les données du graphique SMS
      const query = `
        query GetSMSChartData {
          smsChartData {
            labels
            data
          }
        }
      `;

      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ query }),
      });

      const result = await response.json();
      
      if (result.data && result.data.smsChartData) {
        smsChartData.value = result.data.smsChartData;
      } else {
        console.error('Erreur lors de la récupération des données du graphique SMS:', result.errors);
        // Générer des données de démonstration en cas d'erreur
        const labels = [];
        const data = [];
        const now = new Date();
        
        for (let i = 29; i >= 0; i--) {
          const date = new Date(now);
          date.setDate(date.getDate() - i);
          labels.push(date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' }));
          
          // Générer des données aléatoires pour la démonstration
          data.push(Math.floor(Math.random() * 200) + 50);
        }
        
        smsChartData.value = { labels, data };
      }
    } catch (error) {
      console.error('Erreur lors de la récupération des données du graphique SMS:', error);
      // Générer des données de démonstration en cas d'erreur
      const labels = [];
      const data = [];
      const now = new Date();
      
      for (let i = 29; i >= 0; i--) {
        const date = new Date(now);
        date.setDate(date.getDate() - i);
        labels.push(date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' }));
        
        // Générer des données aléatoires pour la démonstration
        data.push(Math.floor(Math.random() * 200) + 50);
      }
      
      smsChartData.value = { labels, data };
    } finally {
      loading.value = false;
    }
  };

  const loadAllDashboardData = async () => {
    loading.value = true;
    try {
      await Promise.all([
        fetchDashboardStats(),
        fetchRecentActivity(),
        fetchPendingSenderNames(),
        fetchPendingOrders(),
        fetchSMSChartData()
      ]);
    } catch (error) {
      console.error('Erreur lors du chargement des données du tableau de bord:', error);
    } finally {
      loading.value = false;
    }
  };

  return {
    // État
    loading,
    stats,
    recentActivity,
    pendingSenderNames,
    pendingOrders,
    smsChartData,
    
    // Getters
    hasPendingSenderNames,
    hasPendingOrders,
    
    // Actions
    fetchDashboardStats,
    fetchRecentActivity,
    fetchPendingSenderNames,
    fetchPendingOrders,
    fetchSMSChartData,
    loadAllDashboardData
  };
});
