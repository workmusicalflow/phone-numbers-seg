<template>
  <div>
    <q-chip
      :color="color"
      text-color="white"
      :icon="icon"
    >
      {{ count }} contact{{ count !== 1 ? 's' : '' }}
    </q-chip>
    <q-tooltip v-if="tooltipText">
      {{ tooltipText }}
    </q-tooltip>
  </div>
</template>

<script setup lang="ts">
import { defineProps } from 'vue';

interface Props {
  count: number;
  color?: string;
  icon?: string;
  tooltipText?: string;
}

const props = defineProps<Props>();
</script>

<style scoped>
/* Styles spécifiques au composant si nécessaire */
</style>
