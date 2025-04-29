<template>
  <div class="contact-sms-stats q-pa-md">
    <div class="row q-col-gutter-md">
      <!-- Total SMS count -->
      <div class="col-12 col-md-3 q-pa-sm">
        <q-card class="bg-primary text-white">
          <q-card-section>
            <div class="text-h6">Total SMS</div>
            <div class="text-h3">{{ contact.smsTotalCount || 0 }}</div>
          </q-card-section>
        </q-card>
      </div>
      
      <!-- Sent SMS count -->
      <div class="col-12 col-md-3 q-pa-sm">
        <q-card class="bg-positive text-white">
          <q-card-section>
            <div class="text-h6">SMS Envoyés</div>
            <div class="text-h3">{{ contact.smsSentCount || 0 }}</div>
          </q-card-section>
        </q-card>
      </div>
      
      <!-- Failed SMS count -->
      <div class="col-12 col-md-3 q-pa-sm">
        <q-card class="bg-negative text-white">
          <q-card-section>
            <div class="text-h6">SMS Échoués</div>
            <div class="text-h3">{{ contact.smsFailedCount || 0 }}</div>
          </q-card-section>
        </q-card>
      </div>
      
      <!-- Success rate -->
      <div class="col-12 col-md-3 q-pa-sm">
        <q-card :class="successRateCardClass">
          <q-card-section>
            <div class="text-h6">Taux de Succès</div>
            <div class="text-h3">{{ formattedSuccessRate }}</div>
            <q-linear-progress
              :value="successRate / 100"
              :color="successRateColor"
              class="q-mt-sm"
              size="10px"
            />
          </q-card-section>
        </q-card>
      </div>
    </div>
    
    <!-- Empty state message -->
    <div v-if="isEmpty" class="text-center q-mt-md">
      <q-banner class="bg-grey-3">
        <template v-slot:avatar>
          <q-icon name="info" color="primary" />
        </template>
        Aucun SMS n'a été envoyé à ce contact.
      </q-banner>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Contact } from '../../types/contact';

interface Props {
  contact: Contact;
}

const props = defineProps<Props>();

// Check if there are no SMS stats
const isEmpty = computed(() => {
  return !props.contact.smsTotalCount || props.contact.smsTotalCount === 0;
});

// Calculate success rate
const successRate = computed(() => {
  if (!props.contact.smsTotalCount || props.contact.smsTotalCount === 0) {
    return 0;
  }
  
  const sentCount = props.contact.smsSentCount || 0;
  return Math.round((sentCount / props.contact.smsTotalCount) * 100);
});

// Format success rate for display
const formattedSuccessRate = computed(() => {
  return `${successRate.value}%`;
});

// Determine color based on success rate
const successRateColor = computed(() => {
  if (successRate.value >= 90) return 'positive';
  if (successRate.value >= 75) return 'accent';
  if (successRate.value >= 50) return 'warning';
  return 'negative';
});

// Determine card class based on success rate
const successRateCardClass = computed(() => {
  return {
    'text-white': true,
    'bg-positive': successRate.value >= 90,
    'bg-accent': successRate.value >= 75 && successRate.value < 90,
    'bg-warning': successRate.value >= 50 && successRate.value < 75,
    'bg-negative': successRate.value < 50
  };
});
</script>

<style scoped>
.contact-sms-stats {
  margin-bottom: 1.5rem;
}
</style>