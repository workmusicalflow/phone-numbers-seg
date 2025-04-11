<template>
  <q-card>
    <q-card-section>
      <div class="text-h6">Exporter les données</div>
      <div class="text-caption q-mt-sm">
        Exportez les numéros de téléphone et leurs segments au format
        CSV ou Excel
      </div>
    </q-card-section>

    <q-card-section>
      <q-form @submit="onSubmit" class="q-gutter-md">
        <q-select
          v-model="options.format"
          :options="[
            { label: 'CSV', value: 'csv' },
            { label: 'Excel', value: 'excel' },
          ]"
          label="Format d'export"
          outlined
          emit-value
          map-options
        />

        <q-input
          v-model="options.search"
          label="Recherche (optionnel)"
          outlined
          clearable
          hint="Filtrer par numéro, nom, entreprise, etc."
        >
          <template v-slot:prepend>
            <q-icon name="search" />
          </template>
        </q-input>

        <q-input
          v-model.number="options.limit"
          type="number"
          label="Nombre maximum de résultats"
          outlined
          :rules="[
            (val) => val > 0 || 'La limite doit être supérieure à 0',
          ]"
          hint="Maximum 5000 résultats recommandé"
        />

        <q-expansion-item
          label="Filtres avancés"
          header-class="text-primary"
          expand-icon-class="text-primary"
        >
          <q-card>
            <q-card-section>
              <q-select
                v-model="options.operator"
                :options="operatorOptions"
                label="Opérateur"
                outlined
                clearable
                emit-value
                map-options
                class="q-mb-md"
              />

              <q-select
                v-model="options.country"
                :options="countryOptions"
                label="Pays"
                outlined
                clearable
                emit-value
                map-options
                class="q-mb-md"
              />

              <q-select
                v-model="options.segment"
                :options="segmentOptions"
                label="Segment personnalisé"
                outlined
                clearable
                emit-value
                map-options
                class="q-mb-md"
              />

              <div class="row q-col-gutter-md">
                <div class="col-12 col-md-6">
                  <q-input
                    v-model="options.dateFrom"
                    label="Date de début"
                    outlined
                    type="date"
                  />
                </div>
                <div class="col-12 col-md-6">
                  <q-input
                    v-model="options.dateTo"
                    label="Date de fin"
                    outlined
                    type="date"
                  />
                </div>
              </div>
            </q-card-section>
          </q-card>
        </q-expansion-item>

        <q-expansion-item
          label="Options d'export"
          header-class="text-primary"
          expand-icon-class="text-primary"
        >
          <q-card>
            <q-card-section>
              <q-checkbox
                v-model="options.includeHeaders"
                label="Inclure les en-têtes"
              />

              <q-checkbox
                v-model="options.includeContactInfo"
                label="Inclure les informations de contact"
                class="q-mt-sm"
              />

              <q-checkbox
                v-model="options.includeSegments"
                label="Inclure les segments"
                class="q-mt-sm"
              />

              <q-input
                v-if="options.format === 'csv'"
                v-model="options.delimiter"
                label="Délimiteur"
                outlined
                class="q-mt-md"
                maxlength="1"
              />

              <q-input
                v-model="options.filename"
                label="Nom du fichier"
                outlined
                class="q-mt-md"
              />
            </q-card-section>
          </q-card>
        </q-expansion-item>

        <div>
          <q-btn
            label="Exporter"
            type="submit"
            color="primary"
            :loading="loading"
            icon="download"
          />
        </div>
      </q-form>
    </q-card-section>
  </q-card>
</template>

<script setup lang="ts">
import { watch, toRefs } from 'vue';
import { 
  ExportOptions, 
  OperatorOption, 
  CountryOption, 
  SegmentOption 
} from './composables/useExport';

const props = defineProps<{
  options: ExportOptions;
  operatorOptions: OperatorOption[];
  countryOptions: CountryOption[];
  segmentOptions: SegmentOption[];
  loading: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:options', value: ExportOptions): void;
  (e: 'submit'): void;
}>();

// Références locales
const { options, loading } = toRefs(props);

// Surveiller les changements d'options pour les émettre au parent
watch(options, (newOptions) => {
  emit('update:options', { ...newOptions });
}, { deep: true });

// Gestionnaires d'événements
const onSubmit = () => {
  emit('submit');
};
</script>
