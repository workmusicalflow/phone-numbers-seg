<template>
  <q-card>
    <q-card-section class="bg-primary text-white">
      <div class="text-h6">Crédits SMS</div>
    </q-card-section>
    <q-card-section class="text-center q-py-lg">
      <div class="text-h3 q-mb-md">{{ credits }}</div>
      <q-circular-progress
        :value="creditPercentage"
        size="120px"
        :thickness="0.2"
        color="primary"
        track-color="grey-3"
        class="q-ma-md"
      >
        <q-icon name="sms" size="3rem" color="primary" />
      </q-circular-progress>
      <div class="text-subtitle1 q-mt-md">
        {{ creditStatus }}
      </div>
    </q-card-section>
    <q-card-actions align="center">
      <q-btn color="primary" label="Acheter des crédits" to="/sms-orders" />
    </q-card-actions>
  </q-card>
</template>

<script lang="ts">
import { defineComponent, computed } from 'vue';

export default defineComponent({
  name: 'CreditWidget',
  
  props: {
    credits: {
      type: Number,
      required: true
    },
    threshold: {
      type: Number,
      default: 20
    },
    maxCredits: {
      type: Number,
      default: 1000
    }
  },
  
  setup(props) {
    // Calculer le pourcentage de crédits restants
    const creditPercentage = computed(() => {
      return Math.min(100, (props.credits / props.maxCredits) * 100);
    });
    
    // Déterminer le statut des crédits
    const creditStatus = computed(() => {
      if (props.credits <= 0) {
        return 'Aucun crédit disponible';
      } else if (props.credits < props.threshold) {
        return 'Crédits faibles';
      } else if (props.credits < props.maxCredits / 2) {
        return 'Crédits suffisants';
      } else {
        return 'Crédits abondants';
      }
    });
    
    return {
      creditPercentage,
      creditStatus
    };
  }
});
</script>

<style scoped>
/* Styles spécifiques au widget de crédits */
</style>
