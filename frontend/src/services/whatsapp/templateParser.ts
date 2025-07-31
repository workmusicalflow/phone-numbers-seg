/**
 * Service d'analyse des templates WhatsApp
 * 
 * Ce service fournit des fonctions pour analyser les templates WhatsApp,
 * extraire leurs variables, détecter les types de composants, et normaliser
 * les données pour une utilisation cohérente dans l'application.
 */
import { 
  WhatsAppTemplate, 
  WhatsAppTemplateComponent,
  WhatsAppTemplateButton,
  WhatsAppBodyVariable,
  WhatsAppButtonVariable,
  WhatsAppHeaderMedia,
  TemplateAnalysisResult,
  ComponentType,
  HeaderFormat,
  ButtonType,
  VariableType
} from '../../types/whatsapp-templates';

/**
 * Classe principale du service de parsing de templates
 */
export class WhatsAppTemplateParser {
  
  /**
   * Analyse un template WhatsApp et extrait ses composants, variables et structure
   * @param template Le template WhatsApp à analyser
   * @returns Résultat de l'analyse contenant les variables extraites
   */
  public analyzeTemplate(template: WhatsAppTemplate): TemplateAnalysisResult {
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
          case ComponentType.HEADER:
            this.processHeaderComponent(component, result);
            break;
          
          case ComponentType.BODY:
            this.processBodyComponent(component, result);
            break;
          
          case ComponentType.FOOTER:
            this.processFooterComponent(component, result);
            break;
          
          case ComponentType.BUTTONS:
            this.processButtonsComponent(component, result);
            break;
          
          default:
            result.warnings.push(`Type de composant inconnu: ${componentType} à l'index ${index}`);
        }
      });

      // Vérifications de cohérence
      this.performConsistencyChecks(result);
      
    } catch (error) {
      result.errors.push(`Erreur lors de l'analyse du template: ${error instanceof Error ? error.message : String(error)}`);
      console.error('Erreur d\'analyse du template:', error);
    }

    return result;
  }

  /**
   * Extrait les composants d'un template WhatsApp
   * @param template Le template à analyser
   * @returns Tableau des composants du template
   */
  private extractComponents(template: WhatsAppTemplate): WhatsAppTemplateComponent[] {
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
          const componentsArray: WhatsAppTemplateComponent[] = [];
          
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
   * Normalise le type de composant (gère les différences de casse)
   * @param type Type de composant à normaliser
   * @returns Type de composant normalisé
   */
  private normalizeComponentType(type: string | undefined): ComponentType | string {
    if (!type) return 'unknown';
    
    const upperType = type.toUpperCase();
    
    // Vérifier si le type correspond à une valeur de l'enum ComponentType
    for (const enumValue of Object.values(ComponentType)) {
      if (upperType === enumValue) {
        return enumValue;
      }
    }
    
    return type;
  }

  /**
   * Traite un composant d'en-tête de template
   * @param component Composant d'en-tête
   * @param result Résultat d'analyse à enrichir
   */
  private processHeaderComponent(component: WhatsAppTemplateComponent, result: TemplateAnalysisResult): void {
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
   * Traite un composant de corps de template et extrait les variables
   * @param component Composant de corps
   * @param result Résultat d'analyse à enrichir
   */
  private processBodyComponent(component: WhatsAppTemplateComponent, result: TemplateAnalysisResult): void {
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
   * Traite un composant de pied de page
   * @param component Composant de pied de page
   * @param result Résultat d'analyse à enrichir
   */
  private processFooterComponent(component: WhatsAppTemplateComponent, result: TemplateAnalysisResult): void {
    result.hasFooter = true;
    
    if (component.text) {
      result.footerText = component.text;
    }
  }

  /**
   * Traite un composant de boutons et extrait les variables associées
   * @param component Composant de boutons
   * @param result Résultat d'analyse à enrichir
   */
  private processButtonsComponent(component: WhatsAppTemplateComponent, result: TemplateAnalysisResult): void {
    if (!component.buttons || !Array.isArray(component.buttons)) {
      result.warnings.push('Composant buttons sans tableau de boutons');
      return;
    }
    
    // Analyser chaque bouton
    component.buttons.forEach((button, buttonIndex) => {
      const buttonType = this.normalizeButtonType(button.type);
      
      // Traiter différemment selon le type de bouton
      switch (buttonType) {
        case ButtonType.URL:
          this.processUrlButton(button, buttonIndex, result);
          break;
          
        case ButtonType.QUICK_REPLY:
          this.processQuickReplyButton(button, buttonIndex, result);
          break;
          
        case ButtonType.PHONE_NUMBER:
        case ButtonType.CALL_TO_ACTION:
          this.processActionButton(button, buttonIndex, buttonType, result);
          break;
          
        default:
          result.warnings.push(`Type de bouton inconnu: ${buttonType} à l'index ${buttonIndex}`);
      }
    });
  }

  /**
   * Normalise le type de bouton (gère les différences de casse)
   * @param type Type de bouton à normaliser
   * @returns Type de bouton normalisé
   */
  private normalizeButtonType(type: string | undefined): ButtonType | string {
    if (!type) return 'unknown';
    
    const upperType = type.toUpperCase();
    
    // Vérifier si le type correspond à une valeur de l'enum ButtonType
    for (const enumValue of Object.values(ButtonType)) {
      if (upperType === enumValue) {
        return enumValue;
      }
    }
    
    return type;
  }

  /**
   * Traite un bouton de type URL
   * @param button Information sur le bouton
   * @param buttonIndex Index du bouton
   * @param result Résultat d'analyse à enrichir
   */
  private processUrlButton(button: WhatsAppTemplateButton, buttonIndex: number, result: TemplateAnalysisResult): void {
    // Vérifier si l'URL contient une variable
    const urlVar = button.url ? button.url.match(/{{(\d+)}}/) : null;
    
    const buttonVariable: WhatsAppButtonVariable = {
      index: buttonIndex,
      buttonIndex,
      buttonType: ButtonType.URL,
      type: VariableType.LINK,
      value: '',
      placeholder: 'https://',
      required: true,
      maxLength: 2000
    };
    
    // Si une variable a été trouvée dans l'URL, mettre à jour l'index
    if (urlVar && urlVar[1]) {
      const variableIndex = parseInt(urlVar[1], 10);
      buttonVariable.index = variableIndex;
      buttonVariable.placeholder = `Variable URL {{${variableIndex}}}`;
    }
    
    result.buttonVariables.push(buttonVariable);
  }

  /**
   * Traite un bouton de type réponse rapide
   * @param button Information sur le bouton
   * @param buttonIndex Index du bouton
   * @param result Résultat d'analyse à enrichir
   */
  private processQuickReplyButton(button: WhatsAppTemplateButton, buttonIndex: number, result: TemplateAnalysisResult): void {
    // Vérifier si le payload contient une variable
    const payloadVar = button.payload ? button.payload.match(/{{(\d+)}}/) : null;
    
    if (!payloadVar) {
      // Si pas de variable dans le payload, pas besoin d'ajouter une variable de bouton
      return;
    }
    
    const variableIndex = parseInt(payloadVar[1], 10);
    
    const buttonVariable: WhatsAppButtonVariable = {
      index: variableIndex,
      buttonIndex,
      buttonType: ButtonType.QUICK_REPLY,
      type: VariableType.TEXT,
      value: '',
      placeholder: `Variable payload {{${variableIndex}}}`,
      required: false,
      maxLength: 1000
    };
    
    result.buttonVariables.push(buttonVariable);
  }

  /**
   * Traite un bouton de type action (téléphone, etc.)
   * @param button Information sur le bouton
   * @param buttonIndex Index du bouton
   * @param buttonType Type de bouton
   * @param result Résultat d'analyse à enrichir
   */
  private processActionButton(
    button: WhatsAppTemplateButton, 
    buttonIndex: number, 
    buttonType: ButtonType | string, 
    result: TemplateAnalysisResult
  ): void {
    // Pour le moment, ces boutons n'ont pas de variables
    // À étendre selon les besoins futurs
  }

  /**
   * Extrait un contexte autour d'une position donnée dans un texte
   * @param text Texte complet
   * @param position Position à partir de laquelle extraire
   * @param length Longueur du contexte à extraire
   * @param direction Direction (before/after)
   * @returns Contexte extrait
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
   * @param before Texte avant la variable
   * @param after Texte après la variable
   * @returns Type de variable détecté
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
   * @param type Type de variable
   * @returns Longueur maximale recommandée
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
   * @param variables Tableau de variables à remplir
   */
  private fillArrayGaps(variables: WhatsAppBodyVariable[]): void {
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

  /**
   * Effectue des vérifications de cohérence sur le résultat d'analyse
   * @param result Résultat d'analyse à vérifier
   */
  private performConsistencyChecks(result: TemplateAnalysisResult): void {
    // Vérifier que toutes les variables de corps ont un index unique
    const bodyIndices = new Set<number>();
    for (const variable of result.bodyVariables) {
      if (bodyIndices.has(variable.index)) {
        result.warnings.push(`Variable de corps en doublon à l'index ${variable.index}`);
      }
      bodyIndices.add(variable.index);
    }
    
    // Vérifier que tous les boutons ont un index unique
    const buttonIndices = new Set<number>();
    for (const variable of result.buttonVariables) {
      if (buttonIndices.has(variable.buttonIndex)) {
        result.warnings.push(`Variable de bouton en doublon à l'index ${variable.buttonIndex}`);
      }
      buttonIndices.add(variable.buttonIndex);
    }
  }
}

// Exporter une instance singleton du parser
export const templateParser = new WhatsAppTemplateParser();