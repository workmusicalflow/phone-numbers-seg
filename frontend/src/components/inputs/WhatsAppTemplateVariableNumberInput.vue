<template>
  <div class="whatsapp-template-variable-number-input">
    <q-input
      v-model="displayValue"
      :label="label"
      :hint="hint"
      :placeholder="placeholder || '0'"
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
      type="text"
      inputmode="numeric"
    >
      <template v-slot:prepend>
        <q-icon name="numbers" />
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
  name: 'WhatsAppTemplateVariableNumberInput',
  props: {
    modelValue: {
      type: String,
      default: ''
    },
    label: {
      type: String,
      default: 'Nombre'
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
      default: 10
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
    allowDecimals: {
      type: Boolean,
      default: false
    },
    minValue: {
      type: Number,
      default: null
    },
    maxValue: {
      type: Number,
      default: null
    },
    useThousandsSeparator: {
      type: Boolean,
      default: true
    }
  },
  emits: ['update:model-value', 'focus', 'blur'],
  setup(props, { emit }) {
    const displayValue = ref(props.modelValue || '');
    const rawValue = ref('');

    // Number validation rule
    const numberRule = (val) => {
      if (!val) return true;
      
      // Check for valid number format
      const regex = props.allowDecimals ? /^-?\d+([.,]\d+)?$/ : /^-?\d+$/;
      const cleanVal = val.replace(/\s/g, '').replace(',', '.');
      
      if (!regex.test(cleanVal)) {
        return props.allowDecimals 
          ? 'Format de nombre invalide. Utilisez des chiffres, un point ou une virgule.' 
          : 'Format de nombre invalide. Utilisez uniquement des chiffres.';
      }
      
      // Check min value if defined
      if (props.minValue !== null) {
        const numVal = parseFloat(cleanVal);
        if (numVal < props.minValue) {
          return `La valeur minimale est ${props.minValue}`;
        }
      }
      
      // Check max value if defined
      if (props.maxValue !== null) {
        const numVal = parseFloat(cleanVal);
        if (numVal > props.maxValue) {
          return `La valeur maximale est ${props.maxValue}`;
        }
      }
      
      return true;
    };

    // Combine custom validation rules with number rule
    const compoundRules = computed(() => {
      return [...props.rules, numberRule];
    });

    // Format number display
    const formatNumber = (value) => {
      if (!value) return '';
      
      // Remove all non-numeric characters except decimal separator and minus sign
      const cleanVal = value.replace(/[^\d.,-]/g, '').replace(',', '.');
      
      if (cleanVal === '' || cleanVal === '-') return cleanVal;
      
      let parsedValue;
      try {
        parsedValue = parseFloat(cleanVal);
        if (isNaN(parsedValue)) return '';
      } catch (e) {
        return '';
      }
      
      // Format with thousands separators
      let formattedValue;
      if (props.allowDecimals) {
        // Determine decimal places (preserve as in input)
        const decimalPlaces = cleanVal.includes('.') 
          ? cleanVal.split('.')[1].length 
          : 0;
        
        formattedValue = parsedValue.toFixed(decimalPlaces).replace('.', ',');
      } else {
        formattedValue = Math.round(parsedValue).toString();
      }
      
      // Add thousands separators if enabled
      if (props.useThousandsSeparator) {
        const parts = formattedValue.split(',');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
        formattedValue = parts.join(',');
      }
      
      return formattedValue;
    };

    // Handle value update
    const updateValue = (val) => {
      // Save raw value (without formatting)
      rawValue.value = val.replace(/\s/g, '');
      
      // Emit the possibly formatted value
      emit('update:model-value', val);
    };

    // Handle blur event - format number
    const onBlur = (e) => {
      emit('blur', e);
      
      if (displayValue.value) {
        const formatted = formatNumber(displayValue.value);
        displayValue.value = formatted;
        emit('update:model-value', formatted);
      }
    };

    // Watch for external changes to modelValue
    watch(() => props.modelValue, (newVal) => {
      displayValue.value = newVal;
      rawValue.value = newVal ? newVal.replace(/\s/g, '') : '';
    });

    return {
      displayValue,
      compoundRules,
      updateValue,
      onBlur
    };
  }
});
</script>

<style scoped>
.whatsapp-template-variable-number-input {
  width: 100%;
}
</style>