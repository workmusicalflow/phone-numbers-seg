<template>
  <div class="whatsapp-template-variable-reference-input">
    <q-input
      v-model="displayValue"
      :label="label"
      :hint="hint"
      :placeholder="placeholder || 'REF-12345'"
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
      :uppercase="uppercase"
    >
      <template v-slot:prepend>
        <q-icon name="tag" />
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
  name: 'WhatsAppTemplateVariableReferenceInput',
  props: {
    modelValue: {
      type: String,
      default: ''
    },
    label: {
      type: String,
      default: 'Référence'
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
      default: 30
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
    uppercase: {
      type: Boolean,
      default: true
    },
    prefix: {
      type: String,
      default: ''
    },
    allowedPattern: {
      type: RegExp,
      default: () => /^[A-Z0-9\-_]*$/i // Alphanumeric, hyphen, underscore
    }
  },
  emits: ['update:model-value', 'focus', 'blur'],
  setup(props, { emit }) {
    const displayValue = ref(props.modelValue || '');

    // Reference validation rule
    const referenceRule = (val) => {
      if (!val) return true;
      
      // Check for valid format (using the allowed pattern)
      if (!props.allowedPattern.test(val)) {
        return 'Format de référence invalide. Utilisez uniquement des lettres, chiffres, tirets et underscores.';
      }
      
      return true;
    };

    // Combine custom validation rules with reference rule
    const compoundRules = computed(() => {
      return [...props.rules, referenceRule];
    });

    // Format reference on blur
    const formatReference = (value) => {
      if (!value) return '';
      
      let formattedValue = value;
      
      // Apply prefix if configured and not already there
      if (props.prefix && !formattedValue.startsWith(props.prefix)) {
        formattedValue = `${props.prefix}${formattedValue}`;
      }
      
      // Convert to uppercase if configured
      if (props.uppercase) {
        formattedValue = formattedValue.toUpperCase();
      }
      
      return formattedValue;
    };

    // Handle value update
    const updateValue = (val) => {
      emit('update:model-value', val);
    };

    // Handle blur event - format reference
    const onBlur = (e) => {
      emit('blur', e);
      
      if (displayValue.value) {
        displayValue.value = formatReference(displayValue.value);
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
      onBlur
    };
  }
});
</script>

<style scoped>
.whatsapp-template-variable-reference-input {
  width: 100%;
}
</style>