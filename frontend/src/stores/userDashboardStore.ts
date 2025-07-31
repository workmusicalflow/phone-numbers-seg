import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { api } from '../services/api';

export interface SMSHistoryItem {
  id: number;
  recipient: string;
  message: string;
  status: string;
  sentAt: string;
}

export interface SegmentItem {
  id: number;
  name: string;
  description: string;
  count: number;
}

export interface UsageDataPoint {
  date: string;
  sent: number;
  delivered: number;
  failed: number;
}

export const useUserDashboardStore = defineStore('userDashboard', () => {
  // État
  const credits = ref(0);
  const recentSMS = ref<SMSHistoryItem[]>([]);
  const popularSegments = ref<SegmentItem[]>([]);
  const usageData = ref<UsageDataPoint[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);

  // Actions
  async function fetchDashboardData() {
    loading.value = true;
    error.value = null;

    try {
      const response = await api.query({
        query: `
          query GetUserDashboardData {
            userDashboard {
              credits
              recentSMS {
                id
                recipient
                message
                status
                sentAt
              }
              popularSegments {
                id
                name
                description
                count
              }
              usageData {
                date
                sent
                delivered
                failed
              }
            }
          }
        `
      });

      const data = response.data.userDashboard;
      
      credits.value = data.credits;
      recentSMS.value = data.recentSMS;
      popularSegments.value = data.popularSegments;
      usageData.value = data.usageData;
    } catch (err) {
      console.error('Erreur lors de la récupération des données du tableau de bord:', err);
      error.value = 'Impossible de charger les données du tableau de bord';
      
      // Données de démonstration en cas d'erreur
      credits.value = 100;
      recentSMS.value = getDemoSMSData();
      popularSegments.value = getDemoSegmentData();
      usageData.value = getDemoUsageData();
    } finally {
      loading.value = false;
    }
  }

  // Fonctions utilitaires pour les données de démonstration
  function getDemoSMSData(): SMSHistoryItem[] {
    return [
      {
        id: 1,
        recipient: '+22507123456',
        message: 'Votre code de confirmation est 123456',
        status: 'delivered',
        sentAt: new Date(Date.now() - 3600000).toISOString()
      },
      {
        id: 2,
        recipient: '+22508765432',
        message: 'Bienvenue chez Oracle SMS!',
        status: 'delivered',
        sentAt: new Date(Date.now() - 7200000).toISOString()
      },
      {
        id: 3,
        recipient: '+22509876543',
        message: 'Votre commande a été expédiée',
        status: 'pending',
        sentAt: new Date(Date.now() - 10800000).toISOString()
      },
      {
        id: 4,
        recipient: '+22501234567',
        message: 'Rappel: Rendez-vous demain à 10h',
        status: 'failed',
        sentAt: new Date(Date.now() - 14400000).toISOString()
      }
    ];
  }

  function getDemoSegmentData(): SegmentItem[] {
    return [
      {
        id: 1,
        name: 'Orange CI',
        description: 'Numéros Orange Côte d\'Ivoire',
        count: 156
      },
      {
        id: 2,
        name: 'MTN CI',
        description: 'Numéros MTN Côte d\'Ivoire',
        count: 89
      },
      {
        id: 3,
        name: 'Moov CI',
        description: 'Numéros Moov Côte d\'Ivoire',
        count: 42
      },
      {
        id: 4,
        name: 'Clients Premium',
        description: 'Segment personnalisé pour les clients premium',
        count: 27
      }
    ];
  }

  function getDemoUsageData(): UsageDataPoint[] {
    const data: UsageDataPoint[] = [];
    const now = new Date();
    
    for (let i = 6; i >= 0; i--) {
      const date = new Date(now);
      date.setDate(date.getDate() - i);
      
      data.push({
        date: date.toISOString().split('T')[0],
        sent: Math.floor(Math.random() * 50) + 10,
        delivered: Math.floor(Math.random() * 40) + 5,
        failed: Math.floor(Math.random() * 10)
      });
    }
    
    return data;
  }

  return {
    // État
    credits,
    recentSMS,
    popularSegments,
    usageData,
    loading,
    error,
    
    // Actions
    fetchDashboardData
  };
});
