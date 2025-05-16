/**
 * Module d'urgence pour les templates WhatsApp
 * 
 * Ce module fournit des templates d'urgence pour l'interface frontend
 * quand toutes les autres méthodes de récupération de templates échouent.
 */

// Templates d'urgence pour l'interface
export const emergencyTemplates = [
  {
    id: 'ui-emergency-1',
    userId: '', // Sera rempli dynamiquement
    templateName: 'connection_check',
    languageCode: 'fr',
    bodyVariablesCount: 0,
    hasHeaderMedia: false,
    isSpecialTemplate: false,
    headerMediaUrl: null,
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString()
  },
  {
    id: 'ui-emergency-2',
    userId: '', // Sera rempli dynamiquement
    templateName: 'welcome_message',
    languageCode: 'fr',
    bodyVariablesCount: 1,
    hasHeaderMedia: false,
    isSpecialTemplate: false,
    headerMediaUrl: null,
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString()
  }
];

/**
 * Obtenir les templates d'urgence personnalisés pour un utilisateur
 * 
 * @param {string} userId - ID de l'utilisateur
 * @returns {Array} - Templates d'urgence avec l'ID utilisateur défini
 */
export function getEmergencyTemplatesForUser(userId) {
  return emergencyTemplates.map(template => ({
    ...template,
    userId
  }));
}

/**
 * Injection de templates d'urgence dans le store
 * 
 * @param {object} store - Référence au store whatsappUserTemplate
 * @param {string} userId - ID de l'utilisateur
 */
export function injectEmergencyTemplates(store, userId) {
  const templates = getEmergencyTemplatesForUser(userId);
  store.templates = templates;
  store.totalCount = templates.length;
  
  console.warn('Injection de templates d\'urgence dans le store:', templates.length);
  return templates;
}