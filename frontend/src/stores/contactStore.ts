import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { api } from '../services/api';

interface Contact {
  id: string;
  firstName: string;
  lastName: string;
  phoneNumber: string;
  email: string | null;
  notes: string | null;
  groups: ContactGroup[];
  createdAt: string;
  updatedAt: string;
}

interface ContactGroup {
  id: string;
  name: string;
}

interface ContactCreateData {
  firstName: string;
  lastName: string;
  phoneNumber: string;
  email: string | null;
  notes: string | null;
  groups: string[];
}

export const useContactStore = defineStore('contact', () => {
  // État
  const contacts = ref<Contact[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);
  const currentPage = ref(1);
  const itemsPerPage = ref(10);
  const totalCount = ref(0);
  const searchTerm = ref('');

  // Getters
  const filteredContacts = computed(() => {
    if (!searchTerm.value) {
      return contacts.value;
    }
    
    const term = searchTerm.value.toLowerCase();
    return contacts.value.filter(contact => 
      contact.firstName.toLowerCase().includes(term) ||
      contact.lastName.toLowerCase().includes(term) ||
      contact.phoneNumber.includes(term) ||
      (contact.email && contact.email.toLowerCase().includes(term))
    );
  });

  const paginatedContacts = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredContacts.value.slice(start, end);
  });

  const pageCount = computed(() => {
    return Math.ceil(filteredContacts.value.length / itemsPerPage.value);
  });

  // Actions
  async function fetchContacts() {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await api.get('/contacts', {
        params: {
          page: currentPage.value,
          limit: itemsPerPage.value
        }
      });
      
      contacts.value = response.data.contacts;
      totalCount.value = response.data.totalCount;
    } catch (err: any) {
      console.error('Erreur lors de la récupération des contacts:', err);
      error.value = err.message || 'Erreur lors de la récupération des contacts';
    } finally {
      loading.value = false;
    }
  }

  async function createContact(contactData: ContactCreateData) {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await api.post('/contacts', contactData);
      contacts.value.push(response.data);
      totalCount.value++;
      return response.data;
    } catch (err: any) {
      console.error('Erreur lors de la création du contact:', err);
      error.value = err.message || 'Erreur lors de la création du contact';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function updateContact(id: string, contactData: Partial<ContactCreateData>) {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await api.put(`/contacts/${id}`, contactData);
      const index = contacts.value.findIndex(c => c.id === id);
      if (index !== -1) {
        contacts.value[index] = response.data;
      }
      return response.data;
    } catch (err: any) {
      console.error('Erreur lors de la mise à jour du contact:', err);
      error.value = err.message || 'Erreur lors de la mise à jour du contact';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function deleteContact(id: string) {
    loading.value = true;
    error.value = null;
    
    try {
      await api.delete(`/contacts/${id}`);
      contacts.value = contacts.value.filter(c => c.id !== id);
      totalCount.value--;
    } catch (err: any) {
      console.error('Erreur lors de la suppression du contact:', err);
      error.value = err.message || 'Erreur lors de la suppression du contact';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  function searchContacts(term: string) {
    searchTerm.value = term;
    currentPage.value = 1; // Réinitialiser à la première page lors d'une recherche
  }

  function setPage(page: number) {
    currentPage.value = page;
    fetchContacts();
  }

  function setItemsPerPage(limit: number) {
    itemsPerPage.value = limit;
    fetchContacts();
  }

  return {
    contacts,
    loading,
    error,
    currentPage,
    itemsPerPage,
    totalCount,
    filteredContacts,
    paginatedContacts,
    pageCount,
    fetchContacts,
    createContact,
    updateContact,
    deleteContact,
    searchContacts,
    setPage,
    setItemsPerPage
  };
});
