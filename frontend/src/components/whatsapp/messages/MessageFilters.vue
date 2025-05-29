<template>
  <div class="message-filters">
    <div class="filters q-pa-md">
      <div class="row q-col-gutter-md">
        <div class="col-12 col-md-3">
          <q-input
            :model-value="phoneFilter"
            @update:model-value="$emit('update:phoneFilter', $event)"
            label="Rechercher par numéro"
            outlined
            dense
            clearable
            debounce="300"
            @update:model-value="$emit('apply-filters')"
          >
            <template v-slot:prepend>
              <q-icon name="search" />
            </template>
          </q-input>
        </div>
        <div class="col-12 col-md-2">
          <q-select
            :model-value="statusFilter"
            @update:model-value="$emit('update:statusFilter', $event)"
            :options="statusOptions"
            label="Statut"
            outlined
            dense
            clearable
            emit-value
            map-options
            @update:model-value="$emit('apply-filters')"
          />
        </div>
        <div class="col-12 col-md-2">
          <q-select
            :model-value="directionFilter"
            @update:model-value="$emit('update:directionFilter', $event)"
            :options="directionOptions"
            label="Direction"
            outlined
            dense
            clearable
            emit-value
            map-options
            @update:model-value="$emit('apply-filters')"
          />
        </div>
        <div class="col-12 col-md-3">
          <q-input
            :model-value="dateFilter"
            @update:model-value="$emit('update:dateFilter', $event)"
            label="Filtrer par date"
            outlined
            dense
            readonly
            @click="showDatePicker = true"
          >
            <template v-slot:prepend>
              <q-icon name="event" class="cursor-pointer">
                <q-popup-proxy v-model="showDatePicker" cover transition-show="scale" transition-hide="scale">
                  <q-date 
                    :model-value="dateFilter"
                    @update:model-value="handleDateChange"
                    mask="YYYY-MM-DD"
                  >
                    <div class="row items-center justify-end">
                      <q-btn v-close-popup label="Fermer" color="primary" flat />
                    </div>
                  </q-date>
                </q-popup-proxy>
              </q-icon>
            </template>
            <template v-slot:append>
              <q-icon 
                v-if="dateFilter" 
                name="close" 
                @click.stop="clearDate" 
                class="cursor-pointer" 
              />
            </template>
          </q-input>
        </div>
        <div class="col-12 col-md-2 flex items-center">
          <q-btn 
            color="primary" 
            icon="refresh" 
            label="Actualiser" 
            @click="$emit('refresh')" 
            :loading="loading"
          />
          <q-separator vertical inset class="q-mx-md" />
          <q-btn
            flat
            round
            icon="file_download"
            color="primary"
            @click="$emit('export')"
            :disable="!hasMessages"
          >
            <q-tooltip>Exporter les messages filtrés</q-tooltip>
          </q-btn>
        </div>
      </div>
      
      <!-- Barre de résumé des filtres appliqués -->
      <div v-if="hasActiveFilters" class="row q-mt-md">
        <q-chip 
          v-for="filter in activeFilters" 
          :key="filter.type"
          removable
          @remove="$emit('clear-filter', filter.type)"
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
          @click="$emit('clear-all-filters')"
          class="q-ml-md"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { STATUS_OPTIONS, DIRECTION_OPTIONS } from './utils/messageConstants';

interface Props {
  phoneFilter: string;
  statusFilter: string;
  directionFilter: string;
  dateFilter: string;
  loading?: boolean;
  hasMessages?: boolean;
  activeFilters?: Array<{
    type: string;
    label: string;
    value: string;
  }>;
  hasActiveFilters?: boolean;
}

interface Emits {
  (e: 'update:phoneFilter', value: string): void;
  (e: 'update:statusFilter', value: string): void;
  (e: 'update:directionFilter', value: string): void;
  (e: 'update:dateFilter', value: string): void;
  (e: 'apply-filters'): void;
  (e: 'refresh'): void;
  (e: 'export'): void;
  (e: 'clear-filter', type: string): void;
  (e: 'clear-all-filters'): void;
}

withDefaults(defineProps<Props>(), {
  loading: false,
  hasMessages: false,
  activeFilters: () => [],
  hasActiveFilters: false
});

const emit = defineEmits<Emits>();

const showDatePicker = ref(false);
const statusOptions = STATUS_OPTIONS;
const directionOptions = DIRECTION_OPTIONS;

function handleDateChange(value: string) {
  emit('update:dateFilter', value);
  emit('apply-filters');
  showDatePicker.value = false;
}

function clearDate() {
  emit('update:dateFilter', '');
  emit('apply-filters');
}
</script>

<style lang="scss" scoped>
.message-filters {
  .filters {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }
}
</style>