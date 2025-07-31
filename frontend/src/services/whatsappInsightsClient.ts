/**
 * Client REST pour les insights WhatsApp
 * 
 * Architecture Clean Code : Service dédié pour l'API REST
 * Responsabilité unique : Communication avec l'API insights WhatsApp
 */

import type { WhatsAppContactInsights, WhatsAppContactSummary } from '../types/whatsapp-insights';

interface ApiResponse<T> {
  success: boolean;
  data?: T;
  message?: string;
  error?: string;
}

interface ContactSummaryRequest {
  contactIds: string[];
}

/**
 * Service client pour les insights WhatsApp
 */
export class WhatsAppInsightsClient {
  private baseUrl: string;

  constructor(baseUrl: string = 'http://localhost:8000') {
    this.baseUrl = baseUrl;
  }

  /**
   * Récupérer les insights pour un contact spécifique
   */
  async getContactInsights(contactId: string): Promise<WhatsAppContactInsights | null> {
    try {
      const response = await fetch(`${this.baseUrl}/api/whatsapp/insights.php?contactId=${contactId}`, {
        method: 'GET',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        },
      });

      if (!response.ok) {
        if (response.status === 404) {
          return null; // Pas d'insights pour ce contact
        }
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const result: ApiResponse<WhatsAppContactInsights> = await response.json();
      
      if (!result.success) {
        throw new Error(result.error || 'Erreur lors de la récupération des insights');
      }

      return result.data || null;

    } catch (error) {
      console.error('Erreur lors de la récupération des insights WhatsApp:', error);
      throw error;
    }
  }

  /**
   * Récupérer un résumé des insights pour plusieurs contacts
   */
  async getContactsSummary(contactIds: string[]): Promise<WhatsAppContactSummary[]> {
    try {
      const requestBody: ContactSummaryRequest = { contactIds };

      const response = await fetch(`${this.baseUrl}/api/whatsapp/insights.php`, {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestBody),
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const result: ApiResponse<WhatsAppContactSummary[]> = await response.json();
      
      if (!result.success) {
        throw new Error(result.error || 'Erreur lors de la récupération du résumé');
      }

      return result.data || [];

    } catch (error) {
      console.error('Erreur lors de la récupération du résumé des insights WhatsApp:', error);
      throw error;
    }
  }

  /**
   * Vérifier si un contact a des messages WhatsApp
   */
  async hasWhatsAppMessages(contactId: string): Promise<boolean> {
    try {
      const insights = await this.getContactInsights(contactId);
      return insights !== null && insights.totalMessages > 0;
    } catch (error) {
      console.error('Erreur lors de la vérification des messages WhatsApp:', error);
      return false;
    }
  }
}

// Instance singleton
export const whatsappInsightsClient = new WhatsAppInsightsClient();

// Export des types pour faciliter l'utilisation
export type { WhatsAppContactInsights, WhatsAppContactSummary };