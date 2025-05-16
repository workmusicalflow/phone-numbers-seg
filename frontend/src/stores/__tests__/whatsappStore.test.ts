import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useWhatsAppStore } from '../whatsappStore';
import { graphql } from '@/services/graphql';

// Mock de l'API graphql
vi.mock('@/services/graphql', () => ({
  graphql: vi.fn()
}));

describe('WhatsApp Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  describe('Initial State', () => {
    it('should have correct initial values', () => {
      const store = useWhatsAppStore();
      
      expect(store.messages).toEqual([]);
      expect(store.isLoading).toBe(false);
      expect(store.error).toBe(null);
      expect(store.totalCount).toBe(0);
      expect(store.currentPage).toBe(1);
      expect(store.pageSize).toBe(50);
      expect(store.filterPhoneNumber).toBe('');
      expect(store.filterStatus).toBe('');
    });
  });

  describe('Fetch Message History', () => {
    it('should fetch and set messages correctly', async () => {
      const store = useWhatsAppStore();
      const mockMessages = [
        {
          id: '1',
          wabaMessageId: 'wm_123',
          phoneNumber: '+2250101010101',
          direction: 'OUTGOING',
          type: 'text',
          content: 'Test message',
          status: 'sent',
          timestamp: '2024-05-16T10:00:00Z',
          createdAt: '2024-05-16T10:00:00Z'
        }
      ];

      vi.mocked(graphql)
        .mockResolvedValueOnce({ whatsAppHistory: mockMessages })
        .mockResolvedValueOnce({ whatsAppMessageCount: 1 });

      await store.fetchMessageHistory();

      expect(graphql).toHaveBeenCalledTimes(2);
      expect(store.messages).toEqual(mockMessages);
      expect(store.totalCount).toBe(1);
      expect(store.isLoading).toBe(false);
    });

    it('should handle errors gracefully', async () => {
      const store = useWhatsAppStore();
      const errorMessage = 'Network error';

      vi.mocked(graphql).mockRejectedValueOnce(new Error(errorMessage));

      await store.fetchMessageHistory();

      expect(store.error).toBe(errorMessage);
      expect(store.messages).toEqual([]);
      expect(store.isLoading).toBe(false);
    });
  });

  describe('Send Message', () => {
    it('should send a text message successfully', async () => {
      const store = useWhatsAppStore();
      const newMessage = {
        id: '2',
        wabaMessageId: 'wm_124',
        phoneNumber: '+2250202020202',
        direction: 'OUTGOING',
        type: 'text',
        content: 'Hello WhatsApp',
        status: 'sent',
        timestamp: '2024-05-16T10:30:00Z',
        createdAt: '2024-05-16T10:30:00Z'
      };

      vi.mocked(graphql).mockResolvedValueOnce({ sendWhatsAppMessage: newMessage });

      const result = await store.sendMessage({
        recipient: '+2250202020202',
        type: 'text',
        content: 'Hello WhatsApp'
      });

      expect(result).toEqual(newMessage);
      expect(store.messages[0]).toEqual(newMessage);
      expect(store.isLoading).toBe(false);
    });
  });

  describe('Send Template Message', () => {
    it('should send a template message successfully', async () => {
      const store = useWhatsAppStore();
      const templateMessage = {
        id: '3',
        wabaMessageId: 'wm_125',
        phoneNumber: '+2250303030303',
        direction: 'OUTGOING',
        type: 'template',
        templateName: 'welcome_message',
        templateLanguage: 'fr',
        status: 'sent',
        timestamp: '2024-05-16T10:45:00Z',
        createdAt: '2024-05-16T10:45:00Z'
      };

      vi.mocked(graphql).mockResolvedValueOnce({ sendWhatsAppTemplate: templateMessage });

      const result = await store.sendTemplateMessage({
        recipient: '+2250303030303',
        templateName: 'welcome_message',
        languageCode: 'fr',
        body1Param: 'John Doe'
      });

      expect(result).toEqual(templateMessage);
      expect(store.messages[0]).toEqual(templateMessage);
      expect(store.isLoading).toBe(false);
    });
  });

  describe('Pagination and Filtering', () => {
    it('should handle pagination correctly', () => {
      const store = useWhatsAppStore();
      
      // Simuler plusieurs messages
      store.messages = Array.from({ length: 150 }, (_, i) => ({
        id: `${i + 1}`,
        wabaMessageId: `wm_${i + 1}`,
        phoneNumber: '+2250101010101',
        direction: 'OUTGOING',
        type: 'text',
        content: `Message ${i + 1}`,
        status: 'sent',
        timestamp: `2024-05-16T${10 + Math.floor(i / 60)}:${(i % 60).toString().padStart(2, '0')}:00Z`,
        createdAt: `2024-05-16T${10 + Math.floor(i / 60)}:${(i % 60).toString().padStart(2, '0')}:00Z`
      }));

      expect(store.totalPages).toBe(3);
      expect(store.paginatedMessages.length).toBe(50);

      // Les messages sont triés par timestamp décroissant, donc le plus récent en premier
      // Messages 150 -> 1 (triés)
      // Page 1: 150 -> 101 (50 messages)
      // Page 2: 100 -> 51 (50 messages)
      store.setCurrentPage(2);
      expect(store.paginatedMessages[0].content).toBe('Message 100');

      store.setPageSize(20);
      expect(store.totalPages).toBe(8);
      expect(store.paginatedMessages.length).toBe(20);
    });

    it('should filter messages by phone number', () => {
      const store = useWhatsAppStore();
      
      store.messages = [
        {
          id: '1',
          phoneNumber: '+2250101010101',
          type: 'text',
          content: 'Message 1'
        },
        {
          id: '2',
          phoneNumber: '+2250202020202',
          type: 'text',
          content: 'Message 2'
        },
        {
          id: '3',
          phoneNumber: '+2250101010101',
          type: 'text',
          content: 'Message 3'
        }
      ];

      store.setFilters('+2250101010101');
      
      expect(store.filteredMessages.length).toBe(2);
      expect(store.filteredMessages[0].id).toBe('1');
      expect(store.filteredMessages[1].id).toBe('3');
    });
  });
});