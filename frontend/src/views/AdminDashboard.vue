<template>
  <div class="admin-dashboard">
    <q-card class="q-pa-md">
      <q-card-section>
        <div class="text-h5">Tableau de bord administrateur</div>
      </q-card-section>

      <q-separator />

      <q-card-section v-if="dashboardStore.loading">
        <div class="text-center">
          <q-spinner color="primary" size="3em" />
          <div class="q-mt-sm">Chargement des statistiques...</div>
        </div>
      </q-card-section>

      <q-card-section v-else>
        <div class="row q-col-gutter-md">
          <!-- Statistiques générales -->
          <div class="col-12 col-md-6">
            <q-card class="stats-card">
              <q-card-section>
                <div class="text-h6">Statistiques générales</div>
              </q-card-section>

              <q-card-section>
                <div class="row q-col-gutter-md">
                  <div class="col-6">
                    <q-card class="stat-item bg-primary text-white">
                      <q-card-section>
                        <div class="text-h3 text-center">{{ dashboardStore.stats.totalUsers }}</div>
                        <div class="text-subtitle1 text-center">Utilisateurs</div>
                      </q-card-section>
                    </q-card>
                  </div>
                  <div class="col-6">
                    <q-card class="stat-item bg-secondary text-white">
                      <q-card-section>
                        <div class="text-h3 text-center">{{ dashboardStore.stats.totalPhoneNumbers }}</div>
                        <div class="text-subtitle1 text-center">Numéros</div>
                      </q-card-section>
                    </q-card>
                  </div>
                  <div class="col-6">
                    <q-card class="stat-item bg-accent text-white">
                      <q-card-section>
                        <div class="text-h3 text-center">{{ dashboardStore.stats.totalSMSSent }}</div>
                        <div class="text-subtitle1 text-center">SMS envoyés</div>
                      </q-card-section>
                    </q-card>
                  </div>
                  <div class="col-6">
                    <q-card class="stat-item bg-positive text-white">
                      <q-card-section>
                        <div class="text-h3 text-center">{{ dashboardStore.stats.totalCredits }}</div>
                        <div class="text-subtitle1 text-center">Crédits SMS</div>
                      </q-card-section>
                    </q-card>
                  </div>
                </div>
              </q-card-section>
            </q-card>
          </div>

          <!-- Activité récente -->
          <div class="col-12 col-md-6">
            <q-card class="activity-card">
              <q-card-section>
                <div class="text-h6">Activité récente</div>
                <div class="row q-mt-sm">
                  <div class="col-12 col-md-6">
                    <q-select
                      v-model="activityTypeFilter"
                      :options="activityTypeOptions"
                      label="Type d'activité"
                      dense
                      outlined
                      emit-value
                      map-options
                      clearable
                    />
                  </div>
                  <div class="col-12 col-md-6">
                    <q-input
                      v-model="activitySearchQuery"
                      label="Rechercher"
                      dense
                      outlined
                      clearable
                    >
                      <template v-slot:append>
                        <q-icon name="search" />
                      </template>
                    </q-input>
                  </div>
                </div>
              </q-card-section>

              <q-card-section>
                <q-list bordered separator>
                  <q-item v-for="(activity, index) in filteredActivity" :key="index">
                    <q-item-section avatar>
                      <q-icon :name="getActivityIcon(activity.type)" :color="getActivityColor(activity.type)" />
                    </q-item-section>
                    <q-item-section>
                      <q-item-label>{{ activity.description }}</q-item-label>
                      <q-item-label caption>{{ formatDate(activity.date) }}</q-item-label>
                    </q-item-section>
                  </q-item>
                </q-list>
              </q-card-section>
            </q-card>
          </div>

          <!-- Graphique d'envoi de SMS -->
          <div class="col-12">
            <q-card class="chart-card">
              <q-card-section>
                <div class="text-h6">Envoi de SMS par jour (30 derniers jours)</div>
              </q-card-section>

              <q-card-section>
                <div class="chart-container">
                  <canvas id="smsChart"></canvas>
                </div>
              </q-card-section>
            </q-card>
          </div>

          <!-- Demandes en attente -->
          <div class="col-12">
            <q-card class="pending-card">
              <q-card-section>
                <div class="text-h6">Demandes en attente</div>
                <div class="row q-mt-sm" v-if="pendingTab === 'senderNames' && dashboardStore.pendingSenderNames.length > 0">
                  <div class="col-12 col-md-6">
                    <q-input
                      v-model="senderNameSearchQuery"
                      label="Rechercher un nom d'expéditeur"
                      dense
                      outlined
                      clearable
                    >
                      <template v-slot:append>
                        <q-icon name="search" />
                      </template>
                    </q-input>
                  </div>
                  <div class="col-12 col-md-6">
                    <q-select
                      v-model="senderNameSortBy"
                      :options="senderNameSortOptions"
                      label="Trier par"
                      dense
                      outlined
                      emit-value
                      map-options
                    />
                  </div>
                </div>
                <div class="row q-mt-sm" v-if="pendingTab === 'orders' && dashboardStore.pendingOrders.length > 0">
                  <div class="col-12 col-md-6">
                    <q-input
                      v-model="orderSearchQuery"
                      label="Rechercher une commande"
                      dense
                      outlined
                      clearable
                    >
                      <template v-slot:append>
                        <q-icon name="search" />
                      </template>
                    </q-input>
                  </div>
                  <div class="col-12 col-md-6">
                    <q-select
                      v-model="orderSortBy"
                      :options="orderSortOptions"
                      label="Trier par"
                      dense
                      outlined
                      emit-value
                      map-options
                    />
                  </div>
                </div>
              </q-card-section>

              <q-tabs
                v-model="pendingTab"
                dense
                class="text-grey"
                active-color="primary"
                indicator-color="primary"
                align="justify"
                narrow-indicator
              >
                <q-tab name="senderNames" label="Noms d'expéditeur" />
                <q-tab name="orders" label="Commandes de crédits" />
              </q-tabs>

              <q-separator />

              <q-tab-panels v-model="pendingTab" animated>
                <q-tab-panel name="senderNames">
                  <div v-if="dashboardStore.pendingSenderNames.length === 0" class="text-center q-pa-md text-grey">
                    Aucune demande de nom d'expéditeur en attente
                  </div>
                  <q-list v-else bordered separator>
                    <q-item v-for="senderName in filteredSenderNames" :key="senderName.id">
                      <q-item-section>
                        <q-item-label>{{ senderName.name }}</q-item-label>
                        <q-item-label caption>Demandé par {{ senderName.username }}</q-item-label>
                      </q-item-section>
                      <q-item-section side>
                        <q-btn flat round color="positive" icon="check" @click="approveSenderName(senderName.id)" />
                        <q-btn flat round color="negative" icon="close" @click="rejectSenderName(senderName.id)" />
                      </q-item-section>
                    </q-item>
                  </q-list>
                </q-tab-panel>

                <q-tab-panel name="orders">
                  <div v-if="dashboardStore.pendingOrders.length === 0" class="text-center q-pa-md text-grey">
                    Aucune commande de crédits en attente
                  </div>
                  <q-list v-else bordered separator>
                    <q-item v-for="order in filteredOrders" :key="order.id">
                      <q-item-section>
                        <q-item-label>{{ order.quantity }} crédits</q-item-label>
                        <q-item-label caption>Commandé par {{ order.username }}</q-item-label>
                      </q-item-section>
                      <q-item-section side>
                        <q-btn flat round color="positive" icon="check" @click="completeOrder(order.id)" />
                      </q-item-section>
                    </q-item>
                  </q-list>
                </q-tab-panel>
              </q-tab-panels>
            </q-card>
          </div>
        </div>
      </q-card-section>
    </q-card>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { date } from 'quasar';
import { useDashboardStore } from '../stores/dashboardStore';
import { useSenderNameStore } from '../stores/senderNameStore';
import { useSMSOrderStore } from '../stores/smsOrderStore';
import { useNotification } from '../services/NotificationService';
import Chart from 'chart.js/auto';

// Router
const router = useRouter();

// Stores
const dashboardStore = useDashboardStore();
const senderNameStore = useSenderNameStore();
const smsOrderStore = useSMSOrderStore();
const { showSuccess, showError } = useNotification();

// État local
const pendingTab = ref('senderNames');
let smsChart: Chart | null = null;

// Filtres pour l'activité récente
const activitySearchQuery = ref('');
const activityTypeFilter = ref('');
const activityTypeOptions = [
  { label: 'Tous les types', value: '' },
  { label: 'Utilisateurs', value: 'user' },
  { label: 'SMS', value: 'sms' },
  { label: 'Commandes', value: 'order' },
  { label: 'Noms d\'expéditeur', value: 'senderName' }
];

// Filtres pour les noms d'expéditeur
const senderNameSearchQuery = ref('');
const senderNameSortBy = ref('name');
const senderNameSortOptions = [
  { label: 'Nom', value: 'name' },
  { label: 'Utilisateur', value: 'username' }
];

// Filtres pour les commandes
const orderSearchQuery = ref('');
const orderSortBy = ref('quantity');
const orderSortOptions = [
  { label: 'Quantité', value: 'quantity' },
  { label: 'Utilisateur', value: 'username' }
];

// Computed properties pour les données filtrées
const filteredActivity = computed(() => {
  let result = [...dashboardStore.recentActivity];

  // Filtrer par type d'activité
  if (activityTypeFilter.value) {
    result = result.filter(activity => activity.type === activityTypeFilter.value);
  }

  // Filtrer par recherche
  if (activitySearchQuery.value) {
    const query = activitySearchQuery.value.toLowerCase();
    result = result.filter(activity =>
      activity.description.toLowerCase().includes(query)
    );
  }

  return result;
});

const filteredSenderNames = computed(() => {
  let result = [...dashboardStore.pendingSenderNames];

  // Filtrer par recherche
  if (senderNameSearchQuery.value) {
    const query = senderNameSearchQuery.value.toLowerCase();
    result = result.filter(senderName =>
      senderName.name.toLowerCase().includes(query) ||
      senderName.username.toLowerCase().includes(query)
    );
  }

  // Trier les résultats
  result.sort((a, b) => {
    if (senderNameSortBy.value === 'name') {
      return a.name.localeCompare(b.name);
    } else {
      return a.username.localeCompare(b.username);
    }
  });

  return result;
});

const filteredOrders = computed(() => {
  let result = [...dashboardStore.pendingOrders];

  // Filtrer par recherche
  if (orderSearchQuery.value) {
    const query = orderSearchQuery.value.toLowerCase();
    result = result.filter(order =>
      order.username.toLowerCase().includes(query) ||
      order.quantity.toString().includes(query)
    );
  }

  // Trier les résultats
  result.sort((a, b) => {
    if (orderSortBy.value === 'quantity') {
      return b.quantity - a.quantity; // Tri décroissant par quantité
    } else {
      return a.username.localeCompare(b.username);
    }
  });

  return result;
});

onMounted(async () => {
  try {
    // Charger toutes les données du tableau de bord
    await dashboardStore.loadAllDashboardData();

    // Initialiser le graphique SMS
    initSMSChart();
  } catch (error) {
    console.error('Erreur lors du chargement des données du tableau de bord:', error);
    showError('Erreur lors du chargement des données du tableau de bord');
  }
});

const initSMSChart = () => {
  const ctx = document.getElementById('smsChart') as HTMLCanvasElement;
  if (!ctx) return;

  smsChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: dashboardStore.smsChartData.labels,
      datasets: [{
        label: 'SMS envoyés',
        data: dashboardStore.smsChartData.data,
        backgroundColor: 'rgba(54, 162, 235, 0.2)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 2,
        tension: 0.4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });
};

const formatDate = (dateString: string) => {
  return date.formatDate(dateString, 'DD/MM/YYYY HH:mm');
};

const getActivityIcon = (type: string) => {
  switch (type) {
    case 'sms':
      return 'message';
    case 'user':
      return 'person';
    case 'order':
      return 'shopping_cart';
    case 'senderName':
      return 'label';
    default:
      return 'event';
  }
};

const getActivityColor = (type: string) => {
  switch (type) {
    case 'sms':
      return 'primary';
    case 'user':
      return 'secondary';
    case 'order':
      return 'positive';
    case 'senderName':
      return 'accent';
    default:
      return 'grey';
  }
};

const approveSenderName = async (id: number) => {
  try {
    const result = await senderNameStore.updateSenderNameStatus(id, 'approved');
    if (result) {
      showSuccess('Nom d\'expéditeur approuvé avec succès');
    } else {
      showError('Erreur lors de l\'approbation du nom d\'expéditeur');
    }

    // Mettre à jour la liste des demandes en attente
    await dashboardStore.fetchPendingSenderNames();
  } catch (error) {
    console.error('Erreur lors de l\'approbation du nom d\'expéditeur:', error);
    showError('Erreur lors de l\'approbation du nom d\'expéditeur');
  }
};

const rejectSenderName = async (id: number) => {
  try {
    const result = await senderNameStore.updateSenderNameStatus(id, 'rejected');
    if (result) {
      showSuccess('Nom d\'expéditeur rejeté');
    } else {
      showError('Erreur lors du rejet du nom d\'expéditeur');
    }

    // Mettre à jour la liste des demandes en attente
    await dashboardStore.fetchPendingSenderNames();
  } catch (error) {
    console.error('Erreur lors du rejet du nom d\'expéditeur:', error);
    showError('Erreur lors du rejet du nom d\'expéditeur');
  }
};

const completeOrder = async (id: number) => {
  try {
    const result = await smsOrderStore.updateSMSOrderStatus(id, 'completed');
    if (result) {
      showSuccess('Commande complétée avec succès');
    } else {
      showError('Erreur lors de la complétion de la commande');
    }

    // Mettre à jour la liste des commandes en attente
    await dashboardStore.fetchPendingOrders();
  } catch (error) {
    console.error('Erreur lors de la complétion de la commande:', error);
    showError('Erreur lors de la complétion de la commande');
  }
};

const viewUser = (userId: number) => {
  router.push({ name: 'user-details', params: { id: userId } });
};
</script>

<style scoped>
.admin-dashboard {
  max-width: 1200px;
  margin: 0 auto;
}

.stats-card,
.activity-card,
.chart-card,
.pending-card {
  height: 100%;
}

.stat-item {
  border-radius: 8px;
}

.chart-container {
  height: 300px;
  position: relative;
}
</style>
