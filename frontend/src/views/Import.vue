<template>
  <q-page padding>
    <div class="q-pa-md">
      <h1 class="text-h4 q-mb-md">Import / Export</h1>

      <div class="row q-col-gutter-md">
        <!-- Formulaire d'import -->
        <div class="col-12 col-md-6 import-form-container">
          <q-card class="import-card">
            <q-card-section>
              <div class="text-h6">Importer des numéros depuis un fichier CSV</div>
              <div class="text-caption q-mt-sm">
                Le fichier CSV peut contenir les colonnes suivantes : number
                (obligatoire), civility, firstName, name, company, sector, notes, email
              </div>
              <div class="text-caption q-mt-sm">
                <a href="#" @click.prevent="onDownloadTemplate">Télécharger un modèle CSV</a>
              </div>
            </q-card-section>

            <q-card-section>
              <div class="text-caption q-mb-md bg-blue-1 q-pa-sm rounded-borders">
                <p><strong>Guide d'utilisation :</strong></p>
                <ul class="q-mb-none">
                  <li>Utilisez un fichier CSV avec délimiteur "{{ importOptions.delimiter }}"</li>
                  <li>Format de numéro recommandé : international (+XXX...)</li>
                  <li>Taille maximale recommandée : 5000 lignes</li>
                  <li>Sélectionnez les colonnes correspondantes ci-dessous</li>
                </ul>
              </div>
              
              <q-form @submit="onImport" class="q-gutter-md">
                <q-file
                  v-model="csvFile"
                  label="Fichier CSV"
                  accept=".csv"
                  :rules="[(val) => !!val || 'Le fichier est requis']"
                  outlined
                >
                  <template v-slot:prepend>
                    <q-icon name="attach_file" />
                  </template>
                </q-file>

                <q-checkbox
                  v-model="importOptions.hasHeader"
                  label="Le fichier contient une ligne d'en-tête"
                />

                <q-checkbox
                  v-model="importOptions.createContacts"
                  label="Créer des contacts à partir des numéros importés"
                  class="q-mt-sm"
                />

                <q-select
                  v-if="importOptions.createContacts"
                  v-model="importOptions.userId"
                  :options="userOptions"
                  label="Associer les contacts à l'utilisateur"
                  outlined
                  emit-value
                  map-options
                  class="q-mt-sm"
                  hint="Si non spécifié, les contacts seront associés à l'utilisateur par défaut (AfricaQSHE)"
                />

                <q-input
                  v-model="importOptions.delimiter"
                  label="Délimiteur"
                  :rules="[(val) => !!val || 'Le délimiteur est requis']"
                  maxlength="1"
                />

                <!-- Sélection des colonnes -->
                <div class="row q-col-gutter-md">
                  <div class="col-12 col-md-6">
                    <q-select
                      v-model="importOptions.phoneColumn"
                      :options="columnOptions"
                      label="Colonne des numéros de téléphone *"
                      outlined
                      :rules="[val => val !== null || 'Cette colonne est requise']"
                    />
                  </div>
                  
                  <div class="col-12 col-md-6">
                    <q-select
                      v-model="importOptions.nameColumn"
                      :options="columnOptions"
                      label="Colonne du nom (optionnel)"
                      outlined
                      clearable
                    />
                  </div>
                  
                  <div class="col-12 col-md-6">
                    <q-select
                      v-model="importOptions.emailColumn"
                      :options="columnOptions"
                      label="Colonne de l'email (optionnel)"
                      outlined
                      clearable
                    />
                  </div>
                  
                  <div class="col-12 col-md-6">
                    <q-select
                      v-model="importOptions.notesColumn"
                      :options="columnOptions"
                      label="Colonne des notes (optionnel)"
                      outlined
                      clearable
                    />
                  </div>
                </div>

                <div>
                  <q-btn
                    label="Importer"
                    type="submit"
                    color="primary"
                    :loading="loading"
                  />
                  
                  <!-- Indicateur de progression amélioré -->
                  <div v-if="loading" class="q-mt-md">
                    <q-linear-progress indeterminate />
                    <div class="text-caption q-mt-sm">
                      Traitement en cours... Cela peut prendre quelques minutes pour les fichiers volumineux.
                      Veuillez ne pas fermer cette page.
                    </div>
                  </div>
                </div>
              </q-form>
            </q-card-section>
          </q-card>
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
  userOptions,
  importFormRef,
  downloadTemplate,
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
