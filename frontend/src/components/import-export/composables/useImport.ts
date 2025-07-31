import { ref } from "vue";
import { useQuasar, QForm } from "quasar";
import { gql } from '@apollo/client/core';
import { useApolloClient } from '@vue/apollo-composable';
import { useAuthStore } from '../../../stores/authStore';

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
  groupAssignments?: number;
  groupAssignmentErrors?: number;
  skippedRows?: number;
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
  groupIds: number[];
}

export interface ColumnOption {
  label: string;
  value: number;
}


export interface GroupOption {
  label: string;
  value: number;
}

export function useImport() {
  const $q = useQuasar();
  const { client } = useApolloClient();
  const authStore = useAuthStore();
  
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
    userId: null, // Sera automatiquement défini avec l'utilisateur connecté
    groupIds: [] // Par défaut, aucun groupe sélectionné
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
  
  // Les options utilisateur ne sont plus nécessaires

  // Options pour les groupes de contacts
  const groupOptions = ref<GroupOption[]>([]);
  const loadingGroups = ref(false);
  
  // Fonction pour charger les groupes de contacts
  const loadContactGroups = async () => {
    loadingGroups.value = true;
    try {
      const { data, errors } = await client.query({
        query: gql`
          query GetContactGroups {
            contactGroups(limit: 100) {
              id
              name
              description
              contactCount
            }
          }
        `,
        fetchPolicy: 'network-only'
      });

      if (errors) {
        throw new Error(errors.map((e: any) => e.message).join(', '));
      }

      if (data?.contactGroups) {
        groupOptions.value = data.contactGroups.map((group: any) => ({
          label: `${group.name} (${group.contactCount} contacts)`,
          value: parseInt(group.id)
        }));
      }
    } catch (error) {
      console.error('Error loading contact groups:', error);
      $q.notify({
        color: "negative",
        message: "Erreur lors du chargement des groupes",
        icon: "error",
      });
    } finally {
      loadingGroups.value = false;
    }
  };

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
  
  // Fonction d'import avec GraphQL
  const importCSV = async () => {
    if (!csvFile.value) return;

    // Réinitialiser la validation du formulaire avant de soumettre
    importFormRef.value?.resetValidation();
    
    loading.value = true;
    try {
      console.log("Processing CSV file:", csvFile.value);
      
      // Lire le fichier CSV
      const csvText = await readFileAsText(csvFile.value);
      const phoneData = parseCSVToPhoneData(csvText);
      
      if (phoneData.length === 0) {
        throw new Error("Aucune donnée valide trouvée dans le fichier CSV");
      }

      console.log("CSV parsed, importing via GraphQL...", phoneData.length, "records");

      // Utiliser la mutation GraphQL pour l'import
      const { data, errors } = await client.mutate({
        mutation: gql`
          mutation ImportPhoneNumbersWithData(
            $phoneData: [PhoneDataInput!]!
            $skipInvalid: Boolean
            $segmentImmediately: Boolean
            $groupIds: [Int!]
            $userId: Int
          ) {
            importPhoneNumbersWithData(
              phoneData: $phoneData
              skipInvalid: $skipInvalid
              segmentImmediately: $segmentImmediately
              groupIds: $groupIds
              userId: $userId
            ) {
              status
              errors
              stats {
                processed
                successful
                failed
                skipped
                duplicates
                groupAssignments
              }
            }
          }
        `,
        variables: {
          phoneData: phoneData,
          skipInvalid: true,
          segmentImmediately: true,
          groupIds: importOptions.value.groupIds.length > 0 ? importOptions.value.groupIds : null,
          userId: authStore.user?.id || null // Utiliser l'utilisateur connecté
        }
      });

      if (errors) {
        throw new Error(errors.map((e: any) => e.message).join(', '));
      }

      const result = data?.importPhoneNumbersWithData;
      if (!result) {
        throw new Error("Aucune réponse du serveur");
      }

      console.log("Import result:", result);

      // Afficher les résultats
      importResults.value = {
        totalRows: result.stats.processed || 0,
        successRows: result.stats.successful || 0,
        errorRows: result.stats.failed || 0,
        duplicateCount: result.stats.duplicates || 0,
        skippedRows: result.stats.skipped || 0,
        groupAssignments: result.stats.groupAssignments || 0,
        groupAssignmentErrors: 0, // We'll calculate this from failed assignments
        detailedErrors: result.errors?.map((error: string, index: number) => ({
          line: index + 1,
          value: '',
          message: error
        })) || []
      };
      showImportResults.value = true;

      // Afficher notification de succès/erreur avec détails
      if (result.status === 'success') {
        let message = `Import réussi : ${result.stats.successful} contacts importés`;
        if (result.stats.groupAssignments > 0) {
          message += `, ${result.stats.groupAssignments} assignations aux groupes`;
        }
        if (result.stats.skipped > 0) {
          message += `, ${result.stats.skipped} lignes ignorées`;
        }
        if (result.stats.duplicates > 0) {
          message += `, ${result.stats.duplicates} doublons détectés`;
        }
        
        $q.notify({
          color: "positive",
          message: message,
          icon: "check",
          timeout: 8000, // Longer timeout for detailed messages
          position: 'top',
          actions: [
            {
              label: 'Voir détails',
              color: 'white',
              handler: () => { showImportResults.value = true; }
            }
          ]
        });
        csvFile.value = null;
      } else if (result.status === 'error') {
        const errorCount = result.errors?.length || 0;
        const errorMessage = `Erreur d'import : ${errorCount} erreur${errorCount > 1 ? 's' : ''} détectée${errorCount > 1 ? 's' : ''}`;
        
        $q.notify({
          color: "negative",
          message: errorMessage,
          icon: "error",
          timeout: 8000,
          position: 'top',
          actions: [
            {
              label: 'Voir détails',
              color: 'white',
              handler: () => { showImportResults.value = true; }
            }
          ]
        });
      }
      
      return result;
    } catch (error) {
      console.error("Error importing file:", error);
      $q.notify({
        color: "negative",
        message: `Erreur lors de l'import du fichier: ${error}`,
        icon: "error",
      });
      return null;
    } finally {
      loading.value = false;
    }
  };

  // Fonction utilitaire pour lire un fichier comme texte
  const readFileAsText = (file: File): Promise<string> => {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = (e) => resolve(e.target?.result as string);
      reader.onerror = () => reject(reader.error);
      reader.readAsText(file);
    });
  };

  // Fonction utilitaire pour parser le CSV en PhoneDataInput
  const parseCSVToPhoneData = (csvText: string) => {
    const lines = csvText.trim().split('\n');
    const startIndex = importOptions.value.hasHeader ? 1 : 0;
    const phoneData = [];

    for (let i = startIndex; i < lines.length; i++) {
      const values = lines[i].split(importOptions.value.delimiter);
      
      if (values.length > importOptions.value.phoneColumn && values[importOptions.value.phoneColumn].trim()) {
        const phoneNumber = values[importOptions.value.phoneColumn].trim();
        const dataItem: any = { number: phoneNumber };

        // Ajouter les colonnes optionnelles si spécifiées
        if (importOptions.value.nameColumn !== null && values[importOptions.value.nameColumn]) {
          dataItem.name = values[importOptions.value.nameColumn].trim();
        }
        if (importOptions.value.emailColumn !== null && values[importOptions.value.emailColumn]) {
          dataItem.email = values[importOptions.value.emailColumn].trim();
        }
        if (importOptions.value.notesColumn !== null && values[importOptions.value.notesColumn]) {
          dataItem.notes = values[importOptions.value.notesColumn].trim();
        }

        phoneData.push(dataItem);
      }
    }

    return phoneData;
  };
  
  return {
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
  };
}
