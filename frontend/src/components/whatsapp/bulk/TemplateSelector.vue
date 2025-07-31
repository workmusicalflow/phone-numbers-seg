<template>
  <div class="input-group">
    <label class="input-label">
      <q-icon name="mdi-message-text" class="q-mr-xs" />
      Template WhatsApp
    </label>
    <q-select
      v-model="selectedTemplate"
      :options="availableTemplates"
      option-label="name"
      option-value="name"
      placeholder="Sélectionner un template..."
      outlined
      emit-value
      map-options
      :loading="loadingTemplates"
      class="modern-input"
    >
      <template v-slot:option="scope">
        <q-item v-bind="scope.itemProps">
          <q-item-section>
            <q-item-label>{{ scope.opt.name }}</q-item-label>
            <q-item-label caption>
              {{ scope.opt.language }} - {{ scope.opt.status }}
            </q-item-label>
          </q-item-section>
        </q-item>
      </template>
    </q-select>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useWhatsAppTemplateStore } from '@/stores/whatsappTemplateStore'
import type { WhatsAppTemplate } from '@/types/whatsapp-templates'

interface Props {
  modelValue: string
  loadingTemplates?: boolean
}

interface Emits {
  (e: 'update:modelValue', value: string): void
  (e: 'load-templates'): void
}

const props = withDefaults(defineProps<Props>(), {
  loadingTemplates: false
})

const emit = defineEmits<Emits>()

const templateStore = useWhatsAppTemplateStore()

// Computed
const selectedTemplate = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const availableTemplates = computed(() => templateStore.templates || [])

// Charger les templates au montage
onMounted(async () => {
  console.log('[TemplateSelector] Montage du composant - chargement des templates')
  emit('load-templates')
  
  // Si le store n'a pas de templates, les charger directement
  if (templateStore.templates.length === 0) {
    console.log('[TemplateSelector] Aucun template dans le store, chargement...')
    await templateStore.fetchTemplates()
  }
})
</script>

<style lang="scss" scoped>
.input-group {
  margin-bottom: 24px;

  .input-label {
    display: flex;
    align-items: center;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
    font-size: 0.95rem;
  }
}

.modern-input {
  :deep(.q-field__control) {
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    border: 1px solid #e5e7eb;
    transition: all 0.2s ease;

    &:hover {
      border-color: #d1d5db;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    &:focus-within {
      border-color: #25d366;
      box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.1);
    }
  }

  :deep(.q-field__native) {
    padding: 12px 16px;
  }
}
</style>