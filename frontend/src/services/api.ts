import axios from 'axios';
import { ApolloClient, InMemoryCache, gql } from '@apollo/client/core';

// Créer une instance axios avec la configuration de base
const api = axios.create({
  baseURL: 'http://localhost:8000/api', // URL de base pour toutes les requêtes
  withCredentials: true, // Important pour inclure les cookies
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// Intercepteur pour ajouter le token d'authentification à chaque requête
api.interceptors.request.use(
  config => {
    const token = localStorage.getItem('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  error => {
    return Promise.reject(error);
  }
);

// Intercepteur pour gérer les erreurs de réponse
api.interceptors.response.use(
  response => {
    return response;
  },
  error => {
    // Gérer les erreurs d'authentification (401)
    if (error.response && error.response.status === 401) {
      // Rediriger vers la page de connexion
      localStorage.removeItem('auth_token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

// Apollo Client setup
const apolloClient = new ApolloClient({
  uri: '/graphql.php', // URL relative pour utiliser le proxy Vite
  cache: new InMemoryCache(),
  credentials: 'include', // Important pour inclure les cookies
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  defaultOptions: {
    mutate: {
      errorPolicy: 'all' // Return both errors and data in response
    },
    query: {
      errorPolicy: 'all' // Return both errors and data in response
    },
    watchQuery: {
      fetchPolicy: 'cache-and-network' // Toujours chercher les données fraîches
    }
  }
});

export { api, apolloClient, gql };
