/**
 * Composable pour les actions CRUD des contacts
 * Gère la création, mise à jour, suppression et notifications
 */

import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import { useContactStore } from '../../../stores/contactStore';
import type { 
  Contact, 
  ContactFormData, 
  ContactCreateData, 
  ContactActionState 
} from '../types/contacts.types';

export function useContactActions() {
  // Dependencies
  const router = useRouter();
  const $q = useQuasar();
  const contactStore = useContactStore();

  // État local pour les actions
  const actionState = ref<ContactActionState>({
    saving: false,
    deleting: false,
    error: null
  });

  // Contact sélectionné pour les actions
  const selectedContact = ref<Contact | null>(null);
  const contactToDelete = ref<Contact | null>(null);

  // Computed pour les états
  const isSaving = computed(() => actionState.value.saving);
  const isDeleting = computed(() => actionState.value.deleting);
  const hasError = computed(() => !!actionState.value.error);
  const isProcessing = computed(() => isSaving.value || isDeleting.value);

  /**
   * Crée un nouveau contact
   */
  async function createContact(formData: ContactFormData): Promise<Contact | null> {
    actionState.value.saving = true;
    actionState.value.error = null;

    try {
      const contactData: ContactCreateData = {
        name: formData.name,
        phoneNumber: formData.phoneNumber,
        email: formData.email || null,
        groups: formData.groups.map(id => String(id)),
        notes: formData.notes || null
      };

      console.log('Creating contact:', contactData);

      const newContact = await contactStore.createContact(contactData);

      $q.notify({
        color: 'positive',
        message: 'Contact créé avec succès',
        icon: 'check_circle',
        position: 'top'
      });

      return newContact;
    } catch (error: any) {
      console.error('Erreur lors de la création du contact:', error);
      
      const errorMessage = contactStore.error || error.message || 'Erreur lors de la création du contact';
      actionState.value.error = errorMessage;
      
      $q.notify({
        color: 'negative',
        message: errorMessage,
        icon: 'error',
        position: 'top',
        timeout: 5000,
        multiLine: true,
        actions: [
          { 
            icon: 'close', 
            color: 'white',
            handler: () => { /* close */ } 
          }
        ]
      });

      return null;
    } finally {
      actionState.value.saving = false;
    }
  }

  /**
   * Met à jour un contact existant
   */
  async function updateContact(id: string, formData: ContactFormData): Promise<Contact | null> {
    actionState.value.saving = true;
    actionState.value.error = null;

    try {
      const contactData: Partial<ContactCreateData> = {
        name: formData.name,
        phoneNumber: formData.phoneNumber,
        email: formData.email || null,
        groups: formData.groups.map(id => String(id)),
        notes: formData.notes || null
      };

      const updatedContact = await contactStore.updateContact(id, contactData);

      $q.notify({
        color: 'positive',
        message: 'Contact mis à jour avec succès',
        icon: 'check_circle',
        position: 'top'
      });

      return updatedContact;
    } catch (error: any) {
      console.error('Erreur lors de la mise à jour du contact:', error);
      
      const errorMessage = contactStore.error || error.message || 'Erreur lors de la mise à jour du contact';
      actionState.value.error = errorMessage;

      $q.notify({
        color: 'negative',
        message: errorMessage,
        icon: 'error',
        position: 'top',
        timeout: 5000
      });

      return null;
    } finally {
      actionState.value.saving = false;
    }
  }

  /**
   * Supprime un contact
   */
  async function deleteContact(contact: Contact): Promise<boolean> {
    contactToDelete.value = contact;
    actionState.value.deleting = true;
    actionState.value.error = null;

    try {
      await contactStore.deleteContact(contact.id);

      $q.notify({
        color: 'positive',
        message: 'Contact supprimé avec succès',
        icon: 'check_circle',
        position: 'top'
      });

      contactToDelete.value = null;
      return true;
    } catch (error: any) {
      console.error('Erreur lors de la suppression du contact:', error);
      
      const errorMessage = contactStore.error || error.message || 'Erreur lors de la suppression du contact';
      actionState.value.error = errorMessage;

      $q.notify({
        color: 'negative',
        message: errorMessage,
        icon: 'error',
        position: 'top'
      });

      return false;
    } finally {
      actionState.value.deleting = false;
    }
  }

  /**
   * Navigue vers l'envoi de SMS
   */
  function sendSMS(contact: Contact): void {
    router.push({
      path: '/sms',
      query: {
        recipient: contact.phoneNumber,
        name: contact.name
      }
    });
  }

  /**
   * Navigue vers l'envoi WhatsApp
   */
  function sendWhatsApp(contact: Contact): void {
    router.push({
      path: '/whatsapp',
      query: {
        recipient: contact.phoneNumber,
        name: contact.name
      }
    });
  }

  /**
   * Navigue vers l'historique WhatsApp
   */
  function viewWhatsAppHistory(contact: Contact): void {
    router.push({
      path: '/whatsapp/messages',
      query: {
        phone: contact.phoneNumber,
        name: contact.name
      }
    });
  }

  /**
   * Sélectionne un contact pour édition
   */
  function selectContactForEdit(contact: Contact | null): void {
    selectedContact.value = contact;
  }

  /**
   * Efface l'erreur actuelle
   */
  function clearError(): void {
    actionState.value.error = null;
  }

  /**
   * Reset l'état des actions
   */
  function resetActionState(): void {
    actionState.value = {
      saving: false,
      deleting: false,
      error: null
    };
    selectedContact.value = null;
    contactToDelete.value = null;
  }

  return {
    // État
    actionState,
    selectedContact,
    contactToDelete,
    isSaving,
    isDeleting,
    hasError,
    isProcessing,

    // Actions CRUD
    createContact,
    updateContact,
    deleteContact,

    // Actions de navigation
    sendSMS,
    sendWhatsApp,
    viewWhatsAppHistory,

    // Utilitaires
    selectContactForEdit,
    clearError,
    resetActionState
  };
}