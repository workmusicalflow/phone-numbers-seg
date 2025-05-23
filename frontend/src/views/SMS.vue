<template>
  <q-page padding>
    <div class="sms-page">
      <div class="page-header">
        <div class="header-content">
          <div class="header-title-section">
            <div class="title-icon-wrapper">
              <q-icon name="sms" size="md" />
            </div>
            <div class="title-text">
              <h1 class="page-title">Envoi de SMS</h1>
              <p class="page-subtitle">Gestion et envoi de messages SMS</p>
            </div>
          </div>
          
          <div v-if="userStore.currentUser" class="header-badges">
            <!-- Badge crédits SMS modernisé -->
            <div class="credit-badge-wrapper">
              <q-chip
                :class="['modern-credit-badge', getCreditBadgeClass(userStore.currentUser.smsCredit)]"
                text-color="white"
                icon="account_balance_wallet"
              >
                <span class="credit-amount">{{ userStore.currentUser.smsCredit }}</span>
                <span class="credit-label">crédit{{ userStore.currentUser.smsCredit !== 1 ? 's' : '' }}</span>
              </q-chip>
              <q-tooltip class="modern-tooltip">
                <div v-if="userStore.currentUser.smsCredit <= 0">
                  <strong>Crédits épuisés</strong><br>
                  Contactez l'administrateur pour recharger
                </div>
                <div v-else-if="userStore.currentUser.smsCredit < 5">
                  <strong>Crédit faible</strong><br>
                  Il vous reste {{ userStore.currentUser.smsCredit }} crédit{{ userStore.currentUser.smsCredit !== 1 ? 's' : '' }}
                </div>
                <div v-else>
                  <strong>Crédits disponibles</strong><br>
                  {{ userStore.currentUser.smsCredit }} crédit{{ userStore.currentUser.smsCredit !== 1 ? 's' : '' }} SMS
                </div>
              </q-tooltip>
            </div>
            
            <!-- Badge nombre de contacts modernisé -->
            <ContactCountBadge
              :count="contactsCount"
              color="blue"
              icon="contacts"
              tooltipText="Nombre total de contacts disponibles pour l'envoi de SMS."
            />
          </div>
        </div>
      </div>

      <q-tabs
        v-model="activeTab"
        class="modern-tabs"
        active-color="blue"
        indicator-color="blue"
        align="left"
        no-caps
      >
        <q-tab name="single" class="tab-item">
          <div class="tab-content">
            <q-icon name="person" size="sm" class="q-mr-sm" />
            <div>
              <div class="tab-label">Envoi Individuel</div>
              <div class="tab-caption">Un destinataire</div>
            </div>
          </div>
        </q-tab>
        <q-tab name="bulk" class="tab-item">
          <div class="tab-content">
            <q-icon name="people" size="sm" class="q-mr-sm" />
            <div>
              <div class="tab-label">Envoi en Masse</div>
              <div class="tab-caption">Plusieurs numéros</div>
            </div>
          </div>
        </q-tab>
        <q-tab name="segment" class="tab-item">
          <div class="tab-content">
            <q-icon name="segment" size="sm" class="q-mr-sm" />
            <div>
              <div class="tab-label">Par Segment</div>
              <div class="tab-caption">Groupes ciblés</div>
            </div>
          </div>
        </q-tab>
        <q-tab name="allContacts" class="tab-item">
          <div class="tab-content">
            <q-icon name="groups" size="sm" class="q-mr-sm" />
            <div>
              <div class="tab-label">Tous les Contacts</div>
              <div class="tab-caption">Base complète</div>
            </div>
          </div>
        </q-tab>
      </q-tabs>

      <q-tab-panels v-model="activeTab" animated class="modern-panels">
        <!-- Onglet Envoi Individuel -->
        <q-tab-panel name="single" class="panel-content">
          <SingleSmsForm
            ref="singleSmsFormRef"
            :loading="loading"
            :has-insufficient-credits="hasInsufficientCredits"
            :initial-phone-number="recipientPhoneNumber"
            :initial-contact-name="recipientName"
            @submit-single="handleSingleSubmit"
          />
        </q-tab-panel>

        <!-- Onglet Envoi en Masse -->
        <q-tab-panel name="bulk" class="panel-content">
          <BulkSmsForm
            ref="bulkSmsFormRef"
            :loading="loading"
            :has-insufficient-credits="hasInsufficientCredits"
            @submit-bulk="handleBulkSubmit"
          />
        </q-tab-panel>

        <!-- Onglet Envoi par Segment -->
        <q-tab-panel name="segment" class="panel-content">
          <SegmentSmsForm
            ref="segmentSmsFormRef"
            :loading="loading"
            :loading-segments="loadingSegments"
            :has-insufficient-credits="hasInsufficientCredits"
            :segments="segments"
            @submit-segment="handleSegmentSubmit"
          />
        </q-tab-panel>

        <!-- Onglet Envoi à Tous les Contacts -->
        <q-tab-panel name="allContacts" class="panel-content">
          <AllContactsSmsForm
            ref="allContactsSmsFormRef"
            :loading="loading"
            :has-insufficient-credits="hasInsufficientCredits"
            @submit-all-contacts="handleAllContactsSubmit"
          />
        </q-tab-panel>
      </q-tab-panels>

      <!-- Résultats et historique -->
      <div class="results-section">
        <!-- Résultat de l'envoi -->
        <div v-if="smsResult" class="result-card-wrapper">
          <div class="modern-card">
            <div class="card-header sms-gradient">
              <div class="header-content">
                <q-icon name="assessment" size="md" class="header-icon" />
                <div class="header-text">
                  <h3 class="header-title">Résultat de l'envoi</h3>
                  <p class="header-subtitle">Statut de la dernière opération</p>
                </div>
              </div>
            </div>

            <div class="card-content">
              <!-- Status Display -->
              <div class="status-display">
                <div v-if="smsResult.status === 'success'" class="status-item success">
                  <div class="status-icon">
                    <q-icon name="check_circle" size="lg" />
                  </div>
                  <div class="status-content">
                    <h4 class="status-title">Envoi réussi</h4>
                    <p class="status-message">{{ smsResult.message || 'Opération réussie.' }}</p>
                  </div>
                </div>
                
                <div v-else-if="smsResult.status === 'warning'" class="status-item warning">
                  <div class="status-icon">
                    <q-icon name="warning" size="lg" />
                  </div>
                  <div class="status-content">
                    <h4 class="status-title">Envoi partiel</h4>
                    <p class="status-message">{{ smsResult.message || 'Opération terminée avec des avertissements.' }}</p>
                  </div>
                </div>
                
                <div v-else class="status-item error">
                  <div class="status-icon">
                    <q-icon name="error" size="lg" />
                  </div>
                  <div class="status-content">
                    <h4 class="status-title">Envoi échoué</h4>
                    <p class="status-message">{{ smsResult.message || 'Échec de l\'opération.' }}</p>
                  </div>
                </div>
              </div>

              <!-- Summary Cards -->
              <div v-if="'summary' in smsResult && smsResult.summary" class="summary-cards">
                <div class="summary-card total">
                  <div class="summary-icon">
                    <q-icon name="sms" size="md" />
                  </div>
                  <div class="summary-content">
                    <div class="summary-value">{{ smsResult.summary.total }}</div>
                    <div class="summary-label">Total</div>
                  </div>
                </div>
                
                <div class="summary-card success">
                  <div class="summary-icon">
                    <q-icon name="check_circle" size="md" />
                  </div>
                  <div class="summary-content">
                    <div class="summary-value">{{ smsResult.summary.successful }}</div>
                    <div class="summary-label">Réussis</div>
                  </div>
                </div>
                
                <div class="summary-card error">
                  <div class="summary-icon">
                    <q-icon name="cancel" size="md" />
                  </div>
                  <div class="summary-content">
                    <div class="summary-value">{{ smsResult.summary.failed }}</div>
                    <div class="summary-label">Échoués</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Historique des SMS -->
        <div class="history-card-wrapper" :class="{ 'full-width': !smsResult }">
          <div class="modern-card">
            <div class="card-header sms-gradient">
              <div class="header-content">
                <q-icon name="history" size="md" class="header-icon" />
                <div class="header-text">
                  <h3 class="header-title">Historique des SMS</h3>
                  <p class="header-subtitle">Derniers messages envoyés</p>
                </div>
              </div>
              <div class="header-actions">
                <q-btn
                  color="white"
                  text-color="primary"
                  icon="open_in_new"
                  label="Voir tout"
                  outline
                  size="sm"
                  :to="{ name: 'sms-history' }"
                  class="modern-btn"
                />
              </div>
            </div>

            <div class="card-content">
              <div class="history-table-wrapper">
                <q-table
                  :rows="smsHistory"
                  :columns="columns"
                  row-key="id"
                  :loading="loadingHistory"
                  :pagination="{ rowsPerPage: 5 }"
                  flat
                  class="modern-table"
                >
                  <template v-slot:body-cell-status="props">
                    <q-td :props="props">
                      <q-chip
                        :class="['status-chip', getStatusChipClass(props.row.status)]"
                        text-color="white"
                        size="sm"
                      >
                        {{ getStatusLabel(props.row.status) }}
                      </q-chip>
                    </q-td>
                  </template>

                  <template v-slot:loading>
                    <q-inner-loading showing color="primary" />
                  </template>

                  <template v-slot:no-data>
                    <div class="no-data-display">
                      <q-icon name="sms" size="lg" color="grey-5" />
                      <p class="no-data-text">Aucun SMS envoyé récemment</p>
                    </div>
                  </template>
                </q-table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from "vue";
import { useRouter, useRoute } from "vue-router";
// useQuasar is no longer needed here
// import { useQuasar } from "quasar";
// Template store is now used only within SingleSmsForm
// import { useSMSTemplateStore } from "../stores/smsTemplateStore";
import { useUserStore } from "../stores/userStore";
import { useContactStore } from "../stores/contactStore"; // Import contactStore
import { useSmsSender } from "../composables/useSmsSender"; // Import the composable
// QForm type is no longer needed here
// import type { QForm } from 'quasar';
// Corrected import paths (ensuring no extra quotes)
import SingleSmsForm from '@/components/sms/forms/SingleSmsForm.vue';
import BulkSmsForm from '@/components/sms/forms/BulkSmsForm.vue';
import SegmentSmsForm from '@/components/sms/forms/SegmentSmsForm.vue';
import AllContactsSmsForm from '@/components/sms/forms/AllContactsSmsForm.vue';
import ContactCountBadge from '@/components/common/ContactCountBadge.vue';

// $q is no longer needed here
// const $q = useQuasar();
// smsTemplateStore is removed
// Utiliser le store utilisateur pour accéder à l'utilisateur courant
const userStore = useUserStore();
const contactStore = useContactStore(); // Use contactStore
const contactsCount = ref(0); // Add contactsCount variable

// Router and route for URL parameters
const router = useRouter();
const route = useRoute();

// Extract URL parameters for pre-populating the form
const recipientPhoneNumber = ref<string>('');
const recipientName = ref<string>('');

// --- Use the Composable ---
const {
  loading,
  loadingHistory,
  loadingSegments,
  smsHistory,
  segments,
  smsResult,
  hasInsufficientCredits, // Use the computed property from the composable
  fetchSmsHistory,
  fetchSegments,
  sendSingleSms,
  sendBulkSms,
  sendSegmentSms,
  sendSmsToAllContacts,
} = useSmsSender();

// Template logic (variables, functions) moved to SingleSmsForm

// État de l'interface (Only UI state remains)
const activeTab = ref("single");

// Refs for child components
const singleSmsFormRef = ref<InstanceType<typeof SingleSmsForm> | null>(null);
const bulkSmsFormRef = ref<InstanceType<typeof BulkSmsForm> | null>(null);
const segmentSmsFormRef = ref<InstanceType<typeof SegmentSmsForm> | null>(null);
const allContactsSmsFormRef = ref<InstanceType<typeof AllContactsSmsForm> | null>(null); // Add ref for all contacts form

// Données des formulaires (Remove singleSmsData, bulkSmsData, segmentSmsData, allContactsSmsData)
// const singleSmsData = ref({...});
// const bulkSmsData = ref({...});
// const segmentSmsData = ref({...});
// const allContactsSmsData = ref({...}); // Removed as it's in the child
// const allContactsFormRef = ref<QForm | null>(null); // Ref name changed to allContactsSmsFormRef

// Configuration de la table d'historique (Keep column definitions)
const columns = [
  {
    name: "phoneNumber",
    label: "Numéro",
    field: "phoneNumber",
    sortable: true,
  },
  { name: "message", label: "Message", field: "message" },
  { name: "status", label: "Statut", field: "status", sortable: true },
  {
    name: "createdAt",
    label: "Date",
    field: "createdAt",
    sortable: true,
    format: (val: string) => new Date(val).toLocaleString(),
  },
];

// --- Submit Handlers (Wrap composable functions) ---

// Submit handlers using standardized FrontendStatus
// Fonction pour rafraîchir le nombre de contacts
const refreshContactsCount = async () => {
  contactsCount.value = await contactStore.fetchContactsCount();
};

const handleSingleSubmit = async (payload: { phoneNumber: string; message: string }) => {
  const result = await sendSingleSms(payload);
  if (result && result.status === 'success') {
    // Reset the child form upon successful submission
    singleSmsFormRef.value?.reset();
    // Rafraîchir le nombre de contacts (au cas où un nouveau contact a été créé)
    refreshContactsCount();
  }
  // Notification and error handling are done within the composable
};

const handleBulkSubmit = async (payload: { phoneNumbers: string[]; message: string }) => {
  // Phone number processing is now done in the child component
  const result = await sendBulkSms(payload);

  if (result && (result.status === 'success' || result.status === 'warning')) {
    // Reset the child form upon successful or partial submission
    bulkSmsFormRef.value?.reset();
    // Rafraîchir le nombre de contacts
    refreshContactsCount();
  }
};

const handleSegmentSubmit = async (payload: { segmentId: number; message: string }) => {
  // Segment ID check is now done in the child component
  const result = await sendSegmentSms(payload);

  if (result && (result.status === 'success' || result.status === 'warning')) {
    // Reset the child form upon successful or partial submission
    segmentSmsFormRef.value?.reset();
    // Rafraîchir le nombre de contacts
    refreshContactsCount();
  }
};

const handleAllContactsSubmit = async (payload: { message: string }) => {
  const result = await sendSmsToAllContacts(payload);

  if (result && (result.status === 'success' || result.status === 'warning')) {
    // Reset the child form upon successful or partial submission
    allContactsSmsFormRef.value?.reset();
    // Rafraîchir le nombre de contacts
    refreshContactsCount();
  }
};

// Fonction pour déterminer la couleur du badge de crédit (Keep UI helper)
const getCreditColor = (credit: number | undefined) => {
  if (credit === undefined || credit <= 0) return 'negative';
  // Corrected syntax error
  if (credit < 5) return 'warning'; 
  return 'positive';
};

// Helper function for credit badge class
const getCreditBadgeClass = (credit: number | undefined) => {
  if (credit === undefined || credit <= 0) return 'credit-empty';
  if (credit < 5) return 'credit-low';
  return 'credit-good';
};

// Helper functions for status display
const getStatusChipClass = (status: string) => {
  switch (status.toUpperCase()) {
    case 'SENT':
      return 'status-success';
    case 'FAILED':
      return 'status-error';
    default:
      return 'status-warning';
  }
};

const getStatusLabel = (status: string) => {
  switch (status.toUpperCase()) {
    case 'SENT':
      return 'Envoyé';
    case 'FAILED':
      return 'Échoué';
    default:
      return 'En attente';
  }
};

// Process URL parameters
function processRouteParams() {
  if (route.query.recipient) {
    recipientPhoneNumber.value = route.query.recipient as string;
  }
  
  if (route.query.name) {
    recipientName.value = route.query.name as string;
    
    // If we have a name parameter, automatically switch to the single SMS tab
    // This ensures the form is visible when coming from contacts
    activeTab.value = 'single';
  }
}

// Watch for route changes to update form with URL parameters
watch(() => route.query, () => {
  processRouteParams();
}, { deep: true });

// Initialisation (Use composable functions)
onMounted(async () => {
  fetchSmsHistory();
  fetchSegments();
  // Récupérer le nombre de contacts
  contactsCount.value = await contactStore.fetchContactsCount();
  // Process URL parameters on mount
  processRouteParams();
  // smsTemplateStore.fetchTemplates(); // Template fetching might be needed in the child component now, or handled globally
  // User fetching should be handled globally or by userStore itself
});
</script>

<style lang="scss" scoped>
// SMS Color Palette
$sms-primary: #0d47a1;
$sms-secondary: #1976d2;
$sms-accent: #42a5f5;
$sms-light: #e3f2fd;

// Design System Integration
.sms-page {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0;
}

// Modern Page Header
.page-header {
  background: linear-gradient(135deg, $sms-primary 0%, $sms-secondary 100%);
  border-radius: 16px;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 8px 32px rgba(13, 71, 161, 0.2);
  
  .header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1.5rem;
    
    .header-title-section {
      display: flex;
      align-items: center;
      gap: 1.5rem;
      
      .title-icon-wrapper {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 16px;
        padding: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        
        .q-icon {
          color: white;
        }
      }
      
      .title-text {
        color: white;
        
        .page-title {
          font-size: 2rem;
          font-weight: 700;
          margin: 0 0 0.5rem 0;
          line-height: 1.2;
        }
        
        .page-subtitle {
          font-size: 1.1rem;
          margin: 0;
          opacity: 0.9;
          font-weight: 400;
        }
      }
    }
    
    .header-badges {
      display: flex;
      gap: 1rem;
      align-items: center;
    }
  }
}

// Modern Credit Badge
.credit-badge-wrapper {
  position: relative;
  
  .modern-credit-badge {
    font-size: 1rem;
    font-weight: 600;
    padding: 0.5rem 1rem;
    border-radius: 12px;
    border: 2px solid rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    
    &.credit-good {
      background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
    }
    
    &.credit-low {
      background: linear-gradient(135deg, #ff9800 0%, #ffb74d 100%);
    }
    
    &.credit-empty {
      background: linear-gradient(135deg, #f44336 0%, #ef5350 100%);
    }
    
    .credit-amount {
      font-weight: 700;
      font-size: 1.1rem;
    }
    
    .credit-label {
      margin-left: 0.25rem;
      opacity: 0.9;
    }
  }
}

.modern-tooltip {
  background: rgba(0, 0, 0, 0.9);
  border-radius: 8px;
  font-size: 0.875rem;
  padding: 0.75rem;
  backdrop-filter: blur(10px);
}

// Modern Tabs
.modern-tabs {
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  margin-bottom: 1.5rem;
  padding: 0.5rem;
  
  .tab-item {
    border-radius: 12px;
    margin: 0 0.25rem;
    min-height: 60px;
    transition: all 0.3s ease;
    
    &:hover {
      background: rgba(25, 118, 210, 0.05);
    }
    
    .tab-content {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.5rem;
      
      .tab-label {
        font-weight: 600;
        font-size: 0.95rem;
        line-height: 1.2;
      }
      
      .tab-caption {
        font-size: 0.8rem;
        opacity: 0.7;
        line-height: 1.1;
      }
    }
  }
}

.modern-panels {
  .panel-content {
    padding: 1.5rem;
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  }
}

// Results Section
.results-section {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.5rem;
  margin-top: 2rem;
  
  @media (min-width: 1024px) {
    grid-template-columns: 1fr 1fr;
    
    .full-width {
      grid-column: 1 / -1;
    }
  }
}

// Modern Card Structure
.modern-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
  overflow: hidden;
  transition: all 0.3s ease;
  
  &:hover {
    box-shadow: 0 12px 48px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
  }
}

// SMS Gradient
.sms-gradient {
  background: linear-gradient(135deg, $sms-primary 0%, $sms-secondary 100%);
}

// Card Header
.card-header {
  padding: 1.5rem 2rem;
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
        line-height: 1.2;
      }
      
      .header-subtitle {
        font-size: 0.9rem;
        margin: 0;
        opacity: 0.8;
        line-height: 1.1;
      }
    }
  }
  
  .header-actions {
    .modern-btn {
      border-radius: 8px;
      font-weight: 500;
      text-transform: none;
      border: 1px solid rgba(255, 255, 255, 0.3);
      
      &:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.5);
      }
    }
  }
}

.card-content {
  padding: 2rem;
}

// Status Display
.status-display {
  margin-bottom: 2rem;
  
  .status-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    border-radius: 12px;
    
    &.success {
      background: linear-gradient(135deg, #e8f5e8 0%, #f1f8e9 100%);
      border: 1px solid #c8e6c9;
      
      .status-icon {
        color: #4caf50;
      }
    }
    
    &.warning {
      background: linear-gradient(135deg, #fff3e0 0%, #fff8e1 100%);
      border: 1px solid #ffcc02;
      
      .status-icon {
        color: #ff9800;
      }
    }
    
    &.error {
      background: linear-gradient(135deg, #ffebee 0%, #fce4ec 100%);
      border: 1px solid #ffcdd2;
      
      .status-icon {
        color: #f44336;
      }
    }
    
    .status-icon {
      flex-shrink: 0;
    }
    
    .status-content {
      .status-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin: 0 0 0.5rem 0;
        color: #333;
      }
      
      .status-message {
        font-size: 0.95rem;
        margin: 0;
        color: #666;
        line-height: 1.4;
      }
    }
  }
}

// Summary Cards
.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
  
  .summary-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    border: 2px solid;
    transition: all 0.3s ease;
    
    &:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    }
    
    &.total {
      border-color: #2196f3;
      background: linear-gradient(135deg, #e3f2fd 0%, #f0f8ff 100%);
      
      .summary-icon {
        color: #2196f3;
      }
    }
    
    &.success {
      border-color: #4caf50;
      background: linear-gradient(135deg, #e8f5e8 0%, #f1f8e9 100%);
      
      .summary-icon {
        color: #4caf50;
      }
    }
    
    &.error {
      border-color: #f44336;
      background: linear-gradient(135deg, #ffebee 0%, #fce4ec 100%);
      
      .summary-icon {
        color: #f44336;
      }
    }
    
    .summary-icon {
      margin-bottom: 0.75rem;
    }
    
    .summary-content {
      .summary-value {
        font-size: 2rem;
        font-weight: 700;
        color: #333;
        line-height: 1;
        margin-bottom: 0.5rem;
      }
      
      .summary-label {
        font-size: 0.9rem;
        font-weight: 500;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }
    }
  }
}

// History Table
.history-table-wrapper {
  .modern-table {
    .q-table__top {
      padding: 0;
    }
    
    .q-table thead th {
      font-weight: 600;
      font-size: 0.875rem;
      color: #333;
      background: #f8f9fa;
      border-bottom: 2px solid #e9ecef;
    }
    
    .q-table tbody td {
      border-bottom: 1px solid #f0f0f0;
      font-size: 0.875rem;
    }
    
    .q-table tbody tr:hover {
      background: #f8f9fa;
    }
    
    .status-chip {
      font-weight: 500;
      font-size: 0.75rem;
      padding: 0.25rem 0.75rem;
      border-radius: 6px;
      
      &.status-success {
        background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
      }
      
      &.status-error {
        background: linear-gradient(135deg, #f44336 0%, #ef5350 100%);
      }
      
      &.status-warning {
        background: linear-gradient(135deg, #ff9800 0%, #ffb74d 100%);
      }
    }
  }
  
  .no-data-display {
    text-align: center;
    padding: 3rem 1rem;
    
    .no-data-text {
      margin-top: 1rem;
      font-size: 1rem;
      color: #999;
    }
  }
}

// Responsive Design
@media (max-width: 768px) {
  .page-header {
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    
    .header-content {
      flex-direction: column;
      gap: 1rem;
      
      .header-title-section {
        width: 100%;
        
        .title-icon-wrapper {
          padding: 0.75rem;
        }
        
        .title-text {
          .page-title {
            font-size: 1.5rem;
          }
          
          .page-subtitle {
            font-size: 1rem;
          }
        }
      }
      
      .header-badges {
        width: 100%;
        justify-content: center;
        flex-wrap: wrap;
      }
    }
  }
  
  .modern-tabs .tab-item {
    min-height: 50px;
    
    .tab-content {
      flex-direction: column;
      gap: 0.25rem;
      
      .tab-label {
        font-size: 0.875rem;
      }
      
      .tab-caption {
        font-size: 0.75rem;
      }
    }
  }
  
  .modern-panels .panel-content {
    padding: 1rem;
  }
  
  .card-header {
    padding: 1rem 1.5rem;
    flex-direction: column;
    gap: 1rem;
    
    .header-actions {
      width: 100%;
      text-align: center;
    }
  }
  
  .card-content {
    padding: 1.5rem;
  }
  
  .status-item {
    padding: 1rem !important;
    flex-direction: column;
    text-align: center;
    gap: 0.75rem !important;
  }
  
  .summary-cards {
    grid-template-columns: 1fr;
    gap: 0.75rem;
    
    .summary-card {
      padding: 1rem;
    }
  }
  
  .results-section {
    gap: 1rem;
    margin-top: 1.5rem;
  }
}

@media (max-width: 480px) {
  .page-header {
    padding: 1rem;
    border-radius: 12px;
    
    .header-title-section {
      gap: 1rem;
      
      .title-text .page-title {
        font-size: 1.25rem;
      }
    }
  }
  
  .modern-card {
    border-radius: 12px;
  }
  
  .card-content {
    padding: 1rem;
  }
  
  .summary-cards .summary-card {
    padding: 0.75rem;
    
    .summary-content .summary-value {
      font-size: 1.5rem;
    }
  }
}
</style>
