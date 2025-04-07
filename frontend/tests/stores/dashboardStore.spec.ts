import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useDashboardStore } from '../../src/stores/dashboardStore';

// Mock fetch
global.fetch = vi.fn();

describe('dashboardStore', () => {
  let store: ReturnType<typeof useDashboardStore>;

  beforeEach(() => {
    // Create a fresh Pinia instance for each test
    setActivePinia(createPinia());
    
    // Reset mocks
    vi.resetAllMocks();
    
    // Create the store
    store = useDashboardStore();
  });

  afterEach(() => {
    vi.clearAllMocks();
  });

  describe('fetchDashboardStats', () => {
    it('should fetch dashboard stats successfully', async () => {
      // Mock successful API response
      const mockResponse = {
        data: {
          dashboardStats: {
            totalUsers: 25,
            totalPhoneNumbers: 1250,
            totalSMSSent: 3750,
            totalCredits: 5000
          }
        }
      };
      
      // Setup fetch mock
      (global.fetch as any).mockResolvedValueOnce({
        json: () => Promise.resolve(mockResponse),
      });
      
      // Call the action
      await store.fetchDashboardStats();
      
      // Verify fetch was called with correct arguments
      expect(global.fetch).toHaveBeenCalledWith('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: expect.any(String),
      });
      
      // Verify store state was updated correctly
      expect(store.stats).toEqual(mockResponse.data.dashboardStats);
      expect(store.loading).toBe(false);
    });

    it('should handle API error gracefully', async () => {
      // Mock API error
      (global.fetch as any).mockRejectedValueOnce(new Error('API Error'));
      
      // Call the action
      await store.fetchDashboardStats();
      
      // Verify fetch was called
      expect(global.fetch).toHaveBeenCalled();
      
      // Verify store state has fallback values
      expect(store.stats).toEqual({
        totalUsers: 25,
        totalPhoneNumbers: 1250,
        totalSMSSent: 3750,
        totalCredits: 5000
      });
      expect(store.loading).toBe(false);
    });
  });

  describe('fetchRecentActivity', () => {
    it('should fetch recent activity successfully', async () => {
      // Mock successful API response
      const mockActivity = [
        {
          type: 'user',
          description: 'Nouvel utilisateur créé: Jean Dupont',
          date: '2025-04-06T10:15:00'
        }
      ];
      
      const mockResponse = {
        data: {
          recentActivity: mockActivity
        }
      };
      
      // Setup fetch mock
      (global.fetch as any).mockResolvedValueOnce({
        json: () => Promise.resolve(mockResponse),
      });
      
      // Call the action
      await store.fetchRecentActivity();
      
      // Verify fetch was called with correct arguments
      expect(global.fetch).toHaveBeenCalledWith('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: expect.any(String),
      });
      
      // Verify store state was updated correctly
      expect(store.recentActivity).toEqual(mockActivity);
      expect(store.loading).toBe(false);
    });
  });

  describe('fetchPendingSenderNames', () => {
    it('should fetch pending sender names successfully', async () => {
      // Mock successful API response
      const mockPendingSenderNames = [
        {
          id: 1,
          name: 'PromoShop',
          username: 'jean.dupont'
        }
      ];
      
      const mockResponse = {
        data: {
          pendingSenderNames: mockPendingSenderNames
        }
      };
      
      // Setup fetch mock
      (global.fetch as any).mockResolvedValueOnce({
        json: () => Promise.resolve(mockResponse),
      });
      
      // Call the action
      await store.fetchPendingSenderNames();
      
      // Verify fetch was called with correct arguments
      expect(global.fetch).toHaveBeenCalledWith('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: expect.any(String),
      });
      
      // Verify store state was updated correctly
      expect(store.pendingSenderNames).toEqual(mockPendingSenderNames);
      expect(store.loading).toBe(false);
    });
  });

  describe('fetchPendingOrders', () => {
    it('should fetch pending orders successfully', async () => {
      // Mock successful API response
      const mockPendingOrders = [
        {
          id: 1,
          quantity: 500,
          username: 'jean.dupont'
        }
      ];
      
      const mockResponse = {
        data: {
          pendingOrders: mockPendingOrders
        }
      };
      
      // Setup fetch mock
      (global.fetch as any).mockResolvedValueOnce({
        json: () => Promise.resolve(mockResponse),
      });
      
      // Call the action
      await store.fetchPendingOrders();
      
      // Verify fetch was called with correct arguments
      expect(global.fetch).toHaveBeenCalledWith('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: expect.any(String),
      });
      
      // Verify store state was updated correctly
      expect(store.pendingOrders).toEqual(mockPendingOrders);
      expect(store.loading).toBe(false);
    });
  });

  describe('fetchSMSChartData', () => {
    it('should fetch SMS chart data successfully', async () => {
      // Mock successful API response
      const mockChartData = {
        labels: ['01/04', '02/04'],
        data: [120, 150]
      };
      
      const mockResponse = {
        data: {
          smsChartData: mockChartData
        }
      };
      
      // Setup fetch mock
      (global.fetch as any).mockResolvedValueOnce({
        json: () => Promise.resolve(mockResponse),
      });
      
      // Call the action
      await store.fetchSMSChartData();
      
      // Verify fetch was called with correct arguments
      expect(global.fetch).toHaveBeenCalledWith('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: expect.any(String),
      });
      
      // Verify store state was updated correctly
      expect(store.smsChartData).toEqual(mockChartData);
      expect(store.loading).toBe(false);
    });
  });

  describe('loadAllDashboardData', () => {
    it('should load all dashboard data', async () => {
      // Mock all the individual fetch methods
      vi.spyOn(store, 'fetchDashboardStats').mockResolvedValueOnce();
      vi.spyOn(store, 'fetchRecentActivity').mockResolvedValueOnce();
      vi.spyOn(store, 'fetchPendingSenderNames').mockResolvedValueOnce();
      vi.spyOn(store, 'fetchPendingOrders').mockResolvedValueOnce();
      vi.spyOn(store, 'fetchSMSChartData').mockResolvedValueOnce();
      
      // Call the action
      await store.loadAllDashboardData();
      
      // Verify all fetch methods were called
      expect(store.fetchDashboardStats).toHaveBeenCalled();
      expect(store.fetchRecentActivity).toHaveBeenCalled();
      expect(store.fetchPendingSenderNames).toHaveBeenCalled();
      expect(store.fetchPendingOrders).toHaveBeenCalled();
      expect(store.fetchSMSChartData).toHaveBeenCalled();
      
      // Verify loading state is false
      expect(store.loading).toBe(false);
    });

    it('should handle errors during loading', async () => {
      // Mock fetchDashboardStats to throw an error
      vi.spyOn(store, 'fetchDashboardStats').mockRejectedValueOnce(new Error('API Error'));
      
      // Mock console.error
      const consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
      
      // Call the action
      await store.loadAllDashboardData();
      
      // Verify error was logged
      expect(consoleErrorSpy).toHaveBeenCalled();
      
      // Verify loading state is false
      expect(store.loading).toBe(false);
    });
  });

  describe('getters', () => {
    it('hasPendingSenderNames should return true when there are pending sender names', () => {
      store.pendingSenderNames = [{ id: 1, name: 'Test', username: 'test' }];
      expect(store.hasPendingSenderNames).toBe(true);
    });

    it('hasPendingSenderNames should return false when there are no pending sender names', () => {
      store.pendingSenderNames = [];
      expect(store.hasPendingSenderNames).toBe(false);
    });

    it('hasPendingOrders should return true when there are pending orders', () => {
      store.pendingOrders = [{ id: 1, quantity: 100, username: 'test' }];
      expect(store.hasPendingOrders).toBe(true);
    });

    it('hasPendingOrders should return false when there are no pending orders', () => {
      store.pendingOrders = [];
      expect(store.hasPendingOrders).toBe(false);
    });
  });
});
