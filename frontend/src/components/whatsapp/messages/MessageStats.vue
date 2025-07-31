<template>
  <div class="message-stats">
    <div class="stats-bar q-pa-md bg-grey-2">
      <div class="row q-col-gutter-md text-center">
        <div class="col">
          <div class="text-h6">{{ stats.total }}</div>
          <div class="text-caption">Total</div>
        </div>
        <div class="col">
          <div class="text-h6 text-primary">{{ stats.incoming }}</div>
          <div class="text-caption">Reçus</div>
        </div>
        <div class="col">
          <div class="text-h6 text-positive">{{ stats.outgoing }}</div>
          <div class="text-caption">Envoyés</div>
        </div>
        <div class="col">
          <div class="text-h6 text-green">{{ stats.delivered }}</div>
          <div class="text-caption">Délivrés</div>
        </div>
        <div class="col">
          <div class="text-h6 text-info">{{ stats.read }}</div>
          <div class="text-caption">Lus</div>
        </div>
        <div class="col">
          <div class="text-h6 text-negative">{{ stats.failed }}</div>
          <div class="text-caption">Échoués</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { WhatsAppMessageHistory } from '../../../stores/whatsappStore';
import { calculateMessageStats } from './utils/messageHelpers';

interface Props {
  messages: WhatsAppMessageHistory[];
}

const props = defineProps<Props>();

const stats = computed(() => calculateMessageStats(props.messages));
</script>

<style lang="scss" scoped>
.message-stats {
  .stats-bar {
    border-radius: 8px;
    margin-bottom: 16px;
  }
}
</style>