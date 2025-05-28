<template>
  <q-dialog
    v-model="dialogOpen"
    persistent
    maximized
    class="bulk-send-dialog"
    transition-show="slide-up"
    transition-hide="slide-down"
  >
    <q-card class="bulk-send-card">
      <!-- Header moderne avec gradient WhatsApp -->
      <q-card-section class="card-header whatsapp-header">
        <div class="header-content">
          <div class="header-icon-wrapper">
            <q-icon name="send" size="28px" />
          </div>
          <div class="header-text">
            <h3 class="card-title">Envoi en masse WhatsApp</h3>
            <p class="card-subtitle">Envoyez des messages personnalisés à plusieurs destinataires</p>
          </div>
        </div>
        <q-btn 
          icon="close" 
          flat 
          round 
          dense 
          v-close-popup 
          @click="handleCancel"
          class="close-btn"
          color="white"
        >
          <q-tooltip>Fermer</q-tooltip>
        </q-btn>
      </q-card-section>

      <q-separator />

      <!-- Contenu principal -->
      <q-card-section class="content-section">
        <div class="bulk-send-layout">
          <!-- Colonne gauche: Configuration -->
          <div class="config-column">
            <q-scroll-area style="height: calc(100vh - 140px);">
              <div class="config-content">
                <!-- Sélection du template -->
                <TemplateSelector
                  :model-value="selectedTemplate"
                  :loading-templates="loadingTemplates"
                  @update:model-value="updateSelectedTemplate"
                  @load-templates="loadTemplates"
                />

                <!-- Personnalisation du template -->
                <TemplateCustomization
                  v-if="selectedTemplate"
                  :template="currentTemplate"
                  :customization="templateCustomization"
                  :body-variables="bodyVariables || []"
                  :header-variables="headerVariables || []"
                  :has-header-media="hasHeaderMedia || false"
                  :has-template-variables="hasTemplateVariables || false"
                  :preview-message="previewMessage"
                  @update:customization="updateTemplateCustomization"
                  class="q-mt-lg"
                />

                <!-- Gestion des destinataires -->
                <RecipientManager
                  :recipients="recipients"
                  :recipient-tab="recipientTab"
                  :selected-groups="selectedGroups"
                  :selected-segments="selectedSegments"
                  @update:recipients="updateRecipients"
                  @update:recipient-tab="updateRecipientTab"
                  @update:selected-groups="updateSelectedGroups"
                  @update:selected-segments="updateSelectedSegments"
                  class="q-mt-lg"
                />

                <!-- Options avancées -->
                <AdvancedOptions
                  :batch-size="batchSize"
                  :batch-delay="batchDelay"
                  :continue-on-error="continueOnError"
                  :show-progress="showProgress"
                  :retry-policy="retryPolicy"
                  :total-recipients="recipients.length"
                  @update:batch-size="updateBatchSize"
                  @update:batch-delay="updateBatchDelay"
                  @update:continue-on-error="updateContinueOnError"
                  @update:show-progress="updateShowProgress"
                  @update:retry-policy="updateRetryPolicy"
                  class="q-mt-lg"
                />
              </div>
            </q-scroll-area>
          </div>

          <!-- Colonne droite: Aperçu et contrôles -->
          <div class="preview-column">
            <q-scroll-area style="height: calc(100vh - 140px);">
              <div class="preview-content">

                <!-- Progression de l'envoi -->
                <SendProgress
                  v-if="sending || sendingComplete"
                  :progress="progress"
                  :stats="stats"
                  :batch-progress="batchProgress"
                  :errors="errors"
                  :sending="sending"
                  :sending-complete="sendingComplete"
                  :paused="paused"
                  :show-detailed-progress="showProgress"
                  :start-time="sendStartTime || undefined"
                  :current-rate="currentRate"
                  @pause="pauseSending"
                  @resume="resumeSending"
                  @stop="stopSending"
                  class="q-mt-lg"
                />

                <!-- Boutons d'action -->
                <ActionButtons
                  :can-send="!!canSend"
                  :recipient-count="recipients.length"
                  :selected-template-name="currentTemplate?.name"
                  :sending="sending"
                  :sending-complete="sendingComplete"
                  :paused="paused"
                  :preparing="preparing"
                  :validation-errors="validationErrors"
                  :warnings="warnings"
                  :estimated-duration="estimatedDuration"
                  :estimated-cost="estimatedCost"
                  @send="startSending"
                  @pause="pauseSending"
                  @resume="resumeSending"
                  @stop="stopSending"
                  @reset="resetSending"
                  @cancel="handleCancel"
                  @download-report="downloadReport"
                  class="q-mt-lg"
                />
              </div>
            </q-scroll-area>
          </div>
        </div>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useBulkSend } from '../../composables/useBulkSend'
import { useTemplateCustomization } from '../../composables/useTemplateCustomization'
import { useRecipientManagement } from '../../composables/useRecipientManagement'
import TemplateSelector from './bulk/TemplateSelector.vue'
import TemplateCustomization from './bulk/TemplateCustomization.vue'
import RecipientManager from './bulk/RecipientManager.vue'
import AdvancedOptions from './bulk/AdvancedOptions.vue'
import SendProgress from './bulk/SendProgress.vue'
import ActionButtons from './bulk/ActionButtons.vue'

interface Props {
  modelValue?: boolean
  templates?: any[]
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: false,
  templates: () => []
})

const emit = defineEmits<Emits>()

// Dialog state
const dialogOpen = ref(props.modelValue)

// Template management
const templates = ref(props.templates)
const { 
  selectedTemplate, 
  templateCustomization, 
  currentTemplate, 
  bodyVariables,
  headerVariables,
  hasHeaderMedia,
  hasTemplateVariables,
  previewMessage,
  updateSelectedTemplate, 
  updateTemplateCustomization,
  loadTemplates,
  loadingTemplates 
} = useTemplateCustomization(templates)

// Recipient management
const {
  recipientTab,
  recipients,
  selectedGroups,
  selectedSegments,
  updateRecipients,
  updateRecipientTab,
  updateSelectedGroups,
  updateSelectedSegments
} = useRecipientManagement()

// Bulk send logic
const {
  sending,
  sendingComplete,
  paused,
  preparing,
  progress,
  stats,
  batchProgress,
  errors,
  sendStartTime,
  currentRate,
  batchSize,
  batchDelay,
  continueOnError,
  showProgress,
  retryPolicy,
  updateBatchSize,
  updateBatchDelay,
  updateContinueOnError,
  updateShowProgress,
  updateRetryPolicy,
  startSending,
  pauseSending,
  resumeSending,
  stopSending,
  resetSending
} = useBulkSend()

// Computed properties
const canSend = computed(() => {
  return recipients.value.length > 0 && 
         selectedTemplate.value && 
         !sending.value &&
         validationErrors.value.length === 0
})

const validationErrors = computed(() => {
  const errors: string[] = []
  
  if (recipients.value.length === 0) {
    errors.push('Aucun destinataire sélectionné')
  }
  
  if (!selectedTemplate.value) {
    errors.push('Aucun template sélectionné')
  }
  
  if (recipients.value.length > 500) {
    errors.push('Nombre maximum de destinataires dépassé (500)')
  }
  
  return errors
})

const warnings = computed(() => {
  const warnings: string[] = []
  
  if (recipients.value.length > 100) {
    warnings.push('Envoi en masse important - vérifiez vos paramètres')
  }
  
  if (batchDelay.value < 1000) {
    warnings.push('Délai entre lots très court - risque de limitation API')
  }
  
  return warnings
})


const estimatedDuration = computed(() => {
  if (recipients.value.length === 0) return '--'
  
  const batches = Math.ceil(recipients.value.length / batchSize.value)
  const totalTime = (batches - 1) * batchDelay.value + (batches * 2000) // +2s per batch for processing
  const minutes = Math.floor(totalTime / 60000)
  const seconds = Math.floor((totalTime % 60000) / 1000)
  
  if (minutes > 0) {
    return `${minutes}m ${seconds}s`
  }
  return `${seconds}s`
})

const estimatedCost = computed(() => {
  // Estimation basée sur les tarifs WhatsApp Business
  const costPerMessage = 0.05 // €0.05 par message (estimation)
  const totalCost = recipients.value.length * costPerMessage
  return `€${totalCost.toFixed(2)}`
})

// Watchers
watch(() => props.modelValue, (newValue) => {
  dialogOpen.value = newValue
})

watch(dialogOpen, async (newValue) => {
  emit('update:modelValue', newValue)
  
  // Charger les templates quand le dialog s'ouvre
  if (newValue && (!templates.value || templates.value.length === 0)) {
    console.log('[BulkSendDialog] Dialog ouvert - chargement des templates')
    await loadTemplates()
  }
})

watch(() => props.templates, (newTemplates) => {
  templates.value = newTemplates
})

// Lifecycle
onMounted(async () => {
  console.log('[BulkSendDialog] Montage du composant - chargement initial des templates')
  if (!templates.value || templates.value.length === 0) {
    await loadTemplates()
  }
})

// Methods
function handleCancel() {
  if (sending.value) {
    stopSending()
  }
  resetForm()
  dialogOpen.value = false
}

function resetForm() {
  resetSending()
  updateSelectedTemplate('')
  updateRecipients([])
  updateSelectedGroups([])
  updateSelectedSegments([])
}

function downloadReport() {
  // Générer un rapport d'envoi
  const report = {
    timestamp: new Date().toISOString(),
    template: currentTemplate.value?.name,
    recipients: recipients.value.length,
    stats: stats.value,
    errors: errors.value
  }
  
  const dataStr = JSON.stringify(report, null, 2)
  const dataBlob = new Blob([dataStr], { type: 'application/json' })
  const url = URL.createObjectURL(dataBlob)
  const link = document.createElement('a')
  link.href = url
  link.download = `whatsapp-bulk-send-report-${new Date().toISOString().split('T')[0]}.json`
  link.click()
  URL.revokeObjectURL(url)
}
</script>

<style lang="scss" scoped>
.bulk-send-dialog {
  .bulk-send-card {
    max-width: none;
    width: 100%;
    height: 100%;
    
    .card-header {
      background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
      color: white;
      padding: 20px 24px;
      
      .header-content {
        display: flex;
        align-items: center;
        gap: 16px;
        
        .header-icon-wrapper {
          background: rgba(255, 255, 255, 0.2);
          border-radius: 50%;
          padding: 12px;
          
          .q-icon {
            color: white;
          }
        }
        
        .header-text {
          flex: 1;
          
          .card-title {
            margin: 0 0 4px 0;
            font-size: 24px;
            font-weight: 600;
          }
          
          .card-subtitle {
            margin: 0;
            opacity: 0.9;
            font-size: 14px;
          }
        }
      }
      
      .close-btn {
        &:hover {
          background: rgba(255, 255, 255, 0.1);
        }
      }
    }
    
    .content-section {
      padding: 0;
      height: calc(100vh - 120px);
      
      .bulk-send-layout {
        display: grid;
        grid-template-columns: 1fr 400px;
        height: 100%;
        
        .config-column {
          border-right: 1px solid var(--q-grey-4);
          background: #f8f9fa;
          
          .config-content {
            padding: 24px;
          }
        }
        
        .preview-column {
          background: white;
          
          .preview-content {
            padding: 24px;
          }
          
          .preview-section {
            .section-header {
              display: flex;
              align-items: center;
              gap: 8px;
              margin-bottom: 16px;
              
              .section-icon {
                color: var(--q-primary);
                font-size: 20px;
              }
              
              .section-title {
                margin: 0;
                color: var(--q-dark);
                font-weight: 600;
              }
            }
          }
        }
      }
    }
  }
}

// Responsive design
@media (max-width: 1200px) {
  .bulk-send-dialog .bulk-send-card .content-section .bulk-send-layout {
    grid-template-columns: 1fr 350px;
  }
}

@media (max-width: 768px) {
  .bulk-send-dialog .bulk-send-card .content-section .bulk-send-layout {
    grid-template-columns: 1fr;
    
    .config-column {
      border-right: none;
      border-bottom: 1px solid var(--q-grey-4);
    }
    
    .preview-column {
      min-height: 50vh;
    }
  }
}
</style>