import WhatsAppTemplateVariableDateInput from './WhatsAppTemplateVariableDateInput.vue';
import WhatsAppTemplateVariableTimeInput from './WhatsAppTemplateVariableTimeInput.vue';
import WhatsAppTemplateVariableCurrencyInput from './WhatsAppTemplateVariableCurrencyInput.vue';
import WhatsAppTemplateVariableEmailInput from './WhatsAppTemplateVariableEmailInput.vue';
import WhatsAppTemplateVariablePhoneInput from './WhatsAppTemplateVariablePhoneInput.vue';
import WhatsAppTemplateVariableReferenceInput from './WhatsAppTemplateVariableReferenceInput.vue';
import WhatsAppTemplateVariableNumberInput from './WhatsAppTemplateVariableNumberInput.vue';
import WhatsAppTemplateVariableTextInput from './WhatsAppTemplateVariableTextInput.vue';

export {
  WhatsAppTemplateVariableDateInput,
  WhatsAppTemplateVariableTimeInput,
  WhatsAppTemplateVariableCurrencyInput,
  WhatsAppTemplateVariableEmailInput,
  WhatsAppTemplateVariablePhoneInput,
  WhatsAppTemplateVariableReferenceInput,
  WhatsAppTemplateVariableNumberInput,
  WhatsAppTemplateVariableTextInput
};

// Mapping des types de variables vers leurs composants
export const WhatsAppTemplateVariableInputMapping = {
  date: WhatsAppTemplateVariableDateInput,
  time: WhatsAppTemplateVariableTimeInput,
  currency: WhatsAppTemplateVariableCurrencyInput,
  email: WhatsAppTemplateVariableEmailInput,
  phone: WhatsAppTemplateVariablePhoneInput,
  reference: WhatsAppTemplateVariableReferenceInput,
  number: WhatsAppTemplateVariableNumberInput,
  text: WhatsAppTemplateVariableTextInput
};

// Limites de caractères par type
export const WhatsAppTemplateVariableLimits = {
  date: 20,
  time: 10,
  currency: 15,
  email: 100,
  phone: 20,
  reference: 30,
  number: 10,
  text: 60
};

// Labels par type
export const WhatsAppTemplateVariableLabels = {
  date: 'Date',
  time: 'Heure',
  currency: 'Montant',
  email: 'Email',
  phone: 'Téléphone',
  reference: 'Référence',
  number: 'Nombre',
  text: 'Texte'
};

// Placeholder par type
export const WhatsAppTemplateVariablePlaceholders = {
  date: 'JJ/MM/AAAA',
  time: 'HH:MM',
  currency: '0,00 €',
  email: 'exemple@domaine.com',
  phone: '+225 XX XX XX XX',
  reference: 'REF-12345',
  number: '0',
  text: 'Entrez du texte'
};

// Utiliser pour obtenir les informations d'un type de variable
export function getVariableTypeInfo(type: string) {
  const safeType = type && typeof type === 'string' ? type.toLowerCase() : 'text';
  
  return {
    component: WhatsAppTemplateVariableInputMapping[safeType] || WhatsAppTemplateVariableTextInput,
    limit: WhatsAppTemplateVariableLimits[safeType] || 60,
    label: WhatsAppTemplateVariableLabels[safeType] || 'Texte',
    placeholder: WhatsAppTemplateVariablePlaceholders[safeType] || 'Entrez du texte'
  };
}

export default {
  install(app) {
    app.component('WhatsAppTemplateVariableDateInput', WhatsAppTemplateVariableDateInput);
    app.component('WhatsAppTemplateVariableTimeInput', WhatsAppTemplateVariableTimeInput);
    app.component('WhatsAppTemplateVariableCurrencyInput', WhatsAppTemplateVariableCurrencyInput);
    app.component('WhatsAppTemplateVariableEmailInput', WhatsAppTemplateVariableEmailInput);
    app.component('WhatsAppTemplateVariablePhoneInput', WhatsAppTemplateVariablePhoneInput);
    app.component('WhatsAppTemplateVariableReferenceInput', WhatsAppTemplateVariableReferenceInput);
    app.component('WhatsAppTemplateVariableNumberInput', WhatsAppTemplateVariableNumberInput);
    app.component('WhatsAppTemplateVariableTextInput', WhatsAppTemplateVariableTextInput);
  }
};