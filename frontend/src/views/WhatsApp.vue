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
        <q-tab name="messages" label="Messages" icon="chat" />
      </q-tabs>

      <q-tab-panels v-model="activeTab" animated>
        <!-- Onglet Envoi de message -->
        <q-tab-panel name="send">
          <div class="row q-col-gutter-md">
            <div class="col-12 col-md-8">
              <WhatsAppSendMessage />
            </div>
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

              <!-- Aperçu du dernier message reçu -->
              <q-card v-if="lastReceivedMessage" class="q-mt-md">
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

              <!-- Statistiques rapides -->
              <q-card class="q-mt-md">
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

        <!-- Onglet Messages -->
        <q-tab-panel name="messages">
          <WhatsAppMessageList />
        </q-tab-panel>
      </q-tab-panels>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useUserStore } from '@/stores/userStore';
import { useContactStore } from '@/stores/contactStore';
import { useWhatsAppStore } from '@/stores/whatsappStore';
import ContactCountBadge from '@/components/common/ContactCountBadge.vue';
import WhatsAppSendMessage from '@/components/whatsapp/WhatsAppSendMessage.vue';
import WhatsAppMessageList from '@/components/whatsapp/WhatsAppMessageList.vue';

// Stores
const userStore = useUserStore();
const contactStore = useContactStore();
const whatsAppStore = useWhatsAppStore();

// Router and route
const route = useRoute();

// État local
const activeTab = ref('send');
const contactsCount = ref(0);

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
  switch (status.toLowerCase()) {
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

// Fonction pour rafraîchir le nombre de contacts
const refreshContactsCount = async () => {
  contactsCount.value = await contactStore.fetchContactsCount();
};

// Process URL parameters (similar to SMS.vue)
function processRouteParams() {
  if (route.query.recipient) {
    // Si on a un destinataire dans l'URL, on pourrait le transmettre
    // au composant WhatsAppSendMessage via des props ou un événement
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
</style>