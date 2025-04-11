import { ref, computed } from "vue";
import { useQuasar, QForm } from "quasar";

// Interfaces
export interface DetailedError {
  line: string | number;
  value: string;
  message: string;
}

export interface ImportResults {
  totalRows: number;
  successRows: number;
  errorRows: number;
  duplicateCount?: number;
  detailedErrors?: DetailedError[];
}

export interface ImportOptions {
  hasHeader: boolean;
  delimiter: string;
  phoneColumn: number;
  nameColumn: number | null;
  emailColumn: number | null;
  notesColumn: number | null;
  createContacts: boolean;
  userId: number | null;
}

export interface ColumnOption {
  label: string;
  value: number;
}

export interface UserOption {
  label: string;
  value: number;
}

export function useImport() {
  const $q = useQuasar();
  
  // États réactifs
  const csvFile = ref(null);
  const loading = ref(false);
  const showImportResults = ref(false);
  const importFormRef = ref<QForm | null>(null);
  
  // Résultats d'import
  const importResults = ref<ImportResults>({
    totalRows: 0,
    successRows: 0,
    errorRows: 0,
    duplicateCount: 0,
    detailedErrors: []
  });
  
  // Options d'import avec valeurs par défaut
  const importOptions = ref<ImportOptions>({
    hasHeader: true,
    delimiter: ",",
    phoneColumn: 0, // Par défaut, première colonne
    nameColumn: null,
    emailColumn: null,
    notesColumn: null,
    createContacts: true, // Par défaut, créer des contacts
    userId: null // Par défaut, utiliser l'utilisateur par défaut
  });
  
  // Options pour les colonnes du CSV
  const columnOptions = ref<ColumnOption[]>([
    { label: "Colonne A (1ère colonne)", value: 0 },
    { label: "Colonne B (2ème colonne)", value: 1 },
    { label: "Colonne C (3ème colonne)", value: 2 },
    { label: "Colonne D (4ème colonne)", value: 3 },
    { label: "Colonne E (5ème colonne)", value: 4 },
    { label: "Colonne F (6ème colonne)", value: 5 },
    { label: "Colonne G (7ème colonne)", value: 6 },
  ]);
  
  // Options pour les utilisateurs
  const userOptions = ref<UserOption[]>([
    { label: "AfricaQSHE (par défaut)", value: 2 },
    { label: "Admin", value: 1 }
    // Dans une version complète, nous chargerions la liste des utilisateurs depuis l'API
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
  
  // Fonction d'import
  const importCSV = async () => {
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
      
      // Ajouter les options de création de contacts
      formData.append("create_contacts", importOptions.value.createContacts.toString());
      if (importOptions.value.userId !== null && importOptions.value.userId !== undefined) {
        formData.append("user_id", String(importOptions.value.userId));
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
      
      return result;
    } catch (error) {
      console.error("Error importing file:", error);
      $q.notify({
        color: "negative",
        message: "Erreur lors de l'import du fichier",
        icon: "error",
      });
      return null;
    } finally {
      loading.value = false;
    }
  };
  
  return {
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
  };
}
