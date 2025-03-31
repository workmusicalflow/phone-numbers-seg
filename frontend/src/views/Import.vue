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
                (obligatoire), civility, firstName, name, company, sector, notes
              </div>
            </q-card-section>

            <q-card-section>
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

                <q-input
                  v-model="importOptions.delimiter"
                  label="Délimiteur"
                  :rules="[(val) => !!val || 'Le délimiteur est requis']"
                  maxlength="1"
                />

                <div>
                  <q-btn
                    label="Importer"
                    type="submit"
                    color="primary"
                    :loading="loading"
                  />
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
        <q-card style="min-width: 350px">
          <q-card-section>
            <div class="text-h6">Résultats de l'import</div>
          </q-card-section>

          <q-card-section>
            <p>Nombre total de lignes: {{ importResults.totalRows }}</p>
            <p>Lignes importées avec succès: {{ importResults.successRows }}</p>
            <p>Lignes en erreur: {{ importResults.errorRows }}</p>
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
import { useQuasar } from "quasar";

const apolloClient = useApolloClient();
const $q = useQuasar();

const csvFile = ref(null);
const loading = ref(false);
const loadingExport = ref(false);
const showImportResults = ref(false);
const importResults = ref({
  totalRows: 0,
  successRows: 0,
  errorRows: 0,
});

const importOptions = ref({
  hasHeader: true,
  delimiter: ",",
});

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

// Récupérer les segments personnalisés depuis l'API GraphQL
const segmentOptions = ref([]);
const loadSegments = async () => {
  try {
    const { data } = await apolloClient.value.query({
      query: gql`
        query GetCustomSegments {
          customSegments {
            id
            name
          }
        }
      `,
    });

    if (data && data.customSegments) {
      segmentOptions.value = data.customSegments.map((segment) => ({
        label: segment.name,
        value: segment.id,
      }));
    }
  } catch (error) {
    console.error("Error loading segments:", error);
  }
};

// Charger les segments au montage du composant
loadSegments();

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

const onImport = async () => {
  if (!csvFile.value) return;

  loading.value = true;
  try {
    // Créer un FormData pour l'upload du fichier
    const formData = new FormData();
    formData.append("file", csvFile.value);
    formData.append("hasHeader", importOptions.value.hasHeader.toString());
    formData.append("delimiter", importOptions.value.delimiter);

    // Utiliser fetch pour l'upload du fichier
    const response = await fetch("/api.php/import", {
      method: "POST",
      body: formData,
    });

    if (!response.ok) {
      throw new Error("Erreur lors de l'import");
    }

    const result = await response.json();

    // Afficher les résultats
    importResults.value = {
      totalRows: result.totalRows,
      successRows: result.successRows,
      errorRows: result.errorRows,
    };
    showImportResults.value = true;

    // Réinitialiser le formulaire
    csvFile.value = null;
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
