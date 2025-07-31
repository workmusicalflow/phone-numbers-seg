<template>
  <div class="login-page">
    <div class="login-container">
      <h1 class="text-h4 q-mb-md">Connexion</h1>
      
      <!-- Notification component -->
      <notification-toast
        v-model:show="showNotification"
        :message="notificationMessage"
        :type="notificationType"
        :timeout="3000"
      />
      
      <q-form @submit="onSubmit" class="q-gutter-md">
        <q-input
          v-model="username"
          label="Nom d'utilisateur"
          :rules="[val => !!val || 'Le nom d\'utilisateur est requis']"
          outlined
          dense
        />
        
        <q-input
          v-model="password"
          label="Mot de passe"
          :type="isPwdVisible ? 'text' : 'password'"
          :rules="[val => !!val || 'Le mot de passe est requis']"
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
        
        <div class="row justify-between items-center q-mt-md">
          <q-checkbox v-model="rememberMe" label="Se souvenir de moi" />
          <q-btn flat color="primary" label="Mot de passe oublié ?" @click="forgotPassword = true" />
        </div>
        
        <div class="row justify-center q-mt-lg">
          <q-btn
            type="submit"
            color="primary"
            label="Se connecter"
            :loading="loading"
            class="full-width"
          />
        </div>
      </q-form>
    </div>
    
    <!-- Dialog de réinitialisation de mot de passe -->
    <q-dialog v-model="forgotPassword">
      <q-card style="min-width: 350px">
        <q-card-section>
          <div class="text-h6">Réinitialisation du mot de passe</div>
        </q-card-section>
        
        <q-card-section>
          <q-input
            v-model="email"
            label="Email"
            type="email"
            :rules="[val => !!val || 'L\'email est requis', val => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val) || 'Email invalide']"
            outlined
            dense
          />
        </q-card-section>
        
        <q-card-actions align="right">
          <q-btn flat label="Annuler" color="primary" v-close-popup />
          <q-btn flat label="Envoyer" color="primary" @click="onResetPassword" :loading="resetLoading" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, nextTick } from 'vue'; // Import nextTick
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/authStore';
import NotificationToast from '../components/common/NotificationToast.vue';

export default defineComponent({
  name: 'LoginPage',
  components: {
    NotificationToast
  },
  
  setup() {
    const router = useRouter();
    const authStore = useAuthStore();
    
    const username = ref('');
    const password = ref('');
    const rememberMe = ref(false);
    const loading = ref(false);
    const isPwdVisible = ref(false);
    
    // Notification state
    const showNotification = ref(false);
    const notificationMessage = ref('');
    const notificationType = ref('negative');
    
    const forgotPassword = ref(false);
    const email = ref('');
    const resetLoading = ref(false);
    
    const onSubmit = async () => {
      loading.value = true;
      
      try {
        const success = await authStore.login(username.value, password.value);
        
        if (success) {
          // Wait for the next DOM update cycle before redirecting
          await nextTick(); 
          // Rediriger vers la page d'accueil ou le tableau de bord
          if (authStore.isAdmin) {
            router.push('/admin-dashboard'); // Corrected path
          } else {
            router.push('/');
          }
        }
      } catch (error) {
        // Handle the error gracefully
        console.error('Login error:', error);
        
        // Display error notification
        notificationMessage.value = error instanceof Error 
          ? error.message 
          : "Une erreur est survenue lors de la connexion";
        notificationType.value = 'negative';
        showNotification.value = true;
      } finally {
        loading.value = false;
      }
    };
    
    const onResetPassword = async () => {
      resetLoading.value = true;
      
      try {
        const success = await authStore.requestPasswordReset(email.value);
        
        if (success) {
          forgotPassword.value = false;
          email.value = '';
        }
      } finally {
        resetLoading.value = false;
      }
    };
    
    return {
      username,
      password,
      rememberMe,
      loading,
      forgotPassword,
      email,
      resetLoading,
      onSubmit,
      onResetPassword,
      isPwdVisible,
      // Notification props
      showNotification,
      notificationMessage,
      notificationType
    };
  }
});
</script>

<style scoped>
.login-page {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background-color: #f5f5f5;
}

.login-container {
  width: 100%;
  max-width: 400px;
  padding: 2rem;
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}
</style>
