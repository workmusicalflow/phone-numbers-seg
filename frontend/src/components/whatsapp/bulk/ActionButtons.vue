<template>
  <div class="action-buttons">
    <div class="buttons-container">
      <!-- Bouton principal d'envoi -->
      <q-btn
        v-if="!sending && !sendingComplete"
        color="primary"
        size="lg"
        :icon="canSend ? 'send' : 'warning'"
        :label="canSend ? 'Envoyer les messages' : 'Impossible d\'envoyer'"
        :disable="!canSend"
        :loading="preparing"
        @click="handleSend"
        class="send-btn"
      />
      
      <!-- Boutons pendant l'envoi -->
      <template v-if="sending && !sendingComplete">
        <q-btn
          color="warning"
          size="lg"
          :icon="paused ? 'play_arrow' : 'pause'"
          :label="paused ? 'Reprendre' : 'Mettre en pause'"
          @click="handlePauseResume"
          class="pause-btn"
        />
        
        <q-btn
          color="negative"
          size="lg"
          icon="stop"
          label="Arrêter l'envoi"
          @click="handleStop"
          class="stop-btn"
        />
      </template>
      
      <!-- Boutons après l'envoi -->
      <template v-if="sendingComplete">
        <q-btn
          color="primary"
          size="lg"
          icon="refresh"
          label="Nouvel envoi"
          @click="handleReset"
          class="reset-btn"
        />
        
        <q-btn
          flat
          size="lg"
          icon="download"
          label="Télécharger le rapport"
          @click="handleDownloadReport"
          class="download-btn"
        />
      </template>
      
      <!-- Bouton d'annulation -->
      <q-btn
        v-if="!sending"
        flat
        size="lg"
        icon="close"
        label="Annuler"
        @click="handleCancel"
        class="cancel-btn"
      />
    </div>
    
    <!-- Informations et validations -->
    <div class="action-info">
      <!-- Messages d'erreur -->
      <div v-if="validationErrors.length > 0" class="validation-errors">
        <q-banner class="text-white bg-negative">
          <template v-slot:avatar>
            <q-icon name="error" color="white" />
          </template>
          <div class="error-list">
            <div v-for="error in validationErrors" :key="error" class="error-item">
              {{ error }}
            </div>
          </div>
        </q-banner>
      </div>
      
      <!-- Avertissements -->
      <div v-if="warnings.length > 0" class="validation-warnings">
        <q-banner class="text-dark bg-warning">
          <template v-slot:avatar>
            <q-icon name="warning" color="dark" />
          </template>
          <div class="warning-list">
            <div v-for="warning in warnings" :key="warning" class="warning-item">
              {{ warning }}
            </div>
          </div>
        </q-banner>
      </div>
      
      <!-- Résumé de l'envoi -->
      <div v-if="canSend && recipientCount > 0" class="send-summary">
        <div class="summary-card">
          <div class="summary-header">
            <q-icon name="info" class="summary-icon" />
            <span class="summary-title">Résumé de l'envoi</span>
          </div>
          
          <div class="summary-content">
            <div class="summary-item">
              <q-icon name="people" class="item-icon" />
              <span class="item-label">Destinataires:</span>
              <span class="item-value">{{ recipientCount }}</span>
            </div>
            
            <div class="summary-item">
              <q-icon name="message" class="item-icon" />
              <span class="item-label">Template:</span>
              <span class="item-value">{{ selectedTemplateName || 'Aucun template sélectionné' }}</span>
            </div>
            
            <div class="summary-item">
              <q-icon name="schedule" class="item-icon" />
              <span class="item-label">Durée estimée:</span>
              <span class="item-value">{{ estimatedDuration }}</span>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Confirmation finale -->
      <div v-if="showFinalConfirmation" class="final-confirmation">
        <q-banner class="text-white bg-info">
          <template v-slot:avatar>
            <q-icon name="help" color="white" />
          </template>
          <strong>Confirmer l'envoi en masse</strong><br>
          Vous êtes sur le point d'envoyer {{ recipientCount }} message{{ recipientCount > 1 ? 's' : '' }} WhatsApp.
          Cette action est irréversible.
          
          <template v-slot:action>
            <q-btn
              flat
              color="white"
              label="Confirmer"
              @click="confirmSend"
            />
            <q-btn
              flat
              color="white"
              label="Annuler"
              @click="cancelConfirmation"
            />
          </template>
        </q-banner>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useQuasar } from 'quasar'

interface Props {
  canSend?: boolean
  recipientCount?: number
  selectedTemplateName?: string
  sending?: boolean
  sendingComplete?: boolean
  paused?: boolean
  preparing?: boolean
  validationErrors?: string[]
  warnings?: string[]
  batchSize?: number
  batchDelay?: number
  estimatedDuration?: string
}

interface Emits {
  (e: 'send'): void
  (e: 'pause'): void
  (e: 'resume'): void
  (e: 'stop'): void
  (e: 'reset'): void
  (e: 'cancel'): void
  (e: 'download-report'): void
}

const props = withDefaults(defineProps<Props>(), {
  canSend: false,
  recipientCount: 0,
  selectedTemplateName: '',
  sending: false,
  sendingComplete: false,
  paused: false,
  preparing: false,
  validationErrors: () => [],
  warnings: () => [],
  batchSize: 20,
  batchDelay: 1000,
  estimatedDuration: '--'
})

const emit = defineEmits<Emits>()
const $q = useQuasar()

const showFinalConfirmation = ref(false)

// Méthodes d'action
function handleSend() {
  if (!props.canSend) return
  
  // Afficher une confirmation pour les envois importants
  if (props.recipientCount > 100) {
    showFinalConfirmation.value = true
  } else {
    emit('send')
  }
}

function confirmSend() {
  showFinalConfirmation.value = false
  emit('send')
}

function cancelConfirmation() {
  showFinalConfirmation.value = false
}

function handlePauseResume() {
  if (props.paused) {
    emit('resume')
  } else {
    emit('pause')
  }
}

function handleStop() {
  $q.dialog({
    title: 'Confirmer l\'arrêt',
    message: 'Êtes-vous sûr de vouloir arrêter l\'envoi en cours ? Les messages non envoyés seront perdus.',
    cancel: true,
    persistent: true
  }).onOk(() => {
    emit('stop')
  })
}

function handleReset() {
  $q.dialog({
    title: 'Nouvel envoi',
    message: 'Voulez-vous préparer un nouvel envoi ? Les paramètres actuels seront conservés.',
    cancel: true,
    persistent: true
  }).onOk(() => {
    emit('reset')
  })
}

function handleCancel() {
  emit('cancel')
}

function handleDownloadReport() {
  emit('download-report')
}
</script>

<style lang="scss" scoped>
.action-buttons {
  .buttons-container {
    display: flex;
    gap: 12px;
    justify-content: center;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    
    .send-btn {
      min-width: 200px;
      font-weight: 600;
      
      &:disabled {
        opacity: 0.6;
      }
    }
    
    .pause-btn,
    .stop-btn,
    .reset-btn {
      min-width: 140px;
      font-weight: 500;
    }
    
    .download-btn,
    .cancel-btn {
      min-width: 120px;
    }
  }
  
  .action-info {
    .validation-errors,
    .validation-warnings {
      margin-bottom: 16px;
      
      .error-list,
      .warning-list {
        .error-item,
        .warning-item {
          margin-bottom: 4px;
          
          &:last-child {
            margin-bottom: 0;
          }
        }
      }
    }
    
    .send-summary {
      margin-bottom: 16px;
      
      .summary-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        border: 1px solid var(--q-grey-4);
        
        .summary-header {
          display: flex;
          align-items: center;
          gap: 8px;
          margin-bottom: 16px;
          
          .summary-icon {
            color: var(--q-info);
            font-size: 20px;
          }
          
          .summary-title {
            font-weight: 600;
            color: var(--q-dark);
          }
        }
        
        .summary-content {
          display: grid;
          gap: 12px;
          
          .summary-item {
            display: flex;
            align-items: center;
            gap: 8px;
            
            .item-icon {
              color: var(--q-primary);
              font-size: 16px;
              width: 20px;
            }
            
            .item-label {
              color: var(--q-grey-6);
              font-size: 14px;
              min-width: 120px;
            }
            
            .item-value {
              font-weight: 500;
              color: var(--q-dark);
              flex: 1;
            }
          }
        }
      }
    }
    
    .final-confirmation {
      margin-bottom: 16px;
      
      .q-banner {
        border-radius: 8px;
      }
    }
  }
}

// États des boutons
.send-btn {
  &.q-btn--disabled {
    background: var(--q-grey-4) !important;
    color: var(--q-grey-6) !important;
  }
}

.pause-btn {
  &:hover {
    background: rgba(255, 193, 7, 0.1);
  }
}

.stop-btn {
  &:hover {
    background: rgba(244, 67, 54, 0.1);
  }
}

.reset-btn {
  background: linear-gradient(135deg, var(--q-primary) 0%, #4CAF50 100%);
  
  &:hover {
    background: linear-gradient(135deg, #1976D2 0%, #388E3C 100%);
  }
}

// Responsive design
@media (max-width: 768px) {
  .action-buttons {
    .buttons-container {
      flex-direction: column;
      
      .send-btn,
      .pause-btn,
      .stop-btn,
      .reset-btn,
      .download-btn,
      .cancel-btn {
        min-width: 0;
        width: 100%;
        max-width: 300px;
      }
    }
    
    .action-info .send-summary .summary-card .summary-content {
      .summary-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
        
        .item-label {
          min-width: 0;
          font-weight: 600;
        }
      }
    }
  }
}
</style>