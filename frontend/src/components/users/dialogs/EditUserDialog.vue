<template>
  <q-dialog v-model="dialogModel" persistent>
    <q-card style="min-width: 350px">
      <q-card-section>
        <div class="text-h6">Modifier l'utilisateur</div>
      </q-card-section>
      
      <q-card-section>
        <q-form @submit="onSubmit" class="q-gutter-md">
          <q-input
            v-model="formData.username"
            label="Nom d'utilisateur"
            outlined
            readonly
          />
          
          <q-input
            v-model="formData.email"
            label="Email"
            outlined
            type="email"
          />
          
          <q-input
            v-model.number="formData.smsLimit"
            label="Limite de SMS"
            outlined
            type="number"
            min="0"
            hint="Laissez vide pour illimité"
          />
          
          <q-toggle
            v-model="formData.isAdmin"
            label="Administrateur"
            color="primary"
          />
          
          <div class="q-mt-md">
            <q-btn label="Annuler" color="negative" v-close-popup />
            <q-btn label="Mettre à jour" type="submit" color="primary" class="q-ml-sm" :loading="loading" />
          </div>
        </q-form>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { User } from '../../../stores/userStore';

interface EditUserFormData {
  id: number;
  username: string;
  email: string;
  smsLimit: number | null;
  isAdmin: boolean;
}

const props = defineProps<{
  modelValue: boolean;
  user: User | null;
  loading: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
  (e: 'submit', data: EditUserFormData): void;
}>();

// État local
const dialogModel = ref(props.modelValue);
const formData = ref<EditUserFormData>({
  id: 0,
  username: '',
  email: '',
  smsLimit: null,
  isAdmin: false
});

// Synchroniser le modèle local avec la prop
watch(() => props.modelValue, (newVal) => {
  dialogModel.value = newVal;
  
  // Initialiser les données du formulaire lorsque le dialogue s'ouvre
  if (newVal && props.user) {
    formData.value = {
      id: props.user.id,
      username: props.user.username,
      email: props.user.email || '',
      smsLimit: props.user.smsLimit,
      isAdmin: props.user.isAdmin
    };
  }
});

watch(() => dialogModel.value, (newVal) => {
  emit('update:modelValue', newVal);
});

// Méthodes
function onSubmit() {
  emit('submit', { ...formData.value });
}
</script>
