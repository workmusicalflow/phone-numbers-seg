<template>
  <div class="users-page">
    <div class="q-pa-md">
      <h1 class="text-h4 q-mb-md">Gestion des Utilisateurs</h1>
      
      <!-- Statistiques -->
      <UserStatistics 
        :totalUsers="totalUsers" 
        :totalSmsCredits="totalSmsCredits" 
      />
      
      <!-- Barre d&#39;actions -->
      <UserActionBar 
        v-model:searchQuery="localSearchTerm" 
        @create-user="openCreateUserDialog" 
      />
      
      <!-- Tableau des utilisateurs -->
      <UsersTable 
        :users="users" 
        :loading="loading" 
        :pagination="pagination" 
        @request="onRequest" 
        @edit-user="openEditUserDialog"
        @add-credits="openAddCreditsDialog"
        @change-password="openChangePasswordDialog"
        @delete-user="confirmDeleteUser"
      />

      <!-- Removed :filter prop as filtering is server-side -->
      <!-- Pagination is now handled internally by QTable via @request -->
      
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
import { ref, computed, onMounted, watch } from 'vue'; 
import { useUserStore, User } from '../stores/userStore';

// Composants
// import BasePagination from '../components/BasePagination.vue'; // Removed BasePagination import
import UserStatistics from '../components/users/UserStatistics.vue';
import UserActionBar from '../components/users/UserActionBar.vue';
import UsersTable from '../components/users/UsersTable.vue';
import CreateUserDialog from '../components/users/dialogs/CreateUserDialog.vue';
import EditUserDialog from '../components/users/dialogs/EditUserDialog.vue';
import AddCreditsDialog from '../components/users/dialogs/AddCreditsDialog.vue';
import ChangePasswordDialog from '../components/users/dialogs/ChangePasswordDialog.vue';

// Store
const userStore = useUserStore();

// État local pour les dialogues et l'utilisateur sélectionné
const createUserDialog = ref(false);
const editUserDialog = ref(false);
const addCreditsDialog = ref(false);
const changePasswordDialog = ref(false);
const selectedUser = ref<User | null>(null);
const localSearchTerm = ref(''); // Local ref for debounced search input
const newPassword = ref(''); // Added for change password dialog state
const confirmPassword = ref(''); // Added for change password dialog state

// Computed properties
const loading = computed(() => userStore.loading);
const users = computed(() => userStore.users); // Get users directly from store
const totalUsers = computed(() => userStore.totalCount); // Use totalCount from store
const totalSmsCredits = computed(() => userStore.totalSmsCredits); // Keep this as is

// Pagination computed property for table and pagination component
const pagination = computed(() => ({
  sortBy: 'username', // Default sort, can be updated by onRequest
  descending: false,
  page: userStore.currentPage,
  rowsPerPage: userStore.itemsPerPage,
  rowsNumber: userStore.totalCount // Total rows from the store
}));

// Watch local search term and call debounced store action
watch(localSearchTerm, (newValue) => {
  userStore.searchUsers(newValue || '');
});

// Méthodes pour les dialogues
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

// --- CRUD Action Handlers ---
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
  // Optionally show confirmation dialog first
}

// --- Pagination and Sorting Handler ---
// Type matches the payload emitted by UsersTable (which is the QTable pagination object)
function onRequest(paginationPayload: { page: number; rowsPerPage: number; sortBy: string; descending: boolean }) {
  const { page, rowsPerPage, sortBy, descending } = paginationPayload; // Destructure directly from payload
  console.log('onRequest triggered in Users.vue:', paginationPayload);
  // Update store state which triggers fetchUsers
  userStore.setPage(page);
  userStore.setItemsPerPage(rowsPerPage);
  // TODO: Add sorting to store and backend if needed
  // userStore.setSorting(sortBy, descending);
}

// Removed onPageChange and onItemsPerPageChange as QTable handles this via @request

// Cycle de vie
onMounted(() => {
  console.log('Users.vue mounted, fetching initial users...');
  userStore.fetchUsers(); // Fetch initial data
});
</script>

<style scoped>
.users-page {
  max-width: 1200px;
  margin: 0 auto;
}
</style>
