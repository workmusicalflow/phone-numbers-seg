<template>
  <div class="all-contacts-selector">
    <div class="selector-header">
      <q-icon name="contacts" class="selector-icon" />
      <span class="selector-label">Envoyer à tous les contacts</span>
    </div>
    
    <!-- Card principale -->
    <q-card class="selector-card">
      <q-card-section class="card-content">
        <div class="content-layout">
          <div class="icon-section">
            <div class="icon-wrapper">
              <q-icon name="groups" size="48px" color="primary" />
            </div>
          </div>
          
          <div class="info-section">
            <h6 class="info-title">Tous vos contacts WhatsApp</h6>
            <p class="info-description">
              Envoyez votre message à l'ensemble de vos contacts enregistrés.
              Cette option sélectionnera automatiquement tous les numéros valides.
            </p>
            
            <div class="stats-row">
              <div class="stat-item">
                <q-icon name="person" size="16px" />
                <span>{{ totalContacts }} contacts au total</span>
              </div>
              <div class="stat-item" v-if="validContacts > 0">
                <q-icon name="check_circle" size="16px" color="positive" />
                <span>{{ validContacts }} numéros valides</span>
              </div>
            </div>
          </div>
        </div>
      </q-card-section>
      
      <q-separator />
      
      <q-card-actions align="center" class="card-actions">
        <q-btn
          v-if="!isSelected"
          unelevated
          color="primary"
          icon="add_circle"
          label="Sélectionner tous les contacts"
          @click="selectAllContacts"
          :loading="loading"
          :disable="loading || totalContacts === 0"
          class="select-btn"
        />
        
        <div v-else class="selected-state">
          <q-chip
            color="positive"
            text-color="white"
            icon="check"
            :label="`${validContacts} contacts sélectionnés`"
            class="selection-chip"
          />
          <q-btn
            flat
            color="negative"
            icon="remove_circle"
            label="Désélectionner"
            @click="deselectAllContacts"
            class="deselect-btn"
          />
        </div>
      </q-card-actions>
    </q-card>
    
    <!-- Avertissement si beaucoup de contacts -->
    <q-banner 
      v-if="validContacts > 100 && isSelected" 
      class="warning-banner"
      icon="warning"
      dense
    >
      <template v-slot:avatar>
        <q-icon name="info" color="warning" />
      </template>
      <strong>Envoi en masse important</strong><br>
      Vous êtes sur le point d'envoyer {{ validContacts }} messages. 
      L'envoi sera effectué par lots pour respecter les limites de l'API WhatsApp.
    </q-banner>
    
    <!-- État de chargement -->
    <div v-if="loading" class="loading-overlay">
      <q-spinner-dots size="40px" color="primary" />
      <p class="loading-text">Chargement des contacts...</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useContactStore } from '../../../stores/contactStore'

interface Props {
  isSelected?: boolean
}

interface Emits {
  (e: 'update:isSelected', value: boolean): void
  (e: 'recipients-loaded', value: string[]): void
}

const props = withDefaults(defineProps<Props>(), {
  isSelected: false
})

const emit = defineEmits<Emits>()
const $q = useQuasar()
const contactStore = useContactStore()

const loading = ref(false)
const totalContacts = ref(0)
const validContacts = ref(0)
const allContactNumbers = ref<string[]>([])

// Regex pour valider les numéros WhatsApp
const phoneRegex = /^\+[1-9]\d{1,14}$/

// Computed
const isSelected = computed({
  get: () => props.isSelected,
  set: (value) => emit('update:isSelected', value)
})

// Méthodes
async function loadContactsCount() {
  loading.value = true
  try {
    // Charger tous les contacts
    await contactStore.fetchContacts({ limit: 9999 })
    
    // Extraire et valider les numéros
    const contacts = contactStore.contacts
    allContactNumbers.value = contacts
      .map(contact => contact.phoneNumber)
      .filter(phone => phone && phoneRegex.test(phone))
    
    totalContacts.value = contacts.length
    validContacts.value = allContactNumbers.value.length
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erreur lors du chargement des contacts',
      position: 'top'
    })
  } finally {
    loading.value = false
  }
}

async function selectAllContacts() {
  if (allContactNumbers.value.length === 0) {
    await loadContactsCount()
  }
  
  if (allContactNumbers.value.length > 0) {
    emit('recipients-loaded', [...allContactNumbers.value])
    isSelected.value = true
    
    $q.notify({
      type: 'positive',
      message: `${validContacts.value} contacts ajoutés à la liste des destinataires`,
      position: 'top'
    })
  } else {
    $q.notify({
      type: 'warning',
      message: 'Aucun contact valide trouvé',
      position: 'top'
    })
  }
}

function deselectAllContacts() {
  isSelected.value = false
  emit('recipients-loaded', [])
  
  $q.notify({
    type: 'info',
    message: 'Tous les contacts ont été retirés de la sélection',
    position: 'top'
  })
}

// Lifecycle
onMounted(() => {
  loadContactsCount()
})
</script>

<style lang="scss" scoped>
.all-contacts-selector {
  position: relative;
  
  .selector-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
    
    .selector-icon {
      color: var(--q-primary);
      font-size: 18px;
    }
    
    .selector-label {
      font-weight: 600;
      color: var(--q-dark);
    }
  }
  
  .selector-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    
    &:hover {
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
    }
    
    .card-content {
      padding: 24px;
      
      .content-layout {
        display: flex;
        gap: 20px;
        align-items: flex-start;
        
        .icon-section {
          .icon-wrapper {
            width: 80px;
            height: 80px;
            background: rgba(var(--q-primary-rgb), 0.1);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
          }
        }
        
        .info-section {
          flex: 1;
          
          .info-title {
            margin: 0 0 8px 0;
            font-size: 18px;
            font-weight: 600;
            color: var(--q-dark);
          }
          
          .info-description {
            margin: 0 0 16px 0;
            color: var(--q-grey-7);
            line-height: 1.5;
          }
          
          .stats-row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            
            .stat-item {
              display: flex;
              align-items: center;
              gap: 6px;
              color: var(--q-grey-8);
              font-size: 14px;
              
              q-icon {
                opacity: 0.7;
              }
            }
          }
        }
      }
    }
    
    .card-actions {
      padding: 16px 24px;
      background: rgba(var(--q-grey-rgb), 0.05);
      
      .select-btn {
        min-width: 200px;
        font-weight: 500;
      }
      
      .selected-state {
        display: flex;
        align-items: center;
        gap: 16px;
        width: 100%;
        justify-content: center;
        
        .selection-chip {
          font-size: 14px;
          padding: 8px 16px;
        }
        
        .deselect-btn {
          font-size: 14px;
        }
      }
    }
  }
  
  .warning-banner {
    margin-top: 16px;
    border-radius: 8px;
    background: rgba(255, 193, 7, 0.1);
    border: 1px solid rgba(255, 193, 7, 0.3);
  }
  
  .loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    z-index: 10;
    
    .loading-text {
      margin-top: 16px;
      color: var(--q-grey-7);
      font-size: 14px;
    }
  }
}

// Responsive
@media (max-width: 768px) {
  .all-contacts-selector {
    .selector-card .card-content .content-layout {
      flex-direction: column;
      align-items: center;
      text-align: center;
      
      .info-section {
        .stats-row {
          justify-content: center;
        }
      }
    }
    
    .selected-state {
      flex-direction: column;
      gap: 12px !important;
    }
  }
}
</style>