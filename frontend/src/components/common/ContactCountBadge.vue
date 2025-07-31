<template>
  <div class="inline-block" :class="[$q.screen.lt.md ? 'compact-mode' : '']">
    <q-chip
      :color="color || 'primary'"
      text-color="white"
      :icon="icon"
      size="sm"
      class="contact-count-badge"
      :class="[compact ? 'compact' : '']"
    >
      <span v-if="!compact">{{ count }} contact{{ count !== 1 ? 's' : '' }}</span>
      <span v-else>{{ count }}</span>
    </q-chip>
    <q-tooltip v-if="tooltipText">
      {{ tooltipText }}
    </q-tooltip>
  </div>
</template>

<script setup lang="ts">
import { defineProps, computed } from 'vue';
import { useQuasar } from 'quasar';

const $q = useQuasar();

interface Props {
  count: number;
  color?: string;
  icon?: string;
  tooltipText?: string;
  compact?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  color: 'primary',
  icon: undefined,
  tooltipText: undefined,
  compact: false
});
</script>

<style scoped>
.contact-count-badge {
  font-weight: 500;
  min-height: 20px;
}

.contact-count-badge.compact {
  padding: 0 6px;
  min-width: 24px;
  justify-content: center;
}

.compact-mode .contact-count-badge {
  padding: 0 6px;
  min-width: 24px;
  justify-content: center;
}

.compact-mode .contact-count-badge span {
  font-size: 0.8rem;
}
</style>
