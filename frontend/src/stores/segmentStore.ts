import { defineStore } from "pinia";
import { ref } from "vue";
import { useApolloClient } from "@vue/apollo-composable";
import { gql } from "@apollo/client/core";

export interface CustomSegment {
  id: string;
  name: string;
  pattern: string;
  description: string;
}

export const useSegmentStore = defineStore("segment", () => {
  const { client: apolloClient } = useApolloClient();

  const customSegments = ref<CustomSegment[]>([]);
  const loading = ref(false);
  const error = ref<Error | null>(null);

  // Récupérer tous les segments personnalisés
  const fetchCustomSegments = async () => {
    loading.value = true;
    error.value = null;

    try {
      const { data } = await apolloClient.query({
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
    } catch (err) {
      error.value =
        err instanceof Error ? err : new Error("Une erreur est survenue");
      console.error("Error fetching custom segments:", err);
    } finally {
      loading.value = false;
    }
  };

  // Ajouter un nouveau segment personnalisé
  const addCustomSegment = async (segment: {
    name: string;
    pattern: string;
    description: string;
  }) => {
    loading.value = true;
    error.value = null;

    try {
      const { data } = await apolloClient.mutate({
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
          input: segment,
        },
      });

      // Ajouter le nouveau segment à la liste
      customSegments.value.push(data.createCustomSegment);

      return data.createCustomSegment;
    } catch (err) {
      error.value =
        err instanceof Error ? err : new Error("Une erreur est survenue");
      console.error("Error adding custom segment:", err);
      return null;
    } finally {
      loading.value = false;
    }
  };

  // Supprimer un segment personnalisé
  const deleteCustomSegment = async (id: string) => {
    loading.value = true;
    error.value = null;

    try {
      await apolloClient.mutate({
        mutation: gql`
          mutation DeleteCustomSegment($id: ID!) {
            deleteCustomSegment(id: $id)
          }
        `,
        variables: { id },
      });

      // Supprimer le segment de la liste
      customSegments.value = customSegments.value.filter(
        (segment) => segment.id !== id,
      );

      return true;
    } catch (err) {
      error.value =
        err instanceof Error ? err : new Error("Une erreur est survenue");
      console.error("Error deleting custom segment:", err);
      return false;
    } finally {
      loading.value = false;
    }
  };

  // Mettre à jour un segment personnalisé
  const updateCustomSegment = async (
    id: string,
    segment: {
      name: string;
      pattern: string;
      description: string;
    },
  ) => {
    loading.value = true;
    error.value = null;

    try {
      const { data } = await apolloClient.mutate({
        mutation: gql`
          mutation UpdateCustomSegment($id: ID!, $input: CustomSegmentInput!) {
            updateCustomSegment(id: $id, input: $input) {
              id
              name
              pattern
              description
            }
          }
        `,
        variables: {
          id,
          input: segment,
        },
      });

      // Mettre à jour le segment dans la liste
      const index = customSegments.value.findIndex((s) => s.id === id);
      if (index !== -1) {
        customSegments.value[index] = data.updateCustomSegment;
      }

      return data.updateCustomSegment;
    } catch (err) {
      error.value =
        err instanceof Error ? err : new Error("Une erreur est survenue");
      console.error("Error updating custom segment:", err);
      return null;
    } finally {
      loading.value = false;
    }
  };

  return {
    customSegments,
    loading,
    error,
    fetchCustomSegments,
    addCustomSegment,
    deleteCustomSegment,
    updateCustomSegment,
  };
});
