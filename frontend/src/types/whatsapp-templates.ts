/**
 * Interfaces TypeScript pour les templates WhatsApp
 * 
 * Ce fichier définit l'ensemble des interfaces utilisées pour représenter
 * les templates WhatsApp, leurs composants, variables et structures associées.
 * Ces interfaces servent de contrat entre les différentes parties du système.
 */

/**
 * Représente un template WhatsApp complet
 */
export interface WhatsAppTemplate {
  id: string;
  name: string;
  category: string;
  language: string;
  status: string;
  description?: string;
  componentsJson?: string;
  components?: WhatsAppTemplateComponent[];
  bodyVariablesCount?: number;
  hasMediaHeader?: boolean;
  hasButtons?: boolean;
  buttonsCount?: number;
  hasFooter?: boolean;
}

/**
 * Énumération des types de composants possibles dans un template
 */
export enum ComponentType {
  HEADER = 'HEADER',
  BODY = 'BODY',
  FOOTER = 'FOOTER',
  BUTTONS = 'BUTTONS'
}

/**
 * Énumération des formats possibles pour l'en-tête d'un template
 */
export enum HeaderFormat {
  TEXT = 'TEXT',
  IMAGE = 'IMAGE',
  VIDEO = 'VIDEO',
  DOCUMENT = 'DOCUMENT',
  NONE = 'NONE'
}

/**
 * Énumération des types de boutons disponibles
 */
export enum ButtonType {
  URL = 'URL',
  QUICK_REPLY = 'QUICK_REPLY',
  PHONE_NUMBER = 'PHONE_NUMBER',
  CALL_TO_ACTION = 'CALL_TO_ACTION'
}

/**
 * Énumération des types de variables supportés
 */
export enum VariableType {
  TEXT = 'text',
  DATE = 'date',
  TIME = 'time',
  CURRENCY = 'currency',
  EMAIL = 'email',
  PHONE = 'phone',
  REFERENCE = 'reference',
  NUMBER = 'number',
  LINK = 'link'
}

/**
 * Interface générique pour un composant de template
 */
export interface WhatsAppTemplateComponent {
  type: ComponentType | string;
  format?: HeaderFormat | string;
  text?: string;
  example?: any;
  buttons?: WhatsAppTemplateButton[];
  parameters?: any[];
}

/**
 * Interface pour un bouton de template
 */
export interface WhatsAppTemplateButton {
  type: ButtonType | string;
  text: string;
  url?: string;
  phone_number?: string;
  payload?: string;
}

/**
 * Interface de base pour toutes les variables de template
 */
export interface WhatsAppTemplateVariable {
  index: number;
  type: VariableType | string;
  value: string;
  placeholder?: string;
  required?: boolean;
  maxLength?: number;
}

/**
 * Interface pour les variables standardisées du corps du message
 */
export interface WhatsAppBodyVariable extends WhatsAppTemplateVariable {
  contextPattern?: string; // Texte avant/après pour aider à l'identification du type
}

/**
 * Interface pour les variables de bouton
 */
export interface WhatsAppButtonVariable extends WhatsAppTemplateVariable {
  buttonIndex: number;
  buttonType: ButtonType | string;
}

/**
 * Interface pour les médias d'en-tête
 */
export interface WhatsAppHeaderMedia {
  type: HeaderFormat | string;
  url?: string;
  id?: string;
  filename?: string;
}

/**
 * Interface pour les données de template préparées pour l'envoi
 */
export interface WhatsAppTemplateData {
  recipientPhoneNumber: string;
  template: WhatsAppTemplate;
  templateComponentsJsonString?: string;
  bodyVariables: WhatsAppBodyVariable[];
  buttonVariables: Record<string | number, string> | WhatsAppButtonVariable[];
  headerMediaType: string;
  headerMediaUrl?: string;
  headerMediaId?: string;
  components?: WhatsAppTemplateComponent[];
}

/**
 * Interface pour la requête d'envoi de template à l'API
 */
export interface WhatsAppTemplateSendRequest {
  recipientPhoneNumber: string;
  templateName: string;
  templateLanguage: string;
  templateComponentsJsonString?: string;
  headerMediaUrl?: string;
  headerMediaId?: string;
  bodyVariables: string[];
  buttonVariables: string[];
}

/**
 * Interface pour la réponse d'envoi de template
 */
export interface WhatsAppTemplateSendResponse {
  success: boolean;
  messageId?: string;
  timestamp?: string;
  error?: string;
}

/**
 * Interface pour le résultat d'analyse d'un template
 */
export interface TemplateAnalysisResult {
  bodyVariables: WhatsAppBodyVariable[];
  buttonVariables: WhatsAppButtonVariable[];
  headerMedia: WhatsAppHeaderMedia;
  hasFooter: boolean;
  footerText?: string;
  errors: string[];
  warnings: string[];
}