<template>
  <div class="whatsapp-template-variable-currency-input">
    <q-input
      ref="inputRef"
      v-model="displayValue"
      :label="label"
      :hint="hint"
      :placeholder="placeholder || '0.00'"
      :rules="compoundRules"
      :outlined="outlined"
      :dense="dense"
      counter
      :maxlength="maxlength"
      :readonly="readonly"
      :disable="disable"
      :clearable="clearable"
      @focus="onFocus"
      @blur="onBlur"
      @update:model-value="updateValue"
    >
      <template v-slot:prepend>
        <q-icon name="paid" />
      </template>
      <template v-slot:append>
        <q-select
          v-model="selectedCurrency"
          :options="currencyOptions"
          dense
          options-dense
          borderless
          emit-value
          map-options
          style="min-width: 70px"
        />
      </template>
    </q-input>
  </div>
</template>

<script>
import { defineComponent, computed, ref, watch, nextTick, onMounted } from 'vue';

export default defineComponent({
  name: 'WhatsAppTemplateVariableCurrencyInput',
  props: {
    modelValue: {
      type: String,
      default: ''
    },
    label: {
      type: String,
      default: 'Montant'
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
      default: 15
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
    defaultCurrency: {
      type: String,
      default: 'EUR'
    }
  },
  emits: ['update:model-value', 'focus', 'blur'],
  setup(props, { emit }) {
    const inputRef = ref(null);
    const rawValue = ref('');
    const displayValue = ref('');
    const selectedCurrency = ref(props.defaultCurrency);
    const focused = ref(false);

    // Available currency options
    const currencyOptions = [
      { label: 'EUR (€)', value: 'EUR' },
      { label: 'USD ($)', value: 'USD' },
      { label: 'XOF (FCFA)', value: 'XOF' }
    ];

    // Currency validation rule
    const currencyRule = (val) => {
      if (!val) return true;
      
      // Remove currency symbol and spaces
      const cleanVal = val.replace(/[^\d.,]/g, '');
      
      // Check for valid number format
      if (!/^(\d+)([.,]\d{1,2})?$/.test(cleanVal)) {
        return 'Format de montant invalide';
      }
      
      return true;
    };

    // Combine custom validation rules with currency rule
    const compoundRules = computed(() => {
      return [...props.rules, currencyRule];
    });

    // Get currency symbol
    const getCurrencySymbol = (currencyCode) => {
      switch (currencyCode) {
        case 'EUR': return '€';
        case 'USD': return '$';
        case 'XOF': return 'FCFA';
        default: return '';
      }
    };

    // Format displayed value
    const formatDisplay = (value, withSymbol = true) => {
      if (!value) return '';
      
      // Clean the input value
      let cleaned = value.toString().replace(/[^\d.,]/g, '');
      
      // Replace comma with dot for calculations
      cleaned = cleaned.replace(',', '.');
      
      // Format with 2 decimal places
      let formatted;
      try {
        const num = parseFloat(cleaned);
        if (isNaN(num)) return '';
        
        formatted = num.toFixed(2).replace('.', ',');
      } catch (e) {
        return '';
      }
      
      // Add thousands separators
      formatted = formatted.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
      
      // Add currency symbol if needed
      if (withSymbol) {
        const symbol = getCurrencySymbol(selectedCurrency.value);
        if (['EUR', 'XOF'].includes(selectedCurrency.value)) {
          return `${formatted} ${symbol}`;
        } else {
          return `${symbol}${formatted}`;
        }
      }
      
      return formatted;
    };

    // Parse initial value
    const parseInitialValue = (value) => {
      if (!value) return { amount: '', currency: props.defaultCurrency };
      
      // Try to extract amount and currency
      let amount = value;
      let currency = props.defaultCurrency;
      
      // Check for currency symbols
      if (value.includes('€')) {
        currency = 'EUR';
        amount = value.replace('€', '').trim();
      } else if (value.includes('$')) {
        currency = 'USD';
        amount = value.replace('$', '').trim();
      } else if (value.includes('FCFA')) {
        currency = 'XOF';
        amount = value.replace('FCFA', '').trim();
      }
      
      return { amount, currency };
    };

    // Handle focus event
    const onFocus = (e) => {
      focused.value = true;
      emit('focus', e);
      
      // Show raw value without formatting when focused
      nextTick(() => {
        displayValue.value = rawValue.value;
        
        // Position cursor at the end
        if (inputRef.value && inputRef.value.$refs.input) {
          const input = inputRef.value.$refs.input;
          input.selectionStart = input.selectionEnd = input.value.length;
        }
      });
    };

    // Handle blur event
    const onBlur = (e) => {
      focused.value = false;
      emit('blur', e);
      
      // Format display value
      nextTick(() => {
        if (rawValue.value) {
          displayValue.value = formatDisplay(rawValue.value);
        }
      });
    };

    // Handle value update
    const updateValue = (val) => {
      rawValue.value = val.replace(/[^\d.,]/g, '');
      
      // Emit combined value
      const formattedValue = formatDisplay(rawValue.value);
      emit('update:model-value', formattedValue);
    };

    // Handle currency change
    watch(() => selectedCurrency.value, () => {
      // Update displayed value with new currency
      if (rawValue.value) {
        displayValue.value = focused.value ? rawValue.value : formatDisplay(rawValue.value);
        emit('update:model-value', formatDisplay(rawValue.value));
      }
    });

    // Watch for external changes to modelValue
    watch(() => props.modelValue, (newVal) => {
      const { amount, currency } = parseInitialValue(newVal);
      rawValue.value = amount.replace(/[^\d.,]/g, '');
      selectedCurrency.value = currency;
      
      displayValue.value = focused.value ? rawValue.value : formatDisplay(rawValue.value);
    });

    // Initialize component
    onMounted(() => {
      if (props.modelValue) {
        const { amount, currency } = parseInitialValue(props.modelValue);
        rawValue.value = amount.replace(/[^\d.,]/g, '');
        selectedCurrency.value = currency;
        
        displayValue.value = formatDisplay(rawValue.value);
      }
    });

    return {
      inputRef,
      displayValue,
      selectedCurrency,
      currencyOptions,
      compoundRules,
      onFocus,
      onBlur,
      updateValue
    };
  }
});
</script>

<style scoped>
.whatsapp-template-variable-currency-input {
  width: 100%;
}

/* Remove the bottom border of the currency select */
.whatsapp-template-variable-currency-input :deep(.q-field__append .q-field__marginal) {
  border-bottom: none;
}
</style>