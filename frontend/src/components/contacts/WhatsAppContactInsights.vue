<template>
  <div class="whatsapp-insights">
    <div class="insights-header">
      <div class="header-content">
        <q-icon name="whatsapp" size="md" class="header-icon" />
        <div class="header-text">
          <h3 class="header-title">Insights WhatsApp</h3>
          <p class="header-subtitle">Statistiques et métriques de communication</p>
        </div>
      </div>
      <div class="header-actions">
        <q-btn
          icon="refresh"
          round
          flat
          @click="refreshInsights"
          :loading="loading"
          class="refresh-btn"
        >
          <q-tooltip>Actualiser</q-tooltip>
        </q-btn>
      </div>
    </div>

    <div v-if="loading && !insights" class="loading-state">
      <q-skeleton height="120px" class="q-mb-md" />
      <q-skeleton height="200px" class="q-mb-md" />
      <q-skeleton height="150px" />
    </div>

    <div v-else-if="error" class="error-state">
      <q-banner class="text-white bg-negative">
        <template v-slot:avatar>
          <q-icon name="error" color="white" />
        </template>
        <strong>Erreur de chargement</strong><br>
        {{ error }}
        <template v-slot:action>
          <q-btn
            flat
            color="white"
            label="Réessayer"
            @click="refreshInsights"
          />
        </template>
      </q-banner>
    </div>

    <div v-else-if="!insights || insights.totalMessages === 0" class="empty-state">
      <div class="empty-content">
        <q-icon name="chat_bubble_outline" size="4rem" color="grey-5" />
        <h4 class="empty-title">Aucun message WhatsApp</h4>
        <p class="empty-text">
          Aucune conversation WhatsApp trouvée avec ce contact.
        </p>
        <q-btn
          color="primary"
          icon="send"
          label="Envoyer un message"
          @click="$emit('send-whatsapp')"
          class="empty-action-btn"
        />
      </div>
    </div>

    <div v-else class="insights-content">
      <!-- Métriques principales -->
      <div class="metrics-grid">
        <div class="metric-card">
          <div class="metric-icon">
            <q-icon name="chat" color="primary" />
          </div>
          <div class="metric-content">
            <div class="metric-value">{{ insights.totalMessages }}</div>
            <div class="metric-label">Total messages</div>
          </div>
        </div>

        <div class="metric-card">
          <div class="metric-icon">
            <q-icon name="north_east" color="positive" />
          </div>
          <div class="metric-content">
            <div class="metric-value">{{ insights.outgoingMessages }}</div>
            <div class="metric-label">Envoyés</div>
          </div>
        </div>

        <div class="metric-card">
          <div class="metric-icon">
            <q-icon name="south_west" color="info" />
          </div>
          <div class="metric-content">
            <div class="metric-value">{{ insights.incomingMessages }}</div>
            <div class="metric-label">Reçus</div>
          </div>
        </div>

        <div class="metric-card">
          <div class="metric-icon">
            <q-icon name="done_all" color="positive" />
          </div>
          <div class="metric-content">
            <div class="metric-value">{{ insights.deliveryRate }}%</div>
            <div class="metric-label">Taux livraison</div>
          </div>
        </div>
      </div>

      <!-- Statuts des messages -->
      <div class="status-section">
        <h4 class="section-title">Statuts des messages</h4>
        <div class="status-grid">
          <div class="status-item">
            <q-icon name="send" color="blue" />
            <span class="status-label">Envoyés</span>
            <span class="status-value">{{ getStatusCount('sent') }}</span>
          </div>
          <div class="status-item">
            <q-icon name="done" color="green" />
            <span class="status-label">Livrés</span>
            <span class="status-value">{{ insights.deliveredMessages }}</span>
          </div>
          <div class="status-item">
            <q-icon name="done_all" color="primary" />
            <span class="status-label">Lus</span>
            <span class="status-value">{{ insights.readMessages }}</span>
          </div>
          <div class="status-item">
            <q-icon name="error" color="negative" />
            <span class="status-label">Échoués</span>
            <span class="status-value">{{ insights.failedMessages }}</span>
          </div>
        </div>
      </div>

      <!-- Types de messages -->
      <div class="types-section">
        <h4 class="section-title">Types de messages</h4>
        <div class="types-list">
          <div 
            v-for="(count, type) in insights.messagesByType" 
            :key="type"
            class="type-item"
          >
            <q-chip
              :color="getTypeColor(type)"
              text-color="white"
              :icon="getTypeIcon(type)"
              :label="`${getTypeLabel(type)}: ${count}`"
            />
          </div>
        </div>
      </div>

      <!-- Templates utilisés -->
      <div v-if="insights.templatesUsed.length > 0" class="templates-section">
        <h4 class="section-title">Templates utilisés</h4>
        <div class="templates-list">
          <q-chip
            v-for="template in insights.templatesUsed"
            :key="template"
            color="info"
            text-color="white"
            icon="description"
            :label="template"
            class="template-chip"
          />
        </div>
      </div>

      <!-- Dernier message -->
      <div v-if="insights.lastMessageDate" class="last-message-section">
        <h4 class="section-title">Dernier message</h4>
        <div class="last-message-card">
          <div class="message-meta">
            <q-chip
              :color="getTypeColor(insights.lastMessageType || 'text')"
              text-color="white"
              :icon="getTypeIcon(insights.lastMessageType || 'text')"
              :label="getTypeLabel(insights.lastMessageType || 'text')"
              size="sm"
            />
            <span class="message-date">{{ formatDate(insights.lastMessageDate) }}</span>
          </div>
          <p v-if="insights.lastMessageContent" class="message-content">
            {{ insights.lastMessageContent }}
          </p>
        </div>
      </div>

      <!-- Actions -->
      <div class="actions-section">
        <q-btn
          color="primary"
          icon="chat"
          label="Voir historique complet"
          @click="$emit('view-history')"
          class="action-btn"
        />
        <q-btn
          color="positive"
          icon="send"
          label="Envoyer message"
          @click="$emit('send-whatsapp')"
          class="action-btn"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useContactStore } from '../../stores/contactStore'
import type { WhatsAppContactInsights } from '../../types/whatsapp-insights'

interface Props {
  contactId: string
  autoLoad?: boolean
}

interface Emits {
  (e: 'send-whatsapp'): void
  (e: 'view-history'): void
}

const props = withDefaults(defineProps<Props>(), {
  autoLoad: true
})

const emit = defineEmits<Emits>()
const contactStore = useContactStore()

const loading = computed(() => contactStore.whatsappLoading)
const error = computed(() => contactStore.whatsappError)
const insights = computed(() => contactStore.getContactWhatsAppInsights(props.contactId))

// Méthodes
async function refreshInsights() {
  await contactStore.fetchContactWhatsAppInsights(props.contactId)
}

function getStatusCount(status: string): number {
  return insights.value?.messagesByStatus[status] || 0
}

function getTypeColor(type: string): string {
  const colors: Record<string, string> = {
    text: 'blue',
    template: 'purple',
    image: 'green',
    video: 'red',
    audio: 'orange',
    document: 'brown',
    interactive: 'pink'
  }
  return colors[type] || 'grey'
}

function getTypeIcon(type: string): string {
  const icons: Record<string, string> = {
    text: 'chat',
    template: 'description',
    image: 'image',
    video: 'videocam',
    audio: 'mic',
    document: 'description',
    interactive: 'touch_app'
  }
  return icons[type] || 'chat'
}

function getTypeLabel(type: string): string {
  const labels: Record<string, string> = {
    text: 'Texte',
    template: 'Template',
    image: 'Image',
    video: 'Vidéo',
    audio: 'Audio',
    document: 'Document',
    interactive: 'Interactif'
  }
  return labels[type] || type
}

function formatDate(dateString: string): string {
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = Math.abs(now.getTime() - date.getTime())
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))

  if (diffDays === 1) {
    return 'Hier à ' + date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
  } else if (diffDays < 7) {
    return `Il y a ${diffDays} jours`
  } else {
    return date.toLocaleDateString('fr-FR', { 
      day: 'numeric', 
      month: 'short', 
      year: 'numeric' 
    })
  }
}

// Lifecycle
onMounted(() => {
  if (props.autoLoad) {
    refreshInsights()
  }
})

// Watcher pour recharger les insights si le contact change
watch(() => props.contactId, () => {
  if (props.autoLoad) {
    refreshInsights()
  }
})
</script>

<style lang="scss" scoped>
$whatsapp-green: #25d366;
$whatsapp-dark: #128c7e;

.whatsapp-insights {
  .insights-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid $whatsapp-green;
    
    .header-content {
      display: flex;
      align-items: center;
      gap: 1rem;
      
      .header-icon {
        color: $whatsapp-green;
      }
      
      .header-text {
        .header-title {
          margin: 0 0 0.25rem 0;
          font-size: 1.25rem;
          font-weight: 600;
          color: var(--q-dark);
        }
        
        .header-subtitle {
          margin: 0;
          font-size: 0.9rem;
          color: var(--q-grey-7);
        }
      }
    }
    
    .refresh-btn {
      color: $whatsapp-green;
    }
  }
  
  .loading-state, .error-state {
    margin: 2rem 0;
  }
  
  .empty-state {
    text-align: center;
    padding: 3rem 2rem;
    
    .empty-content {
      max-width: 400px;
      margin: 0 auto;
    }
    
    .empty-title {
      font-size: 1.25rem;
      font-weight: 600;
      margin: 1rem 0 0.5rem 0;
      color: var(--q-grey-8);
    }
    
    .empty-text {
      color: var(--q-grey-6);
      margin-bottom: 2rem;
      line-height: 1.6;
    }
    
    .empty-action-btn {
      background: linear-gradient(135deg, $whatsapp-green 0%, $whatsapp-dark 100%);
      color: white;
      border-radius: 8px;
      padding: 0.75rem 2rem;
    }
  }
  
  .insights-content {
    .metrics-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
      margin-bottom: 2rem;
      
      .metric-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        gap: 1rem;
        
        &:hover {
          box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
          transform: translateY(-2px);
        }
        
        .metric-icon {
          background: rgba(var(--q-primary-rgb), 0.1);
          border-radius: 50%;
          width: 48px;
          height: 48px;
          display: flex;
          align-items: center;
          justify-content: center;
          
          .q-icon {
            font-size: 24px;
          }
        }
        
        .metric-content {
          .metric-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--q-dark);
            line-height: 1;
          }
          
          .metric-label {
            font-size: 0.85rem;
            color: var(--q-grey-6);
            margin-top: 0.25rem;
          }
        }
      }
    }
    
    .status-section, .types-section, .templates-section, .last-message-section {
      background: white;
      border-radius: 12px;
      padding: 1.5rem;
      margin-bottom: 1.5rem;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
      
      .section-title {
        margin: 0 0 1rem 0;
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--q-dark);
      }
    }
    
    .status-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 1rem;
      
      .status-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem;
        background: var(--q-grey-1);
        border-radius: 8px;
        
        .status-label {
          flex: 1;
          font-size: 0.9rem;
          color: var(--q-grey-7);
        }
        
        .status-value {
          font-weight: 600;
          color: var(--q-dark);
        }
      }
    }
    
    .types-list, .templates-list {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
      
      .q-chip {
        margin: 0;
      }
    }
    
    .last-message-card {
      .message-meta {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 0.75rem;
        
        .message-date {
          font-size: 0.85rem;
          color: var(--q-grey-6);
        }
      }
      
      .message-content {
        background: var(--q-grey-1);
        padding: 1rem;
        border-radius: 8px;
        margin: 0;
        font-style: italic;
        color: var(--q-grey-8);
        line-height: 1.5;
      }
    }
    
    .actions-section {
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
      justify-content: center;
      margin-top: 2rem;
      
      .action-btn {
        border-radius: 8px;
        font-weight: 500;
        padding: 0.75rem 1.5rem;
      }
    }
  }
}

// Responsive
@media (max-width: 768px) {
  .whatsapp-insights {
    .insights-header {
      flex-direction: column;
      gap: 1rem;
      text-align: center;
    }
    
    .metrics-grid {
      grid-template-columns: 1fr 1fr;
    }
    
    .status-grid {
      grid-template-columns: 1fr;
    }
    
    .actions-section {
      .action-btn {
        flex: 1;
      }
    }
  }
}

@media (max-width: 480px) {
  .whatsapp-insights {
    .metrics-grid {
      grid-template-columns: 1fr;
    }
  }
}
</style>