/**
 * Service amélioré d'analyse des templates WhatsApp
 * 
 * Ce service analyse les templates WhatsApp et génère la structure de paramètres
 * compatible avec l'API Meta Cloud.
 */
import {
  WhatsAppTemplate,
  WhatsAppTemplateComponent as OriginalComponent,
  TemplateAnalysisResult,
  HeaderFormat,
  ComponentType as OldComponentType,
  VariableType
} from '../../types/whatsapp-templates';

import {
  WhatsAppParameter,
  WhatsAppTemplateComponent,
  WhatsAppTextParameter,
  WhatsAppImageParameter,
  WhatsAppVideoParameter,
  WhatsAppDocumentParameter,
  ComponentType,
  ParameterType,
  createTextParameter,
  createCurrencyParameter,
  createSimpleDateParameter,
  createImageParameter,
  createVideoParameter,
  createDocumentParameter,
  createParameterFromValue
} from '../../types/whatsapp-parameters';

/**
 * Classe avancée de parsing de templates WhatsApp
 */
export class WhatsAppTemplateParserV2 {
  
  /**
   * Analyse un template WhatsApp et génère les structures de paramètres Meta API
   * @param template Le template WhatsApp à analyser
   */
  public analyzeTemplate(template: WhatsAppTemplate): TemplateAnalysisResult {
    // Résultat d'analyse initial avec la structure précédente
    const result: TemplateAnalysisResult = {
      bodyVariables: [],
      buttonVariables: [],
      headerMedia: {
        type: HeaderFormat.NONE
      },
      hasFooter: false,
      errors: [],
      warnings: []
    };
    
    try {
      // Extraire les composants du template
      const components = this.extractComponents(template);
      
      if (!components || components.length === 0) {
        result.warnings.push('Aucun composant trouvé dans le template');
        return result;
      }
      
      // Analyser chaque composant
      components.forEach((component, index) => {
        const componentType = this.normalizeComponentType(component.type);
        
        switch (componentType) {
          case OldComponentType.HEADER:
            this.processHeaderComponent(component, result);
            break;
          
          case OldComponentType.BODY:
            this.processBodyComponent(component, result);
            break;
          
          case OldComponentType.FOOTER:
            this.processFooterComponent(component, result);
            break;
          
          case OldComponentType.BUTTONS:
            // Les boutons ne sont pas inclus dans cette version
            break;
          
          default:
            result.warnings.push(`Type de composant inconnu: ${componentType} à l'index ${index}`);
        }
      });
      
    } catch (error) {
      result.errors.push(`Erreur lors de l'analyse du template: ${error instanceof Error ? error.message : String(error)}`);
      console.error('Erreur d\'analyse du template:', error);
    }
    
    return result;
  }
  
  /**
   * Génère les structures de paramètres pour l'API Meta
   * @param template Le template à analyser
   * @param bodyValues Valeurs pour les variables du corps
   * @param headerMedia Média pour l'en-tête (URL ou ID)
   */
  public generateApiParameters(
    template: WhatsAppTemplate,
    bodyValues: string[] = [],
    headerMedia?: { type: string; value: string; isId?: boolean }
  ): WhatsAppTemplateComponent[] {
    const apiComponents: WhatsAppTemplateComponent[] = [];
    const components = this.extractComponents(template);
    
    if (!components || components.length === 0) {
      return apiComponents;
    }
    
    components.forEach(component => {
      const type = this.normalizeComponentType(component.type);
      
      switch (type) {
        case OldComponentType.HEADER:
          const headerComponent = this.createHeaderComponent(component, headerMedia);
          if (headerComponent) {
            apiComponents.push(headerComponent);
          }
          break;
        
        case OldComponentType.BODY:
          const bodyComponent = this.createBodyComponent(component, bodyValues);
          if (bodyComponent) {
            apiComponents.push(bodyComponent);
          }
          break;
        
        case OldComponentType.FOOTER:
          // Le footer n'a généralement pas de paramètres
          break;
        
        // Les boutons ne sont pas inclus dans cette version
      }
    });
    
    return apiComponents;
  }
  
  /**
   * Crée un composant d'en-tête pour l'API
   */
  private createHeaderComponent(
    originalComponent: OriginalComponent,
    headerMedia?: { type: string; value: string; isId?: boolean }
  ): WhatsAppTemplateComponent | null {
    if (!headerMedia || !headerMedia.value) {
      return null; // Pas de média, pas besoin de paramètre
    }
    
    const headerFormat = originalComponent.format?.toUpperCase();
    let parameter: WhatsAppParameter | null = null;
    
    // Créer le bon type de paramètre selon le format d'en-tête
    switch (headerFormat) {
      case HeaderFormat.TEXT:
        parameter = createTextParameter(headerMedia.value);
        break;
      
      case HeaderFormat.IMAGE:
        parameter = createImageParameter(headerMedia.value, headerMedia.isId);
        break;
      
      case HeaderFormat.VIDEO:
        parameter = createVideoParameter(headerMedia.value, headerMedia.isId);
        break;
      
      case HeaderFormat.DOCUMENT:
        parameter = createDocumentParameter(headerMedia.value, headerMedia.isId);
        break;
      
      default:
        return null; // Format inconnu
    }
    
    // Créer le composant avec le paramètre
    return {
      type: ComponentType.HEADER,
      parameters: parameter ? [parameter] : []
    };
  }
  
  /**
   * Crée un composant de corps avec paramètres pour l'API
   */
  private createBodyComponent(
    originalComponent: OriginalComponent,
    bodyValues: string[] = []
  ): WhatsAppTemplateComponent | null {
    if (!originalComponent.text || bodyValues.length === 0) {
      return null;
    }
    
    // Extraire les variables avec regex
    const regex = /{{(\d+)}}/g;
    let match;
    const parameters: WhatsAppParameter[] = [];
    
    while ((match = regex.exec(originalComponent.text)) !== null) {
      const variableIndex = parseInt(match[1], 10) - 1;
      
      // Vérifier que l'index est valide et qu'une valeur existe
      if (isNaN(variableIndex) || variableIndex < 0 || variableIndex >= bodyValues.length) {
        continue;
      }
      
      // Obtenir la valeur de la variable
      const value = bodyValues[variableIndex] || '';
      
      // Détecter automatiquement le type basé sur le contenu
      const type = this.detectParameterType(value);
      
      // Créer le paramètre approprié
      const parameter = createParameterFromValue(value, type);
      parameters.push(parameter);
    }
    
    // Si aucun paramètre n'a été trouvé, ne pas créer de composant
    if (parameters.length === 0) {
      return null;
    }
    
    return {
      type: ComponentType.BODY,
      parameters
    };
  }
  
  /**
   * Détecte automatiquement le type de paramètre basé sur la valeur
   */
  private detectParameterType(value: string): ParameterType {
    // Vérifier si c'est une URL
    if (value.startsWith('http://') || value.startsWith('https://')) {
      // Vérifier si c'est une image, une vidéo ou un document
      const extension = value.split('.').pop()?.toLowerCase();
      
      if (extension) {
        if (['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(extension)) {
          return ParameterType.IMAGE;
        }
        
        if (['mp4', 'mov', 'avi', 'webm'].includes(extension)) {
          return ParameterType.VIDEO;
        }
        
        if (['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'].includes(extension)) {
          return ParameterType.DOCUMENT;
        }
      }
      
      return ParameterType.TEXT;
    }
    
    // Vérifier si c'est une devise
    if (/^\d+([.,]\d{1,2})?\s*[€$£¥FCFA\w]+$/.test(value)) {
      return ParameterType.CURRENCY;
    }
    
    // Vérifier si c'est une date
    if (/^\d{1,2}[\/.-]\d{1,2}[\/.-]\d{2,4}$/.test(value)) {
      return ParameterType.DATE_TIME;
    }
    
    // Par défaut, c'est du texte
    return ParameterType.TEXT;
  }
  
  /**
   * Extrait les composants d'un template WhatsApp
   */
  private extractComponents(template: WhatsAppTemplate): OriginalComponent[] {
    // Si les composants sont déjà présents dans l'objet, les utiliser
    if (template.components && Array.isArray(template.components) && template.components.length > 0) {
      return template.components;
    }
    
    // Sinon, essayer de les extraire du JSON
    try {
      if (template.componentsJson) {
        const parsedComponents = JSON.parse(template.componentsJson);
        
        // Gérer le cas où les composants sont un objet plutôt qu'un tableau
        if (!Array.isArray(parsedComponents)) {
          const componentsArray: OriginalComponent[] = [];
          
          for (const key in parsedComponents) {
            if (Object.prototype.hasOwnProperty.call(parsedComponents, key)) {
              componentsArray.push({
                type: key.toUpperCase(),
                ...parsedComponents[key]
              });
            }
          }
          
          return componentsArray;
        }
        
        return parsedComponents;
      }
    } catch (error) {
      console.error('Erreur lors du parsing des composants JSON:', error);
      throw new Error(`Impossible de parser les composants JSON: ${error instanceof Error ? error.message : String(error)}`);
    }
    
    return [];
  }
  
  /**
   * Normalise le type de composant
   */
  private normalizeComponentType(type: string | undefined): OldComponentType | string {
    if (!type) return 'unknown';
    
    const upperType = type.toUpperCase();
    
    // Vérifier si le type correspond à une valeur de l'enum ComponentType
    for (const enumValue of Object.values(OldComponentType)) {
      if (upperType === enumValue) {
        return enumValue;
      }
    }
    
    return type;
  }
  
  /**
   * Traite un composant d'en-tête pour l'analyse interne
   */
  private processHeaderComponent(component: OriginalComponent, result: TemplateAnalysisResult): void {
    // Détecter le format de l'en-tête
    const headerFormat = component.format?.toUpperCase() as HeaderFormat || HeaderFormat.TEXT;
    
    result.headerMedia.type = headerFormat;
    
    // Si le composant a un exemple avec header_handle, l'enregistrer comme ID
    if (component.example && component.example.header_handle && Array.isArray(component.example.header_handle)) {
      result.headerMedia.id = component.example.header_handle[0];
    }
    
    // Pour les en-têtes texte, conserver le texte
    if (headerFormat === HeaderFormat.TEXT && component.text) {
      result.headerMedia.url = component.text;
    }
  }
  
  /**
   * Traite un composant de corps pour l'analyse interne
   */
  private processBodyComponent(component: OriginalComponent, result: TemplateAnalysisResult): void {
    if (!component.text) {
      result.warnings.push('Composant body sans texte');
      return;
    }
    
    // Extraire les variables du texte avec regex
    const regex = /{{(\d+)}}/g;
    let match;
    
    while ((match = regex.exec(component.text)) !== null) {
      const variableIndex = parseInt(match[1], 10);
      
      // Vérifier que l'index est valide
      if (isNaN(variableIndex) || variableIndex < 1) {
        result.warnings.push(`Index de variable invalide: ${match[0]}`);
        continue;
      }
      
      const index = variableIndex - 1;
      
      // Extraire le contexte pour déterminer le type de variable
      const contextBefore = this.extractContext(component.text, match.index, 30, 'before');
      const contextAfter = this.extractContext(component.text, match.index + match[0].length, 30, 'after');
      
      // Déterminer le type de variable basé sur le contexte
      const variableType = this.determineVariableType(contextBefore, contextAfter);
      
      // Créer la variable si elle n'existe pas déjà
      if (!result.bodyVariables[index]) {
        result.bodyVariables[index] = {
          index: variableIndex,
          type: variableType,
          value: '',
          contextPattern: `${contextBefore}{{${variableIndex}}}${contextAfter}`,
          required: true,
          maxLength: this.getMaxLengthByType(variableType)
        };
      }
    }
    
    // Assurons-nous que le tableau n'a pas de trous
    this.fillArrayGaps(result.bodyVariables);
  }
  
  /**
   * Traite un composant de pied de page pour l'analyse interne
   */
  private processFooterComponent(component: OriginalComponent, result: TemplateAnalysisResult): void {
    result.hasFooter = true;
    
    if (component.text) {
      result.footerText = component.text;
    }
  }
  
  /**
   * Extrait un contexte autour d'une position donnée dans un texte
   */
  private extractContext(text: string, position: number, length: number, direction: 'before' | 'after'): string {
    if (direction === 'before') {
      const start = Math.max(0, position - length);
      return text.substring(start, position).toLowerCase();
    } else {
      const end = Math.min(text.length, position + length);
      return text.substring(position, end).toLowerCase();
    }
  }
  
  /**
   * Détermine le type de variable en fonction du contexte
   */
  private determineVariableType(before: string, after: string): VariableType {
    // Date
    if (before.includes('date') || after.includes('date')) {
      return VariableType.DATE;
    }
    
    // Heure
    if (before.includes('heure') || after.includes('heure') || before.includes('horaire')) {
      return VariableType.TIME;
    }
    
    // Prix/Montant
    if (before.includes('prix') || before.includes('montant') || before.includes('tarif') || 
        before.includes('€') || after.includes('€') || before.includes('euro') || 
        before.includes('fcfa') || after.includes('fcfa')) {
      return VariableType.CURRENCY;
    }
    
    // Référence
    if (before.includes('référence') || before.includes('ref') || before.includes('code')) {
      return VariableType.REFERENCE;
    }
    
    // Email
    if (before.includes('email') || before.includes('e-mail') || before.includes('mail') || 
        before.includes('@') || after.includes('@')) {
      return VariableType.EMAIL;
    }
    
    // Téléphone
    if (before.includes('téléphone') || before.includes('tel') || before.includes('portable') || 
        before.includes('contact')) {
      return VariableType.PHONE;
    }
    
    // Nombre
    if (before.includes('nombre') || before.includes('numéro') || after.includes('nombre')) {
      return VariableType.NUMBER;
    }
    
    // Lien
    if (before.includes('lien') || after.includes('lien') || before.includes('url') || after.includes('url') ||
        before.includes('http') || after.includes('http')) {
      return VariableType.LINK;
    }
    
    // Par défaut, texte simple
    return VariableType.TEXT;
  }
  
  /**
   * Obtient la longueur maximale recommandée en fonction du type de variable
   */
  private getMaxLengthByType(type: VariableType | string): number {
    switch (type) {
      case VariableType.DATE:
        return 20;
      case VariableType.TIME:
        return 10;
      case VariableType.CURRENCY:
        return 15;
      case VariableType.EMAIL:
        return 100;
      case VariableType.PHONE:
        return 20;
      case VariableType.REFERENCE:
        return 30;
      case VariableType.NUMBER:
        return 10;
      case VariableType.LINK:
        return 2000;
      default:
        return 60;
    }
  }
  
  /**
   * Remplit les trous dans un tableau de variables
   */
  private fillArrayGaps(variables: any[]): void {
    // Trouver l'index le plus élevé
    let maxIndex = -1;
    for (let i = 0; i < variables.length; i++) {
      if (variables[i] && variables[i].index > maxIndex) {
        maxIndex = variables[i].index;
      }
    }
    
    // Remplir les trous avec des variables texte par défaut
    for (let i = 0; i < maxIndex; i++) {
      if (!variables[i]) {
        variables[i] = {
          index: i + 1,
          type: VariableType.TEXT,
          value: '',
          required: true,
          maxLength: 60
        };
      }
    }
  }
}

// Exporter une instance singleton du parser
export const templateParserV2 = new WhatsAppTemplateParserV2();