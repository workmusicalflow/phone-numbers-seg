<template>
  <q-table
    :rows="users"
    :columns="columns"
    row-key="id"
    :loading="loading"
    v-model:pagination="paginationModel"
    @request="onRequestInternal" 
    :rows-per-page-options="[5, 10, 20, 50]"
    :filter="filter"
    binary-state-sort
    flat
    bordered
    class="users-table"
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
            @click="$emit('edit-user', props.row)"
          >
            <q-tooltip>Modifier</q-tooltip>
          </q-btn>
          <q-btn
            flat
            round
            color="green"
            icon="add_circle"
            size="sm"
            @click="$emit('add-credits', props.row)"
          >
            <q-tooltip>Ajouter des crédits</q-tooltip>
          </q-btn>
          <q-btn
            flat
            round
            color="orange"
            icon="key"
            size="sm"
            @click="$emit('change-password', props.row)"
          >
            <q-tooltip>Changer le mot de passe</q-tooltip>
          </q-btn>
          <q-btn
            flat
            round
            color="negative"
            icon="delete"
            size="sm"
            @click="$emit('delete-user', props.row)"
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
    
    <!-- Slot pour le statut admin -->
    <template v-slot:body-cell-isAdmin="props">
      <q-td :props="props">
        <q-icon
          :name="props.row.isAdmin ? 'check_circle' : 'cancel'"
          :color="props.row.isAdmin ? 'positive' : 'negative'"
          size="sm"
        />
      </q-td>
    </template>
    
    <!-- Slot pour la date de création -->
    <template v-slot:body-cell-createdAt="props">
      <q-td :props="props">
        {{ formatDate(props.row.createdAt) }}
      </q-td>
    </template>
  </q-table>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'; // Added computed
import { date } from 'quasar';
import { User } from '../../stores/userStore';

// Define the structure for the pagination prop more explicitly
interface Pagination {
  sortBy: string;
  descending: boolean;
  page: number;
  rowsPerPage: number;
  rowsNumber?: number | undefined; // Total number of rows from the server (optional)
}

const props = defineProps<{
  users: User[];
  loading: boolean;
  pagination: Pagination; // Updated pagination prop type
  filter?: string; // Made filter optional as it might not always be used directly by q-table
}>();

// Log users when they change
watch(() => props.users, (newUsers) => {
  console.log('UsersTable received users:', newUsers);
}, { immediate: true });

const emit = defineEmits<{
  (e: 'edit-user', user: User): void;
  (e: 'add-credits', user: User): void;
  (e: 'change-password', user: User): void;
  (e: 'delete-user', user: User): void;
  (e: 'request', pagination: any): void; // Added request event
}>();

// Computed property for v-model:pagination
const paginationModel = computed({
  get: () => props.pagination,
  set: (value) => {
    // Emit the 'request' event when Quasar tries to update pagination internally
    // The parent component (Users.vue) will handle the update via the store
    emit('request', value);
  }
});

// Internal handler for q-table's @request event
const onRequestInternal = (requestProps: { pagination: Pagination }) => {
  // Emit the request event with the pagination payload from q-table
  emit('request', requestProps.pagination);
};


// Colonnes du tableau
const columns = [
  { name: 'id', label: 'ID', field: 'id', sortable: true, align: 'left' as const },
  { name: 'username', label: 'Nom d\'utilisateur', field: 'username', sortable: true, align: 'left' as const },
  { name: 'email', label: 'Email', field: 'email', sortable: true, align: 'left' as const },
  { name: 'smsCredit', label: 'Crédits SMS', field: 'smsCredit', sortable: true, align: 'left' as const },
  { name: 'smsLimit', label: 'Limite SMS', field: 'smsLimit', sortable: true, align: 'left' as const },
  { name: 'isAdmin', label: 'Admin', field: 'isAdmin', sortable: true, align: 'left' as const },
  { name: 'createdAt', label: 'Date de création', field: 'createdAt', sortable: true, align: 'left' as const },
  { name: 'actions', label: 'Actions', field: 'actions', align: 'center' as const }
];

// Méthodes
function formatDate(dateString: string): string {
  return date.formatDate(dateString, 'DD/MM/YYYY HH:mm');
}

function getCreditStatusClass(credits: number): string {
  if (credits <= 0) return 'text-negative';
  if (credits < 10) return 'text-warning';
  return 'text-positive';
}
</script>

<style scoped>
.users-table {
  width: 100%;
}
</style>
