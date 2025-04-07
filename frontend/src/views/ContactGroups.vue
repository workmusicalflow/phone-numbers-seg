<template>
  <div class="contact-groups-container">
    <h1 class="text-h4 q-mb-md">Gestion des Groupes de Contacts</h1>

    <!-- Barre d'outils -->
    <div class="row q-mb-md justify-between items-center">
      <div class="col-12 col-md-6 q-mb-sm-md">
        <q-input
          v-model="searchQuery"
          outlined
          dense
          placeholder="Rechercher un groupe..."
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
          label="Nouveau groupe"
          @click="openGroupDialog()"
          class="q-ml-sm"
        />
      </div>
    </div>

    <!-- Tableau des groupes -->
    <q-table
      :rows="contactGroupStore.filteredGroups"
      :columns="columns"
      row-key="id"
      :loading="contactGroupStore.loading"
      :pagination="pagination"
      @request="onRequest"
      :rows-per-page-options="[5, 10, 20, 50]"
      flat
      bordered
      class="groups-table"
      v-model:pagination="pagination"
      :filter="searchQuery"
    >
      <!-- Slot pour le contenu -->
      <template v-slot:body="props">
        <q-tr :props="props">
          <q-td key="name" :props="props">
            {{ props.row.name }}
          </q-td>
          <q-td key="description" :props="props">
            {{ props.row.description || '-' }}
          </q-td>
          <q-td key="contactCount" :props="props">
            {{ props.row.contactCount }}
          </q-td>
          <q-td key="actions" :props="props">
            <div class="row no-wrap justify-end">
              <q-btn
                flat
                round
                color="primary"
                icon="edit"
                @click="openGroupDialog(props.row)"
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
                icon="message"
                @click="sendSMSToGroup(props.row)"
                size="sm"
              >
                <q-tooltip>Envoyer SMS au groupe</q-tooltip>
              </q-btn>
            </div>
          </q-td>
        </q-tr>
      </template>

      <!-- Slot pour l'état vide -->
      <template v-slot:no-data>
        <div class="full-width row flex-center q-pa-md text-grey-8">
          <q-icon name="sentiment_dissatisfied" size="2em" class="q-mr-sm" />
          <span>Aucun groupe trouvé</span>
        </div>
      </template>
    </q-table>

    <!-- Pagination -->
    <div class="row justify-center q-mt-md">
      <q-pagination
        v-model="currentPage"
        :max="totalPages"
        direction-links
        boundary-links
        @update:model-value="onPageChange"
      />
    </div>

    <!-- Dialog pour créer/modifier un groupe -->
    <q-dialog v-model="groupDialog" persistent>
      <q-card style="min-width: 500px">
        <q-card-section class="row items-center">
          <div class="text-h6">{{ isEditing ? 'Modifier le groupe' : 'Nouveau groupe' }}</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section>
          <q-form @submit="saveGroup" class="q-gutter-md">
            <q-input
              v-model="groupForm.name"
              label="Nom du groupe *"
              outlined
              :rules="[val => !!val || 'Le nom du groupe est obligatoire']"
            />

            <q-input
              v-model="groupForm.description"
              label="Description"
              type="textarea"
              outlined
              autogrow
            />

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
          <span class="q-ml-sm">Êtes-vous sûr de vouloir supprimer ce groupe?</span>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Annuler" color="primary" v-close-popup />
          <q-btn
            flat
            label="Supprimer"
            color="negative"
            @click="deleteGroup"
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
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import { useContactGroupStore } from '../stores/contactGroupStore';

// Router et Quasar
const router = useRouter();
const $q = useQuasar();

// Stores
const contactGroupStore = useContactGroupStore();

// État local
const searchQuery = ref('');
const groupDialog = ref(false);
const deleteDialog = ref(false);
const saving = ref(false);
const deleting = ref(false);
const currentPage = ref(1);
const groupToDelete = ref<any>(null);
const isEditing = ref(false);

// Formulaire
const groupForm = ref({
  id: '',
  name: '',
  description: ''
});

// Pagination
const pagination = ref({
  sortBy: 'name',
  descending: false,
  page: 1,
  rowsPerPage: 10,
  rowsNumber: 0
});

// Colonnes du tableau
const columns = [
  {
    name: 'name',
    required: true,
    label: 'Nom',
    align: 'left' as 'left',
    field: 'name',
    sortable: true
  },
  {
    name: 'description',
    required: false,
    label: 'Description',
    align: 'left' as 'left',
    field: 'description',
    sortable: true
  },
  {
    name: 'contactCount',
    required: true,
    label: 'Nombre de contacts',
    align: 'center' as 'center',
    field: 'contactCount',
    sortable: true
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

// Calculs
const totalPages = computed(() => {
  return Math.ceil(contactGroupStore.totalCount / pagination.value.rowsPerPage);
});

// Méthodes
function onSearch(value: string) {
  if (value.length > 2 || value.length === 0) {
    contactGroupStore.searchGroups(value);
  }
}

function onPageChange(page: number) {
  currentPage.value = page;
  contactGroupStore.setPage(page);
}

function onRequest(props: any) {
  const { page, rowsPerPage } = props.pagination;
  pagination.value.page = page;
  pagination.value.rowsPerPage = rowsPerPage;
  contactGroupStore.setPage(page);
  contactGroupStore.setItemsPerPage(rowsPerPage);
}

function openGroupDialog(group?: any) {
  if (group) {
    // Mode édition
    groupForm.value = {
      id: group.id,
      name: group.name,
      description: group.description || ''
    };
    isEditing.value = true;
  } else {
    // Mode création
    groupForm.value = {
      id: '',
      name: '',
      description: ''
    };
    isEditing.value = false;
  }
  groupDialog.value = true;
}

async function saveGroup() {
  saving.value = true;
  try {
    const groupData = {
      name: groupForm.value.name,
      description: groupForm.value.description || null
    };

    if (isEditing.value) {
      await contactGroupStore.updateGroup(groupForm.value.id, groupData);
      $q.notify({
        color: 'positive',
        message: 'Groupe mis à jour avec succès',
        icon: 'check_circle',
        position: 'top'
      });
    } else {
      await contactGroupStore.createGroup(groupData);
      $q.notify({
        color: 'positive',
        message: 'Groupe créé avec succès',
        icon: 'check_circle',
        position: 'top'
      });
    }
    groupDialog.value = false;
  } catch (error) {
    console.error('Erreur lors de la sauvegarde du groupe:', error);
    $q.notify({
      color: 'negative',
      message: 'Erreur lors de la sauvegarde du groupe',
      icon: 'error',
      position: 'top'
    });
  } finally {
    saving.value = false;
  }
}

function confirmDelete(group: any) {
  groupToDelete.value = group;
  deleteDialog.value = true;
}

async function deleteGroup() {
  if (!groupToDelete.value) return;
  
  deleting.value = true;
  try {
    await contactGroupStore.deleteGroup(groupToDelete.value.id);
    $q.notify({
      color: 'positive',
      message: 'Groupe supprimé avec succès',
      icon: 'check_circle',
      position: 'top'
    });
    deleteDialog.value = false;
  } catch (error) {
    console.error('Erreur lors de la suppression du groupe:', error);
    $q.notify({
      color: 'negative',
      message: 'Erreur lors de la suppression du groupe',
      icon: 'error',
      position: 'top'
    });
  } finally {
    deleting.value = false;
  }
}

function sendSMSToGroup(group: any) {
  router.push({
    path: '/sms',
    query: { 
      groupId: group.id,
      groupName: group.name
    }
  });
}

// Cycle de vie
onMounted(async () => {
  await contactGroupStore.fetchGroups();
});

// Surveiller les changements de pagination
watch(() => contactGroupStore.currentPage, (newPage) => {
  currentPage.value = newPage;
  pagination.value.page = newPage;
});

watch(() => contactGroupStore.totalCount, (newCount) => {
  pagination.value.rowsNumber = newCount;
});
</script>

<style scoped>
.contact-groups-container {
  padding: 16px;
}

.search-input {
  max-width: 400px;
}

@media (max-width: 600px) {
  .groups-table {
    max-height: calc(100vh - 200px);
    overflow-y: auto;
  }
}
</style>
