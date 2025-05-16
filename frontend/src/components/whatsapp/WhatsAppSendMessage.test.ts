import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { Quasar } from 'quasar';
import WhatsAppSendMessage from './WhatsAppSendMessage.vue';
import { useWhatsAppStore } from '@/stores/whatsappStore';

// Mock Quasar notify
const mockNotify = vi.fn();
vi.mock('quasar', async () => {
  const actual = await vi.importActual('quasar');
  return {
    ...actual,
    useQuasar: () => ({
      notify: mockNotify
    })
  };
});

describe('WhatsAppSendMessage.vue', () => {
  let store: ReturnType<typeof useWhatsAppStore>;

  beforeEach(() => {
    setActivePinia(createPinia());
    store = useWhatsAppStore();
    mockNotify.mockClear();
  });

  it('renders the send message form correctly', () => {
    const wrapper = mount(WhatsAppSendMessage, {
      global: {
        plugins: [Quasar]
      }
    });

    expect(wrapper.find('.whatsapp-send-message').exists()).toBe(true);
    expect(wrapper.find('input[label="Numéro de téléphone du destinataire"]').exists()).toBe(true);
  });

  it('sends a text message when form is submitted', async () => {
    const wrapper = mount(WhatsAppSendMessage, {
      global: {
        plugins: [Quasar]
      }
    });

    // Mock the store action
    store.sendMessage = vi.fn().mockResolvedValue({ id: '123', status: 'sent' });
    store.fetchMessages = vi.fn().mockResolvedValue([]);

    // Fill in the form
    const phoneInput = wrapper.find('input[aria-label="Numéro de téléphone du destinataire"]');
    const messageInput = wrapper.find('textarea[aria-label="Message"]');
    
    await phoneInput.setValue('2250123456789');
    await messageInput.setValue('Test message');

    // Click send button
    const sendButton = wrapper.find('button[label="Envoyer"]');
    await sendButton.trigger('click');

    expect(store.sendMessage).toHaveBeenCalledWith({
      recipient: '2250123456789',
      type: 'text',
      content: 'Test message'
    });

    expect(mockNotify).toHaveBeenCalledWith({
      type: 'positive',
      message: 'Message envoyé avec succès'
    });
  });

  it('sends a template message when form is submitted', async () => {
    const wrapper = mount(WhatsAppSendMessage, {
      global: {
        plugins: [Quasar]
      }
    });

    // Mock the store action and templates
    store.sendTemplate = vi.fn().mockResolvedValue({ id: '123', status: 'sent' });
    store.fetchMessages = vi.fn().mockResolvedValue([]);
    store.userTemplates = [
      { id: '1', template_id: 'hello_world', name: 'Hello World', language: 'en_US' }
    ];

    // Switch to template tab
    const templateTab = wrapper.find('div[aria-label="Message template"]');
    await templateTab.trigger('click');

    // Fill in the form
    const phoneInput = wrapper.find('input[aria-label="Numéro de téléphone du destinataire"]');
    await phoneInput.setValue('2250123456789');

    // Select template and language
    const templateSelect = wrapper.find('select[aria-label="Template"]');
    const languageSelect = wrapper.find('select[aria-label="Langue"]');
    
    await templateSelect.setValue('hello_world');
    await languageSelect.setValue('fr');

    // Click send button
    const sendButton = wrapper.find('button[label="Envoyer le template"]');
    await sendButton.trigger('click');

    expect(store.sendTemplate).toHaveBeenCalledWith({
      recipient: '2250123456789',
      templateName: 'hello_world',
      languageCode: 'fr',
      components: undefined
    });

    expect(mockNotify).toHaveBeenCalledWith({
      type: 'positive',
      message: 'Template envoyé avec succès'
    });
  });

  it('validates phone number format', async () => {
    const wrapper = mount(WhatsAppSendMessage, {
      global: {
        plugins: [Quasar]
      }
    });

    const phoneInput = wrapper.find('input[aria-label="Numéro de téléphone du destinataire"]');
    
    // Test invalid number
    await phoneInput.setValue('123');
    expect(wrapper.text()).toContain('Numéro de téléphone invalide');

    // Test valid number
    await phoneInput.setValue('2250123456789');
    expect(wrapper.text()).not.toContain('Numéro de téléphone invalide');
  });

  it('loads templates on mount', async () => {
    store.loadUserTemplates = vi.fn().mockResolvedValue([]);

    mount(WhatsAppSendMessage, {
      global: {
        plugins: [Quasar]
      }
    });

    expect(store.loadUserTemplates).toHaveBeenCalled();
  });

  it('handles send errors gracefully', async () => {
    const wrapper = mount(WhatsAppSendMessage, {
      global: {
        plugins: [Quasar]
      }
    });

    // Mock the store action to throw an error
    store.sendMessage = vi.fn().mockRejectedValue(new Error('Network error'));

    // Fill in the form
    const phoneInput = wrapper.find('input[aria-label="Numéro de téléphone du destinataire"]');
    const messageInput = wrapper.find('textarea[aria-label="Message"]');
    
    await phoneInput.setValue('2250123456789');
    await messageInput.setValue('Test message');

    // Click send button
    const sendButton = wrapper.find('button[label="Envoyer"]');
    await sendButton.trigger('click');

    expect(mockNotify).toHaveBeenCalledWith({
      type: 'negative',
      message: 'Erreur lors de l\'envoi: Network error'
    });
  });
});