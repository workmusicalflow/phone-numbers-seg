<template>
  <q-page padding>
    <div class="q-pa-md">
      <h1 class="text-h4 q-mb-md">Traitement par lot</h1>

      <div class="row q-col-gutter-md">
        <div class="col-12 col-md-6">
          <q-card>
            <q-card-section>
              <div class="text-h6">Saisir plusieurs numéros de téléphone</div>
            </q-card-section>

            <q-card-section>
              <q-form @submit="onSubmit" class="q-gutter-md">
                <q-input
                  v-model="phoneNumbers"
                  type="textarea"
                  label="Numéros de téléphone (un par ligne)"
                  :rules="[(val) => !!val || 'Au moins un numéro est requis']"
                  rows="10"
                />

                <div>
                  <q-btn
                    label="Traiter par lot"
                    type="submit"
                    color="primary"
                  />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-md-6">
          <q-card v-if="results.length > 0">
            <q-card-section>
              <div class="text-h6">Résultats du traitement par lot</div>
            </q-card-section>

            <q-card-section>
              <q-list bordered separator>
                <q-expansion-item
                  v-for="result in results"
                  :key="result.phoneNumber"
                  :label="result.phoneNumber"
                  header-class="text-primary"
                >
                  <q-card>
                    <q-card-section>
                      <q-list>
                        <q-item
                          v-for="segment in result.segments"
                          :key="segment.id"
                        >
                          <q-item-section>
                            <q-item-label>{{ segment.type }}</q-item-label>
                            <q-item-label caption>{{
                              segment.value
                            }}</q-item-label>
                          </q-item-section>
                        </q-item>
                      </q-list>
                    </q-card-section>
                  </q-card>
                </q-expansion-item>
              </q-list>
            </q-card-section>
          </q-card>
        </div>
      </div>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { useApolloClient } from "@vue/apollo-composable";
import { gql } from "@apollo/client/core";

const apolloClient = useApolloClient();

const phoneNumbers = ref("");
const results = ref<any[]>([]);

const onSubmit = async () => {
  try {
    const numbers = phoneNumbers.value
      .split("\n")
      .map((n) => n.trim())
      .filter((n) => n.length > 0);

    const { data } = await apolloClient.default.query({
      query: gql`
        query BatchSegmentPhones($phoneNumbers: [String!]!) {
          batchSegmentPhones(phoneNumbers: $phoneNumbers) {
            phoneNumber
            segments {
              id
              type
              value
            }
          }
        }
      `,
      variables: {
        phoneNumbers: numbers,
      },
    });

    results.value = data.batchSegmentPhones;
  } catch (error) {
    console.error("Error processing batch:", error);
  }
};
</script>
