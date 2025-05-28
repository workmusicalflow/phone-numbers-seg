<template>
  <div class="recipient-manager">
    <div class="section-header">
      <q-icon name="contacts" class="section-icon" />
      <h6 class="section-title">Gestion des destinataires</h6>
      <div class="recipient-count">
        <q-chip 
          :color="recipients.length > 0 ? 'primary' : 'grey'" 
          text-color="white" 
          :icon="recipients.length > 0 ? 'check_circle' : 'info'"
        >
          {{ recipients.length }} destinataire{{ recipients.length > 1 ? 's' : '' }}
        </q-chip>
      </div>
    </div>
    
    <q-tabs
      v-model="recipientTab"
      dense
      class="recipient-tabs"
      active-color="primary"
      indicator-color="primary"
      align="justify"
    >
      <q-tab name="manual" icon="edit" label="Saisie manuelle" />
      <q-tab name="csv" icon="upload_file" label="Import CSV" />
      <q-tab name="groups" icon="group" label="Groupes" />
      <q-tab name="segments" icon="category" label="Segments" />
    </q-tabs>
    
    <q-tab-panels v-model="recipientTab" animated class="tab-panels">
      <q-tab-panel name="manual" class="manual-panel">
        <ManualInput
          :recipients="recipients"
          @update:recipients="updateRecipients"
        />
      </q-tab-panel>
      
      <q-tab-panel name="csv" class="csv-panel">
        <CsvImport
          @recipients-imported="handleCsvImport"
        />
      </q-tab-panel>
      
      <q-tab-panel name="groups" class="groups-panel">
        <GroupSelector
          :selected-groups="selectedGroups"
          @update:selected-groups="updateSelectedGroups"
          @recipients-loaded="handleGroupRecipients"
        />
      </q-tab-panel>
      
      <q-tab-panel name="segments" class="segments-panel">
        <SegmentSelector
          :selected-segments="selectedSegments"
          @update:selected-segments="updateSelectedSegments"
          @recipients-loaded="handleSegmentRecipients"
        />
      </q-tab-panel>
    </q-tab-panels>
    
    <!-- Validation et résumé -->
    <div v-if="recipients.length > 0" class="recipient-summary">
      <div class="summary-header">
        <q-icon name="fact_check" class="summary-icon" />
        <span class="summary-title">Résumé des destinataires</span>
      </div>
      
      <div class="summary-content">
        <div class="summary-stats">
          <div class="stat-item">
            <q-icon name="phone" class="stat-icon" />
            <span class="stat-label">Total:</span>
            <span class="stat-value">{{ recipients.length }}</span>
          </div>
          <div class="stat-item">
            <q-icon name="check_circle" class="stat-icon valid" />
            <span class="stat-label">Valides:</span>
            <span class="stat-value">{{ validRecipients.length }}</span>
          </div>
          <div class="stat-item" v-if="invalidRecipients.length > 0">
            <q-icon name="error" class="stat-icon invalid" />
            <span class="stat-label">Invalides:</span>
            <span class="stat-value">{{ invalidRecipients.length }}</span>
          </div>
        </div>
        
        <!-- Afficher les numéros invalides -->
        <div v-if="invalidRecipients.length > 0" class="invalid-numbers">
          <q-expansion-item
            icon="warning"
            label="Numéros invalides"
            :caption="`${invalidRecipients.length} numéro(s) à corriger`"
            header-class="text-negative"
          >
            <div class="invalid-list">
              <q-chip
                v-for="(number, index) in invalidRecipients"
                :key="index"
                color="negative"
                text-color="white"
                :label="number"
                removable
                @remove="removeInvalidNumber(number)"
              />
            </div>
          </q-expansion-item>
        </div>
        
        <!-- Actions rapides -->
        <div class="quick-actions">
          <q-btn
            flat
            dense
            icon="content_copy"
            label="Copier la liste"
            @click="copyRecipientList"
            class="action-btn"
          />
          <q-btn
            flat
            dense
            icon="download"
            label="Exporter CSV"
            @click="exportRecipientList"
            class="action-btn"
          />
          <q-btn
            flat
            dense
            icon="delete"
            label="Vider la liste"
            color="negative"
            @click="clearRecipients"
            class="action-btn"
          />
        </div>
      </div>
    </div>
    
    <!-- Limite de sécurité -->
    <div v-if="recipients.length > securityLimit" class="security-warning">
      <q-banner class="text-white bg-negative">
        <template v-slot:avatar>
          <q-icon name="security" color="white" />
        </template>
        <strong>Limite de sécurité dépassée</strong><br>
        Vous ne pouvez pas envoyer plus de {{ securityLimit }} messages à la fois.
        Réduisez le nombre de destinataires ou divisez votre envoi en plusieurs lots.
      </q-banner>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useQuasar } from 'quasar'
import ManualInput from './ManualInput.vue'
import CsvImport from './CsvImport.vue'
import GroupSelector from './GroupSelector.vue'
import SegmentSelector from './SegmentSelector.vue'

interface Props {
  recipients?: string[]
  recipientTab?: string
  selectedGroups?: number[]
  selectedSegments?: number[]
  securityLimit?: number
}

interface Emits {
  (e: 'update:recipients', value: string[]): void
  (e: 'update:recipientTab', value: string): void
  (e: 'update:selectedGroups', value: number[]): void
  (e: 'update:selectedSegments', value: number[]): void
}

const props = withDefaults(defineProps<Props>(), {
  recipients: () => [],
  recipientTab: 'manual',
  selectedGroups: () => [],
  selectedSegments: () => [],
  securityLimit: 500
})

const emit = defineEmits<Emits>()
const $q = useQuasar()

const recipientTab = ref(props.recipientTab)
const recipients = ref([...props.recipients])
const selectedGroups = ref([...props.selectedGroups])
const selectedSegments = ref([...props.selectedSegments])

// Validation des numéros de téléphone
const phoneRegex = /^\+[1-9]\d{1,14}$/

const validRecipients = computed(() => {
  return recipients.value.filter(number => phoneRegex.test(number))
})

const invalidRecipients = computed(() => {
  return recipients.value.filter(number => !phoneRegex.test(number))
})

// Watchers pour synchroniser avec le parent
watch(recipientTab, (newValue) => {
  emit('update:recipientTab', newValue)
})

watch(recipients, (newValue) => {
  emit('update:recipients', [...newValue])
}, { deep: true })

watch(selectedGroups, (newValue) => {
  emit('update:selectedGroups', [...newValue])
}, { deep: true })

watch(selectedSegments, (newValue) => {
  emit('update:selectedSegments', [...newValue])
}, { deep: true })

// Méthodes de gestion des destinataires
function updateRecipients(newRecipients: string[]) {
  recipients.value = [...newRecipients]
}

function handleCsvImport(importedRecipients: string[]) {
  recipients.value = [...new Set([...recipients.value, ...importedRecipients])]
  $q.notify({
    type: 'positive',
    message: `${importedRecipients.length} destinataires importés`,
    position: 'top'
  })
}

function updateSelectedGroups(groups: number[]) {
  selectedGroups.value = [...groups]
}

function handleGroupRecipients(groupRecipients: string[]) {
  recipients.value = [...new Set([...recipients.value, ...groupRecipients])]
}

function updateSelectedSegments(segments: number[]) {
  selectedSegments.value = [...segments]
}

function handleSegmentRecipients(segmentRecipients: string[]) {
  recipients.value = [...new Set([...recipients.value, ...segmentRecipients])]
}

function removeInvalidNumber(number: string) {
  const index = recipients.value.indexOf(number)
  if (index > -1) {
    recipients.value.splice(index, 1)
  }
}

function copyRecipientList() {
  const listText = validRecipients.value.join('\n')
  navigator.clipboard.writeText(listText).then(() => {
    $q.notify({
      type: 'positive',
      message: 'Liste copiée dans le presse-papiers',
      position: 'top'
    })
  })
}

function exportRecipientList() {
  const csvContent = 'data:text/csv;charset=utf-8,' + 
    'Numéro de téléphone\n' + 
    validRecipients.value.join('\n')
  
  const encodedUri = encodeURI(csvContent)
  const link = document.createElement('a')
  link.setAttribute('href', encodedUri)
  link.setAttribute('download', 'destinataires_whatsapp.csv')
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  
  $q.notify({
    type: 'positive',
    message: 'Liste exportée avec succès',
    position: 'top'
  })
}

function clearRecipients() {
  $q.dialog({
    title: 'Confirmer la suppression',
    message: 'Êtes-vous sûr de vouloir vider la liste des destinataires ?',
    cancel: true,
    persistent: true
  }).onOk(() => {
    recipients.value = []
    selectedGroups.value = []
    selectedSegments.value = []
    $q.notify({
      type: 'info',
      message: 'Liste des destinataires vidée',
      position: 'top'
    })
  })
}
</script>

<style lang="scss" scoped>
.recipient-manager {
  .section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid var(--q-primary);
    
    .section-icon {
      color: var(--q-primary);
      font-size: 24px;
    }
    
    .section-title {
      margin: 0;
      color: var(--q-dark);
      font-weight: 600;
      flex: 1;
    }
    
    .recipient-count {
      margin-left: auto;
    }
  }
  
  .recipient-tabs {
    margin-bottom: 16px;
    background: rgba(255, 255, 255, 0.8);
    border-radius: 8px;
  }
  
  .tab-panels {
    background: transparent;
    
    .manual-panel,
    .csv-panel,
    .groups-panel,
    .segments-panel {
      padding: 16px 0;
    }
  }
  
  .recipient-summary {
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
            font-size: 16px;
            
            &.valid {
              color: var(--q-positive);
            }
            
            &.invalid {
              color: var(--q-negative);
            }
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
      
      .invalid-numbers {
        margin-bottom: 16px;
        
        .invalid-list {
          display: flex;
          flex-wrap: wrap;
          gap: 8px;
          padding: 12px;
          background: rgba(244, 67, 54, 0.05);
          border-radius: 8px;
        }
      }
      
      .quick-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        
        .action-btn {
          font-size: 12px;
        }
      }
    }
  }
  
  .security-warning {
    margin-top: 16px;
    
    .q-banner {
      border-radius: 8px;
    }
  }
}

// Responsive design
@media (max-width: 768px) {
  .recipient-manager {
    .section-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 8px;
      
      .recipient-count {
        margin-left: 0;
      }
    }
    
    .recipient-summary .summary-content .summary-stats {
      flex-direction: column;
      gap: 12px;
    }
    
    .quick-actions {
      justify-content: center;
    }
  }
}
</style>