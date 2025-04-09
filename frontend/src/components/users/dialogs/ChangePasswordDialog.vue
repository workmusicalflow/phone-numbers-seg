<template>
  <q-dialog v-model="dialogModel" persistent>
    <q-card style="min-width: 350px">
      <q-card-section>
        <div class="text-h6">Changer le mot de passe</div>
      </q-card-section>
      
      <q-card-section>
        <q-form @submit="onSubmit" class="q-gutter-md">
          <p>Utilisateur: <strong>{{ user?.username }}</strong></p>
          
          <q-input
            v-model="newPassword"
            label="Nouveau mot de passe *"
            outlined
            :type="showPassword ? 'text' : 'password'"
            :rules="[
              val => !!val || 'Le mot de passe est requis', 
              val => val.length >= 8 || 'Le mot de passe doit contenir au moins 8 caractères',
              val => /[A-Z]/.test(val) || 'Le mot de passe doit contenir au moins une lettre majuscule',
              val => /[0-9]/.test(val) || 'Le mot de passe doit contenir au moins un chiffre'
            ]"
          >
            <template v-slot:prepend>
              <q-icon name="lock" color="primary" />
            </template>
            <template v-slot:append>
              <q-icon
                :name="showPassword ? 'visibility_off' : 'visibility'"
                class="cursor-pointer"
                @click="showPassword = !showPassword"
              />
            </template>
          </q-input>
          
          <!-- Bloc dédié pour les exigences et la force du mot de passe -->
          <div class="password-requirements-block q-mt-xs q-mb-md q-pa-sm rounded-borders" style="border: 1px solid #e0e0e0;">
            <div class="text-caption text-grey-8 q-mb-xs">Le mot de passe doit contenir au moins:</div>
            <div class="row q-gutter-x-md q-gutter-y-xs items-center" style="flex-wrap: wrap;">
              <div :class="getRequirementClass(newPassword.length >= 8)">
                <q-icon :name="getRequirementIcon(newPassword.length >= 8)" size="1.1em" class="q-mr-xs"/>
                8 caractères
              </div>
              <div :class="getRequirementClass(/[A-Z]/.test(newPassword))">
                <q-icon :name="getRequirementIcon(/[A-Z]/.test(newPassword))" size="1.1em" class="q-mr-xs"/>
                Majuscule (A-Z)
              </div>
              <div :class="getRequirementClass(/[0-9]/.test(newPassword))">
                <q-icon :name="getRequirementIcon(/[0-9]/.test(newPassword))" size="1.1em" class="q-mr-xs"/>
                Chiffre (0-9)
              </div>
            </div>
            
            <!-- Indicateur de force du mot de passe -->
            <div class="q-mt-sm" v-if="newPassword">
              <div class="text-caption q-mb-xs">Force du mot de passe:</div>
              <q-linear-progress
                :value="passwordStrength"
                :color="passwordStrengthColor"
                size="6px"
                rounded
                class="q-mb-xs"
                aria-label="Force du mot de passe"
              />
              <div class="text-caption text-right" :class="`text-${passwordStrengthColor}`">
                Force: {{ passwordStrengthLabel }}
              </div>
            </div>
          </div>
          
          <q-input
            v-model="confirmPassword"
            label="Confirmer le mot de passe *"
            outlined
            :type="showPassword ? 'text' : 'password'"
            :rules="[
              val => !!val || 'La confirmation du mot de passe est requise',
              val => val === newPassword || 'Les mots de passe ne correspondent pas'
            ]"
          >
            <template v-slot:prepend>
              <q-icon name="lock" color="primary" />
            </template>
          </q-input>
          
          <div class="q-mt-md">
            <q-btn label="Annuler" color="negative" v-close-popup />
            <q-btn label="Changer" type="submit" color="primary" class="q-ml-sm" :loading="loading" />
          </div>
        </q-form>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { User } from '../../../stores/userStore';

const props = defineProps<{
  modelValue: boolean;
  user: User | null;
  loading: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
  (e: 'submit', userId: number, password: string): void;
}>();

// État local
const dialogModel = ref(props.modelValue);
const showPassword = ref(false);
const newPassword = ref('');
const confirmPassword = ref('');

// Fonctions utilitaires pour l'affichage des prérequis
function getRequirementClass(met: boolean): string {
  return `row items-center text-caption ${met ? 'text-positive' : 'text-grey-7'}`;
}

function getRequirementIcon(met: boolean): string {
  return met ? 'check_circle' : 'radio_button_unchecked';
}

// Calcul de la force du mot de passe
const passwordStrength = computed(() => {
  const password = newPassword.value;
  if (!password) return 0;
  
  let strength = 0;
  
  // Critères de base (requis)
  if (password.length >= 8) strength += 0.25;
  if (/[A-Z]/.test(password)) strength += 0.25;
  if (/[0-9]/.test(password)) strength += 0.25;
  
  // Critères supplémentaires
  if (password.length > 12) strength += 0.25; // Longueur supplémentaire
  
  // Limiter à 1
  return Math.min(strength, 1);
});

const passwordStrengthColor = computed(() => {
  const strength = passwordStrength.value;
  if (strength < 0.5) return 'negative';
  if (strength < 0.75) return 'warning';
  return 'positive';
});

const passwordStrengthLabel = computed(() => {
  const strength = passwordStrength.value;
  if (strength < 0.5) return 'Faible';
  if (strength < 0.75) return 'Moyen';
  return 'Fort';
});

// Synchroniser le modèle local avec la prop
watch(() => props.modelValue, (newVal) => {
  dialogModel.value = newVal;
  
  // Réinitialiser le formulaire lorsque le dialogue s'ouvre
  if (newVal) {
    newPassword.value = '';
    confirmPassword.value = '';
  }
});

watch(() => dialogModel.value, (newVal) => {
  emit('update:modelValue', newVal);
});

// Méthodes
function onSubmit() {
  if (!props.user || !newPassword.value || newPassword.value !== confirmPassword.value) return;
  
  emit('submit', props.user.id, newPassword.value);
}
</script>
