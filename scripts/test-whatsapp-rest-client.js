/**
 * Script de test pour le client REST WhatsApp frontend
 * 
 * Ce script simule les appels qui seront effectués par le frontend
 * pour s'assurer que l'API REST répond correctement.
 */

const axios = require('axios');

// Configurer axios avec les paramètres de base
const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// Token d'authentification pour les tests (à remplacer par un token valide)
const AUTH_TOKEN = 'votre_token_ici';

// Ajouter le token d'authentification à toutes les requêtes
api.interceptors.request.use(
  config => {
    config.headers.Authorization = `Bearer ${AUTH_TOKEN}`;
    return config;
  },
  error => Promise.reject(error)
);

/**
 * Récupère les templates WhatsApp approuvés
 */
async function getApprovedTemplates(filters = {}) {
  try {
    // Construire les paramètres de requête
    const params = new URLSearchParams();
    
    if (filters.name) params.append('name', filters.name);
    if (filters.language) params.append('language', filters.language);
    if (filters.category) params.append('category', filters.category);
    if (filters.status) params.append('status', filters.status);
    
    // Gestion explicite du cache
    if (filters.use_cache !== undefined) params.append('use_cache', filters.use_cache.toString());
    if (filters.force_refresh !== undefined) params.append('force_refresh', filters.force_refresh.toString());
    
    // Effectuer la requête
    const endpoint = `whatsapp/templates/approved?${params.toString()}`;
    console.log(`Envoi de la requête vers: ${endpoint}`);
    
    const response = await api.get(endpoint);
    
    console.log('Réponse reçue:');
    console.log(`Status: ${response.data.status}`);
    console.log(`Nombre de templates: ${response.data.count}`);
    console.log(`Source: ${response.data.meta.source}`);
    console.log(`Fallback utilisé: ${response.data.meta.usedFallback}`);
    
    // Afficher quelques templates pour vérification
    if (response.data.templates && response.data.templates.length > 0) {
      console.log('\nExemples de templates:');
      const examples = response.data.templates.slice(0, 3);
      
      examples.forEach((template, index) => {
        console.log(`\nTemplate ${index + 1}:`);
        console.log(`- ID: ${template.id}`);
        console.log(`- Nom: ${template.name}`);
        console.log(`- Catégorie: ${template.category}`);
        console.log(`- Langue: ${template.language}`);
        console.log(`- Status: ${template.status}`);
        
        if (template.components) {
          console.log(`- Composants: ${template.components.length}`);
        }
      });
    }
    
    return response.data;
  } catch (error) {
    console.error('Erreur lors de la récupération des templates WhatsApp:', error.message);
    
    if (error.response) {
      console.error('Détails de l\'erreur:', error.response.data);
    }
    
    return {
      status: 'error',
      templates: [],
      count: 0,
      meta: {
        source: 'error',
        usedFallback: true,
        timestamp: new Date().toISOString()
      },
      message: error.message
    };
  }
}

/**
 * Récupère un template spécifique par son ID
 */
async function getTemplateById(templateId) {
  try {
    console.log(`Récupération du template avec l'ID: ${templateId}`);
    
    const response = await api.get(`whatsapp/templates/${templateId}`);
    
    console.log('Réponse reçue:');
    console.log(`Status: ${response.data.status}`);
    
    if (response.data.template) {
      console.log('\nDétails du template:');
      console.log(`- ID: ${response.data.template.id}`);
      console.log(`- Nom: ${response.data.template.name}`);
      console.log(`- Catégorie: ${response.data.template.category}`);
      console.log(`- Langue: ${response.data.template.language}`);
      console.log(`- Status: ${response.data.template.status}`);
    } else {
      console.log('Aucun template trouvé avec cet ID');
    }
    
    return response.data;
  } catch (error) {
    console.error(`Erreur lors de la récupération du template ${templateId}:`, error.message);
    
    if (error.response) {
      console.error('Détails de l\'erreur:', error.response.data);
    }
    
    return null;
  }
}

/**
 * Fonction principale pour exécuter les tests
 */
async function runTests() {
  console.log('\n--- Test 1: Récupération de tous les templates approuvés ---');
  await getApprovedTemplates();
  
  console.log('\n--- Test 2: Récupération des templates avec filtrage ---');
  await getApprovedTemplates({
    language: 'fr_FR',
    category: 'MARKETING'
  });
  
  console.log('\n--- Test 3: Test du rafraîchissement forcé ---');
  await getApprovedTemplates({
    use_cache: true,
    force_refresh: true
  });
  
  console.log('\n--- Test 4: Récupération d\'un template spécifique ---');
  // Récupérer d'abord la liste pour avoir un ID valide à tester
  const response = await getApprovedTemplates();
  
  if (response.templates && response.templates.length > 0) {
    const templateId = response.templates[0].id;
    await getTemplateById(templateId);
  } else {
    console.log('Pas de templates disponibles pour tester getTemplateById');
  }
}

// Exécuter les tests
runTests().catch(err => {
  console.error('Erreur dans l\'exécution des tests:', err);
});