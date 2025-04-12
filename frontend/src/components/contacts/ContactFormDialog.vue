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
          <div class="row q-col-gutter-md">
            <div class="col-12 col-md-6">
              <q-input
                v-model="form.firstName"
                label="Prénom *"
                outlined
                :rules="[val => !!val || 'Le prénom est obligatoire']"
              />
            </div>
            <div class="col-12 col-md-6">
              <q-input
                v-model="form.lastName"
                label="Nom *"
                outlined
                :rules="[val => !!val || 'Le nom est obligatoire']"
              />
            </div>
          </div>

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
import { ref, computed, watch } from 'vue';
import { Contact, ContactFormData, Group } from '../../types/contact';

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
  firstName: '',
  lastName: '',
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
  (newContact) => {
    if (newContact) {
      form.value = {
        id: newContact.id,
        firstName: newContact.firstName,
        lastName: newContact.lastName,
        phoneNumber: newContact.phoneNumber,
        email: newContact.email || '',
        groups: newContact.groups?.map(g => g.id) || [],
        notes: newContact.notes || ''
      };
    } else {
      // Réinitialiser le formulaire pour un nouveau contact
      form.value = {
        id: '',
        firstName: '',
        lastName: '',
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
