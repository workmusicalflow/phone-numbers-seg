import { whatsappApi } from './whatsappApiClient';

/**
 * Service client REST pour l'API WhatsApp
 * 
 * Ce client fournit une interface robuste pour interagir avec le backend WhatsApp
 * via les endpoints REST qui ont été conçus pour être plus fiables que les requêtes GraphQL
 * directes à l'API Meta.
 * 
 * Utilise le client whatsappApi dédié pour gérer correctement les chemins relatifs et absolus.
 */
export interface WhatsAppTemplate {
  id: string;
  name: string;
  category: string;
  language: string;
  status: string;
  components: any[];
  description?: string;
  componentsJson?: string;
  bodyVariablesCount?: number;
  hasMediaHeader?: boolean;
  hasButtons?: boolean;
  buttonsCount?: number;
  hasFooter?: boolean;
}

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
   * Récupère un template spécifique par son ID
   * 
   * @param templateId ID du template à récupérer
   * @returns Promise contenant le template ou null
   */
  async getTemplateById(templateId: string): Promise<WhatsAppTemplate | null> {
    try {
      const response = await api.get(`whatsapp/templates/${templateId}`);
      
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
   * Envoie un message template WhatsApp
   * 
   * @param data Données du message à envoyer
   * @returns Promise contenant le résultat de l'envoi
   */
  async sendTemplateMessage(data: {
    recipientPhoneNumber: string;
    templateName: string;
    templateId: string;
    languageCode: string;
    components: {
      header?: {
        type: string;
        parameters: Array<{
          type: string;
          [key: string]: any;
        }>;
      };
      body?: {
        parameters: Array<{
          type: string;
          text: string;
        }>;
      };
      buttons?: Array<{
        type: string;
        index: number;
        parameters: Array<{
          type: string;
          [key: string]: any;
        }>;
      }>;
    };
  }): Promise<{
    success: boolean;
    messageId?: string;
    timestamp?: string;
    error?: string;
  }> {
    try {
      console.log('Envoi de message template:', data);
      
      // Préparer les données pour l'API
      const apiData = {
        to: data.recipientPhoneNumber,
        template: {
          name: data.templateName,
          language: {
            code: data.languageCode
          },
          components: []
        }
      };
      
      // Ajouter le composant d'en-tête si présent
      if (data.components.header) {
        apiData.template.components.push(data.components.header);
      }
      
      // Ajouter le composant de corps si présent
      if (data.components.body) {
        apiData.template.components.push({
          type: 'body',
          parameters: data.components.body.parameters
        });
      }
      
      // Ajouter les composants de boutons si présents
      if (data.components.buttons && data.components.buttons.length > 0) {
        data.components.buttons.forEach(button => {
          apiData.template.components.push(button);
        });
      }
      
      // Envoyer la requête à l'API
      const response = await api.post('/api/whatsapp/send-template.php', apiData);
      
      // Analyser la réponse
      if (response.data && response.data.success) {
        return {
          success: true,
          messageId: response.data.messageId || response.data.id,
          timestamp: response.data.timestamp || new Date().toISOString()
        };
      }
      
      throw new Error(response.data?.error || 'Échec de l\'envoi du message');
    } catch (error: any) {
      console.error('Erreur lors de l\'envoi du message template:', error);
      return {
        success: false,
        error: error.message || 'Erreur inconnue lors de l\'envoi'
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
   * Envoie un message template WhatsApp v2 (API REST simplifiée)
   * 
   * @param data Données du message à envoyer
   * @returns Promise contenant le résultat de l'envoi
   */
  async sendTemplateMessageV2(data: {
    recipientPhoneNumber: string;
    templateName: string;
    templateLanguage: string;
    templateComponentsJsonString?: string;
    headerMediaUrl?: string;
    headerMediaId?: string;
    bodyVariables?: string[];
    buttonVariables?: string[];
  }): Promise<{
    success: boolean;
    messageId?: string;
    timestamp?: string;
    error?: string;
  }> {
    try {
      console.log('Envoi de message template v2 (REST):', data);
      
      // Vérifier d'abord le statut de l'API
      const statusCheck = await this.checkApiStatus();
      if (!statusCheck.success) {
        throw new Error(`API REST non disponible: ${statusCheck.error}`);
      }
      
      // Utiliser l'endpoint complet pour l'envoi réel
      const response = await whatsappApi.post('/whatsapp/send-template-v2.php', data);
      
      // Analyser la réponse
      if (response.data && response.data.success) {
        return {
          success: true,
          messageId: response.data.messageId,
          timestamp: response.data.timestamp || new Date().toISOString()
        };
      }
      
      throw new Error(response.data?.error || 'Échec de l\'envoi du message');
    } catch (error: any) {
      console.error('Erreur lors de l\'envoi du message template REST v2:', error);
      return {
        success: false,
        error: error.message || 'Erreur inconnue lors de l\'envoi'
      };
    }
  }
  
  /**
   * Envoie un message template avancé avec composants détaillés
   * 
   * @param data Données du message à envoyer
   * @returns Promise contenant le résultat de l'envoi
   */
  async sendTemplateMessageWithComponents(data: {
    recipient: string;
    templateName: string;
    languageCode: string;
    components: any[];
    headerMediaId?: string;
  }): Promise<any> {
    try {
      const response = await api.post('whatsapp/messages/template/advanced', data);
      return response.data;
    } catch (error) {
      console.error('Erreur lors de l\'envoi du message template avancé:', error);
      throw error;
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
      
      const endpoint = `whatsapp/templates/history?${params.toString()}`;
      const response = await api.get(endpoint);
      
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
}

// Export d'une instance unique
export const whatsAppClient = new WhatsAppRestClient();