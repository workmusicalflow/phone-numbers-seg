<template>
  <div class="contacts-container">
    <div class="row items-center q-mb-md">
      <h1 class="text-h4 q-my-none">Gestion des Contacts</h1>
      <q-space />
      <!-- Badge nombre de contacts -->
      <ContactCountBadge
        :count="contactsCount"
        color="primary"
        icon="contacts"
        tooltipText="Nombre total de contacts disponibles."
      />
    </div>

    <!-- Barre d'outils -->
    <div class="row q-mb-md justify-between items-center">
      <div class="col-12 col-md-6 q-mb-sm-md">
        <q-input
          v-model="searchQuery"
          outlined
          dense
          placeholder="Rechercher un contact..."
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
          label="Nouveau contact"
          @click="openContactDialog()"
          class="q-ml-sm"
        />
      </div>
    </div>

    <!-- Tableau des contacts -->
    <q-table
      :rows="contactStore.filteredContacts"
      :columns="columns"
      row-key="id"
      :loading="contactStore.loading"
      @request="onRequest"
      :rows-per-page-options="[5, 10, 20, 50]"
      flat
      bordered
      class="contacts-table"
      v-model:pagination="pagination"
      :filter="searchQuery"
    >
      <!-- Slot pour le contenu -->
      <template v-slot:body="props">
        <q-tr :props="props">
          <q-td key="name" :props="props">
            {{ props.row.firstName }} {{ props.row.lastName }}
          </q-td>
          <q-td key="phone" :props="props">
            {{ props.row.phoneNumber }}
          </q-td>
          <q-td key="email" :props="props">
            {{ props.row.email }}
          </q-td>
          <q-td key="groups" :props="props">
            <q-chip
              v-for="group in props.row.groups"
              :key="group.id"
              size="sm"
              color="secondary"
              text-color="white"
              class="q-ma-xs"
            >
              {{ group.name }}
            </q-chip>
          </q-td>
          <q-td key="actions" :props="props">
            <div class="row no-wrap justify-end">
              <q-btn
                flat
                round
                color="primary"
                icon="edit"
                @click="openContactDialog(props.row)"
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
                @click="sendSMS(props.row)"
                size="sm"
              >
                <q-tooltip>Envoyer SMS</q-tooltip>
              </q-btn>
            </div>
          </q-td>
        </q-tr>
      </template>

      <!-- Slot pour l'état vide -->
      <template v-slot:no-data>
        <div class="full-width row flex-center q-pa-md text-grey-8">
          <q-icon name="sentiment_dissatisfied" size="2em" class="q-mr-sm" />
          <span>Aucun contact trouvé</span>
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

    <!-- Dialog pour créer/modifier un contact -->
    <q-dialog v-model="contactDialog" persistent>
      <q-card style="min-width: 500px">
        <q-card-section class="row items-center">
          <div class="text-h6">{{ isEditing ? 'Modifier le contact' : 'Nouveau contact' }}</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section>
          <q-form @submit="saveContact" class="q-gutter-md">
            <div class="row q-col-gutter-md">
              <div class="col-12 col-md-6">
                <q-input
                  v-model="contactForm.firstName"
                  label="Prénom *"
                  outlined
                  :rules="[val => !!val || 'Le prénom est obligatoire']"
                />
              </div>
              <div class="col-12 col-md-6">
                <q-input
                  v-model="contactForm.lastName"
                  label="Nom *"
                  outlined
                  :rules="[val => !!val || 'Le nom est obligatoire']"
                />
              </div>
            </div>

            <q-input
              v-model="contactForm.phoneNumber"
              label="Numéro de téléphone *"
              outlined
              :rules="[
                val => !!val || 'Le numéro de téléphone est obligatoire',
                val => /^\+?[0-9]{8,15}$/.test(val) || 'Format de numéro invalide'
              ]"
            />

            <q-input
              v-model="contactForm.email"
              label="Email"
              type="email"
              outlined
              :rules="[
                val => !val || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val) || 'Format d\'email invalide'
              ]"
            />

            <q-select
              v-model="contactForm.groups"
              :options="contactGroupStore.groups"
              label="Groupes"
              outlined
              multiple
              use-chips
              option-value="id"
              option-label="name"
              emit-value
              map-options
            />

            <q-input
              v-model="contactForm.notes"
              label="Notes"
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
          <span class="q-ml-sm">Êtes-vous sûr de vouloir supprimer ce contact?</span>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Annuler" color="primary" v-close-popup />
          <q-btn
            flat
            label="Supprimer"
            color="negative"
            @click="deleteContact"
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
import { useContactStore } from '../stores/contactStore';
import { useContactGroupStore } from '../stores/contactGroupStore';
import ContactCountBadge from '../components/common/ContactCountBadge.vue';

// Router et Quasar
const router = useRouter();
const $q = useQuasar();

// Stores
const contactStore = useContactStore();
const contactGroupStore = useContactGroupStore();

// État local
const searchQuery = ref('');
const contactDialog = ref(false);
const deleteDialog = ref(false);
const saving = ref(false);
const deleting = ref(false);
const currentPage = ref(1);
const contactToDelete = ref<any>(null);
const isEditing = ref(false);
const contactsCount = ref(0);

// Fonction pour rafraîchir le nombre de contacts
const refreshContactsCount = async () => {
  contactsCount.value = await contactStore.fetchContactsCount();
};

// Formulaire
const contactForm = ref({
  id: '',
  firstName: '',
  lastName: '',
  phoneNumber: '',
  email: '',
  groups: [] as number[],
  notes: ''
});

// Pagination
const pagination = ref({
  sortBy: 'lastName',
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
    align: 'left' as const,
    field: (row: any) => `${row.firstName} ${row.lastName}`,
    sortable: true
  },
  {
    name: 'phone',
    required: true,
    label: 'Téléphone',
    align: 'left' as const,
    field: 'phoneNumber',
    sortable: true
  },
  {
    name: 'email',
    required: false,
    label: 'Email',
    align: 'left' as const,
    field: 'email',
    sortable: true
  },
  {
    name: 'groups',
    required: false,
    label: 'Groupes',
    align: 'left' as const,
    field: 'groups',
    sortable: false
  },
  {
    name: 'actions',
    required: true,
    label: 'Actions',
    align: 'right' as const,
    field: 'actions',
    sortable: false
  }
];

// Calculs
const totalPages = computed(() => {
  return Math.ceil(contactStore.totalCount / pagination.value.rowsPerPage);
});

// Méthodes
function onSearch(value: string) {
  if (value.length > 2 || value.length === 0) {
    contactStore.searchContacts(value);
  }
}

function onPageChange(page: number) {
  currentPage.value = page;
  contactStore.setPage(page);
}

function onRequest(props: any) {
  const { page, rowsPerPage } = props.pagination;
  pagination.value.page = page;
  pagination.value.rowsPerPage = rowsPerPage;
  contactStore.setPage(page);
  contactStore.setItemsPerPage(rowsPerPage);
}

function openContactDialog(contact?: any) {
  if (contact) {
    // Mode édition
    contactForm.value = {
      id: contact.id,
      firstName: contact.firstName,
      lastName: contact.lastName,
      phoneNumber: contact.phoneNumber,
      email: contact.email || '',
      groups: contact.groups?.map((g: any) => g.id) || [],
      notes: contact.notes || ''
    };
    isEditing.value = true;
  } else {
    // Mode création
    contactForm.value = {
      id: '',
      firstName: '',
      lastName: '',
      phoneNumber: '',
      email: '',
      groups: [],
      notes: ''
    };
    isEditing.value = false;
  }
  contactDialog.value = true;
}

async function saveContact() {
  saving.value = true;
  try {
    const contactData = {
      firstName: contactForm.value.firstName,
      lastName: contactForm.value.lastName,
      phoneNumber: contactForm.value.phoneNumber,
      email: contactForm.value.email || null,
      groups: contactForm.value.groups.map(id => id.toString()), // Convertir number[] en string[]
      notes: contactForm.value.notes || null
    };

    if (isEditing.value) {
      await contactStore.updateContact(contactForm.value.id, contactData);
      $q.notify({
        color: 'positive',
        message: 'Contact mis à jour avec succès',
        icon: 'check_circle',
        position: 'top'
      });
    } else {
      await contactStore.createContact(contactData);
      $q.notify({
        color: 'positive',
        message: 'Contact créé avec succès',
        icon: 'check_circle',
        position: 'top'
      });
    }
    contactDialog.value = false;
    // Rafraîchir le nombre de contacts
    refreshContactsCount();
  } catch (error) {
    console.error('Erreur lors de la sauvegarde du contact:', error);
    $q.notify({
      color: 'negative',
      message: 'Erreur lors de la sauvegarde du contact',
      icon: 'error',
      position: 'top'
    });
  } finally {
    saving.value = false;
  }
}

function confirmDelete(contact: any) {
  contactToDelete.value = contact;
  deleteDialog.value = true;
}

async function deleteContact() {
  if (!contactToDelete.value) return;
  
  deleting.value = true;
  try {
    await contactStore.deleteContact(contactToDelete.value.id);
    $q.notify({
      color: 'positive',
      message: 'Contact supprimé avec succès',
      icon: 'check_circle',
      position: 'top'
    });
    deleteDialog.value = false;
    // Rafraîchir le nombre de contacts
    refreshContactsCount();
  } catch (error) {
    console.error('Erreur lors de la suppression du contact:', error);
    $q.notify({
      color: 'negative',
      message: 'Erreur lors de la suppression du contact',
      icon: 'error',
      position: 'top'
    });
  } finally {
    deleting.value = false;
  }
}

function sendSMS(contact: any) {
  router.push({
    path: '/sms',
    query: { 
      recipient: contact.phoneNumber,
      name: `${contact.firstName} ${contact.lastName}`
    }
  });
}

// Cycle de vie
onMounted(async () => {
  await contactStore.fetchContacts();
  await contactGroupStore.fetchGroups();
  // Récupérer le nombre de contacts
  contactsCount.value = await contactStore.fetchContactsCount();
});

// Surveiller les changements de pagination
watch(() => contactStore.currentPage, (newPage) => {
  currentPage.value = newPage;
  pagination.value.page = newPage;
});

watch(() => contactStore.totalCount, (newCount) => {
  pagination.value.rowsNumber = newCount;
});
</script>

<style scoped>
.contacts-container {
  padding: 16px;
}

.search-input {
  max-width: 400px;
}

@media (max-width: 600px) {
  .contacts-table {
    max-height: calc(100vh - 200px);
    overflow-y: auto;
  }
}
</style>
