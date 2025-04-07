<template>
  <div class="sms-orders-page">
    <div class="q-pa-md">
      <h1 class="text-h4 q-mb-md">Gestion des Commandes de Crédits SMS</h1>
      
      <!-- Statistiques -->
      <div class="row q-mb-lg">
        <div class="col-12 col-md-4 q-pa-sm">
          <q-card class="bg-primary text-white">
            <q-card-section>
              <div class="text-h6">Total des commandes</div>
              <div class="text-h3">{{ totalOrders }}</div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-12 col-md-4 q-pa-sm">
          <q-card class="bg-warning text-white">
            <q-card-section>
              <div class="text-h6">Commandes en attente</div>
              <div class="text-h3">{{ pendingOrders.length }}</div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-12 col-md-4 q-pa-sm">
          <q-card class="bg-positive text-white">
            <q-card-section>
              <div class="text-h6">Crédits SMS commandés</div>
              <div class="text-h3">{{ totalSMSCredits }}</div>
            </q-card-section>
          </q-card>
        </div>
      </div>
      
      <!-- Filtres et recherche -->
      <div class="row q-mb-md items-center justify-between">
        <div class="col-12 col-md-6 q-mb-sm-xs">
          <q-input
            v-model="searchQuery"
            outlined
            dense
            placeholder="Rechercher une commande..."
            class="q-mr-sm"
          >
            <template v-slot:append>
              <q-icon name="search" />
            </template>
          </q-input>
        </div>
        <div class="col-12 col-md-6 text-right">
          <q-btn-group outline>
            <q-btn
              :color="statusFilter === 'all' ? 'primary' : 'grey'"
              label="Toutes"
              @click="statusFilter = 'all'"
            />
            <q-btn
              :color="statusFilter === 'pending' ? 'warning' : 'grey'"
              label="En attente"
              @click="statusFilter = 'pending'"
            />
            <q-btn
              :color="statusFilter === 'completed' ? 'positive' : 'grey'"
              label="Complétées"
              @click="statusFilter = 'completed'"
            />
          </q-btn-group>
        </div>
      </div>
      
      <!-- Tableau des commandes -->
      <q-table
        :rows="filteredOrders"
        :columns="columns"
        row-key="id"
        :loading="loading"
        :pagination="pagination"
        :filter="searchQuery"
        binary-state-sort
      >
        <!-- Slot pour le statut -->
        <template v-slot:body-cell-status="props">
          <q-td :props="props">
            <q-chip
              :color="getStatusColor(props.row.status)"
              text-color="white"
              dense
            >
              {{ getStatusLabel(props.row.status) }}
            </q-chip>
          </q-td>
        </template>
        
        <!-- Slot pour les actions -->
        <template v-slot:body-cell-actions="props">
          <q-td :props="props">
            <div class="q-gutter-sm">
              <q-btn
                v-if="props.row.status === 'pending'"
                flat
                round
                color="positive"
                icon="check"
                size="sm"
                @click="openCompleteDialog(props.row)"
              >
                <q-tooltip>Compléter la commande</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                color="info"
                icon="person"
                size="sm"
                @click="viewUser(props.row.userId)"
              >
                <q-tooltip>Voir l'utilisateur</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                color="negative"
                icon="delete"
                size="sm"
                @click="confirmDeleteOrder(props.row)"
              >
                <q-tooltip>Supprimer</q-tooltip>
              </q-btn>
            </div>
          </q-td>
        </template>
        
        <!-- Slot pour la date de création -->
        <template v-slot:body-cell-createdAt="props">
          <q-td :props="props">
            {{ formatDate(props.row.createdAt) }}
          </q-td>
        </template>
        
        <!-- Slot pour la date de mise à jour -->
        <template v-slot:body-cell-updatedAt="props">
          <q-td :props="props">
            {{ formatDate(props.row.updatedAt) }}
          </q-td>
        </template>
      </q-table>
      
      <!-- Dialogue de complétion de commande -->
      <q-dialog v-model="completeDialog" persistent>
        <q-card style="min-width: 350px">
          <q-card-section>
            <div class="text-h6">Compléter la commande</div>
          </q-card-section>
          
          <q-card-section>
            <p>Êtes-vous sûr de vouloir compléter la commande de <strong>{{ selectedOrder?.quantity }}</strong> crédits SMS ?</p>
            <p>Cette action ajoutera les crédits au compte de l'utilisateur et enverra une notification.</p>
          </q-card-section>
          
          <q-card-actions align="right">
            <q-btn label="Annuler" color="negative" v-close-popup />
            <q-btn label="Compléter" color="positive" @click="completeOrder" :loading="loading" />
          </q-card-actions>
        </q-card>
      </q-dialog>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useSMSOrderStore } from '../stores/smsOrderStore';
import { date } from 'quasar';

// Router
const router = useRouter();

// Store
const smsOrderStore = useSMSOrderStore();

// État local
const searchQuery = ref('');
const statusFilter = ref('all');
const completeDialog = ref(false);
const selectedOrder = ref<any>(null);

// Pagination
const pagination = ref({
  rowsPerPage: 10
});

// Colonnes du tableau
const columns = [
  { name: 'id', label: 'ID', field: 'id', sortable: true, align: 'left' as const },
  { name: 'userId', label: 'ID Utilisateur', field: 'userId', sortable: true, align: 'left' as const },
  { name: 'quantity', label: 'Quantité', field: 'quantity', sortable: true, align: 'right' as const },
  { name: 'status', label: 'Statut', field: 'status', sortable: true, align: 'left' as const },
  { name: 'createdAt', label: 'Date de commande', field: 'createdAt', sortable: true, align: 'left' as const },
  { name: 'updatedAt', label: 'Dernière mise à jour', field: 'updatedAt', sortable: true, align: 'left' as const },
  { name: 'actions', label: 'Actions', field: 'actions', align: 'center' as const }
];

// Computed properties
const loading = computed(() => smsOrderStore.loading);
const totalOrders = computed(() => smsOrderStore.smsOrders.length);
const pendingOrders = computed(() => smsOrderStore.pendingSMSOrders);
const completedOrders = computed(() => smsOrderStore.completedSMSOrders);

const totalSMSCredits = computed(() => {
  return smsOrderStore.smsOrders.reduce((total, order) => total + order.quantity, 0);
});

const filteredOrders = computed(() => {
  let filtered = smsOrderStore.smsOrders;
  
  // Filtrer par statut
  if (statusFilter.value !== 'all') {
    filtered = filtered.filter(order => order.status === statusFilter.value);
  }
  
  // Filtrer par recherche
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(order => 
      order.id.toString().includes(query) ||
      order.userId.toString().includes(query) ||
      order.quantity.toString().includes(query)
    );
  }
  
  return filtered;
});

// Méthodes
function formatDate(dateString: string): string {
  return date.formatDate(dateString, 'DD/MM/YYYY HH:mm');
}

function getStatusColor(status: string): string {
  switch (status) {
    case 'pending': return 'warning';
    case 'completed': return 'positive';
    default: return 'grey';
  }
}

function getStatusLabel(status: string): string {
  switch (status) {
    case 'pending': return 'En attente';
    case 'completed': return 'Complétée';
    default: return 'Inconnu';
  }
}

function openCompleteDialog(order: any) {
  selectedOrder.value = order;
  completeDialog.value = true;
}

async function completeOrder() {
  if (!selectedOrder.value) return;
  
  await smsOrderStore.updateSMSOrderStatus(selectedOrder.value.id, 'completed');
  completeDialog.value = false;
}

function viewUser(userId: number) {
  router.push({ name: 'user-details', params: { id: userId } });
}

async function confirmDeleteOrder(order: any) {
  if (!confirm(`Êtes-vous sûr de vouloir supprimer la commande #${order.id} ?`)) return;
  
  await smsOrderStore.deleteSMSOrder(order.id);
}

// Cycle de vie
onMounted(async () => {
  await smsOrderStore.fetchSMSOrders();
});
</script>

<style scoped>
.sms-orders-page {
  max-width: 1200px;
  margin: 0 auto;
}
</style>
