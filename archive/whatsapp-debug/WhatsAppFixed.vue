<template>
  <q-page padding>
    <div class="q-pa-md">
      <div class="row items-center q-mb-md">
        <h1 class="text-h4 q-my-none">WhatsApp</h1>
        <q-space />
        <div v-if="userStore.currentUser" class="row items-center">
          <!-- Utiliser un simple badge au lieu du ContactCountBadge -->
          <q-badge color="green" :label="`${contactsCount} contacts`" />
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
              <!-- Aperçu du dernier message envoyé - simplifié -->
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
                </q-card-section>
              </q-card>

              <!-- Statistiques simplifiées -->
              <q-card class="q-mt-md">
                <q-card-section>
                  <div class="text-h6">Statistiques</div>
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
                        <q-item-label caption>{{ sentMessagesCount }}</q-item-label>
                      </q-item-section>
                    </q-item>
                    <q-item>
                      <q-item-section avatar>
                        <q-icon name="mail" color="green" />
                      </q-item-section>
                      <q-item-section>
                        <q-item-label>Messages reçus</q-item-label>
                        <q-item-label caption>{{ receivedMessagesCount }}</q-item-label>
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
import { ref, onMounted, computed } from 'vue';
import { useUserStore } from '@/stores/userStore';
import { useWhatsAppStore } from '@/stores/whatsappStore';
import WhatsAppSendMessage from '@/components/whatsapp/WhatsAppSendMessage.vue';
import WhatsAppMessageList from '@/components/whatsapp/WhatsAppMessageList.vue';

// Stores
const userStore = useUserStore();
const whatsAppStore = useWhatsAppStore();

// État local
const activeTab = ref('send');
const contactsCount = ref(0);

// Statistiques simplifiées
const lastSentMessage = computed(() => {
  const outgoing = whatsAppStore.messages.filter(msg => msg.direction === 'OUTGOING');
  return outgoing.length > 0 ? outgoing[0] : null;
});

const sentMessagesCount = computed(() => {
  return whatsAppStore.messages.filter(msg => msg.direction === 'OUTGOING').length;
});

const receivedMessagesCount = computed(() => {
  return whatsAppStore.messages.filter(msg => msg.direction === 'INCOMING').length;
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

// Initialisation
onMounted(async () => {
  try {
    // Charger simplement les messages sans interaction avec contactStore
    await whatsAppStore.fetchMessages();
    
    // Valeur fixe pour le moment
    contactsCount.value = 0;
    
  } catch (err) {
    console.error('Erreur lors du chargement de WhatsApp:', err);
  }
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