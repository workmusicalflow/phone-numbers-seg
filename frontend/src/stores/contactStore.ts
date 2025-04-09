import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { apolloClient, gql } from '../services/api';

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

  // GraphQL Queries
  const FETCH_CONTACTS = gql`
    query GetContacts($limit: Int, $offset: Int) {
      contacts(limit: $limit, offset: $offset) {
        id
        name
        phoneNumber
        email
        notes
        createdAt
        updatedAt
      }
    }
  `;

  const CREATE_CONTACT = gql`
    mutation CreateContact($name: String!, $phoneNumber: String!, $email: String, $notes: String) {
      createContact(name: $name, phoneNumber: $phoneNumber, email: $email, notes: $notes) {
        id
        name
        phoneNumber
        email
        notes
        createdAt
        updatedAt
      }
    }
  `;

  const UPDATE_CONTACT = gql`
    mutation UpdateContact($id: Int!, $name: String!, $phoneNumber: String!, $email: String, $notes: String) {
      updateContact(id: $id, name: $name, phoneNumber: $phoneNumber, email: $email, notes: $notes) {
        id
        name
        phoneNumber
        email
        notes
        createdAt
        updatedAt
      }
    }
  `;

  const DELETE_CONTACT = gql`
    mutation DeleteContact($id: Int!) {
      deleteContact(id: $id)
    }
  `;

  const SEARCH_CONTACTS = gql`
    query SearchContacts($query: String!, $limit: Int, $offset: Int) {
      searchContacts(query: $query, limit: $limit, offset: $offset) {
        id
        name
        phoneNumber
        email
        notes
        createdAt
        updatedAt
      }
    }
  `;

  // Actions
  async function fetchContacts() {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await apolloClient.query({
        query: FETCH_CONTACTS,
        variables: {
          limit: itemsPerPage.value,
          offset: (currentPage.value - 1) * itemsPerPage.value
        }
      });
      
      // Transformer les contacts pour correspondre à l'interface Contact
      contacts.value = response.data.contacts.map((contact: any) => {
        const [firstName, lastName] = contact.name.split(' ');
        return {
          id: contact.id,
          firstName: firstName || '',
          lastName: lastName || '',
          phoneNumber: contact.phoneNumber,
          email: contact.email,
          notes: contact.notes,
          groups: [],
          createdAt: contact.createdAt,
          updatedAt: contact.updatedAt
        };
      });
      
      totalCount.value = response.data.contacts.length;
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
      const response = await apolloClient.mutate({
        mutation: CREATE_CONTACT,
        variables: {
          name: `${contactData.firstName} ${contactData.lastName}`.trim(),
          phoneNumber: contactData.phoneNumber,
          email: contactData.email,
          notes: contactData.notes
        }
      });
      
      const newContact = response.data.createContact;
      const [firstName, lastName] = newContact.name.split(' ');
      
      const formattedContact = {
        id: newContact.id,
        firstName: firstName || '',
        lastName: lastName || '',
        phoneNumber: newContact.phoneNumber,
        email: newContact.email,
        notes: newContact.notes,
        groups: [],
        createdAt: newContact.createdAt,
        updatedAt: newContact.updatedAt
      };
      
      contacts.value.push(formattedContact);
      totalCount.value++;
      return formattedContact;
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
      // Récupérer le contact existant pour obtenir les données complètes
      const existingContact = contacts.value.find(c => c.id === id);
      if (!existingContact) {
        throw new Error('Contact non trouvé');
      }
      
      const response = await apolloClient.mutate({
        mutation: UPDATE_CONTACT,
        variables: {
          id: parseInt(id),
          name: `${contactData.firstName || existingContact.firstName} ${contactData.lastName || existingContact.lastName}`.trim(),
          phoneNumber: contactData.phoneNumber || existingContact.phoneNumber,
          email: contactData.email !== undefined ? contactData.email : existingContact.email,
          notes: contactData.notes !== undefined ? contactData.notes : existingContact.notes
        }
      });
      
      const updatedContact = response.data.updateContact;
      const [firstName, lastName] = updatedContact.name.split(' ');
      
      const formattedContact = {
        id: updatedContact.id,
        firstName: firstName || '',
        lastName: lastName || '',
        phoneNumber: updatedContact.phoneNumber,
        email: updatedContact.email,
        notes: updatedContact.notes,
        groups: existingContact.groups,
        createdAt: updatedContact.createdAt,
        updatedAt: updatedContact.updatedAt
      };
      
      const index = contacts.value.findIndex(c => c.id === id);
      if (index !== -1) {
        contacts.value[index] = formattedContact;
      }
      
      return formattedContact;
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
      await apolloClient.mutate({
        mutation: DELETE_CONTACT,
        variables: {
          id: parseInt(id)
        }
      });
      
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

  async function searchContacts(term: string) {
    searchTerm.value = term;
    currentPage.value = 1; // Réinitialiser à la première page lors d'une recherche
    
    if (term.length > 2) {
      loading.value = true;
      error.value = null;
      
      try {
        const response = await apolloClient.query({
          query: SEARCH_CONTACTS,
          variables: {
            query: term,
            limit: itemsPerPage.value,
            offset: 0
          }
        });
        
        // Transformer les contacts pour correspondre à l'interface Contact
        contacts.value = response.data.searchContacts.map((contact: any) => {
          const [firstName, lastName] = contact.name.split(' ');
          return {
            id: contact.id,
            firstName: firstName || '',
            lastName: lastName || '',
            phoneNumber: contact.phoneNumber,
            email: contact.email,
            notes: contact.notes,
            groups: [],
            createdAt: contact.createdAt,
            updatedAt: contact.updatedAt
          };
        });
        
        totalCount.value = response.data.searchContacts.length;
      } catch (err: any) {
        console.error('Erreur lors de la recherche de contacts:', err);
        error.value = err.message || 'Erreur lors de la recherche de contacts';
      } finally {
        loading.value = false;
      }
    } else if (term.length === 0) {
      fetchContacts();
    }
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
