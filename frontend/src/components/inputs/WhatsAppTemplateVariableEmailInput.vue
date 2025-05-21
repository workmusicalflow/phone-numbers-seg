<template>
  <div class="whatsapp-template-variable-email-input">
    <q-input
      v-model="displayValue"
      :label="label"
      :hint="hint"
      :placeholder="placeholder || 'exemple@domaine.com'"
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
      autocomplete="email"
      type="email"
    >
      <template v-slot:prepend>
        <q-icon name="email" />
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
  name: 'WhatsAppTemplateVariableEmailInput',
  props: {
    modelValue: {
      type: String,
      default: ''
    },
    label: {
      type: String,
      default: 'Email'
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
      default: 100
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
    }
  },
  emits: ['update:model-value', 'focus', 'blur'],
  setup(props, { emit }) {
    const displayValue = ref(props.modelValue || '');

    // Email validation regex
    const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

    // Email validation rule
    const emailRule = (val) => {
      if (!val) return true;
      
      if (!emailRegex.test(val)) {
        return 'Format d\'email invalide';
      }
      
      return true;
    };

    // Combine custom validation rules with email rule
    const compoundRules = computed(() => {
      return [...props.rules, emailRule];
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
.whatsapp-template-variable-email-input {
  width: 100%;
}
</style>