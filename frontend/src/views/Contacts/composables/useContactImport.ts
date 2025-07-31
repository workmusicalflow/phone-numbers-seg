/**
 * Composable pour l'import de contacts via CSV
 * Gère la validation, le traitement et le suivi de progression
 */

import { ref, computed } from 'vue';
import { useQuasar } from 'quasar';
import type { ImportState } from '../types/contacts.types';

export function useContactImport() {
  // Dependencies
  const $q = useQuasar();

  // État de l'import
  const importState = ref<ImportState>({
    importing: false,
    file: null,
    progress: 0,
    error: null
  });

  // Computed properties
  const isImporting = computed(() => importState.value.importing);
  const hasFile = computed(() => !!importState.value.file);
  const hasError = computed(() => !!importState.value.error);
  const canImport = computed(() => hasFile.value && !isImporting.value);

  // Configuration d'import
  const importConfig = {
    maxFileSize: 5242880, // 5MB
    allowedExtensions: ['.csv'],
    supportedFormats: ['text/csv', 'application/vnd.ms-excel'],
    maxRows: 10000,
    requiredColumns: ['nom', 'numero'] // ou ['name', 'number']
  };

  /**
   * Valide un fichier avant import
   */
  function validateFile(file: File): { valid: boolean; error?: string } {
    // Vérifier l'extension
    const extension = '.' + file.name.split('.').pop()?.toLowerCase();
    if (!importConfig.allowedExtensions.includes(extension)) {
      return {
        valid: false,
        error: `Extension non supportée. Utilisez: ${importConfig.allowedExtensions.join(', ')}`
      };
    }

    // Vérifier la taille
    if (file.size > importConfig.maxFileSize) {
      const maxSizeMB = Math.round(importConfig.maxFileSize / 1024 / 1024);
      return {
        valid: false,
        error: `Fichier trop volumineux. Taille maximum: ${maxSizeMB}MB`
      };
    }

    // Vérifier le type MIME
    if (!importConfig.supportedFormats.includes(file.type) && file.type !== '') {
      return {
        valid: false,
        error: 'Format de fichier non supporté'
      };
    }

    return { valid: true };
  }

  /**
   * Sélectionne un fichier pour import
   */
  function selectFile(file: File): boolean {
    importState.value.error = null;
    
    const validation = validateFile(file);
    if (!validation.valid) {
      importState.value.error = validation.error || 'Fichier invalide';
      importState.value.file = null;
      return false;
    }

    importState.value.file = file;
    return true;
  }

  /**
   * Simule le traitement d'import CSV
   * TODO: Remplacer par l'implémentation réelle de l'API
   */
  async function processImport(): Promise<{ success: boolean; count?: number; errors?: string[] }> {
    if (!importState.value.file) {
      throw new Error('Aucun fichier sélectionné');
    }

    importState.value.importing = true;
    importState.value.progress = 0;
    importState.value.error = null;

    try {
      // Simulation du traitement
      for (let i = 0; i <= 100; i += 10) {
        await new Promise(resolve => setTimeout(resolve, 200));
        importState.value.progress = i;
      }

      // TODO: Implémentation réelle
      // const result = await importAPI.uploadCSV(importState.value.file);
      
      // Simulation d'un succès
      const simulatedResult = {
        success: true,
        count: Math.floor(Math.random() * 50) + 10, // 10-59 contacts
        errors: []
      };

      if (simulatedResult.success) {
        $q.notify({
          color: 'positive',
          message: `${simulatedResult.count} contacts importés avec succès`,
          icon: 'upload',
          position: 'top'
        });
      }

      return simulatedResult;
    } catch (error: any) {
      const errorMessage = error.message || 'Erreur lors de l\'import des contacts';
      importState.value.error = errorMessage;

      $q.notify({
        color: 'negative',
        message: errorMessage,
        icon: 'error',
        position: 'top'
      });

      return { success: false, errors: [errorMessage] };
    } finally {
      importState.value.importing = false;
      importState.value.progress = 0;
    }
  }

  /**
   * Analyse le contenu du fichier CSV (preview)
   */
  async function analyzeFile(file: File): Promise<{
    rows: number;
    columns: string[];
    preview: any[];
    valid: boolean;
    errors: string[];
  }> {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      
      reader.onload = (e) => {
        try {
          const csv = e.target?.result as string;
          const lines = csv.split('\n').filter(line => line.trim());
          
          if (lines.length === 0) {
            resolve({
              rows: 0,
              columns: [],
              preview: [],
              valid: false,
              errors: ['Fichier vide']
            });
            return;
          }

          // Analyser l'en-tête
          const headers = lines[0].split(',').map(h => h.trim().replace(/"/g, ''));
          
          // Prévisualiser les premières lignes
          const preview = lines.slice(1, 6).map(line => {
            const values = line.split(',').map(v => v.trim().replace(/"/g, ''));
            const row: any = {};
            headers.forEach((header, index) => {
              row[header] = values[index] || '';
            });
            return row;
          });

          // Validation
          const errors: string[] = [];
          const hasRequiredColumns = importConfig.requiredColumns.some(col => 
            headers.some(header => 
              header.toLowerCase().includes(col.toLowerCase())
            )
          );

          if (!hasRequiredColumns) {
            errors.push(`Colonnes requises manquantes: ${importConfig.requiredColumns.join(', ')}`);
          }

          if (lines.length > importConfig.maxRows) {
            errors.push(`Trop de lignes. Maximum: ${importConfig.maxRows}`);
          }

          resolve({
            rows: lines.length - 1, // Exclure l'en-tête
            columns: headers,
            preview,
            valid: errors.length === 0,
            errors
          });
        } catch (error) {
          reject(new Error('Erreur lors de l\'analyse du fichier CSV'));
        }
      };

      reader.onerror = () => {
        reject(new Error('Erreur lors de la lecture du fichier'));
      };

      reader.readAsText(file);
    });
  }

  /**
   * Remet à zéro l'état d'import
   */
  function resetImport(): void {
    importState.value = {
      importing: false,
      file: null,
      progress: 0,
      error: null
    };
  }

  /**
   * Supprime le fichier sélectionné
   */
  function clearFile(): void {
    importState.value.file = null;
    importState.value.error = null;
    importState.value.progress = 0;
  }

  return {
    // État
    importState,
    isImporting,
    hasFile,
    hasError,
    canImport,
    importConfig,

    // Actions
    selectFile,
    processImport,
    analyzeFile,
    resetImport,
    clearFile,
    
    // Utilitaires
    validateFile
  };
}