<template>
  <div class="q-pa-md">
    <div class="row q-mb-md items-center justify-between">
      <div class="text-h5">SMS Planifiés</div>
      <q-btn
        color="primary"
        icon="add"
        label="Nouveau SMS planifié"
        @click="openCreateDialog"
      />
    </div>

    <div class="row q-mb-md">
      <div class="col-12 col-md-6">
        <q-input
          v-model="searchQuery"
          outlined
          dense
          placeholder="Rechercher..."
          @keyup.enter="search"
        >
          <template v-slot:append>
            <q-icon
              v-if="searchQuery"
              name="close"
              @click="clearSearch"
              class="cursor-pointer"
            />
            <q-btn
              round
              flat
              icon="search"
              @click="search"
            />
          </template>
        </q-input>
      </div>
    </div>

    <q-table
      :rows="scheduledSMSList"
      :columns="columns"
      row-key="id"
      :loading="loading"
      :pagination="{ rowsPerPage: 0 }"
      :filter="searchQuery"
      no-data-label="Aucun SMS planifié trouvé"
      no-results-label="Aucun résultat trouvé"
      loading-label="Chargement..."
    >
      <template v-slot:body="props">
        <q-tr :props="props">
          <q-td key="name" :props="props">
            {{ props.row.name }}
          </q-td>
          <q-td key="message" :props="props">
            <div class="ellipsis" style="max-width: 200px;">
              {{ props.row.message }}
            </div>
          </q-td>
          <q-td key="scheduledDate" :props="props">
            {{ formatDate(props.row.scheduledDate) }}
          </q-td>
          <q-td key="status" :props="props">
            <q-chip
              :color="getStatusColor(props.row.status)"
              text-color="white"
              dense
            >
              {{ getStatusLabel(props.row.status) }}
            </q-chip>
          </q-td>
          <q-td key="isRecurring" :props="props">
            <q-icon
              v-if="props.row.isRecurring"
              name="repeat"
              color="primary"
              size="sm"
            />
            <span v-if="props.row.isRecurring && props.row.formattedRecurrenceConfig">
              {{ props.row.formattedRecurrenceConfig }}
            </span>
            <span v-else-if="props.row.isRecurring">Récurrent</span>
            <span v-else>Non</span>
          </q-td>
          <q-td key="recipientsCount" :props="props">
            {{ props.row.recipientsCount || 0 }}
          </q-td>
          <q-td key="actions" :props="props">
            <div class="row no-wrap">
              <q-btn
                flat
                round
                color="primary"
                icon="visibility"
                @click="viewDetails(props.row.id)"
                size="sm"
              >
                <q-tooltip>Voir les détails</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                color="amber"
                icon="edit"
                @click="editScheduledSMS(props.row.id)"
                size="sm"
                :disable="props.row.status === 'sent' || props.row.status === 'cancelled'"
              >
                <q-tooltip>Modifier</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                color="negative"
                icon="cancel"
                @click="confirmCancel(props.row.id)"
                size="sm"
                :disable="props.row.status === 'sent' || props.row.status === 'cancelled'"
              >
                <q-tooltip>Annuler</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                color="negative"
                icon="delete"
                @click="confirmDelete(props.row.id)"
                size="sm"
              >
                <q-tooltip>Supprimer</q-tooltip>
              </q-btn>
            </div>
          </q-td>
        </q-tr>
      </template>
    </q-table>

    <div class="row justify-center q-mt-md">
      <q-pagination
        v-model="currentPage"
        :max="totalPages"
        :max-pages="6"
        boundary-links
        direction-links
        @update:model-value="onPageChange"
      />
    </div>

    <!-- Détails du SMS planifié -->
    <q-dialog v-model="detailsDialog" persistent>
      <q-card style="min-width: 700px">
        <q-card-section class="row items-center">
          <div class="text-h6">Détails du SMS planifié</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section v-if="selectedScheduledSMS">
          <div class="row q-col-gutter-md">
            <div class="col-12 col-md-6">
              <q-item>
                <q-item-section>
                  <q-item-label caption>Nom</q-item-label>
                  <q-item-label>{{ selectedScheduledSMS.name }}</q-item-label>
                </q-item-section>
              </q-item>
            </div>
            <div class="col-12 col-md-6">
              <q-item>
                <q-item-section>
                  <q-item-label caption>Statut</q-item-label>
                  <q-item-label>
                    <q-chip
                      :color="getStatusColor(selectedScheduledSMS.status)"
                      text-color="white"
                      dense
                    >
                      {{ getStatusLabel(selectedScheduledSMS.status) }}
                    </q-chip>
                  </q-item-label>
                </q-item-section>
              </q-item>
            </div>
            <div class="col-12">
              <q-item>
                <q-item-section>
                  <q-item-label caption>Message</q-item-label>
                  <q-item-label>{{ selectedScheduledSMS.message }}</q-item-label>
                </q-item-section>
              </q-item>
            </div>
            <div class="col-12 col-md-6">
              <q-item>
                <q-item-section>
                  <q-item-label caption>Date planifiée</q-item-label>
                  <q-item-label>{{ formatDate(selectedScheduledSMS.scheduledDate) }}</q-item-label>
                </q-item-section>
              </q-item>
            </div>
            <div class="col-12 col-md-6">
              <q-item>
                <q-item-section>
                  <q-item-label caption>Récurrent</q-item-label>
                  <q-item-label>
                    <span v-if="selectedScheduledSMS.isRecurring && selectedScheduledSMS.formattedRecurrenceConfig">
                      {{ selectedScheduledSMS.formattedRecurrenceConfig }}
                    </span>
                    <span v-else-if="selectedScheduledSMS.isRecurring">Oui</span>
                    <span v-else>Non</span>
                  </q-item-label>
                </q-item-section>
              </q-item>
            </div>
            <div class="col-12 col-md-6">
              <q-item>
                <q-item-section>
                  <q-item-label caption>Type de destinataires</q-item-label>
                  <q-item-label>{{ getRecipientsTypeLabel(selectedScheduledSMS.recipientsType) }}</q-item-label>
                </q-item-section>
              </q-item>
            </div>
            <div class="col-12 col-md-6">
              <q-item>
                <q-item-section>
                  <q-item-label caption>Nombre de destinataires</q-item-label>
                  <q-item-label>{{ selectedScheduledSMS.recipientsCount || 0 }}</q-item-label>
                </q-item-section>
              </q-item>
            </div>
            <div class="col-12 col-md-6">
              <q-item>
                <q-item-section>
                  <q-item-label caption>Créé le</q-item-label>
                  <q-item-label>{{ formatDate(selectedScheduledSMS.createdAt) }}</q-item-label>
                </q-item-section>
              </q-item>
            </div>
            <div class="col-12 col-md-6">
              <q-item>
                <q-item-section>
                  <q-item-label caption>Dernière exécution</q-item-label>
                  <q-item-label>{{ selectedScheduledSMS.lastRunAt ? formatDate(selectedScheduledSMS.lastRunAt) : 'Jamais' }}</q-item-label>
                </q-item-section>
              </q-item>
            </div>
            <div class="col-12 col-md-6">
              <q-item>
                <q-item-section>
                  <q-item-label caption>Prochaine exécution</q-item-label>
                  <q-item-label>{{ selectedScheduledSMS.nextRunAt ? formatDate(selectedScheduledSMS.nextRunAt) : 'N/A' }}</q-item-label>
                </q-item-section>
              </q-item>
            </div>
          </div>
        </q-card-section>

        <q-card-section v-if="selectedScheduledSMS">
          <div class="text-h6">Historique d'exécution</div>
          <q-table
            :rows="scheduledSMSLogs"
            :columns="logsColumns"
            row-key="id"
            :loading="logsLoading"
            :pagination="{ rowsPerPage: 0 }"
            no-data-label="Aucun historique d'exécution trouvé"
            loading-label="Chargement..."
          >
            <template v-slot:body="props">
              <q-tr :props="props">
                <q-td key="executionDate" :props="props">
                  {{ formatDate(props.row.executionDate) }}
                </q-td>
                <q-td key="status" :props="props">
                  <q-chip
                    :color="props.row.statusColor"
                    text-color="white"
                    dense
                  >
                    {{ props.row.statusLabel }}
                  </q-chip>
                </q-td>
                <q-td key="totalRecipients" :props="props">
                  {{ props.row.totalRecipients }}
                </q-td>
                <q-td key="successfulSends" :props="props">
                  {{ props.row.successfulSends }}
                </q-td>
                <q-td key="failedSends" :props="props">
                  {{ props.row.failedSends }}
                </q-td>
                <q-td key="successRate" :props="props">
                  {{ props.row.successRate.toFixed(2) }}%
                </q-td>
                <q-td key="errorDetails" :props="props">
                  <q-btn
                    v-if="props.row.errorDetails"
                    flat
                    round
                    color="negative"
                    icon="error"
                    size="sm"
                    @click="showErrorDetails(props.row.errorDetails)"
                  >
                    <q-tooltip>Voir les erreurs</q-tooltip>
                  </q-btn>
                  <span v-else>-</span>
                </q-td>
              </q-tr>
            </template>
          </q-table>

          <div class="row justify-center q-mt-md">
            <q-pagination
              v-model="logsCurrentPage"
              :max="logsTotalPages"
              :max-pages="5"
              boundary-links
              direction-links
              @update:model-value="onLogsPageChange"
            />
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Formulaire de création/édition -->
    <q-dialog v-model="formDialog" persistent>
      <q-card style="min-width: 700px">
        <q-card-section class="row items-center">
          <div class="text-h6">{{ isEditing ? 'Modifier' : 'Créer' }} un SMS planifié</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section>
          <q-form @submit="submitForm" class="q-gutter-md">
            <q-input
              v-model="form.name"
              label="Nom *"
              outlined
              :rules="[val => !!val || 'Le nom est requis']"
            />

            <q-input
              v-model="form.message"
              label="Message *"
              type="textarea"
              outlined
              :rules="[
                val => !!val || 'Le message est requis',
                val => val.length <= 160 || 'Le message ne doit pas dépasser 160 caractères'
              ]"
              counter
              maxlength="160"
            />

            <q-select
              v-model="form.senderNameId"
              :options="senderNameOptions"
              label="Nom d'expéditeur *"
              outlined
              emit-value
              map-options
              :rules="[val => !!val || 'Le nom d\'expéditeur est requis']"
            />

            <q-input
              v-model="form.scheduledDate"
              label="Date d'envoi *"
              outlined
              :rules="[val => !!val || 'La date d\'envoi est requise']"
            >
              <template v-slot:append>
                <q-icon name="event" class="cursor-pointer">
                  <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                    <q-date
                      v-model="form.scheduledDate"
                      mask="YYYY-MM-DD HH:mm:ss"
                      today-btn
                    >
                      <div class="row items-center justify-end">
                        <q-btn v-close-popup label="Fermer" color="primary" flat />
                      </div>
                    </q-date>
                  </q-popup-proxy>
                </q-icon>
              </template>
              <template v-slot:after>
                <q-icon name="access_time" class="cursor-pointer">
                  <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                    <q-time
                      v-model="form.scheduledDate"
                      mask="YYYY-MM-DD HH:mm:ss"
                      format24h
                    >
                      <div class="row items-center justify-end">
                        <q-btn v-close-popup label="Fermer" color="primary" flat />
                      </div>
                    </q-time>
                  </q-popup-proxy>
                </q-icon>
              </template>
            </q-input>

            <q-toggle
              v-model="form.isRecurring"
              label="Récurrent"
            />

            <div v-if="form.isRecurring" class="q-gutter-md">
              <q-select
                v-model="form.recurrencePattern"
                :options="recurrencePatternOptions"
                label="Type de récurrence *"
                outlined
                emit-value
                map-options
                :rules="[val => !form.isRecurring || !!val || 'Le type de récurrence est requis']"
              />

              <div v-if="form.recurrencePattern === 'daily'">
                <q-input
                  v-model.number="form.recurrenceConfig.interval"
                  label="Intervalle (jours) *"
                  type="number"
                  outlined
                  :rules="[
                    val => !form.isRecurring || !!val || 'L\'intervalle est requis',
                    val => !form.isRecurring || val > 0 || 'L\'intervalle doit être supérieur à 0'
                  ]"
                />
              </div>

              <div v-if="form.recurrencePattern === 'weekly'">
                <q-input
                  v-model.number="form.recurrenceConfig.interval"
                  label="Intervalle (semaines) *"
                  type="number"
                  outlined
                  :rules="[
                    val => !form.isRecurring || !!val || 'L\'intervalle est requis',
                    val => !form.isRecurring || val > 0 || 'L\'intervalle doit être supérieur à 0'
                  ]"
                />
              </div>

              <div v-if="form.recurrencePattern === 'monthly'">
                <q-input
                  v-model.number="form.recurrenceConfig.interval"
                  label="Intervalle (mois) *"
                  type="number"
                  outlined
                  :rules="[
                    val => !form.isRecurring || !!val || 'L\'intervalle est requis',
                    val => !form.isRecurring || val > 0 || 'L\'intervalle doit être supérieur à 0'
                  ]"
                />
              </div>
            </div>

            <q-select
              v-model="form.recipientsType"
              :options="recipientsTypeOptions"
              label="Type de destinataires *"
              outlined
              emit-value
              map-options
              :rules="[val => !!val || 'Le type de destinataires est requis']"
            />

            <div v-if="form.recipientsType === 'segment'">
              <q-select
                v-model="form.recipientsData"
                :options="segmentOptions"
                label="Segment *"
                outlined
                emit-value
                map-options
                :rules="[val => !!val || 'Le segment est requis']"
              />
            </div>

            <div v-else-if="form.recipientsType === 'group'">
              <q-select
                v-model="form.recipientsData"
                :options="contactGroupOptions"
                label="Groupe de contacts *"
                outlined
                emit-value
                map-options
                :rules="[val => !!val || 'Le groupe de contacts est requis']"
              />
            </div>

            <div v-else-if="form.recipientsType === 'custom'">
              <q-input
                v-model="form.recipientsData"
                label="Numéros de téléphone *"
                type="textarea"
                outlined
                :rules="[val => !!val || 'Les numéros de téléphone sont requis']"
                hint="Entrez les numéros de téléphone séparés par des virgules ou des sauts de ligne"
              />
            </div>

            <div class="row justify-end q-mt-md">
              <q-btn
                label="Annuler"
                color="negative"
                flat
                v-close-popup
              />
              <q-btn
                label="Enregistrer"
                type="submit"
                color="primary"
                :loading="loading"
              />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Dialogue de confirmation pour annuler -->
    <q-dialog v-model="cancelDialog" persistent>
      <q-card>
        <q-card-section class="row items-center">
          <q-avatar icon="cancel" color="negative" text-color="white" />
          <span class="q-ml-sm">Annuler le SMS planifié</span>
        </q-card-section>

        <q-card-section>
          Êtes-vous sûr de vouloir annuler ce SMS planifié ? Cette action ne peut pas être annulée.
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Non" color="primary" v-close-popup />
          <q-btn flat label="Oui" color="negative" @click="cancelScheduledSMS" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Dialogue de confirmation pour supprimer -->
    <q-dialog v-model="deleteDialog" persistent>
      <q-card>
        <q-card-section class="row items-center">
          <q-avatar icon="delete" color="negative" text-color="white" />
          <span class="q-ml-sm">Supprimer le SMS planifié</span>
        </q-card-section>

        <q-card-section>
          Êtes-vous sûr de vouloir supprimer ce SMS planifié ? Cette action ne peut pas être annulée.
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Non" color="primary" v-close-popup />
          <q-btn flat label="Oui" color="negative" @click="deleteScheduledSMS" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Dialogue pour afficher les détails d'erreur -->
    <q-dialog v-model="errorDetailsDialog">
      <q-card style="min-width: 350px">
        <q-card-section>
          <div class="text-h6">Détails de l'erreur</div>
        </q-card-section>

        <q-card-section>
          <p>{{ errorDetails }}</p>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Fermer" color="primary" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue';
import { useScheduledSMSStore } from '../stores/scheduledSMSStore';
import { useSenderNameStore } from '../stores/senderNameStore';
import { useSegmentStore } from '../stores/segmentStore';
import { useContactGroupStore } from '../stores/contactGroupStore';
import { date } from 'quasar';

// Stores
const scheduledSMSStore = useScheduledSMSStore();
const senderNameStore = useSenderNameStore();
const segmentStore = useSegmentStore();
const contactGroupStore = useContactGroupStore();

// État local
const searchQuery = ref('');
const detailsDialog = ref(false);
const formDialog = ref(false);
const cancelDialog = ref(false);
const deleteDialog = ref(false);
const errorDetailsDialog = ref(false);
const errorDetails = ref('');
const isEditing = ref(false);
const selectedId = ref(null);

// Formulaire
const form = reactive({
  id: null,
  name: '',
  message: '',
  senderNameId: null,
  scheduledDate: date.formatDate(new Date(Date.now() + 24 * 60 * 60 * 1000), 'YYYY-MM-DD HH:mm:ss'),
  isRecurring: false,
  recurrencePattern: null,
  recurrenceConfig: {
    interval: 1
  },
  recipientsType: null,
  recipientsData: null
});

// Colonnes pour la table principale
const columns = [
  { name: 'name', label: 'Nom', field: 'name', sortable: true },
  { name: 'message', label: 'Message', field: 'message', sortable: true },
  { name: 'scheduledDate', label: 'Date planifiée', field: 'scheduledDate', sortable: true },
  { name: 'status', label: 'Statut', field: 'status', sortable: true },
  { name: 'isRecurring', label: 'Récurrent', field: 'isRecurring', sortable: true },
  { name: 'recipientsCount', label: 'Destinataires', field: 'recipientsCount', sortable: true },
  { name: 'actions', label: 'Actions', field: 'actions', align: 'center' }
];

// Colonnes pour la table des logs
const logsColumns = [
  { name: 'executionDate', label: 'Date d\'exécution', field: 'executionDate', sortable: true },
  { name: 'status', label: 'Statut', field: 'status', sortable: true },
  { name: 'totalRecipients', label: 'Total', field: 'totalRecipients', sortable: true },
  { name: 'successfulSends', label: 'Succès', field: 'successfulSends', sortable: true },
  { name: 'failedSends', label: 'Échecs', field: 'failedSends', sortable: true },
  { name: 'successRate', label: 'Taux de succès', field: 'successRate', sortable: true },
  { name: 'errorDetails', label: 'Erreurs', field: 'errorDetails', align: 'center' }
];

// Options pour les sélecteurs
const recurrencePatternOptions = [
  { label: 'Quotidien', value: 'daily' },
  { label: 'Hebdomadaire', value: 'weekly' },
  { label: 'Mensuel', value: 'monthly' }
];

const recipientsTypeOptions = [
  { label: 'Segment', value: 'segment' },
  { label: 'Groupe de contacts', value: 'group' },
  { label: 'Personnalisé', value: 'custom' }
];

// Getters
const scheduledSMSList = computed(() => scheduledSMSStore.paginatedScheduledSMS);
const loading = computed(() => scheduledSMSStore.loading);
const totalCount = computed(() => scheduledSMSStore.totalCount);
const currentPage = computed({
  get: () => scheduledSMSStore.currentPage,
  set: (value) => scheduledSMSStore.setPage(value)
});
const totalPages = computed(() => scheduledSMSStore.totalPages);
const selectedScheduledSMS = computed(() => scheduledSMSStore.selectedScheduledSMS);
const scheduledSMSLogs = computed(() => scheduledSMSStore.scheduledSMSLogs);
const logsLoading = computed(() => scheduledSMSStore.logsLoading);
const logsTotalCount = computed(() => scheduledSMSStore.logsTotalCount);
const logsCurrentPage = computed({
  get: () => scheduledSMSStore.logsCurrentPage,
  set: (value) => scheduledSMSStore.setLogsPage(value)
});
const logsTotalPages = computed(() => scheduledSMSStore.logsTotalPages);

const senderNameOptions = computed(() => {
  return senderNameStore.senderNames.map(senderName => ({
    label: senderName.name,
    value: senderName.id
  }));
});

const segmentOptions = computed(() => {
  return segmentStore.segments.map(segment => ({
    label: segment.name,
    value: segment.id.toString()
  }));
});

const contactGroupOptions = computed(() => {
  return contactGroupStore.contactGroups.map(group => ({
    label: group.name,
    value: group.id.toString()
  }));
});

// Méthodes
function formatDate(dateString) {
  if (!dateString) return '';
  return date.formatDate(dateString, 'DD/MM/YYYY HH:mm');
}

function getStatusColor(status) {
  switch (status) {
    case 'pending':
      return 'blue';
    case 'sent':
      return 'positive';
    case 'cancelled':
      return 'negative';
    case 'failed':
      return 'negative';
    default:
      return 'grey';
  }
}

function getStatusLabel(status) {
  switch (status) {
    case 'pending':
      return 'En attente';
    case 'sent':
      return 'Envoyé';
    case 'cancelled':
      return 'Annulé';
    case 'failed':
      return 'Échec';
    default:
      return status;
  }
}

function getRecipientsTypeLabel(type) {
  switch (type) {
    case 'segment':
      return 'Segment';
    case 'group':
      return 'Groupe de contacts';
    case 'custom':
      return 'Personnalisé';
    default:
      return type;
  }
}

function resetForm() {
  form.id = null;
  form.name = '';
  form.message = '';
  form.senderNameId = null;
  form.scheduledDate = date.formatDate(new Date(Date.now() + 24 * 60 * 60 * 1000), 'YYYY-MM-DD HH:mm:ss');
  form.isRecurring = false;
  form.recurrencePattern = null;
  form.recurrenceConfig = { interval: 1 };
  form.recipientsType = null;
  form.recipientsData = null;
}

function openCreateDialog() {
  isEditing.value = false;
  resetForm();
  formDialog.value = true;
}

async function editScheduledSMS(id) {
  isEditing.value = true;
  const scheduledSMS = await scheduledSMSStore.fetchScheduledSMSById(id);
  
  if (scheduledSMS) {
    form.id = scheduledSMS.id;
    form.name = scheduledSMS.name;
    form.message = scheduledSMS.message;
    form.senderNameId = scheduledSMS.senderNameId;
    form.scheduledDate = scheduledSMS.scheduledDate;
    form.isRecurring = scheduledSMS.isRecurring;
    form.recurrencePattern = scheduledSMS.recurrencePattern;
    
    if (scheduledSMS.recurrenceConfig) {
      try {
        form.recurrenceConfig = JSON.parse(scheduledSMS.recurrenceConfig);
      } catch (e) {
        console.error('Error parsing recurrence config:', e);
        form.recurrenceConfig = { interval: 1 };
      }
<template>
  <div class="q-pa-md">
    <div class="row q-mb-md items-center justify-between">
      <div class="text-h5">SMS Planifiés</div>
      <q-btn
        color="primary"
        icon="add"
        label="Nouveau SMS planifié"
        @click="openCreateDialog"
      />
    </div>

    <div class="row q-mb-md">
      <div class="col-12 col-md-6">
        <q-input
          v-model="searchQuery"
          outlined
          dense
          placeholder="Rechercher..."
          @keyup.enter="search"
        >
          <template v-slot:append>
            <q-icon
              v-if="searchQuery"
              name="close"
              @click="clearSearch"
              class="cursor-pointer"
            />
            <q-btn
              round
              flat
              icon="search"
              @click="search"
            />
          </template>
        </q-input>
      </div>
    </div>

    <q-table
      :rows="scheduledSMSList"
      :columns="columns"
      row-key="id"
      :loading="loading"
      :pagination="{ rowsPerPage: 0 }"
      :filter="searchQuery"
      no-data-label="Aucun SMS planifié trouvé"
      no-results-label="Aucun résultat trouvé"
      loading-label="Chargement..."
    >
      <template v-slot:body="props">
        <q-tr :props="props">
          <q-td key="name" :props="props">
            {{ props.row.name }}
          </q-td>
          <q-td key="message" :props="props">
            <div class="ellipsis" style="max-width: 200px;">
              {{ props.row.message }}
            </div>
          </q-td>
          <q-td key="scheduledDate" :props="props">
            {{ formatDate(props.row.scheduledDate) }}
          </q-td>
          <q-td key="status" :props="props">
            <q-chip
              :color="getStatusColor(props.row.status)"
              text-color="white"
              dense
            >
              {{ getStatusLabel(props.row.status) }}
            </q-chip>
          </q-td>
          <q-td key="isRecurring" :props="props">
            <q-icon
              v-if="props.row.isRecurring"
              name="repeat"
              color="primary"
              size="sm"
            />
            <span v-if="props.row.isRecurring && props.row.formattedRecurrenceConfig">
              {{ props.row.formattedRecurrenceConfig }}
            </span>
            <span v-else-if="props.row.isRecurring">Récurrent</span>
            <span v-else>Non</span>
          </q-td>
          <q-td key="recipientsCount" :props="props">
            {{ props.row.recipientsCount || 0 }}
          </q-td>
          <q-td key="actions" :props="props">
            <div class="row no-wrap">
              <q-btn
                flat
                round
                color="primary"
                icon="visibility"
                @click="viewDetails(props.row.id)"
                size="sm"
              >
                <q-tooltip>Voir les détails</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                color="amber"
                icon="edit"
                @click="editScheduledSMS(props.row.id)"
                size="sm"
                :disable="props.row.status === 'sent' || props.row.status === 'cancelled'"
              >
                <q-tooltip>Modifier</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                color="negative"
                icon="cancel"
                @click="confirmCancel(props.row.id)"
                size="sm"
                :disable="props.row.status === 'sent' || props.row.status === 'cancelled'"
              >
                <q-tooltip>Annuler</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                color="negative"
                icon="delete"
                @click="confirmDelete(props.row.id)"
                size="sm"
              >
                <q-tooltip>Supprimer</q-tooltip>
              </q-btn>
            </div>
          </q-td>
        </q-tr>
      </template>
    </q-table>

    <div class="row justify-center q-mt-md">
      <q-pagination
        v-model="currentPage"
        :max="totalPages"
        :max-pages="6"
        boundary-links
        direction-links
        @update:model-value="onPageChange"
      />
    </div>

    <!-- Détails du SMS planifié -->
    <q-dialog v-model="detailsDialog" persistent>
      <q-card style="min-width: 700px">
        <q-card-section class="row items-center">
          <div class="text-h6">Détails du SMS planifié</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section v-if="selectedScheduledSMS">
          <div class="row q-col-gutter-md">
            <div class="col-12 col-md-6">
              <q-item>
                <q-item-section>
                  <q-item-label caption>Nom</q-item-label>
                  <q-item-label>{{ selectedScheduledSMS.name }}</q-item-label>
                </q-item-section>
              </q-item>
            </div>
            <div class="col-12 col-md-6">
              <q-item>
                <q-item-section>
                  <q-item-label caption>Statut</q-item-label>
                  <q-item-label>
                    <q-chip
                      :color="getStatusColor(selectedScheduledSMS.status)"
                      text-color="white"
                      dense
                    >
                      {{ getStatusLabel(selectedScheduledSMS.status) }}
                    </q-chip>
                  </q-item-label>
                </q-item-section>
              </q-item>
            </div>
            <div class="col-12">
              <q-item>
                <q-item-section>
                  <q-item-label caption>Message</q-item-label>
                  <q-item-label>{{ selectedScheduledSMS.message }}</q-item-label>
                </q-item-section>
              </q-item>
            </div>
            <div class="col-12 col-md-6">
              <q-item>
                <q-item-section>
                  <q-item-label caption>Date planifiée</q-item-label>
                  <q-item-label>{{ formatDate(selectedScheduledSMS.scheduledDate) }}</q-item-label>
                </q-item-section>
              </q-item>
            </div>
            <div class="col-12 col-md-6">
              <q-item>
                <q-item-section>
                  <q-item-label caption>Récurrent</q-item-label>
                  <q-item-label>
                    <span v-if="selectedScheduledSMS.isRecurring && selectedScheduledSMS.formattedRecurrenceConfig">
                      {{ selectedScheduledSMS.formattedRecurrenceConfig }}
                    </span>
                    <span v-else-if="selectedScheduledSMS.isRecurring">Oui</span>
                    <span v-else>Non</span>
                  </q-item-label>
                </q-item-section>
              </q-item>
            </div>
            <div class="col-12 col-md-6">
              <q-item>
                <q-item-section>
                  <q-item-label caption>Type de destinataires</q-item-label>
                  <q-item-label>{{ getRecipientsTypeLabel(selectedScheduledSMS.recipientsType) }}</q-item-label>
                </q-item-section>
              </q-item>
            </div>
            <div class="col-12 col-md-6">
              <q-item>
                <q-item-section>
                  <q-item-label caption>Nombre de destinataires</q-item-label>
                  <q-item-label>{{ selectedScheduledSMS.recipientsCount || 0 }}</q-item-label>
                </q-item-section>
              </q-item>
            </div>
            <div class="col-12 col-md-6">
              <q-item>
                <q-item-section>
                  <q-item-label caption>Créé le</q-item-label>
                  <q-item-label>{{ formatDate(selectedScheduledSMS.createdAt) }}</q-item-label>
                </q-item-section>
              </q-item>
            </div>
            <div class="col-12 col-md-6">
              <q-item>
                <q-item-section>
                  <q-item-label caption>Dernière exécution</q-item-label>
                  <q-item-label>{{ selectedScheduledSMS.lastRunAt ? formatDate(selectedScheduledSMS.lastRunAt) : 'Jamais' }}</q-item-label>
                </q-item-section>
              </q-item>
            </div>
            <div class="col-12 col-md-6">
              <q-item>
                <q-item-section>
                  <q-item-label caption>Prochaine exécution</q-item-label>
                  <q-item-label>{{ selectedScheduledSMS.nextRunAt ? formatDate(selectedScheduledSMS.nextRunAt) : 'N/A' }}</q-item-label>
                </q-item-section>
              </q-item>
            </div>
          </div>
        </q-card-section>

        <q-card-section v-if="selectedScheduledSMS">
          <div class="text-h6">Historique d'exécution</div>
          <q-table
            :rows="scheduledSMSLogs"
            :columns="logsColumns"
            row-key="id"
            :loading="logsLoading"
            :pagination="{ rowsPerPage: 0 }"
            no-data-label="Aucun historique d'exécution trouvé"
            loading-label="Chargement..."
          >
            <template v-slot:body="props">
              <q-tr :props="props">
                <q-td key="executionDate" :props="props">
                  {{ formatDate(props.row.executionDate) }}
                </q-td>
                <q-td key="status" :props="props">
                  <q-chip
                    :color="props.row.statusColor"
                    text-color="white"
                    dense
                  >
                    {{ props.row.statusLabel }}
                  </q-chip>
                </q-td>
                <q-td key="totalRecipients" :props="props">
                  {{ props.row.totalRecipients }}
                </q-td>
                <q-td key="successfulSends" :props="props">
                  {{ props.row.successfulSends }}
                </q-td>
                <q-td key="failedSends" :props="props">
                  {{ props.row.failedSends }}
                </q-td>
                <q-td key="successRate" :props="props">
                  {{ props.row.successRate.toFixed(2) }}%
                </q-td>
                <q-td key="errorDetails" :props="props">
                  <q-btn
                    v-if="props.row.errorDetails"
                    flat
                    round
                    color="negative"
                    icon="error"
                    size="sm"
                    @click="showErrorDetails(props.row.errorDetails)"
                  >
                    <q-tooltip>Voir les erreurs</q-tooltip>
                  </q-btn>
                  <span v-else>-</span>
                </q-td>
              </q-tr>
            </template>
          </q-table>

          <div class="row justify-center q-mt-md">
            <q-pagination
              v-model="logsCurrentPage"
              :max="logsTotalPages"
              :max-pages="5"
              boundary-links
              direction-links
              @update:model-value="onLogsPageChange"
            />
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Formulaire de création/édition -->
    <q-dialog v-model="formDialog" persistent>
      <q-card style="min-width: 700px">
        <q-card-section class="row items-center">
          <div class="text-h6">{{ isEditing ? 'Modifier' : 'Créer' }} un SMS planifié</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section>
          <q-form @submit="submitForm" class="q-gutter-md">
            <q-input
              v-model="form.name"
              label="Nom *"
              outlined
              :rules="[val => !!val || 'Le nom est requis']"
            />

            <q-input
              v-model="form.message"
              label="Message *"
              type="textarea"
              outlined
              :rules="[
                val => !!val || 'Le message est requis',
                val => val.length <= 160 || 'Le message ne doit pas dépasser 160 caractères'
              ]"
              counter
              maxlength="160"
            />

            <q-select
              v-model="form.senderNameId"
              :options="senderNameOptions"
              label="Nom d'expéditeur *"
              outlined
              emit-value
              map-options
              :rules="[val => !!val || 'Le nom d\'expéditeur est requis']"
            />

            <q-input
              v-model="form.scheduledDate"
              label="Date d'envoi *"
              outlined
              :rules="[val => !!val || 'La date d\'envoi est requise']"
            >
              <template v-slot:append>
                <q-icon name="event" class="cursor-pointer">
                  <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                    <q-date
                      v-model="form.scheduledDate"
                      mask="YYYY-MM-DD HH:mm:ss"
                      today-btn
                    >
                      <div class="row items-center justify-end">
                        <q-btn v-close-popup label="Fermer" color="primary" flat />
                      </div>
                    </q-date>
                  </q-popup-proxy>
                </q-icon>
              </template>
              <template v-slot:after>
                <q-icon name="access_time" class="cursor-pointer">
                  <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                    <q-time
                      v-model="form.scheduledDate"
                      mask="YYYY-MM-DD HH:mm:ss"
                      format24h
                    >
                      <div class="row items-center justify-end">
                        <q-btn v-close-popup label="Fermer" color="primary" flat />
                      </div>
                    </q-time>
                  </q-popup-proxy>
                </q-icon>
              </template>
            </q-input>

            <q-toggle
              v-model="form.isRecurring"
              label="Récurrent"
            />

            <div v-if="form.isRecurring" class="q-gutter-md">
              <q-select
                v-model="form.recurrencePattern"
                :options="recurrencePatternOptions"
                label="Type de récurrence *"
                outlined
                emit-value
                map-options
                :rules="[val => !form.isRecurring || !!val || 'Le type de récurrence est requis']"
              />

              <div v-if="form.recurrencePattern === 'daily'">
                <q-input
                  v-model.number="form.recurrenceConfig.interval"
                  label="Intervalle (jours) *"
                  type="number"
                  outlined
                  :rules="[
                    val => !form.isRecurring || !!val || 'L\'intervalle est requis',
                    val => !form.isRecurring || val > 0 || 'L\'intervalle doit être supérieur à 0'
                  ]"
                />
              </div>

              <div v-if="form.recurrencePattern === 'weekly'">
                <q-input
                  v-model.number="form.recurrenceConfig.interval"
                  label="Intervalle (semaines) *"
                  type="number"
                  outlined
                  :rules="[
                    val => !form.isRecurring || !!val || 'L\'intervalle est requis',
                    val => !form.isRecurring || val > 0 || 'L\'intervalle doit être supérieur à 0'
                  ]"
                />
              </div>

              <div v-if="form.recurrencePattern === 'monthly'">
                <q-input
                  v-model.number="form.recurrenceConfig.interval"
                  label="Intervalle (mois) *"
                  type="number"
                  outlined
                  :rules="[
                    val => !form.isRecurring || !!val || 'L\'intervalle est requis',
                    val => !form.isRecurring || val > 0 || 'L\'intervalle doit être supérieur à 0'
                  ]"
                />
              </div>
            </div>

            <q-select
              v-model="form.recipientsType"
              :options="recipientsTypeOptions"
              label="Type de destinataires *"
              outlined
              emit-value
              map-options
              :rules="[val => !!val || 'Le type de destinataires est requis']"
            />

            <div v-if="form.recipientsType === 'segment'">
              <q-select
                v-model="form.recipientsData"
                :options="segmentOptions"
                label="Segment *"
                outlined
                emit-value
                map-options
                :rules="[val => !!val || 'Le segment est requis']"
              />
            </div>

            <div v-else-if="form.recipientsType === 'group'">
              <q-select
                v-model="form.recipientsData"
                :options="contactGroupOptions"
                label="Groupe de contacts *"
                outlined
                emit-value
                map-options
                :rules="[val => !!val || 'Le groupe de contacts est requis']"
              />
            </div>

            <div v-else-if="form.recipientsType === 'custom'">
              <q-input
                v-model="form.recipientsData"
                label="Numéros de téléphone *"
                type="textarea"
                outlined
                :rules="[val => !!val || 'Les numéros de téléphone sont requis']"
                hint="Entrez les numéros de téléphone séparés par des virgules ou des sauts de ligne"
              />
            </div>

            <div class="row justify-end q-mt-md">
              <q-btn
                label="Annuler"
                color="negative"
                flat
                v-close-popup
              />
              <q-btn
                label="Enregistrer"
                type="submit"
                color="primary"
                :loading="loading"
              />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Dialogue de confirmation pour annuler -->
    <q-dialog v-model="cancelDialog" persistent>
      <q-card>
        <q-card-section class="row items-center">
          <q-avatar icon="cancel" color="negative" text-color="white" />
          <span class="q-ml-sm">Annuler le SMS planifié</span>
        </q-card-section>

        <q-card-section>
          Êtes-vous sûr de vouloir annuler ce SMS planifié ? Cette action ne peut pas être annulée.
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Non" color="primary" v-close-popup />
          <q-btn flat label="Oui" color="negative" @click="cancelScheduledSMS" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Dialogue de confirmation pour supprimer -->
    <q-dialog v-model="deleteDialog" persistent>
      <q-card>
        <q-card-section class="row items-center">
          <q-avatar icon="delete" color="negative" text-color="white" />
          <span class="q-ml-sm">Supprimer le SMS planifié</span>
        </q-card-section>

        <q-card-section>
          Êtes-vous sûr de vouloir supprimer ce SMS planifié ? Cette action ne peut pas être annulée.
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Non" color="primary" v-close-popup />
          <q-btn flat label="Oui" color="negative" @click="deleteScheduledSMS" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Dialogue pour afficher les détails d'erreur -->
    <q-dialog v-model="errorDetailsDialog">
      <q-card style="min-width: 350px">
        <q-card-section>
          <div class="text-h6">Détails de l'erreur</div>
        </q-card-section>

        <q-card-section>
          <p>{{ errorDetails }}</p>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Fermer" color="primary" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue';
import { useScheduledSMSStore } from '../stores/scheduledSMSStore';
import { useSenderNameStore } from '../stores/senderNameStore';
import { useSegmentStore } from '../stores/segmentStore';
import { useContactGroupStore } from '../stores/contactGroupStore';
import { date } from 'quasar';

// Stores
const scheduledSMSStore = useScheduledSMSStore();
const senderNameStore = useSenderNameStore();
const segmentStore = useSegmentStore();
const contactGroupStore = useContactGroupStore();

// État local
const searchQuery = ref('');
const detailsDialog = ref(false);
const formDialog = ref(false);
const cancelDialog = ref(false);
const deleteDialog = ref(false);
const errorDetailsDialog = ref(false);
const errorDetails = ref('');
const isEditing = ref(false);
const selectedId = ref(null);

// Formulaire
const form = reactive({
  id: null,
  name: '',
  message: '',
  senderNameId: null,
  scheduledDate: date.formatDate(new Date(Date.now() + 24 * 60 * 60 * 1000), 'YYYY-MM-DD HH:mm:ss'),
  isRecurring: false,
  recurrencePattern: null,
  recurrenceConfig: {
    interval: 1
  },
  recipientsType: null,
  recipientsData: null
});

// Colonnes pour la table principale
const columns = [
  { name: 'name', label: 'Nom', field: 'name', sortable: true },
  { name: 'message', label: 'Message', field: 'message', sortable: true },
  { name: 'scheduledDate', label: 'Date planifiée', field: 'scheduledDate', sortable: true },
  { name: 'status', label: 'Statut', field: 'status', sortable: true },
  { name: 'isRecurring', label: 'Récurrent', field: 'isRecurring', sortable: true },
  { name: 'recipientsCount', label: 'Destinataires', field: 'recipientsCount', sortable: true },
  { name: 'actions', label: 'Actions', field: 'actions', align: 'center' }
];

// Colonnes pour la table des logs
const logsColumns = [
  { name: 'executionDate', label: 'Date d\'exécution', field: 'executionDate', sortable: true },
  { name: 'status', label: 'Statut', field: 'status', sortable: true },
  { name: 'totalRecipients', label: 'Total', field: 'totalRecipients', sortable: true },
  { name: 'successfulSends', label: 'Succès', field: 'successfulSends', sortable: true },
  { name: 'failedSends', label: 'Échecs', field: 'failedSends', sortable: true },
  { name: 'successRate', label: 'Taux de succès', field: 'successRate', sortable: true },
  { name: 'errorDetails', label: 'Erreurs', field: 'errorDetails', align: 'center' }
];

// Options pour les sélecteurs
const recurrencePatternOptions = [
  { label: 'Quotidien', value: 'daily' },
  { label: 'Hebdomadaire', value: 'weekly' },
  { label: 'Mensuel', value: 'monthly' }
];

const recipientsTypeOptions = [
  { label: 'Segment', value: 'segment' },
  { label: 'Groupe de contacts', value: 'group' },
  { label: 'Personnalisé', value: 'custom' }
];

// Getters
const scheduledSMSList = computed(() => scheduledSMSStore.paginatedScheduledSMS);
const loading = computed(() => scheduledSMSStore.loading);
const totalCount = computed(() => scheduledSMSStore.totalCount);
const currentPage = computed({
  get: () => scheduledSMSStore.currentPage,
  set: (value) => scheduledSMSStore.setPage(value)
});
const totalPages = computed(() => scheduledSMSStore.totalPages);
const selectedScheduledSMS = computed(() => scheduledSMSStore.selectedScheduledSMS);
const scheduledSMSLogs = computed(() => scheduledSMSStore.scheduledSMSLogs);
const logsLoading = computed(() => scheduledSMSStore.logsLoading);
const logsTotalCount = computed(() => scheduledSMSStore.logsTotalCount);
const logsCurrentPage = computed({
  get: () => scheduledSMSStore.logsCurrentPage,
  set: (value) => scheduledSMSStore.setLogsPage(value)
});
const logsTotalPages = computed(() => scheduledSMSStore.logsTotalPages);

const senderNameOptions = computed(() => {
  return senderNameStore.senderNames.map(senderName => ({
    label: senderName.name,
    value: senderName.id
  }));
});

const segmentOptions = computed(() => {
  return segmentStore.segments.map(segment => ({
    label: segment.name,
    value: segment.id.toString()
  }));
});

const contactGroupOptions = computed(() => {
  return contactGroupStore.contactGroups.map(group => ({
    label: group.name,
    value: group.id.toString()
  }));
});

// Méthodes
function formatDate(dateString) {
  if (!dateString) return '';
  return date.formatDate(dateString, 'DD/MM/YYYY HH:mm');
}

function getStatusColor(status) {
  switch (status) {
    case 'pending':
      return 'blue';
    case 'sent':
      return 'positive';
    case 'cancelled':
      return 'negative';
    case 'failed':
      return 'negative';
    default:
      return 'grey';
  }
}

function getStatusLabel(status) {
  switch (status) {
    case 'pending':
      return 'En attente';
    case 'sent':
      return 'Envoyé';
    case 'cancelled':
      return 'Annulé';
    case 'failed':
      return 'Échec';
    default:
      return status;
  }
}

function getRecipientsTypeLabel(type) {
  switch (type) {
    case 'segment':
      return 'Segment';
    case 'group':
      return 'Groupe de contacts';
    case 'custom':
      return 'Personnalisé';
    default:
      return type;
  }
}

function resetForm() {
  form.id = null;
  form.name = '';
  form.message = '';
  form.senderNameId = null;
  form.scheduledDate = date.formatDate(new Date(Date.now() + 24 * 60 * 60 * 1000), 'YYYY-MM-DD HH:mm:ss');
  form.isRecurring = false;
  form.recurrencePattern = null;
  form.recurrenceConfig = { interval: 1 };
  form.recipientsType = null;
  form.recipientsData = null;
}

function openCreateDialog() {
  isEditing.value = false;
  resetForm();
  formDialog.value = true;
}

async function editScheduledSMS(id) {
  isEditing.value = true;
  const scheduledSMS = await scheduledSMSStore.fetchScheduledSMSById(id);
  
  if (scheduledSMS) {
    form.id = scheduledSMS.id;
    form.name = scheduledSMS.name;
    form.message = scheduledSMS.message;
    form.senderNameId = scheduledSMS.senderNameId;
    form.scheduledDate = scheduledSMS.scheduledDate;
    form.isRecurring = scheduledSMS.isRecurring;
    form.recurrencePattern = scheduledSMS.recurrencePattern;
    
    if (scheduledSMS.recurrenceConfig) {
      try {
        form.recurrenceConfig = JSON.parse(scheduledSMS.recurrenceConfig);
      } catch (e
