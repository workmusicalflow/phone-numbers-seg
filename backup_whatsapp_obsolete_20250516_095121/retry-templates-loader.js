/**
 * Module séparé pour la gestion du chargement des templates WhatsApp
 * avec logique de fallback robuste
 */

import { injectEmergencyTemplates } from './emergency-templates';

/**
 * Tente de charger les templates WhatsApp avec plusieurs niveaux de fallback
 * 
 * @param {Object} options - Options de configuration
 * @param {Object} options.whatsappUserTemplateStore - Le store Pinia des templates
 * @param {Object} options.authStore - Le store Pinia d'authentification
 * @param {Object} options.$q - API Quasar pour les notifications
 * @param {Object} options.loadingState - Ref Vue pour l'état de chargement
 * @returns {Promise<boolean>} - True si le chargement a réussi
 */
export async function loadTemplatesWithFallback({
  whatsappUserTemplateStore,
  authStore,
  $q,
  loadingState
}) {
  if (!authStore.user?.id) return false;

  loadingState.value = true;
  
  try {
    // Niveau 1: Essayer le chargement normal avec forceRefresh
    await whatsappUserTemplateStore.fetchTemplates(
      authStore.user.id.toString(), 
      true // Forcer le rechargement
    );

    // Si des templates sont trouvés, nous avons réussi
    if (whatsappUserTemplateStore.templates.length > 0) {
      $q.notify({
        type: 'positive',
        message: 'Templates WhatsApp chargés avec succès!',
        timeout: 2000
      });
      return true;
    }

    // Niveau 2: Appeler directement l'API d'urgence
    try {
      console.log('Tentative de chargement via l\'API d\'urgence...');
      const timestamp = new Date().getTime();
      const userId = authStore.user.id.toString();
      const response = await fetch(`/emergency-whatsapp-templates.php?userId=${userId}&_=${timestamp}`);
      
      if (response.ok) {
        const data = await response.json();
        
        if (data?.data?.whatsappUserTemplates?.length > 0) {
          // Mettre à jour le store manuellement
          whatsappUserTemplateStore.$patch({
            templates: data.data.whatsappUserTemplates,
            totalCount: data.data.whatsappUserTemplates.length,
            error: null
          });
          
          $q.notify({
            type: 'info',
            message: 'Templates chargés depuis l\'API d\'urgence',
            timeout: 2000
          });
          return true;
        }
      }
      throw new Error('Échec de l\'API d\'urgence');
    } catch (emergencyError) {
      console.error('API d\'urgence a échoué:', emergencyError);
      
      // Niveau 3: Générer des templates d'urgence localement
      const emergencyTemplates = injectEmergencyTemplates(
        whatsappUserTemplateStore, 
        authStore.user.id.toString()
      );
      
      if (emergencyTemplates && emergencyTemplates.length > 0) {
        $q.notify({
          type: 'info',
          message: 'Des templates d\'urgence ont été générés localement',
          timeout: 3000
        });
        return true;
      }
    }
    
    // Si toutes les tentatives ont échoué
    $q.notify({
      type: 'negative',
      message: 'Impossible de charger ou générer des templates',
      timeout: 3000
    });
    return false;
  } catch (error) {
    console.error('Erreur lors du chargement des templates:', error);
    
    // Dernier recours: Générer des templates d'urgence localement
    injectEmergencyTemplates(whatsappUserTemplateStore, authStore.user.id.toString());
    
    $q.notify({
      type: 'warning',
      message: 'Des templates d\'urgence ont été générés suite à une erreur',
      timeout: 3000
    });
    return false;
  } finally {
    loadingState.value = false;
  }
}