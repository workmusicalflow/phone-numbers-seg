<template>
  <q-dialog v-model="dialogModel" persistent>
    <q-card style="min-width: 500px">
      <q-card-section class="row items-center">
        <div class="text-h6">{{ isEditing ? 'Modifier le contact' : 'Nouveau contact' }}</div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup @click="onCancel" />
      </q-card-section>

      <q-card-section>
        <q-form @submit="onSubmit" class="q-gutter-md">
           <q-input
             v-model="form.name"
             label="Nom complet *"
             outlined
             :rules="[val => !!val || 'Le nom est obligatoire']"
           />

          <q-input
            v-model="form.phoneNumber"
            label="Numéro de téléphone *"
            outlined
            :rules="[
              val => !!val || 'Le numéro de téléphone est obligatoire',
              val => /^\+?[0-9]{8,15}$/.test(val) || 'Format de numéro invalide'
            ]"
          />

          <q-input
            v-model="form.email"
            label="Email"
            type="email"
            outlined
            :rules="[
              val => !val || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val) || 'Format d\'email invalide'
            ]"
          />

          <q-select
            v-model="form.groups"
            :options="groups"
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
            v-model="form.notes"
            label="Notes"
            type="textarea"
            outlined
            autogrow
          />

          <div class="row justify-end q-mt-md">
            <q-btn label="Annuler" color="grey-7" @click="onCancel" class="q-mr-sm" />
            <q-btn label="Enregistrer" type="submit" color="primary" :loading="loading" />
          </div>
        </q-form>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { Contact, ContactFormData, Group } from '../../types/contact';
import { useContactStore } from '../../stores/contactStore';

const contactStore = useContactStore();

const props = defineProps<{
  modelValue: boolean;
  contact?: Contact | null;
  groups: Group[];
  loading?: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
  (e: 'save', contactData: ContactFormData): void;
  (e: 'cancel'): void;
}>();

// État local
const isEditing = computed(() => !!props.contact);

// Formulaire
const form = ref<ContactFormData>({
  id: '',
  name: '', // Changed from firstName/lastName
  phoneNumber: '',
  email: '',
  groups: [],
  notes: ''
});

// Modèle pour v-model
const dialogModel = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
});

// Surveiller les changements de contact pour mettre à jour le formulaire
watch(
  () => props.contact,
  async (newContact) => {
    if (newContact) {
      // Initialize form with contact data
      form.value = {
        id: newContact.id,
        name: newContact.name,
        phoneNumber: newContact.phoneNumber,
        email: newContact.email || '',
        groups: [], // Will be populated after fetching groups
        notes: newContact.notes || ''
      };
      
      // Fetch the contact's groups to ensure we have the latest data
      try {
        const contactGroups = await contactStore.fetchGroupsForContact(newContact.id);
        // Update form with fetched groups
        form.value.groups = contactGroups.map(g => g.id);
      } catch (error) {
        console.error('Error fetching contact groups:', error);
        // Fallback to groups from the contact object if fetch fails
        form.value.groups = newContact.groups?.map(g => g.id) || [];
      }
    } else {
      // Réinitialiser le formulaire pour un nouveau contact
      form.value = {
        id: '',
        name: '', // Changed from firstName/lastName
        phoneNumber: '',
        email: '',
        groups: [],
        notes: ''
      };
    }
  },
  { immediate: true }
);

// Méthodes
const onSubmit = () => {
  emit('save', { ...form.value });
};

const onCancel = () => {
  emit('cancel');
  emit('update:modelValue', false);
};
</script>
