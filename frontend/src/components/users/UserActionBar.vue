<template>
  <div class="row q-mb-md items-center justify-between">
    <div class="col-12 col-md-6 q-mb-sm-xs">
      <q-input
        v-model="searchModel"
        outlined
        dense
        placeholder="Rechercher un utilisateur..."
        class="q-mr-sm"
        @update:model-value="updateSearch"
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
        label="Nouvel utilisateur"
        @click="$emit('create-user')"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';

const props = defineProps<{
  searchQuery: string;
}>();

const emit = defineEmits<{
  (e: 'update:searchQuery', value: string): void;
  (e: 'create-user'): void;
}>();

const searchModel = ref(props.searchQuery);

// Synchroniser le modèle local avec la prop
watch(() => props.searchQuery, (newVal) => {
  searchModel.value = newVal;
});

function updateSearch(value: string | number | null) {
  emit('update:searchQuery', value as string);
}
</script>
