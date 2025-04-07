<template>
  <q-page padding>
    <div class="q-pa-md">
      <h1 class="text-h4 q-mb-md">Segmentation individuelle</h1>

      <div class="row q-col-gutter-md">
        <div class="col-12 col-md-6">
          <q-card>
            <q-card-section>
              <div class="text-h6">Saisir un numéro de téléphone</div>
            </q-card-section>

            <q-card-section>
              <q-form @submit="onSubmit" class="q-gutter-md">
                <q-input
                  v-model="phoneNumber"
                  label="Numéro de téléphone"
                  :rules="[(val) => !!val || 'Le numéro est requis']"
                  :disable="loading"
                  hint="Format international recommandé (ex: +225 07 07 07 07)"
                  placeholder="+225 XX XX XX XX"
                />

                <q-select
                  v-model="civility"
                  :options="['M.', 'Mme', 'Mlle']"
                  label="Civilité"
                  outlined
                  emit-value
                  map-options
                  clearable
                  :disable="loading"
                  hint="Sélectionnez une civilité"
                />

                <q-input
                  v-model="firstName"
                  label="Prénom"
                  :disable="loading"
                  hint="Prénom du contact"
                  placeholder="Jean"
                />

                <q-input
                  v-model="name"
                  label="Nom"
                  :disable="loading"
                  hint="Nom de famille du contact"
                  placeholder="Dupont"
                />

                <q-input
                  v-model="company"
                  label="Entreprise"
                  :disable="loading"
                  hint="Nom de l'entreprise du contact"
                  placeholder="ACME Inc."
                />

                <div class="row q-col-gutter-sm">
                  <div class="col">
                    <q-btn
                      label="Segmenter"
                      type="submit"
                      color="primary"
                      :loading="loading"
                      :disable="!phoneNumber"
                    />
                  </div>
                  <div class="col" v-if="segmentationComplete">
                    <q-btn
                      label="Nouveau"
                      color="secondary"
                      @click="segmenterNouveau"
                      :disable="loading"
                    />
                  </div>
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-md-6">
          <q-card v-if="segments.length > 0">
            <q-card-section>
              <div class="text-h6">Résultats de la segmentation</div>
              <q-chip color="primary" text-color="white" v-if="phoneNumber">
                {{ phoneNumber }}
              </q-chip>
              <div class="q-mt-sm" v-if="hasContactInfo">
                <q-chip
                  v-if="civility || firstName || name"
                  color="secondary"
                  text-color="white"
                >
                  <q-icon name="person" left />
                  {{ formatContactInfo }}
                </q-chip>
                <q-chip v-if="company" color="accent" text-color="white">
                  <q-icon name="business" left />
                  {{ company }}
                </q-chip>
              </div>
            </q-card-section>

            <q-card-section>
              <div class="text-subtitle2 q-mb-sm">Segments identifiés:</div>
              <q-list bordered separator>
                <q-item v-for="segment in segments" :key="segment.id">
                  <q-item-section avatar>
                    <q-icon
                      :name="getSegmentIcon(segment.type)"
                      :color="getSegmentColor(segment.type)"
                    />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>{{ segment.type }}</q-item-label>
                    <q-item-label caption>{{ segment.value }}</q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card-section>
          </q-card>
        </div>
      </div>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { useApolloClient } from "@vue/apollo-composable";
import { gql } from "@apollo/client/core";
import { useQuasar } from "quasar";

const $q = useQuasar();
const apolloClient = useApolloClient();

const phoneNumber = ref("");
const civility = ref<string | null>(null);
const firstName = ref("");
const name = ref("");
const company = ref("");
const segments = ref<any[]>([]);
const loading = ref(false);
const segmentationComplete = ref(false);

// Vérifier si des informations de contact sont disponibles
const hasContactInfo = computed(() => {
  return !!(civility.value || firstName.value || name.value || company.value);
});

// Formater les informations de contact
const formatContactInfo = computed(() => {
  const parts = [];

  if (civility.value) {
    parts.push(civility.value);
  }

  if (firstName.value) {
    parts.push(firstName.value);
  }

  if (name.value) {
    parts.push(name.value);
  }

  let result = parts.join(" ");

  if (company.value) {
    if (result) {
      result += ` - ${company.value}`;
    } else {
      result = company.value;
    }
  }

  return result;
});

const resetForm = () => {
  phoneNumber.value = "";
  civility.value = null;
  firstName.value = "";
  name.value = "";
  company.value = "";
  segments.value = [];
  segmentationComplete.value = false;
};

const onSubmit = async () => {
  loading.value = true;
  segmentationComplete.value = false;

  try {
    // Afficher un indicateur de chargement
    const loadingNotif = $q.notify({
      message: "Segmentation en cours...",
      color: "info",
      spinner: true,
      timeout: 0,
    });

    // Créer d'abord le numéro de téléphone avec les informations de contact
    const createResult = await apolloClient.default.mutate({
      mutation: gql`
        mutation CreatePhoneNumber(
          $number: String!
          $civility: String
          $firstName: String
          $name: String
          $company: String
        ) {
          createPhoneNumber(
            number: $number
            civility: $civility
            firstName: $firstName
            name: $name
            company: $company
          ) {
            id
            number
            civility
            firstName
            name
            company
            segments {
              id
              type
              value
            }
          }
        }
      `,
      variables: {
        number: phoneNumber.value,
        civility: civility.value,
        firstName: firstName.value || null,
        name: name.value || null,
        company: company.value || null,
      },
    });

    // Fermer l'indicateur de chargement
    loadingNotif();

    // Récupérer les segments du numéro créé
    segments.value = createResult.data.createPhoneNumber.segments;
    segmentationComplete.value = true;

    // Afficher une notification de succès
    $q.notify({
      message: "Segmentation réussie !",
      color: "positive",
      icon: "check_circle",
      timeout: 2000,
    });
  } catch (error) {
    console.error("Error segmenting phone number:", error);

    // Afficher une notification d'erreur
    $q.notify({
      message: "Erreur lors de la segmentation",
      color: "negative",
      icon: "error",
      timeout: 3000,
    });
  } finally {
    loading.value = false;
  }
};

const segmenterNouveau = () => {
  resetForm();
};

// Obtenir l'icône appropriée pour un type de segment
const getSegmentIcon = (type: string): string => {
  const typeLC = type.toLowerCase();

  if (typeLC.includes("pays") || typeLC.includes("country")) {
    return "flag";
  }
  if (typeLC.includes("opérateur") || typeLC.includes("operator")) {
    return "cell_tower";
  }
  if (typeLC.includes("région") || typeLC.includes("region")) {
    return "location_on";
  }
  if (typeLC.includes("ville") || typeLC.includes("city")) {
    return "location_city";
  }
  if (typeLC.includes("type") || typeLC.includes("format")) {
    return "phone";
  }
  if (typeLC.includes("indicatif") || typeLC.includes("code")) {
    return "dialpad";
  }

  // Icône par défaut
  return "label";
};

// Obtenir la couleur appropriée pour un type de segment
const getSegmentColor = (type: string): string => {
  const typeLC = type.toLowerCase();

  if (typeLC.includes("pays") || typeLC.includes("country")) {
    return "green";
  }
  if (typeLC.includes("opérateur") || typeLC.includes("operator")) {
    return "blue";
  }
  if (typeLC.includes("région") || typeLC.includes("region")) {
    return "orange";
  }
  if (typeLC.includes("ville") || typeLC.includes("city")) {
    return "deep-orange";
  }
  if (typeLC.includes("type") || typeLC.includes("format")) {
    return "purple";
  }
  if (typeLC.includes("indicatif") || typeLC.includes("code")) {
    return "teal";
  }

  // Couleur par défaut
  return "grey";
};
</script>
