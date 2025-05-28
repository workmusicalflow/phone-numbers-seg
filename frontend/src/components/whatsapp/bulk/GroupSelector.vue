<template>
  <div class="group-selector">
    <div class="selector-header">
      <q-icon name="group" class="selector-icon" />
      <span class="selector-label">Sélection par groupes de contacts</span>
      <q-btn
        flat
        dense
        icon="refresh"
        @click="loadGroups"
        :loading="loading"
        class="refresh-btn"
      />
    </div>
    
    <!-- Barre de recherche -->
    <div class="search-section">
      <q-input
        v-model="searchQuery"
        label="Rechercher un groupe"
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
    </div>
    
    <!-- Liste des groupes -->
    <div v-if="!loading && filteredGroups.length > 0" class="groups-list">
      <div class="list-header">
        <span class="list-title">Groupes disponibles ({{ filteredGroups.length }})</span>
        <div class="selection-actions">
          <q-btn
            flat
            dense
            icon="select_all"
            label="Tout sélectionner"
            @click="selectAllGroups"
            v-if="!allGroupsSelected"
            class="select-btn"
          />
          <q-btn
            flat
            dense
            icon="deselect"
            label="Tout désélectionner"
            @click="deselectAllGroups"
            v-else
            class="select-btn"
          />
        </div>
      </div>
      
      <div class="groups-container">
        <q-virtual-scroll
          :items="filteredGroups"
          separator
          v-slot="{ item, index }"
          style="max-height: 300px;"
        >
          <q-item 
            :key="item.id"
            clickable
            @click="toggleGroupSelection(item.id)"
            class="group-item"
            :class="{ 'selected': localSelectedGroups.includes(item.id) }"
          >
            <q-item-section avatar>
              <q-checkbox
                :model-value="localSelectedGroups.includes(item.id)"
                @update:model-value="toggleGroupSelection(item.id)"
                color="primary"
              />
            </q-item-section>
            
            <q-item-section>
              <q-item-label class="group-name">{{ item.name }}</q-item-label>
              <q-item-label caption class="group-info">
                <q-icon name="people" size="12px" class="q-mr-xs" />
                {{ item.contactCount || 0 }} contact{{ (item.contactCount || 0) > 1 ? 's' : '' }}
                <span v-if="item.description" class="group-description">
                  • {{ item.description }}
                </span>
              </q-item-label>
            </q-item-section>
            
            <q-item-section side>
              <div class="group-actions">
                <q-btn
                  flat
                  dense
                  round
                  icon="visibility"
                  size="sm"
                  @click.stop="previewGroupContacts(item)"
                  class="preview-btn"
                />
                <q-chip
                  :color="item.contactCount > 0 ? 'primary' : 'grey'"
                  text-color="white"
                  size="sm"
                  :label="item.contactCount || 0"
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
      <p class="loading-message">Chargement des groupes...</p>
    </div>
    
    <!-- État vide -->
    <div v-if="!loading && groups.length === 0" class="empty-state">
      <q-icon name="group_off" size="48px" class="empty-icon" />
      <p class="empty-message">Aucun groupe de contacts trouvé</p>
      <p class="empty-hint">Créez des groupes dans la section "Groupes de contacts"</p>
    </div>
    
    <!-- Résumé de la sélection -->
    <div v-if="localSelectedGroups.length > 0" class="selection-summary">
      <div class="summary-header">
        <q-icon name="fact_check" class="summary-icon" />
        <span class="summary-title">Groupes sélectionnés</span>
      </div>
      
      <div class="summary-content">
        <div class="summary-stats">
          <div class="stat-item">
            <q-icon name="group" class="stat-icon" />
            <span class="stat-label">Groupes:</span>
            <span class="stat-value">{{ localSelectedGroups.length }}</span>
          </div>
          <div class="stat-item">
            <q-icon name="phone" class="stat-icon" />
            <span class="stat-label">Contacts estimés:</span>
            <span class="stat-value">{{ estimatedContactCount }}</span>
          </div>
        </div>
        
        <div class="selected-groups-list">
          <q-chip
            v-for="groupId in localSelectedGroups"
            :key="groupId"
            color="primary"
            text-color="white"
            :label="getGroupName(groupId)"
            removable
            @remove="removeGroupSelection(groupId)"
            class="selected-group-chip"
          />
        </div>
        
        <div class="load-contacts-section">
          <q-btn
            color="primary"
            icon="download"
            label="Charger les contacts"
            @click="loadSelectedGroupsContacts"
            :loading="loadingContacts"
            :disable="localSelectedGroups.length === 0"
            class="load-btn"
          />
          <div class="load-help">
            <q-icon name="info" size="14px" class="q-mr-xs" />
            <span class="help-text">
              Les contacts seront ajoutés à la liste des destinataires
            </span>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Dialog de prévisualisation -->
    <q-dialog v-model="showPreviewDialog" class="preview-dialog">
      <q-card style="width: 500px; max-width: 90vw;">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Contacts du groupe "{{ previewGroup?.name }}"</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>
        
        <q-card-section>
          <div v-if="previewContacts.length > 0" class="preview-contacts">
            <q-list separator>
              <q-item
                v-for="contact in previewContacts.slice(0, 10)"
                :key="contact.phoneNumber"
              >
                <q-item-section>
                  <q-item-label>{{ contact.name || 'Sans nom' }}</q-item-label>
                  <q-item-label caption>{{ contact.phoneNumber }}</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
            
            <div v-if="previewContacts.length > 10" class="more-contacts">
              <q-chip 
                color="grey-4" 
                :label="`+${previewContacts.length - 10} autres contacts`"
              />
            </div>
          </div>
          <div v-else class="no-contacts">
            <p>Ce groupe ne contient aucun contact.</p>
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useContactGroupStore } from '../../../stores/contactGroupStore'

interface ContactGroup {
  id: number
  name: string
  description?: string
  contactCount?: number
}

interface Contact {
  phoneNumber: string
  name?: string
}

interface Props {
  selectedGroups?: number[]
}

interface Emits {
  (e: 'update:selectedGroups', value: number[]): void
  (e: 'recipients-loaded', value: string[]): void
}

const props = withDefaults(defineProps<Props>(), {
  selectedGroups: () => []
})

const emit = defineEmits<Emits>()
const $q = useQuasar()
const contactGroupStore = useContactGroupStore()

const loading = ref(false)
const loadingContacts = ref(false)
const searchQuery = ref('')
const groups = ref<ContactGroup[]>([])
const localSelectedGroups = ref([...props.selectedGroups])
const showPreviewDialog = ref(false)
const previewGroup = ref<ContactGroup | null>(null)
const previewContacts = ref<Contact[]>([])

// Computed properties
const filteredGroups = computed(() => {
  if (!searchQuery.value) return groups.value
  
  const query = searchQuery.value.toLowerCase()
  return groups.value.filter(group => 
    group.name.toLowerCase().includes(query) ||
    (group.description && group.description.toLowerCase().includes(query))
  )
})

const allGroupsSelected = computed(() => {
  return filteredGroups.value.length > 0 && 
         filteredGroups.value.every(group => localSelectedGroups.value.includes(group.id))
})

const estimatedContactCount = computed(() => {
  return localSelectedGroups.value.reduce((total, groupId) => {
    const group = groups.value.find(g => g.id === groupId)
    return total + (group?.contactCount || 0)
  }, 0)
})

// Watchers
watch(localSelectedGroups, (newSelection) => {
  emit('update:selectedGroups', [...newSelection])
}, { deep: true })

watch(() => props.selectedGroups, (newSelection) => {
  localSelectedGroups.value = [...newSelection]
}, { deep: true })

// Méthodes
async function loadGroups() {
  loading.value = true
  try {
    await contactGroupStore.loadContactGroups()
    groups.value = contactGroupStore.contactGroups.map(group => ({
      id: group.id,
      name: group.name,
      description: group.description,
      contactCount: group.contactCount || 0
    }))
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erreur lors du chargement des groupes',
      position: 'top'
    })
  } finally {
    loading.value = false
  }
}

function toggleGroupSelection(groupId: number) {
  const index = localSelectedGroups.value.indexOf(groupId)
  if (index > -1) {
    localSelectedGroups.value.splice(index, 1)
  } else {
    localSelectedGroups.value.push(groupId)
  }
}

function selectAllGroups() {
  localSelectedGroups.value = filteredGroups.value.map(group => group.id)
}

function deselectAllGroups() {
  localSelectedGroups.value = []
}

function removeGroupSelection(groupId: number) {
  const index = localSelectedGroups.value.indexOf(groupId)
  if (index > -1) {
    localSelectedGroups.value.splice(index, 1)
  }
}

function getGroupName(groupId: number): string {
  const group = groups.value.find(g => g.id === groupId)
  return group?.name || `Groupe ${groupId}`
}

async function previewGroupContacts(group: ContactGroup) {
  previewGroup.value = group
  previewContacts.value = []
  showPreviewDialog.value = true
  
  try {
    // Charger les contacts du groupe
    const contacts = await contactGroupStore.getGroupContacts(group.id)
    previewContacts.value = contacts.map(contact => ({
      phoneNumber: contact.phoneNumber,
      name: contact.name
    }))
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erreur lors du chargement des contacts du groupe',
      position: 'top'
    })
  }
}

async function loadSelectedGroupsContacts() {
  if (localSelectedGroups.value.length === 0) return
  
  loadingContacts.value = true
  try {
    const allContacts: string[] = []
    
    for (const groupId of localSelectedGroups.value) {
      const contacts = await contactGroupStore.getGroupContacts(groupId)
      const phoneNumbers = contacts
        .map(contact => contact.phoneNumber)
        .filter(phone => phone && phone.startsWith('+'))
      
      allContacts.push(...phoneNumbers)
    }
    
    // Supprimer les doublons
    const uniqueContacts = [...new Set(allContacts)]
    
    emit('recipients-loaded', uniqueContacts)
    
    $q.notify({
      type: 'positive',
      message: `${uniqueContacts.length} contact(s) chargé(s) depuis les groupes sélectionnés`,
      position: 'top'
    })
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erreur lors du chargement des contacts',
      position: 'top'
    })
  } finally {
    loadingContacts.value = false
  }
}

// Lifecycle
onMounted(() => {
  loadGroups()
})
</script>

<style lang="scss" scoped>
.group-selector {
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
  
  .search-section {
    margin-bottom: 16px;
    
    .search-input {
      max-width: 300px;
    }
  }
  
  .groups-list {
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
    
    .groups-container {
      border: 1px solid var(--q-grey-4);
      border-top: none;
      border-radius: 0 0 8px 8px;
      
      .group-item {
        transition: all 0.2s ease;
        
        &.selected {
          background: rgba(var(--q-primary-rgb), 0.1);
        }
        
        &:hover {
          background: rgba(var(--q-grey-rgb), 0.05);
        }
        
        .group-name {
          font-weight: 500;
          color: var(--q-dark);
        }
        
        .group-info {
          display: flex;
          align-items: center;
          
          .group-description {
            margin-left: 8px;
            color: var(--q-grey-6);
          }
        }
        
        .group-actions {
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
      
      .selected-groups-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 16px;
        
        .selected-group-chip {
          margin: 0;
        }
      }
      
      .load-contacts-section {
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
    .preview-contacts {
      .more-contacts {
        text-align: center;
        margin-top: 16px;
      }
    }
    
    .no-contacts {
      text-align: center;
      color: var(--q-grey-6);
    }
  }
}

// Responsive design
@media (max-width: 768px) {
  .group-selector {
    .groups-list .list-header {
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