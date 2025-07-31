<template>
  <div>
    <q-list bordered separator>
      <q-item-label header>Groupes de Contacts</q-item-label>

      <q-item v-if="store.isLoading" class="justify-center">
        <q-spinner-dots color="primary" size="40px" />
      </q-item>

      <q-item v-else-if="store.error">
        <q-item-section avatar>
          <q-icon color="negative" name="error_outline" />
        </q-item-section>
        <q-item-section>
          <q-item-label>Erreur de chargement</q-item-label>
          <q-item-label caption>{{ store.error.message }}</q-item-label>
        </q-item-section>
      </q-item>

      <q-item v-else-if="store.contactGroups.length === 0">
        <q-item-section>Aucun groupe trouvé.</q-item-section>
      </q-item>

      <q-item
        v-for="group in store.contactGroups"
        :key="group.id"
        clickable
        v-ripple
        :active="selectedGroupId === group.id"
        @click="onGroupSelect(group.id)"
        active-class="bg-blue-1 text-primary"
      >
        <q-item-section avatar>
          <q-icon name="group" />
        </q-item-section>
        <q-item-section>
          <q-item-label>{{ group.name }}</q-item-label>
          <q-item-label caption v-if="group.description">{{ group.description }}</q-item-label>
        </q-item-section>
        <q-item-section side>
          <contact-count-badge 
            :count="group.contactCount" 
            color="primary" 
            icon="contacts"
            :tooltip-text="`${group.contactCount} contact${group.contactCount !== 1 ? 's' : ''} dans ce groupe`"
            :compact="$q.screen.lt.md"
          />
        </q-item-section>
        <q-item-section side>
          <div class="row items-center">
            <q-btn
              flat
              round
              dense
              icon="edit"
              color="primary"
              @click.stop="onEditGroup(group)"
              size="sm"
            >
              <q-tooltip>Modifier le groupe</q-tooltip>
            </q-btn>
            <q-btn
              flat
              round
              dense
              icon="delete"
              color="negative"
              @click.stop="confirmDeleteGroup(group)"
              size="sm"
            >
              <q-tooltip>Supprimer le groupe</q-tooltip>
            </q-btn>
          </div>
        </q-item-section>
      </q-item>

      <q-separator />

      <q-item clickable v-ripple @click="onCreateGroup">
        <q-item-section avatar>
          <q-icon name="add_circle_outline" color="primary" />
        </q-item-section>
        <q-item-section class="text-primary">
          Créer un nouveau groupe
        </q-item-section>
      </q-item>

    </q-list>

    <!-- Dialog for creating/editing groups -->
    <contact-group-form-dialog
      v-model="showFormDialog"
      :group="selectedGroup"
      @group-saved="handleGroupSaved"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useQuasar } from 'quasar';
import { useContactGroupStore } from '@/stores/contactGroupStore';
import ContactGroupFormDialog from './ContactGroupFormDialog.vue';
import ContactCountBadge from '../common/ContactCountBadge.vue';
import type { ContactGroup } from '@/types/contactGroup';

const $q = useQuasar();
const store = useContactGroupStore();
const selectedGroupId = ref<string | null>(null);
const showFormDialog = ref(false);
const selectedGroup = ref<ContactGroup | null>(null);

const emit = defineEmits<{
  (e: 'group-selected', id: string | null): void;
}>();

function onGroupSelect(id: string) {
  selectedGroupId.value = id;
  emit('group-selected', id);
  // Optionally fetch contacts for this group here or in parent component
  // store.fetchContactsInGroup(id);
}

function onCreateGroup() {
  selectedGroup.value = null; // No group = create mode
  showFormDialog.value = true;
}

function onEditGroup(group: ContactGroup) {
  selectedGroup.value = group; // Set group = edit mode
  showFormDialog.value = true;
}

function handleGroupSaved(group: ContactGroup) {
  // Optionally select the new/updated group
  onGroupSelect(group.id);
}

function confirmDeleteGroup(group: ContactGroup) {
  $q.dialog({
    title: 'Confirmer la suppression',
    message: `Êtes-vous sûr de vouloir supprimer le groupe "${group.name}" ?`,
    cancel: true,
    persistent: true
  }).onOk(async () => {
    try {
      const success = await store.deleteContactGroup(group.id);
      if (success) {
        // If the deleted group was selected, clear the selection
        if (selectedGroupId.value === group.id) {
          selectedGroupId.value = null;
          emit('group-selected', null);
        }
      }
    } catch (error) {
      console.error('Error deleting group:', error);
    }
  });
}

onMounted(() => {
  store.fetchContactGroups(); // Fetch groups when component mounts
});
</script>

<style scoped>
/* Add any specific styles if needed */
</style>
