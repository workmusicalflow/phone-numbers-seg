<template>
  <q-page padding>
    <div class="q-pa-md">
      <h1 class="text-h4 q-mb-md">Historique des SMS</h1>

      <!-- Filtres et recherche -->
      <div class="row q-col-gutter-md q-mb-md">
        <div class="col-12 col-md-4">
          <q-input
            v-model="filters.search"
            label="Rechercher un numéro"
            dense
            clearable
            debounce="300"
            @update:model-value="onFilterChange"
          >
            <template v-slot:append>
              <q-icon name="search" />
            </template>
          </q-input>
        </div>

        <div class="col-12 col-md-3">
          <q-select
            v-model="filters.status"
            :options="statusOptions"
            label="Statut"
            dense
            clearable
            emit-value
            map-options
            @update:model-value="onFilterChange"
          />
        </div>

        <div class="col-12 col-md-3">
          <q-select
            v-model="filters.segment"
            :options="segmentOptions"
            label="Segment"
            dense
            clearable
            emit-value
            map-options
            @update:model-value="onFilterChange"
            :loading="loadingSegments"
          />
        </div>

        <div class="col-12 col-md-2">
          <q-btn
            color="primary"
            icon="refresh"
            label="Actualiser"
            @click="refreshData"
            :loading="loading"
            class="full-width"
          />
        </div>
      </div>

      <!-- Tableau d'historique -->
      <q-card>
        <q-card-section>
      <q-table
        :rows="smsHistory"
        :columns="columns"
        row-key="id"
        :loading="loading"
        :rows-per-page-options="[10, 20, 50, 100]"
        @request="onRequest"
        binary-state-sort
        v-model:pagination="pagination"
      >
            <template v-slot:top-right>
              <q-btn
                color="primary"
                icon="file_download"
                label="Exporter"
                @click="exportData"
                :disable="smsHistory.length === 0"
                flat
                dense
              />
            </template>

            <template v-slot:body-cell-status="props">
              <q-td :props="props">
                <q-chip
                  :color="getStatusColor(props.row.status)"
                  text-color="white"
                  dense
                >
                  {{ props.row.status }}
                </q-chip>
              </q-td>
            </template>

            <template v-slot:body-cell-message="props">
              <q-td :props="props">
                <div class="ellipsis" style="max-width: 200px">
                  {{ props.row.message }}
                </div>
                <q-tooltip>{{ props.row.message }}</q-tooltip>
              </q-td>
            </template>

            <template v-slot:body-cell-actions="props">
              <q-td :props="props">
                <q-btn
                  flat
                  round
                  dense
                  color="primary"
                  icon="visibility"
                  @click="showDetails(props.row)"
                >
                  <q-tooltip>Voir les détails</q-tooltip>
                </q-btn>
                <q-btn
                  v-if="props.row.status === 'FAILED'"
                  flat
                  round
                  dense
                  color="positive"
                  icon="replay"
                  @click="retrySms(props.row)"
                  :loading="retryingId === props.row.id"
                >
                  <q-tooltip>Réessayer</q-tooltip>
                </q-btn>
              </q-td>
            </template>

            <template v-slot:no-data>
              <div class="full-width row flex-center q-gutter-sm q-pa-md">
                <q-icon name="inbox" size="2em" color="grey-7" />
                <span class="text-grey-7">
                  Aucun SMS dans l'historique
                </span>
              </div>
            </template>
          </q-table>
        </q-card-section>
      </q-card>
    </div>

    <!-- Dialogue de détails -->
    <q-dialog v-model="detailsDialog" persistent>
      <q-card style="min-width: 350px; max-width: 600px">
        <q-card-section class="row items-center">
          <div class="text-h6">Détails du SMS</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section v-if="selectedSms">
          <q-list>
            <q-item>
              <q-item-section>
                <q-item-label caption>Numéro</q-item-label>
                <q-item-label>{{ selectedSms.phoneNumber }}</q-item-label>
              </q-item-section>
            </q-item>

            <q-item>
              <q-item-section>
                <q-item-label caption>Message</q-item-label>
                <q-item-label>{{ selectedSms.message }}</q-item-label>
              </q-item-section>
            </q-item>

            <q-item>
              <q-item-section>
                <q-item-label caption>Statut</q-item-label>
                <q-item-label>
                  <q-chip
                    :color="getStatusColor(selectedSms.status)"
                    text-color="white"
                    dense
                  >
                    {{ selectedSms.status }}
                  </q-chip>
                </q-item-label>
              </q-item-section>
            </q-item>

            <q-item>
              <q-item-section>
                <q-item-label caption>Date d'envoi</q-item-label>
                <q-item-label>{{
                  new Date(selectedSms.createdAt).toLocaleString()
                }}</q-item-label>
              </q-item-section>
            </q-item>

            <q-item v-if="selectedSms.messageId">
              <q-item-section>
                <q-item-label caption>ID du message</q-item-label>
                <q-item-label>{{ selectedSms.messageId }}</q-item-label>
              </q-item-section>
            </q-item>

            <q-item v-if="selectedSms.errorMessage">
              <q-item-section>
                <q-item-label caption>Message d'erreur</q-item-label>
                <q-item-label class="text-negative">{{
                  selectedSms.errorMessage
                }}</q-item-label>
              </q-item-section>
            </q-item>

            <q-item v-if="selectedSms.senderName">
              <q-item-section>
                <q-item-label caption>Expéditeur</q-item-label>
                <q-item-label>{{ selectedSms.senderName }}</q-item-label>
              </q-item-section>
            </q-item>

            <q-item v-if="selectedSms.segment">
              <q-item-section>
                <q-item-label caption>Segment</q-item-label>
                <q-item-label>{{ selectedSms.segment.name }}</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn
            v-if="selectedSms && selectedSms.status === 'FAILED'"
            color="positive"
            label="Réessayer"
            @click="retrySms(selectedSms)"
            :loading="retryingId === selectedSms.id"
          />
          <q-btn color="primary" label="Fermer" v-close-popup />
        </q-card-actions>
      </q-card>
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

// Utilitaires
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

<style scoped>
.ellipsis {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
