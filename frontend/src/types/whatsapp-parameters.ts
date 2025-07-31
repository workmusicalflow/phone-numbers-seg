/**
 * Types et interfaces pour les paramètres de templates WhatsApp
 * 
 * Ce fichier définit les structures exactes attendues par l'API Meta
 * pour l'envoi de messages basés sur des templates WhatsApp.
 */

// Types de base pour les paramètres
export enum ParameterType {
  TEXT = 'text',
  CURRENCY = 'currency',
  DATE_TIME = 'date_time',
  IMAGE = 'image',
  VIDEO = 'video',
  DOCUMENT = 'document'
}

// Types de composants de template
export enum ComponentType {
  HEADER = 'header',
  BODY = 'body',
  FOOTER = 'footer',
  BUTTON = 'button'
}

// Sous-types pour les boutons
export enum ButtonSubType {
  QUICK_REPLY = 'quick_reply',
  URL = 'url'
}

/**
 * Paramètre texte - utilisé dans body, header texte, et boutons URL
 */
export interface WhatsAppTextParameter {
  type: ParameterType.TEXT;
  text: string;
}

/**
 * Paramètre devise - utilisé dans body pour les variables monétaires
 */
export interface WhatsAppCurrencyParameter {
  type: ParameterType.CURRENCY;
  currency: {
    fallback_value: string;
    code: string;
    amount_1000: number;
  };
}

/**
 * Paramètre date/heure - utilisé dans body pour les variables temporelles
 */
export interface WhatsAppDateTimeParameter {
  type: ParameterType.DATE_TIME;
  date_time: {
    fallback_value: string;
    day_of_week?: number;
    year?: number;
    month?: number;
    day_of_month?: number;
    hour?: number;
    minute?: number;
    calendar?: string;
  };
}

/**
 * Paramètre image - utilisé dans header
 */
export interface WhatsAppImageParameter {
  type: ParameterType.IMAGE;
  image: {
    id?: string;
    link?: string;
  };
}

/**
 * Paramètre vidéo - utilisé dans header
 */
export interface WhatsAppVideoParameter {
  type: ParameterType.VIDEO;
  video: {
    id?: string;
    link?: string;
  };
}

/**
 * Paramètre document - utilisé dans header
 */
export interface WhatsAppDocumentParameter {
  type: ParameterType.DOCUMENT;
  document: {
    id?: string;
    link?: string;
    filename?: string;
  };
}

/**
 * Type union pour tous les types de paramètres possibles
 */
export type WhatsAppParameter =
  | WhatsAppTextParameter
  | WhatsAppCurrencyParameter
  | WhatsAppDateTimeParameter
  | WhatsAppImageParameter
  | WhatsAppVideoParameter
  | WhatsAppDocumentParameter;

/**
 * Composant de template WhatsApp
 */
export interface WhatsAppTemplateComponent {
  type: ComponentType;
  parameters?: WhatsAppParameter[];
  sub_type?: ButtonSubType;
  index?: string; // Pour les boutons, l'index est une chaîne
}

/**
 * Structure complète pour l'envoi d'un template WhatsApp
 */
export interface WhatsAppTemplateMessage {
  messaging_product: 'whatsapp';
  to: string;
  type: 'template';
  template: {
    name: string;
    language: {
      code: string;
    };
    components?: WhatsAppTemplateComponent[];
  };
}

/**
 * Fonctions utilitaires pour la création de paramètres
 */

/**
 * Crée un paramètre texte
 */
export function createTextParameter(value: string): WhatsAppTextParameter {
  return {
    type: ParameterType.TEXT,
    text: value
  };
}

/**
 * Crée un paramètre devise
 */
export function createCurrencyParameter(
  amount: number,
  code: string = 'XOF',
  fallbackValue: string = ''
): WhatsAppCurrencyParameter {
  return {
    type: ParameterType.CURRENCY,
    currency: {
      fallback_value: fallbackValue || `${amount/1000} ${code}`,
      code,
      amount_1000: amount
    }
  };
}

/**
 * Crée un paramètre date/heure simple
 */
export function createSimpleDateParameter(
  date: Date,
  fallbackValue: string = ''
): WhatsAppDateTimeParameter {
  return {
    type: ParameterType.DATE_TIME,
    date_time: {
      fallback_value: fallbackValue || date.toLocaleDateString(),
      year: date.getFullYear(),
      month: date.getMonth() + 1,
      day_of_month: date.getDate(),
      day_of_week: date.getDay()
    }
  };
}

/**
 * Crée un paramètre image
 */
export function createImageParameter(
  linkOrId: string,
  isId: boolean = false
): WhatsAppImageParameter {
  return {
    type: ParameterType.IMAGE,
    image: isId ? { id: linkOrId } : { link: linkOrId }
  };
}

/**
 * Crée un paramètre vidéo
 */
export function createVideoParameter(
  linkOrId: string,
  isId: boolean = false
): WhatsAppVideoParameter {
  return {
    type: ParameterType.VIDEO,
    video: isId ? { id: linkOrId } : { link: linkOrId }
  };
}

/**
 * Crée un paramètre document
 */
export function createDocumentParameter(
  linkOrId: string,
  isId: boolean = false,
  filename: string = ''
): WhatsAppDocumentParameter {
  return {
    type: ParameterType.DOCUMENT,
    document: {
      ...(isId ? { id: linkOrId } : { link: linkOrId }),
      ...(filename ? { filename } : {})
    }
  };
}

/**
 * Convertit une valeur en paramètre approprié selon le type
 */
export function createParameterFromValue(
  value: string,
  parameterType: ParameterType
): WhatsAppParameter {
  switch (parameterType) {
    case ParameterType.TEXT:
      return createTextParameter(value);
    
    case ParameterType.CURRENCY:
      // Essayer de convertir en nombre
      const numericValue = parseFloat(value.replace(/[^\d.,]/g, '').replace(',', '.')) * 1000;
      if (isNaN(numericValue)) {
        return createTextParameter(value);
      }
      return createCurrencyParameter(numericValue);
    
    case ParameterType.DATE_TIME:
      // Essayer de convertir en date
      const dateValue = new Date(value);
      if (isNaN(dateValue.getTime())) {
        return createTextParameter(value);
      }
      return createSimpleDateParameter(dateValue, value);
    
    case ParameterType.IMAGE:
      return createImageParameter(value);
    
    case ParameterType.VIDEO:
      return createVideoParameter(value);
    
    case ParameterType.DOCUMENT:
      return createDocumentParameter(value);
    
    default:
      return createTextParameter(value);
  }
}