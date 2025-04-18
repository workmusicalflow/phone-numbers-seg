<template>
  <q-layout view="lHh Lpr lFf">
    <q-header elevated class="bg-primary text-white">
      <q-toolbar>
        <q-btn
          v-if="authStore.isAuthenticated"
          flat
          dense
          round
          icon="menu"
          aria-label="Menu"
          @click="leftDrawerOpen = !leftDrawerOpen"
        />
        
        <q-toolbar-title>
          Oracle Gestionnaire de Contacts<span class="text-caption"> by Thalamus</span>
        </q-toolbar-title>
        
        <div>
          <q-btn v-if="!authStore.isAuthenticated" to="/login" flat label="Connexion" />
          <q-btn v-else flat round>
            <q-avatar size="26px">
              <q-icon name="person" />
            </q-avatar>
            
            <q-menu>
              <q-list style="min-width: 150px">
                <q-item v-if="authStore.isAdmin" clickable to="/admin/dashboard">
                  <q-item-section>Tableau de bord admin</q-item-section>
                </q-item>
                
                <q-item v-if="!authStore.isAdmin" clickable to="/dashboard">
                  <q-item-section>Tableau de bord</q-item-section>
                </q-item>
                
                <q-item clickable @click="logout">
                  <q-item-section>Déconnexion</q-item-section>
                </q-item>
              </q-list>
            </q-menu>
          </q-btn>
        </div>
      </q-toolbar>
    </q-header>

    <!-- Removed RealtimeNotifications component temporarily -->

    <q-drawer v-if="authStore.isAuthenticated" v-model="leftDrawerOpen" show-if-above bordered>
      <q-list>
        <q-item-label header>Navigation</q-item-label>

        <q-item clickable v-ripple to="/" exact>
          <q-item-section avatar>
            <q-icon name="home" />
          </q-item-section>
          <q-item-section>Accueil</q-item-section>
        </q-item>
        
        <q-item v-if="!authStore.isAdmin" clickable v-ripple to="/dashboard">
          <q-item-section avatar>
            <q-icon name="dashboard" />
          </q-item-section>
          <q-item-section>Tableau de bord</q-item-section>
        </q-item>

        <q-item clickable v-ripple to="/segment">
          <q-item-section avatar>
            <q-icon name="phone" />
          </q-item-section>
          <q-item-section>Segmentation individuelle</q-item-section>
        </q-item>

        <q-item clickable v-ripple to="/batch">
          <q-item-section avatar>
            <q-icon name="list" />
          </q-item-section>
          <q-item-section>Traitement par lot</q-item-section>
        </q-item>

        <q-item clickable v-ripple to="/segments">
          <q-item-section avatar>
            <q-icon name="settings" />
          </q-item-section>
          <q-item-section>Gestion des segments</q-item-section>
        </q-item>

        <q-item clickable v-ripple to="/contacts">
          <q-item-section avatar>
            <q-icon name="people" />
          </q-item-section>
          <q-item-section>Contacts</q-item-section>
        </q-item>

        <q-item clickable v-ripple to="/contact-groups">
          <q-item-section avatar>
            <q-icon name="group_work" />
          </q-item-section>
          <q-item-section>Groupes de contacts</q-item-section>
        </q-item>

        <q-item clickable v-ripple to="/sms">
          <q-item-section avatar>
            <q-icon name="message" />
          </q-item-section>
          <q-item-section>Envoi de SMS</q-item-section>
        </q-item>

        <q-item clickable v-ripple to="/sms/history">
          <q-item-section avatar>
            <q-icon name="history" />
          </q-item-section>
          <q-item-section>Historique SMS</q-item-section>
        </q-item>

        <q-item clickable v-ripple to="/sms/templates">
          <q-item-section avatar>
            <q-icon name="description" />
          </q-item-section>
          <q-item-section>Modèles SMS</q-item-section>
        </q-item>

        <q-item clickable v-ripple to="/import">
          <q-item-section avatar>
            <q-icon name="upload" />
          </q-item-section>
          <q-item-section>Import/Export</q-item-section>
        </q-item>
        
        <q-separator />
        
        <q-item-label header v-if="authStore.isAdmin">Administration</q-item-label>
        
        <q-item v-if="authStore.isAdmin" clickable v-ripple to="/admin/dashboard">
          <q-item-section avatar>
            <q-icon name="dashboard" />
          </q-item-section>
          <q-item-section>Tableau de bord</q-item-section>
        </q-item>
        
        <q-item v-if="authStore.isAdmin" clickable v-ripple to="/admin/users">
          <q-item-section avatar>
            <q-icon name="people" />
          </q-item-section>
          <q-item-section>Gestion des utilisateurs</q-item-section>
        </q-item>
        
        <q-item v-if="authStore.isAdmin" clickable v-ripple to="/admin/sender-names">
          <q-item-section avatar>
            <q-icon name="badge" />
          </q-item-section>
          <q-item-section>Noms d'expéditeur</q-item-section>
        </q-item>
        
        <q-item v-if="authStore.isAdmin" clickable v-ripple to="/admin/sms-orders">
          <q-item-section avatar>
            <q-icon name="shopping_cart" />
          </q-item-section>
          <q-item-section>Commandes SMS</q-item-section>
        </q-item>
        
        <q-item v-if="authStore.isAdmin" clickable v-ripple to="/admin/orange-api-config">
          <q-item-section avatar>
            <q-icon name="settings" />
          </q-item-section>
          <q-item-section>Config API Orange</q-item-section>
        </q-item>
      </q-list>
    </q-drawer>

    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useUserStore } from "./stores/userStore";
import { useAuthStore } from "./stores/authStore";
// Removed RealtimeNotifications import

const router = useRouter();
const leftDrawerOpen = ref(true);
const userStore = useUserStore();
const authStore = useAuthStore();

// Fonction de déconnexion
const logout = async () => {
  await authStore.logout();
  router.push('/login');
};

// Initialiser l'authentification au démarrage de l'application
onMounted(async () => {
  // Initialiser l'authentification
  // Cela va également charger les informations de l'utilisateur connecté dans userStore.currentUser
  await authStore.init();
});
</script>

<style>
/* Global styles */
</style>
