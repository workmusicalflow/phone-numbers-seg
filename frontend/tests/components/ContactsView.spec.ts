import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { createTestingPinia } from '@pinia/testing';
import { Quasar } from 'quasar';
import Contacts from '../../src/views/Contacts.vue';
import { useContactStore } from '../../src/stores/contactStore';
import { useContactGroupStore } from '../../src/stores/contactGroupStore';

// Mock des composants Quasar
vi.mock('quasar', async () => {
  const actual = await vi.importActual('quasar');
  return {
    ...actual,
    useQuasar: () => ({
      notify: vi.fn()
    })
  };
});

// Mock du router
vi.mock('vue-router', () => ({
  useRouter: () => ({
    push: vi.fn()
  })
}));

describe('Contacts.vue', () => {
  let wrapper: any;
  let contactStore: any;
  let contactGroupStore: any;

  beforeEach(() => {
    // Créer un pinia de test
    const pinia = createTestingPinia({
      createSpy: vi.fn,
      stubActions: false
    });

    // Initialiser les stores
    contactStore = useContactStore(pinia);
    contactGroupStore = useContactGroupStore(pinia);

    // Mock des méthodes du store
    contactStore.fetchContacts = vi.fn().mockResolvedValue([]);
    contactStore.searchContacts = vi.fn();
    contactStore.createContact = vi.fn().mockResolvedValue({});
    contactStore.updateContact = vi.fn().mockResolvedValue({});
    contactStore.deleteContact = vi.fn().mockResolvedValue({});
    contactStore.filteredContacts = [];
    contactStore.totalCount = 0;

    contactGroupStore.fetchGroups = vi.fn().mockResolvedValue([]);
    contactGroupStore.groups = [];

    // Monter le composant
    wrapper = mount(Contacts, {
      global: {
        plugins: [pinia, Quasar],
        stubs: {
          QTable: true,
          QInput: true,
          QBtn: true,
          QDialog: true,
          QCard: true,
          QCardSection: true,
          QCardActions: true,
          QForm: true,
          QSelect: true,
          QSpace: true,
          QAvatar: true,
          QIcon: true,
          QTooltip: true,
          QChip: true,
          QPagination: true,
          teleport: true
        }
      }
    });
  });

  it('devrait charger les contacts et les groupes au montage', () => {
    expect(contactStore.fetchContacts).toHaveBeenCalled();
    expect(contactGroupStore.fetchGroups).toHaveBeenCalled();
  });

  it('devrait rechercher des contacts lorsque la requête de recherche change', async () => {
    const searchInput = wrapper.find('.search-input');
    await searchInput.setValue('test');
    await searchInput.trigger('input');
    
    expect(contactStore.searchContacts).toHaveBeenCalledWith('test');
  });

  it('devrait ouvrir le dialogue de création de contact', async () => {
    const addButton = wrapper.find('.q-ml-sm');
    await addButton.trigger('click');
    
    expect(wrapper.vm.contactDialog).toBe(true);
    expect(wrapper.vm.isEditing).toBe(false);
  });

  it('devrait réinitialiser le formulaire lors de l\'ouverture du dialogue de création', async () => {
    const addButton = wrapper.find('.q-ml-sm');
    await addButton.trigger('click');
    
    expect(wrapper.vm.contactForm).toEqual({
      id: '',
      firstName: '',
      lastName: '',
      phoneNumber: '',
      email: '',
      groups: [],
      notes: ''
    });
  });

  it('devrait appeler createContact lors de la sauvegarde d\'un nouveau contact', async () => {
    // Ouvrir le dialogue de création
    wrapper.vm.openContactDialog();
    
    // Remplir le formulaire
    wrapper.vm.contactForm = {
      id: '',
      firstName: 'John',
      lastName: 'Doe',
      phoneNumber: '+22501234567',
      email: 'john.doe@example.com',
      groups: [],
      notes: 'Test notes'
    };
    
    // Sauvegarder le contact
    await wrapper.vm.saveContact();
    
    expect(contactStore.createContact).toHaveBeenCalledWith({
      firstName: 'John',
      lastName: 'Doe',
      phoneNumber: '+22501234567',
      email: 'john.doe@example.com',
      groups: [],
      notes: 'Test notes'
    });
  });

  it('devrait appeler updateContact lors de la sauvegarde d\'un contact existant', async () => {
    // Ouvrir le dialogue d'édition
    wrapper.vm.openContactDialog({
      id: '123',
      firstName: 'John',
      lastName: 'Doe',
      phoneNumber: '+22501234567',
      email: 'john.doe@example.com',
      groups: [],
      notes: 'Test notes'
    });
    
    // Modifier le formulaire
    wrapper.vm.contactForm.firstName = 'Jane';
    
    // Sauvegarder le contact
    await wrapper.vm.saveContact();
    
    expect(contactStore.updateContact).toHaveBeenCalledWith('123', {
      firstName: 'Jane',
      lastName: 'Doe',
      phoneNumber: '+22501234567',
      email: 'john.doe@example.com',
      groups: [],
      notes: 'Test notes'
    });
  });

  it('devrait ouvrir le dialogue de confirmation de suppression', async () => {
    const contact = {
      id: '123',
      firstName: 'John',
      lastName: 'Doe',
      phoneNumber: '+22501234567'
    };
    
    wrapper.vm.confirmDelete(contact);
    
    expect(wrapper.vm.deleteDialog).toBe(true);
    expect(wrapper.vm.contactToDelete).toEqual(contact);
  });

  it('devrait appeler deleteContact lors de la confirmation de suppression', async () => {
    // Configurer le contact à supprimer
    wrapper.vm.contactToDelete = {
      id: '123',
      firstName: 'John',
      lastName: 'Doe'
    };
    
    // Supprimer le contact
    await wrapper.vm.deleteContact();
    
    expect(contactStore.deleteContact).toHaveBeenCalledWith('123');
  });
});
