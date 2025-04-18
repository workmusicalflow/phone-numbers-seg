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

// Import global CSS
import "./assets/global.css";

import App from "./App.vue";

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

// Use plugins
app.use(Quasar, {
  plugins: { Notify, Dialog }
});
app.use(pinia);
app.use(router);

// Provide Apollo client
app.provide(DefaultApolloClient, apolloClient);

// Mount app
app.mount("#app");
