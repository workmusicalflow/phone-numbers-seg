import { whatsappApi } from './whatsappApiClient';
import { 
  WhatsAppTemplate,
  WhatsAppTemplateSendRequest,
  WhatsAppTemplateSendResponse
} from '../types/whatsapp-templates';

// Définition des anciennes interfaces pour rétrocompatibilité
// export type { WhatsAppTemplate }; // Removed this re-export

export interface ApprovedTemplatesResponse {
  status: string;
  templates: WhatsAppTemplate[];
  count: number;
  meta: {
    source: 'api' | 'cache' | 'fallback';
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
 * Client REST pour l'API WhatsApp
 * 
 * Ce client fournit des méthodes pour interagir avec l'API WhatsApp
 * en utilisant des endpoints REST qui sont plus robustes que les requêtes GraphQL
 * directes à l'API Meta.
 */
export class WhatsAppRestClient {
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
    
      // Debug de développement
      if (process.env.NODE_ENV === 'development') {
        console.log('[DEBUG] Réponse de l\'API WhatsApp Templates:', {
          status: response.status,
          templatesCount: response.data?.templates?.length || 0,
          source: response.data?.meta?.source || 'unknown'
        });
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
    
      // Si la réponse n'est pas au format attendu, lever une erreur détaillée
      console.error('Format de réponse inattendu. Attendu: {status, templates[], count, meta}, Reçu:', response.data);
      throw new Error('Format de réponse inattendu');
    } catch (error: any) {
      console.error('Erreur lors de la récupération des templates WhatsApp:', error);
      
      // Retourner une réponse d'erreur formatée
      return {
        status: 'error',
        templates: [],
        count: 0,
        meta: {
          source: 'fallback', // Changed from 'client_error'
          usedFallback: true,
          timestamp: new Date().toISOString()
        },
        message: error.message || 'Erreur inconnue'
      };
    }
  }
  
  /**
   * Récupère un template spécifique par son ID
   * 
   * @param templateId ID du template à récupérer
   * @returns Promise contenant le template ou null
   */
  async getTemplateById(templateId: string): Promise<WhatsAppTemplate | null> {
    try {
      const response = await whatsappApi.get(`/whatsapp/templates/${templateId}`);
      
      if (response.data && response.data.status === 'success' && response.data.template) {
        return response.data.template as WhatsAppTemplate;
      }
      
      return null;
    } catch (error) {
      console.error(`Erreur lors de la récupération du template ${templateId}:`, error);
      return null;
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
   * Envoie un message template WhatsApp v2 (API REST simplifiée)
   * 
   * @param data Données du message à envoyer
   * @returns Promise contenant le résultat de l'envoi
   */
  async sendTemplateMessageV2(data: WhatsAppTemplateSendRequest): Promise<WhatsAppTemplateSendResponse> {
    try {
      console.log('Envoi de message template v2 (REST):', data);
      
      // Valider les données avant l'envoi
      this.validateSendRequest(data);
      
      // Vérifier d'abord le statut de l'API
      const statusCheck = await this.checkApiStatus();
      if (!statusCheck.success) {
        throw new Error(`API REST non disponible: ${statusCheck.error}`);
      }
      
      // Utiliser l'endpoint complet pour l'envoi réel
      const response = await whatsappApi.post('/whatsapp/send-template.php', data);
      
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
      console.error('Erreur lors de l\'envoi du message template REST v2:', error);
      
      const errorInfo = this.analyzeError(error, data.templateName);
      
      return {
        success: false,
        error: errorInfo
      };
    }
  }

  /**
   * Récupère l'historique d'utilisation des templates WhatsApp
   * 
   * @param limit Nombre maximum d'entrées à récupérer
   * @param offset Offset pour la pagination
   * @returns Promise contenant l'historique d'utilisation
   */
  async getTemplateUsageHistory(limit: number = 20, offset: number = 0): Promise<any> {
    try {
      const params = new URLSearchParams();
      params.append('limit', limit.toString());
      params.append('offset', offset.toString());
      
      const endpoint = `/whatsapp/templates/history?${params.toString()}`;
      const response = await whatsappApi.get(endpoint);
      
      if (response.data && response.data.status === 'success') {
        return {
          status: 'success',
          history: response.data.history || [],
          count: response.data.count || 0
        };
      }
      
      throw new Error('Format de réponse inattendu');
    } catch (error: any) {
      console.error('Erreur lors de la récupération de l\'historique des templates:', error);
      return {
        status: 'error',
        history: [],
        count: 0,
        message: error.message || 'Erreur inconnue'
      };
    }
  }

  /**
   * Valide la requête d'envoi avant de la transmettre à l'API
   * @param data Données à valider
   * @throws Error si les données sont invalides
   */
  private validateSendRequest(data: WhatsAppTemplateSendRequest): void {
    const errors: string[] = [];
    
    // Valider les champs obligatoires
    if (!data.recipientPhoneNumber) {
      errors.push('Le numéro de téléphone du destinataire est requis');
    } else if (!this.isValidPhoneNumber(data.recipientPhoneNumber)) {
      errors.push('Le format du numéro de téléphone est invalide, utilisez le format international (+XXX...)');
    }
    
    if (!data.templateName) {
      errors.push('Le nom du template est requis');
    }
    
    if (!data.templateLanguage) {
      errors.push('La langue du template est requise');
    }
    
    // Valider les variables
    if (data.bodyVariables && !Array.isArray(data.bodyVariables)) {
      errors.push('Les variables du corps doivent être un tableau');
    }
    
    if (data.buttonVariables && !Array.isArray(data.buttonVariables)) {
      errors.push('Les variables des boutons doivent être un tableau');
    }
    
    // Vérifier l'en-tête média
    if (data.headerMediaUrl && data.headerMediaId) {
      errors.push('Veuillez spécifier soit une URL de média, soit un ID de média, mais pas les deux');
    }
    
    if (data.headerMediaUrl && !this.isValidUrl(data.headerMediaUrl)) {
      errors.push('L\'URL du média d\'en-tête est invalide');
    }
    
    // Si des erreurs ont été trouvées, lancer une exception
    if (errors.length > 0) {
      throw new Error(`Validation échouée: ${errors.join(', ')}`);
    }
  }

  /**
   * Vérifie si une chaîne est un numéro de téléphone valide
   * @param phone Numéro à vérifier
   * @returns true si le format est valide
   */
  private isValidPhoneNumber(phone: string): boolean {
    // Format international avec + et chiffres
    return /^\+[0-9]{8,15}$/.test(phone.replace(/\s/g, ''));
  }

  /**
   * Vérifie si une chaîne est une URL valide
   * @param url URL à vérifier
   * @returns true si l'URL est valide
   */
  private isValidUrl(url: string): boolean {
    try {
      const parsed = new URL(url);
      return parsed.protocol === 'https:';
    } catch (e) {
      return false;
    }
  }

  /**
   * Analyse une erreur et fournit un message détaillé
   * @param error Erreur survenue
   * @param templateName Nom du template concerné
   * @returns Message d'erreur détaillé
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
export const whatsAppClient = new WhatsAppRestClient();
