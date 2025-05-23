<template>
  <q-page padding>
    <div class="q-pa-md">
      <div class="row items-center q-mb-md">
        <h1 class="text-h4 q-my-none">WhatsApp</h1>
        <q-space />
        <div v-if="userStore.currentUser" class="row items-center">
          <!-- Badge contacts WhatsApp (utilise les mêmes contacts) -->
          <ContactCountBadge
            :count="contactsCount"
            color="green"
            icon="chat"
            tooltipText="Nombre total de contacts disponibles pour WhatsApp."
          />
        </div>
      </div>

      <q-tabs
        v-model="activeTab"
        class="text-primary q-mb-md"
        indicator-color="primary"
        align="left"
      >
        <q-tab name="send" label="Envoyer" icon="send" />
        <q-tab name="media" label="Médias" icon="attachment" />
        <q-tab name="messages" label="Messages" icon="chat" />
        <q-tab name="templates" label="Templates" icon="history" />
      </q-tabs>

      <q-tab-panels v-model="activeTab" animated>
        <!-- Onglet Envoi de message -->
        <q-tab-panel name="send">
          <div class="row q-col-gutter-md">
            <div class="col-12">
              <!-- Étape 1: Sélection du destinataire -->
              <div v-if="currentStep === 'recipient'">
                <div class="text-h6 q-mb-md">Sélection du destinataire</div>
                <WhatsAppSendMessage 
                  @message-sent="onMessageSent" 
                  @recipient-selected="onRecipientSelected"
                />
              </div>

              <!-- Étape 2: Sélection du template -->
              <div v-else-if="currentStep === 'template'" class="q-mt-md">
                <div class="row items-center q-mb-md">
                  <div class="text-h6 q-my-none">Sélection du template</div>
                  <q-space />
                  <q-btn outline color="primary" icon="arrow_back" label="Changer de destinataire" @click="currentStep = 'recipient'" />
                </div>
                
                <q-banner class="bg-grey-2 q-mb-md">
                  <template v-slot:avatar>
                    <q-icon name="info" color="primary" />
                  </template>
                  <div class="text-body1">
                    Destinataire: <strong>{{ selectedRecipient }}</strong>
                  </div>
                </q-banner>
                
                <WhatsAppTemplateSelector 
                  :recipientPhoneNumber="selectedRecipient" 
                  @template-selected="onTemplateSelected"
                  @cancel="currentStep = 'recipient'"
                />
              </div>

              <!-- Étape 3: Personnalisation du message -->
              <div v-else-if="currentStep === 'customize'" class="q-mt-md">
                <WhatsAppMessageComposer 
                  :templateData="selectedTemplateData" 
                  :recipientPhoneNumber="selectedRecipient"
                  @change-template="currentStep = 'template'"
                  @cancel="currentStep = 'recipient'"
                  @message-sent="onTemplateSent"
                />
              </div>

              <!-- Étape 4: Message envoyé avec succès -->
              <div v-else-if="currentStep === 'success'" class="q-mt-md">
                <q-card class="bg-green-1">
                  <q-card-section>
                    <div class="row items-center">
                      <q-icon name="check_circle" color="positive" size="3rem" class="q-mr-md" />
                      <div>
                        <div class="text-h6 text-positive">Message envoyé avec succès</div>
                        <div class="text-subtitle1 q-mt-sm">
                          Le message a été envoyé à <strong>{{ selectedRecipient }}</strong> 
                          via WhatsApp Business API.
                        </div>
                      </div>
                    </div>
                  </q-card-section>
                  <q-card-actions align="right">
                    <q-btn flat color="primary" label="Envoyer un autre message" @click="resetForm" />
                    <q-btn color="primary" label="Voir l'historique des messages" @click="goToMessages" />
                  </q-card-actions>
                </q-card>
              </div>
            </div>
          </div>

          <!-- Statistiques et infos récentes -->
          <div class="stats-section q-mt-xl">
            <div class="section-header q-mb-lg">
              <h2 class="section-title">
                <q-icon name="analytics" class="q-mr-sm" />
                Statistiques et infos récentes
              </h2>
              <p class="section-subtitle">
                Aperçu de votre activité WhatsApp en temps réel
              </p>
            </div>

            <div class="row q-col-gutter-lg">
              <!-- Carte du dernier message envoyé -->
              <div class="col-12 col-lg-4">
                <q-card class="modern-stats-card last-sent-card" v-if="lastSentMessage">
                  <div class="card-gradient-header sent-header">
                    <q-icon name="send" size="md" class="header-icon" />
                    <div class="header-content">
                      <h3 class="card-title">Dernier message envoyé</h3>
                      <p class="card-subtitle">{{ formatRelativeTime(lastSentMessage.createdAt) }}</p>
                    </div>
                  </div>
                  
                  <q-card-section class="card-content">
                    <div class="message-info">
                      <div class="info-item">
                        <q-icon name="person" class="info-icon" />
                        <div class="info-content">
                          <span class="info-label">Destinataire</span>
                          <span class="info-value">{{ formatPhoneNumber(lastSentMessage.phoneNumber) }}</span>
                        </div>
                      </div>
                      
                      <div class="info-item">
                        <q-icon name="label" class="info-icon" />
                        <div class="info-content">
                          <span class="info-label">Type</span>
                          <q-chip 
                            :color="getTypeColor(lastSentMessage.type)" 
                            text-color="white" 
                            size="sm"
                            class="type-chip"
                          >
                            {{ getTypeLabel(lastSentMessage.type) }}
                          </q-chip>
                        </div>
                      </div>
                      
                      <div class="info-item">
                        <q-icon name="check_circle" class="info-icon" />
                        <div class="info-content">
                          <span class="info-label">Statut</span>
                          <q-badge 
                            :color="getStatusColor(lastSentMessage.status)"
                            :label="getStatusLabel(lastSentMessage.status)"
                            class="status-badge"
                          />
                        </div>
                      </div>
                      
                      <div class="info-item" v-if="lastSentMessage.content">
                        <q-icon name="message" class="info-icon" />
                        <div class="info-content">
                          <span class="info-label">Message</span>
                          <span class="info-value message-preview">{{ truncateMessage(lastSentMessage.content) }}</span>
                        </div>
                      </div>
                    </div>
                  </q-card-section>
                </q-card>
                
                <!-- Carte vide si aucun message envoyé -->
                <q-card class="modern-stats-card empty-state-card" v-else>
                  <q-card-section class="text-center q-pa-xl">
                    <q-icon name="send" size="4rem" color="grey-4" class="q-mb-md" />
                    <h4 class="text-grey-6 q-ma-none">Aucun message envoyé</h4>
                    <p class="text-grey-5 q-mt-sm q-mb-none">
                      Vos messages envoyés apparaîtront ici
                    </p>
                  </q-card-section>
                </q-card>
              </div>

              <!-- Carte du dernier message reçu -->
              <div class="col-12 col-lg-4">
                <q-card class="modern-stats-card last-received-card" v-if="lastReceivedMessage">
                  <div class="card-gradient-header received-header">
                    <q-icon name="mail" size="md" class="header-icon" />
                    <div class="header-content">
                      <h3 class="card-title">Dernier message reçu</h3>
                      <p class="card-subtitle">{{ formatRelativeTime(lastReceivedMessage.createdAt) }}</p>
                    </div>
                  </div>
                  
                  <q-card-section class="card-content">
                    <div class="message-info">
                      <div class="info-item">
                        <q-icon name="person" class="info-icon" />
                        <div class="info-content">
                          <span class="info-label">Expéditeur</span>
                          <span class="info-value">{{ formatPhoneNumber(lastReceivedMessage.phoneNumber) }}</span>
                        </div>
                      </div>
                      
                      <div class="info-item">
                        <q-icon name="label" class="info-icon" />
                        <div class="info-content">
                          <span class="info-label">Type</span>
                          <q-chip 
                            :color="getTypeColor(lastReceivedMessage.type)" 
                            text-color="white" 
                            size="sm"
                            class="type-chip"
                          >
                            {{ getTypeLabel(lastReceivedMessage.type) }}
                          </q-chip>
                        </div>
                      </div>
                      
                      <div class="info-item" v-if="lastReceivedMessage.content">
                        <q-icon name="message" class="info-icon" />
                        <div class="info-content">
                          <span class="info-label">Message</span>
                          <span class="info-value message-preview">{{ truncateMessage(lastReceivedMessage.content) }}</span>
                        </div>
                      </div>
                      
                      <div class="info-item">
                        <q-icon name="schedule" class="info-icon" />
                        <div class="info-content">
                          <span class="info-label">Date</span>
                          <span class="info-value">{{ formatDate(lastReceivedMessage.createdAt) }}</span>
                        </div>
                      </div>
                    </div>
                  </q-card-section>
                </q-card>
                
                <!-- Carte vide si aucun message reçu -->
                <q-card class="modern-stats-card empty-state-card" v-else>
                  <q-card-section class="text-center q-pa-xl">
                    <q-icon name="mail" size="4rem" color="grey-4" class="q-mb-md" />
                    <h4 class="text-grey-6 q-ma-none">Aucun message reçu</h4>
                    <p class="text-grey-5 q-mt-sm q-mb-none">
                      Les messages reçus apparaîtront ici
                    </p>
                  </q-card-section>
                </q-card>
              </div>

              <!-- Carte des statistiques du jour -->
              <div class="col-12 col-lg-4">
                <q-card class="modern-stats-card stats-overview-card">
                  <div class="card-gradient-header stats-header">
                    <q-icon name="analytics" size="md" class="header-icon" />
                    <div class="header-content">
                      <h3 class="card-title">Statistiques du jour</h3>
                      <p class="card-subtitle">{{ formatToday() }}</p>
                    </div>
                  </div>
                  
                  <q-card-section class="card-content stats-content">
                    <div class="stats-grid">
                      <div class="stat-item">
                        <div class="stat-icon-wrapper sent-stat">
                          <q-icon name="send" class="stat-icon" />
                        </div>
                        <div class="stat-details">
                          <span class="stat-value">{{ stats.totalMessages }}</span>
                          <span class="stat-label">Envoyés</span>
                        </div>
                      </div>
                      
                      <div class="stat-item">
                        <div class="stat-icon-wrapper delivered-stat">
                          <q-icon name="done_all" class="stat-icon" />
                        </div>
                        <div class="stat-details">
                          <span class="stat-value">{{ stats.deliveredMessages }}</span>
                          <span class="stat-label">Délivrés</span>
                        </div>
                      </div>
                      
                      <div class="stat-item">
                        <div class="stat-icon-wrapper read-stat">
                          <q-icon name="visibility" class="stat-icon" />
                        </div>
                        <div class="stat-details">
                          <span class="stat-value">{{ stats.readMessages }}</span>
                          <span class="stat-label">Lus</span>
                        </div>
                      </div>
                      
                      <div class="stat-item">
                        <div class="stat-icon-wrapper received-stat">
                          <q-icon name="inbox" class="stat-icon" />
                        </div>
                        <div class="stat-details">
                          <span class="stat-value">{{ stats.receivedMessages }}</span>
                          <span class="stat-label">Reçus</span>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Indicateur de performance -->
                    <div class="performance-indicator q-mt-md">
                      <div class="performance-header">
                        <span class="performance-label">Taux de livraison</span>
                        <span class="performance-value">{{ deliveryRate }}%</span>
                      </div>
                      <q-linear-progress 
                        :value="deliveryRate / 100" 
                        color="positive" 
                        size="8px" 
                        rounded 
                        class="q-mt-xs"
                      />
                    </div>
                  </q-card-section>
                </q-card>
              </div>
            </div>
          </div>
        </q-tab-panel>

        <!-- Onglet Médias -->
        <q-tab-panel name="media">
          <div class="row q-col-gutter-md">
            <div class="col-12 col-md-8">
              <WhatsAppMediaUpload />
            </div>
          </div>
        </q-tab-panel>

        <!-- Onglet Messages -->
        <q-tab-panel name="messages">
          <WhatsAppMessageList />
        </q-tab-panel>
        
        <!-- Onglet Templates -->
        <q-tab-panel name="templates">
          <div class="template-redirect-panel">
            <q-card class="modern-redirect-card">
              <q-card-section class="redirect-header">
                <div class="header-content">
                  <div class="header-icon-wrapper">
                    <q-icon name="dashboard_customize" size="md" />
                  </div>
                  <div class="header-text">
                    <h3 class="card-title">Templates WhatsApp</h3>
                    <p class="card-subtitle">Gérez vos templates de messages professionnels</p>
                  </div>
                </div>
              </q-card-section>

              <q-card-section class="redirect-content">
                <q-banner class="template-info-banner">
                  <template v-slot:avatar>
                    <q-icon name="info" color="purple" />
                  </template>
                  <div class="text-body2">
                    <strong>Interface dédiée aux templates</strong><br>
                    Modifiez et utilisez vos templates WhatsApp Business dans une interface spécialisée.
                  </div>
                </q-banner>

                <div class="features-list">
                  <div class="feature-item">
                    <q-icon name="visibility" color="green" class="feature-icon" />
                    <div class="feature-text">
                      <h4>Visualiser tous vos templates</h4>
                      <p>Parcourez l'ensemble de vos templates approuvés</p>
                    </div>
                  </div>
                  
                  <div class="feature-item">
                    <q-icon name="send" color="green" class="feature-icon" />
                    <div class="feature-text">
                      <h4>Envoi rapide</h4>
                      <p>Envoyez directement vos templates avec personnalisation</p>
                    </div>
                  </div>
                  
                  <div class="feature-item">
                    <q-icon name="tune" color="green" class="feature-icon" />
                    <div class="feature-text">
                      <h4>Paramètres avancés</h4>
                      <p>Configurez les variables et options de vos templates</p>
                    </div>
                  </div>
                </div>

                <div class="redirect-actions">
                  <q-btn 
                    class="action-btn primary-btn"
                    color="purple"
                    icon="dashboard_customize"
                    label="Accéder aux Templates"
                    size="lg"
                    @click="goToTemplatesPage"
                  />
                </div>
              </q-card-section>
            </q-card>
          </div>
        </q-tab-panel>
      </q-tab-panels>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useUserStore } from '@/stores/userStore';
import { useContactStore } from '@/stores/contactStore';
import { useWhatsAppStore } from '@/stores/whatsappStore';
import { useQuasar } from 'quasar';
import ContactCountBadge from '@/components/common/ContactCountBadge.vue';
import WhatsAppSendMessage from '@/components/whatsapp/WhatsAppSendMessage.vue';
import WhatsAppMessageList from '@/components/whatsapp/WhatsAppMessageListServerPaginated.vue';
import WhatsAppMediaUpload from '@/components/whatsapp/WhatsAppMediaUpload.vue';
import WhatsAppTemplateSelector from '@/components/whatsapp/WhatsAppTemplateSelector.vue';
import WhatsAppMessageComposer from '@/components/whatsapp/WhatsAppMessageComposer.vue';

// Stores and utilities
const userStore = useUserStore();
const contactStore = useContactStore();
const whatsAppStore = useWhatsAppStore();
const route = useRoute();
const router = useRouter();
const $q = useQuasar();

// État local
const activeTab = ref('send');
const contactsCount = ref(0);
const currentStep = ref('recipient'); // 'recipient', 'template', 'customize', 'success'
const selectedRecipient = ref('');
const selectedTemplateData = ref(null);
const lastSentTemplateMessage = ref(null);

// Computed properties
const lastSentMessage = computed(() => {
  const messages = whatsAppStore.messages
    .filter(msg => msg.direction === 'OUTGOING')
    .sort((a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime());
  return messages[0] || null;
});

const lastReceivedMessage = computed(() => {
  const messages = whatsAppStore.messages
    .filter(msg => msg.direction === 'INCOMING')
    .sort((a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime());
  return messages[0] || null;
});

const stats = computed(() => {
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  
  const todayMessages = whatsAppStore.messages.filter(msg => {
    const msgDate = new Date(msg.createdAt);
    msgDate.setHours(0, 0, 0, 0);
    return msgDate.getTime() === today.getTime();
  });

  const outgoingMessages = todayMessages.filter(msg => msg.direction === 'OUTGOING');
  const incomingMessages = todayMessages.filter(msg => msg.direction === 'INCOMING');

  return {
    totalMessages: outgoingMessages.length,
    deliveredMessages: outgoingMessages.filter(msg => msg.status === 'delivered' || msg.status === 'read').length,
    readMessages: outgoingMessages.filter(msg => msg.status === 'read').length,
    receivedMessages: incomingMessages.length,
    conversationsActive: new Set(todayMessages.map(msg => msg.phoneNumber)).size
  };
});

// Computed pour le taux de livraison
const deliveryRate = computed(() => {
  if (stats.value.totalMessages === 0) return 0;
  return Math.round((stats.value.deliveredMessages / stats.value.totalMessages) * 100);
});

// Helper functions
function getStatusColor(status: string) {
  switch (status?.toLowerCase()) {
    case 'sent':
      return 'blue';
    case 'delivered':
      return 'positive';
    case 'read':
      return 'info';
    case 'failed':
      return 'negative';
    default:
      return 'grey';
  }
}

function formatDate(date: string | Date) {
  return new Date(date).toLocaleString('fr-FR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

// Nouvelles fonctions helper pour l'interface modernisée
function formatRelativeTime(date: string | Date) {
  const now = new Date();
  const messageDate = new Date(date);
  const diffInMinutes = Math.floor((now.getTime() - messageDate.getTime()) / (1000 * 60));
  
  if (diffInMinutes < 1) return 'À l\'instant';
  if (diffInMinutes < 60) return `Il y a ${diffInMinutes} min`;
  
  const diffInHours = Math.floor(diffInMinutes / 60);
  if (diffInHours < 24) return `Il y a ${diffInHours}h`;
  
  const diffInDays = Math.floor(diffInHours / 24);
  if (diffInDays === 1) return 'Hier';
  if (diffInDays < 7) return `Il y a ${diffInDays} jours`;
  
  return formatDate(date);
}

function formatPhoneNumber(phoneNumber: string) {
  // Formatter le numéro pour l'affichage (+XXX XX XX XX XX)
  if (!phoneNumber) return '';
  
  let cleaned = phoneNumber.replace(/\D/g, '');
  if (cleaned.startsWith('225')) {
    // Format Côte d'Ivoire: +225 XX XX XX XX
    return `+225 ${cleaned.slice(3, 5)} ${cleaned.slice(5, 7)} ${cleaned.slice(7, 9)} ${cleaned.slice(9)}`;
  }
  
  // Format générique: +XXX XXXX XXXX
  return `+${cleaned.slice(0, 3)} ${cleaned.slice(3, 7)} ${cleaned.slice(7)}`;
}

function getTypeColor(type: string) {
  switch (type?.toLowerCase()) {
    case 'text': return 'blue';
    case 'template': return 'purple';
    case 'image': return 'pink';
    case 'video': return 'orange';
    case 'document': return 'teal';
    case 'audio': return 'green';
    default: return 'grey';
  }
}

function getTypeLabel(type: string) {
  switch (type?.toLowerCase()) {
    case 'text': return 'Texte';
    case 'template': return 'Template';
    case 'image': return 'Image';
    case 'video': return 'Vidéo';
    case 'document': return 'Document';
    case 'audio': return 'Audio';
    default: return type || 'Inconnu';
  }
}

function getStatusLabel(status: string) {
  switch (status?.toLowerCase()) {
    case 'sent': return 'Envoyé';
    case 'delivered': return 'Délivré';
    case 'read': return 'Lu';
    case 'failed': return 'Échoué';
    default: return status || 'Inconnu';
  }
}

function truncateMessage(message: string, maxLength: number = 80) {
  if (!message) return '';
  return message.length > maxLength ? message.slice(0, maxLength) + '...' : message;
}

function formatToday() {
  return new Date().toLocaleDateString('fr-FR', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
}

// Event handlers
const onRecipientSelected = (recipientInfo: { phoneNumber: string }) => {
  selectedRecipient.value = recipientInfo.phoneNumber;
  currentStep.value = 'template';
};

const onTemplateSelected = (templateData: any) => {
  selectedTemplateData.value = templateData;
  currentStep.value = 'customize';
};

const onMessageSent = (messageData: any) => {
  // Notification pour l'envoi réussi d'un message standard
  $q.notify({
    type: 'positive',
    message: `Message envoyé à ${messageData.phoneNumber}`,
    position: 'top'
  });
  
  // Rafraîchir les statistiques et l'historique des messages
  whatsAppStore.fetchMessages();
};

const onTemplateSent = (result: any) => {
  if (result.success) {
    lastSentTemplateMessage.value = {
      messageId: result.messageId,
      templateName: selectedTemplateData.value.template.name,
      recipient: selectedRecipient.value,
      timestamp: result.timestamp || new Date().toISOString()
    };
    
    // Passer à l'étape de succès
    currentStep.value = 'success';
    
    // Rafraîchir les statistiques et l'historique des messages
    whatsAppStore.fetchMessages();
  } else {
    // Notification d'erreur
    $q.notify({
      type: 'negative',
      message: `Erreur: ${result.error || 'Impossible d\'envoyer le message'}`,
      position: 'top'
    });
  }
};

// Réinitialiser le formulaire pour un nouvel envoi
const resetForm = () => {
  selectedRecipient.value = '';
  selectedTemplateData.value = null;
  lastSentTemplateMessage.value = null;
  currentStep.value = 'recipient';
};

// Aller à l'onglet des messages
const goToMessages = () => {
  activeTab.value = 'messages';
};

// Aller à la page des templates
const goToTemplatesPage = () => {
  router.push('/whatsapp-templates');
};

// Fonction pour rafraîchir le nombre de contacts
const refreshContactsCount = async () => {
  contactsCount.value = await contactStore.fetchContactsCount();
};

// Process URL parameters
function processRouteParams() {
  if (route.query.recipient) {
    // Si on a un destinataire dans l'URL, on le pré-remplit
    selectedRecipient.value = route.query.recipient as string;
    currentStep.value = 'template';
    activeTab.value = 'send';
  }
  
  if (route.query.tab) {
    activeTab.value = route.query.tab as string;
  }
}

// Watch for route changes to update form with URL parameters
watch(() => route.query, () => {
  processRouteParams();
}, { deep: true });

// Watch for tab changes to load appropriate data
watch(activeTab, async (newTab) => {
  // L'onglet templates n'a plus besoin de charger de données pour le moment
  // car le composant WhatsAppTemplateHistoryList a été supprimé
});

// Watch for successful message send
whatsAppStore.$subscribe((mutation) => {
  // Refresh stats when messages change
  if (mutation.type === 'direct') {
    refreshContactsCount();
  }
});

// Initialisation
onMounted(async () => {
  // Charger les données initiales
  await refreshContactsCount();
  await whatsAppStore.fetchMessages();
  
  // L'onglet templates n'a plus besoin de charger de données pour le moment
  
  // Process URL parameters
  processRouteParams();
  
  // Refresh data periodically for real-time feel
  const interval = setInterval(() => {
    whatsAppStore.fetchMessages();
  }, 30000); // Every 30 seconds
  
  // Clean up interval on unmount
  onUnmounted(() => {
    clearInterval(interval);
  });
});
</script>

<style lang="scss" scoped>
.q-card {
  transition: all 0.3s ease;
  
  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px 0 rgba(0, 0, 0, 0.12);
  }
}

// Section des statistiques modernisées
.stats-section {
  background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
  border-radius: 20px;
  padding: 32px;
  margin-top: 40px;
  border: 1px solid rgba(229, 231, 235, 0.8);
}

.section-header {
  text-align: center;
  
  .section-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    justify-content: center;
    
    .q-icon {
      color: #25d366;
    }
  }
  
  .section-subtitle {
    font-size: 1.1rem;
    color: #6b7280;
    margin: 0;
    font-weight: 400;
  }
}

// Cartes de statistiques modernes
.modern-stats-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(229, 231, 235, 0.8);
  overflow: hidden;
  transition: all 0.3s ease;
  height: 100%;

  &:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
  }
}

// Headers avec gradients
.card-gradient-header {
  padding: 20px 24px;
  color: white;
  display: flex;
  align-items: center;
  gap: 16px;
  
  .header-icon {
    font-size: 2rem;
    opacity: 0.9;
  }
  
  .header-content {
    flex: 1;
    
    .card-title {
      font-size: 1.2rem;
      font-weight: 600;
      margin: 0;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .card-subtitle {
      font-size: 0.9rem;
      opacity: 0.9;
      margin: 4px 0 0 0;
    }
  }
}

.sent-header {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
}

.received-header {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.stats-header {
  background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
}

// Contenu des cartes
.card-content {
  padding: 24px;
}

.message-info {
  .info-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 16px;
    
    &:last-child {
      margin-bottom: 0;
    }
    
    .info-icon {
      color: #6b7280;
      font-size: 1.2rem;
      margin-top: 2px;
      flex-shrink: 0;
    }
    
    .info-content {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 4px;
      
      .info-label {
        font-size: 0.85rem;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }
      
      .info-value {
        font-size: 0.95rem;
        color: #1f2937;
        font-weight: 500;
        
        &.message-preview {
          line-height: 1.4;
          color: #4b5563;
        }
      }
    }
  }
}

.type-chip {
  border-radius: 12px;
  font-weight: 600;
  font-size: 0.8rem;
}

.status-badge {
  border-radius: 12px;
  font-weight: 600;
  font-size: 0.8rem;
}

// États vides
.empty-state-card {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 200px;
  
  h4 {
    font-size: 1.1rem;
    font-weight: 600;
  }
  
  p {
    font-size: 0.9rem;
  }
}

// Grille des statistiques
.stats-content {
  .stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 20px;
    
    .stat-item {
      display: flex;
      align-items: center;
      gap: 12px;
      
      .stat-icon-wrapper {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        
        .stat-icon {
          font-size: 1.5rem;
          color: white;
        }
        
        &.sent-stat {
          background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        }
        
        &.delivered-stat {
          background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        &.read-stat {
          background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        
        &.received-stat {
          background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        }
      }
      
      .stat-details {
        flex: 1;
        display: flex;
        flex-direction: column;
        
        .stat-value {
          font-size: 1.5rem;
          font-weight: 700;
          color: #1f2937;
          line-height: 1;
        }
        
        .stat-label {
          font-size: 0.8rem;
          color: #6b7280;
          font-weight: 500;
          text-transform: uppercase;
          letter-spacing: 0.5px;
          margin-top: 2px;
        }
      }
    }
  }
}

// Indicateur de performance
.performance-indicator {
  background: #f8fafc;
  border-radius: 12px;
  padding: 16px;
  border: 1px solid #e5e7eb;
  
  .performance-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    
    .performance-label {
      font-size: 0.9rem;
      font-weight: 600;
      color: #374151;
    }
    
    .performance-value {
      font-size: 1.1rem;
      font-weight: 700;
      color: #059669;
    }
  }
}

// Responsive design
@media (max-width: 1024px) {
  .stats-section {
    padding: 24px 16px;
  }
  
  .section-header .section-title {
    font-size: 1.75rem;
  }
}

@media (max-width: 768px) {
  .stats-section {
    margin-top: 32px;
    padding: 20px 12px;
  }
  
  .section-header {
    .section-title {
      font-size: 1.5rem;
      flex-direction: column;
      gap: 8px;
    }
    
    .section-subtitle {
      font-size: 1rem;
    }
  }
  
  .card-gradient-header {
    padding: 16px 20px;
    flex-direction: column;
    text-align: center;
    gap: 12px;
    
    .header-icon {
      font-size: 1.75rem;
    }
    
    .card-title {
      font-size: 1.1rem;
    }
  }
  
  .card-content {
    padding: 20px;
  }
  
  .stats-content .stats-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }
}

@media (max-width: 480px) {
  .stats-section {
    padding: 16px 8px;
  }
  
  .section-header .section-title {
    font-size: 1.3rem;
  }
  
  .card-gradient-header {
    padding: 14px 16px;
  }
  
  .card-content {
    padding: 16px;
  }
  
  .message-info .info-item {
    margin-bottom: 14px;
  }
}

// Template redirect panel
.template-redirect-panel {
  max-width: 800px;
  margin: 0 auto;
  padding: 16px;
}

.modern-redirect-card {
  background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
  border-radius: 16px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(229, 231, 235, 0.8);
  overflow: hidden;
  transition: all 0.3s ease;

  &:hover {
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
  }
}

.redirect-header {
  background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
  color: white;
  padding: 24px;

  .header-content {
    display: flex;
    align-items: center;
    gap: 16px;

    .header-icon-wrapper {
      width: 56px;
      height: 56px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;

      .q-icon {
        font-size: 1.75rem;
      }
    }

    .header-text {
      flex: 1;

      .card-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin: 0 0 4px 0;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      }

      .card-subtitle {
        font-size: 0.95rem;
        opacity: 0.9;
        margin: 0;
      }
    }
  }
}

.redirect-content {
  padding: 32px 24px;
}

.template-info-banner {
  background: linear-gradient(135deg, #f3e8ff 0%, #faf5ff 100%);
  border: 1px solid #8b5cf6;
  border-radius: 12px;
  margin-bottom: 32px;
  position: relative;
  overflow: hidden;

  &::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: #8b5cf6;
  }

  :deep(.q-icon) {
    color: #8b5cf6;
  }
}

.features-list {
  margin: 32px 0;

  .feature-item {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 24px;
    padding: 20px;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    transition: all 0.2s ease;

    &:hover {
      background: #f0fff4;
      border-color: #25d366;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(37, 211, 102, 0.1);
    }

    &:last-child {
      margin-bottom: 0;
    }

    .feature-icon {
      font-size: 2rem;
      flex-shrink: 0;
    }

    .feature-text {
      flex: 1;

      h4 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #374151;
        margin: 0 0 4px 0;
      }

      p {
        font-size: 0.9rem;
        color: #6b7280;
        margin: 0;
        line-height: 1.4;
      }
    }
  }
}

.redirect-actions {
  text-align: center;
  margin-top: 32px;
  padding-top: 24px;
  border-top: 1px solid #f3f4f6;

  .action-btn {
    border-radius: 12px;
    font-weight: 600;
    padding: 16px 32px;
    text-transform: none;
    transition: all 0.2s ease;
    min-width: 200px;

    &.primary-btn {
      background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
      box-shadow: 0 4px 16px rgba(139, 92, 246, 0.3);

      &:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(139, 92, 246, 0.4);
      }
    }
  }
}

// Responsive design for redirect panel
@media (max-width: 768px) {
  .template-redirect-panel {
    padding: 8px;
  }

  .redirect-header {
    padding: 20px 16px;

    .header-content {
      flex-direction: column;
      text-align: center;
      gap: 12px;

      .header-icon-wrapper {
        width: 48px;
        height: 48px;
      }

      .card-title {
        font-size: 1.3rem;
      }
    }
  }

  .redirect-content {
    padding: 24px 16px;
  }

  .features-list .feature-item {
    flex-direction: column;
    text-align: center;
    gap: 12px;
  }

  .redirect-actions .action-btn {
    width: 100%;
    min-width: auto;
  }
}

@media (max-width: 480px) {
  .template-redirect-panel {
    .redirect-header {
      padding: 16px 12px;
    }

    .redirect-content {
      padding: 20px 12px;
    }

    .features-list .feature-item {
      padding: 16px;
    }
  }
}
</style>