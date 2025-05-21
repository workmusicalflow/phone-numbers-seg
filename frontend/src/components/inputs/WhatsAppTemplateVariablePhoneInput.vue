<template>
  <div class="whatsapp-template-variable-phone-input">
    <q-input
      v-model="displayValue"
      :label="label"
      :hint="hint"
      :placeholder="placeholder || '+XXX XX XX XX XX'"
      :rules="compoundRules"
      :outlined="outlined"
      :dense="dense"
      counter
      :maxlength="maxlength"
      :readonly="readonly"
      :disable="disable"
      :clearable="clearable"
      @focus="$emit('focus', $event)"
      @blur="onBlur"
      @update:model-value="updateValue"
      autocomplete="tel"
      type="tel"
    >
      <template v-slot:prepend>
        <q-icon name="phone" />
      </template>
      <template v-slot:append v-if="$slots.append">
        <slot name="append"></slot>
      </template>
    </q-input>
  </div>
</template>

<script>
import { defineComponent, computed, ref, watch } from 'vue';

export default defineComponent({
  name: 'WhatsAppTemplateVariablePhoneInput',
  props: {
    modelValue: {
      type: String,
      default: ''
    },
    label: {
      type: String,
      default: 'Téléphone'
    },
    hint: {
      type: String,
      default: ''
    },
    placeholder: {
      type: String,
      default: ''
    },
    rules: {
      type: Array,
      default: () => []
    },
    outlined: {
      type: Boolean,
      default: true
    },
    dense: {
      type: Boolean,
      default: false
    },
    maxlength: {
      type: Number,
      default: 20
    },
    readonly: {
      type: Boolean,
      default: false
    },
    disable: {
      type: Boolean,
      default: false
    },
    clearable: {
      type: Boolean,
      default: true
    },
    defaultCountryCode: {
      type: String,
      default: '+225' // Côte d'Ivoire
    }
  },
  emits: ['update:model-value', 'focus', 'blur'],
  setup(props, { emit }) {
    const displayValue = ref(props.modelValue || '');

    // Basic phone number validation regex
    // Note: This is a simplified validation that just checks for a valid international format
    const phoneRegex = /^\+\d{1,3}[\s.-]?\d{1,14}$/;

    // Country specific rules
    const countryRules = {
      '+225': /^\+225[\s.-]?\d{8}$/, // Côte d'Ivoire
      '+33': /^\+33[\s.-]?[1-9]\d{8}$/, // France
      '+1': /^\+1[\s.-]?\d{10}$/ // USA/Canada
    };

    // Phone validation rule
    const phoneRule = (val) => {
      if (!val) return true;
      
      // Check if it has a valid country code
      if (!val.startsWith('+')) {
        return 'Le numéro doit commencer par un indicatif pays (+XXX)';
      }
      
      // Extract country code
      const countryCode = val.substring(0, 4).replace(/[^+\d]/g, '');
      
      // Check if we have specific rules for this country
      for (const [code, regex] of Object.entries(countryRules)) {
        if (val.startsWith(code)) {
          if (!regex.test(val)) {
            return `Format invalide pour ${code}`;
          }
          return true;
        }
      }
      
      // Generic international format check
      if (!phoneRegex.test(val)) {
        return 'Format de numéro de téléphone international invalide';
      }
      
      return true;
    };

    // Combine custom validation rules with phone rule
    const compoundRules = computed(() => {
      return [...props.rules, phoneRule];
    });

    // Format phone number on blur
    const formatPhoneNumber = (value) => {
      if (!value) return '';
      
      // Remove all non-digit characters except the + at the beginning
      let digitsOnly = value.replace(/[^\d+]/g, '');
      
      // Ensure it starts with +
      if (!digitsOnly.startsWith('+')) {
        digitsOnly = `+${digitsOnly}`;
      }
      
      // Different formatting based on country code
      if (digitsOnly.startsWith('+225')) { // Côte d'Ivoire
        // Format: +225 XX XX XX XX
        if (digitsOnly.length >= 5) {
          return `+225 ${digitsOnly.substring(4).replace(/(\d{2})(?=\d)/g, '$1 ').trim()}`;
        }
      } else if (digitsOnly.startsWith('+33')) { // France
        // Format: +33 X XX XX XX XX
        if (digitsOnly.length >= 4) {
          return `+33 ${digitsOnly.substring(3).replace(/(\d)(\d{2})(\d{2})(\d{2})(\d{2})/, '$1 $2 $3 $4 $5')}`;
        }
      } else if (digitsOnly.startsWith('+1')) { // USA/Canada
        // Format: +1 XXX XXX XXXX
        if (digitsOnly.length >= 3) {
          return `+1 ${digitsOnly.substring(2).replace(/(\d{3})(\d{3})(\d{4})/, '$1 $2 $3')}`;
        }
      }
      
      // Generic formatting: add a space after the country code
      const countryCodeEnd = digitsOnly.indexOf(' ', 1);
      if (countryCodeEnd === -1) {
        // No space found, try to insert one after the country code (typically 1-3 digits)
        const match = digitsOnly.match(/^\+(\d{1,3})/);
        if (match) {
          const codeLength = match[1].length + 1; // +1 for the plus sign
          return `${digitsOnly.substring(0, codeLength)} ${digitsOnly.substring(codeLength).replace(/(\d{2})(?=\d)/g, '$1 ').trim()}`;
        }
      }
      
      return digitsOnly;
    };

    // Handle value update
    const updateValue = (val) => {
      emit('update:model-value', val);
    };

    // Handle blur event - format phone number
    const onBlur = (e) => {
      emit('blur', e);
      
      if (displayValue.value) {
        displayValue.value = formatPhoneNumber(displayValue.value);
        emit('update:model-value', displayValue.value);
      }
    };

    // Handle adding default country code if empty
    const addDefaultCountryCode = () => {
      if (!displayValue.value && props.defaultCountryCode) {
        displayValue.value = `${props.defaultCountryCode} `;
        emit('update:model-value', displayValue.value);
      }
    };

    // Watch for external changes to modelValue
    watch(() => props.modelValue, (newVal) => {
      displayValue.value = newVal;
    });

    return {
      displayValue,
      compoundRules,
      updateValue,
      onBlur,
      addDefaultCountryCode
    };
  }
});
</script>

<style scoped>
.whatsapp-template-variable-phone-input {
  width: 100%;
}
</style>