<template>
  <q-page padding>
    <div class="row q-col-gutter-md">
      <!-- En-tête du tableau de bord -->
      <div class="col-12">
        <q-card class="bg-primary text-white">
          <q-card-section>
            <div class="text-h6">Bienvenue, {{ user?.username || 'Utilisateur' }}</div>
            <div class="text-subtitle2">Tableau de bord utilisateur</div>
          </q-card-section>
        </q-card>
      </div>

      <!-- Widget de crédits SMS -->
      <div class="col-12 col-md-4">
        <credit-widget :credits="userCredits" />
      </div>

      <!-- Statistiques d'utilisation récente -->
      <div class="col-12 col-md-8">
        <q-card>
          <q-card-section>
            <div class="text-h6">Utilisation récente</div>
          </q-card-section>
          <q-card-section>
            <usage-chart :data="usageData" />
          </q-card-section>
        </q-card>
      </div>

      <!-- Historique des SMS récents -->
      <div class="col-12 col-md-6">
        <q-card>
          <q-card-section>
            <div class="text-h6">SMS récents</div>
          </q-card-section>
          <q-card-section>
            <q-list bordered separator>
              <q-item v-for="sms in recentSMS" :key="sms.id" clickable v-ripple>
                <q-item-section>
                  <q-item-label>{{ sms.recipient }}</q-item-label>
                  <q-item-label caption>{{ sms.message }}</q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-badge :color="getStatusColor(sms.status)">
                    {{ sms.status }}
                  </q-badge>
                </q-item-section>
                <q-item-section side>
                  {{ formatDate(sms.sentAt) }}
                </q-item-section>
              </q-item>
              <q-item v-if="recentSMS.length === 0">
                <q-item-section>
                  <q-item-label>Aucun SMS récent</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat color="primary" label="Voir tout" to="/sms-history" />
          </q-card-actions>
        </q-card>
      </div>

      <!-- Segments populaires -->
      <div class="col-12 col-md-6">
        <q-card>
          <q-card-section>
            <div class="text-h6">Segments populaires</div>
          </q-card-section>
          <q-card-section>
            <q-list bordered separator>
              <q-item v-for="segment in popularSegments" :key="segment.id" clickable v-ripple>
                <q-item-section>
                  <q-item-label>{{ segment.name }}</q-item-label>
                  <q-item-label caption>{{ segment.description }}</q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-chip color="primary" text-color="white">
                    {{ segment.count }} numéros
                  </q-chip>
                </q-item-section>
              </q-item>
              <q-item v-if="popularSegments.length === 0">
                <q-item-section>
                  <q-item-label>Aucun segment disponible</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat color="primary" label="Gérer les segments" to="/segments" />
          </q-card-actions>
        </q-card>
      </div>

      <!-- Actions rapides -->
      <div class="col-12">
        <q-card>
          <q-card-section>
            <div class="text-h6">Actions rapides</div>
          </q-card-section>
          <q-card-section class="q-pa-none">
            <div class="row q-pa-md">
              <div class="col-6 col-md-3 q-pa-sm">
                <q-btn color="primary" class="full-width" icon="send" label="Envoyer SMS" to="/sms" />
              </div>
              <div class="col-6 col-md-3 q-pa-sm">
                <q-btn color="secondary" class="full-width" icon="phone" label="Segmenter" to="/segment" />
              </div>
              <div class="col-6 col-md-3 q-pa-sm">
                <q-btn color="accent" class="full-width" icon="group" label="Traitement par lot" to="/batch" />
              </div>
              <div class="col-6 col-md-3 q-pa-sm">
                <q-btn color="positive" class="full-width" icon="upload" label="Importer" to="/import" />
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Overlay de chargement -->
    <loading-overlay :loading="loading" />
  </q-page>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted, computed } from 'vue';
import { date } from 'quasar';
import { useUserDashboardStore } from '../stores/userDashboardStore';
import { useAuthStore } from '../stores/authStore';
import { useUserStore } from '../stores/userStore';
import CreditWidget from 'src/components/CreditWidget.vue';
import UsageChart from 'src/components/UsageChart.vue';
import LoadingOverlay from 'src/components/LoadingOverlay.vue';

export default defineComponent({
  name: 'UserDashboard',
  
  components: {
    CreditWidget,
    UsageChart,
    LoadingOverlay
  },
  
  setup() {
    const dashboardStore = useUserDashboardStore();
    const authStore = useAuthStore();
    const userStore = useUserStore();
    const loading = ref(true);
    
    // Données utilisateur
    const user = computed(() => userStore.currentUser);
    const userCredits = computed(() => dashboardStore.credits);
    
    // Données pour les graphiques et listes
    const usageData = computed(() => dashboardStore.usageData);
    const recentSMS = computed(() => dashboardStore.recentSMS);
    const popularSegments = computed(() => dashboardStore.popularSegments);
    
    // Formater la date
    const formatDate = (dateString: string) => {
      return date.formatDate(dateString, 'DD/MM/YYYY HH:mm');
    };
    
    // Obtenir la couleur en fonction du statut
    const getStatusColor = (status: string) => {
      switch (status.toLowerCase()) {
        case 'delivered':
          return 'positive';
        case 'failed':
          return 'negative';
        case 'pending':
          return 'warning';
        default:
          return 'grey';
      }
    };
    
    // Charger les données au montage du composant
    onMounted(async () => {
      try {
        await dashboardStore.fetchDashboardData();
      } catch (error) {
        console.error('Erreur lors du chargement des données du tableau de bord:', error);
      } finally {
        loading.value = false;
      }
    });
    
    return {
      loading,
      user,
      userCredits,
      usageData,
      recentSMS,
      popularSegments,
      formatDate,
      getStatusColor
    };
  }
});
</script>

<style scoped>
/* Styles spécifiques au tableau de bord utilisateur */
</style>
