import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { Quasar } from 'quasar';
import WhatsApp from './WhatsApp.vue';
import { useWhatsAppStore } from '@/stores/whatsappStore';
import { useUserStore } from '@/stores/userStore';
import { useContactStore } from '@/stores/contactStore';
import { createRouter, createWebHistory } from 'vue-router';

// Mock components
vi.mock('@/components/common/ContactCountBadge.vue', () => ({
  default: {
    name: 'ContactCountBadge',
    template: '<div class="contact-count-badge">{{count}}</div>',
    props: ['count', 'color', 'icon', 'tooltipText']
  }
}));

vi.mock('@/components/whatsapp/WhatsAppSendMessage.vue', () => ({
  default: {
    name: 'WhatsAppSendMessage',
    template: '<div class="whatsapp-send-message">Send Message Component</div>'
  }
}));

vi.mock('@/components/whatsapp/WhatsAppMessageList.vue', () => ({
  default: {
    name: 'WhatsAppMessageList', 
    template: '<div class="whatsapp-message-list">Message List Component</div>'
  }
}));

// Create a router
const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'home', component: { template: '<div>Home</div>' } },
    { path: '/whatsapp', name: 'whatsapp', component: WhatsApp }
  ]
});

describe('WhatsApp.vue', () => {
  let whatsAppStore: ReturnType<typeof useWhatsAppStore>;
  let userStore: ReturnType<typeof useUserStore>;
  let contactStore: ReturnType<typeof useContactStore>;

  beforeEach(async () => {
    setActivePinia(createPinia());
    whatsAppStore = useWhatsAppStore();
    userStore = useUserStore();
    contactStore = useContactStore();
    
    // Mock store state and methods
    userStore.currentUser = {
      id: '1',
      name: 'Test User',
      email: 'test@example.com',
      smsCredit: 100
    };

    contactStore.fetchContactsCount = vi.fn().mockResolvedValue(50);
    whatsAppStore.fetchMessages = vi.fn().mockResolvedValue([]);
    whatsAppStore.messages = [];

    await router.push('/whatsapp');
  });

  it('renders the WhatsApp view correctly', () => {
    const wrapper = mount(WhatsApp, {
      global: {
        plugins: [Quasar, router],
        stubs: {
          ContactCountBadge: false,
          WhatsAppSendMessage: false,
          WhatsAppMessageList: false
        }
      }
    });

    expect(wrapper.find('.text-h4').text()).toBe('WhatsApp');
    expect(wrapper.find('.contact-count-badge').exists()).toBe(true);
  });

  it('switches between tabs correctly', async () => {
    const wrapper = mount(WhatsApp, {
      global: {
        plugins: [Quasar, router]
      }
    });

    // Initially on 'send' tab
    expect(wrapper.find('.whatsapp-send-message').exists()).toBe(true);
    expect(wrapper.find('.whatsapp-message-list').exists()).toBe(false);

    // Click on messages tab
    const messagesTab = wrapper.find('[aria-label="Messages"]');
    await messagesTab.trigger('click');

    // Should now show the message list
    expect(wrapper.find('.whatsapp-send-message').exists()).toBe(false);
    expect(wrapper.find('.whatsapp-message-list').exists()).toBe(true);
  });

  it('displays the last sent message', async () => {
    const mockMessage = {
      id: '1',
      phoneNumber: '+2250123456789',
      type: 'text',
      status: 'delivered',
      content: 'Test message',
      direction: 'OUTGOING',
      createdAt: new Date('2025-05-16T10:00:00'),
      deliveredAt: new Date('2025-05-16T10:01:00'),
      readAt: null
    };

    whatsAppStore.messages = [mockMessage];

    const wrapper = mount(WhatsApp, {
      global: {
        plugins: [Quasar, router]
      }
    });

    expect(wrapper.text()).toContain('Dernier message envoyé');
    expect(wrapper.text()).toContain('+2250123456789');
    expect(wrapper.text()).toContain('Test message');
    expect(wrapper.find('.q-badge').text()).toBe('delivered');
  });

  it('calculates statistics correctly', async () => {
    const today = new Date();
    const messages = [
      {
        id: '1',
        phoneNumber: '+2250123456789',
        direction: 'OUTGOING',
        status: 'delivered',
        createdAt: today,
        deliveredAt: today,
        readAt: today
      },
      {
        id: '2',
        phoneNumber: '+2250123456789',
        direction: 'OUTGOING',
        status: 'sent',
        createdAt: today,
        deliveredAt: null,
        readAt: null
      },
      {
        id: '3',
        phoneNumber: '+2250123456790',
        direction: 'OUTGOING',
        status: 'delivered',
        createdAt: today,
        deliveredAt: today,
        readAt: null
      }
    ];

    whatsAppStore.messages = messages;

    const wrapper = mount(WhatsApp, {
      global: {
        plugins: [Quasar, router]
      }
    });

    await wrapper.vm.$nextTick();

    expect(wrapper.text()).toContain('Messages envoyés');
    expect(wrapper.text()).toContain('3'); // Total messages
    expect(wrapper.text()).toContain('Messages délivrés');
    expect(wrapper.text()).toContain('2'); // Delivered messages
    expect(wrapper.text()).toContain('Messages lus');
    expect(wrapper.text()).toContain('1'); // Read messages
  });

  it('processes URL parameters correctly', async () => {
    await router.push('/whatsapp?tab=messages');
    
    const wrapper = mount(WhatsApp, {
      global: {
        plugins: [Quasar, router]
      }
    });

    // Should switch to messages tab based on URL
    expect(wrapper.find('.whatsapp-message-list').exists()).toBe(true);
  });

  it('fetches data on mount', async () => {
    mount(WhatsApp, {
      global: {
        plugins: [Quasar, router]
      }
    });

    expect(contactStore.fetchContactsCount).toHaveBeenCalled();
    expect(whatsAppStore.fetchMessages).toHaveBeenCalled();
  });

  it('formats date correctly', () => {
    const mockMessage = {
      id: '1',
      phoneNumber: '+2250123456789',
      type: 'text',
      status: 'sent',
      content: 'Test',
      direction: 'OUTGOING',
      createdAt: new Date('2025-05-16T14:30:00')
    };

    whatsAppStore.messages = [mockMessage];

    const wrapper = mount(WhatsApp, {
      global: {
        plugins: [Quasar, router]
      }
    });

    expect(wrapper.text()).toContain('16 mai 2025');
    expect(wrapper.text()).toContain('14:30');
  });

  it('refreshes messages periodically', async () => {
    vi.useFakeTimers();
    
    mount(WhatsApp, {
      global: {
        plugins: [Quasar, router]
      }
    });

    // Initial call on mount
    expect(whatsAppStore.fetchMessages).toHaveBeenCalledTimes(1);

    // Advance timer by 30 seconds
    vi.advanceTimersByTime(30000);

    // Should have been called again
    expect(whatsAppStore.fetchMessages).toHaveBeenCalledTimes(2);

    vi.useRealTimers();
  });
});