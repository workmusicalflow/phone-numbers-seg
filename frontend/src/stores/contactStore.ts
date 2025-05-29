import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { apolloClient, gql } from '../services/api';
import type { WhatsAppContactInsights, WhatsAppContactSummary } from '../types/whatsapp-insights';

interface Contact {
  id: string;
  name: string; // Use name
  phoneNumber: string;
  email: string | null;
  notes: string | null;
  groups: ContactGroup[];
  createdAt: string;
  updatedAt: string;
  smsHistory?: SMSHistory[];
  smsTotalCount?: number;
  smsSentCount?: number;
  smsFailedCount?: number;
  smsScore?: number;
}

interface ContactGroup {
  id: string;
  name: string;
}

interface SMSHistory {
  id: string;
  message: string;
  status: string;
  createdAt: string;
  sentAt?: string | null;
  deliveredAt?: string | null;
  failedAt?: string | null;
  errorMessage?: string | null;
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
  const searchTerm = ref(''); // Stores the current search term
  const currentGroupId = ref<string | null>(null); // Stores the current group filter ID
  const sortBy = ref('name'); // Default sort column
  const sortDesc = ref(false); // Default sort direction

  // État WhatsApp Insights
  const whatsappInsights = ref<Map<string, WhatsAppContactInsights>>(new Map());
  const whatsappSummaries = ref<Map<string, WhatsAppContactSummary>>(new Map());
  const whatsappLoading = ref(false);
  const whatsappError = ref<string | null>(null);

  // Getters
  // Removed filteredContacts and paginatedContacts as data fetching handles this now.
  // Components should use the main 'contacts' ref which holds the current page's data.

  const pageCount = computed(() => {
    // Calculate page count based on the total count from the backend
    if (totalCount.value === 0) return 1; // Avoid division by zero, show at least 1 page
    return Math.ceil(totalCount.value / itemsPerPage.value);
  });

  // GraphQL Queries
  const FETCH_CONTACTS = gql`
    query GetContacts(
      $limit: Int
      $offset: Int
      $search: String
      $groupId: ID
      # $sortBy: String # TODO: Add sorting to backend schema
      # $sortDesc: Boolean
    ) {
      contacts(
        limit: $limit
        offset: $offset
        search: $search
        groupId: $groupId
      ) {
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
        smsHistory {
          id
          message
          status
          createdAt
          sentAt
          deliveredAt
          failedAt
          errorMessage
        }
        smsTotalCount
        smsSentCount
        smsFailedCount
        smsScore
      }
    }
  `;

  // Updated COUNT_CONTACTS to accept filters
  const COUNT_CONTACTS = gql`
    query CountContacts(
      $search: String,
      $groupId: ID
      # $sortBy: String # TODO: Add sorting to backend schema
      # $sortDesc: Boolean
      ) {
      contactsCount(
        search: $search,
        groupId: $groupId
        # sortBy: $sortBy # TODO: Add sorting to backend schema
        # sortDesc: $sortDesc
        )
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
        smsHistory {
          id
          message
          status
          createdAt
          sentAt
          deliveredAt
          failedAt
          errorMessage
        }
        smsTotalCount
        smsSentCount
        smsFailedCount
        smsScore
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
        smsHistory {
          id
          message
          status
          createdAt
          sentAt
          deliveredAt
          failedAt
          errorMessage
        }
        smsTotalCount
        smsSentCount
        smsFailedCount
        smsScore
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
        smsHistory {
          id
          message
          status
          createdAt
          sentAt
          deliveredAt
          failedAt
          errorMessage
        }
        smsTotalCount
        smsSentCount
        smsFailedCount
        smsScore
      }
    }
  `;

  // Removed SEARCH_CONTACTS as FETCH_CONTACTS now handles filtering

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

  // Unified fetch function with filtering
  async function fetchContacts() {
    loading.value = true;
    error.value = null;
    const variables = {
      limit: itemsPerPage.value,
      offset: (currentPage.value - 1) * itemsPerPage.value,
      search: searchTerm.value || null, // Use current searchTerm or null
      groupId: currentGroupId.value || null, // Use current group filter or null
      // sortBy: sortBy.value, // TODO: Add sorting to backend schema
      // sortDesc: sortDesc.value,
    };

    try {
      const response = await apolloClient.query({
        query: FETCH_CONTACTS, // Always use the main query now
        variables,
        fetchPolicy: 'network-only', // Ensure fresh data
      });

      // Check if response data and contacts property exists
      if (!response.data || !response.data.contacts) {
        console.error('Invalid GraphQL response format:', response);
        throw new Error('La réponse du serveur ne contient pas les données attendues');
      }
      
      // Transformer les contacts pour correspondre à l'interface Contact
      contacts.value = response.data.contacts.map((contact: any): Contact => {
        // Map groups directly if they are fetched
        const groups = contact.groups ? contact.groups.map((g: any) => ({ id: g.id, name: g.name })) : [];
        
        // Map SMS history if available
        const smsHistory = contact.smsHistory 
          ? contact.smsHistory.map((sms: any) => ({
              id: sms.id,
              message: sms.message,
              status: sms.status,
              createdAt: sms.createdAt,
              sentAt: sms.sentAt,
              deliveredAt: sms.deliveredAt,
              failedAt: sms.failedAt,
              errorMessage: sms.errorMessage
            }))
          : [];
          
        return {
          id: contact.id,
          name: contact.name, // Use name directly
          phoneNumber: contact.phoneNumber,
          email: contact.email,
          notes: contact.notes,
          groups: groups, // Assign fetched groups
          createdAt: contact.createdAt,
          updatedAt: contact.updatedAt,
          smsHistory: smsHistory,
          smsTotalCount: contact.smsTotalCount || 0,
          smsSentCount: contact.smsSentCount || 0,
          smsFailedCount: contact.smsFailedCount || 0,
          smsScore: contact.smsScore || 0
        };
      });

      // Fetch the total count based on current filters
      await fetchContactsCount(searchTerm.value || null, currentGroupId.value || null);

    } catch (err: any) {
      const action = searchTerm.value || currentGroupId.value ? 'du filtrage' : 'des contacts';
      
      // Enhanced error logging with more details
      console.error(`Erreur lors de la récupération ${action}:`, err);
      
      // Check for GraphQL errors
      if (err.graphQLErrors && err.graphQLErrors.length > 0) {
        console.error('GraphQL Errors:', err.graphQLErrors);
        error.value = `Erreur GraphQL: ${err.graphQLErrors.map((e: any) => e.message).join(', ')}`;
      } 
      // Check for network errors
      else if (err.networkError) {
        console.error('Network Error:', err.networkError);
        error.value = 'Erreur de réseau. Veuillez vérifier votre connexion.';
      } 
      // Default error handling
      else {
        error.value = err.message || `Erreur lors de la récupération ${action}`;
      }
      
      contacts.value = []; // Clear contacts on error
      totalCount.value = 0; // Reset count on error
    } finally {
      loading.value = false;
    }
  }

  async function createContact(contactData: ContactCreateData) {
    loading.value = true;
    error.value = null;
    
    try {
      console.log('Creating contact with data:', contactData);
      
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
      
      console.log('GraphQL response:', response);
      
      // Check for GraphQL errors
      if (response.errors && response.errors.length > 0) {
        const errorMessages = response.errors.map((e: any) => e.message).join(', ');
        console.error('GraphQL errors:', response.errors);
        error.value = `Erreur lors de la création du contact: ${errorMessages}`;
        throw new Error(error.value);
      }

      if (!response.data || !response.data.createContact) {
        console.error('Invalid response format:', response);
        error.value = 'Réponse invalide du serveur';
        throw new Error(error.value);
      }

      const newContact = response.data.createContact;
      // Use groups returned by the mutation
      const groups = newContact.groups ? newContact.groups.map((g: any) => ({ id: g.id, name: g.name })) : [];
      
      // Map SMS history if available
      const smsHistory = newContact.smsHistory 
        ? newContact.smsHistory.map((sms: any) => ({
            id: sms.id,
            message: sms.message,
            status: sms.status,
            createdAt: sms.createdAt,
            sentAt: sms.sentAt,
            deliveredAt: sms.deliveredAt,
            failedAt: sms.failedAt,
            errorMessage: sms.errorMessage
          }))
        : [];
        
      const formattedContact: Contact = {
        id: newContact.id,
        name: newContact.name, // Use name directly
        phoneNumber: newContact.phoneNumber,
        email: newContact.email,
        notes: newContact.notes,
        groups: groups, // Use groups from the mutation response
        createdAt: newContact.createdAt,
        updatedAt: newContact.updatedAt,
        smsHistory: smsHistory,
        smsTotalCount: newContact.smsTotalCount || 0,
        smsSentCount: newContact.smsSentCount || 0,
        smsFailedCount: newContact.smsFailedCount || 0,
        smsScore: newContact.smsScore || 0
      };
      
      // Instead of pushing locally, refetch the current page to ensure consistency
      await fetchContacts(); 
      // Optionally, navigate to the page where the new contact would appear if needed
      
      return formattedContact; // Return the newly created contact data
    } catch (err: any) {
      console.error('Erreur lors de la création du contact:', err);
      // Check for network errors
      if (err.networkError) {
        console.error('Network error details:', err.networkError);
        error.value = `Erreur réseau: ${err.networkError.message || 'Impossible de se connecter au serveur'}`;
      } 
      // Check for GraphQL errors (already handled above, but just in case)
      else if (err.graphQLErrors && err.graphQLErrors.length > 0) {
        console.error('GraphQL errors:', err.graphQLErrors);
        error.value = `Erreur GraphQL: ${err.graphQLErrors.map((e: any) => e.message).join(', ')}`;
      } 
      // Generic error fallback
      else {
        error.value = err.message || 'Erreur lors de la création du contact';
      }
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
      
      // Map SMS history if available
      const smsHistory = updatedContact.smsHistory 
        ? updatedContact.smsHistory.map((sms: any) => ({
            id: sms.id,
            message: sms.message,
            status: sms.status,
            createdAt: sms.createdAt,
            sentAt: sms.sentAt,
            deliveredAt: sms.deliveredAt,
            failedAt: sms.failedAt,
            errorMessage: sms.errorMessage
          }))
        : [];
        
      const formattedContact: Contact = {
        id: updatedContact.id,
        name: updatedContact.name, // Use name directly
        phoneNumber: updatedContact.phoneNumber,
        email: updatedContact.email,
        notes: updatedContact.notes,
        groups: groups, // Use groups from the mutation response
        createdAt: updatedContact.createdAt,
        updatedAt: updatedContact.updatedAt,
        smsHistory: smsHistory,
        smsTotalCount: updatedContact.smsTotalCount || 0,
        smsSentCount: updatedContact.smsSentCount || 0,
        smsFailedCount: updatedContact.smsFailedCount || 0,
        smsScore: updatedContact.smsScore || 0
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
      
      // Refetch the current page's data and total count after deletion
      await fetchContacts(); 

    } catch (err: any) {
      console.error('Erreur lors de la suppression du contact:', err);
      error.value = err.message || 'Erreur lors de la suppression du contact';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  // Action to initiate a search or clear it
  async function searchContacts(term: string) {
    searchTerm.value = term;
    currentPage.value = 1; // Reset to first page for new search/filter
    await fetchContacts();
  }

  // Action to filter by group
  async function filterByGroup(groupId: string | null) {
    currentGroupId.value = groupId;
    currentPage.value = 1; // Reset to first page for new filter
    await fetchContacts();
  }

  // Action to change the current page
  function setPage(page: number) {
    currentPage.value = page;
    fetchContacts();
  }

  // Action to change items per page
  function setItemsPerPage(limit: number) {
    itemsPerPage.value = limit;
    currentPage.value = 1; // Reset to first page when changing limit
    fetchContacts();
  }

  // Action to change sorting
  function setSorting(column: string, descending: boolean) {
    sortBy.value = column;
    sortDesc.value = descending;
    currentPage.value = 1; // Reset to first page when changing sort
    fetchContacts();
  }

  // Fetches the total count based on filters
  async function fetchContactsCount(search: string | null = null, groupId: string | null = null) {
    // No need to set loading here as it's usually called within fetchContacts
    try {
      const response = await apolloClient.query({
        query: COUNT_CONTACTS,
        variables: {
          search: search,
          groupId: groupId,
        },
        fetchPolicy: 'network-only', // Force a network request
      });
      totalCount.value = response.data.contactsCount;
      return totalCount.value;
    } catch (err: any) {
      console.error('Erreur lors du comptage des contacts filtrés:', err);
      // Don't overwrite main error, just log count error
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

  // Actions WhatsApp Insights
  async function fetchContactWhatsAppInsights(contactId: string): Promise<WhatsAppContactInsights | null> {
    whatsappLoading.value = true;
    whatsappError.value = null;

    try {
      // Utilisation du client REST au lieu de GraphQL
      const { whatsappInsightsClient } = await import('../services/whatsappInsightsClient');
      const insights = await whatsappInsightsClient.getContactInsights(contactId);
      
      if (insights) {
        whatsappInsights.value.set(contactId, insights);
        return insights;
      }
      return null;
    } catch (err: any) {
      console.error('Erreur lors de la récupération des insights WhatsApp:', err);
      whatsappError.value = err.message || 'Erreur lors de la récupération des insights WhatsApp';
      return null;
    } finally {
      whatsappLoading.value = false;
    }
  }

  async function fetchContactsWhatsAppSummary(contactIds: string[]): Promise<WhatsAppContactSummary[]> {
    if (contactIds.length === 0) return [];

    whatsappLoading.value = true;
    whatsappError.value = null;

    try {
      // Utilisation du client REST au lieu de GraphQL
      const { whatsappInsightsClient } = await import('../services/whatsappInsightsClient');
      const summaries = await whatsappInsightsClient.getContactsSummary(contactIds);
      
      // Mettre à jour le cache
      summaries.forEach((summary: WhatsAppContactSummary) => {
        whatsappSummaries.value.set(summary.contactId, summary);
      });

      return summaries;
    } catch (err: any) {
      console.error('Erreur lors de la récupération du résumé WhatsApp:', err);
      whatsappError.value = err.message || 'Erreur lors de la récupération du résumé WhatsApp';
      return [];
    } finally {
      whatsappLoading.value = false;
    }
  }

  function getContactWhatsAppInsights(contactId: string): WhatsAppContactInsights | null {
    return whatsappInsights.value.get(contactId) || null;
  }

  function getContactWhatsAppSummary(contactId: string): WhatsAppContactSummary | null {
    return whatsappSummaries.value.get(contactId) || null;
  }

  function clearWhatsAppCache(): void {
    whatsappInsights.value.clear();
    whatsappSummaries.value.clear();
    whatsappError.value = null;
  }

  // Computed pour les insights WhatsApp
  const hasWhatsAppData = computed(() => whatsappInsights.value.size > 0);
  const whatsappInsightsCount = computed(() => whatsappInsights.value.size);

  return {
    contacts,
    loading,
    error,
    currentPage,
    itemsPerPage,
    totalCount,
    searchTerm,
    currentGroupId, // Export group filter state
    pageCount,
    fetchContacts,
    fetchContactsCount,
    createContact,
    updateContact,
    deleteContact,
    searchContacts, // Keep for triggering search
    filterByGroup, // Add action for group filtering
    fetchGroupsForContact,
    setPage,
    setItemsPerPage,
    setSorting, // Add sorting action
    sortBy, // Export sorting state
    sortDesc, // Export sorting state
    
    // WhatsApp Insights exports
    whatsappInsights,
    whatsappSummaries,
    whatsappLoading,
    whatsappError,
    fetchContactWhatsAppInsights,
    fetchContactsWhatsAppSummary,
    getContactWhatsAppInsights,
    getContactWhatsAppSummary,
    clearWhatsAppCache,
    hasWhatsAppData,
    whatsappInsightsCount,
  };
});
