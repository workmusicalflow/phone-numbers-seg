<template>
  <q-page padding>
    <div class="q-pa-md">
      <h1 class="text-h4">WhatsApp - Version Minimale</h1>
      
      <q-card class="q-mt-md">
        <q-card-section>
          <div class="text-subtitle1">Cette page fonctionne correctement</div>
          <div class="q-mt-md">
            <q-btn 
              color="primary" 
              label="Tester WhatsApp Store"
              @click="testStore"
            />
          </div>
          <div v-if="storeTest" class="q-mt-md">
            Résultat du test : {{ storeTest }}
          </div>
        </q-card-section>
      </q-card>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useWhatsAppStore } from '@/stores/whatsappStore';

const storeTest = ref('');

const testStore = () => {
  try {
    const whatsAppStore = useWhatsAppStore();
    storeTest.value = 'WhatsApp Store chargé avec succès';
  } catch (err) {
    storeTest.value = `Erreur : ${err.message}`;
  }
};
</script>