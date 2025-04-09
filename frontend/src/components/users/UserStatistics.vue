<template>
  <div class="row q-mb-lg">
    <div class="col-12 col-md-4 q-pa-sm">
      <q-card class="bg-primary text-white">
        <q-card-section>
          <div class="text-h6">Nombre d'utilisateurs</div>
          <div class="text-h3">{{ totalUsers }}</div>
        </q-card-section>
      </q-card>
    </div>
    <div class="col-12 col-md-4 q-pa-sm">
      <q-card class="bg-secondary text-white">
        <q-card-section>
          <div class="text-h6">Total crédits SMS</div>
          <div class="text-h3">{{ totalSmsCredits }}</div>
        </q-card-section>
      </q-card>
    </div>
    <div class="col-12 col-md-4 q-pa-sm">
      <q-card class="bg-accent text-white">
        <q-card-section>
          <div class="text-h6">Moyenne crédits/utilisateur</div>
          <div class="text-h3">{{ averageSmsCredits }}</div>
        </q-card-section>
      </q-card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  totalUsers: number;
  totalSmsCredits: number;
}>();

const averageSmsCredits = computed(() => {
  if (props.totalUsers === 0) return 0;
  return Math.round(props.totalSmsCredits / props.totalUsers);
});
</script>
