/**
 * Service de normalisation des données de template WhatsApp
 * 
 * Ce service fournit des fonctions pour standardiser le format des variables
 * de template, assurant une structure cohérente pour les composants d'UI et les appels API.
 */
import {
  WhatsAppTemplate,
  WhatsAppTemplateData,
  WhatsAppTemplateSendRequest,
  WhatsAppBodyVariable,
  WhatsAppButtonVariable,
  TemplateAnalysisResult
} from '../../types/whatsapp-templates';

/**
 * Classe de service pour la normalisation des données de template
 */
export class TemplateDataNormalizer {
  /**
   * Crée une structure de données standard pour un template à partir du résultat d'analyse
   * @param template Le template de base
   * @param analysisResult Le résultat d'analyse du template
   * @param recipientPhoneNumber Le numéro de téléphone du destinataire
   * @returns Données normalisées du template
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
   * @param buttonVariables Variables de bouton à convertir
   * @returns Tableau de variables de bouton
   */
  private convertButtonVariablesToArray(buttonVariables: WhatsAppButtonVariable[]): WhatsAppButtonVariable[] {
    // Déjà au bon format, tri par index de bouton
    return [...buttonVariables].sort((a, b) => a.buttonIndex - b.buttonIndex);
  }

  /**
   * Convertit les variables de bouton en format objet (pour compatibilité)
   * @param buttonVariables Variables de bouton à convertir
   * @returns Objet de variables de bouton
   */
  public convertButtonVariablesToObject(buttonVariables: WhatsAppButtonVariable[]): Record<string | number, string> {
    const result: Record<string | number, string> = {};
    
    buttonVariables.forEach(variable => {
      result[variable.buttonIndex] = variable.value;
    });
    
    return result;
  }

  /**
   * Prépare les données pour l'envoi à l'API
   * @param templateData Données du template à préparer
   * @returns Requête formatée pour l'API d'envoi
   */
  public prepareApiRequest(templateData: WhatsAppTemplateData): WhatsAppTemplateSendRequest {
    const bodyVariablesArray = templateData.bodyVariables.map(v => v.value);
    let buttonVariablesArray: string[] = [];
    
    // Convertir les variables de bouton en tableau de valeurs
    if (Array.isArray(templateData.buttonVariables)) {
      buttonVariablesArray = templateData.buttonVariables.map(btn => btn.value);
    } else {
      buttonVariablesArray = Object.values(templateData.buttonVariables);
    }
    
    const request: WhatsAppTemplateSendRequest = {
      recipientPhoneNumber: templateData.recipientPhoneNumber,
      templateName: templateData.template.name,
      templateLanguage: templateData.template.language,
      templateComponentsJsonString: templateData.templateComponentsJsonString,
      bodyVariables: bodyVariablesArray,
      buttonVariables: buttonVariablesArray
    };
    
    // Ajouter le média d'en-tête si nécessaire
    if (templateData.headerMediaType === 'url' && templateData.headerMediaUrl) {
      request.headerMediaUrl = templateData.headerMediaUrl;
    } else if ((templateData.headerMediaType === 'id' || templateData.headerMediaType === 'upload') && templateData.headerMediaId) {
      request.headerMediaId = templateData.headerMediaId;
    }
    
    return request;
  }

  /**
   * Trouve une variable de bouton par son index
   * @param buttonVariables Liste de variables de bouton
   * @param buttonIndex Index du bouton recherché
   * @returns La variable correspondante ou undefined si non trouvée
   */
  public findButtonVariableByIndex(
    buttonVariables: WhatsAppButtonVariable[] | Record<string | number, string>,
    buttonIndex: number
  ): WhatsAppButtonVariable | undefined {
    if (Array.isArray(buttonVariables)) {
      return buttonVariables.find(btn => btn.buttonIndex === buttonIndex);
    } else {
      // Si c'est un objet, vérifier si la clé existe et créer une variable virtuelle
      if (buttonVariables[buttonIndex] !== undefined) {
        return {
          index: buttonIndex,
          buttonIndex,
          type: 'text',
          buttonType: 'URL',
          value: buttonVariables[buttonIndex]
        };
      }
      return undefined;
    }
  }
}

// Exporter une instance singleton du normaliseur
export const templateDataNormalizer = new TemplateDataNormalizer();