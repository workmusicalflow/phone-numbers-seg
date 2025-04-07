<template>
  <div class="user-details">
    <q-card class="q-pa-md">
      <q-card-section>
        <div class="text-h5">Détails de l'utilisateur</div>
      </q-card-section>

      <q-separator />

      <q-card-section v-if="loading">
        <div class="text-center">
          <q-spinner color="primary" size="3em" />
          <div class="q-mt-sm">Chargement des détails de l'utilisateur...</div>
        </div>
      </q-card-section>

      <q-card-section v-else-if="error">
        <div class="text-negative">
          <q-icon name="error" size="2em" />
          <div class="q-mt-sm">{{ error }}</div>
        </div>
      </q-card-section>

      <q-card-section v-else-if="user">
        <div class="row q-col-gutter-md">
          <div class="col-12 col-md-6">
            <q-list bordered separator>
              <q-item>
                <q-item-section>
                  <q-item-label caption>Nom d'utilisateur</q-item-label>
                  <q-item-label>{{ user.username }}</q-item-label>
                </q-item-section>
              </q-item>

              <q-item>
                <q-item-section>
                  <q-item-label caption>Email</q-item-label>
                  <q-item-label>{{ user.email || 'Non défini' }}</q-item-label>
                </q-item-section>
              </q-item>

              <q-item>
                <q-item-section>
                  <q-item-label caption>Crédits SMS</q-item-label>
                  <q-item-label>{{ user.smsCredit }}</q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-btn color="primary" label="Ajouter des crédits" @click="showAddCreditDialog = true" />
                </q-item-section>
              </q-item>

              <q-item>
                <q-item-section>
                  <q-item-label caption>Limite SMS</q-item-label>
                  <q-item-label>{{ user.smsLimit || 'Illimité' }}</q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-btn color="secondary" label="Modifier la limite" @click="showLimitDialog = true" />
                </q-item-section>
              </q-item>

              <q-item>
                <q-item-section>
                  <q-item-label caption>Date de création</q-item-label>
                  <q-item-label>{{ formatDate(user.createdAt) }}</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </div>

          <div class="col-12 col-md-6">
            <q-card class="q-mb-md">
              <q-card-section>
                <div class="text-h6">Noms d'expéditeur</div>
              </q-card-section>

              <q-card-section v-if="loadingSenderNames">
                <q-spinner color="primary" size="2em" />
              </q-card-section>

              <q-card-section v-else-if="senderNames.length === 0">
                <div class="text-grey">Aucun nom d'expéditeur</div>
              </q-card-section>

              <q-card-section v-else>
                <q-list bordered separator>
                  <q-item v-for="senderName in senderNames" :key="senderName.id">
                    <q-item-section>
                      <q-item-label>{{ senderName.name }}</q-item-label>
                      <q-item-label caption>
                        <q-badge :color="getStatusColor(senderName.status)">
                          {{ getStatusLabel(senderName.status) }}
                        </q-badge>
                      </q-item-label>
                    </q-item-section>
                    <q-item-section side v-if="senderName.status === 'pending'">
                      <q-btn flat round color="positive" icon="check" @click="approveSenderName(senderName.id)" />
                      <q-btn flat round color="negative" icon="close" @click="rejectSenderName(senderName.id)" />
                    </q-item-section>
                  </q-item>
                </q-list>
              </q-card-section>
            </q-card>

            <q-card>
              <q-card-section>
                <div class="text-h6">Commandes de crédits SMS</div>
              </q-card-section>

              <q-card-section v-if="loadingOrders">
                <q-spinner color="primary" size="2em" />
              </q-card-section>

              <q-card-section v-else-if="orders.length === 0">
                <div class="text-grey">Aucune commande</div>
              </q-card-section>

              <q-card-section v-else>
                <q-list bordered separator>
                  <q-item v-for="order in orders" :key="order.id">
                    <q-item-section>
                      <q-item-label>{{ order.quantity }} crédits</q-item-label>
                      <q-item-label caption>
                        <q-badge :color="order.status === 'completed' ? 'positive' : 'warning'">
                          {{ order.status === 'completed' ? 'Complété' : 'En attente' }}
                        </q-badge>
                        {{ formatDate(order.createdAt) }}
                      </q-item-label>
                    </q-item-section>
                    <q-item-section side v-if="order.status === 'pending'">
                      <q-btn flat round color="positive" icon="check" @click="completeOrder(order.id)" />
                    </q-item-section>
                  </q-item>
                </q-list>
              </q-card-section>
            </q-card>
          </div>
        </div>
      </q-card-section>

      <q-card-actions align="right">
        <q-btn flat color="primary" label="Retour" to="/admin/users" />
      </q-card-actions>
    </q-card>

    <!-- Dialogue pour ajouter des crédits -->
    <q-dialog v-model="showAddCreditDialog">
      <q-card style="min-width: 350px">
        <q-card-section>
          <div class="text-h6">Ajouter des crédits SMS</div>
        </q-card-section>

        <q-card-section>
          <q-input
            v-model.number="creditAmount"
            type="number"
            label="Montant"
            :rules="[val => val > 0 || 'Le montant doit être supérieur à 0']"
          />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Annuler" color="primary" v-close-popup />
          <q-btn
            label="Ajouter"
            color="primary"
            :disable="creditAmount <= 0"
            @click="addCredit"
            v-close-popup
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Dialogue pour modifier la limite SMS -->
    <q-dialog v-model="showLimitDialog">
      <q-card style="min-width: 350px">
        <q-card-section>
          <div class="text-h6">Modifier la limite SMS</div>
        </q-card-section>

        <q-card-section>
          <q-input
            v-model.number="smsLimit"
            type="number"
            label="Limite"
            hint="Laissez vide pour illimité"
          />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Annuler" color="primary" v-close-popup />
          <q-btn
            label="Modifier"
            color="primary"
            @click="updateSmsLimit"
            v-close-popup
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { date } from 'quasar';
import { useUserStore } from '../stores/userStore';
import { useSenderNameStore } from '../stores/senderNameStore';
import { useSMSOrderStore } from '../stores/smsOrderStore';
import { useNotification } from '../services/NotificationService';

// Définition des interfaces pour le typage
interface User {
  id: number;
  username: string;
  email: string | null;
  smsCredit: number;
  smsLimit: number | null;
  createdAt: string;
  updatedAt: string;
}

interface SenderName {
  id: number;
  userId: number;
  name: string;
  status: 'pending' | 'approved' | 'rejected';
  createdAt: string;
  updatedAt: string;
}

interface SMSOrder {
  id: number;
  userId: number;
  quantity: number;
  status: 'pending' | 'completed';
  createdAt: string;
  updatedAt: string;
}

export default defineComponent({
  name: 'UserDetails',
  setup() {
    const route = useRoute();
    const userStore = useUserStore();
    const senderNameStore = useSenderNameStore();
    const smsOrderStore = useSMSOrderStore();
    // Utiliser les méthodes du service de notification
    const { showSuccess, showError } = useNotification();

    const userId = parseInt(route.params.id as string);
    const user = ref<User | null>(null);
    const loading = ref(true);
    const error = ref('');

    const senderNames = ref<SenderName[]>([]);
    const loadingSenderNames = ref(true);

    const orders = ref<SMSOrder[]>([]);
    const loadingOrders = ref(true);

    const showAddCreditDialog = ref(false);
    const creditAmount = ref(0);

    const showLimitDialog = ref(false);
    const smsLimit = ref<number | null>(null);

    onMounted(async () => {
      try {
        loading.value = true;
        const userData = await userStore.getUserById(userId);
        if (userData) {
          user.value = userData as User;
          smsLimit.value = userData.smsLimit;
        }
        loading.value = false;
      } catch (err) {
        loading.value = false;
        error.value = 'Erreur lors du chargement des détails de l\'utilisateur';
        console.error(err);
      }

      try {
        loadingSenderNames.value = true;
        const senderNamesData = await senderNameStore.getSenderNamesByUser(userId);
        if (senderNamesData) {
          senderNames.value = senderNamesData as SenderName[];
        }
        loadingSenderNames.value = false;
      } catch (err) {
        loadingSenderNames.value = false;
        console.error('Erreur lors du chargement des noms d\'expéditeur:', err);
      }

      try {
        loadingOrders.value = true;
        const ordersData = await smsOrderStore.fetchSMSOrdersByUser(userId);
        if (ordersData) {
          orders.value = ordersData as SMSOrder[];
        }
        loadingOrders.value = false;
      } catch (err) {
        loadingOrders.value = false;
        console.error('Erreur lors du chargement des commandes:', err);
      }
    });

    const formatDate = (dateString: string) => {
      return date.formatDate(dateString, 'DD/MM/YYYY HH:mm');
    };

    const getStatusColor = (status: string) => {
      switch (status) {
        case 'approved':
          return 'positive';
        case 'rejected':
          return 'negative';
        default:
          return 'warning';
      }
    };

    const getStatusLabel = (status: string) => {
      switch (status) {
        case 'approved':
          return 'Approuvé';
        case 'rejected':
          return 'Rejeté';
        default:
          return 'En attente';
      }
    };

    const addCredit = async () => {
      try {
        await userStore.addCredits(userId, creditAmount.value);
        const userData = await userStore.getUserById(userId);
        if (userData) {
          user.value = userData as User;
        }
        showSuccess(`${creditAmount.value} crédits ajoutés avec succès`);
        creditAmount.value = 0;
      } catch (err) {
        showError('Erreur lors de l\'ajout de crédits');
        console.error(err);
      }
    };

    const updateSmsLimit = async () => {
      try {
        // Convertir null en undefined pour correspondre à la signature de la méthode
        const limitValue = smsLimit.value === null ? undefined : smsLimit.value;
        await userStore.updateUserLimit(userId, limitValue);
        const userData = await userStore.getUserById(userId);
        if (userData) {
          user.value = userData as User;
        }
        showSuccess('Limite SMS mise à jour avec succès');
      } catch (err) {
        showError('Erreur lors de la mise à jour de la limite SMS');
        console.error(err);
      }
    };

    const approveSenderName = async (senderNameId: number) => {
      try {
        await senderNameStore.approveSenderName(senderNameId);
        const senderNamesData = await senderNameStore.getSenderNamesByUser(userId);
        if (senderNamesData) {
          senderNames.value = senderNamesData as SenderName[];
        }
        showSuccess('Nom d\'expéditeur approuvé avec succès');
      } catch (err) {
        showError('Erreur lors de l\'approbation du nom d\'expéditeur');
        console.error(err);
      }
    };

    const rejectSenderName = async (senderNameId: number) => {
      try {
        await senderNameStore.rejectSenderName(senderNameId);
        const senderNamesData = await senderNameStore.getSenderNamesByUser(userId);
        if (senderNamesData) {
          senderNames.value = senderNamesData as SenderName[];
        }
        showSuccess('Nom d\'expéditeur rejeté');
      } catch (err) {
        showError('Erreur lors du rejet du nom d\'expéditeur');
        console.error(err);
      }
    };

    const completeOrder = async (orderId: number) => {
      try {
        await smsOrderStore.completeOrder(orderId);
        const ordersData = await smsOrderStore.fetchSMSOrdersByUser(userId);
        if (ordersData) {
          orders.value = ordersData as SMSOrder[];
        }
        const userData = await userStore.getUserById(userId);
        if (userData) {
          user.value = userData as User;
        }
        showSuccess('Commande complétée avec succès');
      } catch (err) {
        showError('Erreur lors de la complétion de la commande');
        console.error(err);
      }
    };

    return {
      user,
      loading,
      error,
      senderNames,
      loadingSenderNames,
      orders,
      loadingOrders,
      showAddCreditDialog,
      creditAmount,
      showLimitDialog,
      smsLimit,
      formatDate,
      getStatusColor,
      getStatusLabel,
      addCredit,
      updateSmsLimit,
      approveSenderName,
      rejectSenderName,
      completeOrder
    };
  }
});
</script>

<style scoped>
.user-details {
  max-width: 1200px;
  margin: 0 auto;
}
</style>
