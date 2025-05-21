import { api } from './api';

/**
 * Service client REST pour l'API WhatsApp
 * 
 * Ce client fournit une interface robuste pour interagir avec le backend WhatsApp
 * via les endpoints REST qui ont été conçus pour être plus fiables que les requêtes GraphQL
 * directes à l'API Meta.
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
      const endpoint = `/api/whatsapp/templates/approved.php?${params.toString()}`;
      console.log(`WhatsApp Templates - Requête vers: ${endpoint}`);
      
      const response = await api.get(endpoint);
      
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
    recipient: string;
    templateName: string;
    languageCode: string;
    components?: any[];
    headerImageUrl?: string;
    headerMediaId?: string;
    bodyParams?: string[];
  }): Promise<any> {
    try {
      const response = await api.post('whatsapp/messages/template', data);
      return response.data;
    } catch (error) {
      console.error('Erreur lors de l\'envoi du message template:', error);
      throw error;
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