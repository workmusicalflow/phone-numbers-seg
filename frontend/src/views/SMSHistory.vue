<template>
  <q-page padding>
    <div class="sms-history-page">
      <!-- Modern Page Header -->
      <div class="page-header">
        <div class="header-content">
          <div class="header-title-section">
            <div class="title-icon-wrapper">
              <q-icon name="history" size="md" />
            </div>
            <div class="title-text">
              <h1 class="page-title">Historique des SMS</h1>
              <p class="page-subtitle">Consultez et gérez vos envois passés</p>
            </div>
          </div>
          
          <div class="header-stats">
            <div class="stat-card">
              <div class="stat-value">{{ pagination.rowsNumber }}</div>
              <div class="stat-label">Total</div>
            </div>
            <div class="stat-card">
              <div class="stat-value">{{ sentCount }}</div>
              <div class="stat-label">Envoyés</div>
            </div>
            <div class="stat-card">
              <div class="stat-value">{{ failedCount }}</div>
              <div class="stat-label">Échoués</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Modern Filters Section -->
      <div class="filters-card">
        <div class="modern-card">
          <div class="card-header sms-gradient">
            <div class="header-content">
              <q-icon name="filter_list" size="md" class="header-icon" />
              <div class="header-text">
                <h3 class="header-title">Filtres et Recherche</h3>
                <p class="header-subtitle">Affinez vos résultats</p>
              </div>
            </div>
            <div class="header-actions">
              <q-btn
                color="white"
                text-color="primary"
                icon="refresh"
                label="Actualiser"
                outline
                size="sm"
                @click="refreshData"
                :loading="loading"
                class="modern-btn"
              />
            </div>
          </div>

          <div class="card-content">
            <div class="filters-grid">
              <div class="filter-item">
                <q-input
                  v-model="filters.search"
                  label="Rechercher un numéro"
                  outlined
                  clearable
                  debounce="300"
                  @update:model-value="onFilterChange"
                  class="modern-input"
                >
                  <template v-slot:prepend>
                    <q-icon name="search" />
                  </template>
                </q-input>
              </div>

              <div class="filter-item">
                <q-select
                  v-model="filters.status"
                  :options="statusOptions"
                  label="Filtrer par statut"
                  outlined
                  clearable
                  emit-value
                  map-options
                  @update:model-value="onFilterChange"
                  class="modern-select"
                >
                  <template v-slot:prepend>
                    <q-icon name="flag" />
                  </template>
                </q-select>
              </div>

              <div class="filter-item">
                <q-select
                  v-model="filters.segment"
                  :options="segmentOptions"
                  label="Filtrer par segment"
                  outlined
                  clearable
                  emit-value
                  map-options
                  @update:model-value="onFilterChange"
                  :loading="loadingSegments"
                  class="modern-select"
                >
                  <template v-slot:prepend>
                    <q-icon name="segment" />
                  </template>
                </q-select>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- History Table Card -->
      <div class="history-card">
        <div class="modern-card">
          <div class="card-header sms-gradient">
            <div class="header-content">
              <q-icon name="table_view" size="md" class="header-icon" />
              <div class="header-text">
                <h3 class="header-title">Historique Détaillé</h3>
                <p class="header-subtitle">{{ pagination.rowsNumber }} message{{ pagination.rowsNumber !== 1 ? 's' : '' }} au total</p>
              </div>
            </div>
            <div class="header-actions">
              <q-btn
                color="white"
                text-color="primary"
                icon="file_download"
                label="Exporter"
                outline
                size="sm"
                @click="exportData"
                :disable="smsHistory.length === 0"
                class="modern-btn"
              />
            </div>
          </div>

          <div class="card-content">
            <div class="table-wrapper">
              <q-table
                :rows="smsHistory"
                :columns="columns"
                row-key="id"
                :loading="loading"
                :rows-per-page-options="[10, 20, 50, 100]"
                @request="onRequest"
                binary-state-sort
                v-model:pagination="pagination"
                flat
                class="modern-table"
              >
                <template v-slot:body-cell-status="props">
                  <q-td :props="props">
                    <q-chip
                      :class="['status-chip', getStatusChipClass(props.row.status)]"
                      text-color="white"
                      size="sm"
                    >
                      {{ getStatusLabel(props.row.status) }}
                    </q-chip>
                  </q-td>
                </template>

                <template v-slot:body-cell-message="props">
                  <q-td :props="props">
                    <div class="message-cell">
                      <div class="message-preview">
                        {{ props.row.message }}
                      </div>
                      <q-tooltip class="modern-tooltip" max-width="300px">
                        {{ props.row.message }}
                      </q-tooltip>
                    </div>
                  </q-td>
                </template>

                <template v-slot:body-cell-actions="props">
                  <q-td :props="props">
                    <div class="action-buttons">
                      <q-btn
                        flat
                        round
                        size="sm"
                        color="primary"
                        icon="visibility"
                        @click="showDetails(props.row)"
                        class="action-btn"
                      >
                        <q-tooltip>Voir les détails</q-tooltip>
                      </q-btn>
                      <q-btn
                        v-if="props.row.status === 'FAILED'"
                        flat
                        round
                        size="sm"
                        color="positive"
                        icon="replay"
                        @click="retrySms(props.row)"
                        :loading="retryingId === props.row.id"
                        class="action-btn"
                      >
                        <q-tooltip>Réessayer l'envoi</q-tooltip>
                      </q-btn>
                    </div>
                  </q-td>
                </template>

                <template v-slot:loading>
                  <q-inner-loading showing color="primary">
                    <q-spinner-dots size="50px" color="primary" />
                  </q-inner-loading>
                </template>

                <template v-slot:no-data>
                  <div class="no-data-display">
                    <q-icon name="sms" size="4rem" color="grey-5" />
                    <h4 class="no-data-title">Aucun SMS trouvé</h4>
                    <p class="no-data-text">
                      {{ filters.search || filters.status || filters.segment 
                         ? 'Aucun résultat pour ces filtres' 
                         : 'Aucun SMS dans l\'historique' }}
                    </p>
                    <q-btn
                      v-if="filters.search || filters.status || filters.segment"
                      color="primary"
                      label="Effacer les filtres"
                      @click="clearFilters"
                      flat
                    />
                  </div>
                </template>
              </q-table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modern Details Dialog -->
    <q-dialog v-model="detailsDialog" persistent>
      <div class="details-dialog">
        <div class="modern-card">
          <div class="card-header sms-gradient">
            <div class="header-content">
              <q-icon name="info" size="md" class="header-icon" />
              <div class="header-text">
                <h3 class="header-title">Détails du SMS</h3>
                <p class="header-subtitle">Informations détaillées</p>
              </div>
            </div>
            <div class="header-actions">
              <q-btn
                color="white"
                text-color="primary"
                icon="close"
                round
                flat
                size="sm"
                v-close-popup
                class="close-btn"
              />
            </div>
          </div>

          <div class="card-content" v-if="selectedSms">
            <!-- Status Section -->
            <div class="detail-section">
              <div class="section-header">
                <q-icon name="flag" class="section-icon" />
                <h4 class="section-title">Statut</h4>
              </div>
              <div class="status-display">
                <q-chip
                  :class="['status-chip-large', getStatusChipClass(selectedSms.status)]"
                  text-color="white"
                  size="md"
                >
                  <q-icon :name="getStatusIcon(selectedSms.status)" size="sm" class="q-mr-sm" />
                  {{ getStatusLabel(selectedSms.status) }}
                </q-chip>
              </div>
            </div>

            <!-- Contact Information -->
            <div class="detail-section">
              <div class="section-header">
                <q-icon name="contact_phone" class="section-icon" />
                <h4 class="section-title">Contact</h4>
              </div>
              <div class="detail-grid">
                <div class="detail-item">
                  <div class="detail-label">Numéro de téléphone</div>
                  <div class="detail-value">{{ selectedSms.phoneNumber }}</div>
                </div>
                <div class="detail-item" v-if="selectedSms.senderName">
                  <div class="detail-label">Expéditeur</div>
                  <div class="detail-value">{{ selectedSms.senderName }}</div>
                </div>
              </div>
            </div>

            <!-- Message Content -->
            <div class="detail-section">
              <div class="section-header">
                <q-icon name="message" class="section-icon" />
                <h4 class="section-title">Message</h4>
              </div>
              <div class="message-content">
                <q-card class="message-card">
                  <q-card-section>
                    <p class="message-text">{{ selectedSms.message }}</p>
                  </q-card-section>
                </q-card>
              </div>
            </div>

            <!-- Technical Details -->
            <div class="detail-section">
              <div class="section-header">
                <q-icon name="settings" class="section-icon" />
                <h4 class="section-title">Détails Techniques</h4>
              </div>
              <div class="detail-grid">
                <div class="detail-item">
                  <div class="detail-label">Date d'envoi</div>
                  <div class="detail-value">{{ formatDateTime(selectedSms.createdAt) }}</div>
                </div>
                <div class="detail-item" v-if="selectedSms.messageId">
                  <div class="detail-label">ID du message</div>
                  <div class="detail-value detail-code">{{ selectedSms.messageId }}</div>
                </div>
                <div class="detail-item" v-if="selectedSms.segment">
                  <div class="detail-label">Segment</div>
                  <div class="detail-value">{{ selectedSms.segment.name }}</div>
                </div>
              </div>
            </div>

            <!-- Error Information -->
            <div class="detail-section" v-if="selectedSms.errorMessage">
              <div class="section-header">
                <q-icon name="error" class="section-icon error-icon" />
                <h4 class="section-title">Erreur</h4>
              </div>
              <div class="error-content">
                <q-card class="error-card">
                  <q-card-section>
                    <p class="error-text">{{ selectedSms.errorMessage }}</p>
                  </q-card-section>
                </q-card>
              </div>
            </div>
          </div>

          <div class="dialog-actions">
            <q-btn
              v-if="selectedSms && selectedSms.status === 'FAILED'"
              color="positive"
              icon="replay"
              label="Réessayer l'envoi"
              @click="retrySms(selectedSms)"
              :loading="retryingId === selectedSms.id"
              class="action-btn-primary"
            />
            <q-btn
              color="grey-7"
              label="Fermer"
              v-close-popup
              class="action-btn-secondary"
            />
          </div>
        </div>
      </div>
    </q-dialog>
  </q-page>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { gql } from "@apollo/client/core";
import { useQuasar } from "quasar";
import { exportFile } from "quasar";
import { provideApolloClient } from "@vue/apollo-composable";
import { ApolloClient, InMemoryCache } from "@apollo/client/core";

const $q = useQuasar();

// Créer un client Apollo
const apolloClient = new ApolloClient({
  uri: "/graphql.php",
  cache: new InMemoryCache(),
});

// Fournir le client Apollo aux composants
provideApolloClient(apolloClient);

// État de l'interface
const loading = ref(false);
const loadingSegments = ref(false);
const smsHistory = ref<any[]>([]);
const segments = ref<any[]>([]);
const detailsDialog = ref(false);
const selectedSms = ref<any>(null);
const retryingId = ref<number | null>(null);

// Pagination et filtres
const pagination = ref({
  sortBy: "createdAt",
  descending: true,
  page: 1,
  rowsPerPage: 20,
  rowsNumber: 0,
});

const filters = ref({
  search: "",
  status: null as string | null,
  segment: null as number | null,
});

// Options pour les filtres
const statusOptions = [
  { label: "Envoyé", value: "SENT" },
  { label: "Échoué", value: "FAILED" },
  { label: "En attente", value: "PENDING" },
];

const segmentOptions = computed(() => {
  return segments.value.map((segment) => ({
    label: segment.name,
    value: segment.id,
  }));
});

// Configuration de la table
const columns = [
  {
    name: "phoneNumber",
    label: "Numéro",
    field: "phoneNumber",
    sortable: true,
    align: "left" as const,
  },
  {
    name: "message",
    label: "Message",
    field: "message",
    sortable: false,
    align: "left" as const,
  },
  {
    name: "status",
    label: "Statut",
    field: "status",
    sortable: true,
    align: "center" as const,
  },
  {
    name: "createdAt",
    label: "Date",
    field: "createdAt",
    sortable: true,
    align: "left" as const,
    format: (val: string) => new Date(val).toLocaleString(),
  },
  {
    name: "actions",
    label: "Actions",
    field: "actions",
    sortable: false,
    align: "center" as const,
  },
];

// Récupération de l'historique des SMS
const fetchSmsHistory = async () => {
  loading.value = true;
  try {
    // Utiliser une requête GraphQL directe avec les filtres
    const result = await apolloClient.query({
      query: gql`
        query GetSmsHistory(
          $limit: Int!
          $offset: Int!
          $status: String
          $search: String
          $segmentId: ID
        ) {
          smsHistory(
            limit: $limit
            offset: $offset
            status: $status
            search: $search
            segmentId: $segmentId
          ) {
            id
            phoneNumber
            message
            status
            messageId
            errorMessage
            senderAddress
            senderName
            createdAt
            segment {
              id
              name
            }
          }
          smsHistoryCount(
            status: $status
            search: $search
            segmentId: $segmentId
          )
        }
      `,
      variables: {
        limit: pagination.value.rowsPerPage,
        offset: (pagination.value.page - 1) * pagination.value.rowsPerPage,
        // Ajouter les filtres aux variables
        status: filters.value.status || null, // Envoyer null si pas de filtre
        search: filters.value.search || null,
        segmentId: filters.value.segment || null,
      },
      fetchPolicy: "network-only", // Important pour obtenir les données fraîches
    });

    const data = result.data;

    // Les données sont maintenant filtrées côté serveur
    smsHistory.value = data.smsHistory;
    // Mettre à jour le nombre total de lignes basé sur le compte filtré du backend
    pagination.value.rowsNumber = data.smsHistoryCount || 0;
  } catch (error) {
    console.error("Error fetching SMS history:", error);
    // Utiliser la référence $q déjà importée
    $q.notify({
      color: "negative",
      message: "Erreur lors du chargement de l'historique",
      icon: "error",
    });
  } finally {
    loading.value = false;
  }
};

// Récupération des segments disponibles
const fetchSegments = async () => {
  loadingSegments.value = true;
  try {
    const result = await apolloClient.query({
      query: gql`
        query GetSegmentsForSMS {
          segmentsForSMS {
            id
            name
            description
            phoneNumberCount
          }
        }
      `,
      fetchPolicy: "network-only",
    });

    segments.value = result.data.segmentsForSMS;
  } catch (error) {
    console.error("Error fetching segments:", error);
    $q.notify({
      color: "negative",
      message: "Erreur lors du chargement des segments",
      icon: "error",
    });
  } finally {
    loadingSegments.value = false;
  }
};

// Gestion des filtres
const onFilterChange = () => {
  pagination.value.page = 1;
  fetchSmsHistory();
};

// Gestion de la pagination
const onRequest = (props: any) => {
  const { page, rowsPerPage, sortBy, descending } = props.pagination;
  pagination.value.page = page;
  pagination.value.rowsPerPage = rowsPerPage;
  pagination.value.sortBy = sortBy;
  pagination.value.descending = descending;
  fetchSmsHistory();
};

// Rafraîchir les données
const refreshData = () => {
  fetchSmsHistory();
};

// Afficher les détails d'un SMS
const showDetails = (sms: any) => {
  selectedSms.value = sms;
  detailsDialog.value = true;
};

// Réessayer l'envoi d'un SMS échoué
const retrySms = async (sms: any) => {
  retryingId.value = sms.id;
  try {
    const result = await apolloClient.mutate({
      mutation: gql`
        mutation RetrySms($id: ID!) {
          retrySms(id: $id) {
            id
            phoneNumber
            message
            status
            createdAt
          }
        }
      `,
      variables: {
        id: sms.id,
      },
    });

    const data = result.data;

    // Notification
    $q.notify({
      color: data.retrySms.status === "SENT" ? "positive" : "negative",
      message:
        data.retrySms.status === "SENT"
          ? "SMS renvoyé avec succès"
          : "Échec du renvoi du SMS",
      icon: data.retrySms.status === "SENT" ? "check_circle" : "error",
    });

    // Fermer le dialogue si ouvert
    if (detailsDialog.value) {
      detailsDialog.value = false;
    }

    // Rafraîchir l'historique
    await fetchSmsHistory();
  } catch (error) {
    console.error("Error retrying SMS:", error);
    $q.notify({
      color: "negative",
      message: "Erreur lors du renvoi du SMS",
      icon: "error",
    });
  } finally {
    retryingId.value = null;
  }
};

// Exporter les données
const exportData = () => {
  // Préparer les données pour l'export
  const exportData = smsHistory.value.map((sms) => ({
    Numéro: sms.phoneNumber,
    Message: sms.message,
    Statut: sms.status,
    Date: new Date(sms.createdAt).toLocaleString(),
    "ID Message": sms.messageId || "",
    "Message d'erreur": sms.errorMessage || "",
    Expéditeur: sms.senderName || "",
    Segment: sms.segment ? sms.segment.name : "",
  }));

  // Convertir en CSV
  const content = [
    Object.keys(exportData[0]).join(","),
    ...exportData.map((row) =>
      Object.values(row)
        .map((val) => `"${val.toString().replace(/"/g, '""')}"`)
        .join(",")
    ),
  ].join("\n");

  // Télécharger le fichier
  const status = exportFile(
    `sms-history-${new Date().toISOString().slice(0, 10)}.csv`,
    content,
    {
      mimeType: "text/csv",
    }
  );

  if (status !== true) {
    $q.notify({
      color: "negative",
      message: "Erreur lors de l'export des données",
      icon: "error",
    });
  }
};

// Modern Helper Functions
const getStatusChipClass = (status: string) => {
  switch (status.toUpperCase()) {
    case 'SENT':
      return 'status-success';
    case 'FAILED':
      return 'status-error';
    default:
      return 'status-warning';
  }
};

const getStatusLabel = (status: string) => {
  switch (status.toUpperCase()) {
    case 'SENT':
      return 'Envoyé';
    case 'FAILED':
      return 'Échoué';
    case 'PENDING':
      return 'En attente';
    default:
      return status;
  }
};

const getStatusIcon = (status: string) => {
  switch (status.toUpperCase()) {
    case 'SENT':
      return 'check_circle';
    case 'FAILED':
      return 'error';
    case 'PENDING':
      return 'schedule';
    default:
      return 'help';
  }
};

const formatDateTime = (dateString: string) => {
  const date = new Date(dateString);
  return date.toLocaleString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  });
};

// Clear filters function
const clearFilters = () => {
  filters.value.search = '';
  filters.value.status = null;
  filters.value.segment = null;
  onFilterChange();
};

// Computed properties for statistics
const sentCount = computed(() => {
  return smsHistory.value.filter(sms => sms.status === 'SENT').length;
});

const failedCount = computed(() => {
  return smsHistory.value.filter(sms => sms.status === 'FAILED').length;
});

// Legacy compatibility
const getStatusColor = (status: string) => {
  switch (status) {
    case "SENT":
      return "positive";
    case "FAILED":
      return "negative";
    case "PENDING":
      return "warning";
    default:
      return "grey";
  }
};

// Initialisation
onMounted(() => {
  fetchSmsHistory();
  fetchSegments();
});
</script>

<style lang="scss" scoped>
// SMS History Color Palette
$sms-primary: #0d47a1;
$sms-secondary: #1976d2;
$sms-accent: #42a5f5;
$sms-light: #e3f2fd;

// Design System Integration
.sms-history-page {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0;
}

// Modern Page Header
.page-header {
  background: linear-gradient(135deg, $sms-primary 0%, $sms-secondary 100%);
  border-radius: 16px;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 8px 32px rgba(13, 71, 161, 0.2);
  
  .header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
    
    .header-title-section {
      display: flex;
      align-items: center;
      gap: 1.5rem;
      
      .title-icon-wrapper {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 16px;
        padding: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        
        .q-icon {
          color: white;
        }
      }
      
      .title-text {
        color: white;
        
        .page-title {
          font-size: 2rem;
          font-weight: 700;
          margin: 0 0 0.5rem 0;
          line-height: 1.2;
        }
        
        .page-subtitle {
          font-size: 1.1rem;
          margin: 0;
          opacity: 0.9;
          font-weight: 400;
        }
      }
    }
    
    .header-stats {
      display: flex;
      gap: 1rem;
      
      .stat-card {
        background: rgba(255, 255, 255, 0.15);
        border-radius: 12px;
        padding: 1rem 1.5rem;
        text-align: center;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        min-width: 80px;
        
        .stat-value {
          font-size: 1.5rem;
          font-weight: 700;
          color: white;
          line-height: 1;
          margin-bottom: 0.25rem;
        }
        
        .stat-label {
          font-size: 0.8rem;
          color: rgba(255, 255, 255, 0.8);
          text-transform: uppercase;
          letter-spacing: 0.5px;
          font-weight: 500;
        }
      }
    }
  }
}

// Modern Card Structure
.modern-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
  overflow: hidden;
  transition: all 0.3s ease;
  
  &:hover {
    box-shadow: 0 12px 48px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
  }
}

// SMS Gradient
.sms-gradient {
  background: linear-gradient(135deg, $sms-primary 0%, $sms-secondary 100%);
}

// Card Header
.card-header {
  padding: 1.5rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  
  .header-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    
    .header-icon {
      color: white;
      opacity: 0.9;
    }
    
    .header-text {
      color: white;
      
      .header-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0 0 0.25rem 0;
        line-height: 1.2;
      }
      
      .header-subtitle {
        font-size: 0.9rem;
        margin: 0;
        opacity: 0.8;
        line-height: 1.1;
      }
    }
  }
  
  .header-actions {
    .modern-btn {
      border-radius: 8px;
      font-weight: 500;
      text-transform: none;
      border: 1px solid rgba(255, 255, 255, 0.3);
      
      &:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.5);
      }
    }
  }
}

.card-content {
  padding: 2rem;
}

// Filters Section
.filters-card {
  margin-bottom: 2rem;
}

.filters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
  
  .filter-item {
    .modern-input,
    .modern-select {
      .q-field__control {
        border-radius: 12px;
        height: 56px;
      }
      
      .q-field__native {
        font-size: 1rem;
      }
      
      .q-field__label {
        font-weight: 500;
      }
      
      &.q-field--focused {
        .q-field__control {
          box-shadow: 0 0 0 2px rgba(25, 118, 210, 0.2);
        }
      }
    }
  }
}

// History Table
.history-card {
  margin-bottom: 2rem;
}

.table-wrapper {
  .modern-table {
    .q-table__top {
      padding: 0;
    }
    
    .q-table thead th {
      font-weight: 600;
      font-size: 0.875rem;
      color: #333;
      background: #f8f9fa;
      border-bottom: 2px solid #e9ecef;
      padding: 1rem 0.75rem;
    }
    
    .q-table tbody td {
      border-bottom: 1px solid #f0f0f0;
      font-size: 0.875rem;
      padding: 1rem 0.75rem;
    }
    
    .q-table tbody tr:hover {
      background: #f8f9fa;
    }
    
    .status-chip {
      font-weight: 500;
      font-size: 0.75rem;
      padding: 0.25rem 0.75rem;
      border-radius: 6px;
      
      &.status-success {
        background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
      }
      
      &.status-error {
        background: linear-gradient(135deg, #f44336 0%, #ef5350 100%);
      }
      
      &.status-warning {
        background: linear-gradient(135deg, #ff9800 0%, #ffb74d 100%);
      }
    }
    
    .message-cell {
      .message-preview {
        max-width: 200px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.4;
      }
    }
    
    .action-buttons {
      display: flex;
      gap: 0.5rem;
      justify-content: center;
      
      .action-btn {
        transition: all 0.2s ease;
        
        &:hover {
          transform: scale(1.1);
        }
      }
    }
  }
  
  .no-data-display {
    text-align: center;
    padding: 4rem 2rem;
    
    .no-data-title {
      font-size: 1.25rem;
      font-weight: 600;
      margin: 1rem 0 0.5rem 0;
      color: #666;
    }
    
    .no-data-text {
      font-size: 1rem;
      color: #999;
      margin-bottom: 1.5rem;
    }
  }
}

.modern-tooltip {
  background: rgba(0, 0, 0, 0.9);
  border-radius: 8px;
  font-size: 0.875rem;
  padding: 0.75rem;
  backdrop-filter: blur(10px);
}

// Details Dialog
.details-dialog {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 9999;
  
  .modern-card {
    width: 90vw;
    max-width: 700px;
    min-width: 400px;
    margin: 0 auto;
    margin-top: 5vh;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    position: relative;
  }
  
  .card-content {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem 2rem;
  }
  
  .close-btn {
    &:hover {
      background: rgba(255, 255, 255, 0.2);
    }
  }
}

.detail-section {
  margin-bottom: 2rem;
  
  &:last-child {
    margin-bottom: 0;
  }
  
  .section-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
    
    .section-icon {
      color: $sms-primary;
      
      &.error-icon {
        color: #f44336;
      }
    }
    
    .section-title {
      font-size: 1.1rem;
      font-weight: 600;
      margin: 0;
      color: #333;
    }
  }
  
  .status-display {
    .status-chip-large {
      font-size: 1rem;
      padding: 0.75rem 1.5rem;
      border-radius: 12px;
      font-weight: 600;
      
      &.status-success {
        background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
      }
      
      &.status-error {
        background: linear-gradient(135deg, #f44336 0%, #ef5350 100%);
      }
      
      &.status-warning {
        background: linear-gradient(135deg, #ff9800 0%, #ffb74d 100%);
      }
    }
  }
  
  .detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
  }
  
  .detail-item {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    
    .detail-label {
      font-size: 0.8rem;
      color: #666;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      font-weight: 600;
      margin-bottom: 0.5rem;
    }
    
    .detail-value {
      font-size: 1rem;
      color: #333;
      font-weight: 500;
      word-break: break-word;
      
      &.detail-code {
        font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
        background: #e9ecef;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.875rem;
      }
    }
  }
  
  .message-content,
  .error-content {
    .message-card,
    .error-card {
      border-radius: 12px;
      border: 2px solid #e9ecef;
      
      .q-card-section {
        padding: 1.5rem;
      }
    }
    
    .error-card {
      border-color: #ffcdd2;
      background: linear-gradient(135deg, #ffebee 0%, #fce4ec 100%);
    }
    
    .message-text,
    .error-text {
      margin: 0;
      line-height: 1.6;
      font-size: 1rem;
      color: #333;
      white-space: pre-wrap;
      word-break: break-word;
    }
    
    .error-text {
      color: #d32f2f;
    }
  }
}

.dialog-actions {
  padding: 1.5rem 2rem;
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  border-top: 1px solid #e9ecef;
  background: #fafafa;
  
  .action-btn-primary {
    background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
    color: white;
    font-weight: 600;
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    text-transform: none;
    
    &:hover {
      box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
    }
  }
  
  .action-btn-secondary {
    color: #666;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    text-transform: none;
    
    &:hover {
      background: #f5f5f5;
    }
  }
}

// Responsive Design
@media (max-width: 1024px) {
  .header-stats {
    flex-direction: column;
    gap: 0.75rem !important;
  }
  
  .filters-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .page-header {
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    
    .header-content {
      flex-direction: column;
      gap: 1.5rem;
      
      .header-title-section {
        width: 100%;
        
        .title-icon-wrapper {
          padding: 0.75rem;
        }
        
        .title-text {
          .page-title {
            font-size: 1.5rem;
          }
          
          .page-subtitle {
            font-size: 1rem;
          }
        }
      }
      
      .header-stats {
        width: 100%;
        flex-direction: row;
        justify-content: space-around;
        
        .stat-card {
          min-width: auto;
          flex: 1;
          padding: 0.75rem 1rem;
          
          .stat-value {
            font-size: 1.25rem;
          }
          
          .stat-label {
            font-size: 0.75rem;
          }
        }
      }
    }
  }
  
  .card-header {
    padding: 1rem 1.5rem;
    flex-direction: column;
    gap: 1rem;
    
    .header-actions {
      width: 100%;
      text-align: center;
    }
  }
  
  .card-content {
    padding: 1.5rem;
  }
  
  .filters-grid {
    gap: 1rem;
  }
  
  .details-dialog {
    .modern-card {
      width: 95vw;
      min-width: auto;
      max-width: none;
      margin-top: 2vh;
      margin-left: auto;
      margin-right: auto;
    }
  }
  
  .detail-grid {
    grid-template-columns: 1fr;
  }
  
  .dialog-actions {
    flex-direction: column;
    
    .action-btn-primary,
    .action-btn-secondary {
      width: 100%;
    }
  }
}

@media (max-width: 480px) {
  .page-header {
    padding: 1rem;
    border-radius: 12px;
    
    .header-title-section {
      gap: 1rem;
      
      .title-text .page-title {
        font-size: 1.25rem;
      }
    }
    
    .header-stats {
      .stat-card {
        padding: 0.5rem 0.75rem;
        
        .stat-value {
          font-size: 1rem;
        }
      }
    }
  }
  
  .modern-card {
    border-radius: 12px;
  }
  
  .card-content {
    padding: 1rem;
  }
  
  .details-dialog {
    .modern-card {
      width: 98vw;
      margin-top: 1vh;
      margin-left: auto;
      margin-right: auto;
    }
  }
  
  .table-wrapper .modern-table {
    .q-table thead th,
    .q-table tbody td {
      padding: 0.75rem 0.5rem;
      font-size: 0.8rem;
    }
    
    .message-cell .message-preview {
      max-width: 120px;
    }
  }
}
</style>
