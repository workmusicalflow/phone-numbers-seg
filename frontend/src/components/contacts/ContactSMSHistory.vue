<template>
  <div class="contact-sms-history">
    <q-table
      :rows="paginatedSmsHistory"
      :columns="columns"
      row-key="id"
      :loading="loading"
      flat
      bordered
      class="sms-history-table"
      v-model:pagination="paginationModel"
    >
      <!-- En-tête du tableau -->
      <template v-slot:top>
        <div class="row full-width items-center q-mb-sm">
          <div class="text-h6 q-pl-md">Historique des SMS</div>
          <q-space />
          <q-badge v-if="totalSent > 0" color="positive" class="q-mr-md">
            {{ totalSent }} envoyé{{ totalSent > 1 ? 's' : '' }}
          </q-badge>
          <q-badge v-if="totalFailed > 0" color="negative" class="q-mr-md">
            {{ totalFailed }} échoué{{ totalFailed > 1 ? 's' : '' }}
          </q-badge>
        </div>
      </template>

      <!-- Slot pour le contenu -->
      <template v-slot:body="props">
        <q-tr :props="props">
          <q-td key="date" :props="props">
            {{ formatDate(props.row.sentAt || props.row.createdAt) }}
          </q-td>
          <q-td key="message" :props="props" class="text-pre-wrap">
            {{ truncateMessage(props.row.message) }}
            <q-tooltip v-if="props.row.message.length > 50" max-width="300px">
              {{ props.row.message }}
            </q-tooltip>
          </q-td>
          <q-td key="status" :props="props">
            <q-badge :color="getStatusColor(props.row.status)">
              {{ getStatusLabel(props.row.status) }}
            </q-badge>
          </q-td>
          <q-td key="actions" :props="props">
            <div class="row no-wrap justify-end">
              <q-btn
                flat
                round
                color="primary"
                icon="visibility"
                @click="onViewDetails(props.row)"
                size="sm"
              >
                <q-tooltip>Détails</q-tooltip>
              </q-btn>
            </div>
          </q-td>
        </q-tr>
      </template>

      <!-- Slot pour l'état vide -->
      <template v-slot:no-data>
        <div class="full-width row flex-center q-pa-md text-grey-8">
          <q-icon name="message" size="2em" class="q-mr-sm" />
          <span>Aucun SMS envoyé à ce contact</span>
        </div>
      </template>

      <!-- Pagination personnalisée -->
      <template v-slot:pagination="scope">
        <div class="row items-center justify-end q-my-xs">
          <div class="col-auto text-body2 text-grey-8 q-mr-sm">
            {{ (currentPage - 1) * itemsPerPage + 1 }}-{{ Math.min(currentPage * itemsPerPage, filteredSmsHistory.length) }} sur {{ filteredSmsHistory.length }}
          </div>
          <q-pagination
            v-model="currentPage"
            :max="Math.ceil(filteredSmsHistory.length / itemsPerPage)"
            :max-pages="6"
            boundary-numbers
            direction-links
            color="primary"
            @update:model-value="updatePage"
          />
          <q-select
            v-model="itemsPerPage"
            :options="[5, 10, 20, 50]"
            label="Par page"
            dense
            outlined
            options-dense
            class="q-ml-md"
            style="min-width: 120px"
          />
        </div>
      </template>
    </q-table>

    <!-- Dialogue de détails -->
    <q-dialog v-model="showDetailsDialog" persistent>
      <q-card style="min-width: 350px; max-width: 600px">
        <q-card-section class="row items-center">
          <div class="text-h6">Détails du SMS</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-separator />

        <q-card-section v-if="selectedSms">
          <q-list>
            <q-item>
              <q-item-section>
                <q-item-label caption>Date d'envoi</q-item-label>
                <q-item-label>{{ formatDate(selectedSms.sentAt || selectedSms.createdAt, true) }}</q-item-label>
              </q-item-section>
            </q-item>

            <q-item>
              <q-item-section>
                <q-item-label caption>Statut</q-item-label>
                <q-item-label>
                  <q-badge :color="getStatusColor(selectedSms.status)">
                    {{ getStatusLabel(selectedSms.status) }}
                  </q-badge>
                </q-item-label>
              </q-item-section>
            </q-item>

            <q-item>
              <q-item-section>
                <q-item-label caption>Message</q-item-label>
                <q-item-label class="text-pre-wrap">{{ selectedSms.message }}</q-item-label>
              </q-item-section>
            </q-item>

            <q-item v-if="selectedSms.deliveredAt">
              <q-item-section>
                <q-item-label caption>Date de livraison</q-item-label>
                <q-item-label>{{ formatDate(selectedSms.deliveredAt, true) }}</q-item-label>
              </q-item-section>
            </q-item>

            <q-item v-if="selectedSms.failedAt">
              <q-item-section>
                <q-item-label caption>Date d'échec</q-item-label>
                <q-item-label>{{ formatDate(selectedSms.failedAt, true) }}</q-item-label>
              </q-item-section>
            </q-item>

            <q-item v-if="selectedSms.errorMessage">
              <q-item-section>
                <q-item-label caption>Message d'erreur</q-item-label>
                <q-item-label class="text-negative">{{ selectedSms.errorMessage }}</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Fermer" color="primary" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Contact, SMSHistory } from '../../types/contact';

// Définition des props
const props = defineProps<{
  contact: Contact;
  loading?: boolean;
}>();

// Définition des émissions
const emit = defineEmits<{
  (e: 'view-details', sms: SMSHistory): void;
}>();

// État local
const currentPage = ref(1);
const itemsPerPage = ref(10);
const showDetailsDialog = ref(false);
const selectedSms = ref<SMSHistory | null>(null);

// Définition des colonnes du tableau
const columns = [
  {
    name: 'date',
    required: true,
    label: 'Date',
    align: 'left' as const,
    field: 'sentAt',
    sortable: true
  },
  {
    name: 'message',
    required: true,
    label: 'Message',
    align: 'left' as const,
    field: 'message',
    sortable: false
  },
  {
    name: 'status',
    required: true,
    label: 'Statut',
    align: 'center' as const,
    field: 'status',
    sortable: true
  },
  {
    name: 'actions',
    required: true,
    label: 'Actions',
    align: 'right' as const,
    sortable: false
  }
];

// Calculer l'historique SMS filtré (pour des filtres futurs)
const filteredSmsHistory = computed(() => {
  return props.contact.smsHistory || [];
});

// Calculer l'historique SMS paginé
const paginatedSmsHistory = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value;
  const end = start + itemsPerPage.value;
  return filteredSmsHistory.value.slice(start, end);
});

// Modèle pour la pagination
const paginationModel = computed(() => ({
  page: currentPage.value,
  rowsPerPage: itemsPerPage.value,
  rowsNumber: filteredSmsHistory.value.length,
  sortBy: 'date',
  descending: true
}));

// Calculer le nombre total de SMS envoyés et échoués
const totalSent = computed(() => {
  return filteredSmsHistory.value.filter(sms => sms.status === 'SENT').length;
});

const totalFailed = computed(() => {
  return filteredSmsHistory.value.filter(sms => sms.status === 'FAILED').length;
});

// Méthodes utilitaires
const formatDate = (dateString: string | undefined | null, withTime: boolean = false) => {
  if (!dateString) return 'N/A';
  
  const date = new Date(dateString);
  
  const options: Intl.DateTimeFormatOptions = {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    ...(withTime && { hour: '2-digit', minute: '2-digit' })
  };
  
  return date.toLocaleDateString('fr-FR', options);
};

const truncateMessage = (message: string) => {
  return message.length > 50 ? message.substring(0, 47) + '...' : message;
};

const getStatusColor = (status: string) => {
  switch (status) {
    case 'SENT':
      return 'positive';
    case 'FAILED':
      return 'negative';
    case 'PENDING':
      return 'warning';
    case 'DELIVERED':
      return 'green';
    default:
      return 'grey';
  }
};

const getStatusLabel = (status: string) => {
  switch (status) {
    case 'SENT':
      return 'Envoyé';
    case 'FAILED':
      return 'Échoué';
    case 'PENDING':
      return 'En attente';
    case 'DELIVERED':
      return 'Livré';
    default:
      return status;
  }
};

// Gestionnaires d'événements
const updatePage = (page: number) => {
  currentPage.value = page;
};

const onViewDetails = (sms: SMSHistory) => {
  selectedSms.value = sms;
  showDetailsDialog.value = true;
  emit('view-details', sms);
};

// Observer les changements de props
watch(() => props.contact, () => {
  // Réinitialiser la pagination lorsque le contact change
  currentPage.value = 1;
});
</script>

<style scoped>
.sms-history-table {
  width: 100%;
}

.text-pre-wrap {
  white-space: pre-wrap;
  word-break: break-word;
}

@media (max-width: 600px) {
  .sms-history-table {
    max-height: calc(100vh - 200px);
    overflow-y: auto;
  }
}
</style>