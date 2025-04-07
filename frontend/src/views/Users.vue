<template>
  <div class="users-page">
    <div class="q-pa-md">
      <h1 class="text-h4 q-mb-md">Gestion des Utilisateurs</h1>
      
      <!-- Statistiques -->
      <div class="row q-mb-lg">
        <div class="col-12 col-md-4 q-pa-sm">
          <q-card class="bg-primary text-white">
            <q-card-section>
              <div class="text-h6">Nombre d'utilisateurs</div>
              <div class="text-h3">{{ totalUsers }}</div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-12 col-md-4 q-pa-sm">
          <q-card class="bg-secondary text-white">
            <q-card-section>
              <div class="text-h6">Total crédits SMS</div>
              <div class="text-h3">{{ totalSmsCredits }}</div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-12 col-md-4 q-pa-sm">
          <q-card class="bg-accent text-white">
            <q-card-section>
              <div class="text-h6">Moyenne crédits/utilisateur</div>
              <div class="text-h3">{{ averageSmsCredits }}</div>
            </q-card-section>
          </q-card>
        </div>
      </div>
      
      <!-- Barre d'actions -->
      <div class="row q-mb-md items-center justify-between">
        <div class="col-12 col-md-6 q-mb-sm-xs">
          <q-input
            v-model="searchQuery"
            outlined
            dense
            placeholder="Rechercher un utilisateur..."
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
            label="Nouvel utilisateur"
            @click="openCreateUserDialog"
          />
        </div>
      </div>
      
      <!-- Tableau des utilisateurs -->
      <q-table
        :rows="filteredUsers"
        :columns="columns"
        row-key="id"
        :loading="loading"
        :pagination="pagination"
        :filter="searchQuery"
        binary-state-sort
      >
        <!-- Slot pour les actions -->
        <template v-slot:body-cell-actions="props">
          <q-td :props="props">
            <div class="q-gutter-sm">
              <q-btn
                flat
                round
                color="primary"
                icon="edit"
                size="sm"
                @click="openEditUserDialog(props.row)"
              >
                <q-tooltip>Modifier</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                color="green"
                icon="add_circle"
                size="sm"
                @click="openAddCreditsDialog(props.row)"
              >
                <q-tooltip>Ajouter des crédits</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                color="orange"
                icon="key"
                size="sm"
                @click="openChangePasswordDialog(props.row)"
              >
                <q-tooltip>Changer le mot de passe</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                color="negative"
                icon="delete"
                size="sm"
                @click="confirmDeleteUser(props.row)"
              >
                <q-tooltip>Supprimer</q-tooltip>
              </q-btn>
            </div>
          </q-td>
        </template>
        
        <!-- Slot pour le statut des crédits -->
        <template v-slot:body-cell-smsCredit="props">
          <q-td :props="props">
            <div :class="getCreditStatusClass(props.row.smsCredit)">
              {{ props.row.smsCredit }}
            </div>
          </q-td>
        </template>
        
        <!-- Slot pour le statut de la limite -->
        <template v-slot:body-cell-smsLimit="props">
          <q-td :props="props">
            {{ props.row.smsLimit || 'Illimité' }}
          </q-td>
        </template>
        
        <!-- Slot pour la date de création -->
        <template v-slot:body-cell-createdAt="props">
          <q-td :props="props">
            {{ formatDate(props.row.createdAt) }}
          </q-td>
        </template>
      </q-table>
      
      <!-- Dialogue de création d'utilisateur -->
      <q-dialog v-model="createUserDialog" persistent>
        <q-card style="min-width: 350px">
          <q-card-section>
            <div class="text-h6">Nouvel utilisateur</div>
          </q-card-section>
          
          <q-card-section>
            <q-form @submit="createUser" class="q-gutter-md">
              <q-input
                v-model="newUser.username"
                label="Nom d'utilisateur *"
                outlined
                :rules="[val => !!val || 'Le nom d\'utilisateur est requis']"
              />
              
              <q-input
                v-model="newUser.password"
                label="Mot de passe *"
                outlined
                type="password"
                :rules="[val => !!val || 'Le mot de passe est requis', val => val.length >= 8 || 'Le mot de passe doit contenir au moins 8 caractères']"
              />
              
              <q-input
                v-model="newUser.email"
                label="Email"
                outlined
                type="email"
              />
              
              <q-input
                v-model.number="newUser.smsCredit"
                label="Crédits SMS initiaux"
                outlined
                type="number"
                min="0"
              />
              
              <q-input
                v-model.number="newUser.smsLimit"
                label="Limite de SMS"
                outlined
                type="number"
                min="0"
                hint="Laissez vide pour illimité"
              />
              
              <div class="q-mt-md">
                <q-btn label="Annuler" color="negative" v-close-popup />
                <q-btn label="Créer" type="submit" color="primary" class="q-ml-sm" :loading="loading" />
              </div>
            </q-form>
          </q-card-section>
        </q-card>
      </q-dialog>
      
      <!-- Dialogue de modification d'utilisateur -->
      <q-dialog v-model="editUserDialog" persistent>
        <q-card style="min-width: 350px">
          <q-card-section>
            <div class="text-h6">Modifier l'utilisateur</div>
          </q-card-section>
          
          <q-card-section>
            <q-form @submit="updateUser" class="q-gutter-md">
              <q-input
                v-model="editedUser.username"
                label="Nom d'utilisateur"
                outlined
                readonly
              />
              
              <q-input
                v-model="editedUser.email"
                label="Email"
                outlined
                type="email"
              />
              
              <q-input
                v-model.number="editedUser.smsLimit"
                label="Limite de SMS"
                outlined
                type="number"
                min="0"
                hint="Laissez vide pour illimité"
              />
              
              <div class="q-mt-md">
                <q-btn label="Annuler" color="negative" v-close-popup />
                <q-btn label="Mettre à jour" type="submit" color="primary" class="q-ml-sm" :loading="loading" />
              </div>
            </q-form>
          </q-card-section>
        </q-card>
      </q-dialog>
      
      <!-- Dialogue d'ajout de crédits -->
      <q-dialog v-model="addCreditsDialog" persistent>
        <q-card style="min-width: 350px">
          <q-card-section>
            <div class="text-h6">Ajouter des crédits SMS</div>
          </q-card-section>
          
          <q-card-section>
            <q-form @submit="addCredits" class="q-gutter-md">
              <p>Utilisateur: <strong>{{ selectedUser?.username }}</strong></p>
              <p>Crédits actuels: <strong>{{ selectedUser?.smsCredit }}</strong></p>
              
              <q-input
                v-model.number="creditsToAdd"
                label="Nombre de crédits à ajouter *"
                outlined
                type="number"
                min="1"
                :rules="[val => !!val || 'Le nombre de crédits est requis', val => val > 0 || 'Le nombre de crédits doit être positif']"
              />
              
              <div class="q-mt-md">
                <q-btn label="Annuler" color="negative" v-close-popup />
                <q-btn label="Ajouter" type="submit" color="primary" class="q-ml-sm" :loading="loading" />
              </div>
            </q-form>
          </q-card-section>
        </q-card>
      </q-dialog>
      
      <!-- Dialogue de changement de mot de passe -->
      <q-dialog v-model="changePasswordDialog" persistent>
        <q-card style="min-width: 350px">
          <q-card-section>
            <div class="text-h6">Changer le mot de passe</div>
          </q-card-section>
          
          <q-card-section>
            <q-form @submit="changePassword" class="q-gutter-md">
              <p>Utilisateur: <strong>{{ selectedUser?.username }}</strong></p>
              
              <q-input
                v-model="newPassword"
                label="Nouveau mot de passe *"
                outlined
                type="password"
                :rules="[val => !!val || 'Le mot de passe est requis', val => val.length >= 8 || 'Le mot de passe doit contenir au moins 8 caractères']"
              />
              
              <q-input
                v-model="confirmPassword"
                label="Confirmer le mot de passe *"
                outlined
                type="password"
                :rules="[
                  val => !!val || 'La confirmation du mot de passe est requise',
                  val => val === newPassword || 'Les mots de passe ne correspondent pas'
                ]"
              />
              
              <div class="q-mt-md">
                <q-btn label="Annuler" color="negative" v-close-popup />
                <q-btn label="Changer" type="submit" color="primary" class="q-ml-sm" :loading="loading" />
              </div>
            </q-form>
          </q-card-section>
        </q-card>
      </q-dialog>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useUserStore, User } from '../stores/userStore';
import { date } from 'quasar';

// Store
const userStore = useUserStore();

// État local
const searchQuery = ref('');
const createUserDialog = ref(false);
const editUserDialog = ref(false);
const addCreditsDialog = ref(false);
const changePasswordDialog = ref(false);
const selectedUser = ref<User | null>(null);
const newUser = ref({
  username: '',
  password: '',
  email: '',
  smsCredit: 10,
  smsLimit: null as number | null
});
const editedUser = ref({
  id: 0,
  username: '',
  email: '',
  smsLimit: null as number | null
});
const creditsToAdd = ref(0);
const newPassword = ref('');
const confirmPassword = ref('');

// Pagination
const pagination = ref({
  rowsPerPage: 10
});

// Colonnes du tableau
const columns = [
  { name: 'id', label: 'ID', field: 'id', sortable: true, align: 'left' as const },
  { name: 'username', label: 'Nom d\'utilisateur', field: 'username', sortable: true, align: 'left' as const },
  { name: 'email', label: 'Email', field: 'email', sortable: true, align: 'left' as const },
  { name: 'smsCredit', label: 'Crédits SMS', field: 'smsCredit', sortable: true, align: 'left' as const },
  { name: 'smsLimit', label: 'Limite SMS', field: 'smsLimit', sortable: true, align: 'left' as const },
  { name: 'createdAt', label: 'Date de création', field: 'createdAt', sortable: true, align: 'left' as const },
  { name: 'actions', label: 'Actions', field: 'actions', align: 'center' as const }
];

// Computed properties
const loading = computed(() => userStore.loading);
const filteredUsers = computed(() => {
  if (!searchQuery.value) {
    return userStore.users;
  }
  
  const query = searchQuery.value.toLowerCase();
  return userStore.users.filter(user => 
    user.username.toLowerCase().includes(query) || 
    (user.email && user.email.toLowerCase().includes(query))
  );
});

const totalUsers = computed(() => userStore.totalUsers);
const totalSmsCredits = computed(() => userStore.totalSmsCredits);
const averageSmsCredits = computed(() => {
  if (totalUsers.value === 0) return 0;
  return Math.round(totalSmsCredits.value / totalUsers.value);
});

// Méthodes
function formatDate(dateString: string): string {
  return date.formatDate(dateString, 'DD/MM/YYYY HH:mm');
}

function getCreditStatusClass(credits: number): string {
  if (credits <= 0) return 'text-negative';
  if (credits < 10) return 'text-warning';
  return 'text-positive';
}

function openCreateUserDialog() {
  newUser.value = {
    username: '',
    password: '',
    email: '',
    smsCredit: 10,
    smsLimit: null
  };
  createUserDialog.value = true;
}

function openEditUserDialog(user: User) {
  editedUser.value = {
    id: user.id,
    username: user.username,
    email: user.email || '',
    smsLimit: user.smsLimit
  };
  editUserDialog.value = true;
}

function openAddCreditsDialog(user: User) {
  selectedUser.value = user;
  creditsToAdd.value = 0;
  addCreditsDialog.value = true;
}

function openChangePasswordDialog(user: User) {
  selectedUser.value = user;
  newPassword.value = '';
  confirmPassword.value = '';
  changePasswordDialog.value = true;
}

async function createUser() {
  if (!newUser.value.username || !newUser.value.password) return;
  
  await userStore.createUser(
    newUser.value.username,
    newUser.value.password,
    newUser.value.email || undefined,
    newUser.value.smsCredit,
    newUser.value.smsLimit || undefined
  );
  
  createUserDialog.value = false;
}

async function updateUser() {
  if (!editedUser.value.id) return;
  
  await userStore.updateUser(
    editedUser.value.id,
    editedUser.value.email || undefined,
    editedUser.value.smsLimit || undefined
  );
  
  editUserDialog.value = false;
}

async function addCredits() {
  if (!selectedUser.value || !creditsToAdd.value) return;
  
  await userStore.addCredits(selectedUser.value.id, creditsToAdd.value);
  
  addCreditsDialog.value = false;
}

async function changePassword() {
  if (!selectedUser.value || !newPassword.value || newPassword.value !== confirmPassword.value) return;
  
  await userStore.changePassword(selectedUser.value.id, newPassword.value);
  
  changePasswordDialog.value = false;
}

async function confirmDeleteUser(user: User) {
  if (!confirm(`Êtes-vous sûr de vouloir supprimer l'utilisateur ${user.username} ?`)) return;
  
  await userStore.deleteUser(user.id);
}

// Cycle de vie
onMounted(async () => {
  await userStore.fetchUsers();
});
</script>

<style scoped>
.users-page {
  max-width: 1200px;
  margin: 0 auto;
}
</style>
