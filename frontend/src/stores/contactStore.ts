import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { apolloClient, gql } from '../services/api';

interface Contact {
  id: string;
  name: string; // Use name
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
  name: string; // Changed from firstName/lastName
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
      contact.name.toLowerCase().includes(term) ||
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
        groups { # Fetch groups for the contact
          id
          name
        }
      }
    }
  `;

  const COUNT_CONTACTS = gql`
    query CountContacts {
      contactsCount
    }
  `;

  const CREATE_CONTACT = gql`
    mutation CreateContact(
      $name: String!
      $phoneNumber: String!
      $email: String
      $notes: String
      $groupIds: [ID!] # Added groupIds variable
    ) {
      createContact(
        name: $name
        phoneNumber: $phoneNumber
        email: $email
        notes: $notes
        groupIds: $groupIds # Added groupIds argument
      ) {
        id
        name
        phoneNumber
        email
        notes
        createdAt
        updatedAt
        groups {
          id
          name
        }
      }
    }
  `;

  const UPDATE_CONTACT = gql`
    mutation UpdateContact(
      $id: ID! # Changed from Int! to ID!
      $name: String!
      $phoneNumber: String!
      $email: String
      $notes: String
      $groupIds: [ID!] # Added groupIds variable
    ) {
      updateContact(
        id: $id
        name: $name
        phoneNumber: $phoneNumber
        email: $email
        notes: $notes
        groupIds: $groupIds # Added groupIds argument
      ) {
        id
        name
        phoneNumber
        email
        notes
        createdAt
        updatedAt
        groups {
          id
          name
        }
      }
    }
  `;

  const DELETE_CONTACT = gql`
    mutation DeleteContact($id: ID!) {
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
        groups {
          id
          name
        }
      }
    }
  `;

  // Query to fetch groups for a specific contact
  const FETCH_CONTACT_GROUPS = gql`
    query GetGroupsForContact($contactId: ID!) {
      groupsForContact(contactId: $contactId) {
        id
        name
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
      contacts.value = response.data.contacts.map((contact: any): Contact => {
        // Map groups directly if they are fetched
        const groups = contact.groups ? contact.groups.map((g: any) => ({ id: g.id, name: g.name })) : [];
        return {
          id: contact.id,
          name: contact.name, // Use name directly
          phoneNumber: contact.phoneNumber,
          email: contact.email,
          notes: contact.notes,
          groups: groups, // Assign fetched groups
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
          name: contactData.name, // Use name directly
          phoneNumber: contactData.phoneNumber,
          email: contactData.email,
          notes: contactData.notes,
          groupIds: contactData.groups // Pass group IDs
        }
      });

      const newContact = response.data.createContact;
      // Use groups returned by the mutation
      const groups = newContact.groups ? newContact.groups.map((g: any) => ({ id: g.id, name: g.name })) : [];
      const formattedContact: Contact = {
        id: newContact.id,
        name: newContact.name, // Use name directly
        phoneNumber: newContact.phoneNumber,
        email: newContact.email,
        notes: newContact.notes,
        groups: groups, // Use groups from the mutation response
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
          id: id, // Pass ID as string (GraphQL ID type)
          name: contactData.name || existingContact.name, // Use name directly
          phoneNumber: contactData.phoneNumber || existingContact.phoneNumber,
          email: contactData.email !== undefined ? contactData.email : existingContact.email,
          notes: contactData.notes !== undefined ? contactData.notes : existingContact.notes,
          groupIds: contactData.groups // Pass group IDs
        }
      });

      const updatedContact = response.data.updateContact;
      // Use groups returned by the mutation
      const groups = updatedContact.groups ? updatedContact.groups.map((g: any) => ({ id: g.id, name: g.name })) : [];
      const formattedContact: Contact = {
        id: updatedContact.id,
        name: updatedContact.name, // Use name directly
        phoneNumber: updatedContact.phoneNumber,
        email: updatedContact.email,
        notes: updatedContact.notes,
        groups: groups, // Use groups from the mutation response
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
          id: id // Pass ID as string (GraphQL ID type)
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
        contacts.value = response.data.searchContacts.map((contact: any): Contact => {
           // Map groups directly if they are fetched (assuming search might return groups too)
           const groups = contact.groups ? contact.groups.map((g: any) => ({ id: g.id, name: g.name })) : [];
          return {
            id: contact.id,
            name: contact.name, // Use name directly
            phoneNumber: contact.phoneNumber,
            email: contact.email,
            notes: contact.notes,
            groups: groups, // Assign fetched groups
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

  async function fetchContactsCount() {
    loading.value = true;
    try {
      const response = await apolloClient.query({
        query: COUNT_CONTACTS,
        fetchPolicy: 'network-only' // Force a network request to get the latest count
      });
      totalCount.value = response.data.contactsCount;
      return totalCount.value;
    } catch (err: any) {
      console.error('Erreur lors du comptage des contacts:', err);
      error.value = err.message || 'Erreur lors du comptage des contacts';
      return 0;
    } finally {
      loading.value = false;
    }
  }

  // Fetch groups for a specific contact
  async function fetchGroupsForContact(contactId: string) {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await apolloClient.query({
        query: FETCH_CONTACT_GROUPS,
        variables: {
          contactId
        },
        fetchPolicy: 'network-only' // Force a network request to get the latest data
      });
      
      const groups = response.data.groupsForContact.map((group: any) => ({
        id: group.id,
        name: group.name
      }));
      
      // Update the contact in the store with the fetched groups
      const contactIndex = contacts.value.findIndex(c => c.id === contactId);
      if (contactIndex !== -1) {
        contacts.value[contactIndex].groups = groups;
      }
      
      return groups;
    } catch (err: any) {
      console.error('Erreur lors de la récupération des groupes du contact:', err);
      error.value = err.message || 'Erreur lors de la récupération des groupes du contact';
      return [];
    } finally {
      loading.value = false;
    }
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
    fetchContactsCount,
    createContact,
    updateContact,
    deleteContact,
    searchContacts,
    fetchGroupsForContact, // Add the new function
    setPage,
    setItemsPerPage
  };
});
