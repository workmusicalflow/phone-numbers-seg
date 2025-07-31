<template>
  <q-dialog v-model="dialogModel" persistent>
    <q-card style="min-width: 500px">
      <q-card-section>
        <div class="text-h6">Résultats de l'import</div>
      </q-card-section>

      <q-card-section>
        <!-- Statistiques générales -->
        <div class="q-mb-md">
          <q-card flat bordered class="q-pa-md">
            <div class="text-h6 q-mb-sm">Statistiques d'import</div>
            <div class="row q-col-gutter-md">
              <div class="col-6">
                <q-item dense>
                  <q-item-section avatar>
                    <q-icon name="description" color="primary" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>Total traité</q-item-label>
                    <q-item-label caption>{{ results.totalRows }} lignes</q-item-label>
                  </q-item-section>
                </q-item>
              </div>
              
              <div class="col-6">
                <q-item dense>
                  <q-item-section avatar>
                    <q-icon name="check_circle" color="positive" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>Succès</q-item-label>
                    <q-item-label caption>{{ results.successRows }} contacts créés</q-item-label>
                  </q-item-section>
                </q-item>
              </div>
              
              <div class="col-6" v-if="results.errorRows > 0">
                <q-item dense>
                  <q-item-section avatar>
                    <q-icon name="error" color="negative" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>Erreurs</q-item-label>
                    <q-item-label caption>{{ results.errorRows }} lignes échouées</q-item-label>
                  </q-item-section>
                </q-item>
              </div>
              
              <div class="col-6" v-if="results.skippedRows && results.skippedRows > 0">
                <q-item dense>
                  <q-item-section avatar>
                    <q-icon name="skip_next" color="warning" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>Ignorées</q-item-label>
                    <q-item-label caption>{{ results.skippedRows }} lignes</q-item-label>
                  </q-item-section>
                </q-item>
              </div>
              
              <div class="col-6" v-if="results.duplicateCount && results.duplicateCount > 0">
                <q-item dense>
                  <q-item-section avatar>
                    <q-icon name="content_copy" color="info" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>Doublons</q-item-label>
                    <q-item-label caption>{{ results.duplicateCount }} détectés</q-item-label>
                  </q-item-section>
                </q-item>
              </div>
              
              <div class="col-6" v-if="results.groupAssignments && results.groupAssignments > 0">
                <q-item dense>
                  <q-item-section avatar>
                    <q-icon name="group" color="secondary" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>Assignations aux groupes</q-item-label>
                    <q-item-label caption>{{ results.groupAssignments }} réussies</q-item-label>
                  </q-item-section>
                </q-item>
              </div>
            </div>
          </q-card>
        </div>

        <!-- Taux de succès -->
        <div v-if="successRate !== null" class="q-mb-md">
          <q-linear-progress 
            :value="successRate" 
            :color="successRateColor"
            size="20px"
            rounded
          />
          <div class="text-center q-mt-xs text-caption">
            Taux de succès : {{ Math.round(successRate * 100) }}%
          </div>
        </div>
        
        <!-- Affichage des erreurs détaillées -->
        <div v-if="hasDetailedErrors">
          <q-expansion-item 
            icon="error_outline" 
            :label="`Détails des erreurs (${results.detailedErrors?.length || 0})`"
            header-class="text-negative"
          >
            <q-list bordered separator>
              <q-item v-for="(error, index) in results.detailedErrors" :key="index">
                <q-item-section avatar>
                  <q-icon name="warning" color="negative" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>Ligne {{ error.line }}</q-item-label>
                  <q-item-label caption class="text-negative">{{ error.message }}</q-item-label>
                  <q-item-label caption v-if="error.value" class="text-grey-8">Valeur: {{ error.value }}</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
            <p v-if="hasMoreErrors" class="text-caption q-mt-sm text-center">
              Et {{ results.errorRows - (results.detailedErrors?.length || 0) }} autres erreurs...
            </p>
          </q-expansion-item>
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

const successRate = computed(() => {
  if (props.results.totalRows === 0) return null;
  return props.results.successRows / props.results.totalRows;
});

const successRateColor = computed(() => {
  const rate = successRate.value;
  if (rate === null) return 'grey';
  if (rate >= 0.9) return 'positive';
  if (rate >= 0.7) return 'warning';
  return 'negative';
});

// Modèle local pour le dialogue
const dialogModel = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
});

// Références locales
const { results } = toRefs(props);
</script>
