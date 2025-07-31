<template>
  <q-page padding>
    <div class="q-pa-md">
      <div class="row items-center q-mb-md">
        <h1 class="text-h4 q-my-none">WhatsApp</h1>
        <q-space />
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
import { ref } from 'vue';
import WhatsAppSendMessage from '@/components/whatsapp/WhatsAppSendMessage.vue';
import WhatsAppMessageList from '@/components/whatsapp/WhatsAppMessageList.vue';

// État local
const activeTab = ref('send');
</script>