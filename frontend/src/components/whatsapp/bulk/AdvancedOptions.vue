<template>
  <div class="advanced-options">
    <div class="section-header">
      <q-icon name="tune" class="section-icon" />
      <h6 class="section-title">Options avancées</h6>
    </div>
    
    <div class="options-grid">
      <!-- Taille des lots -->
      <div class="option-card">
        <div class="option-header">
          <q-icon name="group_work" class="option-icon" />
          <span class="option-label">Taille des lots</span>
          <q-tooltip class="custom-tooltip">
            Nombre de messages envoyés simultanément. 
            Réduisez cette valeur si vous rencontrez des erreurs de limite de débit.
          </q-tooltip>
        </div>
        <q-slider
          v-model="localBatchSize"
          :min="1"
          :max="50"
          :step="1"
          label
          :label-value="`${localBatchSize} messages`"
          color="primary"
          track-color="grey-3"
          thumb-color="primary"
          class="batch-slider"
        />
        <div class="slider-info">
          <span class="info-text">Recommandé: 10-20 messages par lot</span>
        </div>
      </div>
      
      <!-- Délai entre les lots -->
      <div class="option-card">
        <div class="option-header">
          <q-icon name="schedule" class="option-icon" />
          <span class="option-label">Délai entre les lots</span>
          <q-tooltip class="custom-tooltip">
            Temps d'attente entre chaque lot pour respecter les limites de l'API WhatsApp.
          </q-tooltip>
        </div>
        <q-slider
          v-model="localBatchDelay"
          :min="100"
          :max="5000"
          :step="100"
          label
          :label-value="`${localBatchDelay}ms`"
          color="primary"
          track-color="grey-3"
          thumb-color="primary"
          class="delay-slider"
        />
        <div class="slider-info">
          <span class="info-text">Recommandé: 1000-2000ms pour éviter les limitations</span>
        </div>
      </div>
      
      <!-- Options d'envoi -->
      <div class="option-card">
        <div class="option-header">
          <q-icon name="send" class="option-icon" />
          <span class="option-label">Paramètres d'envoi</span>
        </div>
        
        <div class="toggle-options">
          <q-toggle
            v-model="localContinueOnError"
            label="Continuer en cas d'erreur"
            color="primary"
            class="toggle-option"
          />
          <div class="toggle-description">
            Continue l'envoi même si certains messages échouent
          </div>
          
          <q-toggle
            v-model="localShowProgress"
            label="Afficher le progrès détaillé"
            color="primary"
            class="toggle-option"
          />
          <div class="toggle-description">
            Affiche les détails de progression pour chaque lot
          </div>
        </div>
      </div>
      
      <!-- Gestion des erreurs -->
      <div class="option-card">
        <div class="option-header">
          <q-icon name="error_outline" class="option-icon" />
          <span class="option-label">Gestion des erreurs</span>
        </div>
        
        <q-select
          v-model="localRetryPolicy"
          :options="retryPolicyOptions"
          label="Politique de nouvelle tentative"
          outlined
          dense
          emit-value
          map-options
          class="retry-select"
        />
        
        <div class="retry-info">
          <q-icon name="info" size="14px" class="q-mr-xs" />
          <span class="info-text">
            {{ getRetryPolicyDescription(localRetryPolicy) }}
          </span>
        </div>
      </div>
    </div>
    
    <!-- Estimation des performances -->
    <div class="performance-estimate">
      <div class="estimate-header">
        <q-icon name="analytics" class="estimate-icon" />
        <span class="estimate-title">Estimation des performances</span>
      </div>
      
      <div class="estimate-metrics">
        <div class="metric">
          <div class="metric-value">{{ estimatedDuration }}</div>
          <div class="metric-label">Durée estimée</div>
        </div>
        <div class="metric">
          <div class="metric-value">{{ estimatedBatches }}</div>
          <div class="metric-label">Nombre de lots</div>
        </div>
        <div class="metric">
          <div class="metric-value">{{ messagesPerMinute }}</div>
          <div class="metric-label">Messages/min</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'

interface Props {
  batchSize?: number
  batchDelay?: number
  continueOnError?: boolean
  showProgress?: boolean
  retryPolicy?: string
  totalRecipients?: number
}

interface Emits {
  (e: 'update:batchSize', value: number): void
  (e: 'update:batchDelay', value: number): void
  (e: 'update:continueOnError', value: boolean): void
  (e: 'update:showProgress', value: boolean): void
  (e: 'update:retryPolicy', value: string): void
}

const props = withDefaults(defineProps<Props>(), {
  batchSize: 20,
  batchDelay: 1000,
  continueOnError: true,
  showProgress: true,
  retryPolicy: 'standard',
  totalRecipients: 0
})

const emit = defineEmits<Emits>()

const localBatchSize = ref(props.batchSize)
const localBatchDelay = ref(props.batchDelay)
const localContinueOnError = ref(props.continueOnError)
const localShowProgress = ref(props.showProgress)
const localRetryPolicy = ref(props.retryPolicy)

const retryPolicyOptions = [
  { label: 'Aucune nouvelle tentative', value: 'none' },
  { label: 'Standard (1 nouvelle tentative)', value: 'standard' },
  { label: 'Agressif (3 nouvelles tentatives)', value: 'aggressive' }
]

// Watchers pour émettre les changements
watch(localBatchSize, (newValue) => {
  emit('update:batchSize', newValue)
})

watch(localBatchDelay, (newValue) => {
  emit('update:batchDelay', newValue)
})

watch(localContinueOnError, (newValue) => {
  emit('update:continueOnError', newValue)
})

watch(localShowProgress, (newValue) => {
  emit('update:showProgress', newValue)
})

watch(localRetryPolicy, (newValue) => {
  emit('update:retryPolicy', newValue)
})

// Calculs de performance
const estimatedBatches = computed(() => {
  if (props.totalRecipients === 0) return 0
  return Math.ceil(props.totalRecipients / localBatchSize.value)
})

const estimatedDuration = computed(() => {
  if (props.totalRecipients === 0) return '0s'
  
  const totalTime = (estimatedBatches.value - 1) * localBatchDelay.value
  const minutes = Math.floor(totalTime / 60000)
  const seconds = Math.floor((totalTime % 60000) / 1000)
  
  if (minutes > 0) {
    return `${minutes}m ${seconds}s`
  }
  return `${seconds}s`
})

const messagesPerMinute = computed(() => {
  if (props.totalRecipients === 0) return 0
  
  const totalTimeMs = (estimatedBatches.value - 1) * localBatchDelay.value + 5000 // +5s pour le processing
  const messagesPerMs = props.totalRecipients / totalTimeMs
  return Math.round(messagesPerMs * 60000)
})

function getRetryPolicyDescription(policy: string): string {
  switch (policy) {
    case 'none':
      return 'Aucune nouvelle tentative en cas d\'échec'
    case 'standard':
      return 'Une nouvelle tentative après 2 secondes'
    case 'aggressive':
      return 'Jusqu\'à 3 nouvelles tentatives avec délais croissants'
    default:
      return ''
  }
}
</script>

<style lang="scss" scoped>
.advanced-options {
  .section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
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
    }
  }
  
  .options-grid {
    display: grid;
    gap: 20px;
    margin-bottom: 24px;
    
    .option-card {
      background: white;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
      border: 1px solid var(--q-grey-4);
      transition: all 0.3s ease;
      
      &:hover {
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
        transform: translateY(-2px);
      }
      
      .option-header {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 16px;
        
        .option-icon {
          color: var(--q-primary);
          font-size: 20px;
        }
        
        .option-label {
          font-weight: 600;
          color: var(--q-dark);
          flex: 1;
        }
      }
      
      .batch-slider, .delay-slider {
        margin: 16px 0;
      }
      
      .slider-info {
        margin-top: 8px;
        
        .info-text {
          font-size: 12px;
          color: var(--q-grey-6);
          font-style: italic;
        }
      }
      
      .toggle-options {
        .toggle-option {
          margin-bottom: 8px;
        }
        
        .toggle-description {
          font-size: 13px;
          color: var(--q-grey-6);
          margin-bottom: 16px;
          margin-left: 32px;
        }
      }
      
      .retry-select {
        margin-bottom: 12px;
      }
      
      .retry-info {
        display: flex;
        align-items: flex-start;
        gap: 4px;
        
        .info-text {
          font-size: 12px;
          color: var(--q-grey-6);
          line-height: 1.4;
        }
      }
    }
  }
  
  .performance-estimate {
    background: linear-gradient(135deg, rgba(37, 211, 102, 0.1) 0%, rgba(18, 140, 126, 0.1) 100%);
    border-radius: 12px;
    padding: 20px;
    border: 1px solid rgba(37, 211, 102, 0.3);
    
    .estimate-header {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 16px;
      
      .estimate-icon {
        color: var(--q-primary);
        font-size: 20px;
      }
      
      .estimate-title {
        font-weight: 600;
        color: var(--q-dark);
      }
    }
    
    .estimate-metrics {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
      gap: 16px;
      
      .metric {
        text-align: center;
        padding: 12px;
        background: rgba(255, 255, 255, 0.8);
        border-radius: 8px;
        
        .metric-value {
          font-size: 18px;
          font-weight: 700;
          color: var(--q-primary);
          margin-bottom: 4px;
        }
        
        .metric-label {
          font-size: 12px;
          color: var(--q-grey-6);
          text-transform: uppercase;
          letter-spacing: 0.5px;
        }
      }
    }
  }
}

.custom-tooltip {
  background: var(--q-dark) !important;
  color: white !important;
  font-size: 12px !important;
  max-width: 200px !important;
}

// Responsive design
@media (max-width: 768px) {
  .advanced-options {
    .options-grid {
      grid-template-columns: 1fr;
    }
    
    .performance-estimate .estimate-metrics {
      grid-template-columns: repeat(2, 1fr);
      gap: 12px;
    }
  }
}
</style>