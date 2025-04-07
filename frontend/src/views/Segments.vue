<template>
  <q-page padding>
    <div class="q-pa-md">
      <h1 class="text-h4 q-mb-md">Gestion des segments personnalisés</h1>

      <div class="row q-col-gutter-md">
        <div class="col-12 col-md-6">
          <q-card>
            <q-card-section>
              <div class="text-h6">Ajouter un segment personnalisé</div>
            </q-card-section>

            <q-card-section>
              <q-form @submit="onSubmit" class="q-gutter-md">
                <q-input
                  v-model="newSegment.name"
                  label="Nom du segment"
                  :rules="[(val) => !!val || 'Le nom est requis']"
                />

                <q-input
                  v-model="newSegment.pattern"
                  label="Motif (regex)"
                  :rules="[(val) => !!val || 'Le motif est requis']"
                />

                <q-input
                  v-model="newSegment.description"
                  type="textarea"
                  label="Description"
                  rows="3"
                />

                <div>
                  <q-btn label="Ajouter" type="submit" color="primary" />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-md-6">
          <q-card>
            <q-card-section>
              <div class="text-h6">Segments personnalisés existants</div>
            </q-card-section>

            <q-card-section>
              <q-table
                :rows="customSegments"
                :columns="columns"
                row-key="id"
                :loading="loading"
                :pagination="{ rowsPerPage: 10 }"
              >
                <template v-slot:body-cell-actions="props">
                  <q-td :props="props">
                    <q-btn
                      flat
                      round
                      dense
                      color="negative"
                      icon="delete"
                      @click="deleteSegment(props.row.id)"
                    />
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

const apolloClient = useApolloClient();

const loading = ref(false);
const customSegments = ref<any[]>([]);
const newSegment = ref({
  name: "",
  pattern: "",
  description: "",
});

const columns = [
  { name: "name", label: "Nom", field: "name", sortable: true },
  { name: "pattern", label: "Motif", field: "pattern", sortable: true },
  { name: "description", label: "Description", field: "description" },
  { name: "actions", label: "Actions", field: "actions" },
];

const fetchCustomSegments = async () => {
  loading.value = true;
  try {
    const { data } = await apolloClient.default.query({
      query: gql`
        query GetCustomSegments {
          customSegments {
            id
            name
            pattern
            description
          }
        }
      `,
      fetchPolicy: "network-only",
    });

    customSegments.value = data.customSegments;
  } catch (error) {
    console.error("Error fetching custom segments:", error);
  } finally {
    loading.value = false;
  }
};

const onSubmit = async () => {
  try {
    await apolloClient.default.mutate({
      mutation: gql`
        mutation CreateCustomSegment($input: CustomSegmentInput!) {
          createCustomSegment(input: $input) {
            id
            name
            pattern
            description
          }
        }
      `,
      variables: {
        input: {
          name: newSegment.value.name,
          pattern: newSegment.value.pattern,
          description: newSegment.value.description,
        },
      },
    });

    // Reset form
    newSegment.value = {
      name: "",
      pattern: "",
      description: "",
    };

    // Refresh list
    await fetchCustomSegments();
  } catch (error) {
    console.error("Error creating custom segment:", error);
  }
};

const deleteSegment = async (id: string) => {
  try {
    await apolloClient.default.mutate({
      mutation: gql`
        mutation DeleteCustomSegment($id: ID!) {
          deleteCustomSegment(id: $id)
        }
      `,
      variables: {
        id,
      },
    });

    // Refresh list
    await fetchCustomSegments();
  } catch (error) {
    console.error("Error deleting custom segment:", error);
  }
};

onMounted(fetchCustomSegments);
</script>
