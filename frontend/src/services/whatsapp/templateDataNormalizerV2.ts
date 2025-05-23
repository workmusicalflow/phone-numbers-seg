/**
 * Service amélioré de normalisation des données de template WhatsApp
 * 
 * Ce service fournit des fonctions pour standardiser le format des données
 * de template et pour préparer les requêtes vers l'API Meta Cloud.
 */
import {
  WhatsAppTemplate,
  WhatsAppTemplateData,
  WhatsAppBodyVariable,
  WhatsAppButtonVariable,
  TemplateAnalysisResult
} from '../../types/whatsapp-templates';

import {
  WhatsAppTemplateMessage,
  WhatsAppTemplateComponent,
  WhatsAppParameter,
  ComponentType,
  ParameterType,
  createTextParameter,
  createCurrencyParameter,
  createImageParameter,
  createVideoParameter,
  createDocumentParameter
} from '../../types/whatsapp-parameters';

/**
 * Classe de service pour la normalisation des données de template
 */
export class TemplateDataNormalizerV2 {
  /**
   * Prépare une requête complète de message template pour l'API Meta Cloud
   * 
   * @param recipientPhone Numéro de téléphone du destinataire
   * @param template Template WhatsApp à utiliser
   * @param bodyValues Valeurs pour les variables du corps
   * @param headerMedia Données du média d'en-tête (si applicable)
   * @returns Message template formaté pour l'API
   */
  public prepareTemplateMessage(
    recipientPhone: string,
    template: WhatsAppTemplate,
    bodyValues: string[] = [],
    headerMedia?: { type: string; value: string; isId?: boolean }
  ): WhatsAppTemplateMessage {
    // Vérifier et nettoyer le numéro de téléphone
    const cleanedPhone = this.normalizePhoneNumber(recipientPhone);
    
    // Préparer les composants
    const components: WhatsAppTemplateComponent[] = [];
    
    // Ajouter le composant d'en-tête si nécessaire
    if (headerMedia && headerMedia.value) {
      const headerComponent = this.createHeaderComponent(headerMedia);
      if (headerComponent) {
        components.push(headerComponent);
      }
    }
    
    // Ajouter le composant de corps si des variables sont présentes
    if (bodyValues.length > 0) {
      const bodyComponent = this.createBodyComponent(bodyValues);
      if (bodyComponent) {
        components.push(bodyComponent);
      }
    }
    
    // Construire le message complet
    return {
      messaging_product: 'whatsapp',
      to: cleanedPhone,
      type: 'template',
      template: {
        name: template.name,
        language: {
          code: template.language
        },
        components: components.length > 0 ? components : undefined
      }
    };
  }
  
  /**
   * Crée un composant d'en-tête pour l'API
   */
  private createHeaderComponent(
    headerMedia: { type: string; value: string; isId?: boolean }
  ): WhatsAppTemplateComponent | null {
    if (!headerMedia.value) {
      return null;
    }
    
    const parameters: WhatsAppParameter[] = [];
    const isId = !!headerMedia.isId;
    
    // Créer le paramètre approprié selon le type de média
    switch (headerMedia.type.toUpperCase()) {
      case 'IMAGE':
        parameters.push(createImageParameter(headerMedia.value, isId));
        break;
      
      case 'VIDEO':
        parameters.push(createVideoParameter(headerMedia.value, isId));
        break;
      
      case 'DOCUMENT':
        parameters.push(createDocumentParameter(headerMedia.value, isId));
        break;
      
      case 'TEXT':
        parameters.push(createTextParameter(headerMedia.value));
        break;
      
      default:
        // Type inconnu, ne pas créer de paramètre
        return null;
    }
    
    return {
      type: ComponentType.HEADER,
      parameters
    };
  }
  
  /**
   * Crée un composant de corps pour l'API
   */
  private createBodyComponent(
    bodyValues: string[]
  ): WhatsAppTemplateComponent | null {
    if (!bodyValues.length) {
      return null;
    }
    
    // Créer les paramètres texte pour chaque valeur
    const parameters: WhatsAppParameter[] = bodyValues.map(value => 
      createTextParameter(value)
    );
    
    return {
      type: ComponentType.BODY,
      parameters
    };
  }
  
  /**
   * Normalise un numéro de téléphone au format international
   */
  private normalizePhoneNumber(phone: string): string {
    // Supprimer tous les caractères non numériques sauf le +
    let cleaned = phone.replace(/[^\d+]/g, '');
    
    // S'assurer que le numéro commence par +
    if (!cleaned.startsWith('+')) {
      cleaned = '+' + cleaned;
    }
    
    return cleaned;
  }
  
  /**
   * Crée une structure de données standard pour un template à partir du résultat d'analyse
   */
  public createTemplateData(
    template: WhatsAppTemplate,
    analysisResult: TemplateAnalysisResult,
    recipientPhoneNumber: string
  ): WhatsAppTemplateData {
    return {
      recipientPhoneNumber,
      template,
      templateComponentsJsonString: template.componentsJson,
      bodyVariables: analysisResult.bodyVariables,
      buttonVariables: this.convertButtonVariablesToArray(analysisResult.buttonVariables),
      headerMediaType: analysisResult.headerMedia.type,
      headerMediaUrl: analysisResult.headerMedia.url || '',
      headerMediaId: analysisResult.headerMedia.id || '',
      components: template.components
    };
  }
  
  /**
   * Convertit les variables de bouton en format tableau standard
   */
  private convertButtonVariablesToArray(buttonVariables: WhatsAppButtonVariable[]): WhatsAppButtonVariable[] {
    // Déjà au bon format, tri par index de bouton
    return [...buttonVariables].sort((a, b) => a.buttonIndex - b.buttonIndex);
  }
  
  /**
   * Convertit les variables du formulaire en paramètres API
   */
  public convertFormValuesToParameters(
    bodyVariables: WhatsAppBodyVariable[],
    headerMedia?: { type: string; value: string; isId?: boolean }
  ): {
    bodyValues: string[];
    headerMedia?: { type: string; value: string; isId?: boolean };
  } {
    // Extraire les valeurs de corps
    const bodyValues = bodyVariables.map(v => v.value || '');
    
    // Préparer le média d'en-tête
    let processedHeaderMedia = headerMedia;
    
    if (headerMedia && !headerMedia.isId && headerMedia.value.startsWith('http')) {
      // S'assurer que les URLs commencent par https
      if (headerMedia.value.startsWith('http://')) {
        processedHeaderMedia = {
          ...headerMedia,
          value: headerMedia.value.replace('http://', 'https://')
        };
      }
    }
    
    return {
      bodyValues,
      headerMedia: processedHeaderMedia
    };
  }
}

// Exporter une instance singleton du normaliseur
export const templateDataNormalizerV2 = new TemplateDataNormalizerV2();