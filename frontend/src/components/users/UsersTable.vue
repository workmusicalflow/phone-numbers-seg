<template>
  <q-table
    :rows="users"
    :columns="columns"
    row-key="id"
    :loading="loading"
    :pagination="pagination"
    :filter="filter"
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
import { ref, watch } from 'vue';
import { date } from 'quasar';
import { User } from '../../stores/userStore';

const props = defineProps<{
  users: User[];
  loading: boolean;
  filter: string;
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
}>();

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
