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
    <ContactsToolbar
      :search-query="searchQuery"
      @search="onSearch"
      @add="openContactDialog"
    />

    <!-- Tableau des contacts -->
    <ContactTable
      :contacts="contactStore.filteredContacts"
      :loading="contactStore.loading"
      :pagination="pagination"
      :filter="searchQuery"
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
      :groups="contactGroupStore.groups"
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
import ContactsToolbar from '../components/contacts/ContactsToolbar.vue';

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
const selectedContact = ref<Contact | null>(null);
const contactToDelete = ref<Contact | null>(null);
const contactsCount = ref(0);

// Fonction pour rafraîchir le nombre de contacts
const refreshContactsCount = async () => {
  contactsCount.value = await contactStore.fetchContactsCount();
};

// Pagination
const pagination = ref({
  sortBy: 'lastName',
  descending: false,
  page: 1,
  rowsPerPage: 10,
  rowsNumber: 0
});

// Méthodes
function onSearch(value: string) {
  searchQuery.value = value;
  if (value.length > 2 || value.length === 0) {
    contactStore.searchContacts(value);
  }
}

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

function onRequest(paginationData: any) {
  const { page, rowsPerPage } = paginationData;
  pagination.value.page = page;
  pagination.value.rowsPerPage = rowsPerPage;
  contactStore.setPage(page);
  contactStore.setItemsPerPage(rowsPerPage);
}

function openContactDialog(contact?: Contact) {
  selectedContact.value = contact || null;
  contactDialog.value = true;
}

async function saveContact(formData: ContactFormData) {
  saving.value = true;
  try {
    const contactData: ContactCreateData = {
      firstName: formData.firstName,
      lastName: formData.lastName,
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

@media (max-width: 600px) {
  .contacts-container {
    padding: 8px;
  }
}
</style>
