<template>
  <div class="sender-names-page">
    <div class="q-pa-md">
      <h1 class="text-h4 q-mb-md">Gestion des Noms d'Expéditeur</h1>
      
      <!-- Statistiques -->
      <div class="row q-mb-lg">
        <div class="col-12 col-md-4 q-pa-sm">
          <q-card class="bg-primary text-white">
            <q-card-section>
              <div class="text-h6">Total des demandes</div>
              <div class="text-h3">{{ totalSenderNames }}</div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-12 col-md-4 q-pa-sm">
          <q-card class="bg-warning text-white">
            <q-card-section>
              <div class="text-h6">Demandes en attente</div>
              <div class="text-h3">{{ pendingSenderNames.length }}</div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-12 col-md-4 q-pa-sm">
          <q-card class="bg-positive text-white">
            <q-card-section>
              <div class="text-h6">Demandes approuvées</div>
              <div class="text-h3">{{ approvedSenderNames.length }}</div>
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
            placeholder="Rechercher un nom d'expéditeur..."
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
              label="Tous"
              @click="statusFilter = 'all'"
            />
            <q-btn
              :color="statusFilter === 'pending' ? 'warning' : 'grey'"
              label="En attente"
              @click="statusFilter = 'pending'"
            />
            <q-btn
              :color="statusFilter === 'approved' ? 'positive' : 'grey'"
              label="Approuvés"
              @click="statusFilter = 'approved'"
            />
            <q-btn
              :color="statusFilter === 'rejected' ? 'negative' : 'grey'"
              label="Rejetés"
              @click="statusFilter = 'rejected'"
            />
          </q-btn-group>
        </div>
      </div>
      
      <!-- Tableau des noms d'expéditeur -->
      <q-table
        :rows="filteredSenderNames"
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
                @click="openApproveDialog(props.row)"
              >
                <q-tooltip>Approuver</q-tooltip>
              </q-btn>
              <q-btn
                v-if="props.row.status === 'pending'"
                flat
                round
                color="negative"
                icon="close"
                size="sm"
                @click="openRejectDialog(props.row)"
              >
                <q-tooltip>Rejeter</q-tooltip>
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
                @click="confirmDeleteSenderName(props.row)"
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
      
      <!-- Dialogue d'approbation -->
      <q-dialog v-model="approveDialog" persistent>
        <q-card style="min-width: 350px">
          <q-card-section>
            <div class="text-h6">Approuver le nom d'expéditeur</div>
          </q-card-section>
          
          <q-card-section>
            <p>Êtes-vous sûr de vouloir approuver le nom d'expéditeur <strong>{{ selectedSenderName?.name }}</strong> ?</p>
            <p>Cette action enverra une notification à l'utilisateur.</p>
          </q-card-section>
          
          <q-card-actions align="right">
            <q-btn label="Annuler" color="negative" v-close-popup />
            <q-btn label="Approuver" color="positive" @click="approveSenderName" :loading="loading" />
          </q-card-actions>
        </q-card>
      </q-dialog>
      
      <!-- Dialogue de rejet -->
      <q-dialog v-model="rejectDialog" persistent>
        <q-card style="min-width: 350px">
          <q-card-section>
            <div class="text-h6">Rejeter le nom d'expéditeur</div>
          </q-card-section>
          
          <q-card-section>
            <p>Êtes-vous sûr de vouloir rejeter le nom d'expéditeur <strong>{{ selectedSenderName?.name }}</strong> ?</p>
            <q-input
              v-model="rejectReason"
              label="Raison du rejet"
              outlined
              type="textarea"
              autogrow
            />
          </q-card-section>
          
          <q-card-actions align="right">
            <q-btn label="Annuler" color="primary" v-close-popup />
            <q-btn label="Rejeter" color="negative" @click="rejectSenderName" :loading="loading" />
          </q-card-actions>
        </q-card>
      </q-dialog>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useSenderNameStore } from '../stores/senderNameStore';
import { date } from 'quasar';

// Router
const router = useRouter();

// Store
const senderNameStore = useSenderNameStore();

// État local
const searchQuery = ref('');
const statusFilter = ref('all');
const approveDialog = ref(false);
const rejectDialog = ref(false);
const selectedSenderName = ref(null);
const rejectReason = ref('');

// Pagination
const pagination = ref({
  rowsPerPage: 10
});

// Colonnes du tableau
const columns = [
  { name: 'id', label: 'ID', field: 'id', sortable: true, align: 'left' as const },
  { name: 'name', label: 'Nom d\'expéditeur', field: 'name', sortable: true, align: 'left' as const },
  { name: 'userId', label: 'ID Utilisateur', field: 'userId', sortable: true, align: 'left' as const },
  { name: 'status', label: 'Statut', field: 'status', sortable: true, align: 'left' as const },
  { name: 'createdAt', label: 'Date de création', field: 'createdAt', sortable: true, align: 'left' as const },
  { name: 'updatedAt', label: 'Date de mise à jour', field: 'updatedAt', sortable: true, align: 'left' as const },
  { name: 'actions', label: 'Actions', field: 'actions', align: 'center' as const }
];

// Computed properties
const loading = computed(() => senderNameStore.loading);
const totalSenderNames = computed(() => senderNameStore.senderNames.length);
const pendingSenderNames = computed(() => senderNameStore.pendingSenderNames);
const approvedSenderNames = computed(() => senderNameStore.approvedSenderNames);
const rejectedSenderNames = computed(() => senderNameStore.rejectedSenderNames);

const filteredSenderNames = computed(() => {
  let filtered = senderNameStore.senderNames;
  
  // Filtrer par statut
  if (statusFilter.value !== 'all') {
    filtered = filtered.filter(senderName => senderName.status === statusFilter.value);
  }
  
  // Filtrer par recherche
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(senderName => 
      senderName.name.toLowerCase().includes(query) || 
      senderName.id.toString().includes(query) ||
      senderName.userId.toString().includes(query)
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
    case 'approved': return 'positive';
    case 'rejected': return 'negative';
    default: return 'grey';
  }
}

function getStatusLabel(status: string): string {
  switch (status) {
    case 'pending': return 'En attente';
    case 'approved': return 'Approuvé';
    case 'rejected': return 'Rejeté';
    default: return 'Inconnu';
  }
}

function openApproveDialog(senderName: any) {
  selectedSenderName.value = senderName;
  approveDialog.value = true;
}

function openRejectDialog(senderName: any) {
  selectedSenderName.value = senderName;
  rejectReason.value = '';
  rejectDialog.value = true;
}

async function approveSenderName() {
  if (!selectedSenderName.value) return;
  
  await senderNameStore.updateSenderNameStatus(selectedSenderName.value.id, 'approved');
  approveDialog.value = false;
}

async function rejectSenderName() {
  if (!selectedSenderName.value) return;
  
  await senderNameStore.updateSenderNameStatus(selectedSenderName.value.id, 'rejected');
  rejectDialog.value = false;
}

function viewUser(userId: number) {
  router.push({ name: 'user-details', params: { id: userId } });
}

async function confirmDeleteSenderName(senderName: any) {
  if (!confirm(`Êtes-vous sûr de vouloir supprimer le nom d'expéditeur "${senderName.name}" ?`)) return;
  
  await senderNameStore.deleteSenderName(senderName.id);
}

// Cycle de vie
onMounted(async () => {
  await senderNameStore.fetchSenderNames();
});
</script>

<style scoped>
.sender-names-page {
  max-width: 1200px;
  margin: 0 auto;
}
</style>
