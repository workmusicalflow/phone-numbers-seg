<template>
  <q-page padding>
    <div class="row q-mb-lg">
      <div class="col-12">
        <h1 class="text-h4 q-my-none">WhatsApp Business</h1>
        <p class="text-grey-8">Gestion des messages WhatsApp</p>
      </div>
    </div>

    <div class="row q-col-gutter-lg">
      <div class="col-12">
        <!-- Onglets pour basculer entre l'envoi et la réception de messages -->
        <q-tabs
          v-model="activeTab"
          class="text-primary shadow-2 rounded-borders"
          indicator-color="primary"
          align="left"
        >
          <q-tab name="messages" icon="chat" label="Messages reçus" />
          <q-tab name="send" icon="send" label="Envoyer un message" />
        </q-tabs>

        <q-separator />

        <q-tab-panels v-model="activeTab" animated class="bg-transparent">
          <!-- Panel pour les messages reçus -->
          <q-tab-panel name="messages">
            <whats-app-message-list />
          </q-tab-panel>

          <!-- Panel pour l'envoi de messages -->
          <q-tab-panel name="send">
            <whats-app-send-message />
          </q-tab-panel>
        </q-tab-panels>
      </div>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import WhatsAppMessageList from '@/components/whatsapp/WhatsAppMessageList.vue';
import WhatsAppSendMessage from '@/components/whatsapp/WhatsAppSendMessage.vue';

// État local
const activeTab = ref('messages');
</script>