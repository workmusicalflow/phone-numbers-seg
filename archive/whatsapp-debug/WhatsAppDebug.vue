<template>
  <q-page padding>
    <div class="q-pa-md">
      <h1 class="text-h4">WhatsApp Debug</h1>
      <p>Test de base</p>
      
      <!-- Test par étapes -->
      <div class="q-mt-md">
        <p>Étape 1: Page de base - OK</p>
        
        <!-- Étape 2: Test stores -->
        <div v-if="true">
          <p>Étape 2: Stores - {{ storesLoaded ? 'OK' : 'Erreur' }}</p>
        </div>
        
        <!-- Étape 3: Test WhatsAppSendMessage -->
        <div v-if="storesLoaded">
          <p>Étape 3: WhatsAppSendMessage</p>
          <WhatsAppSendMessage />
        </div>
        
        <!-- Étape 4: Test WhatsAppMessageList -->
        <div v-if="storesLoaded">
          <p>Étape 4: WhatsAppMessageList</p>
          <WhatsAppMessageList />
        </div>
      </div>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useUserStore } from '@/stores/userStore';
import { useWhatsAppStore } from '@/stores/whatsappStore';

// Import des composants un par un pour isoler l'erreur
import WhatsAppSendMessage from '@/components/whatsapp/WhatsAppSendMessage.vue';
import WhatsAppMessageList from '@/components/whatsapp/WhatsAppMessageList.vue';

const storesLoaded = ref(false);

onMounted(() => {
  try {
    const userStore = useUserStore();
    const whatsAppStore = useWhatsAppStore();
    storesLoaded.value = true;
  } catch (error) {
    console.error('Erreur lors du chargement des stores:', error);
  }
});
</script>