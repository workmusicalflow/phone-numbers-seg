<template>
  <q-dialog
    :model-value="visible"
    @update:model-value="handleVisibilityChange"
    persistent
    transition-show="fade"
    transition-hide="fade"
  >
    <q-card class="import-dialog">
      <!-- Dialog Header -->
      <q-card-section class="dialog-header contacts-gradient">
        <div class="header-content">
          <q-icon name="import_export" size="lg" class="header-icon" />
          <div class="header-text">
            <h3 class="header-title">Importer des Contacts</h3>
            <p class="header-subtitle">Ajoutez plusieurs contacts depuis un fichier CSV</p>
          </div>
        </div>
        <div class="header-actions">
          <q-btn
            flat
            round
            color="white"
            icon="close"
            @click="handleClose"
            class="close-btn"
          />
        </div>
      </q-card-section>

      <!-- Dialog Content -->
      <q-card-section class="dialog-content">
        <!-- Import en cours -->
        <div v-if="state.importing" class="import-progress">
          <div class="progress-header">
            <q-icon name="upload" size="2rem" color="primary" />
            <h4>Import en cours...</h4>
          </div>
          
          <q-linear-progress
            :value="state.progress / 100"
            size="12px"
            color="primary"
            class="progress-bar"
          />
          
          <p class="progress-text">{{ state.progress }}% terminé</p>
          
          <div class="progress-info">
            <q-icon name="info" color="info" />
            <span>Traitement du fichier, veuillez patienter...</span>
          </div>
        </div>

        <!-- Interface d'import -->
        <div v-else class="import-interface">
          <!-- Instructions -->
          <div class="import-section">
            <h4 class="section-title">
              <q-icon name="info" class="section-icon" />
              Format de fichier
            </h4>
            <div class="format-info">
              <p class="section-text">
                Importez un fichier CSV avec les colonnes suivantes :
              </p>
              <div class="required-columns">
                <q-chip
                  v-for="column in importConfig.requiredColumns"
                  :key="column"
                  color="primary"
                  text-color="white"
                  icon="check"
                  size="sm"
                >
                  {{ column }}
                </q-chip>
              </div>
              <p class="format-details">
                Colonnes optionnelles : email, notes<br>
                Taille maximum : {{ Math.round(importConfig.maxFileSize / 1024 / 1024) }}MB<br>
                Lignes maximum : {{ importConfig.maxRows.toLocaleString() }}
              </p>
            </div>
          </div>

          <!-- Sélection de fichier -->
          <div class="import-section">
            <h4 class="section-title">
              <q-icon name="attach_file" class="section-icon" />
              Sélectionner un fichier
            </h4>
            
            <div class="file-upload-area">
              <q-file
                v-model="fileModel"
                label="Choisir un fichier CSV"
                outlined
                accept=".csv"
                :max-file-size="importConfig.maxFileSize"
                class="file-input"
                @update:model-value="handleFileSelect"
              >
                <template v-slot:prepend>
                  <q-icon name="attach_file" />
                </template>
                
                <template v-slot:append>
                  <q-icon 
                    v-if="hasFile" 
                    name="check_circle" 
                    color="positive" 
                  />
                </template>
              </q-file>

              <!-- Informations sur le fichier -->
              <div v-if="fileInfo" class="file-info">
                <div class="file-details">
                  <q-icon name="description" color="primary" />
                  <div class="file-meta">
                    <p class="file-name">{{ fileInfo.name }}</p>
                    <p class="file-size">{{ formatFileSize(fileInfo.size) }}</p>
                  </div>
                </div>
                
                <q-btn
                  flat
                  round
                  icon="close"
                  color="negative"
                  size="sm"
                  @click="clearFile"
                  class="remove-file-btn"
                />
              </div>
            </div>
          </div>

          <!-- Aperçu du fichier (si analysé) -->
          <div v-if="filePreview" class="import-section">
            <h4 class="section-title">
              <q-icon name="preview" class="section-icon" />
              Aperçu du fichier
            </h4>
            
            <div class="preview-info">
              <div class="preview-stats">
                <q-chip color="info" text-color="white" icon="table_rows">
                  {{ filePreview.rows }} lignes
                </q-chip>
                <q-chip color="info" text-color="white" icon="table_chart">
                  {{ filePreview.columns.length }} colonnes
                </q-chip>
              </div>
              
              <div v-if="filePreview.errors.length > 0" class="preview-errors">
                <q-banner class="text-white bg-negative">
                  <template v-slot:avatar>
                    <q-icon name="error" color="white" />
                  </template>
                  <strong>Problèmes détectés :</strong>
                  <ul class="error-list">
                    <li v-for="error in filePreview.errors" :key="error">{{ error }}</li>
                  </ul>
                </q-banner>
              </div>
              
              <div v-else class="preview-success">
                <q-banner class="text-white bg-positive">
                  <template v-slot:avatar>
                    <q-icon name="check_circle" color="white" />
                  </template>
                  Fichier valide et prêt pour l'import !
                </q-banner>
              </div>
            </div>
          </div>

          <!-- Message d'erreur -->
          <div v-if="hasError" class="error-section">
            <q-banner class="text-white bg-negative">
              <template v-slot:avatar>
                <q-icon name="error" color="white" />
              </template>
              <strong>Erreur :</strong> {{ state.error }}
            </q-banner>
          </div>
        </div>
      </q-card-section>

      <!-- Dialog Actions -->
      <q-card-actions v-if="!state.importing" align="right" class="dialog-actions">
        <q-btn
          flat
          color="grey-7"
          label="Annuler"
          @click="handleClose"
          class="action-btn-secondary"
        />
        <q-btn
          color="primary"
          icon="upload"
          label="Importer"
          @click="processImport"
          :disable="!canImportFile"
          :loading="state.importing"
          class="action-btn-primary"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useContactImport } from '../composables/useContactImport';
import type { ContactImportDialogProps } from '../types/contacts.types';

// Props
const props = defineProps<ContactImportDialogProps>();

// Events
const emit = defineEmits<{
  'update:visible': [visible: boolean];
  'close': [];
  'import-success': [count: number];
  'import-error': [error: string];
}>();

// Composable
const {
  importConfig,
  hasFile,
  hasError,
  selectFile,
  processImport: processImportFile,
  analyzeFile,
  clearFile: clearSelectedFile,
  resetImport
} = useContactImport();

// État local
const fileModel = ref<File | null>(null);
const fileInfo = ref<File | null>(null);
const filePreview = ref<any>(null);

// Computed pour synchroniser avec les props
const state = computed(() => props.state);

// Computed pour déterminer si l'import est possible (local)
const canImportFile = computed(() => {
  return hasFile.value && 
         !state.value.importing && 
         (!filePreview.value || filePreview.value.valid);
});

// Methods
function handleVisibilityChange(visible: boolean): void {
  emit('update:visible', visible);
  if (!visible) {
    handleClose();
  }
}

function handleClose(): void {
  resetImport();
  fileModel.value = null;
  fileInfo.value = null;
  filePreview.value = null;
  emit('close');
}

async function handleFileSelect(file: File | null): Promise<void> {
  filePreview.value = null;
  
  if (!file) {
    clearSelectedFile();
    fileInfo.value = null;
    return;
  }

  const success = selectFile(file);
  if (success) {
    fileInfo.value = file;
    
    // Analyser le fichier pour l'aperçu
    try {
      filePreview.value = await analyzeFile(file);
    } catch (error) {
      console.error('Erreur lors de l\'analyse du fichier:', error);
    }
  } else {
    fileModel.value = null;
    fileInfo.value = null;
  }
}

function clearFile(): void {
  fileModel.value = null;
  fileInfo.value = null;
  filePreview.value = null;
  clearSelectedFile();
}

async function processImport(): Promise<void> {
  try {
    const result = await processImportFile();
    
    if (result.success && result.count) {
      emit('import-success', result.count);
      handleClose();
    } else if (result.errors) {
      emit('import-error', result.errors.join(', '));
    }
  } catch (error: any) {
    emit('import-error', error.message || 'Erreur lors de l\'import');
  }
}

function formatFileSize(bytes: number): string {
  if (bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Watcher pour synchroniser le fichier avec le composable
watch(() => props.state.file, (newFile) => {
  if (!newFile && fileModel.value) {
    clearFile();
  }
});
</script>

<style lang="scss" scoped>
// Contacts Color Palette
$contacts-primary: #673ab7;
$contacts-secondary: #9c27b0;

.import-dialog {
  min-width: 600px;
  max-width: 800px;
  width: 90vw;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

// Dialog Header
.dialog-header {
  &.contacts-gradient {
    background: linear-gradient(135deg, $contacts-primary 0%, $contacts-secondary 100%);
  }
  
  display: flex;
  justify-content: space-between;
  align-items: center;
  
  .header-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    
    .header-icon {
      color: white;
      opacity: 0.9;
    }
    
    .header-text {
      color: white;
      
      .header-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0 0 0.25rem 0;
      }
      
      .header-subtitle {
        font-size: 0.9rem;
        margin: 0;
        opacity: 0.8;
      }
    }
  }
  
  .close-btn {
    border-radius: 8px;
    
    &:hover {
      background: rgba(255, 255, 255, 0.1);
    }
  }
}

// Dialog Content
.dialog-content {
  flex: 1;
  overflow: auto;
  
  .import-progress {
    text-align: center;
    padding: 2rem;
    
    .progress-header {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 1rem;
      margin-bottom: 2rem;
      
      h4 {
        margin: 0;
        font-size: 1.25rem;
        color: #333;
      }
    }
    
    .progress-bar {
      margin-bottom: 1rem;
    }
    
    .progress-text {
      font-size: 1.1rem;
      font-weight: 600;
      color: $contacts-primary;
      margin-bottom: 1rem;
    }
    
    .progress-info {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      color: #666;
    }
  }
  
  .import-interface {
    .import-section {
      margin-bottom: 2rem;
      
      &:last-child {
        margin-bottom: 0;
      }
      
      .section-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 1.1rem;
        font-weight: 600;
        color: #333;
        margin: 0 0 1rem 0;
        
        .section-icon {
          color: $contacts-primary;
        }
      }
      
      .section-text {
        color: #666;
        line-height: 1.5;
        margin-bottom: 1rem;
      }
      
      .format-info {
        .required-columns {
          display: flex;
          gap: 0.5rem;
          flex-wrap: wrap;
          margin: 1rem 0;
        }
        
        .format-details {
          font-size: 0.9rem;
          color: #888;
          line-height: 1.4;
        }
      }
      
      .file-upload-area {
        .file-input {
          margin-bottom: 1rem;
          
          :deep(.q-field__control) {
            border-radius: 12px;
          }
        }
        
        .file-info {
          display: flex;
          align-items: center;
          justify-content: space-between;
          background: #f8f9fa;
          border: 1px solid #e9ecef;
          border-radius: 8px;
          padding: 1rem;
          
          .file-details {
            display: flex;
            align-items: center;
            gap: 1rem;
            
            .file-meta {
              .file-name {
                font-weight: 500;
                margin: 0 0 0.25rem 0;
              }
              
              .file-size {
                font-size: 0.85rem;
                color: #666;
                margin: 0;
              }
            }
          }
        }
      }
      
      .preview-info {
        .preview-stats {
          display: flex;
          gap: 0.5rem;
          margin-bottom: 1rem;
        }
        
        .error-list {
          margin: 0.5rem 0 0 1rem;
          padding: 0;
        }
      }
    }
  }
  
  .error-section {
    margin-top: 1rem;
  }
}

// Dialog Actions
.dialog-actions {
  border-top: 1px solid #e9ecef;
  background: #fafafa;
  padding: 1.5rem 2rem;
  
  .action-btn-primary {
    background: linear-gradient(135deg, $contacts-primary 0%, $contacts-secondary 100%);
    color: white;
    font-weight: 600;
    border-radius: 8px;
    text-transform: none;
    
    &:hover:not(:disabled) {
      box-shadow: 0 4px 12px rgba(103, 58, 183, 0.3);
    }
  }
  
  .action-btn-secondary {
    color: #666;
    border-radius: 8px;
    text-transform: none;
    
    &:hover {
      background: #f5f5f5;
    }
  }
}

// Responsive Design
@media (max-width: 768px) {
  .import-dialog {
    min-width: auto;
    width: 95vw;
    margin: 1rem;
  }
  
  .dialog-header {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
    
    .header-actions {
      position: absolute;
      top: 1rem;
      right: 1rem;
    }
  }
  
  .dialog-content {
    .import-interface {
      .import-section {
        .format-info {
          .required-columns {
            justify-content: center;
          }
        }
      }
    }
  }
  
  .dialog-actions {
    flex-direction: column;
    gap: 0.5rem;
    
    .q-btn {
      width: 100%;
    }
  }
}
</style>