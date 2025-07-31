/**
 * Composable pour les actions sur les messages
 */

import { ref } from 'vue';
import { useQuasar } from 'quasar';
import type { WhatsAppMessageHistory } from '../../../../stores/whatsappStore';
import { useWhatsAppStore } from '../../../../stores/whatsappStore';
import { canReply } from '../utils/messageHelpers';
import { exportMessagesToCSV } from '../utils/messageFormatters';

export function useMessageActions() {
  const $q = useQuasar();
  const whatsAppStore = useWhatsAppStore();
  const sendingReply = ref(false);
  
  /**
   * Envoie une réponse à un message
   */
  async function sendReply(message: WhatsAppMessageHistory, content: string): Promise<boolean> {
    if (!message || !content.trim()) {
      return false;
    }
    
    sendingReply.value = true;
    
    try {
      const response = await whatsAppStore.sendMessage({
        recipient: message.phoneNumber,
        type: 'text',
        content: content
      });
      
      if (response) {
        $q.notify({
          type: 'positive',
          message: 'Message envoyé avec succès',
          position: 'top'
        });
        return true;
      } else {
        $q.notify({
          type: 'negative',
          message: whatsAppStore.error || 'Erreur lors de l\'envoi du message',
          position: 'top'
        });
        return false;
      }
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erreur lors de l\'envoi du message',
        position: 'top'
      });
      return false;
    } finally {
      sendingReply.value = false;
    }
  }
  
  /**
   * Télécharge un média
   */
  function downloadMedia(message: WhatsAppMessageHistory) {
    if (!message.mediaId) {
      return;
    }
    
    // TODO: Implémenter le téléchargement des médias
    $q.notify({
      type: 'info',
      message: 'Le téléchargement des médias sera bientôt disponible',
      position: 'top'
    });
  }
  
  /**
   * Exporte les messages
   */
  function exportMessages(messages: WhatsAppMessageHistory[]) {
    try {
      exportMessagesToCSV(messages);
      
      $q.notify({
        type: 'positive',
        message: 'Messages exportés avec succès',
        position: 'top'
      });
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erreur lors de l\'export',
        position: 'top'
      });
    }
  }
  
  /**
   * Copie le contenu d'un message
   */
  async function copyMessageContent(message: WhatsAppMessageHistory) {
    if (!message.content) {
      return;
    }
    
    try {
      await navigator.clipboard.writeText(message.content);
      $q.notify({
        type: 'positive',
        message: 'Contenu copié dans le presse-papiers',
        position: 'top'
      });
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erreur lors de la copie',
        position: 'top'
      });
    }
  }
  
  /**
   * Copie le numéro de téléphone
   */
  async function copyPhoneNumber(phoneNumber: string) {
    try {
      await navigator.clipboard.writeText(phoneNumber);
      $q.notify({
        type: 'positive',
        message: 'Numéro copié dans le presse-papiers',
        position: 'top'
      });
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erreur lors de la copie',
        position: 'top'
      });
    }
  }
  
  /**
   * Rafraîchit l'historique des messages
   */
  async function refreshMessages() {
    try {
      await whatsAppStore.fetchMessageHistory();
      $q.notify({
        type: 'positive',
        message: 'Messages actualisés',
        position: 'top'
      });
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erreur lors de l\'actualisation',
        position: 'top'
      });
    }
  }
  
  return {
    // État
    sendingReply,
    
    // Méthodes
    sendReply,
    downloadMedia,
    exportMessages,
    copyMessageContent,
    copyPhoneNumber,
    refreshMessages,
    canReply
  };
}