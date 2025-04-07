<template>
  <div class="reset-password-page">
    <div class="reset-password-container">
      <h1 class="text-h4 q-mb-md">Réinitialisation du mot de passe</h1>
      
      <div v-if="!tokenValid && !resetSuccess" class="text-center">
        <p class="text-negative">Le lien de réinitialisation est invalide ou a expiré.</p>
        <q-btn color="primary" label="Retour à la connexion" to="/login" class="q-mt-md" />
      </div>
      
      <div v-else-if="resetSuccess" class="text-center">
        <p class="text-positive">Votre mot de passe a été réinitialisé avec succès.</p>
        <q-btn color="primary" label="Se connecter" to="/login" class="q-mt-md" />
      </div>
      
      <q-form v-else @submit="onSubmit" class="q-gutter-md">
        <q-input
          v-model="password"
          label="Nouveau mot de passe"
          :type="isPwdVisible ? 'text' : 'password'"
          :rules="[
            val => !!val || 'Le mot de passe est requis',
            val => val.length >= 8 || 'Le mot de passe doit contenir au moins 8 caractères',
            val => /[A-Z]/.test(val) || 'Le mot de passe doit contenir au moins une majuscule',
            val => /[a-z]/.test(val) || 'Le mot de passe doit contenir au moins une minuscule',
            val => /[0-9]/.test(val) || 'Le mot de passe doit contenir au moins un chiffre',
            val => /[^A-Za-z0-9]/.test(val) || 'Le mot de passe doit contenir au moins un caractère spécial'
          ]"
          outlined
          dense
        >
          <template v-slot:append>
            <q-icon
              :name="isPwdVisible ? 'visibility_off' : 'visibility'"
              class="cursor-pointer"
              @click="isPwdVisible = !isPwdVisible"
            />
          </template>
        </q-input>
        
        <q-input
          v-model="confirmPassword"
          label="Confirmer le mot de passe"
          :type="isConfirmPwdVisible ? 'text' : 'password'"
          :rules="[
            val => !!val || 'La confirmation du mot de passe est requise',
            val => val === password || 'Les mots de passe ne correspondent pas'
          ]"
          outlined
          dense
        >
          <template v-slot:append>
            <q-icon
              :name="isConfirmPwdVisible ? 'visibility_off' : 'visibility'"
              class="cursor-pointer"
              @click="isConfirmPwdVisible = !isConfirmPwdVisible"
            />
          </template>
        </q-input>
        
        <div class="row justify-center q-mt-lg">
          <q-btn
            type="submit"
            color="primary"
            label="Réinitialiser le mot de passe"
            :loading="loading"
            class="full-width"
          />
        </div>
      </q-form>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/authStore';

export default defineComponent({
  name: 'ResetPasswordPage',
  
  setup() {
    const route = useRoute();
    const router = useRouter();
    const authStore = useAuthStore();
    
    const token = ref<string>('');
    const tokenValid = ref<boolean>(true); // Optimiste par défaut
    const password = ref<string>('');
    const confirmPassword = ref<string>('');
    const loading = ref<boolean>(false);
    const resetSuccess = ref<boolean>(false);
    const isPwdVisible = ref<boolean>(false);
    const isConfirmPwdVisible = ref<boolean>(false);
    
    onMounted(() => {
      // Récupérer le token depuis l'URL
      token.value = route.query.token as string || '';
      
      if (!token.value) {
        tokenValid.value = false;
      }
      
      // Vérifier la validité du token (optionnel)
      // Cette étape pourrait être ajoutée si le backend fournit une API pour vérifier la validité du token
    });
    
    const onSubmit = async () => {
      if (password.value !== confirmPassword.value) {
        return;
      }
      
      loading.value = true;
      
      try {
        const success = await authStore.resetPassword(token.value, password.value);
        
        if (success) {
          resetSuccess.value = true;
        }
      } catch (error) {
        tokenValid.value = false;
      } finally {
        loading.value = false;
      }
    };
    
    return {
      password,
      confirmPassword,
      loading,
      tokenValid,
      resetSuccess,
      onSubmit,
      isPwdVisible,
      isConfirmPwdVisible
    };
  }
});
</script>

<style scoped>
.reset-password-page {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background-color: #f5f5f5;
}

.reset-password-container {
  width: 100%;
  max-width: 400px;
  padding: 2rem;
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}
</style>
