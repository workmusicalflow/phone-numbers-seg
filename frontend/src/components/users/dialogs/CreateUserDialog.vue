<template>
  <q-dialog v-model="dialogModel" persistent @hide="resetForm">
    <q-card style="min-width: 450px; max-width: 550px; width: 100%;">
      <!-- En-tête -->
      <q-card-section class="bg-primary text-white row items-center">
        <q-icon name="person_add" size="sm" class="q-mr-sm" />
        <div class="text-h6">Nouvel utilisateur</div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup />
      </q-card-section>

      <!-- Corps du formulaire -->
      <q-card-section class="q-pt-md scroll" style="max-height: 70vh;">
        <q-form ref="userFormRef" @submit.prevent="onSubmit" class="q-gutter-y-md">

          <!-- Section: Informations de base -->
          <div class="form-section">
            <div class="text-subtitle1 q-mb-sm text-primary row items-center">
              <q-icon name="account_circle" class="q-mr-sm" />
              Informations de base
            </div>
            <q-separator class="q-mb-md" />

            <q-input
              v-model.trim="formData.username"
              label="Nom d'utilisateur *"
              outlined
              dense
              stack-label
              autofocus
              lazy-rules
              :rules="[rules.required, rules.usernameUnique]"
              aria-label="Nom d'utilisateur"
              ref="usernameInputRef"
            >
              <template v-slot:prepend>
                <q-icon name="person" color="primary" />
              </template>
              <template v-slot:append>
                <q-icon name="help_outline" color="grey-7" class="cursor-pointer">
                  <q-tooltip max-width="250px">
                    Nom unique utilisé pour la connexion. Doit contenir uniquement des lettres, chiffres, underscores ou tirets.
                  </q-tooltip>
                </q-icon>
              </template>
            </q-input>

            <q-input
              v-model="formData.password"
              label="Mot de passe *"
              outlined
              dense
              stack-label
              :type="showPassword ? 'text' : 'password'"
              lazy-rules
              :rules="[rules.required, rules.passwordLength, rules.passwordUppercase, rules.passwordDigit]"
              aria-label="Mot de passe"
              class="q-mt-md"
            >
              <template v-slot:prepend>
                <q-icon name="lock" color="primary" />
              </template>
              <template v-slot:append>
                <q-icon
                  :name="showPassword ? 'visibility_off' : 'visibility'"
                  class="cursor-pointer"
                  @click="showPassword = !showPassword"
                  aria-label="Afficher ou masquer le mot de passe"
                />
              </template>
              <!-- Hint slot is now empty or removed -->
            </q-input>

            <!-- Bloc dédié pour les exigences et la force du mot de passe -->
            <div class="password-requirements-block q-mt-xs q-mb-md q-pa-sm rounded-borders" style="border: 1px solid #e0e0e0;">
              <div class="text-caption text-grey-8 q-mb-xs">Le mot de passe doit contenir au moins:</div>
              <div class="row q-gutter-x-md q-gutter-y-xs items-center" style="flex-wrap: wrap;">
                <div :class="getRequirementClass(passwordRequirements.length)">
                  <q-icon :name="getRequirementIcon(passwordRequirements.length)" size="1.1em" class="q-mr-xs"/>
                  8 caractères
                </div>
                <div :class="getRequirementClass(passwordRequirements.uppercase)">
                   <q-icon :name="getRequirementIcon(passwordRequirements.uppercase)" size="1.1em" class="q-mr-xs"/>
                  Majuscule (A-Z)
                </div>
                <div :class="getRequirementClass(passwordRequirements.digit)">
                   <q-icon :name="getRequirementIcon(passwordRequirements.digit)" size="1.1em" class="q-mr-xs"/>
                  Chiffre (0-9)
                </div>
              </div>

              <!-- Indicateur de force -->
              <div class="q-mt-sm" v-if="formData.password">
                <q-linear-progress
                  :value="passwordStrengthScore / 4"
                  :color="passwordStrengthColor"
                  size="6px"
                  rounded
                  class="q-mb-xs"
                  aria-label="Force du mot de passe"
                  :aria-valuenow="passwordStrengthScore"
                  aria-valuemin="0"
                  aria-valuemax="4"
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
              dense
              stack-label
              :type="showPassword ? 'text' : 'password'"
              lazy-rules
              :rules="[rules.required, rules.passwordsMatch]"
              aria-label="Confirmation du mot de passe"
               class="q-mt-md"
            >
              <template v-slot:prepend>
                <q-icon name="lock_clock" color="primary" />
              </template>
            </q-input>

            <q-input
              v-model.trim="formData.email"
              label="Email (optionnel)"
              outlined
              dense
              stack-label
              type="email"
              lazy-rules
              :rules="[rules.emailFormat]"
              aria-label="Adresse e-mail"
               class="q-mt-md"
            >
              <template v-slot:prepend>
                <q-icon name="email" color="primary" />
              </template>
              <template v-slot:append>
                <q-icon name="help_outline" color="grey-7" class="cursor-pointer">
                  <q-tooltip max-width="250px">
                    Utilisé pour les notifications et la récupération de mot de passe.
                  </q-tooltip>
                </q-icon>
              </template>
            </q-input>
          </div>

          <!-- Section: Paramètres SMS -->
          <div class="form-section q-mt-lg">
            <div class="text-subtitle1 q-mb-sm text-primary row items-center">
              <q-icon name="sms" class="q-mr-sm" />
              Paramètres SMS
            </div>
            <q-separator class="q-mb-md" />

            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="formData.smsCredit"
                  label="Crédits SMS initiaux"
                  outlined
                  dense
                  stack-label
                  type="number"
                  min="0"
                  lazy-rules
                  :rules="[rules.nonNegativeNumber]"
                  aria-label="Crédits SMS initiaux"
                >
                  <template v-slot:prepend>
                    <q-icon name="payments" color="primary" />
                  </template>
                  <template v-slot:append>
                    <q-icon name="help_outline" color="grey-7" class="cursor-pointer">
                      <q-tooltip>
                        Nombre de crédits SMS à l'inscription.
                      </q-tooltip>
                    </q-icon>
                  </template>
                </q-input>
              </div>
              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="formData.smsLimit"
                  label="Limite de SMS (optionnel)"
                  outlined
                  dense
                  stack-label
                  type="number"
                  min="0"
                  clearable
                  lazy-rules
                  :rules="[rules.nonNegativeOrNull]"
                  aria-label="Limite de SMS"
                >
                  <template v-slot:prepend>
                    <q-icon name="speed" color="primary" />
                  </template>
                  <template v-slot:append>
                    <q-icon name="help_outline" color="grey-7" class="cursor-pointer">
                      <q-tooltip max-width="250px">
                        Maximum de SMS envoyables. Laissez vide ou 0 pour illimité.
                      </q-tooltip>
                    </q-icon>
                  </template>
                  <template v-slot:hint>
                    Vide ou 0 = illimité
                  </template>
                </q-input>
              </div>
            </div>
          </div>

          <!-- Section: Droits d'accès -->
          <div class="form-section q-mt-lg">
            <div class="text-subtitle1 q-mb-sm text-primary row items-center">
              <q-icon name="security" class="q-mr-sm" />
              Droits d'accès
            </div>
            <q-separator class="q-mb-md" />

            <q-card flat bordered class="q-pa-sm bg-grey-1">
               <q-item tag="label" class="q-pa-none">
                 <q-item-section avatar>
                   <q-toggle
                      v-model="formData.isAdmin"
                      color="primary"
                      icon="admin_panel_settings"
                      aria-label="Donner les droits administrateur"
                    />
                 </q-item-section>
                 <q-item-section>
                    <q-item-label>Administrateur</q-item-label>
                    <q-item-label caption>Accès complet au système.</q-item-label>
                 </q-item-section>
                 <q-item-section side top>
                    <q-icon name="help_outline" color="grey-7" class="cursor-pointer q-mt-xs">
                      <q-tooltip max-width="250px">
                        Permet de gérer les utilisateurs, les paramètres et toutes les fonctionnalités. À utiliser avec précaution.
                      </q-tooltip>
                    </q-icon>
                 </q-item-section>
               </q-item>
               <q-slide-transition>
                <div v-show="formData.isAdmin" class="text-caption text-warning q-mt-sm q-ml-md q-pb-xs row items-center no-wrap">
                  <q-icon name="warning" size="xs" class="q-mr-xs" />
                  Attention: Accorde des privilèges élevés.
                </div>
              </q-slide-transition>
            </q-card>
          </div>

        </q-form>
      </q-card-section>

       <!-- Pied de page avec actions -->
      <q-card-actions align="right" class="q-pa-md bg-grey-2">
        <q-btn
          label="Annuler"
          color="grey-8"
          flat
          no-caps
          icon="cancel"
          v-close-popup
          aria-label="Annuler la création"
        />
        <q-btn
          label="Créer l'utilisateur"
          type="submit"
          color="primary"
          no-caps
          :loading="loading"
          icon="person_add"
          @click="submitForm"
          aria-label="Valider la création de l'utilisateur"
        >
          <template v-slot:loading>
            <q-spinner-dots />
          </template>
        </q-btn>
      </q-card-actions>

    </q-card>
  </q-dialog>
</template>

<script setup lang="ts">
import { ref, watch, computed, nextTick } from 'vue';
import { QForm, QInput } from 'quasar'; // Importer les types si nécessaire pour les refs

interface UserFormData {
  username: string;
  password: string;
  email: string;
  smsCredit: number;
  smsLimit: number | null;
  isAdmin: boolean;
}

const props = defineProps<{
  modelValue: boolean;
  loading: boolean;
  // Optionnel: Passer une fonction pour vérifier l'unicité du username
  isUsernameTaken?: (username: string) => Promise<boolean>;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
  (e: 'submit', data: UserFormData): void;
}>();

// Refs des composants
const userFormRef = ref<QForm | null>(null);
const usernameInputRef = ref<QInput | null>(null);

// État local
const dialogModel = ref(props.modelValue);
const showPassword = ref(false);
const confirmPassword = ref('');
const initialFormData: UserFormData = {
  username: '',
  password: '',
  email: '',
  smsCredit: 10, // Valeur par défaut
  smsLimit: null, // Par défaut: illimité
  isAdmin: false,
};
const formData = ref<UserFormData>({ ...initialFormData });

// --- Logique du mot de passe ---

const passwordRequirements = computed(() => ({
  length: formData.value.password.length >= 8,
  uppercase: /[A-Z]/.test(formData.value.password),
  digit: /[0-9]/.test(formData.value.password),
  lowercase: /[a-z]/.test(formData.value.password), // Ajouté pour la force
  special: /[^A-Za-z0-9]/.test(formData.value.password), // Ajouté pour la force
}));

const passwordStrengthScore = computed(() => {
  const reqs = passwordRequirements.value;
  // On donne plus de poids aux critères de base
  let score = 0;
  if (reqs.length) score++;
  if (reqs.uppercase) score++;
  if (reqs.digit) score++;
  // Critères additionnels pour la force
  if (reqs.lowercase && reqs.length) score += 0.5; // Minuscule a moins d'impact si déjà majuscule/chiffre
  if (reqs.special && reqs.length) score += 0.5;   // Spécial augmente bien la force
  if (formData.value.password.length > 12) score += 0.5; // Longueur > 12

  // On peut avoir un score max de 4 (8c, Maj, chiffre, min, special) ou un peu plus avec la longueur > 12
  // On normalise ici sur une échelle de 0 à 4 pour la couleur/label
   const baseScore = (reqs.length ? 1 : 0) + (reqs.uppercase ? 1 : 0) + (reqs.digit ? 1 : 0);
   const bonusScore = (reqs.lowercase ? 0.5 : 0) + (reqs.special ? 1 : 0) + (formData.value.password.length > 12 ? 0.5 : 0);
   return Math.min(baseScore + bonusScore, 4); // Plafonner à 4 pour la démo
});

const passwordStrengthColor = computed(() => {
  const score = passwordStrengthScore.value;
  if (score < 2) return 'negative';    // Rouge si moins de 2 points (ex: longueur + 1 autre)
  if (score < 3.5) return 'warning';   // Orange si < 3.5 points (ex: longueur + maj + chiffre)
  return 'positive';                  // Vert si 3.5 points ou plus
});

const passwordStrengthLabel = computed(() => {
  const score = passwordStrengthScore.value;
  if (score < 2) return 'Faible';
  if (score < 3.5) return 'Moyen';
  return 'Fort';
});

// Fonctions utilitaires pour l'affichage des prérequis
function getRequirementClass(met: boolean): string {
  return `row items-center text-caption ${met ? 'text-positive' : 'text-grey-7'}`;
}
function getRequirementIcon(met: boolean): string {
  return met ? 'check_circle' : 'radio_button_unchecked';
  // Alternative: 'cancel' pour non rempli?
  // return met ? 'check_circle' : 'cancel';
}

// --- Règles de validation ---
const rules = {
  required: (val: string | number | null) => !!val || 'Ce champ est requis.',
  emailFormat: (val: string | null) => (!val || /.+@.+\..+/.test(val)) || 'Format d\'email invalide.',
  passwordLength: (val: string) => (val && val.length >= 8) || 'Minimum 8 caractères.',
  passwordUppercase: (val: string) => (val && /[A-Z]/.test(val)) || 'Requiert une majuscule.',
  passwordDigit: (val: string) => (val && /[0-9]/.test(val)) || 'Requiert un chiffre.',
  passwordsMatch: (val: string) => val === formData.value.password || 'Les mots de passe ne correspondent pas.',
  nonNegativeNumber: (val: number | null) => (val !== null && val >= 0) || 'Doit être un nombre positif ou zéro.',
  nonNegativeOrNull: (val: number | null) => (val === null || val >= 0) || 'Doit être un nombre positif, zéro, ou vide.',
  // Règle d'unicité (exemple asynchrone)
  usernameUnique: async (val: string) => {
    if (!val || !props.isUsernameTaken) return true; // Pas de validation si vide ou fonction non fournie
    const isTaken = await props.isUsernameTaken(val);
    return !isTaken || 'Ce nom d\'utilisateur est déjà pris.';
  },
  // Optionnel: Règle plus stricte pour username
  usernameFormat: (val: string) => /^[a-zA-Z0-9_-]+$/.test(val) || 'Caractères autorisés: lettres, chiffres, _, -'
};

// --- Gestion du dialogue et du formulaire ---

// Synchroniser le modèle local avec la prop
watch(() => props.modelValue, (newVal) => {
  dialogModel.value = newVal;
  if (newVal) {
    // Donner le focus au premier champ quand le dialogue s'ouvre
    nextTick(() => {
      usernameInputRef.value?.focus();
    });
  }
});

watch(dialogModel, (newVal) => {
  emit('update:modelValue', newVal);
});

// Réinitialiser le formulaire
function resetForm() {
  formData.value = { ...initialFormData };
  confirmPassword.value = '';
  showPassword.value = false;
  // Réinitialiser la validation du formulaire Quasar
  nextTick(() => {
     userFormRef.value?.resetValidation();
  });
}

// Soumettre le formulaire
async function submitForm() {
  if (!userFormRef.value) return;

  const isValid = await userFormRef.value.validate();
  if (isValid) {
    // Les règles (y compris passwordsMatch et usernameUnique si asynchrone) sont passées
    emit('submit', { ...formData.value });
  } else {
    // Optionnel: Notifier l'utilisateur que le formulaire a des erreurs
    console.error('Validation du formulaire échouée');
    // On pourrait afficher une notification Quasar ici
    // $q.notify({ type: 'negative', message: 'Veuillez corriger les erreurs dans le formulaire.' });
  }
}

// Remplacement de l'appel direct à onSubmit par un clic sur le bouton
// qui appelle submitForm(), qui gère la validation QForm.
function onSubmit() {
  // Cette fonction est toujours liée à `@submit` sur q-form
  // pour gérer la soumission par la touche Entrée, par exemple.
  submitForm();
}

</script>

<style lang="scss" scoped>
.password-requirements {
  line-height: 1.3; // Améliore la lisibilité de la liste
}

// Assurer que le q-card-section scrollable fonctionne bien
.scroll {
  overflow-y: auto;
}
</style>
