import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useContactGroupStore } from '../../src/stores/contactGroupStore';

// Mock fetch global
const mockFetch = vi.fn();
global.fetch = mockFetch;

describe('contactGroupStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    mockFetch.mockClear();
  });

  afterEach(() => {
    vi.clearAllMocks();
  });

  describe('GraphQL Integration', () => {
    it('should make GraphQL request to fetch user groups', async () => {
      const store = useContactGroupStore();
      
      // Mock successful GraphQL response
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          data: {
            contactGroups: [
              {
                id: '1',
                name: 'Groupe Test',
                description: 'Description test',
                userId: 1,
                createdAt: '2024-01-01',
                updatedAt: '2024-01-01'
              }
            ]
          }
        })
      });

      await store.fetchUserGroups(1);

      // Verify GraphQL request was made correctly
      expect(mockFetch).toHaveBeenCalledWith('http://localhost:8000/graphql.php', 
        expect.objectContaining({
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          credentials: 'include',
          body: expect.stringContaining('query ContactGroups')
        })
      );

      // Verify store state was updated
      expect(store.userGroups).toHaveLength(1);
      expect(store.userGroups[0].name).toBe('Groupe Test');
    });

    it('should handle GraphQL errors properly', async () => {
      const store = useContactGroupStore();
      
      // Mock GraphQL error response
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          errors: [{ message: 'Test GraphQL error' }]
        })
      });

      await expect(store.fetchUserGroups(1)).rejects.toThrow('Test GraphQL error');
      expect(store.error).toBe('Test GraphQL error');
    });

    it('should handle network errors properly', async () => {
      const store = useContactGroupStore();
      
      // Mock network error
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 500
      });

      await expect(store.fetchUserGroups(1)).rejects.toThrow('HTTP error! status: 500');
    });
  });

  describe('Store State Management', () => {
    it('should initialize with empty state', () => {
      const store = useContactGroupStore();
      
      expect(store.groups).toEqual([]);
      expect(store.userGroups).toEqual([]);
      expect(store.loading).toBe(false);
      expect(store.error).toBe(null);
    });

    it('should set loading state during fetch', async () => {
      const store = useContactGroupStore();
      
      // Mock delayed response
      mockFetch.mockImplementationOnce(() => 
        new Promise(resolve => setTimeout(() => resolve({
          ok: true,
          json: async () => ({ data: { contactGroups: [] } })
        }), 100))
      );

      const fetchPromise = store.fetchUserGroups(1);
      
      // Should be loading immediately
      expect(store.loading).toBe(true);
      
      await fetchPromise;
      
      // Should not be loading after completion
      expect(store.loading).toBe(false);
    });
  });
});