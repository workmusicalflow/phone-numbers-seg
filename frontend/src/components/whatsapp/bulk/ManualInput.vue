<template>
  <div class="manual-input">
    <div class="input-section">
      <div class="input-header">
        <q-icon name="edit" class="input-icon" />
        <span class="input-label">Saisie des numéros de téléphone</span>
      </div>
      
      <q-input
        v-model="phoneNumberInput"
        label="Ajouter un numéro de téléphone"
        placeholder="+225XXXXXXXX ou +33XXXXXXXXX"
        outlined
        dense
        :error="inputError !== null"
        :error-message="inputError"
        @keyup.enter="addPhoneNumber"
        class="phone-input"
      >
        <template v-slot:append>
          <q-btn
            flat
            dense
            icon="add"
            color="primary"
            @click="addPhoneNumber"
            :disable="!phoneNumberInput.trim()"
          />
        </template>
      </q-input>
      
      <div class="input-help">
        <q-icon name="info" size="14px" class="q-mr-xs" />
        <span class="help-text">
          Format international requis (ex: +225XXXXXXXX). Appuyez sur Entrée pour ajouter.
        </span>
      </div>
    </div>
    
    <!-- Saisie en lot -->
    <div class="batch-input-section">
      <div class="batch-header">
        <q-icon name="format_list_bulleted" class="batch-icon" />
        <span class="batch-label">Saisie en lot</span>
        <q-btn
          flat
          dense
          :icon="showBatchInput ? 'expand_less' : 'expand_more'"
          @click="showBatchInput = !showBatchInput"
          class="toggle-btn"
        />
      </div>
      
      <q-slide-transition>
        <div v-show="showBatchInput" class="batch-input-content">
          <q-input
            v-model="batchPhoneInput"
            type="textarea"
            label="Coller plusieurs numéros"
            placeholder="Un numéro par ligne:&#10;+225XXXXXXXX&#10;+33XXXXXXXXX&#10;+1XXXXXXXXXX"
            outlined
            rows="5"
            :error="batchInputError !== null"
            :error-message="batchInputError"
            class="batch-textarea"
          />
          
          <div class="batch-actions">
            <q-btn
              flat
              dense
              icon="add_circle"
              label="Ajouter tous les numéros"
              color="primary"
              @click="addBatchPhoneNumbers"
              :disable="!batchPhoneInput.trim()"
            />
            <q-btn
              flat
              dense
              icon="clear"
              label="Effacer"
              @click="clearBatchInput"
            />
          </div>
          
          <div class="batch-help">
            <q-icon name="lightbulb" size="14px" class="q-mr-xs" />
            <span class="help-text">
              Vous pouvez coller une liste de numéros séparés par des retours à la ligne, 
              des virgules ou des points-virgules.
            </span>
          </div>
        </div>
      </q-slide-transition>
    </div>
    
    <!-- Liste des numéros ajoutés -->
    <div v-if="localRecipients.length > 0" class="recipients-list">
      <div class="list-header">
        <q-icon name="list" class="list-icon" />
        <span class="list-label">Numéros ajoutés ({{ localRecipients.length }})</span>
        <q-space />
        <q-btn
          flat
          dense
          icon="select_all"
          label="Tout sélectionner"
          @click="selectAll"
          v-if="!allSelected"
          class="select-btn"
        />
        <q-btn
          flat
          dense
          icon="deselect"
          label="Tout désélectionner"
          @click="selectNone"
          v-else
          class="select-btn"
        />
        <q-btn
          flat
          dense
          icon="delete"
          color="negative"
          @click="deleteSelected"
          :disable="selectedNumbers.length === 0"
          class="delete-btn"
        />
      </div>
      
      <div class="recipients-container">
        <q-virtual-scroll
          :items="localRecipients"
          separator
          v-slot="{ item, index }"
          class="recipients-scroll"
          style="max-height: 300px;"
        >
          <q-item 
            :key="index"
            clickable
            @click="toggleSelection(item)"
            class="recipient-item"
            :class="{ 
              'selected': selectedNumbers.includes(item),
              'invalid': !isValidPhoneNumber(item)
            }"
          >
            <q-item-section avatar>
              <q-checkbox
                :model-value="selectedNumbers.includes(item)"
                @update:model-value="toggleSelection(item)"
                color="primary"
              />
            </q-item-section>
            
            <q-item-section>
              <q-item-label class="phone-number">{{ item }}</q-item-label>
              <q-item-label 
                v-if="!isValidPhoneNumber(item)" 
                caption 
                class="error-caption"
              >
                <q-icon name="error" size="12px" class="q-mr-xs" />
                Format invalide
              </q-item-label>
            </q-item-section>
            
            <q-item-section side>
              <q-btn
                flat
                dense
                round
                icon="delete"
                color="negative"
                size="sm"
                @click.stop="removePhoneNumber(item)"
              />
            </q-item-section>
          </q-item>
        </q-virtual-scroll>
      </div>
    </div>
    
    <!-- État vide -->
    <div v-else class="empty-state">
      <q-icon name="phone_disabled" size="48px" class="empty-icon" />
      <p class="empty-message">Aucun numéro de téléphone ajouté</p>
      <p class="empty-hint">Commencez par ajouter des numéros ci-dessus</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useQuasar } from 'quasar'

interface Props {
  recipients?: string[]
}

interface Emits {
  (e: 'update:recipients', value: string[]): void
}

const props = withDefaults(defineProps<Props>(), {
  recipients: () => []
})

const emit = defineEmits<Emits>()
const $q = useQuasar()

const phoneNumberInput = ref('')
const batchPhoneInput = ref('')
const showBatchInput = ref(false)
const inputError = ref<string | null>(null)
const batchInputError = ref<string | null>(null)
const selectedNumbers = ref<string[]>([])
const localRecipients = ref([...props.recipients])

// Validation du numéro de téléphone
const phoneRegex = /^\+[1-9]\d{1,14}$/

// Computed properties
const allSelected = computed(() => {
  return localRecipients.value.length > 0 && 
         selectedNumbers.value.length === localRecipients.value.length
})

// Watchers
watch(() => props.recipients, (newRecipients) => {
  localRecipients.value = [...newRecipients]
}, { deep: true })

watch(localRecipients, (newRecipients) => {
  emit('update:recipients', [...newRecipients])
}, { deep: true })

// Méthodes de validation
function isValidPhoneNumber(phone: string): boolean {
  return phoneRegex.test(phone.trim())
}

function normalizePhoneNumber(phone: string): string {
  // Retirer tous les espaces, tirets, parenthèses
  let normalized = phone.replace(/[\s\-\(\)]/g, '')
  
  // S'assurer que le numéro commence par +
  if (!normalized.startsWith('+')) {
    normalized = '+' + normalized
  }
  
  return normalized
}

// Méthodes de gestion des numéros
function addPhoneNumber() {
  if (!phoneNumberInput.value.trim()) {
    return
  }
  
  const normalizedNumber = normalizePhoneNumber(phoneNumberInput.value)
  
  if (!isValidPhoneNumber(normalizedNumber)) {
    inputError.value = 'Format de numéro invalide. Utilisez le format international (+XXX...)'
    return
  }
  
  if (localRecipients.value.includes(normalizedNumber)) {
    inputError.value = 'Ce numéro est déjà dans la liste'
    return
  }
  
  localRecipients.value.push(normalizedNumber)
  phoneNumberInput.value = ''
  inputError.value = null
  
  $q.notify({
    type: 'positive',
    message: 'Numéro ajouté avec succès',
    position: 'top'
  })
}

function addBatchPhoneNumbers() {
  if (!batchPhoneInput.value.trim()) {
    return
  }
  
  // Séparer par lignes, virgules ou points-virgules
  const numbers = batchPhoneInput.value
    .split(/[\n,;]/)
    .map(num => num.trim())
    .filter(num => num.length > 0)
  
  const validNumbers: string[] = []
  const invalidNumbers: string[] = []
  const duplicateNumbers: string[] = []
  
  numbers.forEach(number => {
    const normalizedNumber = normalizePhoneNumber(number)
    
    if (!isValidPhoneNumber(normalizedNumber)) {
      invalidNumbers.push(number)
    } else if (localRecipients.value.includes(normalizedNumber)) {
      duplicateNumbers.push(number)
    } else {
      validNumbers.push(normalizedNumber)
    }
  })
  
  if (validNumbers.length > 0) {
    localRecipients.value.push(...validNumbers)
    batchPhoneInput.value = ''
    batchInputError.value = null
    
    $q.notify({
      type: 'positive',
      message: `${validNumbers.length} numéro(s) ajouté(s) avec succès`,
      position: 'top'
    })
  }
  
  if (invalidNumbers.length > 0 || duplicateNumbers.length > 0) {
    let errorMessage = ''
    if (invalidNumbers.length > 0) {
      errorMessage += `${invalidNumbers.length} numéro(s) invalide(s). `
    }
    if (duplicateNumbers.length > 0) {
      errorMessage += `${duplicateNumbers.length} numéro(s) déjà présent(s).`
    }
    
    batchInputError.value = errorMessage
    
    $q.notify({
      type: 'warning',
      message: errorMessage,
      position: 'top'
    })
  }
}

function removePhoneNumber(phoneNumber: string) {
  const index = localRecipients.value.indexOf(phoneNumber)
  if (index > -1) {
    localRecipients.value.splice(index, 1)
    // Retirer de la sélection si nécessaire
    const selectedIndex = selectedNumbers.value.indexOf(phoneNumber)
    if (selectedIndex > -1) {
      selectedNumbers.value.splice(selectedIndex, 1)
    }
  }
}

function clearBatchInput() {
  batchPhoneInput.value = ''
  batchInputError.value = null
}

// Méthodes de sélection
function toggleSelection(phoneNumber: string) {
  const index = selectedNumbers.value.indexOf(phoneNumber)
  if (index > -1) {
    selectedNumbers.value.splice(index, 1)
  } else {
    selectedNumbers.value.push(phoneNumber)
  }
}

function selectAll() {
  selectedNumbers.value = [...localRecipients.value]
}

function selectNone() {
  selectedNumbers.value = []
}

function deleteSelected() {
  if (selectedNumbers.value.length === 0) return
  
  $q.dialog({
    title: 'Confirmer la suppression',
    message: `Supprimer ${selectedNumbers.value.length} numéro(s) sélectionné(s) ?`,
    cancel: true,
    persistent: true
  }).onOk(() => {
    selectedNumbers.value.forEach(number => {
      const index = localRecipients.value.indexOf(number)
      if (index > -1) {
        localRecipients.value.splice(index, 1)
      }
    })
    selectedNumbers.value = []
    
    $q.notify({
      type: 'info',
      message: 'Numéros supprimés',
      position: 'top'
    })
  })
}

// Nettoyer les erreurs quand l'utilisateur modifie l'input
watch(phoneNumberInput, () => {
  if (inputError.value) {
    inputError.value = null
  }
})

watch(batchPhoneInput, () => {
  if (batchInputError.value) {
    batchInputError.value = null
  }
})
</script>

<style lang="scss" scoped>
.manual-input {
  .input-section {
    margin-bottom: 24px;
    
    .input-header {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 12px;
      
      .input-icon {
        color: var(--q-primary);
        font-size: 18px;
      }
      
      .input-label {
        font-weight: 600;
        color: var(--q-dark);
      }
    }
    
    .phone-input {
      margin-bottom: 8px;
    }
    
    .input-help {
      display: flex;
      align-items: center;
      color: var(--q-grey-6);
      font-size: 12px;
    }
  }
  
  .batch-input-section {
    margin-bottom: 24px;
    border: 1px solid var(--q-grey-4);
    border-radius: 8px;
    padding: 16px;
    background: rgba(255, 255, 255, 0.5);
    
    .batch-header {
      display: flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      
      .batch-icon {
        color: var(--q-primary);
        font-size: 18px;
      }
      
      .batch-label {
        font-weight: 600;
        color: var(--q-dark);
        flex: 1;
      }
      
      .toggle-btn {
        color: var(--q-grey-6);
      }
    }
    
    .batch-input-content {
      margin-top: 16px;
      
      .batch-textarea {
        margin-bottom: 12px;
      }
      
      .batch-actions {
        display: flex;
        gap: 8px;
        margin-bottom: 8px;
        flex-wrap: wrap;
      }
      
      .batch-help {
        display: flex;
        align-items: flex-start;
        gap: 4px;
        color: var(--q-grey-6);
        font-size: 12px;
        line-height: 1.4;
      }
    }
  }
  
  .recipients-list {
    .list-header {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 12px 16px;
      background: rgba(var(--q-primary-rgb), 0.1);
      border-radius: 8px 8px 0 0;
      border-bottom: 1px solid var(--q-grey-4);
      
      .list-icon {
        color: var(--q-primary);
        font-size: 18px;
      }
      
      .list-label {
        font-weight: 600;
        color: var(--q-dark);
      }
      
      .select-btn,
      .delete-btn {
        font-size: 12px;
      }
    }
    
    .recipients-container {
      border: 1px solid var(--q-grey-4);
      border-top: none;
      border-radius: 0 0 8px 8px;
      
      .recipients-scroll {
        background: white;
      }
      
      .recipient-item {
        transition: all 0.2s ease;
        
        &.selected {
          background: rgba(var(--q-primary-rgb), 0.1);
        }
        
        &.invalid {
          background: rgba(244, 67, 54, 0.05);
          
          .phone-number {
            color: var(--q-negative);
          }
        }
        
        &:hover {
          background: rgba(var(--q-grey-rgb), 0.05);
        }
        
        .phone-number {
          font-family: 'Courier New', monospace;
          font-weight: 500;
        }
        
        .error-caption {
          color: var(--q-negative);
        }
      }
    }
  }
  
  .empty-state {
    text-align: center;
    padding: 48px 16px;
    color: var(--q-grey-6);
    
    .empty-icon {
      margin-bottom: 16px;
      opacity: 0.5;
    }
    
    .empty-message {
      font-size: 16px;
      font-weight: 500;
      margin-bottom: 8px;
    }
    
    .empty-hint {
      font-size: 14px;
      margin: 0;
    }
  }
}

// Responsive design
@media (max-width: 768px) {
  .manual-input {
    .batch-input-section .batch-input-content .batch-actions {
      flex-direction: column;
    }
    
    .recipients-list .list-header {
      flex-wrap: wrap;
      gap: 12px;
    }
  }
}
</style>