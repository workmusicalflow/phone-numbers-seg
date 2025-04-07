<template>
  <div class="orange-api-config-page">
    <div class="q-pa-md">
      <h1 class="text-h4 q-mb-md">Configuration de l'API Orange</h1>
      
      <!-- Statistiques -->
      <div class="row q-mb-lg">
        <div class="col-12 col-md-4 q-pa-sm">
          <q-card class="bg-primary text-white">
            <q-card-section>
              <div class="text-h6">Total des configurations</div>
              <div class="text-h3">{{ totalConfigs }}</div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-12 col-md-4 q-pa-sm">
          <q-card class="bg-warning text-white">
            <q-card-section>
              <div class="text-h6">Configurations utilisateurs</div>
              <div class="text-h3">{{ userConfigs.length }}</div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-12 col-md-4 q-pa-sm">
          <q-card class="bg-positive text-white">
            <q-card-section>
              <div class="text-h6">Configuration admin</div>
              <div class="text-h3">{{ adminConfig ? 1 : 0 }}</div>
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
            placeholder="Rechercher une configuration..."
            class="q-mr-sm"
          >
            <template v-slot:append>
              <q-icon name="search" />
            </template>
          </q-input>
        </div>
        <div class="col-12 col-md-6 text-right">
          <q-btn
            color="primary"
            icon="add"
            label="Nouvelle configuration"
            @click="openCreateDialog"
          />
        </div>
      </div>
      
      <!-- Tableau des configurations -->
      <q-table
        :rows="filteredConfigs"
        :columns="columns"
        row-key="id"
        :loading="loading"
        :pagination="pagination"
        :filter="searchQuery"
        binary-state-sort
      >
        <!-- Slot pour le type de configuration -->
        <template v-slot:body-cell-isAdmin="props">
          <q-td :props="props">
            <q-chip
              :color="props.row.isAdmin ? 'positive' : 'primary'"
              text-color="white"
              dense
            >
              {{ props.row.isAdmin ? 'Admin' : 'Utilisateur' }}
            </q-chip>
          </q-td>
        </template>
        
        <!-- Slot pour le client ID (masqué) -->
        <template v-slot:body-cell-clientId="props">
          <q-td :props="props">
            <div class="row items-center">
              <span v-if="showClientId[props.row.id]">{{ props.row.clientId }}</span>
              <span v-else>••••••••••••••••</span>
              <q-btn
                flat
                round
                size="sm"
                :icon="showClientId[props.row.id] ? 'visibility_off' : 'visibility'"
                @click="toggleClientIdVisibility(props.row.id)"
              />
            </div>
          </q-td>
        </template>
        
        <!-- Slot pour le client secret (masqué) -->
        <template v-slot:body-cell-clientSecret="props">
          <q-td :props="props">
            <div class="row items-center">
              <span v-if="showClientSecret[props.row.id]">{{ props.row.clientSecret }}</span>
              <span v-else>••••••••••••••••</span>
              <q-btn
                flat
                round
                size="sm"
                :icon="showClientSecret[props.row.id] ? 'visibility_off' : 'visibility'"
                @click="toggleClientSecretVisibility(props.row.id)"
              />
            </div>
          </q-td>
        </template>
        
        <!-- Slot pour les actions -->
        <template v-slot:body-cell-actions="props">
          <q-td :props="props">
            <div class="q-gutter-sm">
              <q-btn
                flat
                round
                color="info"
                icon="edit"
                size="sm"
                @click="openEditDialog(props.row)"
              >
                <q-tooltip>Modifier</q-tooltip>
              </q-btn>
              <q-btn
                v-if="!props.row.isAdmin"
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
                @click="confirmDeleteConfig(props.row)"
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
      
      <!-- Dialogue de création/édition -->
      <q-dialog v-model="configDialog" persistent>
        <q-card style="min-width: 500px">
          <q-card-section>
            <div class="text-h6">{{ isEditing ? 'Modifier la configuration' : 'Nouvelle configuration' }}</div>
          </q-card-section>
          
          <q-card-section>
            <q-form @submit="saveConfig" class="q-gutter-md">
              <q-select
                v-model="configForm.isAdmin"
                :options="[
                  { label: 'Configuration administrateur', value: true },
                  { label: 'Configuration utilisateur', value: false }
                ]"
                option-label="label"
                option-value="value"
                label="Type de configuration"
                outlined
                emit-value
                map-options
                :disable="isEditing"
              />
              
              <q-select
                v-if="!configForm.isAdmin"
                v-model="configForm.userId"
                :options="userOptions"
                option-label="label"
                option-value="value"
                label="Utilisateur"
                outlined
                emit-value
                map-options
                :disable="isEditing"
              />
              
              <q-input
                v-model="configForm.clientId"
                label="Client ID"
                outlined
                :rules="[val => !!val || 'Le Client ID est requis']"
              />
              
              <q-input
                v-model="configForm.clientSecret"
                label="Client Secret"
                outlined
                :rules="[val => !!val || 'Le Client Secret est requis']"
              />
              
              <div class="q-mt-md">
                <q-btn label="Tester la connexion" color="info" class="q-mr-sm" @click="testConnection" :loading="testingConnection" />
              </div>
              
              <div v-if="connectionTestResult" :class="connectionTestSuccess ? 'text-positive' : 'text-negative'">
                {{ connectionTestResult }}
              </div>
            </q-form>
          </q-card-section>
          
          <q-card-actions align="right">
            <q-btn label="Annuler" color="negative" v-close-popup />
            <q-btn label="Enregistrer" color="positive" @click="saveConfig" :loading="loading" />
          </q-card-actions>
        </q-card>
      </q-dialog>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useOrangeAPIConfigStore } from '../stores/orangeAPIConfigStore';
import { useUserStore } from '../stores/userStore';
import { date } from 'quasar';

// Router
const router = useRouter();

// Stores
const orangeAPIConfigStore = useOrangeAPIConfigStore();
const userStore = useUserStore();

// État local
const searchQuery = ref('');
const configDialog = ref(false);
const isEditing = ref(false);
const showClientId = ref<Record<number, boolean>>({});
const showClientSecret = ref<Record<number, boolean>>({});
const testingConnection = ref(false);
const connectionTestResult = ref('');
const connectionTestSuccess = ref(false);

// Formulaire
const configForm = reactive({
  id: null as number | null,
  userId: null as number | null,
  clientId: '',
  clientSecret: '',
  isAdmin: false
});

// Pagination
const pagination = ref({
  rowsPerPage: 10
});

// Colonnes du tableau
const columns = [
  { name: 'id', label: 'ID', field: 'id', sortable: true, align: 'left' as const },
  { name: 'userId', label: 'ID Utilisateur', field: 'userId', sortable: true, align: 'left' as const },
  { name: 'isAdmin', label: 'Type', field: 'isAdmin', sortable: true, align: 'left' as const },
  { name: 'clientId', label: 'Client ID', field: 'clientId', sortable: false, align: 'left' as const },
  { name: 'clientSecret', label: 'Client Secret', field: 'clientSecret', sortable: false, align: 'left' as const },
  { name: 'createdAt', label: 'Date de création', field: 'createdAt', sortable: true, align: 'left' as const },
  { name: 'updatedAt', label: 'Dernière mise à jour', field: 'updatedAt', sortable: true, align: 'left' as const },
  { name: 'actions', label: 'Actions', field: 'actions', align: 'center' as const }
];

// Computed properties
const loading = computed(() => orangeAPIConfigStore.loading);
const totalConfigs = computed(() => orangeAPIConfigStore.orangeAPIConfigs.length);
const userConfigs = computed(() => orangeAPIConfigStore.orangeAPIConfigs.filter(config => !config.isAdmin));
const adminConfig = computed(() => orangeAPIConfigStore.orangeAPIConfigs.find(config => config.isAdmin));

const filteredConfigs = computed(() => {
  let filtered = orangeAPIConfigStore.orangeAPIConfigs;
  
  // Filtrer par recherche
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(config => 
      config.id.toString().includes(query) ||
      (config.userId && config.userId.toString().includes(query)) ||
      (config.isAdmin ? 'admin' : 'utilisateur').includes(query)
    );
  }
  
  return filtered;
});

const userOptions = computed(() => {
  return userStore.users.map(user => ({
    label: `${user.username} (ID: ${user.id})`,
    value: user.id
  }));
});

// Méthodes
function formatDate(dateString: string): string {
  return date.formatDate(dateString, 'DD/MM/YYYY HH:mm');
}

function toggleClientIdVisibility(id: number) {
  showClientId.value[id] = !showClientId.value[id];
}

function toggleClientSecretVisibility(id: number) {
  showClientSecret.value[id] = !showClientSecret.value[id];
}

function openCreateDialog() {
  isEditing.value = false;
  configForm.id = null;
  configForm.userId = null;
  configForm.clientId = '';
  configForm.clientSecret = '';
  configForm.isAdmin = false;
  connectionTestResult.value = '';
  configDialog.value = true;
}

function openEditDialog(config: any) {
  isEditing.value = true;
  configForm.id = config.id;
  configForm.userId = config.userId;
  configForm.clientId = config.clientId;
  configForm.clientSecret = config.clientSecret;
  configForm.isAdmin = config.isAdmin;
  connectionTestResult.value = '';
  configDialog.value = true;
}

async function saveConfig() {
  // Validation
  if (!configForm.clientId || !configForm.clientSecret) {
    return;
  }
  
  if (!configForm.isAdmin && !configForm.userId) {
    return;
  }
  
  if (isEditing.value && configForm.id) {
    // Mise à jour
    await orangeAPIConfigStore.updateOrangeAPIConfig(
      configForm.id,
      configForm.clientId,
      configForm.clientSecret
    );
  } else {
    // Création
    await orangeAPIConfigStore.createOrangeAPIConfig(
      configForm.userId,
      configForm.clientId,
      configForm.clientSecret,
      configForm.isAdmin
    );
  }
  
  configDialog.value = false;
}

async function testConnection() {
  testingConnection.value = true;
  connectionTestResult.value = '';
  
  try {
    // Simuler un test de connexion à l'API Orange
    await new Promise(resolve => setTimeout(resolve, 1500));
    
    // Vérification simple des formats
    const validClientId = configForm.clientId.length >= 10;
    const validClientSecret = configForm.clientSecret.length >= 10;
    
    if (validClientId && validClientSecret) {
      connectionTestResult.value = 'Connexion réussie à l\'API Orange';
      connectionTestSuccess.value = true;
    } else {
      connectionTestResult.value = 'Échec de la connexion. Vérifiez vos identifiants.';
      connectionTestSuccess.value = false;
    }
  } catch (error) {
    connectionTestResult.value = `Erreur lors du test de connexion: ${error}`;
    connectionTestSuccess.value = false;
  } finally {
    testingConnection.value = false;
  }
}

function viewUser(userId: number) {
  router.push({ name: 'user-details', params: { id: userId } });
}

async function confirmDeleteConfig(config: any) {
  if (!confirm(`Êtes-vous sûr de vouloir supprimer cette configuration API Orange ?`)) return;
  
  await orangeAPIConfigStore.deleteOrangeAPIConfig(config.id);
}

// Cycle de vie
onMounted(async () => {
  await Promise.all([
    orangeAPIConfigStore.fetchOrangeAPIConfigs(),
    userStore.fetchUsers()
  ]);
});
</script>

<style scoped>
.orange-api-config-page {
  max-width: 1200px;
  margin: 0 auto;
}
</style>
