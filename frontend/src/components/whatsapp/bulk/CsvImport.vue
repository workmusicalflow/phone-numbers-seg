<template>
  <div class="csv-import">
    <div class="upload-section">
      <div class="upload-header">
        <q-icon name="upload_file" class="upload-icon" />
        <span class="upload-label">Import depuis un fichier CSV</span>
      </div>
      
      <q-file
        v-model="selectedFile"
        label="Sélectionner un fichier CSV"
        outlined
        accept=".csv,.txt"
        max-file-size="5242880"
        @rejected="onFileRejected"
        @update:model-value="onFileSelected"
        class="file-input"
      >
        <template v-slot:prepend>
          <q-icon name="attach_file" />
        </template>
      </q-file>
      
      <div class="upload-help">
        <q-icon name="info" size="14px" class="q-mr-xs" />
        <span class="help-text">
          Fichier CSV/TXT avec un numéro par ligne (max 5MB). Format: +225XXXXXXXX
        </span>
      </div>
    </div>
    
    <!-- Aperçu du fichier -->
    <div v-if="filePreview.length > 0" class="preview-section">
      <div class="preview-header">
        <q-icon name="preview" class="preview-icon" />
        <span class="preview-label">Aperçu du fichier ({{ filePreview.length }} ligne{{ filePreview.length > 1 ? 's' : '' }})</span>
      </div>
      
      <div class="preview-content">
        <div class="preview-stats">
          <div class="stat-item">
            <q-icon name="check_circle" class="stat-icon valid" />
            <span class="stat-value">{{ validNumbers.length }}</span>
            <span class="stat-label">Valides</span>
          </div>
          <div class="stat-item">
            <q-icon name="error" class="stat-icon invalid" />
            <span class="stat-value">{{ invalidNumbers.length }}</span>
            <span class="stat-label">Invalides</span>
          </div>
          <div class="stat-item">
            <q-icon name="content_copy" class="stat-icon duplicate" />
            <span class="stat-value">{{ duplicateNumbers.length }}</span>
            <span class="stat-label">Doublons</span>
          </div>
        </div>
        
        <div class="preview-list">
          <q-virtual-scroll
            :items="previewItems"
            separator
            v-slot="{ item, index }"
            style="max-height: 200px;"
          >
            <q-item 
              :key="index"
              class="preview-item"
              :class="item.status"
            >
              <q-item-section avatar>
                <q-icon 
                  :name="getStatusIcon(item.status)"
                  :color="getStatusColor(item.status)"
                  size="16px"
                />
              </q-item-section>
              
              <q-item-section>
                <q-item-label class="phone-number">{{ item.number }}</q-item-label>
                <q-item-label v-if="item.error" caption class="error-message">
                  {{ item.error }}
                </q-item-label>
              </q-item-section>
            </q-item>
          </q-virtual-scroll>
        </div>
      </div>
    </div>
    
    <!-- Configuration d'import -->
    <div v-if="selectedFile" class="config-section">
      <div class="config-header">
        <q-icon name="settings" class="config-icon" />
        <span class="config-label">Configuration d'import</span>
      </div>
      
      <div class="config-options">
        <q-select
          v-model="columnIndex"
          :options="columnOptions"
          label="Colonne des numéros"
          outlined
          dense
          emit-value
          map-options
          class="column-select"
        />
        
        <q-toggle
          v-model="skipInvalid"
          label="Ignorer les numéros invalides"
          color="primary"
          class="skip-toggle"
        />
        
        <q-toggle
          v-model="allowDuplicates"
          label="Autoriser les doublons"
          color="primary"
          class="duplicate-toggle"
        />
      </div>
    </div>
    
    <!-- Actions -->
    <div v-if="selectedFile" class="actions-section">
      <q-btn
        color="primary"
        icon="download"
        label="Importer les numéros"
        @click="importNumbers"
        :disable="!canImport"
        class="import-btn"
      />
      
      <q-btn
        flat
        icon="clear"
        label="Annuler"
        @click="clearFile"
        class="cancel-btn"
      />
      
      <div v-if="!canImport" class="import-warning">
        <q-icon name="warning" size="14px" class="q-mr-xs" />
        <span class="warning-text">Aucun numéro valide à importer</span>
      </div>
    </div>
    
    <!-- Zone de glisser-déposer -->
    <div 
      v-if="!selectedFile"
      @drop="onDrop"
      @dragover="onDragOver"
      @dragenter="onDragEnter"
      @dragleave="onDragLeave"
      :class="{ 'drag-over': isDragOver }"
      class="drop-zone"
    >
      <q-icon name="cloud_upload" size="48px" class="drop-icon" />
      <p class="drop-message">Glissez-déposez votre fichier CSV ici</p>
      <p class="drop-hint">ou cliquez sur "Sélectionner un fichier" ci-dessus</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useQuasar } from 'quasar'

interface PreviewItem {
  number: string
  status: 'valid' | 'invalid' | 'duplicate'
  error?: string
}

interface Emits {
  (e: 'recipients-imported', value: string[]): void
}

const emit = defineEmits<Emits>()
const $q = useQuasar()

const selectedFile = ref<File | null>(null)
const fileContent = ref('')
const filePreview = ref<string[]>([])
const columnIndex = ref(0)
const skipInvalid = ref(true)
const allowDuplicates = ref(false)
const isDragOver = ref(false)

// Validation du numéro de téléphone
const phoneRegex = /^\+[1-9]\d{1,14}$/

// Computed properties
const columnOptions = computed(() => {
  if (filePreview.value.length === 0) return []
  
  // Déterminer le nombre de colonnes en analysant la première ligne
  const firstLine = filePreview.value[0]
  const separators = [',', ';', '\t', '|']
  let maxColumns = 1
  let bestSeparator = ','
  
  separators.forEach(sep => {
    const columns = firstLine.split(sep)
    if (columns.length > maxColumns) {
      maxColumns = columns.length
      bestSeparator = sep
    }
  })
  
  const options = []
  for (let i = 0; i < maxColumns; i++) {
    options.push({
      label: `Colonne ${i + 1}`,
      value: i
    })
  }
  
  return options
})

const validNumbers = computed(() => {
  return getNumbersFromColumn().filter(num => phoneRegex.test(normalizePhoneNumber(num)))
})

const invalidNumbers = computed(() => {
  return getNumbersFromColumn().filter(num => !phoneRegex.test(normalizePhoneNumber(num)))
})

const duplicateNumbers = computed(() => {
  const numbers = getNumbersFromColumn()
  const seen = new Set()
  const duplicates = new Set()
  
  numbers.forEach(num => {
    const normalized = normalizePhoneNumber(num)
    if (seen.has(normalized)) {
      duplicates.add(num)
    } else {
      seen.add(normalized)
    }
  })
  
  return Array.from(duplicates)
})

const previewItems = computed((): PreviewItem[] => {
  const numbers = getNumbersFromColumn()
  const seen = new Set()
  
  return numbers.map(num => {
    const normalized = normalizePhoneNumber(num)
    let status: 'valid' | 'invalid' | 'duplicate' = 'valid'
    let error = ''
    
    if (!phoneRegex.test(normalized)) {
      status = 'invalid'
      error = 'Format invalide'
    } else if (seen.has(normalized)) {
      status = 'duplicate'
      error = 'Numéro en double'
    } else {
      seen.add(normalized)
    }
    
    return { number: num, status, error }
  })
})

const canImport = computed(() => {
  const validCount = validNumbers.value.length
  const hasValidNumbers = validCount > 0
  
  if (!allowDuplicates.value) {
    return hasValidNumbers && duplicateNumbers.value.length === 0
  }
  
  return hasValidNumbers
})

// Méthodes utilitaires
function normalizePhoneNumber(phone: string): string {
  let normalized = phone.replace(/[\s\-\(\)]/g, '')
  if (!normalized.startsWith('+')) {
    normalized = '+' + normalized
  }
  return normalized
}

function getNumbersFromColumn(): string[] {
  if (filePreview.value.length === 0) return []
  
  const separators = [',', ';', '\t', '|']
  let bestSeparator = ','
  let maxColumns = 1
  
  separators.forEach(sep => {
    const columns = filePreview.value[0].split(sep)
    if (columns.length > maxColumns) {
      maxColumns = columns.length
      bestSeparator = sep
    }
  })
  
  return filePreview.value
    .map(line => {
      const columns = line.split(bestSeparator)
      return columns[columnIndex.value] || ''
    })
    .map(num => num.trim())
    .filter(num => num.length > 0)
}

function getStatusIcon(status: string): string {
  switch (status) {
    case 'valid': return 'check_circle'
    case 'invalid': return 'error'
    case 'duplicate': return 'content_copy'
    default: return 'help'
  }
}

function getStatusColor(status: string): string {
  switch (status) {
    case 'valid': return 'positive'
    case 'invalid': return 'negative'
    case 'duplicate': return 'warning'
    default: return 'grey'
  }
}

// Gestion des fichiers
function onFileSelected(file: File | null) {
  if (!file) {
    clearFile()
    return
  }
  
  const reader = new FileReader()
  reader.onload = (e) => {
    fileContent.value = e.target?.result as string
    filePreview.value = fileContent.value
      .split('\n')
      .map(line => line.trim())
      .filter(line => line.length > 0)
      .slice(0, 1000) // Limiter l'aperçu à 1000 lignes
  }
  reader.readAsText(file)
}

function onFileRejected(rejectedEntries: any[]) {
  $q.notify({
    type: 'negative',
    message: `Fichier rejeté: ${rejectedEntries[0].failedPropValidation}`,
    position: 'top'
  })
}

function clearFile() {
  selectedFile.value = null
  fileContent.value = ''
  filePreview.value = []
  columnIndex.value = 0
}

function importNumbers() {
  if (!canImport.value) return
  
  let numbersToImport = validNumbers.value.map(num => normalizePhoneNumber(num))
  
  if (!allowDuplicates.value) {
    numbersToImport = [...new Set(numbersToImport)]
  }
  
  emit('recipients-imported', numbersToImport)
  clearFile()
}

// Gestion du glisser-déposer
function onDragOver(e: DragEvent) {
  e.preventDefault()
  isDragOver.value = true
}

function onDragEnter(e: DragEvent) {
  e.preventDefault()
  isDragOver.value = true
}

function onDragLeave(e: DragEvent) {
  e.preventDefault()
  isDragOver.value = false
}

function onDrop(e: DragEvent) {
  e.preventDefault()
  isDragOver.value = false
  
  const files = e.dataTransfer?.files
  if (files && files.length > 0) {
    const file = files[0]
    if (file.type === 'text/csv' || file.name.endsWith('.csv') || file.name.endsWith('.txt')) {
      selectedFile.value = file
      onFileSelected(file)
    } else {
      $q.notify({
        type: 'negative',
        message: 'Seuls les fichiers CSV et TXT sont acceptés',
        position: 'top'
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.csv-import {
  .upload-section {
    margin-bottom: 24px;
    
    .upload-header {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 12px;
      
      .upload-icon {
        color: var(--q-primary);
        font-size: 18px;
      }
      
      .upload-label {
        font-weight: 600;
        color: var(--q-dark);
      }
    }
    
    .file-input {
      margin-bottom: 8px;
    }
    
    .upload-help {
      display: flex;
      align-items: center;
      color: var(--q-grey-6);
      font-size: 12px;
    }
  }
  
  .preview-section {
    margin-bottom: 24px;
    border: 1px solid var(--q-grey-4);
    border-radius: 8px;
    padding: 16px;
    background: white;
    
    .preview-header {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 16px;
      
      .preview-icon {
        color: var(--q-primary);
        font-size: 18px;
      }
      
      .preview-label {
        font-weight: 600;
        color: var(--q-dark);
      }
    }
    
    .preview-content {
      .preview-stats {
        display: flex;
        gap: 24px;
        margin-bottom: 16px;
        flex-wrap: wrap;
        
        .stat-item {
          display: flex;
          align-items: center;
          gap: 6px;
          
          .stat-icon {
            font-size: 16px;
            
            &.valid {
              color: var(--q-positive);
            }
            
            &.invalid {
              color: var(--q-negative);
            }
            
            &.duplicate {
              color: var(--q-warning);
            }
          }
          
          .stat-value {
            font-weight: 600;
            color: var(--q-dark);
          }
          
          .stat-label {
            color: var(--q-grey-6);
            font-size: 14px;
          }
        }
      }
      
      .preview-list {
        border: 1px solid var(--q-grey-4);
        border-radius: 6px;
        
        .preview-item {
          &.valid {
            background: rgba(76, 175, 80, 0.05);
          }
          
          &.invalid {
            background: rgba(244, 67, 54, 0.05);
          }
          
          &.duplicate {
            background: rgba(255, 152, 0, 0.05);
          }
          
          .phone-number {
            font-family: 'Courier New', monospace;
            font-weight: 500;
          }
          
          .error-message {
            color: var(--q-negative);
            font-size: 12px;
          }
        }
      }
    }
  }
  
  .config-section {
    margin-bottom: 24px;
    border: 1px solid var(--q-grey-4);
    border-radius: 8px;
    padding: 16px;
    background: rgba(255, 255, 255, 0.5);
    
    .config-header {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 16px;
      
      .config-icon {
        color: var(--q-primary);
        font-size: 18px;
      }
      
      .config-label {
        font-weight: 600;
        color: var(--q-dark);
      }
    }
    
    .config-options {
      display: grid;
      gap: 16px;
      
      .column-select {
        max-width: 200px;
      }
      
      .skip-toggle,
      .duplicate-toggle {
        margin: 0;
      }
    }
  }
  
  .actions-section {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
    
    .import-btn {
      font-weight: 600;
    }
    
    .import-warning {
      display: flex;
      align-items: center;
      color: var(--q-warning);
      font-size: 12px;
      margin-left: auto;
    }
  }
  
  .drop-zone {
    border: 2px dashed var(--q-grey-4);
    border-radius: 12px;
    padding: 48px 24px;
    text-align: center;
    color: var(--q-grey-6);
    transition: all 0.3s ease;
    cursor: pointer;
    
    &.drag-over {
      border-color: var(--q-primary);
      background: rgba(var(--q-primary-rgb), 0.05);
      color: var(--q-primary);
    }
    
    .drop-icon {
      margin-bottom: 16px;
      opacity: 0.7;
    }
    
    .drop-message {
      font-size: 16px;
      font-weight: 500;
      margin-bottom: 8px;
    }
    
    .drop-hint {
      font-size: 14px;
      margin: 0;
    }
  }
}

// Responsive design
@media (max-width: 768px) {
  .csv-import {
    .preview-section .preview-content .preview-stats {
      flex-direction: column;
      gap: 12px;
    }
    
    .actions-section {
      flex-direction: column;
      align-items: stretch;
      
      .import-warning {
        margin-left: 0;
        justify-content: center;
      }
    }
  }
}
</style>