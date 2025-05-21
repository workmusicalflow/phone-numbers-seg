<template>
  <div class="whatsapp-template-variable-text-input">
    <q-input
      v-model="displayValue"
      :label="label"
      :hint="hint"
      :placeholder="placeholder"
      :rules="compoundRules"
      :outlined="outlined"
      :dense="dense"
      counter
      :maxlength="maxlength"
      :readonly="readonly"
      :disable="disable"
      :clearable="clearable"
      @focus="$emit('focus', $event)"
      @blur="$emit('blur', $event)"
      @update:model-value="updateValue"
      :autogrow="autogrow"
      :type="multiline ? 'textarea' : 'text'"
    >
      <template v-slot:prepend>
        <q-icon name="subject" />
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
  name: 'WhatsAppTemplateVariableTextInput',
  props: {
    modelValue: {
      type: String,
      default: ''
    },
    label: {
      type: String,
      default: 'Texte'
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
      default: 60
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
    multiline: {
      type: Boolean,
      default: false
    },
    autogrow: {
      type: Boolean,
      default: true
    },
    minLength: {
      type: Number,
      default: 0
    },
    disallowedChars: {
      type: Array,
      default: () => []
    }
  },
  emits: ['update:model-value', 'focus', 'blur'],
  setup(props, { emit }) {
    const displayValue = ref(props.modelValue || '');

    // Text validation rule
    const textRule = (val) => {
      if (!val) {
        if (props.minLength > 0) {
          return `Ce champ doit contenir au moins ${props.minLength} caractères`;
        }
        return true;
      }
      
      if (props.minLength > 0 && val.length < props.minLength) {
        return `Ce champ doit contenir au moins ${props.minLength} caractères`;
      }
      
      // Check for disallowed characters
      if (props.disallowedChars.length > 0) {
        for (const char of props.disallowedChars) {
          if (val.includes(char)) {
            return `Le caractère "${char}" n'est pas autorisé`;
          }
        }
      }
      
      return true;
    };

    // Combine custom validation rules with text rule
    const compoundRules = computed(() => {
      return [...props.rules, textRule];
    });

    // Handle value update
    const updateValue = (val) => {
      emit('update:model-value', val);
    };

    // Watch for external changes to modelValue
    watch(() => props.modelValue, (newVal) => {
      displayValue.value = newVal;
    });

    return {
      displayValue,
      compoundRules,
      updateValue
    };
  }
});
</script>

<style scoped>
.whatsapp-template-variable-text-input {
  width: 100%;
}
</style>