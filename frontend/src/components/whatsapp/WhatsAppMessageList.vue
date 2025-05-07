<template>
  <div class="whatsapp-message-list">
    <div class="filters q-pa-md">
      <div class="row q-col-gutter-md">
        <div class="col-12 col-md-4">
          <q-input
            v-model="senderFilter"
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
            v-model="typeFilter"
            :options="messageTypeOptions"
            label="Type de message"
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

        <template v-slot:body-cell-sender="props">
          <q-td :props="props">
            <div class="sender-cell">
              {{ formatPhoneNumber(props.row.sender) }}
              <q-tooltip>{{ props.row.sender }}</q-tooltip>
            </div>
          </q-td>
        </template>

        <template v-slot:body-cell-recipient="props">
          <q-td :props="props">
            <div v-if="props.row.recipient" class="recipient-cell">
              {{ formatPhoneNumber(props.row.recipient) }}
              <q-tooltip>{{ props.row.recipient }}</q-tooltip>
            </div>
            <div v-else class="text-grey-7">-</div>
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
            <div v-else-if="props.row.type === 'image' && props.row.mediaUrl" class="media-cell">
              <q-img
                :src="props.row.mediaUrl"
                style="max-width: 100px; max-height: 100px;"
                fit="contain"
              >
                <template v-slot:error>
                  <div class="absolute-full flex flex-center text-grey-7">
                    <q-icon name="image" size="md" />
                  </div>
                </template>
              </q-img>
              <div v-if="props.row.content" class="caption q-mt-xs">
                {{ props.row.content }}
              </div>
            </div>
            <div v-else-if="props.row.type === 'document' && props.row.mediaUrl" class="media-cell">
              <q-icon name="description" size="md" color="primary" />
              <div class="caption q-mt-xs">
                {{ props.row.content || 'Document' }}
              </div>
            </div>
            <div v-else-if="props.row.type === 'audio' && props.row.mediaUrl" class="media-cell">
              <q-icon name="audio_file" size="md" color="primary" />
              <div class="caption q-mt-xs">Audio</div>
            </div>
            <div v-else-if="props.row.type === 'video' && props.row.mediaUrl" class="media-cell">
              <q-icon name="video_file" size="md" color="primary" />
              <div class="caption q-mt-xs">
                {{ props.row.content || 'Vidéo' }}
              </div>
            </div>
            <div v-else-if="props.row.status" class="status-cell">
              <q-chip
                :color="getStatusColor(props.row.status)"
                text-color="white"
                dense
              >
                {{ props.row.status }}
              </q-chip>
            </div>
            <div v-else class="text-grey-7">-</div>
          </q-td>
        </template>

        <template v-slot:body-cell-timestamp="props">
          <q-td :props="props">
            {{ props.row.formattedTimestamp }}
          </q-td>
        </template>

        <template v-slot:body-cell-actions="props">
          <q-td :props="props">
            <q-btn
              v-if="props.row.sender && props.row.type !== 'status'"
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
              v-if="props.row.mediaUrl"
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
          <div class="text-h6">Répondre à {{ formatPhoneNumber(selectedMessage?.sender || '') }}</div>
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
import { useWhatsAppStore, type WhatsAppMessage } from '@/stores/whatsappStore';

const $q = useQuasar();
const whatsAppStore = useWhatsAppStore();

// État des filtres
const senderFilter = ref('');
const typeFilter = ref('');

// État de la pagination
const pagination = ref({
  rowsPerPage: 10,
  page: 1,
  rowsNumber: 0
});

// État du dialogue de réponse
const replyDialogOpen = ref(false);
const selectedMessage = ref<WhatsAppMessage | null>(null);
const replyMessage = ref('');
const sendingReply = ref(false);

// Valeurs calculées
const isLoading = computed(() => whatsAppStore.isLoading);
const messages = computed(() => whatsAppStore.filteredMessages);

// Options pour le select de type de message
const messageTypeOptions = [
  { label: 'Texte', value: 'text' },
  { label: 'Image', value: 'image' },
  { label: 'Document', value: 'document' },
  { label: 'Audio', value: 'audio' },
  { label: 'Vidéo', value: 'video' },
  { label: 'Statut', value: 'status' }
];

// Définition des colonnes du tableau
const columns = [
  {
    name: 'sender',
    required: true,
    label: 'Expéditeur',
    align: 'left',
    field: 'sender',
    sortable: true
  },
  {
    name: 'recipient',
    required: false,
    label: 'Destinataire',
    align: 'left',
    field: 'recipient',
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
    name: 'timestamp',
    required: true,
    label: 'Date',
    align: 'left',
    field: 'formattedTimestamp',
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

function getMessageTypeColor(type: string): string {
  switch (type) {
    case 'text': return 'primary';
    case 'image': return 'green';
    case 'document': return 'blue';
    case 'audio': return 'deep-purple';
    case 'video': return 'red';
    case 'status': return 'grey';
    default: return 'black';
  }
}

function getStatusColor(status: string): string {
  switch (status) {
    case 'sent': return 'blue';
    case 'delivered': return 'green';
    case 'read': return 'deep-purple';
    case 'failed': return 'negative';
    default: return 'grey';
  }
}

// Fonctions d'interaction avec l'API
async function refreshMessages() {
  await whatsAppStore.fetchMessages();
  pagination.value.rowsNumber = whatsAppStore.totalCount;
}

function applyFilters() {
  whatsAppStore.setFilter(senderFilter.value, typeFilter.value);
  pagination.value.page = 1;
}

function onRequest(props: any) {
  const { page, rowsPerPage } = props.pagination;
  
  pagination.value.page = page;
  pagination.value.rowsPerPage = rowsPerPage;
  
  whatsAppStore.setPageSize(rowsPerPage);
  whatsAppStore.setCurrentPage(page);
}

function promptReply(message: WhatsAppMessage) {
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
    const response = await whatsAppStore.sendTextMessage(
      selectedMessage.value.sender,
      replyMessage.value
    );
    
    if (response.success) {
      $q.notify({
        type: 'positive',
        message: 'Message envoyé avec succès'
      });
      replyDialogOpen.value = false;
    } else {
      $q.notify({
        type: 'negative',
        message: `Erreur lors de l'envoi: ${response.error || 'Erreur inconnue'}`
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

function downloadMedia(message: WhatsAppMessage) {
  if (!message.mediaUrl) {
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
  
  .sender-cell,
  .recipient-cell {
    font-family: monospace;
    white-space: nowrap;
  }
  
  .content-cell {
    max-width: 300px;
    white-space: pre-wrap;
    word-break: break-word;
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