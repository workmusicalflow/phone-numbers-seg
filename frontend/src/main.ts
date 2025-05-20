import { createApp } from "vue";
import { createPinia } from "pinia";
import { Quasar, Notify, Dialog } from "quasar";
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
import "@quasar/extras/fontawesome-v6/fontawesome-v6.css";

// Import global CSS
import "./assets/global.css";

import App from "./App.vue";
import { useAuthStore } from "./stores/authStore"; // Import the auth store

// Import des composants globaux
import WhatsAppTemplateSelector from "./components/whatsapp/WhatsAppTemplateSelector.vue";

// Create Apollo client
const httpLink = createHttpLink({
  uri: "/graphql.php",
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

// Create Vue app
const app = createApp(App);

// Enregistrer les composants globaux
app.component('WhatsAppTemplateSelector', WhatsAppTemplateSelector);
console.log('Composants globaux enregistrés:', Object.keys(app._context.components));

// Use Pinia plugin first, so stores can be instantiated
app.use(pinia);

// Get AuthStore instance
const authStore = useAuthStore(); // No need to pass pinia if app.use(pinia) is called before

// Asynchronous function to initialize critical services and then mount the app
async function initializeAndMountApp() {
  try {
    // Initialize authentication: this will call checkAuth and update isAuthenticated
    await authStore.init();
    console.log('Auth store initialized from main.ts. isAuthenticated:', authStore.isAuthenticated);
  } catch (error) {
    console.error("Error during auth initialization in main.ts:", error);
    // App will still mount, router guards will handle redirection if auth failed
  } finally {
    // Setup other plugins and mount the app AFTER auth init attempt
    app.use(Quasar, {
      plugins: { Notify, Dialog }
    });
    app.use(router); // Router is used after authStore.init has resolved

    // Provide Apollo client
    app.provide(DefaultApolloClient, apolloClient);

    // Mount app
    app.mount("#app");
    console.log('Vue app mounted.');
  }
}

// Call the initialization function
initializeAndMountApp();
