<template>
  <div class="realtime-notifications">
    <q-banner v-if="connectionStatus === 'connecting'" class="bg-warning text-white">
      <template v-slot:avatar>
        <q-icon name="sync" />
      </template>
      Connexion au système de notification en cours...
    </q-banner>
    
    <q-banner v-if="connectionStatus === 'disconnected'" class="bg-negative text-white">
      <template v-slot:avatar>
        <q-icon name="signal_wifi_off" />
      </template>
      Déconnecté du système de notification. 
      <template v-slot:action>
        <q-btn flat label="Reconnecter" @click="connect" />
      </template>
    </q-banner>
    
    <div class="notification-container">
      <transition-group name="notification-list" tag="div">
        <div v-for="notification in notifications" :key="notification.id" class="notification-item">
          <q-card :class="getNotificationClass(notification.type)" class="notification-card">
            <q-card-section class="row items-center no-wrap">
              <div class="col-auto">
                <q-icon :name="getNotificationIcon(notification.type)" size="md" />
              </div>
              <div class="col">
                <div class="text-subtitle1">{{ notification.message }}</div>
                <div class="text-caption">{{ formatDate(notification.timestamp) }}</div>
              </div>
              <div class="col-auto">
                <q-btn flat round icon="close" size="sm" @click="removeNotification(notification.id)" />
              </div>
            </q-card-section>
            
            <q-card-section v-if="notification.data && Object.keys(notification.data).length > 0">
              <q-expansion-item
                switch-toggle-side
                dense
                label="Détails"
                header-class="text-caption"
              >
                <q-card>
                  <q-card-section>
                    <pre class="notification-details">{{ JSON.stringify(notification.data, null, 2) }}</pre>
                  </q-card-section>
                </q-card>
              </q-expansion-item>
            </q-card-section>
          </q-card>
        </div>
      </transition-group>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useUserStore } from '../stores/userStore';
import { date } from 'quasar';

// Types
interface Notification {
  id: string;
  type: 'info' | 'success' | 'warning' | 'error' | 'admin_event';
  message: string;
  timestamp: string;
  data?: Record<string, any>;
}

// Stores
const userStore = useUserStore();

// État local
const notifications = ref<Notification[]>([]);
const connectionStatus = ref<'connected' | 'connecting' | 'disconnected'>('connecting');
const pusherInstance = ref<any>(null);
const maxNotifications = ref(5);

// Computed
const userId = computed(() => userStore.currentUser?.id || 0);
const isAdmin = computed(() => userStore.isAdmin);

// Méthodes
function connect() {
  connectionStatus.value = 'connecting';
  
  // Simuler une connexion réussie après 1 seconde
  setTimeout(() => {
    connectionStatus.value = 'connected';
    
    // Ajouter une notification de connexion réussie
    addNotification({
      id: generateId(),
      type: 'info',
      message: 'Connecté au système de notification en temps réel',
      timestamp: new Date().toISOString()
    });
    
    // Simuler la réception de notifications en temps réel
    setupNotificationListeners();
  }, 1000);
}

function setupNotificationListeners() {
  // Dans une implémentation réelle, nous utiliserions Pusher ou une autre bibliothèque
  // pour écouter les événements en temps réel
  
  // Simuler la réception d'une notification toutes les 30 secondes
  const interval = setInterval(() => {
    // Générer un type aléatoire
    const types = ['info', 'success', 'warning', 'error', 'admin_event'];
    const randomType = types[Math.floor(Math.random() * types.length)] as 'info' | 'success' | 'warning' | 'error' | 'admin_event';
    
    // Générer un message aléatoire
    const messages = [
      'Nouvelle commande de crédits SMS',
      'Demande de nom d\'expéditeur en attente',
      'Utilisateur créé avec succès',
      'Erreur lors de l\'envoi de SMS',
      'Crédits SMS ajoutés à un utilisateur'
    ];
    const randomMessage = messages[Math.floor(Math.random() * messages.length)];
    
    // Ajouter la notification
    addNotification({
      id: generateId(),
      type: randomType,
      message: randomMessage,
      timestamp: new Date().toISOString(),
      data: {
        event: 'simulated',
        random: Math.random()
      }
    });
  }, 30000);
  
  // Nettoyer l'intervalle lors du démontage du composant
  onUnmounted(() => {
    clearInterval(interval);
  });
}

function addNotification(notification: Notification) {
  // Ajouter la notification au début du tableau
  notifications.value.unshift(notification);
  
  // Limiter le nombre de notifications affichées
  if (notifications.value.length > maxNotifications.value) {
    notifications.value = notifications.value.slice(0, maxNotifications.value);
  }
  
  // Supprimer automatiquement la notification après 10 secondes
  setTimeout(() => {
    removeNotification(notification.id);
  }, 10000);
}

function removeNotification(id: string) {
  notifications.value = notifications.value.filter(n => n.id !== id);
}

function getNotificationClass(type: string): string {
  switch (type) {
    case 'success':
      return 'bg-positive text-white';
    case 'warning':
      return 'bg-warning text-white';
    case 'error':
      return 'bg-negative text-white';
    case 'admin_event':
      return 'bg-purple text-white';
    case 'info':
    default:
      return 'bg-info text-white';
  }
}

function getNotificationIcon(type: string): string {
  switch (type) {
    case 'success':
      return 'check_circle';
    case 'warning':
      return 'warning';
    case 'error':
      return 'error';
    case 'admin_event':
      return 'admin_panel_settings';
    case 'info':
    default:
      return 'info';
  }
}

function formatDate(dateString: string): string {
  return date.formatDate(dateString, 'DD/MM/YYYY HH:mm:ss');
}

function generateId(): string {
  return Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
}

// Cycle de vie
onMounted(() => {
  connect();
});

onUnmounted(() => {
  // Nettoyer les ressources
  if (pusherInstance.value) {
    // Déconnecter Pusher
    // pusherInstance.value.disconnect();
  }
});

// Exposer les méthodes pour les tests
defineExpose({
  addNotification,
  removeNotification
});
</script>

<style scoped>
.realtime-notifications {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 9999;
  width: 350px;
}

.notification-container {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.notification-card {
  margin-bottom: 10px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.notification-details {
  font-size: 12px;
  white-space: pre-wrap;
  word-break: break-word;
  margin: 0;
  padding: 0;
}

/* Animations */
.notification-list-enter-active,
.notification-list-leave-active {
  transition: all 0.5s ease;
}

.notification-list-enter-from {
  opacity: 0;
  transform: translateX(30px);
}

.notification-list-leave-to {
  opacity: 0;
  transform: translateX(30px);
}
</style>
