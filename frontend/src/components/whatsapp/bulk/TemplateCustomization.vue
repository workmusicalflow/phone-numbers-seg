<template>
  <div class="input-group">
    <q-expansion-item
      icon="mdi-variable"
      :label="hasTemplateVariables ? 'Personnalisation du template' : 'Aperçu du template'"
      default-opened
      class="template-customization"
    >
      <q-card class="customization-card">
        <q-card-section>
          <!-- Informations sur le template -->
          <div v-if="template" class="template-info q-mb-md">
            <div class="info-row">
              <span class="info-label">Nom du template:</span>
              <span class="info-value">{{ template.name }}</span>
            </div>
            <div v-if="template.language" class="info-row">
              <span class="info-label">Langue:</span>
              <span class="info-value">{{ template.language }}</span>
            </div>
            <div v-if="template.status" class="info-row">
              <span class="info-label">Statut:</span>
              <q-chip 
                :color="template.status === 'APPROVED' ? 'positive' : 'warning'" 
                text-color="white" 
                size="sm"
                dense
              >
                {{ template.status }}
              </q-chip>
            </div>
          </div>

          <!-- Message si pas de variables -->
          <div v-if="!hasTemplateVariables && !hasHeaderMedia" class="no-variables-info">
            <q-banner class="bg-info text-white">
              <template v-slot:avatar>
                <q-icon name="info" />
              </template>
              Ce template ne contient pas de variables personnalisables.
              Il sera envoyé tel quel à tous les destinataires.
            </q-banner>
          </div>

          <!-- Variables du corps -->
          <div v-if="bodyVariables.length > 0" class="variable-section">
            <div class="variable-section-title">
              <q-icon name="mdi-text-box" class="q-mr-sm" />
              Variables du corps du message
            </div>
            <div class="input-group" v-for="(_, index) in bodyVariables" :key="`body-${index}`">
              <label class="input-label">
                <q-icon name="mdi-variable" class="q-mr-xs" />
                Variable {{index + 1}}
              </label>
              <q-input
                v-model="customization.bodyVariables[index]"
                :placeholder="`Valeur pour {{${index + 1}}}`"
                outlined
                class="modern-input"
                hint="Cette valeur sera utilisée pour tous les destinataires"
              />
            </div>
          </div>
        
          <!-- Média d'en-tête -->
          <div v-if="hasHeaderMedia" class="variable-section">
            <div class="variable-section-title">
              <q-icon name="mdi-image" class="q-mr-sm" />
              Média d'en-tête
            </div>
            <div class="input-group">
              <label class="input-label">
                <q-icon name="mdi-link" class="q-mr-xs" />
                URL du média
              </label>
              <q-input
                v-model="customization.headerMediaUrl"
                placeholder="https://exemple.com/image.jpg"
                outlined
                class="modern-input"
                hint="URL publique de l'image/vidéo/document"
              />
            </div>
          </div>
        
          <!-- Variables d'en-tête -->
          <div v-if="headerVariables.length > 0" class="variable-section">
            <div class="variable-section-title">
              <q-icon name="mdi-format-header-1" class="q-mr-sm" />
              Variables d'en-tête
            </div>
            <div class="input-group" v-for="(_, index) in headerVariables" :key="`header-${index}`">
              <label class="input-label">
                <q-icon name="mdi-variable" class="q-mr-xs" />
                En-tête {{index + 1}}
              </label>
              <q-input
                v-model="customization.headerVariables[index]"
                :placeholder="`Valeur pour l'en-tête ${index + 1}`"
                outlined
                class="modern-input"
              />
            </div>
          </div>

          <!-- Prévisualisation -->
          <div class="preview-section q-mt-lg">
            <div class="variable-section-title">
              <q-icon name="mdi-eye" class="q-mr-sm" />
              Aperçu du message
            </div>
            <WhatsAppPreview
              :template-header="templateHeader"
              :template-body="previewMessage || templateBody"
              :template-footer="templateFooter"
              :template-buttons="templateButtons"
              :header-media-url="customization.headerMediaUrl || templateHeaderMediaUrl"
              :business-name="'Votre Entreprise'"
            />
          </div>
        </q-card-section>
      </q-card>
    </q-expansion-item>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import WhatsAppPreview from './WhatsAppPreview.vue'
import type { TemplateCustomization } from '@/composables/useTemplateCustomization'

interface Props {
  template?: any // Template object from the store
  customization: TemplateCustomization
  bodyVariables: string[]
  headerVariables: string[]
  hasHeaderMedia: boolean
  hasTemplateVariables: boolean
  previewMessage: string
}

interface Emits {
  (e: 'update:customization', value: TemplateCustomization): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Computed pour la réactivité bidirectionnelle
const customization = computed({
  get: () => props.customization,
  set: (value) => emit('update:customization', value)
})

// Extraire les composants du template
const templateComponents = computed(() => {
  if (!props.template || !props.template.componentsJson) return []
  try {
    return JSON.parse(props.template.componentsJson)
  } catch (e) {
    console.error('Erreur parsing componentsJson:', e)
    return []
  }
})

const templateHeader = computed(() => {
  const header = templateComponents.value.find((c: any) => c.type === 'HEADER')
  if (!header) return null
  
  // Extraire l'URL de l'exemple si disponible
  if (header.format === 'IMAGE' && header.example?.header_handle?.[0]) {
    return {
      type: 'IMAGE',
      exampleUrl: header.example.header_handle[0]
    }
  }
  
  return {
    type: header.format || 'TEXT',
    text: header.text
  }
})

const templateHeaderMediaUrl = computed(() => {
  const header = templateHeader.value
  if (header?.type === 'IMAGE' && header.exampleUrl) {
    return header.exampleUrl
  }
  return ''
})

const templateBody = computed(() => {
  const body = templateComponents.value.find((c: any) => c.type === 'BODY')
  return body?.text || ''
})

const templateFooter = computed(() => {
  const footer = templateComponents.value.find((c: any) => c.type === 'FOOTER')
  return footer?.text || ''
})

const templateButtons = computed(() => {
  const buttonsComponent = templateComponents.value.find((c: any) => c.type === 'BUTTONS')
  return buttonsComponent?.buttons || []
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

.variable-section {
  margin-bottom: 24px;

  .variable-section-title {
    display: flex;
    align-items: center;
    font-weight: 600;
    color: #374151;
    margin-bottom: 16px;
    font-size: 1rem;
    padding-bottom: 8px;
    border-bottom: 1px solid #f3f4f6;
  }
}

.template-customization {
  background: #f8fafc;
  border-radius: 12px;
  border: 1px solid #e5e7eb;

  :deep(.q-expansion-item__header) {
    padding: 16px 20px;
    font-weight: 600;
    color: #374151;
  }

  .customization-card {
    background: white;
    border-radius: 8px;
    margin: 0;
    box-shadow: none;
    border: none;
  }
}

.template-info {
  background: #f9fafb;
  padding: 16px;
  border-radius: 8px;
  border: 1px solid #e5e7eb;

  .info-row {
    display: flex;
    align-items: center;
    margin-bottom: 8px;

    &:last-child {
      margin-bottom: 0;
    }
  }

  .info-label {
    font-weight: 600;
    color: #6b7280;
    margin-right: 8px;
    min-width: 140px;
  }

  .info-value {
    color: #374151;
    font-weight: 500;
  }
}

.no-variables-info {
  margin-top: 16px;
  
  .q-banner {
    border-radius: 8px;
  }
}

</style>