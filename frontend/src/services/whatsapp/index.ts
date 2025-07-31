/**
 * Point d'entrée pour les services de gestion des templates WhatsApp
 * 
 * Exporte les services pour l'analyse et la normalisation des templates WhatsApp.
 */
import { templateParser } from './templateParser';
import { templateDataNormalizer } from './templateDataNormalizer';

// Exporter les services individuels
export { templateParser, templateDataNormalizer };

// Exporter un service combiné pour simplifier l'utilisation
export const whatsAppTemplateService = {
  /**
   * Analyse un template et normalise ses données pour l'affichage et l'envoi
   */
  processTemplate(template, recipientPhoneNumber) {
    // Analyser le template
    const analysisResult = templateParser.analyzeTemplate(template);
    
    // Normaliser les données
    return templateDataNormalizer.createTemplateData(
      template,
      analysisResult,
      recipientPhoneNumber
    );
  },
  
  /**
   * Prépare les données de template pour l'envoi à l'API
   */
  prepareForSending(templateData) {
    return templateDataNormalizer.prepareApiRequest(templateData);
  }
};