<template>
  <q-page padding>
    <div class="contacts-page">
      <!-- Modern Page Header -->
      <div class="page-header">
        <div class="header-content">
          <div class="header-title-section">
            <div class="title-icon-wrapper">
              <q-icon name="contacts" size="md" />
            </div>
            <div class="title-text">
              <h1 class="page-title">Gestion des Contacts</h1>
              <p class="page-subtitle">Organisez et gérez votre carnet d'adresses</p>
            </div>
          </div>
          
          <div class="header-stats">
            <div class="stat-card">
              <div class="stat-value">{{ contactsCount }}</div>
              <div class="stat-label">Total</div>
            </div>
            <div class="stat-card">
              <div class="stat-value">{{ activeContacts }}</div>
              <div class="stat-label">Actifs</div>
            </div>
            <div class="stat-card">
              <div class="stat-value">{{ contactGroupStore.groups?.length || 0 }}</div>
              <div class="stat-label">Groupes</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Search and Filters Section -->
      <div class="filters-section">
        <div class="modern-card">
          <div class="card-header contacts-gradient">
            <div class="header-content">
              <q-icon name="filter_list" size="md" class="header-icon" />
              <div class="header-text">
                <h3 class="header-title">Recherche et Filtres</h3>
                <p class="header-subtitle">Trouvez rapidement vos contacts</p>
              </div>
            </div>
            <div class="header-actions">
              <q-btn
                color="white"
                text-color="white"
                icon="add"
                label="Nouveau Contact"
                outline
                size="sm"
                @click="openContactDialog()"
                class="modern-btn"
              />
            </div>
          </div>

          <div class="card-content">
            <div class="filters-grid">
              <div class="filter-item">
                <q-input
                  v-model="searchTermModel"
                  label="Rechercher nom ou numéro..."
                  outlined
                  clearable
                  debounce="300"
                  class="modern-input"
                >
                  <template v-slot:prepend>
                    <q-icon name="search" />
                  </template>
                </q-input>
              </div>

              <div class="filter-item">
                <q-select
                  v-model="selectedGroupIdModel"
                  :options="groupOptions"
                  label="Filtrer par groupe"
                  outlined
                  clearable
                  emit-value
                  map-options
                  :loading="contactGroupStore.isLoading"
                  class="modern-select"
                >
                  <template v-slot:prepend>
                    <q-icon name="group" />
                  </template>
                </q-select>
              </div>

              <div class="filter-item actions-item">
                <div class="quick-actions">
                  <q-btn
                    color="primary"
                    icon="refresh"
                    label="Actualiser"
                    @click="refreshContacts"
                    class="action-btn"
                  />
                  <q-btn
                    color="secondary"
                    icon="import_export"
                    label="Importer"
                    @click="openImportDialog"
                    class="action-btn"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>


      <!-- Contact Detail View -->
      <div v-if="selectedContactDetail" class="detail-section">
        <div class="modern-card">
          <div class="card-header contacts-gradient">
            <div class="header-content">
              <q-icon name="person" size="md" class="header-icon" />
              <div class="header-text">
                <h3 class="header-title">Détails du Contact</h3>
                <p class="header-subtitle">{{ selectedContactDetail.name }}</p>
              </div>
            </div>
            <div class="header-actions">
              <q-btn
                color="white"
                text-color="primary"
                icon="arrow_back"
                label="Retour"
                outline
                size="sm"
                @click="selectedContactDetail = null"
                class="modern-btn"
              />
            </div>
          </div>

          <div class="card-content">
            <ContactDetailView
              :contact="selectedContactDetail"
              :loading="contactStore.loading"
              @edit="openContactDialog"
              @delete="confirmDelete"
              @send-sms="sendSMS"
              @view-sms-details="viewSMSDetails"
            />
          </div>
        </div>
      </div>
      
      <!-- Contacts List View -->
      <div v-else class="contacts-list-section">
        <div class="modern-card">
          <div class="card-header contacts-gradient">
            <div class="header-content">
              <q-icon name="list" size="md" class="header-icon" />
              <div class="header-text">
                <h3 class="header-title">Liste des Contacts</h3>
                <p class="header-subtitle">{{ contactStore.totalCount || 0 }} contact{{ (contactStore.totalCount || 0) !== 1 ? 's' : '' }} au total</p>
              </div>
            </div>
            <div class="header-actions">
              <q-btn
                color="white"
                text-color="white"
                icon="view_module"
                label="Vue Grille"
                outline
                size="sm"
                @click="toggleViewMode"
                class="modern-btn"
              />
            </div>
          </div>

          <div class="card-content">
            <div class="table-wrapper">
              <ContactTable
                :contacts="contactStore.contacts || []"
                :loading="contactStore.loading"
                :pagination="{ ...pagination, rowsNumber: contactStore.totalCount || 0 }" 
                @request="onRequest"
                @edit="openContactDialog"
                @delete="confirmDelete"
                @send-sms="sendSMS"
                @view-details="viewContactDetails"
                class="modern-table"
              />
            </div>

            <!-- Modern Pagination -->
            <div v-if="(contactStore.totalCount || 0) > 0" class="pagination-section">
              <BasePagination
                :total-items="contactStore.totalCount || 0"
                :items-per-page="pagination.rowsPerPage"
                :initial-page="currentPage"
                @page-change="onPageChange"
                @items-per-page-change="onItemsPerPageChange"
              />
            </div>

            <!-- Empty State -->
            <div v-else-if="!contactStore.loading" class="empty-state">
              <q-icon name="contacts" size="4rem" color="grey-5" />
              <h4 class="empty-title">Aucun contact trouvé</h4>
              <p class="empty-text">
                {{ searchTermModel || selectedGroupIdModel 
                   ? 'Aucun résultat pour ces critères de recherche' 
                   : 'Commencez par ajouter votre premier contact' }}
              </p>
              <q-btn
                v-if="!searchTermModel && !selectedGroupIdModel"
                color="primary"
                icon="add"
                label="Ajouter un contact"
                @click="openContactDialog()"
                class="empty-action-btn"
              />
              <q-btn
                v-else
                color="primary"
                label="Effacer les filtres"
                @click="clearFilters"
                outline
                class="empty-action-btn"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Modern Contact Form Dialog -->
      <ContactFormDialog
        v-model="contactDialog"
        :contact="selectedContact"
        :groups="contactGroupStore.groupsForSelect || []" 
        :loading="saving"
        @save="saveContact"
        @cancel="contactDialog = false"
      />

      <!-- Modern Confirmation Dialog -->
      <ConfirmationDialog
        v-model="deleteDialog"
        message="Êtes-vous sûr de vouloir supprimer ce contact?"
        icon="warning"
        color="negative"
        confirm-label="Supprimer"
        cancel-label="Annuler"
        confirm-color="negative"
        :loading="deleting"
        @confirm="deleteContact"
        @cancel="deleteDialog = false"
      />

      <!-- Import Dialog -->
      <q-dialog v-model="importDialog" persistent>
        <div class="import-dialog">
          <div class="modern-card">
            <div class="card-header contacts-gradient">
              <div class="header-content">
                <q-icon name="import_export" size="md" class="header-icon" />
                <div class="header-text">
                  <h3 class="header-title">Importer des Contacts</h3>
                  <p class="header-subtitle">Ajoutez plusieurs contacts depuis un fichier</p>
                </div>
              </div>
              <div class="header-actions">
                <q-btn
                  color="white"
                  text-color="primary"
                  icon="close"
                  round
                  flat
                  size="sm"
                  v-close-popup
                  class="close-btn"
                />
              </div>
            </div>

            <div class="card-content">
              <div class="import-content">
                <div class="import-section">
                  <h4 class="section-title">Format de fichier</h4>
                  <p class="section-text">
                    Importez un fichier CSV avec les colonnes : nom, numéro, email (optionnel), notes (optionnel)
                  </p>
                </div>

                <div class="import-section">
                  <h4 class="section-title">Sélectionner un fichier</h4>
                  <q-file
                    v-model="importFile"
                    label="Choisir un fichier CSV"
                    outlined
                    accept=".csv"
                    max-file-size="5242880"
                    class="import-file-input"
                  >
                    <template v-slot:prepend>
                      <q-icon name="attach_file" />
                    </template>
                  </q-file>
                </div>
              </div>
            </div>

            <div class="dialog-actions">
              <q-btn
                color="primary"
                icon="upload"
                label="Importer"
                @click="processImport"
                :loading="importing"
                :disable="!importFile"
                class="action-btn-primary"
              />
              <q-btn
                color="grey-7"
                label="Annuler"
                v-close-popup
                class="action-btn-secondary"
              />
            </div>
          </div>
        </div>
      </q-dialog>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import { useContactStore } from '../stores/contactStore';
import { useContactGroupStore } from '../stores/contactGroupStore';
import { Contact, ContactFormData, ContactCreateData } from '../types/contact';

// Composants
import ContactCountBadge from '../components/common/ContactCountBadge.vue';
import BasePagination from '../components/BasePagination.vue';
import ContactTable from '../components/contacts/ContactTable.vue';
import ContactFormDialog from '../components/contacts/ContactFormDialog.vue';
import ConfirmationDialog from '../components/common/ConfirmationDialog.vue';
import ContactDetailView from '../components/contacts/ContactDetailView.vue';

// Router et Quasar
const router = useRouter();
const $q = useQuasar();

// Stores
const contactStore = useContactStore();
const contactGroupStore = useContactGroupStore();

// État local
const contactDialog = ref(false);
const deleteDialog = ref(false);
const importDialog = ref(false);
const saving = ref(false);
const deleting = ref(false);
const importing = ref(false);
const currentPage = ref(1);
const selectedContact = ref<Contact | null>(null);
const contactToDelete = ref<Contact | null>(null);
const contactsCount = ref(0);
const selectedContactDetail = ref<Contact | null>(null);
const importFile = ref<File | null>(null);
const viewMode = ref<'list' | 'grid'>('list');

// Computed properties for statistics
const activeContacts = computed(() => {
  return contactStore.contacts?.filter(contact => 
    contact.phoneNumber && contact.phoneNumber.trim() !== ''
  ).length || 0;
});

// Fonction pour rafraîchir le nombre de contacts
const refreshContactsCount = async () => {
  contactsCount.value = await contactStore.fetchContactsCount();
};

// Pagination & Filtres
const pagination = ref({
  sortBy: 'lastName', // Default sort
  descending: false,
  page: 1,
  rowsPerPage: 10,
  // rowsNumber will be dynamically updated via watchEffect or similar
});

// Computed property for search term model
const searchTermModel = computed({
  get: () => contactStore.searchTerm, // Corrected: use searchTerm
  set: (value) => {
    // Trigger search action in the store
    contactStore.searchContacts(value || ''); // Ensure empty string if null/undefined
  }
});

// Computed property for selected group ID model
const selectedGroupIdModel = computed({
  get: () => contactStore.currentGroupId,
  set: (value) => {
    // Trigger filter action in the store
    contactStore.filterByGroup(value);
  }
});

// Computed property for group options for the select dropdown
const groupOptions = computed(() => {
  // Map groups to the format required by q-select: { label: string, value: number | null }
  // Add an option for "All Groups"
  return [
    { label: 'Tous les groupes', value: null },
    ...contactGroupStore.groupsForSelect
  ];
});


// Nouvelles méthodes modernes

function refreshContacts() {
  contactStore.fetchContacts();
  refreshContactsCount();
  $q.notify({
    color: 'positive',
    message: 'Contacts actualisés',
    icon: 'refresh'
  });
}

function openImportDialog() {
  importDialog.value = true;
  importFile.value = null;
}

async function processImport() {
  if (!importFile.value) return;
  
  importing.value = true;
  try {
    // Simuler le traitement d'import (à implémenter selon votre API)
    await new Promise(resolve => setTimeout(resolve, 2000));
    
    $q.notify({
      color: 'positive',
      message: 'Contacts importés avec succès',
      icon: 'upload'
    });
    
    importDialog.value = false;
    refreshContacts();
  } catch (error) {
    console.error('Erreur lors de l\'import:', error);
    $q.notify({
      color: 'negative',
      message: 'Erreur lors de l\'import des contacts',
      icon: 'error'
    });
  } finally {
    importing.value = false;
  }
}

function toggleViewMode() {
  viewMode.value = viewMode.value === 'list' ? 'grid' : 'list';
  $q.notify({
    color: 'info',
    message: `Mode ${viewMode.value === 'list' ? 'liste' : 'grille'} activé`,
    icon: viewMode.value === 'list' ? 'list' : 'view_module'
  });
}

function clearFilters() {
  contactStore.searchContacts('');
  contactStore.filterByGroup(null);
  $q.notify({
    color: 'info',
    message: 'Filtres effacés',
    icon: 'clear'
  });
}

// Méthodes

function onPageChange(page: number) {
  currentPage.value = page;
  contactStore.setPage(page);
}

function onItemsPerPageChange(itemsPerPage: number) {
  pagination.value.rowsPerPage = itemsPerPage;
  contactStore.setItemsPerPage(itemsPerPage);
  // Retourner à la première page lors du changement d'éléments par page
  currentPage.value = 1;
  contactStore.setPage(1);
}

// QTable @request handler
function onRequest(paginationPayload: { page: number; rowsPerPage: number; sortBy: string; descending: boolean }) {
  const { page, rowsPerPage, sortBy, descending } = paginationPayload;

  // Update local pagination state used by QTable
  pagination.value.page = page;
  pagination.value.rowsPerPage = rowsPerPage;
  pagination.value.sortBy = sortBy;
  pagination.value.descending = descending;

  // Update store state which triggers fetchContacts with current filters AND new pagination/sorting
  contactStore.setPage(page);
  contactStore.setItemsPerPage(rowsPerPage);
  contactStore.setSorting(sortBy, descending);
  // fetchContacts is automatically called by the store actions
}


function openContactDialog(contact?: Contact) {
  selectedContact.value = contact || null;
  contactDialog.value = true;
}

async function saveContact(formData: ContactFormData) {
  saving.value = true;
  try {
    // Prepare data using the 'name' field
    const contactData: ContactCreateData = {
      name: formData.name, // Use name field
      phoneNumber: formData.phoneNumber,
      email: formData.email || null,
      groups: formData.groups.map(id => String(id)), // Convertir en string[]
      notes: formData.notes || null
    };

    console.log('Saving contact:', contactData);

    if (selectedContact.value) {
      // Mode édition
      await contactStore.updateContact(formData.id, contactData);
      $q.notify({
        color: 'positive',
        message: 'Contact mis à jour avec succès',
        icon: 'check_circle',
        position: 'top'
      });
    } else {
      // Mode création
      await contactStore.createContact(contactData);
      $q.notify({
        color: 'positive',
        message: 'Contact créé avec succès',
        icon: 'check_circle',
        position: 'top'
      });
    }
    contactDialog.value = false;
    // Rafraîchir le nombre de contacts
    refreshContactsCount();
  } catch (error: any) {
    console.error('Erreur lors de la sauvegarde du contact:', error);
    
    // Get the error message from the store if available
    const errorMessage = contactStore.error || error.message || 'Erreur lors de la sauvegarde du contact';
    
    $q.notify({
      color: 'negative',
      message: errorMessage,
      icon: 'error',
      position: 'top',
      timeout: 5000, // Show message for longer
      multiLine: true, // Allow for longer error messages
      actions: [
        { 
          icon: 'close', 
          color: 'white',
          handler: () => { /* close */ } 
        }
      ]
    });
  } finally {
    saving.value = false;
  }
}

function confirmDelete(contact: Contact) {
  contactToDelete.value = contact;
  deleteDialog.value = true;
}

async function deleteContact() {
  if (!contactToDelete.value) return;
  
  deleting.value = true;
  try {
    await contactStore.deleteContact(contactToDelete.value.id);
    $q.notify({
      color: 'positive',
      message: 'Contact supprimé avec succès',
      icon: 'check_circle',
      position: 'top'
    });
    deleteDialog.value = false;
    // Rafraîchir le nombre de contacts
    refreshContactsCount();
  } catch (error) {
    console.error('Erreur lors de la suppression du contact:', error);
    $q.notify({
      color: 'negative',
      message: 'Erreur lors de la suppression du contact',
      icon: 'error',
      position: 'top'
    });
  } finally {
    deleting.value = false;
  }
}

function sendSMS(contact: Contact) {
  router.push({
    path: '/sms',
    query: {
      recipient: contact.phoneNumber,
      name: contact.name // Use name field
    }
  });
}

function viewContactDetails(contact: Contact) {
  selectedContactDetail.value = contact;
}

function viewSMSDetails(sms: any) {
  // This function is just for handling the event from the SMS history component
  // The actual display of SMS details is handled in the ContactSMSHistory component
  console.log('SMS details viewed:', sms);
}

// Cycle de vie
onMounted(async () => {
  // Fetch initial data including groups for the filter dropdown
  await contactGroupStore.fetchContactGroups(); // Fetch groups first
  await contactStore.fetchContacts(); // Fetch contacts using internal store state
  // Récupérer le nombre de contacts initial
  contactsCount.value = contactStore.totalCount; // Use count from store state after fetch
});

// Surveiller les changements dans le store pour mettre à jour la pagination locale et le compte total
watch(() => contactStore.currentPage, (newPage) => {
  currentPage.value = newPage;
  pagination.value.page = newPage; // Keep local pagination in sync
});

watch(() => contactStore.totalCount, (newCount) => {
  // Update the total rows number for QTable pagination
  // QTable uses this value internally, no need for a separate computed property in pagination ref
  // This ensures the pagination component reflects the total number of items correctly
  // pagination.value.rowsNumber = newCount; // QTable handles this internally based on the prop passed to it
  contactsCount.value = newCount; // Update the badge as well
});

// Watch for changes in items per page from the BasePagination component
watch(() => pagination.value.rowsPerPage, (newItemsPerPage) => {
    if (newItemsPerPage !== contactStore.itemsPerPage) {
        contactStore.setItemsPerPage(newItemsPerPage);
    }
});

</script>

<style lang="scss" scoped>
// Contacts Color Palette
$contacts-primary: #673ab7;
$contacts-secondary: #9c27b0;
$contacts-accent: #3f51b5;
$contacts-light: #f3e5f5;

// Design System Integration
.contacts-page {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0;
}

// Modern Page Header
.page-header {
  background: linear-gradient(135deg, $contacts-primary 0%, $contacts-secondary 100%);
  border-radius: 16px;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 8px 32px rgba(103, 58, 183, 0.2);
  
  .header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
    
    .header-title-section {
      display: flex;
      align-items: center;
      gap: 1.5rem;
      
      .title-icon-wrapper {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 16px;
        padding: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        
        .q-icon {
          color: white;
        }
      }
      
      .title-text {
        color: white;
        
        .page-title {
          font-size: 2rem;
          font-weight: 700;
          margin: 0 0 0.5rem 0;
          line-height: 1.2;
        }
        
        .page-subtitle {
          font-size: 1.1rem;
          margin: 0;
          opacity: 0.9;
          font-weight: 400;
        }
      }
    }
    
    .header-stats {
      display: flex;
      gap: 1rem;
      
      .stat-card {
        background: rgba(255, 255, 255, 0.15);
        border-radius: 12px;
        padding: 1rem 1.5rem;
        text-align: center;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        min-width: 80px;
        
        .stat-value {
          font-size: 1.5rem;
          font-weight: 700;
          color: white;
          line-height: 1;
          margin-bottom: 0.25rem;
        }
        
        .stat-label {
          font-size: 0.8rem;
          color: rgba(255, 255, 255, 0.8);
          text-transform: uppercase;
          letter-spacing: 0.5px;
          font-weight: 500;
        }
      }
    }
  }
}

// Filters Section
.filters-section {
  margin-bottom: 2rem;
}

// Modern Card Structure
.modern-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
  overflow: hidden;
  transition: all 0.3s ease;
  
  &:hover {
    box-shadow: 0 12px 48px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
  }
}

// Contacts Gradient
.contacts-gradient {
  background: linear-gradient(135deg, $contacts-primary 0%, $contacts-secondary 100%);
}

// Card Header
.card-header {
  padding: 1.5rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  
  .header-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    
    .header-icon {
      color: white;
      opacity: 0.9;
    }
    
    .header-text {
      color: white;
      
      .header-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0 0 0.25rem 0;
        line-height: 1.2;
      }
      
      .header-subtitle {
        font-size: 0.9rem;
        margin: 0;
        opacity: 0.8;
        line-height: 1.1;
      }
    }
  }
  
  .header-actions {
    .modern-btn,
    .close-btn {
      border-radius: 8px;
      font-weight: 500;
      text-transform: none;
      border: 1px solid rgba(255, 255, 255, 0.3);
      
      &:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.5);
      }
    }
  }
}

.card-content {
  padding: 2rem;
}

// Filters Grid
.filters-grid {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 1.5rem;
  
  @media (max-width: 1024px) {
    grid-template-columns: 1fr 1fr;
  }
  
  @media (max-width: 768px) {
    grid-template-columns: 1fr;
  }
  
  .filter-item {
    &.actions-item {
      display: flex;
      align-items: flex-end;
      
      .quick-actions {
        display: flex;
        gap: 0.75rem;
        width: 100%;
        
        .action-btn {
          flex: 1;
          text-transform: none;
          font-weight: 500;
          border-radius: 8px;
        }
      }
    }
    
    .modern-input,
    .modern-select {
      .q-field__control {
        border-radius: 12px;
        height: 56px;
      }
      
      .q-field__native {
        font-size: 1rem;
      }
      
      .q-field__label {
        font-weight: 500;
      }
      
      &.q-field--focused {
        .q-field__control {
          box-shadow: 0 0 0 2px rgba(103, 58, 183, 0.2);
        }
      }
    }
  }
}

// Detail and List Sections
.detail-section,
.contacts-list-section {
  margin-bottom: 2rem;
}

// Table Wrapper
.table-wrapper {
  .modern-table {
    :deep(.q-table__top) {
      padding: 0;
    }
    
    :deep(.q-table thead th) {
      font-weight: 600;
      font-size: 0.875rem;
      color: #333;
      background: #f8f9fa;
      border-bottom: 2px solid #e9ecef;
      padding: 1rem 0.75rem;
    }
    
    :deep(.q-table tbody td) {
      border-bottom: 1px solid #f0f0f0;
      font-size: 0.875rem;
      padding: 1rem 0.75rem;
    }
    
    :deep(.q-table tbody tr:hover) {
      background: #f8f9fa;
    }
  }
}

// Pagination Section
.pagination-section {
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e9ecef;
  display: flex;
  justify-content: center;
}

// Empty State
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  
  .empty-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 1rem 0 0.5rem 0;
    color: #666;
  }
  
  .empty-text {
    font-size: 1rem;
    color: #999;
    margin-bottom: 2rem;
    line-height: 1.6;
  }
  
  .empty-action-btn {
    background: linear-gradient(135deg, $contacts-primary 0%, $contacts-secondary 100%);
    color: white;
    font-weight: 600;
    border-radius: 12px;
    padding: 0.75rem 2rem;
    text-transform: none;
    
    &:hover {
      box-shadow: 0 8px 24px rgba(103, 58, 183, 0.3);
    }
  }
}

// Import Dialog
.import-dialog {
  .modern-card {
    min-width: 500px;
    max-width: 700px;
    margin: 2rem;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
  }
  
  .import-content {
    .import-section {
      margin-bottom: 2rem;
      
      &:last-child {
        margin-bottom: 0;
      }
      
      .section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #333;
        margin: 0 0 0.75rem 0;
      }
      
      .section-text {
        font-size: 0.95rem;
        color: #666;
        line-height: 1.5;
        margin: 0;
      }
      
      .import-file-input {
        .q-field__control {
          border-radius: 12px;
          height: 56px;
        }
      }
    }
  }
}

.dialog-actions {
  padding: 1.5rem 2rem;
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  border-top: 1px solid #e9ecef;
  background: #fafafa;
  
  .action-btn-primary {
    background: linear-gradient(135deg, $contacts-primary 0%, $contacts-secondary 100%);
    color: white;
    font-weight: 600;
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    text-transform: none;
    
    &:hover {
      box-shadow: 0 4px 12px rgba(103, 58, 183, 0.3);
    }
  }
  
  .action-btn-secondary {
    color: #666;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    text-transform: none;
    
    &:hover {
      background: #f5f5f5;
    }
  }
}

// Responsive Design
@media (max-width: 1024px) {
  .header-stats {
    flex-direction: column;
    gap: 0.75rem !important;
  }
  
  .filters-grid {
    grid-template-columns: 1fr 1fr;
    
    .actions-item {
      grid-column: 1 / -1;
      
      .quick-actions {
        justify-content: center;
        max-width: 400px;
        margin: 0 auto;
      }
    }
  }
}

@media (max-width: 768px) {
  .page-header {
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    
    .header-content {
      flex-direction: column;
      gap: 1.5rem;
      
      .header-title-section {
        width: 100%;
        
        .title-icon-wrapper {
          padding: 0.75rem;
        }
        
        .title-text {
          .page-title {
            font-size: 1.5rem;
          }
          
          .page-subtitle {
            font-size: 1rem;
          }
        }
      }
      
      .header-stats {
        width: 100%;
        flex-direction: row;
        justify-content: space-around;
        
        .stat-card {
          min-width: auto;
          flex: 1;
          padding: 0.75rem 1rem;
          
          .stat-value {
            font-size: 1.25rem;
          }
          
          .stat-label {
            font-size: 0.75rem;
          }
        }
      }
    }
  }
  
  .card-header {
    padding: 1rem 1.5rem;
    flex-direction: column;
    gap: 1rem;
    
    .header-actions {
      width: 100%;
      text-align: center;
    }
  }
  
  .card-content {
    padding: 1.5rem;
  }
  
  .filters-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
    
    .actions-item .quick-actions {
      .action-btn {
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
      }
    }
  }
  
  .import-dialog .modern-card {
    margin: 1rem;
    min-width: auto;
    max-width: none;
    width: calc(100vw - 2rem);
  }
  
  .dialog-actions {
    flex-direction: column;
    
    .action-btn-primary,
    .action-btn-secondary {
      width: 100%;
    }
  }
}

@media (max-width: 480px) {
  .page-header {
    padding: 1rem;
    border-radius: 12px;
    
    .header-title-section {
      gap: 1rem;
      
      .title-text .page-title {
        font-size: 1.25rem;
      }
    }
    
    .header-stats {
      .stat-card {
        padding: 0.5rem 0.75rem;
        
        .stat-value {
          font-size: 1rem;
        }
      }
    }
  }
  
  .modern-card {
    border-radius: 12px;
  }
  
  .card-content {
    padding: 1rem;
  }
  
  .import-dialog .modern-card {
    margin: 0.5rem;
  }
  
  .empty-state {
    padding: 2rem 1rem;
    
    .empty-action-btn {
      width: 100%;
      max-width: 250px;
    }
  }
}
</style>
