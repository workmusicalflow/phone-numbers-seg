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

    <!-- Barre d'outils avec Filtres -->
    <div class="row q-col-gutter-md q-mb-md">
       <div class="col-12 col-sm-5">
         <q-input
           v-model="searchTermModel"
           label="Rechercher nom/numéro..."
           dense
           clearable
           debounce="300"
         >
           <template v-slot:append>
             <q-icon name="search" />
           </template>
         </q-input>
       </div>
       <div class="col-12 col-sm-4">
         <q-select
           v-model="selectedGroupIdModel"
           :options="groupOptions"
           label="Filtrer par groupe"
           dense
           clearable
           emit-value
           map-options
           :loading="contactGroupStore.isLoading" 
         />
       </div>
       <div class="col-12 col-sm-3">
         <q-btn
           color="primary"
           icon="add"
           label="Ajouter Contact"
           @click="openContactDialog()"
           class="full-width"
         />
       </div>
     </div>


    <!-- Tableau des contacts -->
    <ContactTable
      :contacts="contactStore.contacts"
      :loading="contactStore.loading"
      :pagination="{ ...pagination, rowsNumber: contactStore.totalCount }" 
      @request="onRequest"
      @edit="openContactDialog"
      @delete="confirmDelete"
      @send-sms="sendSMS"
    />

    <!-- Pagination -->
    <div class="row justify-center q-mt-md">
      <BasePagination
        :total-items="contactStore.totalCount"
        :items-per-page="pagination.rowsPerPage"
        :initial-page="currentPage"
        @page-change="onPageChange"
        @items-per-page-change="onItemsPerPageChange"
      />
    </div>

    <!-- Dialog pour créer/modifier un contact -->
    <ContactFormDialog
      v-model="contactDialog"
      :contact="selectedContact"
      :groups="contactGroupStore.groupsForSelect" 
      :loading="saving"
      @save="saveContact"
      @cancel="contactDialog = false"
    />

    <!-- Dialog de confirmation de suppression -->
    <ConfirmationDialog
      v-model="deleteDialog"
      message="Êtes-vous sûr de vouloir supprimer ce contact?"
      icon="warning"
      color="negative"
      confirm-label="Supprimer"
      cancel-label="Annuler"
      confirm-color="negative"
      :loading="deleting"
      @confirm="deleteContact"
      @cancel="deleteDialog = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import { useContactStore } from '../stores/contactStore';
import { useContactGroupStore } from '../stores/contactGroupStore';
import { Contact, ContactFormData, ContactCreateData } from '../types/contact';

// Composants
import ContactCountBadge from '../components/common/ContactCountBadge.vue';
import BasePagination from '../components/BasePagination.vue';
import ContactTable from '../components/contacts/ContactTable.vue';
import ContactFormDialog from '../components/contacts/ContactFormDialog.vue';
import ConfirmationDialog from '../components/common/ConfirmationDialog.vue';
// import ContactsToolbar from '../components/contacts/ContactsToolbar.vue'; // Removed unused import

// Router et Quasar
const router = useRouter();
const $q = useQuasar();

// Stores
const contactStore = useContactStore();
const contactGroupStore = useContactGroupStore();

// État local
const contactDialog = ref(false);
const deleteDialog = ref(false);
const saving = ref(false);
const deleting = ref(false);
const currentPage = ref(1);
const selectedContact = ref<Contact | null>(null);
const contactToDelete = ref<Contact | null>(null);
const contactsCount = ref(0);

// Fonction pour rafraîchir le nombre de contacts
const refreshContactsCount = async () => {
  contactsCount.value = await contactStore.fetchContactsCount();
};

// Pagination & Filtres
const pagination = ref({
  sortBy: 'lastName', // Default sort
  descending: false,
  page: 1,
  rowsPerPage: 10,
  // rowsNumber will be dynamically updated via watchEffect or similar
});

// Computed property for search term model
const searchTermModel = computed({
  get: () => contactStore.searchTerm, // Corrected: use searchTerm
  set: (value) => {
    // Trigger search action in the store
    contactStore.searchContacts(value || ''); // Ensure empty string if null/undefined
  }
});

// Computed property for selected group ID model
const selectedGroupIdModel = computed({
  get: () => contactStore.currentGroupId,
  set: (value) => {
    // Trigger filter action in the store
    contactStore.filterByGroup(value);
  }
});

// Computed property for group options for the select dropdown
const groupOptions = computed(() => {
  // Map groups to the format required by q-select: { label: string, value: number | null }
  // Add an option for "All Groups"
  return [
    { label: 'Tous les groupes', value: null },
    ...contactGroupStore.groupsForSelect
  ];
});


// Méthodes

function onPageChange(page: number) {
  currentPage.value = page;
  contactStore.setPage(page);
}

function onItemsPerPageChange(itemsPerPage: number) {
  pagination.value.rowsPerPage = itemsPerPage;
  contactStore.setItemsPerPage(itemsPerPage);
  // Retourner à la première page lors du changement d'éléments par page
  currentPage.value = 1;
  contactStore.setPage(1);
}

// QTable @request handler
function onRequest(paginationPayload: { page: number; rowsPerPage: number; sortBy: string; descending: boolean }) {
  const { page, rowsPerPage, sortBy, descending } = paginationPayload;

  // Update local pagination state used by QTable
  pagination.value.page = page;
  pagination.value.rowsPerPage = rowsPerPage;
  pagination.value.sortBy = sortBy;
  pagination.value.descending = descending;

  // Update store state which triggers fetchContacts with current filters AND new pagination/sorting
  contactStore.setPage(page);
  contactStore.setItemsPerPage(rowsPerPage);
  contactStore.setSorting(sortBy, descending);
  // fetchContacts is automatically called by the store actions
}


function openContactDialog(contact?: Contact) {
  selectedContact.value = contact || null;
  contactDialog.value = true;
}

async function saveContact(formData: ContactFormData) {
  saving.value = true;
  try {
    // Prepare data using the 'name' field
    const contactData: ContactCreateData = {
      name: formData.name, // Use name field
      phoneNumber: formData.phoneNumber,
      email: formData.email || null,
      groups: formData.groups.map(id => String(id)), // Convertir en string[]
      notes: formData.notes || null
    };

    if (selectedContact.value) {
      // Mode édition
      await contactStore.updateContact(formData.id, contactData);
      $q.notify({
        color: 'positive',
        message: 'Contact mis à jour avec succès',
        icon: 'check_circle',
        position: 'top'
      });
    } else {
      // Mode création
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

function confirmDelete(contact: Contact) {
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

function sendSMS(contact: Contact) {
  router.push({
    path: '/sms',
    query: {
      recipient: contact.phoneNumber,
      name: contact.name // Use name field
    }
  });
}

// Cycle de vie
onMounted(async () => {
  // Fetch initial data including groups for the filter dropdown
  await contactGroupStore.fetchContactGroups(); // Fetch groups first
  await contactStore.fetchContacts(); // Fetch contacts using internal store state
  // Récupérer le nombre de contacts initial
  contactsCount.value = contactStore.totalCount; // Use count from store state after fetch
});

// Surveiller les changements dans le store pour mettre à jour la pagination locale et le compte total
watch(() => contactStore.currentPage, (newPage) => {
  currentPage.value = newPage;
  pagination.value.page = newPage; // Keep local pagination in sync
});

watch(() => contactStore.totalCount, (newCount) => {
  // Update the total rows number for QTable pagination
  // QTable uses this value internally, no need for a separate computed property in pagination ref
  // This ensures the pagination component reflects the total number of items correctly
  // pagination.value.rowsNumber = newCount; // QTable handles this internally based on the prop passed to it
  contactsCount.value = newCount; // Update the badge as well
});

// Watch for changes in items per page from the BasePagination component
watch(() => pagination.value.rowsPerPage, (newItemsPerPage) => {
    if (newItemsPerPage !== contactStore.itemsPerPage) {
        contactStore.setItemsPerPage(newItemsPerPage);
    }
});

</script>

<style scoped>
.contacts-container {
  padding: 16px;
}

@media (max-width: 600px) {
  .contacts-container {
    padding: 8px;
  }
}
</style>
