<template>
  <div class="whatsapp-message-list">
    <div class="filters q-pa-md">
      <div class="row q-col-gutter-md">
        <div class="col-12 col-md-4">
          <q-input
            v-model="phoneFilter"
            label="Rechercher par numéro"
            outlined
            dense
            clearable
            @update:model-value="applyFilters"
          >
            <template v-slot:append>
              <q-icon name="search" />
            </template>
          </q-input>
        </div>
        <div class="col-12 col-md-4">
          <q-select
            v-model="statusFilter"
            :options="statusOptions"
            label="Statut"
            outlined
            dense
            clearable
            @update:model-value="applyFilters"
          />
        </div>
        <div class="col-12 col-md-4">
          <q-btn color="primary" icon="refresh" label="Actualiser" @click="refreshMessages" />
        </div>
      </div>
    </div>

    <div class="table-container q-pa-md">
      <q-table
        :rows="messages"
        :columns="columns"
        :loading="isLoading"
        row-key="id"
        :pagination="pagination"
        @request="onRequest"
        binary-state-sort
      >
        <template v-slot:loading>
          <q-inner-loading showing>
            <q-spinner size="50px" color="primary" />
          </q-inner-loading>
        </template>

        <template v-slot:body-cell-direction="props">
          <q-td :props="props">
            <q-icon 
              :name="props.row.direction === 'INCOMING' ? 'arrow_downward' : 'arrow_upward'"
              :color="props.row.direction === 'INCOMING' ? 'primary' : 'positive'"
              size="sm"
            >
              <q-tooltip>{{ props.row.direction === 'INCOMING' ? 'Entrant' : 'Sortant' }}</q-tooltip>
            </q-icon>
          </q-td>
        </template>

        <template v-slot:body-cell-phoneNumber="props">
          <q-td :props="props">
            <div class="phone-cell">
              {{ formatPhoneNumber(props.row.phoneNumber) }}
              <q-tooltip>{{ props.row.phoneNumber }}</q-tooltip>
            </div>
          </q-td>
        </template>

        <template v-slot:body-cell-type="props">
          <q-td :props="props">
            <q-chip
              :color="getMessageTypeColor(props.row.type)"
              text-color="white"
              dense
              class="message-type-chip"
            >
              {{ props.row.type }}
            </q-chip>
          </q-td>
        </template>

        <template v-slot:body-cell-content="props">
          <q-td :props="props">
            <div v-if="props.row.type === 'text' && props.row.content" class="content-cell">
              {{ props.row.content }}
            </div>
            <div v-else-if="props.row.type === 'template'" class="template-cell">
              <q-chip dense color="info" text-color="white">
                {{ props.row.templateName || 'Template' }}
              </q-chip>
              <span v-if="props.row.templateLanguage" class="text-grey-7 q-ml-xs">
                ({{ props.row.templateLanguage }})
              </span>
            </div>
            <div v-else-if="props.row.type === 'image' && props.row.mediaId" class="media-cell">
              <q-icon name="image" size="md" color="green" />
              <div v-if="props.row.content" class="caption q-mt-xs">
                {{ props.row.content }}
              </div>
            </div>
            <div v-else-if="props.row.type === 'document' && props.row.mediaId" class="media-cell">
              <q-icon name="description" size="md" color="primary" />
              <div class="caption q-mt-xs">
                {{ props.row.content || 'Document' }}
              </div>
            </div>
            <div v-else-if="props.row.type === 'audio' && props.row.mediaId" class="media-cell">
              <q-icon name="audio_file" size="md" color="deep-purple" />
              <div class="caption q-mt-xs">Audio</div>
            </div>
            <div v-else-if="props.row.type === 'video' && props.row.mediaId" class="media-cell">
              <q-icon name="video_file" size="md" color="red" />
              <div class="caption q-mt-xs">
                {{ props.row.content || 'Vidéo' }}
              </div>
            </div>
            <div v-else class="text-grey-7">-</div>
          </q-td>
        </template>

        <template v-slot:body-cell-status="props">
          <q-td :props="props">
            <q-chip
              :color="getStatusColor(props.row.status)"
              text-color="white"
              dense
            >
              {{ getStatusLabel(props.row.status) }}
            </q-chip>
            <div v-if="props.row.errorMessage" class="text-negative text-caption q-mt-xs">
              {{ props.row.errorMessage }}
            </div>
          </q-td>
        </template>

        <template v-slot:body-cell-timestamp="props">
          <q-td :props="props">
            {{ formatDate(props.row.timestamp) }}
          </q-td>
        </template>

        <template v-slot:body-cell-actions="props">
          <q-td :props="props">
            <q-btn
              v-if="props.row.direction === 'INCOMING'"
              icon="reply"
              color="primary"
              flat
              dense
              @click="promptReply(props.row)"
              :disable="isLoading"
            >
              <q-tooltip>Répondre</q-tooltip>
            </q-btn>
            <q-btn
              v-if="props.row.mediaId"
              icon="download"
              color="secondary"
              flat
              dense
              @click="downloadMedia(props.row)"
              :disable="isLoading"
            >
              <q-tooltip>Télécharger le média</q-tooltip>
            </q-btn>
          </q-td>
        </template>
      </q-table>
    </div>

    <!-- Dialogue de réponse -->
    <q-dialog v-model="replyDialogOpen" persistent>
      <q-card style="min-width: 350px">
        <q-card-section>
          <div class="text-h6">Répondre à {{ formatPhoneNumber(selectedMessage?.phoneNumber || '') }}</div>
        </q-card-section>

        <q-card-section>
          <q-input
            v-model="replyMessage"
            autofocus
            outlined
            type="textarea"
            label="Message"
          />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Annuler" color="negative" v-close-popup />
          <q-btn
            flat
            label="Envoyer"
            color="primary"
            :loading="sendingReply"
            @click="sendReply"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useQuasar } from 'quasar';
import { useWhatsAppStore, type WhatsAppMessageHistory } from '@/stores/whatsappStore';

const $q = useQuasar();
const whatsAppStore = useWhatsAppStore();

// État des filtres
const phoneFilter = ref('');
const statusFilter = ref('');

// État de la pagination
const pagination = ref({
  rowsPerPage: whatsAppStore.pageSize,
  page: whatsAppStore.currentPage,
  rowsNumber: 0
});

// État du dialogue de réponse
const replyDialogOpen = ref(false);
const selectedMessage = ref<WhatsAppMessageHistory | null>(null);
const replyMessage = ref('');
const sendingReply = ref(false);

// Valeurs calculées
const isLoading = computed(() => whatsAppStore.isLoading);
const messages = computed(() => whatsAppStore.paginatedMessages);

// Options pour le select de statut
const statusOptions = [
  { label: 'Envoyé', value: 'sent' },
  { label: 'Livré', value: 'delivered' },
  { label: 'Lu', value: 'read' },
  { label: 'Échoué', value: 'failed' },
  { label: 'Reçu', value: 'received' }
];

// Définition des colonnes du tableau
const columns = [
  {
    name: 'direction',
    required: true,
    label: 'Direction',
    align: 'center',
    field: 'direction',
    sortable: true
  },
  {
    name: 'phoneNumber',
    required: true,
    label: 'Numéro de téléphone',
    align: 'left',
    field: 'phoneNumber',
    sortable: true
  },
  {
    name: 'type',
    required: true,
    label: 'Type',
    align: 'left',
    field: 'type',
    sortable: true
  },
  {
    name: 'content',
    required: true,
    label: 'Contenu',
    align: 'left',
    field: 'content',
    sortable: false
  },
  {
    name: 'status',
    required: true,
    label: 'Statut',
    align: 'left',
    field: 'status',
    sortable: true
  },
  {
    name: 'timestamp',
    required: true,
    label: 'Date',
    align: 'left',
    field: 'timestamp',
    sortable: true
  },
  {
    name: 'actions',
    required: true,
    label: 'Actions',
    align: 'center',
    field: 'actions',
    sortable: false
  }
];

// Fonctions pour le formatage et les couleurs
function formatPhoneNumber(phoneNumber: string): string {
  if (!phoneNumber) return '';
  
  // Si le numéro commence par un "+" et a plus de 10 chiffres
  if (phoneNumber.startsWith('+') && phoneNumber.length > 10) {
    // Format international : +XXX XX XX XX XX XX
    return phoneNumber.substr(0, 4) + ' ' + 
           phoneNumber.substr(4, 2) + ' ' + 
           phoneNumber.substr(6, 2) + ' ' + 
           phoneNumber.substr(8, 2) + ' ' + 
           phoneNumber.substr(10, 2) + ' ' + 
           phoneNumber.substr(12);
  }
  
  // Sinon, retourner tel quel
  return phoneNumber;
}

function formatDate(dateString: string): string {
  const date = new Date(dateString);
  const now = new Date();
  const diffTime = Math.abs(now.getTime() - date.getTime());
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  
  if (diffDays === 0) {
    return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
  } else if (diffDays === 1) {
    return 'Hier ' + date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
  } else if (diffDays < 7) {
    return date.toLocaleDateString('fr-FR', { weekday: 'long' }) + ' ' + 
           date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
  } else {
    return date.toLocaleDateString('fr-FR') + ' ' + 
           date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
  }
}

function getMessageTypeColor(type: string): string {
  switch (type) {
    case 'text': return 'primary';
    case 'image': return 'green';
    case 'document': return 'blue';
    case 'audio': return 'deep-purple';
    case 'video': return 'red';
    case 'template': return 'info';
    default: return 'grey';
  }
}

function getStatusColor(status: string): string {
  switch (status) {
    case 'sent': return 'blue';
    case 'delivered': return 'green';
    case 'read': return 'deep-purple';
    case 'failed': return 'negative';
    case 'received': return 'primary';
    default: return 'grey';
  }
}

function getStatusLabel(status: string): string {
  switch (status) {
    case 'sent': return 'Envoyé';
    case 'delivered': return 'Livré';
    case 'read': return 'Lu';
    case 'failed': return 'Échoué';
    case 'received': return 'Reçu';
    default: return status;
  }
}

// Fonctions d'interaction avec le store
async function refreshMessages() {
  await whatsAppStore.fetchMessageHistory();
  pagination.value.rowsNumber = whatsAppStore.totalCount;
}

function applyFilters() {
  whatsAppStore.setFilters(phoneFilter.value, statusFilter.value);
  pagination.value.page = 1;
}

function onRequest(props: any) {
  const { page, rowsPerPage } = props.pagination;
  
  pagination.value.page = page;
  pagination.value.rowsPerPage = rowsPerPage;
  
  whatsAppStore.setPageSize(rowsPerPage);
  whatsAppStore.setCurrentPage(page);
}

function promptReply(message: WhatsAppMessageHistory) {
  selectedMessage.value = message;
  replyMessage.value = '';
  replyDialogOpen.value = true;
}

async function sendReply() {
  if (!selectedMessage.value || !replyMessage.value.trim()) {
    return;
  }
  
  sendingReply.value = true;
  
  try {
    const response = await whatsAppStore.sendMessage({
      recipient: selectedMessage.value.phoneNumber,
      type: 'text',
      content: replyMessage.value
    });
    
    if (response) {
      $q.notify({
        type: 'positive',
        message: 'Message envoyé avec succès'
      });
      replyDialogOpen.value = false;
      await refreshMessages();
    } else {
      $q.notify({
        type: 'negative',
        message: whatsAppStore.error || 'Erreur lors de l\'envoi du message'
      });
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erreur lors de l\'envoi du message'
    });
  } finally {
    sendingReply.value = false;
  }
}

function downloadMedia(message: WhatsAppMessageHistory) {
  if (!message.mediaId) {
    return;
  }
  
  // Créer un lien de téléchargement
  // Note: Dans une implémentation réelle, vous devriez avoir un
  // endpoint qui sert de proxy pour télécharger le media depuis l'API Meta
  $q.notify({
    type: 'info',
    message: 'Téléchargement non implémenté dans cette démo'
  });
}

// Initialisation
onMounted(() => {
  refreshMessages();
});
</script>

<style lang="scss" scoped>
.whatsapp-message-list {
  .filters {
    background-color: #fff;
    border-radius: 4px;
    box-shadow: 0 1px 5px rgba(0, 0, 0, 0.2);
    margin-bottom: 16px;
  }
  
  .table-container {
    background-color: #fff;
    border-radius: 4px;
    box-shadow: 0 1px 5px rgba(0, 0, 0, 0.2);
  }
  
  .message-type-chip {
    min-width: 70px;
    justify-content: center;
  }
  
  .phone-cell {
    font-family: monospace;
    white-space: nowrap;
  }
  
  .content-cell {
    max-width: 300px;
    white-space: pre-wrap;
    word-break: break-word;
  }
  
  .template-cell {
    display: flex;
    align-items: center;
  }
  
  .media-cell {
    display: flex;
    flex-direction: column;
    align-items: center;
    
    .caption {
      font-size: 0.8rem;
      color: #666;
      max-width: 100px;
      text-align: center;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
  }
}
</style>