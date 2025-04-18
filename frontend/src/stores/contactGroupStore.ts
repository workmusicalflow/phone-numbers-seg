import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { gql } from '@apollo/client/core'; // Or your preferred GraphQL client library
import { useApolloClient } from '@vue/apollo-composable'; // Adjust if using a different client setup
import { notificationHelper } from '@/helpers/notificationHelper'; // Use the new helper
import type {
  ContactGroup,
  CreateContactGroupInput,
  UpdateContactGroupInput,
  AddContactsToGroupResult,
} from '@/types/contactGroup';
import type { Contact } from '@/types/contact';

export const useContactGroupStore = defineStore('contactGroup', () => {
  const { client } = useApolloClient(); // Get Apollo client instance
  const { showSuccess: notifySuccess, showError: notifyError } = notificationHelper; // Use the helper's methods

  // --- State ---
  const contactGroups = ref<ContactGroup[]>([]);
  const currentGroup = ref<ContactGroup | null>(null);
  const currentGroupContacts = ref<Contact[]>([]);
  const isLoading = ref(false);
  const isLoadingContacts = ref(false);
  const error = ref<Error | null>(null);
  const totalGroups = ref(0);
  const totalContactsInGroup = ref(0);

  // --- Getters ---
  const groupsForSelect = computed(() =>
    contactGroups.value.map((group) => ({
      id: group.id,
      name: group.name,
    })),
  );

  // --- Actions ---

  // Fetch all contact groups for the current user
  async function fetchContactGroups(limit = 100, offset = 0) {
    isLoading.value = true;
    error.value = null;
    try {
      const { data, errors } = await client.query<{
        contactGroups: ContactGroup[];
        contactGroupsCount: number;
      }>({
        query: gql`
          query GetContactGroups($limit: Int, $offset: Int) {
            contactGroups(limit: $limit, offset: $offset) {
              id
              name
              description
              contactCount
              createdAt
              updatedAt
            }
            contactGroupsCount
          }
        `,
        variables: { limit, offset },
        fetchPolicy: 'network-only', // Ensure fresh data
      });

      if (errors) throw new Error(errors.map((e) => e.message).join(', '));
      if (!data) throw new Error('Aucune donnée reçue du serveur.'); // Check if data is null/undefined

      contactGroups.value = data.contactGroups;
      totalGroups.value = data.contactGroupsCount;
      // notifySuccess('Groupes de contacts chargés.'); // Notification might be too noisy here
    } catch (err) {
      error.value = err as Error;
      notifyError(`Erreur lors du chargement des groupes: ${error.value?.message || 'Inconnue'}`);
      console.error('Error fetching contact groups:', err);
    } finally {
      isLoading.value = false;
    }
  }

  // Fetch a single contact group by ID
  async function fetchContactGroupById(id: string) {
    isLoading.value = true;
    error.value = null;
    currentGroup.value = null;
    try {
      const { data, errors } = await client.query<{
        contactGroup: ContactGroup | null;
      }>({
        query: gql`
          query GetContactGroup($id: ID!) {
            contactGroup(id: $id) {
              id
              name
              description
              contactCount
              createdAt
              updatedAt
            }
          }
        `,
        variables: { id },
        fetchPolicy: 'network-only',
      });

      if (errors) throw new Error(errors.map((e) => e.message).join(', '));
      if (!data) throw new Error('Aucune donnée reçue du serveur.'); // Check if data is null/undefined

      if (data.contactGroup) {
        currentGroup.value = data.contactGroup;
      } else {
        // Don't throw an error if not found, just set to null
        currentGroup.value = null;
        // notifyError('Groupe non trouvé.'); // Maybe not needed? Depends on UX
      }
    } catch (err) {
      error.value = err as Error;
      notifyError(`Erreur lors du chargement du groupe: ${error.value?.message || 'Inconnue'}`);
      console.error('Error fetching contact group by ID:', err);
    } finally {
      isLoading.value = false;
    }
  }

  // Fetch contacts within a specific group
  async function fetchContactsInGroup(groupId: string, limit = 50, offset = 0) {
    isLoadingContacts.value = true;
    error.value = null;
    currentGroupContacts.value = [];
    try {
      const { data, errors } = await client.query<{
        contactsInGroup: Contact[];
        contactsInGroupCount: number;
      }>({
        query: gql`
          query GetContactsInGroup($groupId: ID!, $limit: Int, $offset: Int) {
            contactsInGroup(groupId: $groupId, limit: $limit, offset: $offset) {
              id
              name
              phoneNumber
              email
              notes
              createdAt
              updatedAt
            }
            contactsInGroupCount(groupId: $groupId)
          }
        `,
        variables: { groupId, limit, offset },
        fetchPolicy: 'network-only',
      });

      if (errors) throw new Error(errors.map((e) => e.message).join(', '));
      if (!data) throw new Error('Aucune donnée reçue du serveur.'); // Check if data is null/undefined

      currentGroupContacts.value = data.contactsInGroup;
      totalContactsInGroup.value = data.contactsInGroupCount;
    } catch (err) {
      error.value = err as Error;
      notifyError(`Erreur lors du chargement des contacts du groupe: ${error.value?.message || 'Inconnue'}`);
      console.error('Error fetching contacts in group:', err);
    } finally {
      isLoadingContacts.value = false;
    }
  }

  // Create a new contact group
  async function createContactGroup(input: CreateContactGroupInput): Promise<ContactGroup | null> {
    isLoading.value = true;
    error.value = null;
    try {
      const { data, errors } = await client.mutate<{
        createContactGroup: ContactGroup;
      }>({
        mutation: gql`
          mutation CreateContactGroup($name: String!, $description: String) {
            createContactGroup(name: $name, description: $description) {
              id
              name
              description
              contactCount
              createdAt
              updatedAt
            }
          }
        `,
        variables: input,
      });

      if (errors) throw new Error(errors.map((e) => e.message).join(', '));
      if (!data?.createContactGroup) throw new Error('Réponse invalide du serveur lors de la création du groupe.'); // Check data and nested property

      const newGroup = data.createContactGroup;
      contactGroups.value.push(newGroup); // Add to local state
      totalGroups.value++;
      notifySuccess(`Groupe "${newGroup.name}" créé avec succès.`);
      return newGroup;
    } catch (err) {
      error.value = err as Error;
      notifyError(`Erreur lors de la création du groupe: ${error.value?.message || 'Inconnue'}`);
      console.error('Error creating contact group:', err);
      return null;
    } finally {
      isLoading.value = false;
    }
  }

  // Update an existing contact group
  async function updateContactGroup(input: UpdateContactGroupInput): Promise<ContactGroup | null> {
    isLoading.value = true;
    error.value = null;
    try {
      const { data, errors } = await client.mutate<{
        updateContactGroup: ContactGroup;
      }>({
        mutation: gql`
          mutation UpdateContactGroup($id: ID!, $name: String, $description: String) {
            updateContactGroup(id: $id, name: $name, description: $description) {
              id
              name
              description
              contactCount
              createdAt
              updatedAt
            }
          }
        `,
        variables: input,
      });

      if (errors) throw new Error(errors.map((e) => e.message).join(', '));
      if (!data?.updateContactGroup) throw new Error('Réponse invalide du serveur lors de la mise à jour du groupe.'); // Check data and nested property

      const updatedGroup = data.updateContactGroup;
      // Update local state
      const index = contactGroups.value.findIndex((g) => g.id === updatedGroup.id);
      if (index !== -1) {
        contactGroups.value[index] = updatedGroup;
      }
      if (currentGroup.value?.id === updatedGroup.id) {
        currentGroup.value = updatedGroup;
      }
      notifySuccess(`Groupe "${updatedGroup.name}" mis à jour.`);
      return updatedGroup;
    } catch (err) {
      error.value = err as Error;
      notifyError(`Erreur lors de la mise à jour du groupe: ${error.value?.message || 'Inconnue'}`);
      console.error('Error updating contact group:', err);
      return null;
    } finally {
      isLoading.value = false;
    }
  }

  // Delete a contact group
  async function deleteContactGroup(id: string): Promise<boolean> {
    isLoading.value = true;
    error.value = null;
    try {
      const { data, errors } = await client.mutate<{
        deleteContactGroup: boolean;
      }>({
        mutation: gql`
          mutation DeleteContactGroup($id: ID!) {
            deleteContactGroup(id: $id)
          }
        `,
        variables: { id },
      });

      if (errors) throw new Error(errors.map((e) => e.message).join(', '));
      if (data === null || data === undefined || typeof data.deleteContactGroup !== 'boolean') throw new Error('Réponse invalide du serveur lors de la suppression du groupe.'); // Check data and nested property type

      if (data.deleteContactGroup) {
        // Remove from local state
        contactGroups.value = contactGroups.value.filter((g) => g.id !== id);
        totalGroups.value--;
        if (currentGroup.value?.id === id) {
          currentGroup.value = null;
          currentGroupContacts.value = [];
          totalContactsInGroup.value = 0;
        }
        notifySuccess('Groupe supprimé avec succès.');
        return true;
      } else {
        // If deleteContactGroup returned false from backend
        throw new Error('La suppression du groupe a échoué côté serveur.');
      }
    } catch (err) {
      error.value = err as Error;
      notifyError(`Erreur lors de la suppression du groupe: ${error.value?.message || 'Inconnue'}`);
      console.error('Error deleting contact group:', err);
      return false;
    } finally {
      isLoading.value = false;
    }
  }

  // Add a single contact to a group
  async function addContactToGroup(contactId: string, groupId: string): Promise<boolean> {
    isLoading.value = true; // Or a specific loading state?
    error.value = null;
    try {
      const { data, errors } = await client.mutate<{
        addContactToGroup: { id: string }; // Assuming it returns the membership ID
      }>({
        mutation: gql`
          mutation AddContactToGroup($contactId: ID!, $groupId: ID!) {
            addContactToGroup(contactId: $contactId, groupId: $groupId) {
              id # Request membership ID
            }
          }
        `,
        variables: { contactId, groupId },
      });

      if (errors) throw new Error(errors.map((e) => e.message).join(', '));
      if (!data?.addContactToGroup) throw new Error('Réponse invalide du serveur lors de l\'ajout du contact au groupe.'); // Check data and nested property

      if (data.addContactToGroup) {
        notifySuccess('Contact ajouté au groupe.');
        // Optionally refetch group details or contacts in group if needed
        const groupIndex = contactGroups.value.findIndex((g) => g.id === groupId);
        if (groupIndex !== -1) {
          const group = {...contactGroups.value[groupIndex]};
          group.contactCount++;
          contactGroups.value[groupIndex] = group;
        }
        
        if (currentGroup.value?.id === groupId) {
          currentGroup.value = {
            ...currentGroup.value,
            contactCount: currentGroup.value.contactCount + 1
          };
        }
        return true;
      } else {
        // This case should ideally be handled by the check above, but as a fallback:
        throw new Error("L'ajout du contact au groupe a échoué (réponse invalide).");
      }
    } catch (err) {
      error.value = err as Error;
      notifyError(`Erreur lors de l'ajout du contact au groupe: ${error.value?.message || 'Inconnue'}`);
      console.error('Error adding contact to group:', err);
      return false;
    } finally {
      isLoading.value = false;
    }
  }

  // Remove a single contact from a group
  async function removeContactFromGroup(contactId: string, groupId: string): Promise<boolean> {
    isLoading.value = true; // Or a specific loading state?
    error.value = null;
    try {
      const { data, errors } = await client.mutate<{
        removeContactFromGroup: boolean;
      }>({
        mutation: gql`
          mutation RemoveContactFromGroup($contactId: ID!, $groupId: ID!) {
            removeContactFromGroup(contactId: $contactId, groupId: $groupId)
          }
        `,
        variables: { contactId, groupId },
      });

      if (errors) throw new Error(errors.map((e) => e.message).join(', '));
      if (data === null || data === undefined || typeof data.removeContactFromGroup !== 'boolean') throw new Error('Réponse invalide du serveur lors du retrait du contact du groupe.'); // Check data and nested property type

      if (data.removeContactFromGroup) {
        notifySuccess('Contact retiré du groupe.');
        // Update local state if necessary
        currentGroupContacts.value = currentGroupContacts.value.filter((c) => c.id !== contactId);
        totalContactsInGroup.value--;
        
        const groupIndex = contactGroups.value.findIndex(g => g.id === groupId);
        if (groupIndex !== -1) {
          const group = {...contactGroups.value[groupIndex]};
          group.contactCount--;
          contactGroups.value[groupIndex] = group;
        }
        
        if (currentGroup.value?.id === groupId) {
          currentGroup.value = {
            ...currentGroup.value,
            contactCount: currentGroup.value.contactCount - 1
          };
        }
        return true;
      } else {
        // This might happen if the contact wasn't in the group or backend returned false
        notifyError('Le contact n\'était pas dans ce groupe ou la suppression a échoué.');
        return false;
      }
    } catch (err) {
      error.value = err as Error;
      notifyError(`Erreur lors du retrait du contact du groupe: ${error.value?.message || 'Inconnue'}`);
      console.error('Error removing contact from group:', err);
      return false;
    } finally {
      isLoading.value = false;
    }
  }

   // Add multiple contacts to a group
  async function addContactsToGroup(contactIds: string[], groupId: string): Promise<AddContactsToGroupResult | null> {
    isLoading.value = true;
    error.value = null;
    try {
      const { data, errors } = await client.mutate<{
        addContactsToGroup: AddContactsToGroupResult;
      }>({
        mutation: gql`
          mutation AddContactsToGroup($contactIds: [ID!]!, $groupId: ID!) {
            addContactsToGroup(contactIds: $contactIds, groupId: $groupId) {
              status
              message
              successful
              failed
              # memberships { id contact { id name } group { id name } } # Optionally fetch details
              errors { contactId message }
            }
          }
        `,
        variables: { contactIds, groupId },
      });

      if (errors) throw new Error(errors.map((e) => e.message).join(', '));
      if (!data?.addContactsToGroup) throw new Error('Réponse invalide du serveur lors de l\'ajout des contacts au groupe.'); // Check data and nested property

      const result = data.addContactsToGroup;
      if (result.status === 'success' || result.status === 'partial') {
        notifySuccess(result.message);
         // Update counts
        const groupIndex = contactGroups.value.findIndex((g) => g.id === groupId);
        if (groupIndex !== -1) {
          const group = {...contactGroups.value[groupIndex]};
          group.contactCount += result.successful;
          contactGroups.value[groupIndex] = group;
        }
        
        if (currentGroup.value?.id === groupId) {
          currentGroup.value = {
            ...currentGroup.value,
            contactCount: currentGroup.value.contactCount + result.successful
          };
        }
      } else {
         notifyError(result.message || 'Erreur lors de l\'ajout des contacts.');
      }
      return result;

    } catch (err) {
      error.value = err as Error;
      notifyError(`Erreur lors de l'ajout des contacts au groupe: ${error.value?.message || 'Inconnue'}`);
      console.error('Error adding contacts to group:', err);
      return null;
    } finally {
      isLoading.value = false;
    }
  }


  return {
    // State
    contactGroups,
    currentGroup,
    currentGroupContacts,
    isLoading,
    isLoadingContacts,
    error,
    totalGroups,
    totalContactsInGroup,

    // Getters
    groupsForSelect,

    // Actions
    fetchContactGroups,
    fetchContactGroupById,
    fetchContactsInGroup,
    createContactGroup,
    updateContactGroup,
    deleteContactGroup,
    addContactToGroup,
    removeContactFromGroup,
    addContactsToGroup,
  };
});
