import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import AdminDashboard from '../../src/views/AdminDashboard.vue';
import { useDashboardStore } from '../../src/stores/dashboardStore';
import { useSenderNameStore } from '../../src/stores/senderNameStore';
import { useSMSOrderStore } from '../../src/stores/smsOrderStore';

// Mock Chart.js
vi.mock('chart.js/auto', () => ({
  default: class Chart {
    constructor() {
      return {};
    }
  }
}));

// Mock date from quasar
vi.mock('quasar', () => ({
  date: {
    formatDate: vi.fn().mockImplementation(() => '01/01/2025 12:00')
  }
}));

describe('AdminDashboard.vue', () => {
  let wrapper;
  let dashboardStore;
  let senderNameStore;
  let smsOrderStore;

  beforeEach(() => {
    // Create a fresh Pinia instance for each test
    const pinia = createPinia();
    setActivePinia(pinia);

    // Create the stores
    dashboardStore = useDashboardStore(pinia);
    senderNameStore = useSenderNameStore(pinia);
    smsOrderStore = useSMSOrderStore(pinia);

    // Mock store data
    dashboardStore.stats = {
      totalUsers: 25,
      totalPhoneNumbers: 1250,
      totalSMSSent: 3750,
      totalCredits: 5000
    };

    dashboardStore.recentActivity = [
      {
        type: 'user',
        description: 'Nouvel utilisateur créé: Jean Dupont',
        date: '2025-04-06T10:15:00'
      },
      {
        type: 'sms',
        description: '150 SMS envoyés par Marie Martin',
        date: '2025-04-06T09:30:00'
      }
    ];

    dashboardStore.pendingSenderNames = [
      {
        id: 1,
        name: 'PromoShop',
        username: 'jean.dupont'
      },
      {
        id: 2,
        name: 'InfoAlert',
        username: 'marie.martin'
      }
    ];

    dashboardStore.pendingOrders = [
      {
        id: 1,
        quantity: 500,
        username: 'jean.dupont'
      },
      {
        id: 2,
        quantity: 1000,
        username: 'marie.martin'
      }
    ];

    dashboardStore.smsChartData = {
      labels: ['01/04', '02/04', '03/04'],
      data: [120, 150, 180]
    };

    // Mock store actions
    dashboardStore.loadAllDashboardData = vi.fn();
    dashboardStore.fetchPendingSenderNames = vi.fn();
    dashboardStore.fetchPendingOrders = vi.fn();
    senderNameStore.updateSenderNameStatus = vi.fn();
    smsOrderStore.updateSMSOrderStatus = vi.fn();

    // Mount the component
    wrapper = mount(AdminDashboard, {
      global: {
        plugins: [pinia],
        stubs: {
          'q-card': true,
          'q-card-section': true,
          'q-separator': true,
          'q-spinner': true,
          'q-list': true,
          'q-item': true,
          'q-item-section': true,
          'q-item-label': true,
          'q-icon': true,
          'q-tabs': true,
          'q-tab': true,
          'q-tab-panels': true,
          'q-tab-panel': true,
          'q-btn': true,
          'q-select': true,
          'q-input': true
        }
      }
    });
  });

  it('loads dashboard data on mount', () => {
    expect(dashboardStore.loadAllDashboardData).toHaveBeenCalled();
  });

  it('displays statistics correctly', () => {
    const stats = wrapper.findAll('.stat-item');
    expect(stats.length).toBe(4);
    
    const statsText = wrapper.text();
    expect(statsText).toContain('25');
    expect(statsText).toContain('1250');
    expect(statsText).toContain('3750');
    expect(statsText).toContain('5000');
  });

  it('displays recent activity', () => {
    const activityText = wrapper.text();
    expect(activityText).toContain('Nouvel utilisateur créé: Jean Dupont');
    expect(activityText).toContain('150 SMS envoyés par Marie Martin');
  });

  it('filters activity by type', async () => {
    // Set the activity type filter
    wrapper.vm.activityTypeFilter = 'user';
    
    // Check that only user activities are shown
    expect(wrapper.vm.filteredActivity.length).toBe(1);
    expect(wrapper.vm.filteredActivity[0].type).toBe('user');
  });

  it('filters activity by search query', async () => {
    // Set the search query
    wrapper.vm.activitySearchQuery = 'Jean';
    
    // Check that only activities containing "Jean" are shown
    expect(wrapper.vm.filteredActivity.length).toBe(1);
    expect(wrapper.vm.filteredActivity[0].description).toContain('Jean');
  });

  it('displays pending sender names', () => {
    // Switch to sender names tab
    wrapper.vm.pendingTab = 'senderNames';
    
    const senderNamesText = wrapper.text();
    expect(senderNamesText).toContain('PromoShop');
    expect(senderNamesText).toContain('InfoAlert');
  });

  it('filters sender names by search query', async () => {
    // Switch to sender names tab
    wrapper.vm.pendingTab = 'senderNames';
    
    // Set the search query
    wrapper.vm.senderNameSearchQuery = 'Promo';
    
    // Check that only sender names containing "Promo" are shown
    expect(wrapper.vm.filteredSenderNames.length).toBe(1);
    expect(wrapper.vm.filteredSenderNames[0].name).toBe('PromoShop');
  });

  it('sorts sender names by name', async () => {
    // Switch to sender names tab
    wrapper.vm.pendingTab = 'senderNames';
    
    // Set the sort by
    wrapper.vm.senderNameSortBy = 'name';
    
    // Check that sender names are sorted by name
    expect(wrapper.vm.filteredSenderNames[0].name).toBe('InfoAlert');
    expect(wrapper.vm.filteredSenderNames[1].name).toBe('PromoShop');
  });

  it('displays pending orders', () => {
    // Switch to orders tab
    wrapper.vm.pendingTab = 'orders';
    
    const ordersText = wrapper.text();
    expect(ordersText).toContain('500 crédits');
    expect(ordersText).toContain('1000 crédits');
  });

  it('filters orders by search query', async () => {
    // Switch to orders tab
    wrapper.vm.pendingTab = 'orders';
    
    // Set the search query
    wrapper.vm.orderSearchQuery = 'marie';
    
    // Check that only orders containing "marie" are shown
    expect(wrapper.vm.filteredOrders.length).toBe(1);
    expect(wrapper.vm.filteredOrders[0].username).toBe('marie.martin');
  });

  it('sorts orders by quantity', async () => {
    // Switch to orders tab
    wrapper.vm.pendingTab = 'orders';
    
    // Set the sort by
    wrapper.vm.orderSortBy = 'quantity';
    
    // Check that orders are sorted by quantity (descending)
    expect(wrapper.vm.filteredOrders[0].quantity).toBe(1000);
    expect(wrapper.vm.filteredOrders[1].quantity).toBe(500);
  });

  it('approves a sender name', async () => {
    // Call the approve method
    await wrapper.vm.approveSenderName(1);
    
    // Check that the store method was called with the correct parameters
    expect(senderNameStore.updateSenderNameStatus).toHaveBeenCalledWith(1, 'approved');
    expect(dashboardStore.fetchPendingSenderNames).toHaveBeenCalled();
  });

  it('rejects a sender name', async () => {
    // Call the reject method
    await wrapper.vm.rejectSenderName(1);
    
    // Check that the store method was called with the correct parameters
    expect(senderNameStore.updateSenderNameStatus).toHaveBeenCalledWith(1, 'rejected');
    expect(dashboardStore.fetchPendingSenderNames).toHaveBeenCalled();
  });

  it('completes an order', async () => {
    // Call the complete method
    await wrapper.vm.completeOrder(1);
    
    // Check that the store method was called with the correct parameters
    expect(smsOrderStore.updateSMSOrderStatus).toHaveBeenCalledWith(1, 'completed');
    expect(dashboardStore.fetchPendingOrders).toHaveBeenCalled();
  });
});
