<template>
  <div class="whatsapp-template-variable-time-input">
    <q-input
      v-model="displayValue"
      :label="label"
      :hint="hint"
      :placeholder="placeholder || 'HH:MM'"
      :rules="compoundRules"
      :outlined="outlined"
      :dense="dense"
      mask="##:##"
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
        <q-icon name="schedule" class="cursor-pointer">
          <q-popup-proxy cover transition-show="scale" transition-hide="scale">
            <q-time
              v-model="timeObject"
              format24h
              @update:model-value="onTimeSelect"
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
  name: 'WhatsAppTemplateVariableTimeInput',
  props: {
    modelValue: {
      type: String,
      default: ''
    },
    label: {
      type: String,
      default: 'Heure'
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
      default: 5
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
    use24Hours: {
      type: Boolean,
      default: true
    }
  },
  emits: ['update:model-value', 'focus', 'blur'],
  setup(props, { emit }) {
    const displayValue = ref(props.modelValue || '');
    const timeObject = ref(null);

    // Time validation rule
    const timeRule = (val) => {
      if (!val) return true;
      
      const parts = val.split(':');
      if (parts.length !== 2) return 'Format d\'heure invalide (HH:MM)';
      
      const hours = parseInt(parts[0], 10);
      const minutes = parseInt(parts[1], 10);
      
      if (isNaN(hours) || isNaN(minutes)) return 'Heure invalide';
      if (hours < 0 || hours > 23) return 'Heure invalide (00-23)';
      if (minutes < 0 || minutes > 59) return 'Minutes invalides (00-59)';
      
      return true;
    };

    // Combine custom validation rules with time rule
    const compoundRules = computed(() => {
      return [...props.rules, timeRule];
    });

    // Format time display (add suffix if needed)
    const formatTimeDisplay = (val) => {
      if (!val) return '';
      
      // By default, just return the HH:MM format
      return val;
    };

    // Update time object from display value
    const updateTimeObject = (val) => {
      if (!val) {
        timeObject.value = null;
        return;
      }
      
      const parts = val.split(':');
      if (parts.length !== 2) return;
      
      const hours = parts[0].padStart(2, '0');
      const minutes = parts[1].padStart(2, '0');
      
      timeObject.value = `${hours}:${minutes}`;
    };

    // Handle time selection from picker
    const onTimeSelect = () => {
      if (!timeObject.value) return;
      
      displayValue.value = timeObject.value;
      emit('update:model-value', formatTimeDisplay(displayValue.value));
    };

    // Handle manual input update
    const updateValue = (val) => {
      emit('update:model-value', formatTimeDisplay(val));
    };

    // Handle blur event
    const onBlur = (e) => {
      emit('blur', e);
      
      // Format time on blur if needed
      if (displayValue.value) {
        const parts = displayValue.value.split(':');
        if (parts.length === 2) {
          const hours = parts[0].padStart(2, '0');
          const minutes = parts[1].padStart(2, '0');
          
          displayValue.value = `${hours}:${minutes}`;
          emit('update:model-value', formatTimeDisplay(displayValue.value));
        }
      }
    };

    // Watch for external changes to modelValue
    watch(() => props.modelValue, (newVal) => {
      displayValue.value = newVal;
      updateTimeObject(newVal);
    });

    return {
      displayValue,
      timeObject,
      compoundRules,
      onTimeSelect,
      updateValue,
      onBlur
    };
  }
});
</script>

<style scoped>
.whatsapp-template-variable-time-input {
  width: 100%;
}
</style>