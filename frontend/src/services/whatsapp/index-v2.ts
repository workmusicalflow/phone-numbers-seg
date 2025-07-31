/**
 * Point d'entrée pour les services améliorés de gestion des templates WhatsApp
 * 
 * Exporte les services v2 pour l'analyse et la normalisation des templates WhatsApp
 * selon les spécifications exactes de l'API Meta Cloud.
 */
import { templateParserV2 } from './templateParserV2';
import { templateDataNormalizerV2 } from './templateDataNormalizerV2';

// Exporter les services individuels
export { templateParserV2, templateDataNormalizerV2 };

// Exporter un service combiné pour simplifier l'utilisation
export const whatsAppTemplateServiceV2 = {
  /**
   * Analyse un template et normalise ses données pour l'affichage
   */
  processTemplate(template, recipientPhoneNumber) {
    // Analyser le template
    const analysisResult = templateParserV2.analyzeTemplate(template);
    
    // Normaliser les données
    return templateDataNormalizerV2.createTemplateData(
      template,
      analysisResult,
      recipientPhoneNumber
    );
  },
  
  /**
   * Prépare un message de template formaté pour l'API Meta
   */
  prepareApiMessage(
    recipientPhone,
    template,
    bodyValues = [],
    headerMedia
  ) {
    return templateDataNormalizerV2.prepareTemplateMessage(
      recipientPhone,
      template,
      bodyValues,
      headerMedia
    );
  },
  
  /**
   * Génère les composants API avec paramètres
   */
  generateApiComponents(
    template,
    bodyValues = [],
    headerMedia
  ) {
    return templateParserV2.generateApiParameters(
      template,
      bodyValues,
      headerMedia
    );
  }
};