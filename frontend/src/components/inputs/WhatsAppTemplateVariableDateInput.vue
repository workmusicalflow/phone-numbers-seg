<template>
  <div class="whatsapp-template-variable-date-input">
    <q-input
      v-model="displayValue"
      :label="label"
      :hint="hint"
      :placeholder="placeholder || 'JJ/MM/AAAA'"
      :rules="compoundRules"
      :outlined="outlined"
      :dense="dense"
      mask="##/##/####"
      counter
      :maxlength="maxlength"
      :readonly="readonly"
      :disable="disable"
      :clearable="clearable"
      @focus="$emit('focus', $event)"
      @blur="onBlur"
      @update:model-value="updateValue"
    >
      <template v-slot:prepend>
        <q-icon name="event" class="cursor-pointer">
          <q-popup-proxy cover transition-show="scale" transition-hide="scale">
            <q-date
              v-model="dateObject"
              :mask="dateMask"
              @update:model-value="onDateSelect"
              :options="dateOptions"
              today-btn
            />
          </q-popup-proxy>
        </q-icon>
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
  name: 'WhatsAppTemplateVariableDateInput',
  props: {
    modelValue: {
      type: String,
      default: ''
    },
    label: {
      type: String,
      default: 'Date'
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
    format: {
      type: String,
      default: 'DD/MM/YYYY'
    },
    minDate: {
      type: String,
      default: ''
    },
    maxDate: {
      type: String,
      default: ''
    }
  },
  emits: ['update:model-value', 'focus', 'blur'],
  setup(props, { emit }) {
    const displayValue = ref(props.modelValue || '');
    const dateObject = ref(null);
    const dateMask = computed(() => 'YYYY/MM/DD');

    // Date validation rule
    const dateRule = (val) => {
      if (!val) return true;
      
      const parts = val.split('/');
      if (parts.length !== 3) return 'Format de date invalide (JJ/MM/AAAA)';
      
      const day = parseInt(parts[0], 10);
      const month = parseInt(parts[1], 10);
      const year = parseInt(parts[2], 10);
      
      if (isNaN(day) || isNaN(month) || isNaN(year)) return 'Date invalide';
      if (day < 1 || day > 31) return 'Jour invalide';
      if (month < 1 || month > 12) return 'Mois invalide';
      if (year < 1900 || year > 2100) return 'Année invalide';
      
      // Vérifier les mois avec moins de 31 jours
      if (month === 2) {
        // Vérification des années bissextiles
        const isLeapYear = (year % 4 === 0 && year % 100 !== 0) || (year % 400 === 0);
        if (day > (isLeapYear ? 29 : 28)) return 'Jour invalide pour février';
      } else if ([4, 6, 9, 11].includes(month) && day > 30) {
        return 'Ce mois ne contient que 30 jours';
      }
      
      return true;
    };

    // Combine custom validation rules with date rule
    const compoundRules = computed(() => {
      return [...props.rules, dateRule];
    });

    // Date options for q-date
    const dateOptions = computed(() => {
      return (date) => {
        if (props.minDate && date < props.minDate) return false;
        if (props.maxDate && date > props.maxDate) return false;
        return true;
      };
    });

    // Convert date from DD/MM/YYYY to date object
    const updateDateObject = (val) => {
      if (!val) {
        dateObject.value = null;
        return;
      }
      
      const parts = val.split('/');
      if (parts.length !== 3) return;
      
      const day = parts[0].padStart(2, '0');
      const month = parts[1].padStart(2, '0');
      const year = parts[2];
      
      if (year.length !== 4) return;
      
      dateObject.value = `${year}/${month}/${day}`;
    };

    // Handle date selection from picker
    const onDateSelect = () => {
      if (!dateObject.value) return;
      
      const parts = dateObject.value.split('/');
      if (parts.length !== 3) return;
      
      const year = parts[0];
      const month = parts[1];
      const day = parts[2];
      
      displayValue.value = `${day}/${month}/${year}`;
      emit('update:model-value', displayValue.value);
    };

    // Handle manual input update
    const updateValue = (val) => {
      emit('update:model-value', val);
      updateDateObject(val);
    };

    // Handle blur event
    const onBlur = (e) => {
      emit('blur', e);
      
      // Format date on blur if needed
      if (displayValue.value) {
        const parts = displayValue.value.split('/');
        if (parts.length === 3) {
          const day = parts[0].padStart(2, '0');
          const month = parts[1].padStart(2, '0');
          const year = parts[2].padStart(4, '0');
          
          displayValue.value = `${day}/${month}/${year}`;
          emit('update:model-value', displayValue.value);
        }
      }
    };

    // Watch for external changes to modelValue
    watch(() => props.modelValue, (newVal) => {
      displayValue.value = newVal;
      updateDateObject(newVal);
    });

    return {
      displayValue,
      dateObject,
      dateMask,
      compoundRules,
      dateOptions,
      onDateSelect,
      updateValue,
      onBlur
    };
  }
});
</script>

<style scoped>
.whatsapp-template-variable-date-input {
  width: 100%;
}
</style>