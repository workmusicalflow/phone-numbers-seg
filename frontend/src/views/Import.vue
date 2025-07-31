<template>
  <q-page padding>
    <div class="q-pa-md">
      <h1 class="text-h4 q-mb-md">Import / Export</h1>

      <div class="row q-col-gutter-md">
        <!-- Formulaire d'import -->
        <div class="col-12 col-md-6 import-form-container">
          <ImportCSVForm
            v-model:options="importOptions"
            v-model:file="csvFile"
            :column-options="columnOptions"
            :group-options="groupOptions"
            :loading-groups="loadingGroups"
            :loading="loading"
            @submit="onImport"
            @download-template="onDownloadTemplate"
            @refresh-groups="onRefreshGroups"
          />
        </div>

        <!-- Formulaire d'export -->
        <div class="col-12 col-md-6">
          <export-data-form
            v-model:options="exportOptions"
            :operator-options="operatorOptions"
            :country-options="countryOptions"
            :segment-options="segmentOptions"
            :loading="loadingExport"
            @submit="onExport"
          />
        </div>
      </div>

      <!-- Dialogue des résultats d'import -->
      <import-result-dialog
        v-model="showImportResults"
        :results="importResults"
      />
    </div>
  </q-page>
</template>

<style scoped>
.import-form-container {
  display: block !important;
  visibility: visible !important;
}

.import-card {
  width: 100%;
  max-width: 100%;
}
</style>

<script setup lang="ts">
import { useImport } from '../components/import-export/composables/useImport';
import { useExport } from '../components/import-export/composables/useExport';
import ImportCSVForm from '../components/import-export/ImportCSVForm.vue';
import ExportDataForm from '../components/import-export/ExportDataForm.vue';
import ImportResultDialog from '../components/import-export/ImportResultDialog.vue';
import { defineComponent, ref, onMounted, onErrorCaptured } from 'vue';

// This helps TypeScript recognize that the imported components are used in the template
defineComponent({
  components: {
    ImportCSVForm,
    ExportDataForm,
    ImportResultDialog
  }
});

// Variables pour le débogage
const importComponentLoaded = ref(false);
const exportComponentLoaded = ref(false);
const runtimeErrors = ref<string[]>([]);

// Utiliser les composables
const { 
  csvFile, 
  loading, 
  showImportResults, 
  importResults, 
  importOptions, 
  columnOptions,
  groupOptions,
  loadingGroups,
  importFormRef,
  downloadTemplate,
  loadContactGroups,
  importCSV 
} = useImport();

const { 
  loadingExport, 
  exportOptions, 
  operatorOptions,
  countryOptions,
  segmentOptions,
  exportData 
} = useExport();

// Fonctions de gestion des événements
const onImport = () => {
  importCSV();
};

const onExport = () => {
  exportData();
};

// Fonction pour télécharger le modèle CSV
const onDownloadTemplate = () => {
  downloadTemplate();
};

// Fonction pour actualiser les groupes
const onRefreshGroups = () => {
  loadContactGroups();
};

// Capturer les erreurs
onErrorCaptured((err, instance, info) => {
  const errorMessage = `Erreur: ${err.message || 'Erreur inconnue'} (${info})`;
  console.error(errorMessage, err);
  runtimeErrors.value.push(errorMessage);
  return false; // Ne pas propager l'erreur
});

// Vérifier le chargement des composants
onMounted(() => {
  // Vérifier si les composants sont chargés
  importComponentLoaded.value = true;
  exportComponentLoaded.value = true;
  
  // Charger les groupes de contacts au démarrage
  loadContactGroups();
  
  // Surveiller les erreurs de console
  const originalConsoleError = console.error;
  console.error = (...args) => {
    const errorMessage = args.map(arg => 
      typeof arg === 'string' ? arg : JSON.stringify(arg)  
    ).join(' ');
    
    if (errorMessage.includes('runtime.lastError')) {
      runtimeErrors.value.push(`Console: ${errorMessage}`);
    }
    
    originalConsoleError(...args);
  };
});
</script>
