/**
 * Mocks de résultats d'analyse de templates WhatsApp pour les tests unitaires
 */

import { VariableType, HeaderFormat } from '@/types/whatsapp-templates';

export const mockAnalysisResults = {
  // Résultat d'analyse pour le template basique
  basicTemplateResult: {
    bodyVariables: [
      { index: 1, type: VariableType.TEXT, value: '', required: true, maxLength: 60, contextPattern: 'bonjour {{1}}, votre' },
      { index: 2, type: VariableType.DATE, value: '', required: true, maxLength: 20, contextPattern: 'pour le {{2}}.' }
    ],
    buttonVariables: [],
    headerMedia: { type: HeaderFormat.NONE },
    hasFooter: false,
    footerText: undefined,
    errors: [],
    warnings: []
  },
  
  // Résultat d'analyse pour le template avec en-tête texte
  textHeaderTemplateResult: {
    bodyVariables: [
      { index: 1, type: VariableType.TEXT, value: '', required: true, maxLength: 60, contextPattern: 'bonjour {{1}}, nous vous' },
      { index: 2, type: VariableType.TEXT, value: '', required: true, maxLength: 60, contextPattern: 'que {{2}}.' }
    ],
    buttonVariables: [],
    headerMedia: { 
      type: HeaderFormat.TEXT,
      url: 'Information importante'
    },
    hasFooter: false,
    footerText: undefined,
    errors: [],
    warnings: []
  },
  
  // Résultat d'analyse pour le template avec en-tête image
  imageHeaderTemplateResult: {
    bodyVariables: [
      { index: 1, type: VariableType.TEXT, value: '', required: true, maxLength: 60, contextPattern: 'nouvelle offre {{1}} à partir' },
      { index: 2, type: VariableType.CURRENCY, value: '', required: true, maxLength: 15, contextPattern: 'à partir de {{2}}!' }
    ],
    buttonVariables: [],
    headerMedia: { 
      type: HeaderFormat.IMAGE
    },
    hasFooter: false,
    footerText: undefined,
    errors: [],
    warnings: []
  },
  
  // Résultat d'analyse pour le template avec pied de page
  footerTemplateResult: {
    bodyVariables: [
      { index: 1, type: VariableType.TEXT, value: '', required: true, maxLength: 60, contextPattern: 'bonjour {{1}}, nous confirmons' },
      { index: 2, type: VariableType.TEXT, value: '', required: true, maxLength: 60, contextPattern: 'commande {{2}}.' }
    ],
    buttonVariables: [],
    headerMedia: { type: HeaderFormat.NONE },
    hasFooter: true,
    footerText: 'Ceci est un message automatique, merci de ne pas y répondre.',
    errors: [],
    warnings: []
  },
  
  // Résultat d'analyse pour le template avec multiples variables
  multiVariableTemplateResult: {
    bodyVariables: [
      { index: 1, type: VariableType.TEXT, value: '', required: true, maxLength: 60, contextPattern: 'bonjour {{1}}, votre commande' },
      { index: 2, type: VariableType.REFERENCE, value: '', required: true, maxLength: 30, contextPattern: 'commande n°{{2}} d\'un montant' },
      { index: 3, type: VariableType.CURRENCY, value: '', required: true, maxLength: 15, contextPattern: 'montant de {{3}} est prévue' },
      { index: 4, type: VariableType.DATE, value: '', required: true, maxLength: 20, contextPattern: 'à la date du {{4}}. pour plus' },
      { index: 5, type: VariableType.EMAIL, value: '', required: true, maxLength: 100, contextPattern: 'par email à {{5}} ou par téléphone' },
      { index: 6, type: VariableType.PHONE, value: '', required: true, maxLength: 20, contextPattern: 'téléphone au {{6}}. suivez votre' },
      { index: 7, type: VariableType.LINK, value: '', required: true, maxLength: 2000, contextPattern: 'notre site: {{7}}.' }
    ],
    buttonVariables: [],
    headerMedia: { type: HeaderFormat.NONE },
    hasFooter: false,
    footerText: undefined,
    errors: [],
    warnings: []
  },
  
  // Résultat d'analyse avec erreurs
  errorTemplateResult: {
    bodyVariables: [],
    buttonVariables: [],
    headerMedia: { type: HeaderFormat.NONE },
    hasFooter: false,
    footerText: undefined,
    errors: ['Erreur lors de l\'analyse du template: Format invalide'],
    warnings: []
  },
  
  // Résultat d'analyse avec avertissements
  warningTemplateResult: {
    bodyVariables: [
      { index: 1, type: VariableType.TEXT, value: '', required: true, maxLength: 60 }
    ],
    buttonVariables: [],
    headerMedia: { type: HeaderFormat.NONE },
    hasFooter: false,
    footerText: undefined,
    errors: [],
    warnings: ['Variable non reconnue trouvée à l\'index 2']
  }
};