<template>
  <div class="contacts-list">
    <div class="modern-card">
      <div class="card-header contacts-gradient">
        <div class="header-content">
          <q-icon name="list" size="md" class="header-icon" />
          <div class="header-text">
            <h3 class="header-title">Liste des Contacts</h3>
            <p class="header-subtitle">{{ totalCount || 0 }} contact{{ (totalCount || 0) !== 1 ? 's' : '' }} au total</p>
          </div>
        </div>
        <div class="header-actions">
          <q-btn
            color="white"
            text-color="white"
            :icon="viewMode === 'list' ? 'view_module' : 'list'"
            :label="viewMode === 'list' ? 'Vue Grille' : 'Vue Liste'"
            outline
            size="sm"
            @click="toggleViewMode"
            class="modern-btn"
          />
        </div>
      </div>

      <div class="card-content">
        <!-- Loading State -->
        <div v-if="loading && contacts.length === 0" class="loading-state">
          <q-skeleton height="60px" class="q-mb-md" />
          <q-skeleton height="400px" />
        </div>

        <!-- Empty State -->
        <div v-else-if="!loading && contacts.length === 0" class="empty-state">
          <q-icon name="contacts" size="4rem" color="grey-5" />
          <h4 class="empty-title">Aucun contact trouvé</h4>
          <p class="empty-text">
            {{ emptyStateMessage }}
          </p>
          <q-btn
            v-if="showAddButton"
            color="primary"
            icon="add"
            label="Ajouter un contact"
            @click="$emit('add-contact')"
            class="empty-action-btn"
          />
          <q-btn
            v-else
            color="primary"
            label="Effacer les filtres"
            @click="$emit('clear-filters')"
            outline
            class="empty-action-btn"
          />
        </div>

        <!-- Contacts Table/Grid -->
        <div v-else class="contacts-content">
          <!-- Liste -->
          <div v-if="viewMode === 'list'" class="table-wrapper">
            <ContactTable
              :contacts="contacts"
              :loading="loading"
              :pagination="paginationPayload"
              @request="handlePaginationRequest"
              @edit="$emit('edit-contact', $event)"
              @delete="$emit('delete-contact', $event)"
              @send-sms="$emit('send-sms', $event)"
              @view-details="$emit('view-details', $event)"
              class="modern-table"
            />
          </div>

          <!-- Grille (Vue future - placeholder) -->
          <div v-else class="grid-wrapper">
            <div class="grid-placeholder">
              <q-icon name="view_module" size="2rem" color="grey-5" />
              <p>Vue grille en cours de développement</p>
              <q-btn
                label="Retour à la liste"
                color="primary"
                outline
                @click="setViewMode('list')"
              />
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="totalCount > 0" class="pagination-section">
            <BasePagination
              :total-items="totalCount"
              :items-per-page="pagination.rowsPerPage"
              :initial-page="pagination.page"
              @page-change="handlePageChange"
              @items-per-page-change="handleItemsPerPageChange"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import ContactTable from '../../../components/contacts/ContactTable.vue';
import BasePagination from '../../../components/BasePagination.vue';
import type { ContactsListProps, Contact, ViewMode } from '../types/contacts.types';

// Props
const props = withDefaults(defineProps<ContactsListProps>(), {
  loading: false,
  viewMode: 'list'
});

// Events
const emit = defineEmits<{
  'add-contact': [];
  'edit-contact': [contact: Contact];
  'delete-contact': [contact: Contact];
  'send-sms': [contact: Contact];
  'view-details': [contact: Contact];
  'clear-filters': [];
  'pagination-request': [payload: { page: number; rowsPerPage: number; sortBy: string; descending: boolean }];
  'page-change': [page: number];
  'items-per-page-change': [itemsPerPage: number];
  'view-mode-change': [mode: ViewMode];
}>();

// Computed properties
const paginationPayload = computed(() => ({
  ...props.pagination,
  rowsNumber: props.totalCount
}));

const emptyStateMessage = computed(() => {
  // Si nous avons des filtres actifs (détectable par la différence entre contacts et totalCount)
  if (props.totalCount === 0 && props.contacts.length === 0) {
    return 'Aucun résultat pour ces critères de recherche';
  }
  return 'Commencez par ajouter votre premier contact';
});

const showAddButton = computed(() => {
  // Montrer le bouton d'ajout seulement s'il n'y a vraiment aucun contact
  return props.totalCount === 0 && props.contacts.length === 0;
});

// Methods
function handlePaginationRequest(payload: { page: number; rowsPerPage: number; sortBy: string; descending: boolean }): void {
  emit('pagination-request', payload);
}

function handlePageChange(page: number): void {
  emit('page-change', page);
}

function handleItemsPerPageChange(itemsPerPage: number): void {
  emit('items-per-page-change', itemsPerPage);
}

function toggleViewMode(): void {
  const newMode: ViewMode = props.viewMode === 'list' ? 'grid' : 'list';
  setViewMode(newMode);
}

function setViewMode(mode: ViewMode): void {
  emit('view-mode-change', mode);
}
</script>

<style lang="scss" scoped>
// Contacts Color Palette
$contacts-primary: #673ab7;
$contacts-secondary: #9c27b0;

.contacts-list {
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
    .modern-btn {
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

// Loading State
.loading-state {
  padding: 2rem 0;
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

// Grid Wrapper (Placeholder)
.grid-wrapper {
  .grid-placeholder {
    text-align: center;
    padding: 4rem 2rem;
    color: #666;
    
    p {
      margin: 1rem 0 2rem 0;
      font-size: 1.1rem;
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

// Responsive Design
@media (max-width: 768px) {
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
  
  .empty-state {
    padding: 2rem 1rem;
    
    .empty-action-btn {
      width: 100%;
      max-width: 250px;
    }
  }
}

@media (max-width: 480px) {
  .modern-card {
    border-radius: 12px;
  }
  
  .card-content {
    padding: 1rem;
  }
}
</style>