<template>
  <div class="template-history-container">
    <q-card class="q-mb-md">
      <q-card-section>
        <div class="text-h6">Historique des Templates</div>
        <div class="text-subtitle2">Consultez et analysez l'utilisation des templates WhatsApp</div>
      </q-card-section>

      <q-card-section>
        <div class="row q-col-gutter-md">
          <!-- Filtres -->
          <div class="col-12 col-md-12">
            <div class="row q-col-gutter-sm">
              <div class="col-12 col-sm-4">
                <q-input v-model="filterTemplateName" dense outlined label="Nom du template" clearable>
                  <template v-slot:append>
                    <q-icon name="search" />
                  </template>
                </q-input>
              </div>
              <div class="col-12 col-sm-3">
                <q-select 
                  v-model="filterLanguage" 
                  :options="languageOptions" 
                  dense 
                  outlined 
                  label="Langue" 
                  emit-value 
                  map-options 
                  clearable
                />
              </div>
              <div class="col-12 col-sm-3">
                <q-input v-model="filterPhoneNumber" dense outlined label="Numéro de téléphone" clearable>
                  <template v-slot:append>
                    <q-icon name="phone" />
                  </template>
                </q-input>
              </div>
              <div class="col-12 col-sm-2">
                <q-btn color="primary" icon="filter_alt" label="Filtrer" @click="applyFilters" />
                <q-btn color="secondary" flat icon="refresh" @click="resetFilters" class="q-ml-sm" />
              </div>
            </div>
          </div>
        </div>
      </q-card-section>

      <q-separator />

      <!-- Statistiques -->
      <q-card-section v-if="mostUsedTemplates.length > 0">
        <div class="text-subtitle1 q-mb-sm">Templates les plus utilisés</div>
        <div class="row q-col-gutter-md">
          <div class="col-12 col-md-6">
            <q-list bordered separator>
              <q-item v-for="(template, index) in mostUsedTemplates" :key="index">
                <q-item-section>
                  <q-item-label>{{ template.templateName }}</q-item-label>
                  <q-item-label caption>Langue: {{ template.language }}</q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-badge color="primary" :label="template.count" />
                </q-item-section>
              </q-item>
            </q-list>
          </div>
          <div class="col-12 col-md-6">
            <div class="text-subtitle1 q-mb-sm">Paramètres courants</div>
            <q-list bordered separator>
              <q-expansion-item
                v-for="(param, index) in commonParameters" 
                :key="index"
                :label="param.templateName"
                group="parameters"
                dense
              >
                <q-card>
                  <q-card-section>
                    <div v-for="(values, key) in param.parameterValues" :key="key" class="q-mb-sm">
                      <div class="text-weight-bold">{{ key }}</div>
                      <q-chip 
                        v-for="(value, i) in values" 
                        :key="i" 
                        color="grey-3" 
                        text-color="black"
                        class="q-ma-xs"
                      >
                        {{ value }}
                      </q-chip>
                    </div>
                  </q-card-section>
                </q-card>
              </q-expansion-item>
            </q-list>
          </div>
        </div>
      </q-card-section>

      <q-separator v-if="mostUsedTemplates.length > 0" />

      <!-- Liste des templates utilisés -->
      <q-card-section>
        <q-table
          :rows="paginatedTemplateHistory"
          :columns="columns"
          row-key="id"
          :loading="isLoading"
          :pagination.sync="pagination"
          :rows-per-page-options="[10, 20, 50, 100]"
          binary-state-sort
          flat
          bordered
          @request="onRequest"
          no-data-label="Aucun historique de template disponible"
        >
          <template v-slot:loading>
            <q-inner-loading showing color="primary" />
          </template>

          <template v-slot:body-cell-templateName="props">
            <q-td :props="props">
              <div class="text-weight-medium">{{ props.row.templateName }}</div>
              <q-badge color="blue-grey" text-color="white" class="q-ml-xs">{{ props.row.language }}</q-badge>
              <q-badge v-if="props.row.category" color="teal" text-color="white" class="q-ml-xs">{{ props.row.category }}</q-badge>
            </q-td>
          </template>

          <template v-slot:body-cell-bodyVariables="props">
            <q-td :props="props">
              <div v-if="props.row.bodyVariables && props.row.bodyVariables.length">
                <q-chip 
                  v-for="(variable, index) in props.row.bodyVariables" 
                  :key="index" 
                  size="sm" 
                  color="grey-3" 
                  text-color="black"
                >
                  {{ variable }}
                </q-chip>
              </div>
              <div v-else>-</div>
            </q-td>
          </template>

          <template v-slot:body-cell-media="props">
            <q-td :props="props">
              <div v-if="props.row.headerMediaType">
                <q-badge :color="getMediaColor(props.row.headerMediaType)">{{ props.row.headerMediaType }}</q-badge>
                <q-btn 
                  v-if="props.row.headerMediaUrl" 
                  dense 
                  flat 
                  round 
                  color="primary" 
                  icon="visibility" 
                  @click="previewMedia(props.row.headerMediaUrl, props.row.headerMediaType)"
                />
              </div>
              <div v-else>-</div>
            </q-td>
          </template>

          <template v-slot:body-cell-status="props">
            <q-td :props="props">
              <q-badge :color="getStatusColor(props.row.status)">{{ props.row.status }}</q-badge>
            </q-td>
          </template>

          <template v-slot:body-cell-createdAt="props">
            <q-td :props="props">
              {{ formatDateTime(props.row.createdAt) }}
            </q-td>
          </template>

          <template v-slot:body-cell-actions="props">
            <q-td :props="props" class="q-gutter-xs">
              <q-btn flat round dense color="primary" icon="repeat" @click="reuseTemplate(props.row)" />
              <q-tooltip>Réutiliser ce template</q-tooltip>
            </q-td>
          </template>
        </q-table>
      </q-card-section>

      <q-card-section v-if="totalPages > 1">
        <div class="row justify-center">
          <q-pagination
            v-model="currentPage"
            :max="totalPages"
            direction-links
            boundary-links
          />
        </div>
      </q-card-section>
    </q-card>

    <!-- Modal pour prévisualisation des médias -->
    <q-dialog v-model="mediaPreviewOpen">
      <q-card style="min-width: 350px">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Prévisualisation du média</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section class="text-center">
          <img v-if="mediaType === 'image'" :src="mediaUrl" style="max-width: 100%; max-height: 60vh;" />
          <video v-else-if="mediaType === 'video'" :src="mediaUrl" controls style="max-width: 100%; max-height: 60vh;"></video>
          <audio v-else-if="mediaType === 'audio'" :src="mediaUrl" controls></audio>
          <div v-else-if="mediaType === 'document'">
            <q-icon name="description" size="100px" color="primary" />
            <div class="q-mt-md">
              <q-btn color="primary" icon="download" label="Télécharger" @click="downloadDocument(mediaUrl)" />
            </div>
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useWhatsAppStore, WhatsAppTemplateHistory } from '@/stores/whatsappStore';
import { useQuasar } from 'quasar';
import { formatFullDate } from '@/utils/formatters';

const whatsAppStore = useWhatsAppStore();
const $q = useQuasar();

// État local
const filterTemplateName = ref('');
const filterLanguage = ref('');
const filterPhoneNumber = ref('');
const currentPage = ref(1);
const pageSize = ref(20);
const mediaPreviewOpen = ref(false);
const mediaUrl = ref('');
const mediaType = ref('');

// Options pour le filtre de langue
const languageOptions = [
  { label: 'Français', value: 'fr' },
  { label: 'Anglais', value: 'en' },
  { label: 'Espagnol', value: 'es' }
];

// Colonnes pour la table
const columns = [
  { name: 'templateName', required: true, label: 'Template', align: 'left', field: 'templateName', sortable: true },
  { name: 'recipient', label: 'Destinataire', align: 'left', field: 'recipient', sortable: true },
  { name: 'bodyVariables', label: 'Variables', align: 'left', field: 'bodyVariables' },
  { name: 'media', label: 'Média', align: 'center', field: 'headerMediaType' },
  { name: 'status', label: 'Statut', align: 'center', field: 'status', sortable: true },
  { name: 'createdAt', label: 'Date d\'envoi', align: 'left', field: 'createdAt', sortable: true },
  { name: 'actions', label: 'Actions', align: 'center' }
];

// Configuration de la pagination
const pagination = ref({
  page: 1,
  rowsPerPage: 20,
  sortBy: 'createdAt',
  descending: true
});

// Getters
const paginatedTemplateHistory = computed(() => whatsAppStore.paginatedTemplateHistory);
const totalPages = computed(() => whatsAppStore.templateHistoryTotalPages);
const isLoading = computed(() => whatsAppStore.isLoadingTemplateHistory);
const mostUsedTemplates = computed(() => whatsAppStore.mostUsedTemplates);
const commonParameters = computed(() => whatsAppStore.commonParameters);

// Méthodes
const applyFilters = () => {
  whatsAppStore.setTemplateHistoryFilters(
    filterTemplateName.value,
    filterLanguage.value,
    filterPhoneNumber.value
  );
};

const resetFilters = () => {
  filterTemplateName.value = '';
  filterLanguage.value = '';
  filterPhoneNumber.value = '';
  whatsAppStore.setTemplateHistoryFilters('', '', '');
};

const formatDateTime = (dateTime: string) => {
  return formatFullDate(dateTime);
};

const getStatusColor = (status: string) => {
  switch (status.toLowerCase()) {
    case 'sent':
      return 'blue';
    case 'delivered':
      return 'green';
    case 'read':
      return 'positive';
    case 'failed':
      return 'negative';
    default:
      return 'grey';
  }
};

const getMediaColor = (mediaType: string) => {
  switch (mediaType.toLowerCase()) {
    case 'image':
      return 'purple';
    case 'video':
      return 'deep-purple';
    case 'audio':
      return 'indigo';
    case 'document':
      return 'blue';
    default:
      return 'grey';
  }
};

const previewMedia = (url: string, type: string) => {
  mediaUrl.value = url;
  mediaType.value = type;
  mediaPreviewOpen.value = true;
};

const downloadDocument = (url: string) => {
  window.open(url, '_blank');
};

// Event handlers
const onRequest = (props: { pagination: any }) => {
  const { page, rowsPerPage } = props.pagination;
  whatsAppStore.setTemplateHistoryCurrentPage(page);
  whatsAppStore.setTemplateHistoryPageSize(rowsPerPage);
};

const reuseTemplate = (template: WhatsAppTemplateHistory) => {
  $q.notify({
    message: `Réutilisation du template "${template.templateName}" initiée`,
    color: 'primary'
  });
  
  // Émettre un événement pour ouvrir le dialogue de réutilisation du template
  // On pourrait aussi utiliser un event bus ou un mécanisme d'événements personnalisé
  document.dispatchEvent(new CustomEvent('reuse-whatsapp-template', { 
    detail: {
      templateName: template.templateName,
      language: template.language,
      recipient: template.recipient,
      bodyVariables: template.bodyVariables,
      headerMediaType: template.headerMediaType,
      headerMediaUrl: template.headerMediaUrl,
      headerMediaId: template.headerMediaId
    }
  }));
};

// Watches
watch(currentPage, (newPage) => {
  whatsAppStore.setTemplateHistoryCurrentPage(newPage);
});

watch(pageSize, (newSize) => {
  whatsAppStore.setTemplateHistoryPageSize(newSize);
});

// Chargement des données
onMounted(async () => {
  await whatsAppStore.fetchTemplateHistory();
  await whatsAppStore.fetchMostUsedTemplates();
  await whatsAppStore.fetchCommonParameterValues();
});
</script>

<style scoped>
.template-history-container {
  max-width: 100%;
  overflow-x: auto;
}
</style>