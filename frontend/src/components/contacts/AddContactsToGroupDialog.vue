<template>
  <q-dialog
    v-model="dialogVisible"
    persistent
    :maximized="$q.screen.lt.sm"
    transition-show="scale"
    transition-hide="scale"
  >
    <q-card style="min-width: 500px; max-width: 90vw;">
      <q-card-section>
        <div class="text-h6">Ajouter des contacts au groupe</div>
        <div v-if="groupName" class="text-subtitle2">{{ groupName }}</div>
      </q-card-section>

      <q-card-section>
        <q-input
          v-model="searchQuery"
          label="Rechercher des contacts"
          outlined
          dense
          clearable
          debounce="300"
          @update:model-value="searchContacts"
        >
          <template v-slot:append>
            <q-icon name="search" />
          </template>
        </q-input>
      </q-card-section>

      <q-card-section style="max-height: 50vh" class="scroll">
        <div v-if="isLoading" class="text-center q-pa-md">
          <q-spinner-dots color="primary" size="40px" />
        </div>

        <div v-else-if="error" class="text-negative q-pa-md">
          {{ error.message }}
        </div>

        <div v-else-if="availableContacts.length === 0" class="text-center q-pa-md">
          <p>Aucun contact trouvé.</p>
        </div>

        <q-list v-else separator>
          <q-item v-for="contact in availableContacts" :key="contact.id">
            <q-item-section side>
              <q-checkbox v-model="selectedContactIds" :val="contact.id" />
            </q-item-section>
            <q-item-section>
              <q-item-label>{{ contact.name }}</q-item-label>
              <q-item-label caption>{{ contact.phoneNumber }}</q-item-label>
              <q-item-label caption v-if="contact.email">{{ contact.email }}</q-item-label>
            </q-item-section>
          </q-item>
        </q-list>
      </q-card-section>

      <q-card-section v-if="availableContacts.length > 0">
        <div class="row justify-between items-center">
          <div>
            <q-checkbox
              v-model="selectAll"
              label="Tout sélectionner"
              @update:model-value="toggleSelectAll"
            />
          </div>
          <div>
            {{ selectedContactIds.length }} contact(s) sélectionné(s)
          </div>
        </div>
      </q-card-section>

      <q-card-actions align="right">
        <q-btn
          flat
          label="Annuler"
          color="negative"
          v-close-popup
          @click="onCancel"
        />
        <q-btn
          flat
          label="Ajouter"
          color="primary"
          :loading="isSubmitting"
          :disable="selectedContactIds.length === 0"
          @click="onSubmit"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useQuasar } from 'quasar';
import { useContactStore } from '@/stores/contactStore';
import { useContactGroupStore } from '@/stores/contactGroupStore';
import type { Contact } from '@/types/contact';

const props = defineProps<{
  modelValue: boolean;
  groupId: string;
  groupName?: string;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
  (e: 'contacts-added', count: number): void;
}>();

const $q = useQuasar();
const contactStore = useContactStore();
const groupStore = useContactGroupStore();

// Computed for v-model binding
const dialogVisible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
});

// State
const searchQuery = ref('');
const availableContacts = ref<Contact[]>([]);
const selectedContactIds = ref<string[]>([]);
const selectAll = ref(false);
const isLoading = ref(false);
const isSubmitting = ref(false);
const error = ref<Error | null>(null);

// Watch for dialog visibility changes
watch(
  () => props.modelValue,
  (visible) => {
    if (visible) {
      // Reset state when dialog opens
      searchQuery.value = '';
      selectedContactIds.value = [];
      selectAll.value = false;
      error.value = null;
      
      // Load initial contacts
      loadAvailableContacts();
    }
  },
  { immediate: true }
);

// Methods
async function loadAvailableContacts() {
  if (!props.groupId) return;
  
  isLoading.value = true;
  error.value = null;
  
  try {
    // This would ideally call a specific API endpoint to get contacts not in the group
    // For now, we'll use a simple approach: get all contacts and filter out those already in the group
    
    // Get all contacts
    await contactStore.fetchContacts();
    const allContacts = contactStore.contacts;
    
    // Get contacts in the group
    await groupStore.fetchContactsInGroup(props.groupId, 1000, 0); // Get all contacts in the group
    const groupContactIds = new Set(groupStore.currentGroupContacts.map(c => c.id));
    
    // Filter out contacts already in the group
    availableContacts.value = allContacts.filter(contact => !groupContactIds.has(contact.id));
  } catch (err) {
    error.value = err as Error;
    console.error('Error loading available contacts:', err);
  } finally {
    isLoading.value = false;
  }
}

async function searchContacts() {
  if (!searchQuery.value.trim()) {
    await loadAvailableContacts();
    return;
  }
  
  isLoading.value = true;
  error.value = null;
  
  try {
    // This would ideally call a search API endpoint
    // For now, we'll filter the already loaded contacts
    const query = searchQuery.value.toLowerCase();
    
    // Get all contacts if not already loaded
    if (contactStore.contacts.length === 0) {
      await contactStore.fetchContacts();
    }
    
    // Get contacts in the group if not already loaded
    if (groupStore.currentGroupContacts.length === 0) {
      await groupStore.fetchContactsInGroup(props.groupId, 1000, 0);
    }
    
    const groupContactIds = new Set(groupStore.currentGroupContacts.map(c => c.id));
    
    // Filter contacts by search query and exclude those already in the group
    availableContacts.value = contactStore.contacts.filter(contact => 
      !groupContactIds.has(contact.id) && 
      (
        contact.name.toLowerCase().includes(query) || 
        contact.phoneNumber.includes(query) ||
        (contact.email && contact.email.toLowerCase().includes(query))
      )
    );
  } catch (err) {
    error.value = err as Error;
    console.error('Error searching contacts:', err);
  } finally {
    isLoading.value = false;
  }
}

function toggleSelectAll(value: boolean) {
  if (value) {
    // Select all available contacts
    selectedContactIds.value = availableContacts.value.map(c => c.id);
  } else {
    // Deselect all
    selectedContactIds.value = [];
  }
}

// Watch for changes in selected contacts to update selectAll state
watch(
  selectedContactIds,
  (ids) => {
    selectAll.value = ids.length > 0 && ids.length === availableContacts.value.length;
  }
);

async function onSubmit() {
  if (selectedContactIds.value.length === 0) return;
  
  isSubmitting.value = true;
  
  try {
    const result = await groupStore.addContactsToGroup(selectedContactIds.value, props.groupId);
    
    if (result && result.successful > 0) {
      emit('contacts-added', result.successful);
      dialogVisible.value = false;
      
      // Show success message
      $q.notify({
        color: 'positive',
        message: result.message || `${result.successful} contact(s) ajouté(s) au groupe.`,
        icon: 'check_circle'
      });
      
      // If there were failures, show a warning
      if (result.failed > 0) {
        $q.notify({
          color: 'warning',
          message: `${result.failed} contact(s) n'ont pas pu être ajoutés.`,
          icon: 'warning'
        });
      }
    } else if (result) {
      // Si result existe mais aucun contact n'a été ajouté avec succès
      $q.notify({
        color: 'warning',
        message: result.message || 'Aucun contact n\'a pu être ajouté au groupe.',
        icon: 'warning'
      });
      dialogVisible.value = false;
    } else {
      throw new Error('Échec de l\'ajout des contacts au groupe.');
    }
  } catch (err) {
    error.value = err as Error;
    console.error('Error adding contacts to group:', err);
    
    $q.notify({
      color: 'negative',
      message: `Erreur: ${error.value.message || 'Échec de l\'ajout des contacts au groupe.'}`,
      icon: 'error'
    });
  } finally {
    isSubmitting.value = false;
  }
}

function onCancel() {
  dialogVisible.value = false;
}
</script>
