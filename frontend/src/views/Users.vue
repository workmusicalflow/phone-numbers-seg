<template>
  <div class="users-page">
    <div class="q-pa-md">
      <h1 class="text-h4 q-mb-md">Gestion des Utilisateurs</h1>
      
      <!-- Statistiques -->
      <UserStatistics 
        :totalUsers="totalUsers" 
        :totalSmsCredits="totalSmsCredits" 
      />
      
      <!-- Barre d'actions -->
      <UserActionBar 
        v-model:searchQuery="searchQuery" 
        @create-user="openCreateUserDialog" 
      />
      
      <!-- Tableau des utilisateurs -->
      <UsersTable 
        :users="filteredUsers" 
        :loading="loading" 
        :filter="searchQuery"
        @edit-user="openEditUserDialog"
        @add-credits="openAddCreditsDialog"
        @change-password="openChangePasswordDialog"
        @delete-user="confirmDeleteUser"
      />
      
      <!-- Dialogues -->
      <CreateUserDialog 
        v-model="createUserDialog" 
        :loading="loading"
        @submit="createUser"
      />
      
      <EditUserDialog 
        v-model="editUserDialog" 
        :user="selectedUser"
        :loading="loading"
        @submit="updateUser"
      />
      
      <AddCreditsDialog 
        v-model="addCreditsDialog" 
        :user="selectedUser"
        :loading="loading"
        @submit="addCredits"
      />
      
      <ChangePasswordDialog 
        v-model="changePasswordDialog" 
        :user="selectedUser"
        :loading="loading"
        @submit="changePassword"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useUserStore, User } from '../stores/userStore';

// Composants
import UserStatistics from '../components/users/UserStatistics.vue';
import UserActionBar from '../components/users/UserActionBar.vue';
import UsersTable from '../components/users/UsersTable.vue';
import CreateUserDialog from '../components/users/dialogs/CreateUserDialog.vue';
import EditUserDialog from '../components/users/dialogs/EditUserDialog.vue';
import AddCreditsDialog from '../components/users/dialogs/AddCreditsDialog.vue';
import ChangePasswordDialog from '../components/users/dialogs/ChangePasswordDialog.vue';

// Store
const userStore = useUserStore();

// État local
const searchQuery = ref('');
const createUserDialog = ref(false);
const editUserDialog = ref(false);
const addCreditsDialog = ref(false);
const changePasswordDialog = ref(false);
const selectedUser = ref<User | null>(null);

// Computed properties
const loading = computed(() => userStore.loading);
const filteredUsers = computed(() => {
  console.log('Computing filteredUsers, all users:', userStore.users);
  if (!searchQuery.value) {
    console.log('No search query, returning all users');
    return userStore.users;
  }
  
  const query = searchQuery.value.toLowerCase();
  const filtered = userStore.users.filter(user => 
    user.username.toLowerCase().includes(query) || 
    (user.email && user.email.toLowerCase().includes(query))
  );
  console.log('Filtered users:', filtered);
  return filtered;
});

const totalUsers = computed(() => {
  console.log('Computing totalUsers:', userStore.totalUsers);
  return userStore.totalUsers;
});
const totalSmsCredits = computed(() => {
  console.log('Computing totalSmsCredits:', userStore.totalSmsCredits);
  return userStore.totalSmsCredits;
});

// Méthodes
function openCreateUserDialog() {
  createUserDialog.value = true;
}

function openEditUserDialog(user: User) {
  selectedUser.value = user;
  editUserDialog.value = true;
}

function openAddCreditsDialog(user: User) {
  selectedUser.value = user;
  addCreditsDialog.value = true;
}

function openChangePasswordDialog(user: User) {
  selectedUser.value = user;
  newPassword.value = '';
  confirmPassword.value = '';
  changePasswordDialog.value = true;
}

async function createUser(userData: {
  username: string;
  password: string;
  email: string;
  smsCredit: number;
  smsLimit: number | null;
  isAdmin: boolean;
}) {
  await userStore.createUser(
    userData.username,
    userData.password,
    userData.email || undefined,
    userData.smsCredit,
    userData.smsLimit || undefined,
    userData.isAdmin
  );
  
  createUserDialog.value = false;
}

async function updateUser(userData: {
  id: number;
  email: string;
  smsLimit: number | null;
  isAdmin: boolean;
}) {
  await userStore.updateUser(
    userData.id,
    userData.email || undefined,
    userData.smsLimit || undefined,
    userData.isAdmin
  );
  
  editUserDialog.value = false;
}

async function addCredits(userId: number, credits: number) {
  await userStore.addCredits(userId, credits);
  addCreditsDialog.value = false;
}

async function changePassword(userId: number, password: string) {
  await userStore.changePassword(userId, password);
  changePasswordDialog.value = false;
}

async function confirmDeleteUser(user: User) {
  if (!confirm(`Êtes-vous sûr de vouloir supprimer l'utilisateur ${user.username} ?`)) return;
  
  await userStore.deleteUser(user.id);
}

// Variables pour le changement de mot de passe
const newPassword = ref('');
const confirmPassword = ref('');

// Cycle de vie
onMounted(async () => {
  console.log('Users.vue mounted, fetching users...');
  await userStore.fetchUsers();
  console.log('Initial fetch complete, users:', userStore.users);
  
  // Force a refresh after a short delay
  setTimeout(async () => {
    console.log('Forcing refresh...');
    await userStore.fetchUsers();
    console.log('Refresh complete, users:', userStore.users);
  }, 500);
});
</script>

<style scoped>
.users-page {
  max-width: 1200px;
  margin: 0 auto;
}
</style>
