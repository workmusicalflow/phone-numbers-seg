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
          <div class="row q-col-gutter-md q-mt-lg">
            <div class="col-12 col-md-4">
              <!-- Aperçu du dernier message envoyé -->
              <q-card v-if="lastSentMessage">
                <q-card-section>
                  <div class="text-h6">Dernier message envoyé</div>
                </q-card-section>
                <q-separator />
                <q-card-section>
                  <div class="row q-mb-sm">
                    <div class="col-5 text-weight-bold">Destinataire :</div>
                    <div class="col-7">{{ lastSentMessage.phoneNumber }}</div>
                  </div>
                  <div class="row q-mb-sm">
                    <div class="col-5 text-weight-bold">Type :</div>
                    <div class="col-7">{{ lastSentMessage.type }}</div>
                  </div>
                  <div class="row q-mb-sm">
                    <div class="col-5 text-weight-bold">Statut :</div>
                    <div class="col-7">
                      <q-badge 
                        :color="getStatusColor(lastSentMessage.status)"
                        :label="lastSentMessage.status"
                      />
                    </div>
                  </div>
                  <div class="row q-mb-sm" v-if="lastSentMessage.content">
                    <div class="col-5 text-weight-bold">Message :</div>
                    <div class="col-7">{{ lastSentMessage.content }}</div>
                  </div>
                  <div class="row">
                    <div class="col-5 text-weight-bold">Date :</div>
                    <div class="col-7">{{ formatDate(lastSentMessage.createdAt) }}</div>
                  </div>
                </q-card-section>
              </q-card>
            </div>

            <div class="col-12 col-md-4">
              <!-- Aperçu du dernier message reçu -->
              <q-card v-if="lastReceivedMessage">
                <q-card-section>
                  <div class="text-h6">Dernier message reçu</div>
                </q-card-section>
                <q-separator />
                <q-card-section>
                  <div class="row q-mb-sm">
                    <div class="col-5 text-weight-bold">De :</div>
                    <div class="col-7">{{ lastReceivedMessage.phoneNumber }}</div>
                  </div>
                  <div class="row q-mb-sm">
                    <div class="col-5 text-weight-bold">Type :</div>
                    <div class="col-7">{{ lastReceivedMessage.type }}</div>
                  </div>
                  <div class="row q-mb-sm" v-if="lastReceivedMessage.content">
                    <div class="col-5 text-weight-bold">Message :</div>
                    <div class="col-7">{{ lastReceivedMessage.content }}</div>
                  </div>
                  <div class="row">
                    <div class="col-5 text-weight-bold">Date :</div>
                    <div class="col-7">{{ formatDate(lastReceivedMessage.createdAt) }}</div>
                  </div>
                </q-card-section>
              </q-card>
            </div>

            <div class="col-12 col-md-4">
              <!-- Statistiques rapides -->
              <q-card>
                <q-card-section>
                  <div class="text-h6">Statistiques du jour</div>
                </q-card-section>
                <q-separator />
                <q-card-section>
                  <q-list>
                    <q-item>
                      <q-item-section avatar>
                        <q-icon name="send" color="primary" />
                      </q-item-section>
                      <q-item-section>
                        <q-item-label>Messages envoyés</q-item-label>
                        <q-item-label caption>{{ stats.totalMessages }}</q-item-label>
                      </q-item-section>
                    </q-item>
                    <q-item>
                      <q-item-section avatar>
                        <q-icon name="done" color="positive" />
                      </q-item-section>
                      <q-item-section>
                        <q-item-label>Messages délivrés</q-item-label>
                        <q-item-label caption>{{ stats.deliveredMessages }}</q-item-label>
                      </q-item-section>
                    </q-item>
                    <q-item>
                      <q-item-section avatar>
                        <q-icon name="visibility" color="info" />
                      </q-item-section>
                      <q-item-section>
                        <q-item-label>Messages lus</q-item-label>
                        <q-item-label caption>{{ stats.readMessages }}</q-item-label>
                      </q-item-section>
                    </q-item>
                    <q-item>
                      <q-item-section avatar>
                        <q-icon name="mail" color="green" />
                      </q-item-section>
                      <q-item-section>
                        <q-item-label>Messages reçus</q-item-label>
                        <q-item-label caption>{{ stats.receivedMessages }}</q-item-label>
                      </q-item-section>
                    </q-item>
                  </q-list>
                </q-card-section>
              </q-card>
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
          <WhatsAppTemplateHistoryList />
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
import WhatsAppTemplateHistoryList from '@/components/whatsapp/WhatsAppTemplateHistoryList.vue';
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
  if (newTab === 'templates') {
    await Promise.all([
      whatsAppStore.fetchTemplateHistory(),
      whatsAppStore.fetchMostUsedTemplates(),
      whatsAppStore.fetchCommonParameterValues()
    ]);
  }
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
  
  // Charger les données d'historique des templates si on est sur l'onglet templates
  if (activeTab.value === 'templates') {
    await Promise.all([
      whatsAppStore.fetchTemplateHistory(),
      whatsAppStore.fetchMostUsedTemplates(),
      whatsAppStore.fetchCommonParameterValues()
    ]);
  }
  
  // Process URL parameters
  processRouteParams();
  
  // Refresh data periodically for real-time feel
  const interval = setInterval(() => {
    whatsAppStore.fetchMessages();
    
    // Si on est sur l'onglet templates, actualiser aussi l'historique
    if (activeTab.value === 'templates') {
      whatsAppStore.fetchTemplateHistory();
    }
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
</style>