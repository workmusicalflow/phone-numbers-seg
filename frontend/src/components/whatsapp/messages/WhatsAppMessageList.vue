<template>
  <div class="whatsapp-message-list">
    <!-- Filtres -->
    <MessageFilters
      :phone-filter="phoneFilter"
      :status-filter="statusFilter"
      :direction-filter="directionFilter"
      :date-filter="dateFilter"
      :loading="isLoading"
      :has-messages="filteredMessages.length > 0"
      :active-filters="activeFilters"
      :has-active-filters="hasActiveFilters"
      @update:phone-filter="phoneFilter = $event"
      @update:status-filter="statusFilter = $event"
      @update:direction-filter="directionFilter = $event"
      @update:date-filter="dateFilter = $event"
      @apply-filters="applyFilters"
      @refresh="refreshMessages"
      @export="exportMessages"
      @clear-filter="clearFilter"
      @clear-all-filters="clearAllFilters"
    />
    
    <!-- Statistiques -->
    <MessageStats :messages="filteredMessages" />
    
    <!-- Table des messages -->
    <MessageTable
      :messages="paginatedMessages"
      :loading="isLoading"
      :pagination="pagination"
      :pagination-label="paginationLabel"
      :total-pages="totalPages"
      @reply="promptReply"
      @download="downloadMedia"
      @show-details="showMessageDetails"
      @filter-by-phone="filterByPhone"
      @update:pagination="onRequest"
      @update:page="updatePage"
    />
    
    <!-- Dialogue de réponse -->
    <ReplyDialog
      v-model="replyDialogOpen"
      :message="selectedMessage"
      :loading="sendingReply"
      @send="sendReply"
    />
    
    <!-- Dialogue des détails -->
    <MessageDetailsDialog
      v-model="detailsDialogOpen"
      :message="selectedMessage"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useQuasar } from 'quasar';
import { useWhatsAppStore, type WhatsAppMessageHistory } from '../../../stores/whatsappStore';
import { useMessageFilters } from './composables/useMessageFilters';
import { useMessagePagination } from './composables/useMessagePagination';
import { useMessageActions } from './composables/useMessageActions';
import { REFRESH_INTERVAL } from './utils/messageConstants';

// Importer les composants
import MessageFilters from './MessageFilters.vue';
import MessageStats from './MessageStats.vue';
import MessageTable from './MessageTable.vue';
import ReplyDialog from './ReplyDialog.vue';
import MessageDetailsDialog from './MessageDetailsDialog.vue';

const $q = useQuasar();
const whatsAppStore = useWhatsAppStore();

// État local
const messages = computed(() => whatsAppStore.messages);
const isLoading = computed(() => whatsAppStore.isLoading);
const replyDialogOpen = ref(false);
const detailsDialogOpen = ref(false);
const selectedMessage = ref<WhatsAppMessageHistory | null>(null);
let refreshTimer: number | null = null;

// Composables
const {
  phoneFilter,
  statusFilter,
  directionFilter,
  dateFilter,
  activeFilters,
  hasActiveFilters,
  filteredMessages,
  clearFilter,
  clearAllFilters,
  applyFilters,
  filterByPhone
} = useMessageFilters(messages);

const {
  pagination,
  paginatedItems: paginatedMessages,
  totalPages,
  paginationLabel,
  updatePage,
  onRequest,
  updateRowsNumber
} = useMessagePagination(filteredMessages);

const {
  sendingReply,
  sendReply: sendReplyAction,
  downloadMedia,
  exportMessages: exportMessagesAction,
  refreshMessages: refreshMessagesAction
} = useMessageActions();

// Méthodes
async function refreshMessages() {
  await refreshMessagesAction();
  updateRowsNumber();
}

function promptReply(message: WhatsAppMessageHistory) {
  selectedMessage.value = message;
  replyDialogOpen.value = true;
}

async function sendReply(content: string) {
  if (!selectedMessage.value) return;
  
  const success = await sendReplyAction(selectedMessage.value, content);
  if (success) {
    replyDialogOpen.value = false;
    await refreshMessages();
  }
}

function showMessageDetails(message: WhatsAppMessageHistory) {
  selectedMessage.value = message;
  detailsDialogOpen.value = true;
}

function exportMessages() {
  exportMessagesAction(filteredMessages.value);
}

// Lifecycle
onMounted(() => {
  refreshMessages();
  
  // Rafraîchissement automatique
  refreshTimer = window.setInterval(() => {
    refreshMessages();
  }, REFRESH_INTERVAL);
});

onUnmounted(() => {
  if (refreshTimer) {
    clearInterval(refreshTimer);
  }
});
</script>

<style lang="scss" scoped>
.whatsapp-message-list {
  // Le conteneur principal n'a pas besoin de styles particuliers
  // car tous les styles sont dans les sous-composants
}
</style>