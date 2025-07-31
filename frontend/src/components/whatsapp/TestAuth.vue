<template>
  <div class="q-pa-md">
    <q-card>
      <q-card-section>
        <div class="text-h6">Test d'authentification</div>
      </q-card-section>
      
      <q-card-section>
        <div v-if="loading">Chargement...</div>
        <div v-else>
          <p><strong>Utilisateur connecté :</strong> {{ isAuthenticated ? 'Oui' : 'Non' }}</p>
          <p v-if="currentUser"><strong>Email :</strong> {{ currentUser.email }}</p>
          <p v-if="currentUser"><strong>ID :</strong> {{ currentUser.id }}</p>
          <p v-if="currentUser"><strong>Admin :</strong> {{ currentUser.isAdmin ? 'Oui' : 'Non' }}</p>
          
          <div class="q-mt-md">
            <q-btn 
              color="primary" 
              @click="testWhatsAppQuery"
              label="Tester la requête WhatsApp"
            />
          </div>
          
          <div v-if="whatsappResult" class="q-mt-md">
            <pre>{{ JSON.stringify(whatsappResult, null, 2) }}</pre>
          </div>
        </div>
      </q-card-section>
    </q-card>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/authStore';
import { apolloClient, gql } from '@/services/api';

const authStore = useAuthStore();
const loading = ref(true);
const whatsappResult = ref(null);

const isAuthenticated = ref(false);
const currentUser = ref(null);

onMounted(async () => {
  try {
    // Vérifier l'authentification
    await authStore.checkAuth();
    isAuthenticated.value = authStore.isAuthenticated;
    currentUser.value = authStore.currentUser;
  } catch (error) {
    console.error('Erreur lors de la vérification auth:', error);
  } finally {
    loading.value = false;
  }
});

const testWhatsAppQuery = async () => {
  try {
    const result = await apolloClient.query({
      query: gql`
        query GetWhatsAppMessages($limit: Int, $offset: Int) {
          getWhatsAppMessages(limit: $limit, offset: $offset) {
            messages {
              id
              phoneNumber
              direction
              type
              status
            }
            totalCount
            hasMore
          }
        }
      `,
      variables: {
        limit: 5,
        offset: 0
      },
      fetchPolicy: 'network-only'
    });
    
    whatsappResult.value = result.data;
  } catch (error) {
    console.error('Erreur lors de la requête WhatsApp:', error);
    whatsappResult.value = { error: error.message };
  }
};
</script>