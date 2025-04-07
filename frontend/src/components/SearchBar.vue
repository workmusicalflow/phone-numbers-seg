<template>
  <div class="search-bar">
    <q-input
      v-model="searchQuery"
      :placeholder="placeholder"
      outlined
      dense
      clearable
      @update:model-value="onSearch"
      @clear="onClear"
    >
      <template v-slot:append>
        <q-icon name="search" />
      </template>
    </q-input>
    <slot name="filters"></slot>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import { debounce } from "quasar";

const props = withDefaults(
  defineProps<{
    initialValue?: string;
    placeholder?: string;
    debounceTime?: number;
  }>(),
  {
    initialValue: "",
    placeholder: "Rechercher...",
    debounceTime: 300,
  },
);

const emit = defineEmits<{
  (e: "search", query: string): void;
  (e: "clear"): void;
}>();

const searchQuery = ref(props.initialValue);

// Fonction debounce pour éviter trop d'appels lors de la saisie rapide
const debouncedSearch = debounce((query: string) => {
  emit("search", query);
}, props.debounceTime);

// Déclencher la recherche lorsque la valeur change
const onSearch = (value: string) => {
  debouncedSearch(value);
};

// Réinitialiser la recherche
const onClear = () => {
  searchQuery.value = "";
  emit("clear");
};

// Mettre à jour la valeur si la prop initialValue change
watch(
  () => props.initialValue,
  (newValue) => {
    searchQuery.value = newValue;
  },
);

// Exposer des méthodes au composant parent
defineExpose({
  clear: onClear,
  setValue: (value: string) => {
    searchQuery.value = value;
  },
});
</script>

<style scoped>
.search-bar {
  margin-bottom: 1rem;
}
</style>
