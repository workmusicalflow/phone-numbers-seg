<template>
  <q-page padding>
    <div class="q-pa-md">
      <h1 class="text-h4 q-mb-md">Import / Export</h1>

      <div class="row q-col-gutter-md">
        <div class="col-12 col-md-6">
          <q-card>
            <q-card-section>
              <div class="text-h6">
                Importer des numéros depuis un fichier CSV
              </div>
              <div class="text-caption q-mt-sm">
                Le fichier CSV peut contenir les colonnes suivantes : number
                (obligatoire), civility, firstName, name, company, sector, notes, email
              </div>
              <div class="text-caption q-mt-sm">
                <a href="#" @click.prevent="downloadTemplate">Télécharger un modèle CSV</a>
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
              
              <q-form ref="importFormRef" @submit="onImport" class="q-gutter-md">
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

        <div class="col-12 col-md-6">
          <q-card>
            <q-card-section>
              <div class="text-h6">Exporter les données</div>
              <div class="text-caption q-mt-sm">
                Exportez les numéros de téléphone et leurs segments au format
                CSV ou Excel
              </div>
            </q-card-section>

            <q-card-section>
              <q-form @submit="onExport" class="q-gutter-md">
                <q-select
                  v-model="exportOptions.format"
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
                  v-model="exportOptions.search"
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
                  v-model.number="exportOptions.limit"
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
                        v-model="exportOptions.operator"
                        :options="operatorOptions"
                        label="Opérateur"
                        outlined
                        clearable
                        emit-value
                        map-options
                        class="q-mb-md"
                      />

                      <q-select
                        v-model="exportOptions.country"
                        :options="countryOptions"
                        label="Pays"
                        outlined
                        clearable
                        emit-value
                        map-options
                        class="q-mb-md"
                      />

                      <q-select
                        v-model="exportOptions.segment"
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
                            v-model="exportOptions.dateFrom"
                            label="Date de début"
                            outlined
                            type="date"
                          />
                        </div>
                        <div class="col-12 col-md-6">
                          <q-input
                            v-model="exportOptions.dateTo"
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
                        v-model="exportOptions.includeHeaders"
                        label="Inclure les en-têtes"
                      />

                      <q-checkbox
                        v-model="exportOptions.includeContactInfo"
                        label="Inclure les informations de contact"
                        class="q-mt-sm"
                      />

                      <q-checkbox
                        v-model="exportOptions.includeSegments"
                        label="Inclure les segments"
                        class="q-mt-sm"
                      />

                      <q-input
                        v-if="exportOptions.format === 'csv'"
                        v-model="exportOptions.delimiter"
                        label="Délimiteur"
                        outlined
                        class="q-mt-md"
                        maxlength="1"
                      />

                      <q-input
                        v-model="exportOptions.filename"
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
                    :loading="loadingExport"
                    icon="download"
                  />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <!-- Résultats d'import -->
      <q-dialog v-model="showImportResults" persistent>
        <q-card style="min-width: 500px">
          <q-card-section>
            <div class="text-h6">Résultats de l'import</div>
          </q-card-section>

          <q-card-section>
            <p>Nombre total de lignes: {{ importResults.totalRows }}</p>
            <p>Lignes importées avec succès: {{ importResults.successRows }}</p>
            <p>Lignes en erreur: {{ importResults.errorRows }}</p>
            <p v-if="importResults.duplicateCount">Doublons détectés: {{ importResults.duplicateCount }}</p>
            
            <!-- Affichage des erreurs détaillées -->
            <div v-if="importResults.detailedErrors && importResults.detailedErrors.length > 0">
              <p class="text-subtitle1 q-mt-md">Aperçu des erreurs:</p>
              <q-list bordered separator>
                <q-item v-for="(error, index) in importResults.detailedErrors" :key="index">
                  <q-item-section>
                    <q-item-label>Ligne {{ error.line }}: {{ error.message }}</q-item-label>
                    <q-item-label caption v-if="error.value">Valeur: {{ error.value }}</q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
              <p v-if="importResults.errorRows > importResults.detailedErrors.length" class="text-caption">
                Et {{ importResults.errorRows - importResults.detailedErrors.length }} autres erreurs...
              </p>
            </div>
          </q-card-section>

          <q-card-actions align="right">
            <q-btn flat label="Fermer" color="primary" v-close-popup />
          </q-card-actions>
        </q-card>
      </q-dialog>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { useApolloClient } from "@vue/apollo-composable";
import { gql } from "@apollo/client/core";
import { useQuasar, QForm } from "quasar";

const apolloClient = useApolloClient();
const $q = useQuasar();

const csvFile = ref(null);
const loading = ref(false);
const loadingExport = ref(false);
const showImportResults = ref(false);
// Interface pour les erreurs détaillées
interface DetailedError {
  line: string | number;
  value: string;
  message: string;
}

// Interface pour les résultats d'import
interface ImportResults {
  totalRows: number;
  successRows: number;
  errorRows: number;
  duplicateCount?: number;
  detailedErrors?: DetailedError[];
}

const importResults = ref<ImportResults>({
  totalRows: 0,
  successRows: 0,
  errorRows: 0,
  duplicateCount: 0,
  detailedErrors: []
});

const importOptions = ref({
  hasHeader: true,
  delimiter: ",",
  phoneColumn: 0, // Par défaut, première colonne
  nameColumn: null,
  emailColumn: null,
  notesColumn: null
});

// Options pour les colonnes du CSV avec libellés plus clairs
const columnOptions = ref([
  { label: "Colonne A (1ère colonne)", value: 0 },
  { label: "Colonne B (2ème colonne)", value: 1 },
  { label: "Colonne C (3ème colonne)", value: 2 },
  { label: "Colonne D (4ème colonne)", value: 3 },
  { label: "Colonne E (5ème colonne)", value: 4 },
  { label: "Colonne F (6ème colonne)", value: 5 },
  { label: "Colonne G (7ème colonne)", value: 6 },
]);

// Fonction pour télécharger un modèle CSV
const downloadTemplate = () => {
  const header = "phoneNumber,firstName,lastName,organization,email,notes\n";
  const example = "+2250123456789,John,Doe,ACME Inc.,john.doe@example.com,Contact important\n";
  const blob = new Blob([header, example], { type: 'text/csv' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = 'contacts_template.csv';
  link.click();
  URL.revokeObjectURL(link.href);
};

// Options pour les filtres avancés
const operatorOptions = ref([
  { label: "Orange", value: "Orange" },
  { label: "MTN", value: "MTN" },
  { label: "Moov", value: "Moov" },
  { label: "Autre", value: "Autre" },
]);

const countryOptions = ref([
  { label: "Côte d'Ivoire", value: "CI" },
  { label: "Sénégal", value: "SN" },
  { label: "Mali", value: "ML" },
  { label: "Burkina Faso", value: "BF" },
  { label: "Autre", value: "Autre" },
]);

// Segments personnalisés (version simplifiée pour le MVP)
const segmentOptions = ref([
  { label: "Segment 1", value: 1 },
  { label: "Segment 2", value: 2 },
  { label: "Segment 3", value: 3 },
]);

// Note: Dans une version complète, nous chargerions les segments depuis l'API GraphQL
// mais pour le MVP, nous utilisons des données statiques

const exportOptions = ref({
  format: "csv",
  search: "",
  limit: 1000,
  includeHeaders: true,
  includeContactInfo: true,
  includeSegments: true,
  delimiter: ",",
  filename: `phone_numbers_export_${new Date().toISOString().slice(0, 10)}`,
  // Options de filtrage avancées
  operator: null,
  country: null,
  segment: null,
  dateFrom: null,
  dateTo: null,
});

// Référence au formulaire pour pouvoir réinitialiser la validation
const importFormRef = ref<QForm | null>(null);

const onImport = async () => {
  if (!csvFile.value) return;

  // Réinitialiser la validation du formulaire avant de soumettre
  importFormRef.value?.resetValidation();
  
  loading.value = true;
  try {
    console.log("Uploading file:", csvFile.value);
    
    // Créer un FormData pour l'upload du fichier
    const formData = new FormData();
    formData.append("csv_file", csvFile.value); // Nom du fichier attendu par le backend
    formData.append("has_header", importOptions.value.hasHeader.toString());
    formData.append("delimiter", importOptions.value.delimiter);
    formData.append("phone_column", importOptions.value.phoneColumn.toString());
    
    // Ajouter les colonnes optionnelles
    if (importOptions.value.nameColumn !== null && importOptions.value.nameColumn !== undefined) {
      formData.append("name_column", String(importOptions.value.nameColumn));
    }
    
    if (importOptions.value.emailColumn !== null && importOptions.value.emailColumn !== undefined) {
      formData.append("email_column", String(importOptions.value.emailColumn));
    }
    
    if (importOptions.value.notesColumn !== null && importOptions.value.notesColumn !== undefined) {
      formData.append("notes_column", String(importOptions.value.notesColumn));
    }

    console.log("FormData prepared, sending request...");

    // Utiliser fetch pour l'upload du fichier avec l'endpoint correct
    const response = await fetch("/api.php?endpoint=import-csv", {
      method: "POST",
      body: formData,
    });

    console.log("Response received:", response);

    if (!response.ok) {
      throw new Error("Erreur lors de l'import");
    }

    const result = await response.json();
    console.log("Import result:", result);

    // Afficher les résultats
    importResults.value = {
      totalRows: result.totalRows || 0,
      successRows: result.successRows || 0,
      errorRows: result.errorRows || 0,
      duplicateCount: result.duplicateCount || 0,
      detailedErrors: result.detailedErrors || []
    };
    showImportResults.value = true;

    // Réinitialiser le formulaire seulement en cas de succès
    if (result.status === 'success') {
      csvFile.value = null;
      // Ne pas réinitialiser la validation ici, car cela pourrait réafficher le message d'erreur
    }
  } catch (error) {
    console.error("Error importing file:", error);
    $q.notify({
      color: "negative",
      message: "Erreur lors de l'import du fichier",
      icon: "error",
    });
  } finally {
    loading.value = false;
  }
};

const onExport = async () => {
  loadingExport.value = true;
  try {
    // Construire l'URL d'export avec tous les paramètres
    const params = new URLSearchParams();

    // Paramètre de base
    params.append(
      "endpoint",
      exportOptions.value.format === "csv" ? "export-csv" : "export-excel",
    );

    // Options de filtrage de base
    if (exportOptions.value.search) {
      params.append("search", exportOptions.value.search);
    }
    params.append("limit", exportOptions.value.limit.toString());

    // Options de filtrage avancées
    if (exportOptions.value.operator) {
      params.append("operator", exportOptions.value.operator);
    }

    if (exportOptions.value.country) {
      params.append("country", exportOptions.value.country);
    }

    if (exportOptions.value.segment) {
      params.append("segment", exportOptions.value.segment);
    }

    if (exportOptions.value.dateFrom) {
      params.append("dateFrom", exportOptions.value.dateFrom);
    }

    if (exportOptions.value.dateTo) {
      params.append("dateTo", exportOptions.value.dateTo);
    }

    // Options d'inclusion
    params.append(
      "include_headers",
      exportOptions.value.includeHeaders.toString(),
    );
    params.append(
      "include_contact_info",
      exportOptions.value.includeContactInfo.toString(),
    );
    params.append(
      "include_segments",
      exportOptions.value.includeSegments.toString(),
    );

    // Options spécifiques au format CSV
    if (exportOptions.value.format === "csv") {
      params.append("delimiter", exportOptions.value.delimiter);
    }

    // Nom du fichier
    if (exportOptions.value.filename) {
      params.append(
        "filename",
        exportOptions.value.filename +
          (exportOptions.value.format === "csv" ? ".csv" : ".xlsx"),
      );
    }

    // Construire l'URL complète
    const url = `/api.php?${params.toString()}`;

    // Ouvrir l'URL dans un nouvel onglet pour télécharger le fichier
    window.open(url, "_blank");

    $q.notify({
      color: "positive",
      message: `Export en ${exportOptions.value.format.toUpperCase()} lancé avec succès`,
      icon: "check_circle",
    });
  } catch (error) {
    console.error("Error exporting data:", error);
    $q.notify({
      color: "negative",
      message: "Erreur lors de l'export",
      icon: "error",
    });
  } finally {
    loadingExport.value = false;
  }
};
</script>
