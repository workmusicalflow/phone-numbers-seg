import { whatsappApi } from './whatsappApiClient';
import { 
  WhatsAppTemplate,
  WhatsAppTemplateSendResponse
} from '../types/whatsapp-templates';

import {
  WhatsAppTemplateMessage
} from '../types/whatsapp-parameters';

import { whatsAppTemplateServiceV2 } from './whatsapp/index-v2';

// Définition des interfaces pour les réponses API
export interface ApprovedTemplatesResponse {
  status: string;
  templates: WhatsAppTemplate[];
  count: number;
  meta: {
    source: 'api' | 'cache' | 'fallback' | 'client_error';
    usedFallback: boolean;
    timestamp: string;
  };
  message?: string; // Présent uniquement en cas d'erreur
}

export interface TemplateFilters {
  name?: string;
  language?: string;
  category?: string;
  status?: string;
  use_cache?: boolean;
  force_refresh?: boolean;
}

// Liste des erreurs connues avec des messages d'erreur détaillés
const ERROR_DESCRIPTIONS: Record<string, string> = {
  'recipient_not_found': 'Le numéro de téléphone du destinataire est invalide ou n\'est pas enregistré sur WhatsApp.',
  'invalid_template': 'Le template spécifié est invalide ou n\'a pas été approuvé.',
  'invalid_variables': 'Les variables fournies ne correspondent pas aux variables requises dans le template.',
  'api_timeout': 'L\'API WhatsApp a mis trop de temps à répondre. Veuillez réessayer.',
  'rate_limited': 'Limite de débit atteinte. Veuillez ralentir les envois de messages.',
  'outside_window': 'Le destinataire est en dehors de la fenêtre de 24h. Utilisez un template pour initier la conversation.',
  'media_error': 'Erreur avec le média fourni (taille, format ou URL non accessible).',
  'access_denied': 'Accès refusé à l\'API WhatsApp. Vérifiez votre token d\'accès.'
};

/**
 * Client REST pour l'API WhatsApp version 2
 * 
 * Cette version utilise la structure exacte attendue par l'API Meta Cloud
 * pour l'envoi de messages basés sur des templates WhatsApp.
 */
export class WhatsAppRestClientV2 {
  /**
   * Récupère les templates WhatsApp approuvés
   * 
   * @param filters Filtres optionnels pour la recherche
   * @returns Promise contenant les templates et métadonnées
   */
  async getApprovedTemplates(filters: TemplateFilters = {}): Promise<ApprovedTemplatesResponse> {
    try {
      // Construire les paramètres de requête
      const params = new URLSearchParams();
      
      if (filters.name) params.append('name', filters.name);
      if (filters.language) params.append('language', filters.language);
      if (filters.category) params.append('category', filters.category);
      if (filters.status) params.append('status', filters.status);
      
      // Toujours forcer l'utilisation de l'API Meta et rafraîchir les données
      params.append('force_meta', 'true');
      params.append('force_refresh', 'true');
      params.append('use_cache', 'false');
      
      // Activer le mode debug en dev
      if (process.env.NODE_ENV === 'development') {
        params.append('debug', 'true');
      }
      
      // Effectuer la requête - utiliser l'URL complète avec le chemin correct
      const endpoint = `/whatsapp/templates/approved.php?${params.toString()}`;
      console.log(`WhatsApp Templates - Requête vers: ${endpoint}`);
      
      const response = await whatsappApi.get(endpoint);
      
      if (process.env.NODE_ENV === 'development') {
        console.log("WhatsApp Templates - Statut de la réponse:", response.status, response.statusText);
        
        if (response.data) {
          console.log("WhatsApp Templates - Structure:", 
            Object.keys(response.data).join(", "), 
            "Templates count:", response.data.templates?.length || 0,
            "Source:", response.data.meta?.source || 'unknown');
        }
      }
    
      // Vérifier que la réponse est au format attendu
      if (response.data && response.data.templates && Array.isArray(response.data.templates)) {
        if (response.data.status === 'error') {
          console.error('Erreur API templates WhatsApp:', response.data.message);
          throw new Error(response.data.message || 'Erreur API templates WhatsApp');
        }
        
        // Si nous avons un avertissement ou notice, l'afficher en console
        if (response.data.warning) {
          console.warn('⚠️ Avertissement API templates WhatsApp:', response.data.warning);
        } else if (response.data.notice) {
          console.info('ℹ️ Info API templates WhatsApp:', response.data.notice);
        }
        
        return response.data as ApprovedTemplatesResponse;
      }
    
      // Si la réponse n'est pas au format attendu, lever une erreur
      console.error('Format de réponse inattendu:', response.data);
      throw new Error('Format de réponse inattendu');
    } catch (error: any) {
      console.error('Erreur lors de la récupération des templates WhatsApp:', error);
      
      // Retourner une réponse d'erreur formatée
      return {
        status: 'error',
        templates: [],
        count: 0,
        meta: {
          source: 'client_error',
          usedFallback: true,
          timestamp: new Date().toISOString()
        },
        message: error.message || 'Erreur inconnue'
      };
    }
  }
  
  /**
   * Vérifie le statut de l'API REST WhatsApp
   * 
   * @returns Promise contenant l'état de l'API
   */
  async checkApiStatus(): Promise<{
    success: boolean;
    status?: string;
    details?: any;
    error?: string;
  }> {
    try {
      console.log('Vérification du statut de l\'API REST WhatsApp');
      
      const response = await whatsappApi.get('/whatsapp/status.php');
      
      return {
        success: true,
        status: response.data.status || 'online',
        details: response.data
      };
    } catch (error: any) {
      console.error('Erreur lors de la vérification du statut de l\'API:', error);
      return {
        success: false,
        error: error.message || 'Erreur inconnue'
      };
    }
  }

  /**
   * Envoie un message template WhatsApp au format Meta API
   * 
   * @param templateMessage Le message template complet au format Meta
   * @returns Promise contenant le résultat de l'envoi
   */
  async sendTemplateMessageMeta(
    templateMessage: WhatsAppTemplateMessage
  ): Promise<WhatsAppTemplateSendResponse> {
    try {
      console.log('Envoi de message template (format Meta):', 
                 JSON.stringify(templateMessage, null, 2));
      
      // Valider le message avant l'envoi
      this.validateTemplateMessage(templateMessage);
      
      // Vérifier d'abord le statut de l'API
      const statusCheck = await this.checkApiStatus();
      if (!statusCheck.success) {
        throw new Error(`API REST non disponible: ${statusCheck.error}`);
      }
      
      // Utiliser l'endpoint d'envoi de message Meta
      const response = await whatsappApi.post('/whatsapp/send-message.php', templateMessage);
      
      // Analyser la réponse
      if (response.data && response.data.success) {
        return {
          success: true,
          messageId: response.data.messageId,
          timestamp: response.data.timestamp || new Date().toISOString()
        };
      }
      
      // Enrichir le message d'erreur si c'est un code d'erreur connu
      let errorMessage = response.data?.error || 'Échec de l\'envoi du message';
      let errorCode = response.data?.errorCode || '';
      
      if (errorCode && ERROR_DESCRIPTIONS[errorCode]) {
        errorMessage = `${errorMessage}: ${ERROR_DESCRIPTIONS[errorCode]}`;
      }
      
      throw new Error(errorMessage);
    } catch (error: any) {
      console.error('Erreur lors de l\'envoi du message template (format Meta):', error);
      
      const errorInfo = this.analyzeError(error, templateMessage.template.name);
      
      return {
        success: false,
        error: errorInfo
      };
    }
  }
  
  /**
   * Simplification : méthode pour envoyer un template avec paramètres
   * 
   * @param recipientPhone Numéro de téléphone du destinataire
   * @param template Template WhatsApp à utiliser
   * @param bodyValues Valeurs pour les variables du corps
   * @param headerMedia Données du média d'en-tête (si applicable)
   * @returns Promise contenant le résultat de l'envoi
   */
  async sendTemplate(
    recipientPhone: string,
    template: WhatsAppTemplate,
    bodyValues: string[] = [],
    headerMedia?: { type: string; value: string; isId?: boolean }
  ): Promise<WhatsAppTemplateSendResponse> {
    // Utiliser le service de template pour préparer le message
    const templateMessage = whatsAppTemplateServiceV2.prepareApiMessage(
      recipientPhone,
      template,
      bodyValues,
      headerMedia
    );
    
    // Envoyer le message formaté
    return this.sendTemplateMessageMeta(templateMessage);
  }
  
  /**
   * Méthode de compatibilité avec l'ancien format sendTemplateMessageV2
   * 
   * @param data Données au format ancien
   * @returns Promise contenant le résultat de l'envoi
   */
  async sendTemplateMessageV2(
    data: {
      recipientPhoneNumber: string;
      templateName: string;
      templateLanguage: string;
      bodyVariables: string[];
      headerMediaUrl?: string;
      headerMediaId?: string;
    }
  ): Promise<WhatsAppTemplateSendResponse> {
    try {
      // Déterminer le type de média et sa source
      let headerMedia: { type: string; value: string; isId?: boolean } | undefined;
      
      if (data.headerMediaUrl) {
        // Déterminer le type de média à partir de l'extension de l'URL
        const url = data.headerMediaUrl.toLowerCase();
        let mediaType = 'IMAGE'; // Par défaut
        
        if (url.endsWith('.mp4') || url.endsWith('.mov') || url.includes('video')) {
          mediaType = 'VIDEO';
        } else if (url.endsWith('.pdf') || url.endsWith('.doc') || url.endsWith('.docx')) {
          mediaType = 'DOCUMENT';
        }
        
        headerMedia = {
          type: mediaType,
          value: data.headerMediaUrl,
          isId: false
        };
      } else if (data.headerMediaId) {
        // Utiliser l'ID média fourni
        headerMedia = {
          type: 'IMAGE', // Le type n'importe pas autant avec un ID
          value: data.headerMediaId,
          isId: true
        };
      }
      
      // Récupérer d'abord des informations sur le template
      const templateInfo = {
        name: data.templateName,
        language: data.templateLanguage,
        // Ici, nous n'avons pas toutes les informations sur le template,
        // mais suffisamment pour l'envoi
      } as WhatsAppTemplate;
      
      // Envoyer avec la méthode moderne
      return this.sendTemplate(
        data.recipientPhoneNumber,
        templateInfo,
        data.bodyVariables,
        headerMedia
      );
    } catch (error: any) {
      console.error('Erreur lors de l\'envoi du message template V2 (compatibilité):', error);
      return {
        success: false,
        error: error.message || 'Erreur inconnue'
      };
    }
  }
  
  /**
   * Valide un message template avant l'envoi
   */
  private validateTemplateMessage(message: WhatsAppTemplateMessage): void {
    const errors: string[] = [];
    
    // Vérifier les champs obligatoires
    if (!message.to) {
      errors.push('Le numéro de téléphone du destinataire est requis');
    } else if (!this.isValidPhoneNumber(message.to)) {
      errors.push('Le format du numéro de téléphone est invalide');
    }
    
    if (!message.template || !message.template.name) {
      errors.push('Le nom du template est requis');
    }
    
    if (!message.template || !message.template.language || !message.template.language.code) {
      errors.push('La langue du template est requise');
    }
    
    // Si des erreurs ont été trouvées, lancer une exception
    if (errors.length > 0) {
      throw new Error(`Validation échouée: ${errors.join(', ')}`);
    }
  }
  
  /**
   * Vérifie si une chaîne est un numéro de téléphone valide
   */
  private isValidPhoneNumber(phone: string): boolean {
    // Format international avec + et chiffres
    return /^\+[0-9]{8,15}$/.test(phone.replace(/\s/g, ''));
  }
  
  /**
   * Analyse une erreur et fournit un message détaillé
   */
  private analyzeError(error: Error, templateName: string): string {
    const errorMessage = error.message || 'Erreur inconnue';
    
    // Détecter les types d'erreurs courants
    if (errorMessage.includes('outside the allowed window')) {
      return 'Le destinataire n\'a pas interagi avec votre numéro WhatsApp ces dernières 24h. Utilisez un template pour initier une nouvelle conversation.';
    }
    
    if (errorMessage.includes('invalid recipient')) {
      return 'Le numéro de téléphone du destinataire n\'est pas valide ou n\'est pas enregistré sur WhatsApp.';
    }
    
    if (errorMessage.includes('template not found')) {
      return `Le template "${templateName}" n'a pas été trouvé ou n'est pas approuvé.`;
    }
    
    if (errorMessage.includes('parameters do not match template')) {
      return `Les variables fournies ne correspondent pas à celles attendues par le template "${templateName}".`;
    }
    
    if (errorMessage.includes('media')) {
      return `Problème avec le média fourni: ${errorMessage}`;
    }
    
    // Erreur générique
    return `Erreur lors de l'envoi du template "${templateName}": ${errorMessage}`;
  }
}

// Export d'une instance unique
export const whatsAppClientV2 = new WhatsAppRestClientV2();