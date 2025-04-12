<template>
  <div class="base-pagination">
    <div class="row items-center justify-between q-col-gutter-md">
      <!-- Informations sur les résultats -->
      <div class="col-12 col-sm-auto text-body2 text-grey-8">
        {{ startItem }}-{{ endItem }} sur {{ totalItems }} résultats
      </div>
      
      <!-- Pagination -->
      <div class="col-12 col-sm-auto">
        <q-pagination
          v-model="currentPage"
          :max="totalPages"
          :max-pages="6"
          boundary-numbers
          direction-links
          @update:model-value="onPageChange"
          color="primary"
        />
      </div>
      
      <!-- Sélecteur d'éléments par page -->
      <div class="col-12 col-sm-auto">
        <q-select
          v-model="itemsPerPageModel"
          :options="rowsPerPageOptions"
          label="Par page"
          dense
          outlined
          options-dense
          emit-value
          map-options
          style="min-width: 120px"
          @update:model-value="onItemsPerPageChange"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from "vue";

const props = defineProps<{
  totalItems: number;
  itemsPerPage: number;
  initialPage?: number;
}>();

const emit = defineEmits<{
  (e: "page-change", page: number): void;
  (e: "items-per-page-change", itemsPerPage: number): void;
}>();

const totalPages = ref(Math.ceil(props.totalItems / props.itemsPerPage) || 1);
const currentPage = ref(props.initialPage || 1);
const itemsPerPageModel = ref(props.itemsPerPage);

// Options pour le nombre d'éléments par page
const rowsPerPageOptions = [
  { label: '5 par page', value: 5 },
  { label: '10 par page', value: 10 },
  { label: '20 par page', value: 20 },
  { label: '50 par page', value: 50 },
  { label: '100 par page', value: 100 },
];

// Calcul des indices de début et de fin pour l'affichage
const startItem = computed(() => {
  return props.totalItems === 0 ? 0 : (currentPage.value - 1) * props.itemsPerPage + 1;
});

const endItem = computed(() => {
  return Math.min(currentPage.value * props.itemsPerPage, props.totalItems);
});

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
    itemsPerPageModel.value = newValue;
    totalPages.value = Math.ceil(props.totalItems / newValue) || 1;
    if (currentPage.value > totalPages.value) {
      currentPage.value = totalPages.value;
    }
  },
);

const onPageChange = (page: number) => {
  emit("page-change", page);
};

const onItemsPerPageChange = (value: number) => {
  emit("items-per-page-change", value);
};
</script>

<style scoped>
.base-pagination {
  padding: 8px 16px;
}

@media (max-width: 600px) {
  .base-pagination .row {
    flex-direction: column;
    align-items: center;
  }
  
  .base-pagination .col-12 {
    margin-bottom: 8px;
  }
}
</style>
