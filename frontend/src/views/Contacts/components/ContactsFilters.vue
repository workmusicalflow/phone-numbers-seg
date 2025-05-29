<template>
  <div class="contacts-filters">
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
            @click="$emit('new-contact')"
            class="modern-btn"
          />
        </div>
      </div>

      <div class="card-content">
        <div class="filters-grid">
          <!-- Barre de recherche -->
          <div class="filter-item">
            <q-input
              :model-value="filters.searchTerm"
              @update:model-value="handleSearchUpdate"
              label="Rechercher nom ou numéro..."
              outlined
              clearable
              debounce="300"
              class="modern-input"
              :loading="loading"
            >
              <template v-slot:prepend>
                <q-icon name="search" />
              </template>
            </q-input>
          </div>

          <!-- Sélecteur de groupe -->
          <div class="filter-item">
            <q-select
              :model-value="filters.groupId"
              @update:model-value="handleGroupUpdate"
              :options="groupOptions"
              label="Filtrer par groupe"
              outlined
              clearable
              emit-value
              map-options
              :loading="loading"
              class="modern-select"
            >
              <template v-slot:prepend>
                <q-icon name="group" />
              </template>
            </q-select>
          </div>

          <!-- Actions rapides -->
          <div class="filter-item actions-item">
            <div class="quick-actions">
              <q-btn
                color="primary"
                icon="refresh"
                label="Actualiser"
                @click="$emit('refresh')"
                :loading="loading"
                class="action-btn"
              />
              <q-btn
                color="secondary"
                icon="import_export"
                label="Importer"
                @click="$emit('import')"
                class="action-btn"
              />
            </div>
          </div>
        </div>

        <!-- Résumé des filtres actifs -->
        <div v-if="hasActiveFilters" class="active-filters">
          <div class="filters-summary">
            <q-icon name="filter_alt" size="sm" />
            <span class="summary-text">Filtres actifs:</span>
            <div class="filter-chips">
              <q-chip
                v-for="summary in activeFiltersSummary"
                :key="summary"
                removable
                @remove="clearSpecificFilter(summary)"
                color="primary"
                text-color="white"
                size="sm"
              >
                {{ summary }}
              </q-chip>
            </div>
            <q-btn
              flat
              dense
              icon="clear"
              size="sm"
              color="grey-7"
              @click="$emit('clear-filters')"
              class="clear-all-btn"
            >
              <q-tooltip>Effacer tous les filtres</q-tooltip>
            </q-btn>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { ContactsFiltersProps } from '../types/contacts.types';

// Props
const props = withDefaults(defineProps<ContactsFiltersProps>(), {
  loading: false
});

// Events
const emit = defineEmits<{
  'update-search': [searchTerm: string];
  'update-group': [groupId: string | null];
  'new-contact': [];
  'refresh': [];
  'import': [];
  'clear-filters': [];
}>();

// Computed
const hasActiveFilters = computed(() => {
  return !!(props.filters.searchTerm || props.filters.groupId);
});

const activeFiltersSummary = computed(() => {
  const summary: string[] = [];
  
  if (props.filters.searchTerm) {
    summary.push(`Recherche: "${props.filters.searchTerm}"`);
  }
  
  if (props.filters.groupId) {
    const group = props.groupOptions.find(g => g.value === props.filters.groupId);
    if (group && group.label !== 'Tous les groupes') {
      summary.push(`Groupe: ${group.label}`);
    }
  }
  
  return summary;
});

// Methods
function handleSearchUpdate(value: string | number | null): void {
  emit('update-search', String(value || ''));
}

function handleGroupUpdate(value: string | null): void {
  emit('update-group', value);
}

function clearSpecificFilter(summary: string): void {
  if (summary.startsWith('Recherche:')) {
    emit('update-search', '');
  } else if (summary.startsWith('Groupe:')) {
    emit('update-group', null);
  }
}
</script>

<style lang="scss" scoped>
// Contacts Color Palette
$contacts-primary: #673ab7;
$contacts-secondary: #9c27b0;

.contacts-filters {
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
      :deep(.q-field__control) {
        border-radius: 12px;
        height: 56px;
      }
      
      :deep(.q-field__native) {
        font-size: 1rem;
      }
      
      :deep(.q-field__label) {
        font-weight: 500;
      }
      
      &.q-field--focused {
        :deep(.q-field__control) {
          box-shadow: 0 0 0 2px rgba(103, 58, 183, 0.2);
        }
      }
    }
  }
}

// Active Filters
.active-filters {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e9ecef;
  
  .filters-summary {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
    
    .summary-text {
      font-weight: 500;
      color: #666;
      font-size: 0.9rem;
    }
    
    .filter-chips {
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;
    }
    
    .clear-all-btn {
      margin-left: auto;
    }
  }
}

// Responsive Design
@media (max-width: 1024px) {
  .filters-grid {
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
    gap: 1rem;
    
    .actions-item .quick-actions {
      .action-btn {
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
      }
    }
  }
  
  .active-filters {
    .filters-summary {
      flex-direction: column;
      align-items: flex-start;
      gap: 0.75rem;
    }
  }
}
</style>