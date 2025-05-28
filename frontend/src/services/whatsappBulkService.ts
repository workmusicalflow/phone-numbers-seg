import axios from 'axios'
import { API } from '../config/urls'

export interface BulkSendRequest {
  recipients: string[]
  templateName: string
  templateLanguage?: string
  bodyVariables?: string[]
  headerVariables?: string[]
  headerMediaUrl?: string | null
  headerMediaId?: string | null
  defaultParameters?: Record<string, any>
  recipientParameters?: Record<string, Record<string, any>>
  options?: {
    batchSize?: number
    batchDelay?: number
    continueOnError?: boolean
    includeDetails?: boolean
  }
}

export interface BulkSendResponse {
  success: boolean
  message: string
  data: {
    totalSent: number
    totalFailed: number
    totalAttempted: number
    successRate: number
    errorSummary: Record<string, number>
    failedRecipients?: Array<{
      recipient: string
      error: string
    }>
  }
}

export class WhatsAppBulkService {
  private static instance: WhatsAppBulkService

  private constructor() {
    // Service singleton
  }

  static getInstance(): WhatsAppBulkService {
    if (!WhatsAppBulkService.instance) {
      WhatsAppBulkService.instance = new WhatsAppBulkService()
    }
    return WhatsAppBulkService.instance
  }

  /**
   * Envoie des messages WhatsApp en masse
   */
  async bulkSend(request: BulkSendRequest): Promise<BulkSendResponse> {
    try {
      const response = await axios.post<BulkSendResponse>(
        API.WHATSAPP.BULK_SEND(),
        request,
        {
          withCredentials: true,
          headers: {
            'Content-Type': 'application/json'
          }
        }
      )
      
      return response.data
    } catch (error: any) {
      if (error.response?.data) {
        throw new Error(error.response.data.error || 'Erreur lors de l\'envoi en masse')
      }
      throw new Error('Erreur de connexion au serveur')
    }
  }

  /**
   * Vérifie si l'utilisateur a assez de crédits
   */
  async checkCredits(recipientCount: number): Promise<{ hasEnough: boolean; available: number; required: number }> {
    // Cette méthode pourrait appeler une API dédiée pour vérifier les crédits
    // Pour l'instant, on retourne true
    return {
      hasEnough: true,
      available: 1000,
      required: recipientCount
    }
  }
}

export const whatsappBulkService = WhatsAppBulkService.getInstance()