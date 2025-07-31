import { ref, computed } from 'vue'
import { whatsappBulkService, type BulkSendRequest } from '../services/whatsappBulkService'
import { useQuasar } from 'quasar'
import { WhatsAppRestClient } from '../services/whatsappRestClient'

export interface BulkSendOptions {
  batchSize: number
  delayBetweenBatches: number
  stopOnError: boolean
}

export interface BatchProgress {
  status: 'pending' | 'sending' | 'completed' | 'failed'
  progress: number
  successful: number
  failed: number
  total: number
  duration?: number
}

export interface SendError {
  message: string
  phoneNumber?: string
  timestamp: Date
}

export interface BulkSendStats {
  successful: number
  failed: number
  total: number
}

export interface ErrorMessage {
  recipient: string
  message: string
}

export function useBulkSend() {
  const $q = useQuasar()
  const whatsAppRestClient = new WhatsAppRestClient()

  // État de l'envoi
  const sending = ref(false)
  const sendingComplete = ref(false)
  const paused = ref(false)
  const preparing = ref(false)
  const progress = ref(0)
  const stats = ref<BulkSendStats>({ successful: 0, failed: 0, total: 0 })
  const errorMessages = ref<ErrorMessage[]>([])
  const batchProgress = ref<BatchProgress[]>([])
  const errors = ref<SendError[]>([])
  const sendStartTime = ref<Date | null>(null)
  const currentRate = ref(0)

  // Options par défaut
  const batchSize = ref(20)
  const batchDelay = ref(1000)
  const continueOnError = ref(true)
  const showProgress = ref(true)
  const retryPolicy = ref('standard')

  // Computed
  const progressColor = computed(() => {
    if (stats.value.failed > 0) return 'warning'
    if (sendingComplete.value) return 'positive'
    return 'primary'
  })

  const canSend = computed(() => {
    return !sending.value
  })

  // Méthodes
  const startBulkSend = async (
    recipients: string[],
    templateName: string,
    templateLanguage: string,
    bodyVariables: string[],
    headerVariables: string[],
    headerMediaUrl?: string,
    headerMediaId?: string
  ) => {
    if (!templateName || recipients.length === 0) {
      throw new Error('Template et destinataires requis')
    }

    sending.value = true
    sendingComplete.value = false
    progress.value = 0
    stats.value = { successful: 0, failed: 0, total: recipients.length }
    errorMessages.value = []

    try {
      const response = await whatsAppRestClient.sendBulkTemplate({
        recipients,
        templateName,
        templateLanguage,
        bodyVariables: bodyVariables.filter(v => v.length > 0),
        headerVariables: headerVariables.filter(v => v.length > 0),
        headerMediaUrl: headerMediaUrl || undefined,
        headerMediaId: headerMediaId || undefined,
        options: {
          batchSize: batchSize.value,
          delayBetweenBatches: batchDelay.value,
          stopOnError: !continueOnError.value
        },
        includeDetails: true
      })

      if (response.success) {
        handleBulkSendComplete({
          successful: response.data.totalSent,
          failed: response.data.totalFailed,
          errors: response.data.failedRecipients ? 
            Object.entries(response.data.failedRecipients).map(([recipient, error]) => ({
              recipient,
              message: (error as any).error
            })) : []
        })
      } else {
        throw new Error(response.message)
      }

    } catch (error: any) {
      sending.value = false
      const message = error.response?.data?.error || error.message || 'Erreur lors de l\'envoi'
      
      $q.notify({
        type: 'negative',
        message: 'Erreur lors de l\'envoi en masse',
        caption: message
      })
      
      throw error
    }
  }

  const handleBulkSendComplete = (result: {
    successful: number
    failed: number
    errors: ErrorMessage[]
  }) => {
    stats.value.successful = result.successful
    stats.value.failed = result.failed
    errorMessages.value = result.errors
    progress.value = 1
    sending.value = false
    sendingComplete.value = true

    $q.notify({
      type: result.failed > 0 ? 'warning' : 'positive',
      message: `Envoi terminé: ${result.successful} succès, ${result.failed} échecs`,
      timeout: 5000
    })
  }

  const resetSendState = () => {
    sending.value = false
    sendingComplete.value = false
    progress.value = 0
    stats.value = { successful: 0, failed: 0, total: 0 }
    errorMessages.value = []
  }

  // Méthodes d'update pour le component parent
  const updateBatchSize = (size: number) => {
    batchSize.value = size
  }

  const updateBatchDelay = (delay: number) => {
    batchDelay.value = delay
  }

  const updateContinueOnError = (value: boolean) => {
    continueOnError.value = value
  }

  const updateShowProgress = (value: boolean) => {
    showProgress.value = value
  }

  const updateRetryPolicy = (policy: string) => {
    retryPolicy.value = policy
  }

  const startSending = async (
    recipients: string[],
    templateName: string,
    templateLanguage: string = 'fr',
    customization: any = {}
  ) => {
    console.log('Démarrage de l\'envoi en masse')
    
    // Validation
    if (!recipients || recipients.length === 0) {
      $q.notify({
        type: 'negative',
        message: 'Aucun destinataire sélectionné'
      })
      return
    }
    
    if (!templateName) {
      $q.notify({
        type: 'negative',
        message: 'Aucun template sélectionné'
      })
      return
    }
    
    // Vérifier la limite de 500
    if (recipients.length > 500) {
      $q.notify({
        type: 'negative',
        message: 'Le nombre maximum de destinataires est de 500'
      })
      return
    }
    
    try {
      // Préparer l'envoi
      preparing.value = true
      sending.value = true
      sendingComplete.value = false
      paused.value = false
      sendStartTime.value = new Date()
      stats.value = { successful: 0, failed: 0, total: recipients.length }
      progress.value = 0
      errors.value = []
      batchProgress.value = []
      
      // Préparer la requête
      const request: BulkSendRequest = {
        recipients,
        templateName,
        templateLanguage,
        bodyVariables: customization.bodyVariables || [],
        headerVariables: customization.headerVariables || [],
        headerMediaUrl: customization.headerMediaUrl,
        headerMediaId: customization.headerMediaId,
        options: {
          batchSize: batchSize.value,
          batchDelay: batchDelay.value,
          continueOnError: continueOnError.value,
          includeDetails: true
        }
      }
      
      preparing.value = false
      
      // Envoyer la requête
      $q.notify({
        type: 'info',
        message: 'Envoi en cours...'
      })
      
      const response = await whatsappBulkService.bulkSend(request)
      
      // Mettre à jour les statistiques
      stats.value = {
        successful: response.data.totalSent,
        failed: response.data.totalFailed,
        total: response.data.totalAttempted
      }
      
      progress.value = 100
      sending.value = false
      sendingComplete.value = true
      
      // Afficher le résultat
      if (response.success) {
        $q.notify({
          type: 'positive',
          message: `Envoi terminé : ${response.data.totalSent} messages envoyés avec succès`
        })
      } else {
        $q.notify({
          type: 'warning',
          message: `Envoi partiellement réussi : ${response.data.totalSent} envoyés, ${response.data.totalFailed} échoués`
        })
      }
      
      // Traiter les erreurs détaillées si disponibles
      if (response.data.failedRecipients) {
        errors.value = response.data.failedRecipients.map(failed => ({
          message: `${failed.recipient}: ${failed.error}`,
          phoneNumber: failed.recipient,
          timestamp: new Date()
        }))
      }
      
      // Calculer le taux d'envoi
      const duration = (new Date().getTime() - sendStartTime.value.getTime()) / 1000
      currentRate.value = Math.round(response.data.totalAttempted / duration * 60)
      
    } catch (error: any) {
      console.error('Erreur envoi en masse:', error)
      
      preparing.value = false
      sending.value = false
      sendingComplete.value = false
      
      $q.notify({
        type: 'negative',
        message: error.message || 'Erreur lors de l\'envoi en masse'
      })
      
      // Ajouter l'erreur à la liste
      errors.value.push({
        message: error.message || 'Erreur inconnue',
        timestamp: new Date()
      })
      
      // Si c'est une erreur de crédits
      if (error.message?.includes('Crédits insuffisants')) {
        stats.value.failed = recipients.length
      }
    }
  }

  const pauseSending = () => {
    paused.value = true
  }

  const resumeSending = () => {
    paused.value = false
  }

  const stopSending = () => {
    sending.value = false
    paused.value = false
  }

  const resetSending = () => {
    sending.value = false
    sendingComplete.value = false
    paused.value = false
    preparing.value = false
    progress.value = 0
    stats.value = { successful: 0, failed: 0, total: 0 }
    errorMessages.value = []
    batchProgress.value = []
    errors.value = []
    sendStartTime.value = null
    currentRate.value = 0
  }

  return {
    // État
    sending,
    sendingComplete,
    paused,
    preparing,
    progress,
    stats,
    errorMessages,
    batchProgress,
    errors,
    sendStartTime,
    currentRate,
    batchSize,
    batchDelay,
    continueOnError,
    showProgress,
    retryPolicy,
    
    // Computed
    progressColor,
    canSend,
    
    // Méthodes
    updateBatchSize,
    updateBatchDelay,
    updateContinueOnError,
    updateShowProgress,
    updateRetryPolicy,
    startSending,
    pauseSending,
    resumeSending,
    stopSending,
    resetSending,
    startBulkSend,
    resetSendState
  }
}