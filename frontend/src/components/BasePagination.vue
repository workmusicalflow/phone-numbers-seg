<template>
  <div class="q-pa-md">
    <q-pagination
      v-model="currentPage"
      :max="totalPages"
      :max-pages="6"
      boundary-numbers
      direction-links
      @update:model-value="onPageChange"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";

const props = defineProps<{
  totalItems: number;
  itemsPerPage: number;
  initialPage?: number;
}>();

const emit = defineEmits<{
  (e: "page-change", page: number): void;
}>();

const totalPages = ref(Math.ceil(props.totalItems / props.itemsPerPage) || 1);
const currentPage = ref(props.initialPage || 1);

// Mettre à jour le nombre total de pages si les props changent
watch(
  () => props.totalItems,
  (newValue) => {
    totalPages.value = Math.ceil(newValue / props.itemsPerPage) || 1;
    if (currentPage.value > totalPages.value) {
      currentPage.value = totalPages.value;
    }
  },
);

watch(
  () => props.itemsPerPage,
  (newValue) => {
    totalPages.value = Math.ceil(props.totalItems / newValue) || 1;
    if (currentPage.value > totalPages.value) {
      currentPage.value = totalPages.value;
    }
  },
);

const onPageChange = (page: number) => {
  emit("page-change", page);
};
</script>
