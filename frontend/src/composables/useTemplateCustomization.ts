import { ref, computed, watch } from 'vue'
import type { WhatsAppTemplate } from '@/types/whatsapp-templates'
import { useWhatsAppTemplateStore } from '@/stores/whatsappTemplateStore'

export interface TemplateCustomization {
  bodyVariables: string[]
  headerVariables: string[]
  headerMediaUrl: string
  headerMediaId: string
}

export function useTemplateCustomization(templates: any) {
  // État
  const selectedTemplate = ref<string>('')
  const templateCustomization = ref<TemplateCustomization>({
    bodyVariables: [],
    headerVariables: [],
    headerMediaUrl: '',
    headerMediaId: ''
  })
  const mediaError = ref(false)
  const loadingTemplates = ref(false)
  
  // Store
  const templateStore = useWhatsAppTemplateStore()

  // Computed
  const selectedTemplateData = computed(() => {
    // D'abord chercher dans le store
    const storeTemplate = templateStore.templates.find((t: any) => t.name === selectedTemplate.value)
    if (storeTemplate) {
      return storeTemplate
    }
    
    // Sinon chercher dans les templates passés en paramètre
    if (templates && templates.value) {
      return templates.value.find((t: WhatsAppTemplate) => t.name === selectedTemplate.value)
    }
    
    return null
  })

  const bodyVariables = computed(() => {
    if (!selectedTemplateData.value?.componentsJson) return []
    
    try {
      const components = JSON.parse(selectedTemplateData.value.componentsJson)
      const bodyComponent = components.find((c: any) => c.type === 'BODY')
      if (!bodyComponent?.text) return []
      
      const matches = bodyComponent.text.match(/{{\d+}}/g) || []
      return Array.from({ length: matches.length }, (_, i) => `{{${i + 1}}}`)
    } catch {
      return []
    }
  })

  const headerVariables = computed(() => {
    if (!selectedTemplateData.value?.componentsJson) return []
    
    try {
      const components = JSON.parse(selectedTemplateData.value.componentsJson)
      const headerComponent = components.find((c: any) => c.type === 'HEADER')
      if (!headerComponent?.text) return []
      
      const matches = headerComponent.text.match(/{{\d+}}/g) || []
      return Array.from({ length: matches.length }, (_, i) => `{{${i + 1}}}`)
    } catch {
      return []
    }
  })

  const hasHeaderMedia = computed(() => {
    if (!selectedTemplateData.value?.componentsJson) return false
    
    try {
      const components = JSON.parse(selectedTemplateData.value.componentsJson)
      const headerComponent = components.find((c: any) => c.type === 'HEADER')
      return headerComponent?.format && ['IMAGE', 'VIDEO', 'DOCUMENT'].includes(headerComponent.format)
    } catch {
      return false
    }
  })

  const hasTemplateVariables = computed(() => {
    return selectedTemplateData.value && (
      bodyVariables.value.length > 0 || 
      headerVariables.value.length > 0 || 
      hasHeaderMedia.value
    )
  })

  const previewMessage = computed(() => {
    if (!selectedTemplateData.value?.componentsJson) return ''
    
    try {
      const components = JSON.parse(selectedTemplateData.value.componentsJson)
      const bodyComponent = components.find((c: any) => c.type === 'BODY')
      if (!bodyComponent?.text) return ''
      
      let text = bodyComponent.text
      
      templateCustomization.value.bodyVariables.forEach((value, index) => {
        if (value) {
          text = text.replace(`{{${index + 1}}}`, value)
        }
      })
      
      return text
    } catch {
      return ''
    }
  })

  // Watchers
  watch(selectedTemplate, (templateName) => {
    console.log('[useTemplateCustomization] Template sélectionné:', templateName)
    console.log('[useTemplateCustomization] Template data:', selectedTemplateData.value)
    
    if (templateName && selectedTemplateData.value) {
      const bodyVariablesCount = bodyVariables.value.length
      const headerVariablesCount = headerVariables.value.length
      
      console.log('[useTemplateCustomization] Variables détectées - Body:', bodyVariablesCount, 'Header:', headerVariablesCount)
      
      templateCustomization.value.bodyVariables = Array(bodyVariablesCount).fill('')
      templateCustomization.value.headerVariables = Array(headerVariablesCount).fill('')
      templateCustomization.value.headerMediaUrl = ''
      templateCustomization.value.headerMediaId = ''
    } else {
      templateCustomization.value = {
        bodyVariables: [],
        headerVariables: [],
        headerMediaUrl: '',
        headerMediaId: ''
      }
    }
  })

  // Méthodes
  const resetCustomization = () => {
    selectedTemplate.value = ''
    templateCustomization.value = {
      bodyVariables: [],
      headerVariables: [],
      headerMediaUrl: '',
      headerMediaId: ''
    }
    mediaError.value = false
  }

  // Méthodes d'update pour le component parent
  const updateSelectedTemplate = (templateName: string) => {
    selectedTemplate.value = templateName
  }

  const updateTemplateCustomization = (customization: TemplateCustomization) => {
    templateCustomization.value = customization
  }

  const loadTemplates = async () => {
    console.log('[useTemplateCustomization] Début du chargement des templates')
    const templateStore = useWhatsAppTemplateStore()
    
    loadingTemplates.value = true
    
    try {
      // Charger les templates depuis le store
      await templateStore.fetchTemplates()
      
      // Mettre à jour les templates dans la réf réactive
      if (templates && templates.value) {
        templates.value = templateStore.templates
      }
      
      console.log('[useTemplateCustomization] Templates chargés:', templateStore.templates.length)
    } catch (error) {
      console.error('[useTemplateCustomization] Erreur lors du chargement des templates:', error)
      throw error
    } finally {
      loadingTemplates.value = false
    }
  }

  return {
    // État
    selectedTemplate,
    templateCustomization,
    mediaError,
    loadingTemplates,
    
    // Computed
    currentTemplate: selectedTemplateData,
    bodyVariables,
    headerVariables,
    hasHeaderMedia,
    hasTemplateVariables,
    previewMessage,
    
    // Méthodes
    updateSelectedTemplate,
    updateTemplateCustomization,
    loadTemplates,
    resetCustomization
  }
}