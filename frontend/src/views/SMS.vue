<template>
  <q-page padding>
    <div class="q-pa-md">
      <div class="row items-center q-mb-md">
        <h1 class="text-h4 q-my-none">Envoi de SMS</h1>
        <q-space />
        <div v-if="userStore.currentUser" class="row items-center">
          <!-- Badge crédits SMS -->
          <q-chip
            :color="getCreditColor(userStore.currentUser.smsCredit)"
            text-color="white"
            icon="sms"
            class="q-mr-sm"
          >
            {{ userStore.currentUser.smsCredit }} crédit{{ userStore.currentUser.smsCredit !== 1 ? 's' : '' }} SMS
          </q-chip>
          <q-tooltip>
            <div v-if="userStore.currentUser.smsCredit <= 0">
              Vous n'avez plus de crédits SMS. Contactez l'administrateur pour en obtenir plus.
            </div>
            <div v-else-if="userStore.currentUser.smsCredit < 5">
              Attention, votre crédit SMS est faible.
            </div>
            <div v-else>
              Crédit SMS disponible.
            </div>
          </q-tooltip>
          
          <!-- Badge nombre de contacts -->
          <q-chip
            color="primary"
            text-color="white"
            icon="contacts"
          >
            {{ contactsCount }} contact{{ contactsCount !== 1 ? 's' : '' }}
          </q-chip>
          <q-tooltip>
            Nombre total de contacts disponibles pour l'envoi de SMS.
          </q-tooltip>
        </div>
      </div>

      <q-tabs
        v-model="activeTab"
        class="text-primary q-mb-md"
        indicator-color="primary"
        align="left"
      >
        <q-tab name="single" label="Envoi Individuel" icon="person" />
        <q-tab name="bulk" label="Envoi en Masse" icon="people" />
        <q-tab name="segment" label="Envoi par Segment" icon="segment" />
        <q-tab name="allContacts" label="À Tous les Contacts" icon="groups" />
      </q-tabs>

      <q-tab-panels v-model="activeTab" animated>
        <!-- Onglet Envoi Individuel -->
        <q-tab-panel name="single">
          <!-- Use the new component -->
          <SingleSmsForm
            ref="singleSmsFormRef"
            :loading="loading"
            :has-insufficient-credits="hasInsufficientCredits"
            @submit-single="handleSingleSubmit"
          />
        </q-tab-panel>

        <!-- Onglet Envoi en Masse -->
        <q-tab-panel name="bulk">
          <!-- Use the new component -->
          <BulkSmsForm
            ref="bulkSmsFormRef"
            :loading="loading"
            :has-insufficient-credits="hasInsufficientCredits"
            @submit-bulk="handleBulkSubmit"
          />
        </q-tab-panel>

        <!-- Onglet Envoi par Segment -->
         <q-tab-panel name="segment">
           <!-- Use the new component -->
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
         <q-tab-panel name="allContacts">
           <!-- Use the new component -->
           <AllContactsSmsForm
             ref="allContactsSmsFormRef"
             :loading="loading"
             :has-insufficient-credits="hasInsufficientCredits"
             @submit-all-contacts="handleAllContactsSubmit"
           />
         </q-tab-panel>

      </q-tab-panels>

      <!-- Résultats et historique -->
      <div class="row q-col-gutter-md q-mt-md">
        <!-- Résultat de l'envoi -->
        <div class="col-12 col-md-6" v-if="smsResult">
          <q-card>
            <q-card-section>
              <div class="text-h6">Résultat de l'envoi</div>
            </q-card-section>

            <q-card-section>
              <!-- Display logic using standardized FrontendStatus -->
              <div v-if="smsResult">
                <!-- Success status -->
                <div v-if="smsResult.status === 'success'" class="text-positive">
                  <q-icon name="check_circle" size="md" />
                  <span class="q-ml-sm">{{ smsResult.message || 'Opération réussie.' }}</span>
                </div>
                <!-- Warning status -->
                <div v-else-if="smsResult.status === 'warning'" class="text-warning">
                  <q-icon name="warning" size="md" />
                  <span class="q-ml-sm">{{ smsResult.message || 'Opération terminée avec des avertissements.' }}</span>
                </div>
                <!-- Error status -->
                <div v-else class="text-negative">
                  <q-icon name="error" size="md" />
                  <span class="q-ml-sm">{{ smsResult.message || 'Échec de l\'opération.' }}</span>
                </div>
              </div>

              <!-- Résumé pour l'envoi en masse, par segment ou à tous -->
              <!-- Use 'in' operator for type guarding here as well -->
              <q-list
                v-if="'summary' in smsResult && smsResult.summary"
                bordered
                separator
                class="q-mt-md"
              >
                <q-item>
                  <q-item-section>
                    <q-item-label>Total</q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-badge color="primary">{{
                      smsResult.summary.total
                    }}</q-badge>
                  </q-item-section>
                </q-item>
                <q-item>
                  <q-item-section>
                    <q-item-label>Réussis</q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-badge color="positive">{{
                      smsResult.summary.successful
                    }}</q-badge>
                  </q-item-section>
                </q-item>
                <q-item>
                  <q-item-section>
                    <q-item-label>Échoués</q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-badge color="negative">{{
                      smsResult.summary.failed
                    }}</q-badge>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card-section>
          </q-card>
        </div>

      <!-- Historique des SMS -->
      <div class="col-12" :class="{ 'col-md-6': smsResult }">
        <q-card>
          <q-card-section class="row items-center">
            <div class="text-h6">Historique des SMS</div>
            <q-space />
            <q-btn
              color="primary"
              icon="history"
              label="Voir tout l'historique"
              flat
              :to="{ name: 'sms-history' }"
            />
          </q-card-section>

          <q-card-section>
            <q-table
              :rows="smsHistory"
              :columns="columns"
              row-key="id"
              :loading="loadingHistory"
              :pagination="{ rowsPerPage: 5 }"
            >
              <template v-slot:body-cell-status="props">
                <q-td :props="props">
                  <q-chip
                    :color="
                      props.row.status === 'SENT'
                        ? 'positive'
                        : props.row.status === 'FAILED'
                          ? 'negative'
                          : 'warning'
                    "
                    text-color="white"
                    dense
                  >
                    {{ props.row.status }}
                  </q-chip>
                </q-td>
              </template>
            </q-table>
          </q-card-section>
        </q-card>
      </div>
      </div>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
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

// $q is no longer needed here
// const $q = useQuasar();
// smsTemplateStore is removed
// Utiliser le store utilisateur pour accéder à l'utilisateur courant
const userStore = useUserStore();
const contactStore = useContactStore(); // Use contactStore
const contactsCount = ref(0); // Add contactsCount variable

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

// Initialisation (Use composable functions)
onMounted(async () => {
  fetchSmsHistory();
  fetchSegments();
  // Récupérer le nombre de contacts
  contactsCount.value = await contactStore.fetchContactsCount();
  // smsTemplateStore.fetchTemplates(); // Template fetching might be needed in the child component now, or handled globally
  // User fetching should be handled globally or by userStore itself
});
</script>
