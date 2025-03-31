<template>
  <q-page padding>
    <div class="q-pa-md">
      <h1 class="text-h4 q-mb-md">Envoi de SMS</h1>

      <q-tabs
        v-model="activeTab"
        class="text-primary q-mb-md"
        indicator-color="primary"
        align="left"
      >
        <q-tab name="single" label="Envoi Individuel" icon="person" />
        <q-tab name="bulk" label="Envoi en Masse" icon="people" />
        <q-tab name="segment" label="Envoi par Segment" icon="segment" />
      </q-tabs>

      <q-tab-panels v-model="activeTab" animated>
        <!-- Onglet Envoi Individuel -->
        <q-tab-panel name="single">
          <q-card>
            <q-card-section>
              <div class="text-h6">Envoyer un SMS à un numéro</div>
            </q-card-section>

            <q-card-section>
              <q-form @submit="onSubmitSingle" class="q-gutter-md">
                <q-input
                  v-model="singleSmsData.phoneNumber"
                  label="Numéro de téléphone"
                  :rules="[(val) => !!val || 'Le numéro est requis']"
                />

                <q-input
                  v-model="singleSmsData.message"
                  type="textarea"
                  label="Message"
                  :rules="[(val) => !!val || 'Le message est requis']"
                  rows="5"
                />

                <div>
                  <q-btn
                    label="Envoyer SMS"
                    type="submit"
                    color="primary"
                    :loading="loading"
                  />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </q-tab-panel>

        <!-- Onglet Envoi en Masse -->
        <q-tab-panel name="bulk">
          <q-card>
            <q-card-section>
              <div class="text-h6">Envoyer un SMS à plusieurs numéros</div>
            </q-card-section>

            <q-card-section>
              <q-form @submit="onSubmitBulk" class="q-gutter-md">
                <q-input
                  v-model="bulkSmsData.phoneNumbers"
                  type="textarea"
                  label="Numéros de téléphone (séparés par des virgules, espaces ou sauts de ligne)"
                  :rules="[(val) => !!val || 'Les numéros sont requis']"
                  rows="5"
                  hint="Exemple: +2250777104936, +2250141399354, +2250546560953"
                />

                <q-input
                  v-model="bulkSmsData.message"
                  type="textarea"
                  label="Message"
                  :rules="[(val) => !!val || 'Le message est requis']"
                  rows="5"
                />

                <div>
                  <q-btn
                    label="Envoyer SMS en masse"
                    type="submit"
                    color="primary"
                    :loading="loading"
                  />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </q-tab-panel>

        <!-- Onglet Envoi par Segment -->
        <q-tab-panel name="segment">
          <q-card>
            <q-card-section>
              <div class="text-h6">Envoyer un SMS à un segment</div>
            </q-card-section>

            <q-card-section>
              <q-form @submit="onSubmitSegment" class="q-gutter-md">
                <div class="q-mb-md">
                  <div class="text-subtitle2 q-mb-sm">
                    Sélectionnez un segment
                  </div>
                  <q-list bordered separator>
                    <q-item
                      v-for="segment in segments"
                      :key="segment.id"
                      clickable
                      v-ripple
                      :active="segmentSmsData.segmentId === segment.id"
                      @click="segmentSmsData.segmentId = segment.id"
                    >
                      <q-item-section>
                        <q-item-label>{{ segment.name }}</q-item-label>
                        <q-item-label caption>{{
                          segment.description || "Aucune description"
                        }}</q-item-label>
                      </q-item-section>
                      <q-item-section side>
                        <q-badge color="primary"
                          >{{ segment.phoneNumberCount }} numéros</q-badge
                        >
                      </q-item-section>
                    </q-item>
                  </q-list>
                  <div v-if="segments.length === 0" class="text-center q-pa-md">
                    <q-spinner
                      color="primary"
                      size="2em"
                      v-if="loadingSegments"
                    />
                    <div v-else>Aucun segment disponible</div>
                  </div>
                </div>

                <q-input
                  v-model="segmentSmsData.message"
                  type="textarea"
                  label="Message"
                  :rules="[(val) => !!val || 'Le message est requis']"
                  rows="5"
                />

                <div>
                  <q-btn
                    label="Envoyer SMS au segment"
                    type="submit"
                    color="primary"
                    :loading="loading"
                    :disable="!segmentSmsData.segmentId"
                  />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </q-tab-panel>
      </q-tab-panels>

      <!-- Résultats et historique -->
      <div class="row q-col-gutter-md q-mt-md">
        <!-- Résultat de l'envoi -->
        <div class="col-12 col-md-6" v-if="smsResult">
          <q-card>
            <q-card-section>
              <div class="text-h6">Résultat de l'envoi</div>
            </q-card-section>

            <q-card-section>
              <div v-if="smsResult.status === 'success'" class="text-positive">
                <q-icon name="check_circle" size="md" />
                <span class="q-ml-sm">SMS envoyé avec succès</span>
              </div>
              <div v-else class="text-negative">
                <q-icon name="error" size="md" />
                <span class="q-ml-sm"
                  >Échec de l'envoi: {{ smsResult.message }}</span
                >
              </div>

              <!-- Résumé pour l'envoi en masse ou par segment -->
              <q-list
                v-if="smsResult.summary"
                bordered
                separator
                class="q-mt-md"
              >
                <q-item>
                  <q-item-section>
                    <q-item-label>Total</q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-badge color="primary">{{
                      smsResult.summary.total
                    }}</q-badge>
                  </q-item-section>
                </q-item>
                <q-item>
                  <q-item-section>
                    <q-item-label>Réussis</q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-badge color="positive">{{
                      smsResult.summary.successful
                    }}</q-badge>
                  </q-item-section>
                </q-item>
                <q-item>
                  <q-item-section>
                    <q-item-label>Échoués</q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-badge color="negative">{{
                      smsResult.summary.failed
                    }}</q-badge>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card-section>
          </q-card>
        </div>

        <!-- Historique des SMS -->
        <div class="col-12" :class="{ 'col-md-6': smsResult }">
          <q-card>
            <q-card-section>
              <div class="text-h6">Historique des SMS</div>
            </q-card-section>

            <q-card-section>
              <q-table
                :rows="smsHistory"
                :columns="columns"
                row-key="id"
                :loading="loadingHistory"
                :pagination="{ rowsPerPage: 10 }"
              >
                <template v-slot:body-cell-status="props">
                  <q-td :props="props">
                    <q-chip
                      :color="
                        props.row.status === 'SENT'
                          ? 'positive'
                          : props.row.status === 'FAILED'
                            ? 'negative'
                            : 'warning'
                      "
                      text-color="white"
                      dense
                    >
                      {{ props.row.status }}
                    </q-chip>
                  </q-td>
                </template>
              </q-table>
            </q-card-section>
          </q-card>
        </div>
      </div>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useApolloClient } from "@vue/apollo-composable";
import { gql } from "@apollo/client/core";
import { useQuasar } from "quasar";

const apolloClient = useApolloClient();
const $q = useQuasar();

// État de l'interface
const activeTab = ref("single");
const loading = ref(false);
const loadingHistory = ref(false);
const loadingSegments = ref(false);
const smsHistory = ref<any[]>([]);
const segments = ref<any[]>([]);
const smsResult = ref<any>(null);

// Données des formulaires
const singleSmsData = ref({
  phoneNumber: "",
  message: "",
});

const bulkSmsData = ref({
  phoneNumbers: "",
  message: "",
});

const segmentSmsData = ref({
  segmentId: null as number | null,
  message: "",
});

// Configuration de la table d'historique
const columns = [
  {
    name: "phoneNumber",
    label: "Numéro",
    field: "phoneNumber",
    sortable: true,
  },
  { name: "message", label: "Message", field: "message" },
  { name: "status", label: "Statut", field: "status", sortable: true },
  {
    name: "createdAt",
    label: "Date",
    field: "createdAt",
    sortable: true,
    format: (val: string) => new Date(val).toLocaleString(),
  },
];

// Récupération de l'historique des SMS
const fetchSmsHistory = async () => {
  loadingHistory.value = true;
  try {
    const { data } = await apolloClient.default.query({
      query: gql`
        query GetSmsHistory {
          smsHistory {
            id
            phoneNumber
            message
            status
            createdAt
          }
        }
      `,
      fetchPolicy: "network-only",
    });

    smsHistory.value = data.smsHistory;
  } catch (error) {
    console.error("Error fetching SMS history:", error);
    $q.notify({
      color: "negative",
      message: "Erreur lors du chargement de l'historique",
      icon: "error",
    });
  } finally {
    loadingHistory.value = false;
  }
};

// Récupération des segments disponibles
const fetchSegments = async () => {
  loadingSegments.value = true;
  try {
    const { data } = await apolloClient.default.query({
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

    segments.value = data.segmentsForSMS;
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

// Envoi d'un SMS à un numéro individuel
const onSubmitSingle = async () => {
  loading.value = true;
  smsResult.value = null;

  try {
    const { data } = await apolloClient.default.mutate({
      mutation: gql`
        mutation SendSms($phoneNumber: String!, $message: String!) {
          sendSms(phoneNumber: $phoneNumber, message: $message) {
            id
            phoneNumber
            message
            status
            createdAt
          }
        }
      `,
      variables: {
        phoneNumber: singleSmsData.value.phoneNumber,
        message: singleSmsData.value.message,
      },
    });

    // Afficher le résultat
    smsResult.value = {
      status: data.sendSms.status === "SENT" ? "success" : "error",
      message:
        data.sendSms.status === "SENT"
          ? "SMS envoyé avec succès"
          : "Échec de l'envoi du SMS",
    };

    // Notification
    $q.notify({
      color: data.sendSms.status === "SENT" ? "positive" : "negative",
      message: smsResult.value.message,
      icon: data.sendSms.status === "SENT" ? "check_circle" : "error",
    });

    // Réinitialiser le formulaire en cas de succès
    if (data.sendSms.status === "SENT") {
      singleSmsData.value = {
        phoneNumber: "",
        message: "",
      };
    }

    // Rafraîchir l'historique
    await fetchSmsHistory();
  } catch (error) {
    console.error("Error sending SMS:", error);
    smsResult.value = {
      status: "error",
      message: "Erreur lors de l'envoi du SMS",
    };
    $q.notify({
      color: "negative",
      message: "Erreur lors de l'envoi du SMS",
      icon: "error",
    });
  } finally {
    loading.value = false;
  }
};

// Envoi de SMS en masse
const onSubmitBulk = async () => {
  loading.value = true;
  smsResult.value = null;

  // Traiter les numéros (séparés par virgules, espaces ou sauts de ligne)
  const phoneNumbers = bulkSmsData.value.phoneNumbers
    .split(/[\s,;]+/)
    .map((num) => num.trim())
    .filter((num) => num.length > 0);

  if (phoneNumbers.length === 0) {
    $q.notify({
      color: "negative",
      message: "Aucun numéro valide trouvé",
      icon: "error",
    });
    loading.value = false;
    return;
  }

  try {
    const { data } = await apolloClient.default.mutate({
      mutation: gql`
        mutation SendBulkSms($phoneNumbers: [String!]!, $message: String!) {
          sendBulkSms(phoneNumbers: $phoneNumbers, message: $message) {
            status
            message
            summary {
              total
              successful
              failed
            }
            results {
              phoneNumber
              status
              message
            }
          }
        }
      `,
      variables: {
        phoneNumbers,
        message: bulkSmsData.value.message,
      },
    });

    // Afficher le résultat
    smsResult.value = data.sendBulkSms;

    // Notification
    $q.notify({
      color: data.sendBulkSms.status === "success" ? "positive" : "negative",
      message:
        data.sendBulkSms.status === "success"
          ? `SMS envoyés avec succès (${data.sendBulkSms.summary.successful}/${data.sendBulkSms.summary.total})`
          : data.sendBulkSms.message,
      icon: data.sendBulkSms.status === "success" ? "check_circle" : "error",
    });

    // Réinitialiser le formulaire en cas de succès
    if (data.sendBulkSms.status === "success") {
      bulkSmsData.value = {
        phoneNumbers: "",
        message: "",
      };
    }

    // Rafraîchir l'historique
    await fetchSmsHistory();
  } catch (error) {
    console.error("Error sending bulk SMS:", error);
    smsResult.value = {
      status: "error",
      message: "Erreur lors de l'envoi des SMS en masse",
    };
    $q.notify({
      color: "negative",
      message: "Erreur lors de l'envoi des SMS en masse",
      icon: "error",
    });
  } finally {
    loading.value = false;
  }
};

// Envoi de SMS à un segment
const onSubmitSegment = async () => {
  if (!segmentSmsData.value.segmentId) {
    $q.notify({
      color: "negative",
      message: "Veuillez sélectionner un segment",
      icon: "error",
    });
    return;
  }

  loading.value = true;
  smsResult.value = null;

  try {
    const { data } = await apolloClient.default.mutate({
      mutation: gql`
        mutation SendSmsToSegment($segmentId: ID!, $message: String!) {
          sendSmsToSegment(segmentId: $segmentId, message: $message) {
            status
            message
            segment {
              id
              name
            }
            summary {
              total
              successful
              failed
            }
            results {
              phoneNumber
              status
              message
            }
          }
        }
      `,
      variables: {
        segmentId: segmentSmsData.value.segmentId,
        message: segmentSmsData.value.message,
      },
    });

    // Afficher le résultat
    smsResult.value = data.sendSmsToSegment;

    // Notification
    $q.notify({
      color:
        data.sendSmsToSegment.status === "success" ? "positive" : "negative",
      message:
        data.sendSmsToSegment.status === "success"
          ? `SMS envoyés avec succès au segment ${data.sendSmsToSegment.segment.name} (${data.sendSmsToSegment.summary.successful}/${data.sendSmsToSegment.summary.total})`
          : data.sendSmsToSegment.message,
      icon:
        data.sendSmsToSegment.status === "success" ? "check_circle" : "error",
    });

    // Réinitialiser le formulaire en cas de succès
    if (data.sendSmsToSegment.status === "success") {
      segmentSmsData.value = {
        segmentId: null,
        message: "",
      };
    }

    // Rafraîchir l'historique
    await fetchSmsHistory();
  } catch (error) {
    console.error("Error sending SMS to segment:", error);
    smsResult.value = {
      status: "error",
      message: "Erreur lors de l'envoi des SMS au segment",
    };
    $q.notify({
      color: "negative",
      message: "Erreur lors de l'envoi des SMS au segment",
      icon: "error",
    });
  } finally {
    loading.value = false;
  }
};

// Initialisation
onMounted(() => {
  fetchSmsHistory();
  fetchSegments();
});
</script>
