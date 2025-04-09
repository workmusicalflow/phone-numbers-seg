<template>
  <q-dialog v-model="dialogModel" persistent>
    <q-card style="min-width: 350px">
      <q-card-section>
        <div class="text-h6">Ajouter des crédits SMS</div>
      </q-card-section>
      
      <q-card-section>
        <q-form @submit="onSubmit" class="q-gutter-md">
          <p>Utilisateur: <strong>{{ user?.username }}</strong></p>
          <p>Crédits actuels: <strong>{{ user?.smsCredit }}</strong></p>
          
          <q-input
            v-model.number="creditsToAdd"
            label="Nombre de crédits à ajouter *"
            outlined
            type="number"
            min="1"
            :rules="[val => !!val || 'Le nombre de crédits est requis', val => val > 0 || 'Le nombre de crédits doit être positif']"
          />
          
          <div class="q-mt-md">
            <q-btn label="Annuler" color="negative" v-close-popup />
            <q-btn label="Ajouter" type="submit" color="primary" class="q-ml-sm" :loading="loading" />
          </div>
        </q-form>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { User } from '../../../stores/userStore';

const props = defineProps<{
  modelValue: boolean;
  user: User | null;
  loading: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
  (e: 'submit', userId: number, credits: number): void;
}>();

// État local
const dialogModel = ref(props.modelValue);
const creditsToAdd = ref(0);

// Synchroniser le modèle local avec la prop
watch(() => props.modelValue, (newVal) => {
  dialogModel.value = newVal;
  
  // Réinitialiser le formulaire lorsque le dialogue s'ouvre
  if (newVal) {
    creditsToAdd.value = 0;
  }
});

watch(() => dialogModel.value, (newVal) => {
  emit('update:modelValue', newVal);
});

// Méthodes
function onSubmit() {
  if (!props.user || !creditsToAdd.value) return;
  
  emit('submit', props.user.id, creditsToAdd.value);
}
</script>
