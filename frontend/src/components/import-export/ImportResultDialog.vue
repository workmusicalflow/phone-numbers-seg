<template>
  <q-dialog v-model="dialogModel" persistent>
    <q-card style="min-width: 500px">
      <q-card-section>
        <div class="text-h6">Résultats de l'import</div>
      </q-card-section>

      <q-card-section>
        <p>Nombre total de lignes: {{ results.totalRows }}</p>
        <p>Lignes importées avec succès: {{ results.successRows }}</p>
        <p>Lignes en erreur: {{ results.errorRows }}</p>
        <p v-if="results.duplicateCount">Doublons détectés: {{ results.duplicateCount }}</p>
        
        <!-- Affichage des erreurs détaillées -->
        <div v-if="hasDetailedErrors">
          <p class="text-subtitle1 q-mt-md">Aperçu des erreurs:</p>
          <q-list bordered separator>
            <q-item v-for="(error, index) in results.detailedErrors" :key="index">
              <q-item-section>
                <q-item-label>Ligne {{ error.line }}: {{ error.message }}</q-item-label>
                <q-item-label caption v-if="error.value">Valeur: {{ error.value }}</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
          <p v-if="hasMoreErrors" class="text-caption">
            Et {{ results.errorRows - results.detailedErrors!.length }} autres erreurs...
          </p>
        </div>
      </q-card-section>

      <q-card-actions align="right">
        <q-btn flat label="Fermer" color="primary" v-close-popup />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup lang="ts">
import { computed, watch, toRefs } from 'vue';
import { ImportResults } from './composables/useImport';

const props = defineProps<{
  modelValue: boolean;
  results: ImportResults;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
}>();

// Propriétés calculées
const hasDetailedErrors = computed(() => {
  return props.results.detailedErrors && props.results.detailedErrors.length > 0;
});

const hasMoreErrors = computed(() => {
  if (!props.results.detailedErrors) return false;
  return props.results.errorRows > props.results.detailedErrors.length;
});

// Modèle local pour le dialogue
const dialogModel = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
});

// Références locales
const { results } = toRefs(props);
</script>
