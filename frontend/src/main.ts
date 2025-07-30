import { createApp } from "vue";
import { createPinia } from "pinia";
import { Quasar, Notify } from "quasar";
import {
  ApolloClient,
  InMemoryCache,
  createHttpLink,
} from "@apollo/client/core";
import { DefaultApolloClient } from "@vue/apollo-composable";
import router from "./router";

// Import Quasar css
import "quasar/src/css/index.sass";

// Import icon libraries
import "@quasar/extras/material-icons/material-icons.css";

// Import global CSS
import "./assets/global.css";

import App from "./App.vue";

// Create Apollo client
const httpLink = createHttpLink({
  uri: "/graphql.php",
  credentials: 'include', // Ensure cookies are sent with cross-origin requests
});

const apolloClient = new ApolloClient({
  link: httpLink,
  cache: new InMemoryCache(),
  defaultOptions: {
    watchQuery: {
      fetchPolicy: "cache-and-network",
    },
    query: {
      fetchPolicy: "network-only",
    },
    mutate: {
      fetchPolicy: "no-cache",
    },
  },
});

// Create Pinia store
const pinia = createPinia();

// --- DÉBUT DE L'INTÉGRATION DU MCP APPOLLO-DEVTOOLS ---
// On vérifie qu'on est en mode développement pour ne pas inclure cet outil en production
if (import.meta.env.DEV) {
  // Importation dynamique pour que le code soit retiré en production (tree-shaking)
  import('./apollo-connector.js').then(({ initializeApolloConnector }) => {
    // On passe notre instance d'apolloClient au connecteur
    initializeApolloConnector(apolloClient); 
    console.log('🔌 Apollo Devtools MCP Connector Initialized.');
  }).catch(err => {
    console.error('Failed to load Apollo Devtools MCP Connector:', err);
  });
}
// --- FIN DE L'INTÉGRATION ---

// Create Vue app
const app = createApp(App);

// Use plugins
app.use(Quasar, {
  plugins: {
    Notify
  }
});
app.use(pinia);
app.use(router);

// Provide Apollo client
app.provide(DefaultApolloClient, apolloClient);

// Mount app
app.mount("#app");