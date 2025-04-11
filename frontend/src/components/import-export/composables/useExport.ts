import { ref } from "vue";
import { useQuasar } from "quasar";

// Interfaces
export interface ExportOptions {
  format: 'csv' | 'excel';
  search: string;
  limit: number;
  includeHeaders: boolean;
  includeContactInfo: boolean;
  includeSegments: boolean;
  delimiter: string;
  filename: string;
  operator: string | null;
  country: string | null;
  segment: number | null;
  dateFrom: string | null;
  dateTo: string | null;
}

export interface OperatorOption {
  label: string;
  value: string;
}

export interface CountryOption {
  label: string;
  value: string;
}

export interface SegmentOption {
  label: string;
  value: number;
}

export function useExport() {
  const $q = useQuasar();
  
  // État réactif
  const loadingExport = ref(false);
  
  // Options d'export avec valeurs par défaut
  const exportOptions = ref<ExportOptions>({
    format: "csv",
    search: "",
    limit: 1000,
    includeHeaders: true,
    includeContactInfo: true,
    includeSegments: true,
    delimiter: ",",
    filename: `phone_numbers_export_${new Date().toISOString().slice(0, 10)}`,
    operator: null,
    country: null,
    segment: null,
    dateFrom: null,
    dateTo: null,
  });
  
  // Options pour les filtres avancés
  const operatorOptions = ref<OperatorOption[]>([
    { label: "Orange", value: "Orange" },
    { label: "MTN", value: "MTN" },
    { label: "Moov", value: "Moov" },
    { label: "Autre", value: "Autre" },
  ]);

  const countryOptions = ref<CountryOption[]>([
    { label: "Côte d'Ivoire", value: "CI" },
    { label: "Sénégal", value: "SN" },
    { label: "Mali", value: "ML" },
    { label: "Burkina Faso", value: "BF" },
    { label: "Autre", value: "Autre" },
  ]);

  // Segments personnalisés (version simplifiée pour le MVP)
  const segmentOptions = ref<SegmentOption[]>([
    { label: "Segment 1", value: 1 },
    { label: "Segment 2", value: 2 },
    { label: "Segment 3", value: 3 },
  ]);
  
  // Fonction d'export
  const exportData = async () => {
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
        params.append("segment", exportOptions.value.segment.toString());
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
      
      return true;
    } catch (error) {
      console.error("Error exporting data:", error);
      $q.notify({
        color: "negative",
        message: "Erreur lors de l'export",
        icon: "error",
      });
      return false;
    } finally {
      loadingExport.value = false;
    }
  };
  
  return {
    loadingExport,
    exportOptions,
    operatorOptions,
    countryOptions,
    segmentOptions,
    exportData
  };
}
