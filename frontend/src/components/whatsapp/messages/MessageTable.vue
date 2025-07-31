<template>
  <div class="message-table">
    <div class="table-container q-pa-md">
      <q-table
        :rows="messages"
        :columns="columns"
        :loading="loading"
        row-key="id"
        :pagination="pagination"
        @request="$emit('update:pagination', $event)"
        binary-state-sort
        virtual-scroll
        :rows-per-page-options="rowsPerPageOptions"
      >
        <template v-slot:loading>
          <q-inner-loading showing>
            <q-spinner size="50px" color="primary" />
          </q-inner-loading>
        </template>

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
            <div class="phone-cell cursor-pointer" @click="$emit('filter-by-phone', props.row.phoneNumber)">
              {{ formatters.formatPhoneNumber(props.row.phoneNumber) }}
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
              :color="formatters.getMessageTypeColor(props.row.type)"
              text-color="white"
              dense
              size="sm"
              class="message-type-chip"
            >
              <q-icon :name="formatters.getMessageTypeIcon(props.row.type)" size="xs" class="q-mr-xs" />
              {{ formatters.getMessageTypeLabel(props.row.type) }}
            </q-chip>
          </q-td>
        </template>

        <template v-slot:body-cell-content="props">
          <q-td :props="props">
            <div v-if="props.row.type === 'text' && props.row.content" class="content-cell">
              {{ formatters.truncateContent(props.row.content) }}
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
                :color="formatters.getMessageTypeColor(props.row.type)" 
                text-color="white" 
                dense 
                size="sm"
              >
                <q-icon :name="formatters.getMessageTypeIcon(props.row.type)" size="xs" class="q-mr-xs" />
                {{ formatters.getMessageTypeLabel(props.row.type) }}
              </q-chip>
              <div v-if="props.row.content" class="caption q-mt-xs text-grey-7">
                {{ formatters.truncateContent(props.row.content, 30) }}
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
              :color="formatters.getStatusColor(props.row.status)"
              text-color="white"
              dense
              size="sm"
            >
              <q-icon 
                :name="formatters.getStatusIcon(props.row.status)" 
                size="xs" 
                class="q-mr-xs" 
              />
              {{ formatters.getStatusLabel(props.row.status) }}
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
              <div class="text-weight-medium">{{ formatters.formatTime(props.row.timestamp) }}</div>
              <div class="text-caption text-grey-6">{{ formatters.formatDateOnly(props.row.timestamp) }}</div>
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
                @click="$emit('reply', props.row)"
                :disable="loading"
              >
                <q-tooltip>Répondre dans la fenêtre de 24h</q-tooltip>
              </q-btn>
              <q-btn
                v-if="props.row.mediaId"
                icon="download"
                color="secondary"
                size="sm"
                @click="$emit('download', props.row)"
                :disable="loading"
              >
                <q-tooltip>Télécharger le média</q-tooltip>
              </q-btn>
              <q-btn
                icon="info"
                color="grey"
                size="sm"
                @click="$emit('show-details', props.row)"
              >
                <q-tooltip>Détails du message</q-tooltip>
              </q-btn>
            </q-btn-group>
          </q-td>
        </template>

        <template v-slot:bottom>
          <div class="row justify-between items-center full-width q-px-md">
            <div class="text-caption">
              {{ paginationLabel }}
            </div>
            <q-pagination
              :model-value="pagination.page"
              @update:model-value="updatePage"
              :max="totalPages"
              :max-pages="7"
              boundary-numbers
              direction-links
            />
          </div>
        </template>
      </q-table>
    </div>
  </div>
</template>

<script setup lang="ts">
// import { computed } from 'vue'; // Non utilisé actuellement
import type { WhatsAppMessageHistory } from '../../../stores/whatsappStore';
import type { PaginationState } from './composables/useMessagePagination';
import { useMessageFormatters } from './composables/useMessageFormatters';
import { canReply } from './utils/messageHelpers';
import { TABLE_COLUMNS, ROWS_PER_PAGE_OPTIONS } from './utils/messageConstants';

interface Props {
  messages: WhatsAppMessageHistory[];
  loading?: boolean;
  pagination: PaginationState;
  paginationLabel: string;
  totalPages: number;
}

interface Emits {
  (e: 'reply', message: WhatsAppMessageHistory): void;
  (e: 'download', message: WhatsAppMessageHistory): void;
  (e: 'show-details', message: WhatsAppMessageHistory): void;
  (e: 'filter-by-phone', phone: string): void;
  (e: 'update:pagination', request: any): void;
  (e: 'update:page', page: number): void;
}

withDefaults(defineProps<Props>(), {
  loading: false
});

const emit = defineEmits<Emits>();

const formatters = useMessageFormatters();
const columns = TABLE_COLUMNS;
const rowsPerPageOptions = ROWS_PER_PAGE_OPTIONS;

function updatePage(page: number) {
  emit('update:page', page);
}
</script>

<style lang="scss" scoped>
.message-table {
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