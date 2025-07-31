<template>
  <div class="contacts-view">
    <!-- Header Section -->
    <ContactsHeader 
      :stats="stats" 
      :loading="loadingStats" 
    />

    <!-- Filters Section -->
    <ContactsFilters
      :filters="filters"
      :group-options="groupOptions"
      :loading="loading"
      @update-search="handleSearchUpdate"
      @update-group="handleGroupUpdate"
      @new-contact="showContactDialog = true"
      @refresh="handleRefresh"
      @import="showImportDialog = true"
      @clear-filters="handleClearFilters"
    />

    <!-- Contacts List Section -->
    <ContactsList
      :contacts="filteredContacts"
      :loading="loading"
      :total-count="totalCount"
      :pagination="pagination"
      :view-mode="viewMode"
      @add-contact="showContactDialog = true"
      @edit-contact="handleEditContact"
      @delete-contact="handleDeleteContact"
      @send-sms="handleSendSMS"
      @view-details="handleViewDetails"
      @clear-filters="handleClearFilters"
      @pagination-request="handlePaginationRequest"
      @page-change="handlePageChange"
      @items-per-page-change="handleItemsPerPageChange"
      @view-mode-change="handleViewModeChange"
    />

    <!-- Contact Detail Modal -->
    <ContactDetailModal
      v-model:visible="showDetailModal"
      :contact="selectedContact"
      :loading="detailLoading"
      @close="handleCloseDetailModal"
      @edit-contact="handleEditContact"
      @delete-contact="handleDeleteContact"
      @send-sms="handleSendSMS"
      @send-whatsapp="handleSendWhatsApp"
      @view-whatsapp-history="handleViewWhatsAppHistory"
      @view-sms-details="handleViewSMSDetails"
    />

    <!-- Contact Form Dialog -->
    <ContactFormDialog
      v-model="showContactDialog"
      :contact="editingContact"
      :groups="[]"
      :loading="savingContact"
      @cancel="handleCloseContactDialog"
      @save="handleSaveContact"
    />

    <!-- Import Dialog -->
    <ContactImportDialog
      v-model:visible="showImportDialog"
      :state="importState"
      @close="handleCloseImportDialog"
      @import-success="handleImportSuccess"
      @import-error="handleImportError"
    />

    <!-- Confirm Delete Dialog -->
    <ConfirmDialog
      v-model:visible="showDeleteDialog"
      :title="deleteDialogTitle"
      :message="deleteDialogMessage"
      :loading="deletingContact"
      @confirm="handleConfirmDelete"
      @cancel="handleCancelDelete"
    />

    <!-- Loading Overlay -->
    <LoadingOverlay v-if="initialLoading" :loading="initialLoading" />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import ContactsHeader from './components/ContactsHeader.vue';
import ContactsFilters from './components/ContactsFilters.vue';
import ContactsList from './components/ContactsList.vue';
import ContactDetailModal from './components/ContactDetailModal.vue';
import ContactImportDialog from './components/ContactImportDialog.vue';
import ContactFormDialog from '../../components/contacts/ContactFormDialog.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import LoadingOverlay from '../../components/LoadingOverlay.vue';

// Composables
import { useContactsData } from './composables/useContactsData';
import { useContactsFilters } from './composables/useContactsFilters';
import { useContactActions } from './composables/useContactActions';
import { useContactImport } from './composables/useContactImport';

// Types
import type { Contact, ViewMode } from './types/contacts.types';

// Router et Quasar
const router = useRouter();
const $q = useQuasar();

// Composables
const {
  contacts,
  stats,
  totalCount,
  pagination,
  loading: loadingContacts,
  refreshContacts
} = useContactsData();

const {
  filters,
  groupOptions,
  updateSearchTerm,
  updateGroupFilter,
  clearAllFilters
} = useContactsFilters();

const {
  actionState,
  createContact,
  updateContact,
  deleteContact
} = useContactActions();

const {
  importState,
  resetImport
} = useContactImport();

// Local state
const initialLoading = ref(true);
const loadingStats = ref(false);
const detailLoading = ref(false);
const viewMode = ref<ViewMode>('list');

// Dialog states
const showDetailModal = ref(false);
const showContactDialog = ref(false);
const showImportDialog = ref(false);
const showDeleteDialog = ref(false);

// Selected items
const selectedContact = ref<Contact | null>(null);
const editingContact = ref<Contact | null>(null);
const contactToDelete = ref<Contact | null>(null);

// Computed
const loading = computed(() => loadingContacts.value || initialLoading.value);
const savingContact = computed(() => actionState.value.saving);
const deletingContact = computed(() => actionState.value.deleting);
const filteredContacts = computed(() => contacts.value);

const deleteDialogTitle = computed(() => 
  contactToDelete.value ? `Supprimer ${contactToDelete.value.name}` : 'Supprimer le contact'
);

const deleteDialogMessage = computed(() => 
  contactToDelete.value 
    ? `Êtes-vous sûr de vouloir supprimer le contact "${contactToDelete.value.name}" ? Cette action est irréversible.`
    : 'Êtes-vous sûr de vouloir supprimer ce contact ?'
);

// Methods
async function initializeView(): Promise<void> {
  try {
    initialLoading.value = true;
    
    // Charger les données en parallèle
    await refreshContacts();
    
  } catch (error) {
    console.error('Erreur lors de l\'initialisation de la vue contacts:', error);
    showErrorNotification('Erreur lors du chargement des données');
  } finally {
    initialLoading.value = false;
  }
}

// Filter handlers
async function handleSearchUpdate(searchTerm: string): Promise<void> {
  await updateSearchTerm(searchTerm);
}

async function handleGroupUpdate(groupId: string | null): Promise<void> {
  await updateGroupFilter(groupId);
}

async function handleClearFilters(): Promise<void> {
  await clearAllFilters();
}

async function handleRefresh(): Promise<void> {
  try {
    loadingStats.value = true;
    await refreshContacts();
    showSuccessNotification('Données actualisées');
  } catch (error) {
    console.error('Erreur lors de l\'actualisation:', error);
    showErrorNotification('Erreur lors de l\'actualisation');
  } finally {
    loadingStats.value = false;
  }
}

// Pagination handlers
function handlePaginationRequest(_payload: { page: number; rowsPerPage: number; sortBy: string; descending: boolean }): void {
  // L'objet pagination est géré par le store
  refreshContacts();
}

function handlePageChange(_page: number): void {
  refreshContacts();
}

function handleItemsPerPageChange(_itemsPerPage: number): void {
  refreshContacts();
}

function handleViewModeChange(mode: ViewMode): void {
  viewMode.value = mode;
}

// Contact handlers
function handleEditContact(contact: Contact): void {
  editingContact.value = contact;
  showContactDialog.value = true;
}

async function handleSaveContact(contactData: any): Promise<void> {
  try {
    if (editingContact.value) {
      // Update existing contact
      await updateContact(editingContact.value.id, contactData);
      showSuccessNotification('Contact modifié avec succès');
    } else {
      // Create new contact
      await createContact(contactData);
      showSuccessNotification('Contact créé avec succès');
    }
    
    handleCloseContactDialog();
    await refreshContacts();
    
  } catch (error) {
    console.error('Erreur lors de la sauvegarde:', error);
    showErrorNotification('Erreur lors de la sauvegarde du contact');
  }
}

function handleDeleteContact(contact: Contact): void {
  contactToDelete.value = contact;
  showDeleteDialog.value = true;
}

async function handleConfirmDelete(): Promise<void> {
  if (!contactToDelete.value) return;
  
  try {
    await deleteContact(contactToDelete.value);
    showSuccessNotification('Contact supprimé avec succès');
    handleCancelDelete();
    await refreshContacts();
    
  } catch (error) {
    console.error('Erreur lors de la suppression:', error);
    showErrorNotification('Erreur lors de la suppression du contact');
  }
}

function handleCancelDelete(): void {
  contactToDelete.value = null;
  showDeleteDialog.value = false;
}

// Detail modal handlers
function handleViewDetails(contact: Contact): void {
  selectedContact.value = contact;
  showDetailModal.value = true;
}

function handleCloseDetailModal(): void {
  selectedContact.value = null;
  showDetailModal.value = false;
}

// Dialog handlers
function handleCloseContactDialog(): void {
  editingContact.value = null;
  showContactDialog.value = false;
}

function handleCloseImportDialog(): void {
  resetImport();
  showImportDialog.value = false;
}

async function handleImportSuccess(count: number): Promise<void> {
  showSuccessNotification(`${count} contact(s) importé(s) avec succès`);
  handleCloseImportDialog();
  await refreshContacts();
}

function handleImportError(error: string): void {
  showErrorNotification(`Erreur d'import: ${error}`);
}

// Navigation handlers
function handleSendSMS(contact: Contact): void {
  router.push({
    name: 'SMS',
    query: { phones: contact.phoneNumber }
  });
}

function handleSendWhatsApp(contact: Contact): void {
  router.push({
    name: 'whatsapp',
    query: { contacts: contact.id }
  });
}

// Notification helpers
function showSuccessNotification(message: string): void {
  $q.notify({
    type: 'positive',
    message,
    position: 'top'
  });
}

function showErrorNotification(message: string): void {
  $q.notify({
    type: 'negative',
    message,
    position: 'top'
  });
}

function handleViewWhatsAppHistory(contact: Contact): void {
  router.push({
    name: 'whatsapp',
    query: { contact: contact.id }
  });
}

function handleViewSMSDetails(sms: any): void {
  router.push({
    name: 'SMSHistory',
    query: { sms: sms.id }
  });
}

// Watchers sont gérés par les stores

// Lifecycle
onMounted(async () => {
  await initializeView();
});
</script>

<style lang="scss" scoped>
.contacts-view {
  padding: 1rem;
  max-width: 1400px;
  margin: 0 auto;
  
  @media (max-width: 768px) {
    padding: 0.5rem;
  }
}
</style>