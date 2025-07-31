<template>
  <q-page padding>
    <div class="q-pa-md">
      <h1 class="text-h4">WhatsApp Debug avec gestion d'erreurs</h1>
      
      <div v-if="error" class="q-mb-md">
        <q-banner class="bg-negative text-white">
          <template v-slot:avatar>
            <q-icon name="error" />
          </template>
          Une erreur s'est produite : {{ error.message }}
          <br>
          {{ error.stack }}
        </q-banner>
      </div>
      
      <q-card class="q-mb-md">
        <q-card-section>
          <div class="text-h6">État des stores :</div>
          <div>User store ok : {{ userStoreStatus }}</div>
          <div>Contact store ok : {{ contactStoreStatus }}</div>
          <div>WhatsApp store ok : {{ whatsAppStoreStatus }}</div>
          <div>Nombre de contacts : {{ contactsCount }}</div>
        </q-card-section>
      </q-card>
      
      <q-card class="q-mb-md">
        <q-card-section>
          <div class="text-h6">ContactCountBadge test :</div>
          <ContactCountBadge
            v-if="!badgeError"
            :count="10"
            color="green"
            icon="chat"
            tooltipText="Test de badge"
          />
          <div v-if="badgeError" class="text-negative">
            Erreur badge : {{ badgeError }}
          </div>
        </q-card-section>
      </q-card>
      
      <q-card>
        <q-card-section>
          <div class="text-h6">WhatsApp original page mount test :</div>
          <q-btn
            color="primary"
            @click="testOriginalPage"
            label="Tester le chargement"
          />
          <div v-if="originalPageError" class="text-negative q-mt-md">
            Erreur page originale : {{ originalPageError }}
          </div>
        </q-card-section>
      </q-card>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { ref, onMounted, onErrorCaptured } from 'vue';
import ContactCountBadge from '@/components/common/ContactCountBadge.vue';
import { useUserStore } from '@/stores/userStore';
import { useContactStore } from '@/stores/contactStore';
import { useWhatsAppStore } from '@/stores/whatsappStore';

const error = ref(null);
const badgeError = ref(null);
const originalPageError = ref(null);
const userStoreStatus = ref('chargement...');
const contactStoreStatus = ref('chargement...');
const whatsAppStoreStatus = ref('chargement...');
const contactsCount = ref(0);

// Capturer toutes les erreurs
onErrorCaptured((err) => {
  error.value = err;
  return false;
});

onMounted(async () => {
  try {
    // Test du user store
    const userStore = useUserStore();
    userStoreStatus.value = userStore ? 'OK' : 'Non disponible';
    
    // Test du contact store
    const contactStore = useContactStore();
    contactStoreStatus.value = contactStore ? 'OK' : 'Non disponible';
    
    // Test du whatsapp store
    const whatsAppStore = useWhatsAppStore();
    whatsAppStoreStatus.value = whatsAppStore ? 'OK' : 'Non disponible';
    
    // Test de l'appel fetchContactsCount
    if (contactStore && contactStore.fetchContactsCount) {
      contactsCount.value = await contactStore.fetchContactsCount();
    } else {
      contactsCount.value = -1; // Indiquer une erreur
    }
  } catch (err) {
    error.value = err;
  }
});

const testOriginalPage = async () => {
  try {
    originalPageError.value = null;
    
    // Simuler les appels qui sont faits dans la page originale
    const userStore = useUserStore();
    const contactStore = useContactStore();
    const whatsAppStore = useWhatsAppStore();
    
    // Test du fetchContactsCount
    const count = await contactStore.fetchContactsCount();
    console.log('Contacts count:', count);
    
    // Test du fetchMessages
    await whatsAppStore.fetchMessages();
    console.log('Messages fetched');
    
    originalPageError.value = 'Aucune erreur détectée';
  } catch (err) {
    originalPageError.value = err.message + '\n' + err.stack;
  }
};
</script>