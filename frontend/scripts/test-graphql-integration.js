#!/usr/bin/env node

/**
 * Script pour tester l'intégration avec le backend GraphQL
 *
 * Ce script effectue des requêtes GraphQL pour vérifier que le backend
 * est correctement configuré et accessible.
 */

const {
  ApolloClient,
  InMemoryCache,
  HttpLink,
  gql,
} = require("@apollo/client/core");
const fetch = require("cross-fetch");

// Configuration du client Apollo
const client = new ApolloClient({
  link: new HttpLink({
    uri: "http://localhost/graphql.php",
    fetch,
  }),
  cache: new InMemoryCache(),
  defaultOptions: {
    watchQuery: {
      fetchPolicy: "no-cache",
    },
    query: {
      fetchPolicy: "no-cache",
    },
  },
});

// Requêtes à tester
const queries = [
  {
    name: "Récupérer tous les numéros de téléphone",
    query: gql`
      query GetPhoneNumbers {
        phoneNumbers {
          id
          number
          createdAt
        }
      }
    `,
  },
  {
    name: "Récupérer tous les segments personnalisés",
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
  },
];

// Fonction pour exécuter les tests
async function runTests() {
  console.log("🧪 Test d'intégration avec le backend GraphQL");
  console.log("===========================================");

  let success = 0;
  let failure = 0;

  for (const test of queries) {
    try {
      console.log(`\n📝 Test: ${test.name}`);
      console.log("Exécution de la requête...");

      const result = await client.query({
        query: test.query,
      });

      console.log("✅ Succès!");
      console.log("Résultat:", JSON.stringify(result.data, null, 2));
      success++;
    } catch (error) {
      console.log("❌ Échec!");
      console.log("Erreur:", error.message);
      failure++;
    }
  }

  console.log("\n===========================================");
  console.log(`📊 Résultats: ${success} succès, ${failure} échecs`);

  if (failure > 0) {
    console.log(
      "\n⚠️ Certains tests ont échoué. Vérifiez que le backend GraphQL est correctement configuré et accessible.",
    );
    process.exit(1);
  } else {
    console.log(
      "\n🎉 Tous les tests ont réussi! L'intégration avec le backend GraphQL fonctionne correctement.",
    );
    process.exit(0);
  }
}

// Exécuter les tests
runTests().catch((error) => {
  console.error("Erreur lors de l'exécution des tests:", error);
  process.exit(1);
});
