<template>
  <div class="sms-templates-container">
    <h1 class="text-h4 q-mb-md">Modèles de SMS</h1>

    <!-- Barre d'outils -->
    <div class="row q-mb-md justify-between items-center">
      <div class="col-12 col-md-6 q-mb-sm-md">
        <q-input
          v-model="searchQuery"
          outlined
          dense
          placeholder="Rechercher un modèle..."
          @input="onSearch"
          class="search-input"
        >
          <template v-slot:append>
            <q-icon name="search" />
          </template>
        </q-input>
      </div>
      <div class="col-12 col-md-6 text-right">
        <q-btn
          color="primary"
          icon="add"
          label="Nouveau modèle"
          @click="openTemplateDialog()"
          class="q-ml-sm"
        />
      </div>
    </div>

    <!-- Tableau des modèles -->
    <q-table
      :rows="smsTemplateStore.paginatedTemplates"
      :columns="columns"
      row-key="id"
      :loading="smsTemplateStore.loading"
      :pagination="pagination"
      @request="onRequest"
      :rows-per-page-options="[5, 10, 20, 50]"
      flat
      bordered
      class="templates-table"
      v-model:pagination="pagination"
      :filter="searchQuery"
    >
      <!-- Slot pour le contenu -->
      <template v-slot:body="props">
        <q-tr :props="props">
          <q-td key="title" :props="props">
            {{ props.row.title }}
          </q-td>
          <q-td key="content" :props="props">
            <div class="content-preview">{{ props.row.content }}</div>
          </q-td>
          <q-td key="variables" :props="props">
            <q-chip
              v-for="variable in props.row.variables"
              :key="variable"
              size="sm"
              color="secondary"
              text-color="white"
              class="q-ma-xs"
            >
              {{ variable }}
            </q-chip>
          </q-td>
          <q-td key="actions" :props="props">
            <div class="row no-wrap justify-end">
              <q-btn
                flat
                round
                color="primary"
                icon="edit"
                @click="openTemplateDialog(props.row)"
                size="sm"
              >
                <q-tooltip>Modifier</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                color="negative"
                icon="delete"
                @click="confirmDelete(props.row)"
                size="sm"
              >
                <q-tooltip>Supprimer</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                color="secondary"
                icon="content_copy"
                @click="copyToClipboard(props.row.content)"
                size="sm"
              >
                <q-tooltip>Copier le contenu</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                color="primary"
                icon="send"
                @click="useTemplate(props.row)"
                size="sm"
              >
                <q-tooltip>Utiliser ce modèle</q-tooltip>
              </q-btn>
            </div>
          </q-td>
        </q-tr>
      </template>

      <!-- Slot pour l'état vide -->
      <template v-slot:no-data>
        <div class="full-width row flex-center q-pa-md text-grey-8">
          <q-icon name="sentiment_dissatisfied" size="2em" class="q-mr-sm" />
          <span>Aucun modèle de SMS trouvé</span>
        </div>
      </template>
    </q-table>

    <!-- Pagination -->
    <div class="row justify-center q-mt-md">
      <q-pagination
        v-model="currentPage"
        :max="smsTemplateStore.pageCount"
        direction-links
        boundary-links
        @update:model-value="onPageChange"
      />
    </div>

    <!-- Dialog pour créer/modifier un modèle -->
    <q-dialog v-model="templateDialog" persistent>
      <q-card style="min-width: 500px">
        <q-card-section class="row items-center">
          <div class="text-h6">{{ isEditing ? 'Modifier le modèle' : 'Nouveau modèle' }}</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section>
          <q-form @submit="saveTemplate" class="q-gutter-md">
            <q-input
              v-model="templateForm.title"
              label="Titre *"
              outlined
              :rules="[val => !!val || 'Le titre est obligatoire']"
            />

            <q-input
              v-model="templateForm.content"
              label="Contenu *"
              type="textarea"
              outlined
              autogrow
              :rules="[
                val => !!val || 'Le contenu est obligatoire',
                val => val.length <= 1000 || 'Le contenu ne doit pas dépasser 1000 caractères'
              ]"
            >
              <template v-slot:hint>
                <div class="row justify-between">
                  <span>Utilisez &#123;&#123;variable&#125;&#125; pour les variables dynamiques</span>
                  <span>{{ templateForm.content.length }}/1000</span>
                </div>
              </template>
            </q-input>

            <q-input
              v-model="templateForm.description"
              label="Description"
              type="textarea"
              outlined
              autogrow
              :rules="[
                val => !val || val.length <= 500 || 'La description ne doit pas dépasser 500 caractères'
              ]"
            />

            <div class="q-mt-md">
              <div class="text-subtitle2 q-mb-sm">Variables détectées:</div>
              <div v-if="detectedVariables.length > 0">
                <q-chip
                  v-for="variable in detectedVariables"
                  :key="variable"
                  color="secondary"
                  text-color="white"
                  class="q-ma-xs"
                >
                  {{ variable }}
                </q-chip>
              </div>
              <div v-else class="text-grey-8">
                Aucune variable détectée
              </div>
            </div>

            <div class="row justify-end q-mt-md">
              <q-btn label="Annuler" color="grey-7" v-close-popup class="q-mr-sm" />
              <q-btn label="Enregistrer" type="submit" color="primary" :loading="saving" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Dialog de confirmation de suppression -->
    <q-dialog v-model="deleteDialog" persistent>
      <q-card>
        <q-card-section class="row items-center">
          <q-avatar icon="warning" color="negative" text-color="white" />
          <span class="q-ml-sm">Êtes-vous sûr de vouloir supprimer ce modèle?</span>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Annuler" color="primary" v-close-popup />
          <q-btn
            flat
            label="Supprimer"
            color="negative"
            @click="deleteTemplate"
            :loading="deleting"
            v-close-popup
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useQuasar } from 'quasar';
import { useSMSTemplateStore } from '../stores/smsTemplateStore';

// Quasar
const $q = useQuasar();

// Store
const smsTemplateStore = useSMSTemplateStore();

// État local
const searchQuery = ref('');
const templateDialog = ref(false);
const deleteDialog = ref(false);
const saving = ref(false);
const deleting = ref(false);
const currentPage = ref(1);
const templateToDelete = ref<any>(null);
const isEditing = ref(false);

// Formulaire
const templateForm = ref({
  id: '',
  title: '',
  content: '',
  description: ''
});

// Pagination
const pagination = ref({
  sortBy: 'title',
  descending: false,
  page: 1,
  rowsPerPage: 10,
  rowsNumber: 0
});

// Colonnes du tableau
const columns = [
  {
    name: 'title',
    required: true,
    label: 'Titre',
    align: 'left' as 'left',
    field: 'title',
    sortable: true
  },
  {
    name: 'content',
    required: true,
    label: 'Contenu',
    align: 'left' as 'left',
    field: 'content',
    sortable: false
  },
  {
    name: 'variables',
    required: false,
    label: 'Variables',
    align: 'left' as 'left',
    field: 'variables',
    sortable: false
  },
  {
    name: 'actions',
    required: true,
    label: 'Actions',
    align: 'right' as 'right',
    field: 'actions',
    sortable: false
  }
];

// Variables détectées dans le contenu
const detectedVariables = computed(() => {
  const regex = /\{\{([^}]+)\}\}/g;
  const content = templateForm.value.content;
  const matches = [];
  let match;

  while ((match = regex.exec(content)) !== null) {
    matches.push(match[1].trim());
  }

  return [...new Set(matches)]; // Éliminer les doublons
});

// Méthodes
function onSearch(value: string) {
  if (value.length > 2 || value.length === 0) {
    smsTemplateStore.searchTemplates(value);
  }
}

function onPageChange(page: number) {
  smsTemplateStore.setPage(page);
}

function onRequest(props: any) {
  const { page, rowsPerPage } = props.pagination;
  smsTemplateStore.setPage(page);
  smsTemplateStore.setItemsPerPage(rowsPerPage);
}

function openTemplateDialog(template?: any) {
  if (template) {
    // Mode édition
    templateForm.value = {
      id: template.id,
      title: template.title,
      content: template.content,
      description: template.description || ''
    };
    isEditing.value = true;
  } else {
    // Mode création
    templateForm.value = {
      id: '',
      title: '',
      content: '',
      description: ''
    };
    isEditing.value = false;
  }
  templateDialog.value = true;
}

async function saveTemplate() {
  saving.value = true;
  try {
    const templateData = {
      title: templateForm.value.title,
      content: templateForm.value.content,
      description: templateForm.value.description || null
    };

    if (isEditing.value) {
      await smsTemplateStore.updateTemplate(templateForm.value.id, templateData);
    } else {
      await smsTemplateStore.createTemplate(templateData);
    }
    templateDialog.value = false;
  } catch (error) {
    console.error('Erreur lors de la sauvegarde du modèle:', error);
  } finally {
    saving.value = false;
  }
}

function confirmDelete(template: any) {
  templateToDelete.value = template;
  deleteDialog.value = true;
}

async function deleteTemplate() {
  if (!templateToDelete.value) return;
  
  deleting.value = true;
  try {
    await smsTemplateStore.deleteTemplate(templateToDelete.value.id);
    deleteDialog.value = false;
  } catch (error) {
    console.error('Erreur lors de la suppression du modèle:', error);
  } finally {
    deleting.value = false;
  }
}

function copyToClipboard(text: string) {
  navigator.clipboard.writeText(text).then(() => {
    $q.notify({
      color: 'positive',
      message: 'Contenu copié dans le presse-papiers',
      icon: 'content_copy',
      position: 'top'
    });
  });
}

function useTemplate(template: any) {
  // Sélectionner le modèle dans le store
  smsTemplateStore.selectTemplate(template);
  
  // Rediriger vers la page d'envoi de SMS
  window.location.href = '/sms.html';
}

// Cycle de vie
onMounted(() => {
  smsTemplateStore.init();
});

// Surveiller les changements de pagination
watch(() => smsTemplateStore.currentPage, (newPage) => {
  currentPage.value = newPage;
  pagination.value.page = newPage;
});

watch(() => smsTemplateStore.totalCount, (newCount) => {
  pagination.value.rowsNumber = newCount;
});
</script>

<style scoped>
.sms-templates-container {
  padding: 16px;
}

.content-preview {
  max-width: 300px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.search-input {
  max-width: 400px;
}

@media (max-width: 600px) {
  .templates-table {
    max-height: calc(100vh - 200px);
    overflow-y: auto;
  }
}
</style>
