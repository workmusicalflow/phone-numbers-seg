import { defineStore } from "pinia";
import { ref } from "vue";
import { useApolloClient } from "@vue/apollo-composable";
import { gql } from "@apollo/client/core";

export interface PhoneNumber {
  id: string;
  number: string;
  createdAt: string;
  civility?: string;
  firstName?: string;
  name?: string;
  company?: string;
  sector?: string;
  notes?: string;
  segments: Segment[];
}

export interface Segment {
  id: string;
  type: string;
  value: string;
}

export const usePhoneStore = defineStore("phone", () => {
  const { client: apolloClient } = useApolloClient();

  const phoneNumbers = ref<PhoneNumber[]>([]);
  const loading = ref(false);
  const error = ref<Error | null>(null);

  // Récupérer tous les numéros de téléphone
  const fetchPhoneNumbers = async () => {
    loading.value = true;
    error.value = null;

    try {
      const { data } = await apolloClient.query({
        query: gql`
          query GetPhoneNumbers {
            phoneNumbers {
              id
              number
              createdAt
              civility
              firstName
              name
              company
              sector
              notes
              segments {
                id
                type
                value
              }
            }
          }
        `,
        fetchPolicy: "network-only",
      });

      phoneNumbers.value = data.phoneNumbers;
    } catch (err) {
      error.value =
        err instanceof Error ? err : new Error("Une erreur est survenue");
      console.error("Error fetching phone numbers:", err);
    } finally {
      loading.value = false;
    }
  };

  // Récupérer un numéro de téléphone par son ID
  const fetchPhoneNumberById = async (id: string) => {
    loading.value = true;
    error.value = null;

    try {
      const { data } = await apolloClient.query({
        query: gql`
          query GetPhoneNumber($id: ID!) {
            phoneNumber(id: $id) {
              id
              number
              createdAt
              civility
              firstName
              name
              company
              sector
              notes
              segments {
                id
                type
                value
              }
            }
          }
        `,
        variables: { id },
      });

      return data.phoneNumber;
    } catch (err) {
      error.value =
        err instanceof Error ? err : new Error("Une erreur est survenue");
      console.error("Error fetching phone number:", err);
      return null;
    } finally {
      loading.value = false;
    }
  };

  // Ajouter un nouveau numéro de téléphone
  const addPhoneNumber = async (
    number: string,
    civility?: string,
    firstName?: string,
    name?: string,
    company?: string,
    sector?: string,
    notes?: string,
  ) => {
    loading.value = true;
    error.value = null;

    try {
      const { data } = await apolloClient.mutate({
        mutation: gql`
          mutation CreatePhoneNumber(
            $number: String!
            $civility: String
            $firstName: String
            $name: String
            $company: String
            $sector: String
            $notes: String
          ) {
            createPhoneNumber(
              number: $number
              civility: $civility
              firstName: $firstName
              name: $name
              company: $company
              sector: $sector
              notes: $notes
            ) {
              id
              number
              createdAt
              civility
              firstName
              name
              company
              sector
              notes
              segments {
                id
                type
                value
              }
            }
          }
        `,
        variables: {
          number,
          civility,
          firstName,
          name,
          company,
          sector,
          notes,
        },
      });

      // Ajouter le nouveau numéro à la liste
      phoneNumbers.value.push(data.createPhoneNumber);

      return data.createPhoneNumber;
    } catch (err) {
      error.value =
        err instanceof Error ? err : new Error("Une erreur est survenue");
      console.error("Error adding phone number:", err);
      return null;
    } finally {
      loading.value = false;
    }
  };

  // Supprimer un numéro de téléphone
  const deletePhoneNumber = async (id: string) => {
    loading.value = true;
    error.value = null;

    try {
      await apolloClient.mutate({
        mutation: gql`
          mutation DeletePhoneNumber($id: ID!) {
            deletePhoneNumber(id: $id)
          }
        `,
        variables: { id },
      });

      // Supprimer le numéro de la liste
      phoneNumbers.value = phoneNumbers.value.filter(
        (phone) => phone.id !== id,
      );

      return true;
    } catch (err) {
      error.value =
        err instanceof Error ? err : new Error("Une erreur est survenue");
      console.error("Error deleting phone number:", err);
      return false;
    } finally {
      loading.value = false;
    }
  };

  return {
    phoneNumbers,
    loading,
    error,
    fetchPhoneNumbers,
    fetchPhoneNumberById,
    addPhoneNumber,
    deletePhoneNumber,
  };
});
