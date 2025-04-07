<template>
  <div v-if="loading" class="loading-overlay">
    <q-spinner-dots color="primary" size="40px" />
    <div class="loading-message q-mt-sm">{{ message }}</div>
  </div>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    loading: boolean;
    message?: string;
  }>(),
  {
    message: "Chargement en cours...",
  },
);
</script>

<style scoped>
.loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(255, 255, 255, 0.7);
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}

.loading-message {
  font-size: 1rem;
  color: #1976d2;
}
</style>
