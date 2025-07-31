<template>
  <div class="send-progress">
    <div class="progress-header">
      <q-icon name="analytics" class="progress-icon" />
      <h6 class="progress-title">Progression de l'envoi</h6>
    </div>
    
    <!-- Barre de progression globale -->
    <div class="global-progress">
      <div class="progress-info">
        <span class="progress-label">Progression globale</span>
        <span class="progress-percentage">{{ Math.round(progress) }}%</span>
      </div>
      
      <q-linear-progress
        :value="progress / 100"
        size="12px"
        color="primary"
        track-color="grey-3"
        class="progress-bar"
      />
      
      <div class="progress-details">
        <span class="detail-text">
          {{ stats.successful + stats.failed }} / {{ stats.total }} messages traités
        </span>
        <span class="detail-text">
          ETA: {{ estimatedTimeRemaining }}
        </span>
      </div>
    </div>
    
    <!-- Statistiques en temps réel -->
    <div class="stats-grid">
      <div class="stat-card success">
        <div class="stat-header">
          <q-icon name="check_circle" class="stat-icon" />
          <span class="stat-label">Réussis</span>
        </div>
        <div class="stat-value">{{ stats.successful }}</div>
        <div class="stat-percentage">
          {{ stats.total > 0 ? Math.round((stats.successful / stats.total) * 100) : 0 }}%
        </div>
      </div>
      
      <div class="stat-card error">
        <div class="stat-header">
          <q-icon name="error" class="stat-icon" />
          <span class="stat-label">Échecs</span>
        </div>
        <div class="stat-value">{{ stats.failed }}</div>
        <div class="stat-percentage">
          {{ stats.total > 0 ? Math.round((stats.failed / stats.total) * 100) : 0 }}%
        </div>
      </div>
      
      <div class="stat-card pending">
        <div class="stat-header">
          <q-icon name="schedule" class="stat-icon" />
          <span class="stat-label">En attente</span>
        </div>
        <div class="stat-value">{{ stats.total - stats.successful - stats.failed }}</div>
        <div class="stat-percentage">
          {{ stats.total > 0 ? Math.round(((stats.total - stats.successful - stats.failed) / stats.total) * 100) : 0 }}%
        </div>
      </div>
      
      <div class="stat-card rate">
        <div class="stat-header">
          <q-icon name="speed" class="stat-icon" />
          <span class="stat-label">Débit</span>
        </div>
        <div class="stat-value">{{ currentRate }}</div>
        <div class="stat-label">msg/min</div>
      </div>
    </div>
    
    <!-- Progression par lots -->
    <div v-if="showDetailedProgress && batchProgress.length > 0" class="batch-progress">
      <div class="batch-header">
        <q-icon name="group_work" class="batch-icon" />
        <span class="batch-label">Progression par lots</span>
        <q-btn
          flat
          dense
          :icon="showBatches ? 'expand_less' : 'expand_more'"
          @click="showBatches = !showBatches"
          class="toggle-btn"
        />
      </div>
      
      <q-slide-transition>
        <div v-show="showBatches" class="batch-list">
          <div
            v-for="(batch, index) in batchProgress"
            :key="index"
            class="batch-item"
            :class="batch.status"
          >
            <div class="batch-info">
              <span class="batch-number">Lot {{ index + 1 }}</span>
              <span class="batch-status">{{ getBatchStatusLabel(batch.status) }}</span>
            </div>
            
            <div class="batch-details">
              <q-linear-progress
                :value="batch.progress / 100"
                size="6px"
                :color="getBatchStatusColor(batch.status)"
                track-color="grey-3"
                class="batch-progress-bar"
              />
              
              <div class="batch-stats">
                <span class="batch-stat">
                  {{ batch.successful }}/{{ batch.total }} réussis
                </span>
                <span v-if="batch.failed > 0" class="batch-stat error">
                  {{ batch.failed }} échecs
                </span>
                <span v-if="batch.duration" class="batch-stat">
                  {{ batch.duration }}ms
                </span>
              </div>
            </div>
          </div>
        </div>
      </q-slide-transition>
    </div>
    
    <!-- Journal des erreurs -->
    <div v-if="errors.length > 0" class="error-log">
      <div class="error-header">
        <q-icon name="bug_report" class="error-icon" />
        <span class="error-label">Journal des erreurs ({{ errors.length }})</span>
        <q-btn
          flat
          dense
          :icon="showErrors ? 'expand_less' : 'expand_more'"
          @click="showErrors = !showErrors"
          class="toggle-btn"
        />
      </div>
      
      <q-slide-transition>
        <div v-show="showErrors" class="error-list">
          <div
            v-for="(error, index) in errors.slice(0, 10)"
            :key="index"
            class="error-item"
          >
            <div class="error-time">{{ formatTime(error.timestamp) }}</div>
            <div class="error-message">{{ error.message }}</div>
            <div v-if="error.phoneNumber" class="error-phone">{{ error.phoneNumber }}</div>
          </div>
          
          <div v-if="errors.length > 10" class="more-errors">
            <q-chip 
              color="negative" 
              text-color="white"
              :label="`+${errors.length - 10} autres erreurs`"
            />
          </div>
        </div>
      </q-slide-transition>
    </div>
    
    <!-- Actions de contrôle -->
    <div v-if="sending && !sendingComplete" class="control-actions">
      <q-btn
        flat
        icon="pause"
        label="Mettre en pause"
        @click="$emit('pause')"
        :disable="paused"
        class="control-btn"
      />
      <q-btn
        flat
        icon="play_arrow"
        label="Reprendre"
        @click="$emit('resume')"
        :disable="!paused"
        class="control-btn"
      />
      <q-btn
        flat
        icon="stop"
        label="Arrêter"
        color="negative"
        @click="$emit('stop')"
        class="control-btn"
      />
    </div>
    
    <!-- Message de fin -->
    <div v-if="sendingComplete" class="completion-message">
      <div class="completion-content" :class="{ 'has-errors': stats.failed > 0 }">
        <q-icon 
          :name="stats.failed > 0 ? 'warning' : 'check_circle'" 
          size="32px" 
          :color="stats.failed > 0 ? 'warning' : 'positive'"
          class="completion-icon"
        />
        
        <div class="completion-text">
          <h6 class="completion-title">
            {{ stats.failed > 0 ? 'Envoi terminé avec des erreurs' : 'Envoi terminé avec succès' }}
          </h6>
          <p class="completion-summary">
            {{ stats.successful }} message{{ stats.successful > 1 ? 's' : '' }} envoyé{{ stats.successful > 1 ? 's' : '' }}
            {{ stats.failed > 0 ? `, ${stats.failed} échec${stats.failed > 1 ? 's' : ''}` : '' }}
            en {{ totalDuration }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'

interface BulkSendStats {
  successful: number
  failed: number
  total: number
}

interface BatchProgress {
  status: 'pending' | 'sending' | 'completed' | 'failed'
  progress: number
  successful: number
  failed: number
  total: number
  duration?: number
}

interface SendError {
  message: string
  phoneNumber?: string
  timestamp: Date
}

interface Props {
  progress?: number
  stats?: BulkSendStats
  batchProgress?: BatchProgress[]
  errors?: SendError[]
  sending?: boolean
  sendingComplete?: boolean
  paused?: boolean
  showDetailedProgress?: boolean
  startTime?: Date
  currentRate?: number
}

interface Emits {
  (e: 'pause'): void
  (e: 'resume'): void
  (e: 'stop'): void
}

const props = withDefaults(defineProps<Props>(), {
  progress: 0,
  stats: () => ({ successful: 0, failed: 0, total: 0 }),
  batchProgress: () => [],
  errors: () => [],
  sending: false,
  sendingComplete: false,
  paused: false,
  showDetailedProgress: true,
  startTime: () => new Date(),
  currentRate: 0
})

const emit = defineEmits<Emits>()

const showBatches = ref(false)
const showErrors = ref(false)

// Computed properties
const estimatedTimeRemaining = computed(() => {
  if (!props.sending || props.currentRate === 0) return '--'
  
  const remaining = props.stats.total - props.stats.successful - props.stats.failed
  const minutesRemaining = remaining / props.currentRate
  
  if (minutesRemaining < 1) {
    return '< 1 min'
  } else if (minutesRemaining < 60) {
    return `${Math.round(minutesRemaining)} min`
  } else {
    const hours = Math.floor(minutesRemaining / 60)
    const minutes = Math.round(minutesRemaining % 60)
    return `${hours}h ${minutes}min`
  }
})

const totalDuration = computed(() => {
  if (!props.startTime) return '--'
  
  const endTime = props.sendingComplete ? new Date() : new Date()
  const diffMs = endTime.getTime() - props.startTime.getTime()
  const diffMinutes = Math.floor(diffMs / 60000)
  const diffSeconds = Math.floor((diffMs % 60000) / 1000)
  
  if (diffMinutes < 1) {
    return `${diffSeconds}s`
  } else {
    return `${diffMinutes}min ${diffSeconds}s`
  }
})

// Méthodes
function getBatchStatusLabel(status: string): string {
  switch (status) {
    case 'pending': return 'En attente'
    case 'sending': return 'En cours'
    case 'completed': return 'Terminé'
    case 'failed': return 'Échec'
    default: return status
  }
}

function getBatchStatusColor(status: string): string {
  switch (status) {
    case 'pending': return 'grey'
    case 'sending': return 'primary'
    case 'completed': return 'positive'
    case 'failed': return 'negative'
    default: return 'grey'
  }
}

function formatTime(timestamp: Date): string {
  return timestamp.toLocaleTimeString('fr-FR', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

// Watchers pour auto-expand en cas d'erreurs
watch(() => props.errors.length, (newLength, oldLength) => {
  if (newLength > oldLength && newLength > 0) {
    showErrors.value = true
  }
})
</script>

<style lang="scss" scoped>
.send-progress {
  .progress-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
    padding-bottom: 12px;
    border-bottom: 2px solid var(--q-primary);
    
    .progress-icon {
      color: var(--q-primary);
      font-size: 24px;
    }
    
    .progress-title {
      margin: 0;
      color: var(--q-dark);
      font-weight: 600;
    }
  }
  
  .global-progress {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    border: 1px solid var(--q-grey-4);
    
    .progress-info {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 12px;
      
      .progress-label {
        font-weight: 600;
        color: var(--q-dark);
      }
      
      .progress-percentage {
        font-size: 18px;
        font-weight: 700;
        color: var(--q-primary);
      }
    }
    
    .progress-bar {
      margin-bottom: 12px;
      border-radius: 6px;
    }
    
    .progress-details {
      display: flex;
      justify-content: space-between;
      
      .detail-text {
        font-size: 12px;
        color: var(--q-grey-6);
      }
    }
  }
  
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
    
    .stat-card {
      background: white;
      border-radius: 12px;
      padding: 16px;
      box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
      border: 1px solid var(--q-grey-4);
      transition: transform 0.2s ease;
      
      &:hover {
        transform: translateY(-2px);
      }
      
      &.success {
        border-left: 4px solid var(--q-positive);
        
        .stat-icon {
          color: var(--q-positive);
        }
      }
      
      &.error {
        border-left: 4px solid var(--q-negative);
        
        .stat-icon {
          color: var(--q-negative);
        }
      }
      
      &.pending {
        border-left: 4px solid var(--q-warning);
        
        .stat-icon {
          color: var(--q-warning);
        }
      }
      
      &.rate {
        border-left: 4px solid var(--q-info);
        
        .stat-icon {
          color: var(--q-info);
        }
      }
      
      .stat-header {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
        
        .stat-icon {
          font-size: 18px;
        }
        
        .stat-label {
          font-size: 12px;
          color: var(--q-grey-6);
          text-transform: uppercase;
          letter-spacing: 0.5px;
        }
      }
      
      .stat-value {
        font-size: 24px;
        font-weight: 700;
        color: var(--q-dark);
        margin-bottom: 4px;
      }
      
      .stat-percentage {
        font-size: 12px;
        color: var(--q-grey-6);
      }
    }
  }
  
  .batch-progress {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    border: 1px solid var(--q-grey-4);
    
    .batch-header {
      display: flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      margin-bottom: 16px;
      
      .batch-icon {
        color: var(--q-primary);
        font-size: 18px;
      }
      
      .batch-label {
        font-weight: 600;
        color: var(--q-dark);
        flex: 1;
      }
      
      .toggle-btn {
        color: var(--q-grey-6);
      }
    }
    
    .batch-list {
      .batch-item {
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 8px;
        border-left: 4px solid var(--q-grey-4);
        
        &.pending {
          border-left-color: var(--q-warning);
          background: rgba(255, 193, 7, 0.05);
        }
        
        &.sending {
          border-left-color: var(--q-primary);
          background: rgba(var(--q-primary-rgb), 0.05);
        }
        
        &.completed {
          border-left-color: var(--q-positive);
          background: rgba(76, 175, 80, 0.05);
        }
        
        &.failed {
          border-left-color: var(--q-negative);
          background: rgba(244, 67, 54, 0.05);
        }
        
        .batch-info {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 8px;
          
          .batch-number {
            font-weight: 600;
            color: var(--q-dark);
          }
          
          .batch-status {
            font-size: 12px;
            color: var(--q-grey-6);
            text-transform: uppercase;
          }
        }
        
        .batch-details {
          .batch-progress-bar {
            margin-bottom: 8px;
            border-radius: 3px;
          }
          
          .batch-stats {
            display: flex;
            gap: 12px;
            
            .batch-stat {
              font-size: 11px;
              color: var(--q-grey-6);
              
              &.error {
                color: var(--q-negative);
              }
            }
          }
        }
      }
    }
  }
  
  .error-log {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    border: 1px solid var(--q-negative);
    
    .error-header {
      display: flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      margin-bottom: 16px;
      
      .error-icon {
        color: var(--q-negative);
        font-size: 18px;
      }
      
      .error-label {
        font-weight: 600;
        color: var(--q-negative);
        flex: 1;
      }
      
      .toggle-btn {
        color: var(--q-grey-6);
      }
    }
    
    .error-list {
      .error-item {
        padding: 8px 12px;
        border-radius: 6px;
        margin-bottom: 8px;
        background: rgba(244, 67, 54, 0.05);
        border-left: 3px solid var(--q-negative);
        
        .error-time {
          font-size: 10px;
          color: var(--q-grey-6);
          margin-bottom: 4px;
        }
        
        .error-message {
          font-size: 12px;
          color: var(--q-negative);
          margin-bottom: 2px;
        }
        
        .error-phone {
          font-size: 11px;
          color: var(--q-grey-6);
          font-family: monospace;
        }
      }
      
      .more-errors {
        text-align: center;
        margin-top: 12px;
      }
    }
  }
  
  .control-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
    margin-bottom: 20px;
    
    .control-btn {
      min-width: 120px;
    }
  }
  
  .completion-message {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    border: 1px solid var(--q-positive);
    
    &.has-errors {
      border-color: var(--q-warning);
    }
    
    .completion-content {
      display: flex;
      align-items: center;
      gap: 16px;
      text-align: left;
      
      .completion-icon {
        flex-shrink: 0;
      }
      
      .completion-text {
        .completion-title {
          margin: 0 0 8px 0;
          color: var(--q-dark);
          font-weight: 600;
        }
        
        .completion-summary {
          margin: 0;
          color: var(--q-grey-6);
          line-height: 1.4;
        }
      }
    }
  }
}

// Responsive design
@media (max-width: 768px) {
  .send-progress {
    .stats-grid {
      grid-template-columns: repeat(2, 1fr);
    }
    
    .global-progress .progress-details {
      flex-direction: column;
      gap: 4px;
    }
    
    .control-actions {
      flex-direction: column;
      
      .control-btn {
        min-width: 0;
      }
    }
    
    .completion-message .completion-content {
      flex-direction: column;
      text-align: center;
    }
  }
}
</style>