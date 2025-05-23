<template>
  <div class="whatsapp-message-list">
    <div class="filters q-pa-md">
      <div class="row q-col-gutter-md">
        <div class="col-12 col-md-3">
          <q-input
            v-model="filters.phoneNumber"
            label="Rechercher par numéro"
            outlined
            dense
            clearable
            debounce="500"
            @update:model-value="resetPageAndFetch"
          >
            <template v-slot:prepend>
              <q-icon name="search" />
            </template>
          </q-input>
        </div>
        <div class="col-12 col-md-2">
          <q-select
            v-model="filters.status"
            :options="statusOptions"
            label="Statut"
            outlined
            dense
            clearable
            emit-value
            map-options
            @update:model-value="resetPageAndFetch"
          />
        </div>
        <div class="col-12 col-md-2">
          <q-select
            v-model="filters.direction"
            :options="directionOptions"
            label="Direction"
            outlined
            dense
            clearable
            emit-value
            map-options
            @update:model-value="resetPageAndFetch"
          />
        </div>
        <div class="col-12 col-md-3">
          <q-input
            v-model="formattedDateFilter"
            label="Filtrer par date"
            outlined
            dense
            readonly
            clearable
            @clear="filters.date = ''; resetPageAndFetch()"
          >
            <template v-slot:append>
              <q-icon name="event" class="cursor-pointer">
                <q-popup-proxy cover transition-show="scale" transition-hide="scale" ref="datePopup">
                  <q-date 
                    v-model="filters.date" 
                    mask="YYYY-MM-DD" 
                    @update:model-value="onDateSelected"
                  >
                    <div class="row items-center justify-end">
                      <q-btn v-close-popup label="Fermer" color="primary" flat />
                    </div>
                  </q-date>
                </q-popup-proxy>
              </q-icon>
            </template>
          </q-input>
        </div>
        <div class="col-12 col-md-2 flex items-center">
          <q-btn 
            color="primary" 
            icon="refresh" 
            label="Actualiser" 
            @click="fetchMessages" 
            :loading="isLoading"
          />
          <q-separator vertical inset class="q-mx-md" />
          <q-btn
            flat
            round
            icon="file_download"
            color="primary"
            @click="exportMessages"
            :disable="!totalCount"
          >
            <q-tooltip>Exporter tous les messages filtrés</q-tooltip>
          </q-btn>
        </div>
      </div>
      
      <!-- Barre de résumé des filtres appliqués -->
      <div v-if="hasActiveFilters" class="row q-mt-md">
        <!-- Badge spécial pour aujourd'hui -->
        <q-badge v-if="filters.date === new Date().toISOString().split('T')[0]" 
                 color="accent" 
                 class="q-mr-sm q-mt-xs">
          Filtré sur aujourd'hui ({{ totalCount }} messages)
        </q-badge>
        
        <q-chip 
          v-for="filter in activeFilters" 
          :key="filter.type"
          removable
          @remove="clearFilter(filter.type)"
          color="primary"
          text-color="white"
        >
          {{ filter.label }}: {{ filter.value }}
        </q-chip>
        <q-btn 
          v-if="activeFilters.length > 1"
          flat 
          label="Effacer tout" 
          color="negative"
          size="sm"
          @click="clearAllFilters"
          class="q-ml-md"
        />
      </div>
    </div>

    <div class="stats-bar q-pa-md bg-grey-2">
      <div class="row q-col-gutter-md text-center">
        <div class="col">
          <div class="text-h6">{{ stats.total }}</div>
          <div class="text-caption">Total</div>
        </div>
        <div class="col">
          <div class="text-h6 text-primary">{{ stats.incoming }}</div>
          <div class="text-caption">Reçus</div>
        </div>
        <div class="col">
          <div class="text-h6 text-positive">{{ stats.outgoing }}</div>
          <div class="text-caption">Envoyés</div>
        </div>
        <div class="col">
          <div class="text-h6 text-green">{{ stats.delivered }}</div>
          <div class="text-caption">Délivrés</div>
        </div>
        <div class="col">
          <div class="text-h6 text-info">{{ stats.read }}</div>
          <div class="text-caption">Lus</div>
        </div>
        <div class="col">
          <div class="text-h6 text-negative">{{ stats.failed }}</div>
          <div class="text-caption">Échoués</div>
        </div>
      </div>
    </div>

    <div class="table-container q-pa-md">
      <q-table
        ref="tableRef"
        :rows="rows"
        :columns="columns"
        :loading="isLoading"
        row-key="id"
        v-model:pagination="pagination"
        @request="onRequest"
        :rows-per-page-options="[10, 20, 50, 100]"
        binary-state-sort
      >
        <template v-slot:loading>
          <q-inner-loading showing>
            <q-spinner size="50px" color="primary" />
          </q-inner-loading>
        </template>

        <!-- Templates de cellules identiques à la version précédente -->
        <template v-slot:body-cell-direction="props">
          <q-td :props="props">
            <q-icon 
              :name="props.row.direction === 'INCOMING' ? 'south' : 'north'"
              :color="props.row.direction === 'INCOMING' ? 'primary' : 'positive'"
              size="sm"
            >
              <q-tooltip>{{ props.row.direction === 'INCOMING' ? 'Message entrant' : 'Message sortant' }}</q-tooltip>
            </q-icon>
          </q-td>
        </template>

        <template v-slot:body-cell-phoneNumber="props">
          <q-td :props="props">
            <div class="phone-cell cursor-pointer" @click="filterByPhone(props.row.phoneNumber)">
              {{ formatPhoneNumber(props.row.phoneNumber) }}
              <q-tooltip>
                {{ props.row.phoneNumber }}
                <br>
                <small>Cliquez pour filtrer</small>
              </q-tooltip>
            </div>
          </q-td>
        </template>

        <template v-slot:body-cell-type="props">
          <q-td :props="props">
            <q-chip
              :color="getMessageTypeColor(props.row.type)"
              text-color="white"
              dense
              size="sm"
              class="message-type-chip"
            >
              <q-icon :name="getMessageTypeIcon(props.row.type)" size="xs" class="q-mr-xs" />
              {{ getMessageTypeLabel(props.row.type) }}
            </q-chip>
          </q-td>
        </template>

        <template v-slot:body-cell-content="props">
          <q-td :props="props">
            <div v-if="props.row.type === 'text' && props.row.content" class="content-cell">
              {{ truncateContent(props.row.content) }}
              <q-tooltip v-if="props.row.content.length > 50" class="bg-dark">
                {{ props.row.content }}
              </q-tooltip>
            </div>
            <div v-else-if="props.row.type === 'template'" class="template-cell">
              <q-chip dense color="info" text-color="white" size="sm">
                <q-icon name="description" size="xs" class="q-mr-xs" />
                {{ props.row.templateName || 'Template' }}
              </q-chip>
              <span v-if="props.row.templateLanguage" class="text-grey-7 q-ml-xs">
                ({{ props.row.templateLanguage }})
              </span>
            </div>
            <div v-else-if="['image', 'video', 'audio', 'document'].includes(props.row.type)" class="media-cell">
              <q-chip 
                :color="getMessageTypeColor(props.row.type)" 
                text-color="white" 
                dense 
                size="sm"
              >
                <q-icon :name="getMessageTypeIcon(props.row.type)" size="xs" class="q-mr-xs" />
                {{ getMessageTypeLabel(props.row.type) }}
              </q-chip>
              <div v-if="props.row.content" class="caption q-mt-xs text-grey-7">
                {{ truncateContent(props.row.content, 30) }}
              </div>
            </div>
            <div v-else class="text-grey-6">
              <em>Aucun contenu</em>
            </div>
          </q-td>
        </template>

        <template v-slot:body-cell-status="props">
          <q-td :props="props">
            <q-chip
              :color="getStatusColor(props.row.status)"
              text-color="white"
              dense
              size="sm"
            >
              <q-icon 
                :name="getStatusIcon(props.row.status)" 
                size="xs" 
                class="q-mr-xs" 
              />
              {{ getStatusLabel(props.row.status) }}
            </q-chip>
            <div v-if="props.row.errorMessage" class="text-negative text-caption q-mt-xs">
              <q-icon name="error_outline" size="xs" />
              {{ props.row.errorMessage }}
            </div>
          </q-td>
        </template>

        <template v-slot:body-cell-timestamp="props">
          <q-td :props="props">
            <div class="timestamp-cell">
              <div class="text-weight-medium">{{ formatTime(props.row.timestamp) }}</div>
              <div class="text-caption text-grey-6">{{ formatDateOnly(props.row.timestamp) }}</div>
            </div>
          </q-td>
        </template>

        <template v-slot:body-cell-actions="props">
          <q-td :props="props">
            <q-btn-group flat>
              <q-btn
                v-if="props.row.direction === 'INCOMING' && canReply(props.row)"
                icon="reply"
                color="primary"
                size="sm"
                @click="promptReply(props.row)"
                :disable="isLoading"
              >
                <q-tooltip>Répondre dans la fenêtre de 24h</q-tooltip>
              </q-btn>
              <q-btn
                v-if="props.row.mediaId"
                icon="download"
                color="secondary"
                size="sm"
                @click="downloadMedia(props.row)"
                :disable="isLoading"
              >
                <q-tooltip>Télécharger le média</q-tooltip>
              </q-btn>
              <q-btn
                icon="info"
                color="grey"
                size="sm"
                @click="showMessageDetails(props.row)"
              >
                <q-tooltip>Détails du message</q-tooltip>
              </q-btn>
            </q-btn-group>
          </q-td>
        </template>
      </q-table>
    </div>

    <!-- Dialogues -->
    <q-dialog v-model="replyDialogOpen" persistent>
      <q-card style="min-width: 450px">
        <q-card-section>
          <div class="text-h6">Répondre à {{ formatPhoneNumber(selectedMessage?.phoneNumber || '') }}</div>
          <div class="text-caption text-warning q-mt-sm">
            <q-icon name="warning" />
            Vous devez répondre dans les 24h suivant le dernier message reçu
          </div>
        </q-card-section>

        <q-card-section>
          <q-input
            v-model="replyMessage"
            autofocus
            outlined
            type="textarea"
            label="Message"
            :rules="[val => !!val || 'Le message est requis']"
            counter
            maxlength="1000"
          />
          <div class="text-caption text-grey-6 q-mt-sm">
            Caractères : {{ replyMessage.length }} / 1000
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Annuler" color="negative" v-close-popup />
          <q-btn
            unelevated
            label="Envoyer"
            color="primary"
            :loading="sendingReply"
            :disable="!replyMessage.trim()"
            @click="sendReply"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="detailsDialogOpen">
      <q-card style="min-width: 500px; max-width: 800px">
        <q-card-section>
          <div class="text-h6">Détails du message</div>
        </q-card-section>

        <q-card-section v-if="selectedMessage">
          <q-list>
            <q-item>
              <q-item-section>
                <q-item-label overline>ID WhatsApp</q-item-label>
                <q-item-label>{{ selectedMessage.wabaMessageId || 'N/A' }}</q-item-label>
              </q-item-section>
            </q-item>
            <q-item>
              <q-item-section>
                <q-item-label overline>Numéro de téléphone</q-item-label>
                <q-item-label>{{ selectedMessage.phoneNumber }}</q-item-label>
              </q-item-section>
            </q-item>
            <q-item>
              <q-item-section>
                <q-item-label overline>Direction</q-item-label>
                <q-item-label>{{ selectedMessage.direction }}</q-item-label>
              </q-item-section>
            </q-item>
            <q-item>
              <q-item-section>
                <q-item-label overline>Type</q-item-label>
                <q-item-label>{{ selectedMessage.type }}</q-item-label>
              </q-item-section>
            </q-item>
            <q-item>
              <q-item-section>
                <q-item-label overline>Statut</q-item-label>
                <q-item-label>{{ selectedMessage.status }}</q-item-label>
              </q-item-section>
            </q-item>
            <q-item v-if="selectedMessage.content">
              <q-item-section>
                <q-item-label overline>Contenu</q-item-label>
                <q-item-label class="pre-wrap">{{ selectedMessage.content }}</q-item-label>
              </q-item-section>
            </q-item>
            <q-item>
              <q-item-section>
                <q-item-label overline>Date d'envoi</q-item-label>
                <q-item-label>{{ formatFullDate(selectedMessage.timestamp) }}</q-item-label>
              </q-item-section>
            </q-item>
            <q-item>
              <q-item-section>
                <q-item-label overline>Date de création</q-item-label>
                <q-item-label>{{ formatFullDate(selectedMessage.createdAt) }}</q-item-label>
              </q-item-section>
            </q-item>
            <q-item v-if="selectedMessage.errorCode">
              <q-item-section>
                <q-item-label overline class="text-negative">Code d'erreur</q-item-label>
                <q-item-label>{{ selectedMessage.errorCode }}</q-item-label>
              </q-item-section>
            </q-item>
            <q-item v-if="selectedMessage.errorMessage">
              <q-item-section>
                <q-item-label overline class="text-negative">Message d'erreur</q-item-label>
                <q-item-label>{{ selectedMessage.errorMessage }}</q-item-label>
              </q-item-section>
            </q-item>
            <q-item v-if="selectedMessage.conversationId">
              <q-item-section>
                <q-item-label overline>ID de conversation</q-item-label>
                <q-item-label>{{ selectedMessage.conversationId }}</q-item-label>
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
import { ref, onMounted, computed, onUnmounted, watch } from 'vue';
import { useQuasar, QTableProps } from 'quasar';
import { useWhatsAppStore, type WhatsAppMessageHistory } from '../../stores/whatsappStore';
import { formatPhoneNumber, formatTime, formatDateOnly, formatFullDate } from '../../utils/formatters';
import { MESSAGE_CONSTANTS } from '../../constants/whatsapp';

const $q = useQuasar();
const whatsAppStore = useWhatsAppStore();

// Props avec valeurs par défaut
const props = withDefaults(defineProps<{
  autoRefresh?: boolean;
  refreshInterval?: number;
}>(), {
  autoRefresh: true,
  refreshInterval: 30000
});

// Refs
const tableRef = ref();
const datePopup = ref();

// État des filtres
const filters = ref({
  phoneNumber: '',
  status: '',
  direction: '',
  date: ''
});

// État de la pagination serveur
const pagination = ref({
  sortBy: 'timestamp',
  descending: true,
  page: 1,
  rowsPerPage: 20,
  rowsNumber: 0
});

// Type pour les statistiques
interface MessageStats {
  total: number;
  incoming: number;
  outgoing: number;
  delivered: number;
  read: number;
  failed: number;
}

// État local
const rows = ref<WhatsAppMessageHistory[]>([]);
const stats = ref<MessageStats>({
  total: 0,
  incoming: 0,
  outgoing: 0,
  delivered: 0,
  read: 0,
  failed: 0
});
const isLoading = ref(false);
const totalCount = ref(0);

// État des dialogues
const replyDialogOpen = ref(false);
const detailsDialogOpen = ref(false);
const selectedMessage = ref<WhatsAppMessageHistory | null>(null);
const replyMessage = ref('');
const sendingReply = ref(false);

// Timer pour le rafraîchissement automatique
let refreshTimer: number | null = null;

// Computed
const formattedDateFilter = computed(() => {
  if (!filters.value.date) return '';
  return formatDateOnly(filters.value.date);
});

const hasActiveFilters = computed(() => {
  return Object.values(filters.value).some(v => !!v);
});

const activeFilters = computed(() => {
  const filterList = [];
  
  if (filters.value.phoneNumber) {
    filterList.push({ 
      type: 'phoneNumber', 
      label: 'Numéro', 
      value: filters.value.phoneNumber 
    });
  }
  
  if (filters.value.status) {
    filterList.push({ 
      type: 'status', 
      label: 'Statut', 
      value: statusOptions.find(opt => opt.value === filters.value.status)?.label || filters.value.status 
    });
  }
  
  if (filters.value.direction) {
    filterList.push({ 
      type: 'direction', 
      label: 'Direction', 
      value: directionOptions.find(opt => opt.value === filters.value.direction)?.label || filters.value.direction 
    });
  }
  
  if (filters.value.date) {
    filterList.push({ 
      type: 'date', 
      label: 'Date', 
      value: formattedDateFilter.value
    });
  }
  
  return filterList;
});

// Options - Importées depuis des constantes
const statusOptions = MESSAGE_CONSTANTS.statusOptions;
const directionOptions = MESSAGE_CONSTANTS.directionOptions;

// Colonnes de la table
const columns: QTableProps['columns'] = [
  {
    name: 'direction',
    required: true,
    label: '',
    align: 'center',
    field: 'direction',
    sortable: true,
    style: 'width: 40px'
  },
  {
    name: 'phoneNumber',
    required: true,
    label: 'Numéro',
    align: 'left',
    field: 'phoneNumber',
    sortable: true
  },
  {
    name: 'type',
    required: true,
    label: 'Type',
    align: 'center',
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
    align: 'center',
    field: 'status',
    sortable: true
  },
  {
    name: 'timestamp',
    required: true,
    label: 'Date/Heure',
    align: 'center',
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

// Actions principales
async function fetchMessages(props?: any) {
  isLoading.value = true;
  
  try {
    // Log filters before sending
    console.log('[Component] Current filters:', filters.value);
    console.log('[Component] Phone filter:', filters.value.phoneNumber);
    console.log('[Component] Phone filter type:', typeof filters.value.phoneNumber);
    
    // Préparation des paramètres pour l'API
    const params = {
      page: props?.pagination?.page || pagination.value.page,
      limit: props?.pagination?.rowsPerPage || pagination.value.rowsPerPage,
      sortBy: props?.pagination?.sortBy || pagination.value.sortBy,
      descending: props?.pagination?.descending ?? pagination.value.descending,
      filters: {
        phoneNumber: filters.value.phoneNumber || '',
        status: filters.value.status || '',
        direction: filters.value.direction || '',
        date: filters.value.date || ''
      }
    };
    
    console.log('Fetching messages with params:', params);
    
    // Appel API
    const response = await whatsAppStore.fetchMessagesPaginated(params);
    
    console.log('Response received:', response);
    
    if (response) {
      rows.value = response.data || [];
      stats.value = response.stats as MessageStats || {
        total: 0,
        incoming: 0,
        outgoing: 0,
        delivered: 0,
        read: 0,
        failed: 0
      };
      totalCount.value = response.totalCount || 0;
      
      // Mise à jour de la pagination
      pagination.value = {
        ...pagination.value,
        page: params.page,
        rowsPerPage: params.limit,
        sortBy: params.sortBy,
        descending: params.descending,
        rowsNumber: response.totalCount || 0
      };
    }
  } catch (error: unknown) {
    console.error('Error fetching messages:', error);
    
    let errorMessage = 'Erreur lors du chargement des messages';
    if (error instanceof Error) {
      errorMessage = `${errorMessage}: ${error.message}`;
    }
    
    $q.notify({
      type: 'negative',
      message: errorMessage,
      position: 'top'
    });
  } finally {
    isLoading.value = false;
  }
}

// Gestionnaire de requête de la table
function onRequest(props: any) {
  console.log('onRequest called with props:', props);
  fetchMessages(props);
}

// Gestion des filtres
function resetPageAndFetch() {
  pagination.value.page = 1;
  fetchMessages();
}

function onDateSelected(date: string) {
  console.log('[WhatsApp Component] Date selected:', date);
  console.log('[WhatsApp Component] Date type:', typeof date);
  console.log('[WhatsApp Component] Current date:', new Date().toISOString().split('T')[0]);
  console.log('[WhatsApp Component] Is today?', date === new Date().toISOString().split('T')[0]);
  
  filters.value.date = date;
  datePopup.value?.hide();
  resetPageAndFetch();
}

function clearFilter(type: string) {
  filters.value[type as keyof typeof filters.value] = '';
  resetPageAndFetch();
}

function clearAllFilters() {
  filters.value = {
    phoneNumber: '',
    status: '',
    direction: '',
    date: ''
  };
  resetPageAndFetch();
}

function filterByPhone(phone: string) {
  filters.value.phoneNumber = phone;
  resetPageAndFetch();
}

// Export des messages
async function exportMessages() {
  try {
    // Appel API pour récupérer tous les messages filtrés
    const response = await whatsAppStore.exportFilteredMessages(filters.value);
    
    if (response) {
      const csvContent = generateCSV(response.data);
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      const url = URL.createObjectURL(blob);
      
      link.setAttribute('href', url);
      link.setAttribute('download', `whatsapp_messages_${new Date().toISOString().split('T')[0]}.csv`);
      link.style.visibility = 'hidden';
      
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      
      $q.notify({
        type: 'positive',
        message: 'Messages exportés avec succès',
        position: 'top'
      });
    }
  } catch (error: unknown) {
    let errorMessage = 'Erreur lors de l\'export';
    if (error instanceof Error) {
      errorMessage = `${errorMessage}: ${error.message}`;
    }
    
    $q.notify({
      type: 'negative',
      message: errorMessage,
      position: 'top'
    });
  }
}

// Utilitaires (utilisant les fonctions importées)
function truncateContent(content: string, maxLength: number = 50): string {
  if (content.length <= maxLength) return content;
  return content.substring(0, maxLength) + '...';
}

function canReply(message: WhatsAppMessageHistory): boolean {
  const messageDate = new Date(message.timestamp);
  const now = new Date();
  const hoursDiff = (now.getTime() - messageDate.getTime()) / (1000 * 60 * 60);
  return hoursDiff <= 24;
}

// Gestion des messages
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
      phoneNumber: selectedMessage.value.phoneNumber,
      type: 'text',
      content: replyMessage.value
    });
    
    if (response) {
      $q.notify({
        type: 'positive',
        message: 'Message envoyé avec succès',
        position: 'top'
      });
      replyDialogOpen.value = false;
      await fetchMessages();
    } else {
      $q.notify({
        type: 'negative',
        message: whatsAppStore.error || 'Erreur lors de l\'envoi du message',
        position: 'top'
      });
    }
  } catch (error: unknown) {
    let errorMessage = 'Erreur lors de l\'envoi du message';
    if (error instanceof Error) {
      errorMessage = `${errorMessage}: ${error.message}`;
    }
    
    $q.notify({
      type: 'negative',
      message: errorMessage,
      position: 'top'
    });
  } finally {
    sendingReply.value = false;
  }
}

function showMessageDetails(message: WhatsAppMessageHistory) {
  selectedMessage.value = message;
  detailsDialogOpen.value = true;
}

function downloadMedia(message: WhatsAppMessageHistory) {
  if (!message.mediaId) {
    return;
  }
  
  // Appel à l'API pour télécharger le média
  whatsAppStore.downloadMedia(message.mediaId).then(url => {
    window.open(url, '_blank');
  }).catch(() => {
    $q.notify({
      type: 'negative',
      message: 'Erreur lors du téléchargement',
      position: 'top'
    });
  });
}

// CSV Generation
function generateCSV(messages: WhatsAppMessageHistory[]): string {
  const headers = [
    'Date/Heure',
    'Direction',
    'Numéro',
    'Type',
    'Contenu',
    'Statut',
    'ID WhatsApp'
  ];
  
  const rows = messages.map(msg => [
    formatFullDate(msg.timestamp),
    msg.direction,
    msg.phoneNumber,
    msg.type,
    msg.content ? `"${msg.content.replace(/"/g, '""')}"` : '',
    msg.status,
    msg.wabaMessageId || ''
  ]);
  
  return [headers, ...rows]
    .map(row => row.map(cell => typeof cell === 'string' && cell.includes(',') ? `"${cell}"` : cell).join(','))
    .join('\n');
}

// Fonctions utilitaires pour les styles (importées depuis MESSAGE_CONSTANTS)
const { getMessageTypeColor, getMessageTypeIcon, getMessageTypeLabel, getStatusColor, getStatusIcon, getStatusLabel } = MESSAGE_CONSTANTS;

// Lifecycle
onMounted(async () => {
  console.log('Component mounted');
  
  // Charger les messages initiaux
  await fetchMessages();
  
  // Configuration du rafraîchissement automatique
  if (props.autoRefresh) {
    refreshTimer = window.setInterval(() => {
      fetchMessages();
    }, props.refreshInterval);
  }
});

onUnmounted(() => {
  // Nettoyer le timer
  if (refreshTimer) {
    clearInterval(refreshTimer);
  }
});
</script>

<style lang="scss" scoped>
.whatsapp-message-list {
  .filters {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }
  
  .stats-bar {
    border-radius: 8px;
    margin-bottom: 16px;
  }
  
  .table-container {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }
  
  .message-type-chip {
    min-width: 80px;
    justify-content: center;
  }
  
  .phone-cell {
    font-family: 'Roboto Mono', monospace;
    font-size: 0.9em;
    
    &:hover {
      color: $primary;
      text-decoration: underline;
    }
  }
  
  .content-cell {
    max-width: 350px;
    white-space: pre-wrap;
    word-break: break-word;
    line-height: 1.4;
  }
  
  .template-cell {
    display: flex;
    align-items: center;
    gap: 4px;
  }
  
  .media-cell {
    display: flex;
    align-items: center;
    gap: 8px;
    
    .caption {
      font-size: 0.85em;
      max-width: 200px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
  }
  
  .timestamp-cell {
    text-align: center;
    line-height: 1.2;
  }
  
  .pre-wrap {
    white-space: pre-wrap;
    word-break: break-word;
  }
  
  :deep(.q-table) {
    th {
      font-weight: 600;
      color: $primary;
    }
    
    tbody tr {
      &:hover {
        background-color: rgba($primary, 0.05);
      }
    }
  }
}
</style>