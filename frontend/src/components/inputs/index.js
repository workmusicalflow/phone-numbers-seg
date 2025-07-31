// WhatsApp Template Variable Input Components
import WhatsAppTemplateVariableDateInput from './WhatsAppTemplateVariableDateInput.vue';
import WhatsAppTemplateVariableTimeInput from './WhatsAppTemplateVariableTimeInput.vue';
import WhatsAppTemplateVariableCurrencyInput from './WhatsAppTemplateVariableCurrencyInput.vue';
import WhatsAppTemplateVariableEmailInput from './WhatsAppTemplateVariableEmailInput.vue';
import WhatsAppTemplateVariablePhoneInput from './WhatsAppTemplateVariablePhoneInput.vue';
import WhatsAppTemplateVariableReferenceInput from './WhatsAppTemplateVariableReferenceInput.vue';
import WhatsAppTemplateVariableNumberInput from './WhatsAppTemplateVariableNumberInput.vue';
import WhatsAppTemplateVariableTextInput from './WhatsAppTemplateVariableTextInput.vue';

// Variable type limits
const WhatsAppTemplateVariableLimits = {
  text: 60,
  number: 20,
  date: 20,
  time: 10,
  currency: 20,
  email: 50,
  phone: 20,
  reference: 30
};

export {
  WhatsAppTemplateVariableDateInput,
  WhatsAppTemplateVariableTimeInput,
  WhatsAppTemplateVariableCurrencyInput,
  WhatsAppTemplateVariableEmailInput,
  WhatsAppTemplateVariablePhoneInput,
  WhatsAppTemplateVariableReferenceInput,
  WhatsAppTemplateVariableNumberInput,
  WhatsAppTemplateVariableTextInput,
  WhatsAppTemplateVariableLimits
};