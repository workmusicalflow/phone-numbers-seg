<template>
  <div class="segment-selector">
    <div class="selector-header">
      <q-icon name="category" class="selector-icon" />
      <span class="selector-label">Sélection par segments</span>
      <q-btn
        flat
        dense
        icon="refresh"
        @click="loadSegments"
        :loading="loading"
        class="refresh-btn"
      />
    </div>
    
    <!-- Barre de recherche et filtres -->
    <div class="filter-section">
      <q-input
        v-model="searchQuery"
        label="Rechercher un segment"
        outlined
        dense
        clearable
        debounce="300"
        class="search-input"
      >
        <template v-slot:prepend>
          <q-icon name="search" />
        </template>
      </q-input>
      
      <q-select
        v-model="filterType"
        :options="segmentTypeOptions"
        label="Type de segment"
        outlined
        dense
        emit-value
        map-options
        clearable
        class="type-filter"
      />
    </div>
    
    <!-- Liste des segments -->
    <div v-if="!loading && filteredSegments.length > 0" class="segments-list">
      <div class="list-header">
        <span class="list-title">Segments disponibles ({{ filteredSegments.length }})</span>
        <div class="selection-actions">
          <q-btn
            flat
            dense
            icon="select_all"
            label="Tout sélectionner"
            @click="selectAllSegments"
            v-if="!allSegmentsSelected"
            class="select-btn"
          />
          <q-btn
            flat
            dense
            icon="deselect"
            label="Tout désélectionner"
            @click="deselectAllSegments"
            v-else
            class="select-btn"
          />
        </div>
      </div>
      
      <div class="segments-container">
        <q-virtual-scroll
          :items="filteredSegments"
          separator
          v-slot="{ item, index }"
          style="max-height: 300px;"
        >
          <q-item 
            :key="item.id"
            clickable
            @click="toggleSegmentSelection(item.id)"
            class="segment-item"
            :class="{ 'selected': localSelectedSegments.includes(item.id) }"
          >
            <q-item-section avatar>
              <q-checkbox
                :model-value="localSelectedSegments.includes(item.id)"
                @update:model-value="toggleSegmentSelection(item.id)"
                color="primary"
              />
            </q-item-section>
            
            <q-item-section>
              <q-item-label class="segment-name">{{ item.name }}</q-item-label>
              <q-item-label caption class="segment-info">
                <q-chip
                  :color="getSegmentTypeColor(item.type)"
                  text-color="white"
                  size="sm"
                  :label="getSegmentTypeLabel(item.type)"
                  class="segment-type-chip"
                />
                <span class="segment-description">
                  {{ item.description || 'Aucune description' }}
                </span>
              </q-item-label>
            </q-item-section>
            
            <q-item-section side>
              <div class="segment-actions">
                <q-btn
                  flat
                  dense
                  round
                  icon="visibility"
                  size="sm"
                  @click.stop="previewSegmentNumbers(item)"
                  class="preview-btn"
                />
                <q-chip
                  :color="item.phoneCount > 0 ? 'primary' : 'grey'"
                  text-color="white"
                  size="sm"
                  :label="item.phoneCount || 0"
                />
              </div>
            </q-item-section>
          </q-item>
        </q-virtual-scroll>
      </div>
    </div>
    
    <!-- État de chargement -->
    <div v-if="loading" class="loading-state">
      <q-spinner-dots size="40px" color="primary" />
      <p class="loading-message">Chargement des segments...</p>
    </div>
    
    <!-- État vide -->
    <div v-if="!loading && segments.length === 0" class="empty-state">
      <q-icon name="category_off" size="48px" class="empty-icon" />
      <p class="empty-message">Aucun segment trouvé</p>
      <p class="empty-hint">Les segments sont créés automatiquement lors de l'import de numéros</p>
    </div>
    
    <!-- Résumé de la sélection -->
    <div v-if="localSelectedSegments.length > 0" class="selection-summary">
      <div class="summary-header">
        <q-icon name="fact_check" class="summary-icon" />
        <span class="summary-title">Segments sélectionnés</span>
      </div>
      
      <div class="summary-content">
        <div class="summary-stats">
          <div class="stat-item">
            <q-icon name="category" class="stat-icon" />
            <span class="stat-label">Segments:</span>
            <span class="stat-value">{{ localSelectedSegments.length }}</span>
          </div>
          <div class="stat-item">
            <q-icon name="phone" class="stat-icon" />
            <span class="stat-label">Numéros estimés:</span>
            <span class="stat-value">{{ estimatedPhoneCount }}</span>
          </div>
        </div>
        
        <div class="selected-segments-list">
          <q-chip
            v-for="segmentId in localSelectedSegments"
            :key="segmentId"
            :color="getSelectedSegmentColor(segmentId)"
            text-color="white"
            :label="getSegmentName(segmentId)"
            removable
            @remove="removeSegmentSelection(segmentId)"
            class="selected-segment-chip"
          />
        </div>
        
        <div class="load-numbers-section">
          <q-btn
            color="primary"
            icon="download"
            label="Charger les numéros"
            @click="loadSelectedSegmentsNumbers"
            :loading="loadingNumbers"
            :disable="localSelectedSegments.length === 0"
            class="load-btn"
          />
          <div class="load-help">
            <q-icon name="info" size="14px" class="q-mr-xs" />
            <span class="help-text">
              Les numéros seront ajoutés à la liste des destinataires
            </span>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Dialog de prévisualisation -->
    <q-dialog v-model="showPreviewDialog" class="preview-dialog">
      <q-card style="width: 500px; max-width: 90vw;">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Numéros du segment "{{ previewSegment?.name }}"</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>
        
        <q-card-section>
          <div v-if="previewNumbers.length > 0" class="preview-numbers">
            <q-list separator>
              <q-item
                v-for="phoneNumber in previewNumbers.slice(0, 20)"
                :key="phoneNumber"
              >
                <q-item-section>
                  <q-item-label class="phone-number">{{ phoneNumber }}</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
            
            <div v-if="previewNumbers.length > 20" class="more-numbers">
              <q-chip 
                color="grey-4" 
                :label="`+${previewNumbers.length - 20} autres numéros`"
              />
            </div>
          </div>
          <div v-else class="no-numbers">
            <p>Ce segment ne contient aucun numéro.</p>
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useSegmentStore } from '../../../stores/segmentStore'

interface Segment {
  id: number
  name: string
  description?: string
  type: 'country' | 'operator' | 'custom' | 'technical'
  phoneCount?: number
}

interface Props {
  selectedSegments?: number[]
}

interface Emits {
  (e: 'update:selectedSegments', value: number[]): void
  (e: 'recipients-loaded', value: string[]): void
}

const props = withDefaults(defineProps<Props>(), {
  selectedSegments: () => []
})

const emit = defineEmits<Emits>()
const $q = useQuasar()
const segmentStore = useSegmentStore()

const loading = ref(false)
const loadingNumbers = ref(false)
const searchQuery = ref('')
const filterType = ref<string | null>(null)
const segments = ref<Segment[]>([])
const localSelectedSegments = ref([...props.selectedSegments])
const showPreviewDialog = ref(false)
const previewSegment = ref<Segment | null>(null)
const previewNumbers = ref<string[]>([])

const segmentTypeOptions = [
  { label: 'Pays', value: 'country' },
  { label: 'Opérateur', value: 'operator' },
  { label: 'Personnalisé', value: 'custom' },
  { label: 'Technique', value: 'technical' }
]

// Computed properties
const filteredSegments = computed(() => {
  let filtered = segments.value
  
  // Filtrer par recherche
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(segment => 
      segment.name.toLowerCase().includes(query) ||
      (segment.description && segment.description.toLowerCase().includes(query))
    )
  }
  
  // Filtrer par type
  if (filterType.value) {
    filtered = filtered.filter(segment => segment.type === filterType.value)
  }
  
  return filtered
})

const allSegmentsSelected = computed(() => {
  return filteredSegments.value.length > 0 && 
         filteredSegments.value.every(segment => localSelectedSegments.value.includes(segment.id))
})

const estimatedPhoneCount = computed(() => {
  return localSelectedSegments.value.reduce((total, segmentId) => {
    const segment = segments.value.find(s => s.id === segmentId)
    return total + (segment?.phoneCount || 0)
  }, 0)
})

// Watchers
watch(localSelectedSegments, (newSelection) => {
  emit('update:selectedSegments', [...newSelection])
}, { deep: true })

watch(() => props.selectedSegments, (newSelection) => {
  localSelectedSegments.value = [...newSelection]
}, { deep: true })

// Méthodes
async function loadSegments() {
  loading.value = true
  try {
    await segmentStore.loadSegments()
    segments.value = segmentStore.segments.map(segment => ({
      id: segment.id,
      name: segment.name,
      description: segment.description,
      type: segment.type as 'country' | 'operator' | 'custom' | 'technical',
      phoneCount: segment.phoneCount || 0
    }))
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erreur lors du chargement des segments',
      position: 'top'
    })
  } finally {
    loading.value = false
  }
}

function toggleSegmentSelection(segmentId: number) {
  const index = localSelectedSegments.value.indexOf(segmentId)
  if (index > -1) {
    localSelectedSegments.value.splice(index, 1)
  } else {
    localSelectedSegments.value.push(segmentId)
  }
}

function selectAllSegments() {
  localSelectedSegments.value = filteredSegments.value.map(segment => segment.id)
}

function deselectAllSegments() {
  localSelectedSegments.value = []
}

function removeSegmentSelection(segmentId: number) {
  const index = localSelectedSegments.value.indexOf(segmentId)
  if (index > -1) {
    localSelectedSegments.value.splice(index, 1)
  }
}

function getSegmentName(segmentId: number): string {
  const segment = segments.value.find(s => s.id === segmentId)
  return segment?.name || `Segment ${segmentId}`
}

function getSegmentTypeLabel(type: string): string {
  const option = segmentTypeOptions.find(opt => opt.value === type)
  return option?.label || type
}

function getSegmentTypeColor(type: string): string {
  switch (type) {
    case 'country': return 'blue'
    case 'operator': return 'green'
    case 'custom': return 'purple'
    case 'technical': return 'orange'
    default: return 'grey'
  }
}

function getSelectedSegmentColor(segmentId: number): string {
  const segment = segments.value.find(s => s.id === segmentId)
  return segment ? getSegmentTypeColor(segment.type) : 'grey'
}

async function previewSegmentNumbers(segment: Segment) {
  previewSegment.value = segment
  previewNumbers.value = []
  showPreviewDialog.value = true
  
  try {
    // Charger les numéros du segment
    const numbers = await segmentStore.getSegmentPhoneNumbers(segment.id)
    previewNumbers.value = numbers
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erreur lors du chargement des numéros du segment',
      position: 'top'
    })
  }
}

async function loadSelectedSegmentsNumbers() {
  if (localSelectedSegments.value.length === 0) return
  
  loadingNumbers.value = true
  try {
    const allNumbers: string[] = []
    
    for (const segmentId of localSelectedSegments.value) {
      const numbers = await segmentStore.getSegmentPhoneNumbers(segmentId)
      allNumbers.push(...numbers)
    }
    
    // Supprimer les doublons
    const uniqueNumbers = [...new Set(allNumbers)]
    
    emit('recipients-loaded', uniqueNumbers)
    
    $q.notify({
      type: 'positive',
      message: `${uniqueNumbers.length} numéro(s) chargé(s) depuis les segments sélectionnés`,
      position: 'top'
    })
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erreur lors du chargement des numéros',
      position: 'top'
    })
  } finally {
    loadingNumbers.value = false
  }
}

// Lifecycle
onMounted(() => {
  loadSegments()
})
</script>

<style lang="scss" scoped>
.segment-selector {
  .selector-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
    
    .selector-icon {
      color: var(--q-primary);
      font-size: 18px;
    }
    
    .selector-label {
      font-weight: 600;
      color: var(--q-dark);
      flex: 1;
    }
    
    .refresh-btn {
      color: var(--q-grey-6);
    }
  }
  
  .filter-section {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 12px;
    margin-bottom: 16px;
    
    .search-input {
      min-width: 0;
    }
    
    .type-filter {
      min-width: 150px;
    }
  }
  
  .segments-list {
    .list-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 12px 16px;
      background: rgba(var(--q-primary-rgb), 0.1);
      border-radius: 8px 8px 0 0;
      border-bottom: 1px solid var(--q-grey-4);
      
      .list-title {
        font-weight: 600;
        color: var(--q-dark);
      }
      
      .selection-actions {
        .select-btn {
          font-size: 12px;
        }
      }
    }
    
    .segments-container {
      border: 1px solid var(--q-grey-4);
      border-top: none;
      border-radius: 0 0 8px 8px;
      
      .segment-item {
        transition: all 0.2s ease;
        
        &.selected {
          background: rgba(var(--q-primary-rgb), 0.1);
        }
        
        &:hover {
          background: rgba(var(--q-grey-rgb), 0.05);
        }
        
        .segment-name {
          font-weight: 500;
          color: var(--q-dark);
        }
        
        .segment-info {
          display: flex;
          align-items: center;
          gap: 8px;
          
          .segment-type-chip {
            margin: 0;
          }
          
          .segment-description {
            color: var(--q-grey-6);
            font-size: 12px;
          }
        }
        
        .segment-actions {
          display: flex;
          align-items: center;
          gap: 8px;
          
          .preview-btn {
            color: var(--q-grey-6);
          }
        }
      }
    }
  }
  
  .loading-state {
    text-align: center;
    padding: 48px 16px;
    color: var(--q-grey-6);
    
    .loading-message {
      margin-top: 16px;
      font-size: 14px;
    }
  }
  
  .empty-state {
    text-align: center;
    padding: 48px 16px;
    color: var(--q-grey-6);
    
    .empty-icon {
      margin-bottom: 16px;
      opacity: 0.5;
    }
    
    .empty-message {
      font-size: 16px;
      font-weight: 500;
      margin-bottom: 8px;
    }
    
    .empty-hint {
      font-size: 14px;
      margin: 0;
    }
  }
  
  .selection-summary {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-top: 20px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    border: 1px solid var(--q-grey-4);
    
    .summary-header {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 16px;
      
      .summary-icon {
        color: var(--q-primary);
        font-size: 20px;
      }
      
      .summary-title {
        font-weight: 600;
        color: var(--q-dark);
      }
    }
    
    .summary-content {
      .summary-stats {
        display: flex;
        gap: 24px;
        margin-bottom: 16px;
        flex-wrap: wrap;
        
        .stat-item {
          display: flex;
          align-items: center;
          gap: 6px;
          
          .stat-icon {
            color: var(--q-primary);
            font-size: 16px;
          }
          
          .stat-label {
            color: var(--q-grey-6);
            font-size: 14px;
          }
          
          .stat-value {
            font-weight: 600;
            color: var(--q-dark);
          }
        }
      }
      
      .selected-segments-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 16px;
        
        .selected-segment-chip {
          margin: 0;
        }
      }
      
      .load-numbers-section {
        .load-btn {
          margin-bottom: 8px;
        }
        
        .load-help {
          display: flex;
          align-items: center;
          color: var(--q-grey-6);
          font-size: 12px;
        }
      }
    }
  }
  
  .preview-dialog {
    .preview-numbers {
      .phone-number {
        font-family: 'Courier New', monospace;
        font-weight: 500;
      }
      
      .more-numbers {
        text-align: center;
        margin-top: 16px;
      }
    }
    
    .no-numbers {
      text-align: center;
      color: var(--q-grey-6);
    }
  }
}

// Responsive design
@media (max-width: 768px) {
  .segment-selector {
    .filter-section {
      grid-template-columns: 1fr;
      
      .type-filter {
        min-width: 0;
      }
    }
    
    .segments-list .list-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 8px;
    }
    
    .selection-summary .summary-content .summary-stats {
      flex-direction: column;
      gap: 12px;
    }
  }
}
</style>