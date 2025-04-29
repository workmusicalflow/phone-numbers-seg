<template>
  <div>
    <q-table
      :rows="contacts"
      :columns="columns"
      row-key="id"
      :loading="loading"
      @request="onRequest"
      :rows-per-page-options="[5, 10, 20, 50]"
      flat
      bordered
      class="contacts-table"
      v-model:pagination="paginationModel"
      :filter="filter"
    >
      <!-- Slot pour le contenu -->
      <template v-slot:body="props">
        <q-tr :props="props">
          <q-td key="name" :props="props">
            {{ props.row.name }}
          </q-td>
          <q-td key="phone" :props="props">
            {{ props.row.phoneNumber }}
          </q-td>
          <q-td key="email" :props="props">
            {{ props.row.email }}
          </q-td>
          <q-td key="groups" :props="props">
            <q-chip
              v-for="group in props.row.groups"
              :key="group.id"
              size="sm"
              color="secondary"
              text-color="white"
              class="q-ma-xs"
            >
              {{ group.name }}
            </q-chip>
          </q-td>
          <q-td key="actions" :props="props">
            <div class="row no-wrap justify-end">
              <q-btn
                flat
                round
                color="primary"
                icon="edit"
                @click="onEdit(props.row)"
                size="sm"
              >
                <q-tooltip>Modifier</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                color="negative"
                icon="delete"
                @click="onDelete(props.row)"
                size="sm"
              >
                <q-tooltip>Supprimer</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                color="secondary"
                icon="message"
                @click="onSendSMS(props.row)"
                size="sm"
              >
                <q-tooltip>Envoyer SMS</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                color="info"
                icon="visibility"
                @click="onViewDetails(props.row)"
                size="sm"
              >
                <q-tooltip>Voir les détails</q-tooltip>
              </q-btn>
            </div>
          </q-td>
        </q-tr>
      </template>

      <!-- Slot pour l'état vide -->
      <template v-slot:no-data>
        <div class="full-width row flex-center q-pa-md text-grey-8">
          <q-icon name="sentiment_dissatisfied" size="2em" class="q-mr-sm" />
          <span>Aucun contact trouvé</span>
        </div>
      </template>
    </q-table>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Contact } from '../../types/contact';

// Définition des colonnes du tableau
const columns = [
  {
    name: 'name',
    required: true,
    label: 'Nom',
    align: 'left' as const,
    field: 'name', // Use name field directly
    sortable: true
  },
  {
    name: 'phone',
    required: true,
    label: 'Téléphone',
    align: 'left' as const,
    field: 'phoneNumber',
    sortable: true
  },
  {
    name: 'email',
    required: false,
    label: 'Email',
    align: 'left' as const,
    field: 'email',
    sortable: true
  },
  {
    name: 'groups',
    required: false,
    label: 'Groupes',
    align: 'left' as const,
    field: 'groups',
    sortable: false
  },
  {
    name: 'actions',
    required: true,
    label: 'Actions',
    align: 'right' as const,
    field: 'actions',
    sortable: false
  }
];

const props = defineProps<{
  contacts: Contact[];
  loading: boolean;
  pagination: {
    sortBy: string;
    descending: boolean;
    page: number;
    rowsPerPage: number;
    rowsNumber: number;
  };
  filter?: string;
}>();

const emit = defineEmits<{
  (e: 'request', pagination: any): void;
  (e: 'edit', contact: Contact): void;
  (e: 'delete', contact: Contact): void;
  (e: 'send-sms', contact: Contact): void;
  (e: 'view-details', contact: Contact): void;
}>();

// Modèle pour la pagination
const paginationModel = computed({
  get: () => props.pagination,
  set: (value) => {
    emit('request', value);
  }
});

// Méthodes
const onRequest = (props: any) => {
  emit('request', props.pagination);
};

const onEdit = (contact: Contact) => {
  emit('edit', contact);
};

const onDelete = (contact: Contact) => {
  emit('delete', contact);
};

const onSendSMS = (contact: Contact) => {
  emit('send-sms', contact);
};

const onViewDetails = (contact: Contact) => {
  emit('view-details', contact);
};
</script>

<style scoped>
.contacts-table {
  width: 100%;
}

@media (max-width: 600px) {
  .contacts-table {
    max-height: calc(100vh - 200px);
    overflow-y: auto;
  }
}
</style>
