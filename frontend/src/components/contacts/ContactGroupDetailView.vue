<template>
  <div>
    <div v-if="!groupId" class="text-center q-pa-md">
      <p class="text-h6">Sélectionnez un groupe pour voir ses détails</p>
    </div>

    <div v-else>
      <div v-if="store.isLoading" class="text-center q-pa-md">
        <q-spinner-dots color="primary" size="40px" />
      </div>

      <div v-else-if="!store.currentGroup" class="text-center q-pa-md">
        <p class="text-h6">Groupe non trouvé</p>
      </div>

      <div v-else>
        <!-- Group header -->
        <div class="row items-center q-mb-md">
          <div class="col">
            <div class="text-h5">
              {{ store.currentGroup.name }}
              <contact-count-badge 
                :count="store.currentGroup.contactCount" 
                color="primary" 
                icon="contacts"
                :tooltip-text="`${store.currentGroup.contactCount} contact${store.currentGroup.contactCount !== 1 ? 's' : ''} dans ce groupe`"
                class="q-ml-sm"
              />
            </div>
            <div v-if="store.currentGroup.description" class="text-subtitle2">
              {{ store.currentGroup.description }}
            </div>
          </div>
          <div class="col-auto">
            <q-btn
              flat
              round
              color="primary"
              icon="edit"
              @click="onEditGroup"
            >
              <q-tooltip>Modifier le groupe</q-tooltip>
            </q-btn>
            <q-btn
              flat
              round
              color="negative"
              icon="delete"
              @click="confirmDeleteGroup"
            >
              <q-tooltip>Supprimer le groupe</q-tooltip>
            </q-btn>
          </div>
        </div>

        <!-- Contacts in group section -->
        <q-card flat bordered>
          <q-card-section class="row items-center">
            <div class="text-h6">
              Contacts dans ce groupe
              <contact-count-badge 
                :count="store.totalContactsInGroup" 
                color="secondary" 
                icon="people"
                class="q-ml-sm"
              />
            </div>
            <q-space />
            <q-btn
              color="primary"
              icon="add"
              label="Ajouter des contacts"
              @click="onAddContacts"
            />
          </q-card-section>

          <q-separator />

          <!-- Loading state for contacts -->
          <q-card-section v-if="store.isLoadingContacts" class="text-center">
            <q-spinner-dots color="primary" size="40px" />
          </q-card-section>

          <!-- Empty state -->
          <q-card-section v-else-if="store.currentGroupContacts.length === 0" class="text-center">
            <p>Aucun contact dans ce groupe</p>
          </q-card-section>

          <!-- Contacts list -->
          <q-list v-else separator>
            <q-item v-for="contact in store.currentGroupContacts" :key="contact.id">
              <q-item-section>
                <q-item-label>{{ contact.name }}</q-item-label>
                <q-item-label caption>{{ contact.phoneNumber }}</q-item-label>
                <q-item-label caption v-if="contact.email">{{ contact.email }}</q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-btn
                  flat
                  round
                  dense
                  color="negative"
                  icon="remove_circle_outline"
                  @click="confirmRemoveContact(contact)"
                >
                  <q-tooltip>Retirer du groupe</q-tooltip>
                </q-btn>
              </q-item-section>
            </q-item>
          </q-list>

          <!-- Pagination if needed -->
          <q-card-section v-if="store.totalContactsInGroup > 0">
            <div class="row justify-center">
              <q-pagination
                v-model="currentPage"
                :max="Math.ceil(store.totalContactsInGroup / pageSize)"
                :max-pages="6"
                boundary-links
                direction-links
                @update:model-value="onPageChange"
              />
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Dialog for editing group -->
    <contact-group-form-dialog
      v-model="showEditDialog"
      :group="store.currentGroup"
      @group-saved="onGroupUpdated"
    />

    <!-- Dialog for adding contacts to group -->
    <add-contacts-to-group-dialog
      v-model="showAddContactsDialog"
      :group-id="groupId || ''"
      :group-name="store.currentGroup?.name || ''"
      @contacts-added="onContactsAdded"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { useQuasar } from 'quasar';
import { useContactGroupStore } from '@/stores/contactGroupStore';
import ContactGroupFormDialog from './ContactGroupFormDialog.vue';
import AddContactsToGroupDialog from './AddContactsToGroupDialog.vue';
import ContactCountBadge from '../common/ContactCountBadge.vue';
import type { Contact } from '@/types/contact';

const props = defineProps<{
  groupId: string | null;
}>();

const emit = defineEmits<{
  (e: 'group-deleted'): void;
}>();

const $q = useQuasar();
const store = useContactGroupStore();
const showEditDialog = ref(false);
const showAddContactsDialog = ref(false);
const currentPage = ref(1);
const pageSize = 10; // Number of contacts per page

// Watch for changes in groupId prop
watch(
  () => props.groupId,
  (newGroupId) => {
    if (newGroupId) {
      loadGroupDetails(newGroupId);
    } else {
      // Clear current group data if no group is selected
      store.currentGroup = null;
      store.currentGroupContacts = [];
      store.totalContactsInGroup = 0;
    }
  },
  { immediate: true }
);

// Load group details and its contacts
async function loadGroupDetails(groupId: string) {
  currentPage.value = 1; // Reset to first page
  await store.fetchContactGroupById(groupId);
  if (store.currentGroup) {
    await loadContacts();
  }
}

// Load contacts for the current group with pagination
async function loadContacts() {
  if (!props.groupId) return;
  
  const offset = (currentPage.value - 1) * pageSize;
  await store.fetchContactsInGroup(props.groupId, pageSize, offset);
}

// Handle page change in pagination
function onPageChange() {
  loadContacts();
}

// Edit the current group
function onEditGroup() {
  showEditDialog.value = true;
}

// Handle group update
function onGroupUpdated() {
  if (props.groupId) {
    loadGroupDetails(props.groupId);
  }
}

// Confirm and delete the current group
function confirmDeleteGroup() {
  if (!store.currentGroup) return;

  $q.dialog({
    title: 'Confirmer la suppression',
    message: `Êtes-vous sûr de vouloir supprimer le groupe "${store.currentGroup.name}" ?`,
    cancel: true,
    persistent: true
  }).onOk(async () => {
    if (!props.groupId) return;
    
    try {
      const success = await store.deleteContactGroup(props.groupId);
      if (success) {
        emit('group-deleted');
      }
    } catch (error) {
      console.error('Error deleting group:', error);
    }
  });
}

// Confirm and remove a contact from the group
function confirmRemoveContact(contact: Contact) {
  $q.dialog({
    title: 'Confirmer le retrait',
    message: `Êtes-vous sûr de vouloir retirer "${contact.name}" de ce groupe ?`,
    cancel: true,
    persistent: true
  }).onOk(async () => {
    if (!props.groupId) return;
    
    try {
      const success = await store.removeContactFromGroup(contact.id, props.groupId);
      if (success) {
        // Reload contacts to reflect the change
        loadContacts();
      }
    } catch (error) {
      console.error('Error removing contact from group:', error);
    }
  });
}

// Open dialog for adding contacts to the group
function onAddContacts() {
  showAddContactsDialog.value = true;
}

// Handle contacts added to the group
function onContactsAdded(count: number) {
  // Reload contacts to reflect the changes
  loadContacts();
}

// Load initial data if groupId is provided
onMounted(() => {
  if (props.groupId) {
    loadGroupDetails(props.groupId);
  }
});
</script>
