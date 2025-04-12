<template>
  <div class="row q-mb-md justify-between items-center">
    <div class="col-12 col-md-6 q-mb-sm-md">
      <q-input
        v-model="searchModel"
        outlined
        dense
        placeholder="Rechercher un contact..."
        @update:model-value="onSearch"
        :debounce="300"
        class="search-input"
      >
        <template v-slot:append>
          <q-icon name="search" />
        </template>
      </q-input>
    </div>
    <div class="col-12 col-md-6 text-right">
      <q-btn
        color="primary"
        icon="add"
        label="Nouveau contact"
        @click="onAdd"
        class="q-ml-sm"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';

const props = defineProps<{
  searchQuery: string;
}>();

const emit = defineEmits<{
  (e: 'search', query: string): void;
  (e: 'add'): void;
}>();

// Modèle pour la recherche
const searchModel = computed({
  get: () => props.searchQuery,
  set: (value) => {
    emit('search', value);
  }
});

// Méthodes
const onSearch = (value: string | number | null) => {
  emit('search', value?.toString() || '');
};

const onAdd = () => {
  emit('add');
};
</script>

<style scoped>
.search-input {
  max-width: 400px;
}

@media (max-width: 600px) {
  .q-mb-sm-md {
    margin-bottom: 16px;
  }
}
</style>
