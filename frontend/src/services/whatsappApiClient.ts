import axios from 'axios';

/**
 * Client API dédié pour les endpoints REST de WhatsApp
 * 
 * Ce client est configuré spécifiquement pour communiquer avec les 
 * endpoints PHP de l'API REST WhatsApp, en résolvant les problèmes de
 * chemin relatif entre le serveur de développement Vite et le serveur PHP.
 */
const whatsappApi = axios.create({
  // Utiliser l'URL absolue du serveur PHP (à adapter selon l'environnement)
  baseURL: '/api',
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// Gestion des erreurs spécifiques à l'API WhatsApp
whatsappApi.interceptors.response.use(
  response => {
    return response;
  },
  error => {
    // Log détaillé des erreurs pour faciliter le debug
    if (error.response) {
      console.error('Erreur API WhatsApp:', {
        status: error.response.status,
        statusText: error.response.statusText,
        url: error.config.url,
        method: error.config.method,
        data: error.config.data,
        responseData: error.response.data
      });
    } else if (error.request) {
      console.error('Erreur de connexion à l\'API WhatsApp:', {
        url: error.config.url,
        method: error.config.method,
        data: error.config.data,
        error: error.message
      });
    } else {
      console.error('Erreur de configuration de la requête WhatsApp:', error.message);
    }
    
    return Promise.reject(error);
  }
);

export { whatsappApi };