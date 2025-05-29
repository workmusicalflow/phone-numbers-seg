/**
 * Fonctions utilitaires pour les messages WhatsApp
 */

import type { WhatsAppMessageHistory } from '../../../../stores/whatsappStore';
import {
  MESSAGE_TYPE_COLORS,
  MESSAGE_TYPE_ICONS,
  MESSAGE_TYPE_LABELS,
  STATUS_COLORS,
  STATUS_ICONS,
  STATUS_LABELS,
  REPLY_WINDOW_HOURS
} from './messageConstants';

/**
 * Retourne la couleur associée au type de message
 */
export function getMessageTypeColor(type: string): string {
  return MESSAGE_TYPE_COLORS[type] || 'grey';
}

/**
 * Retourne l'icône associée au type de message
 */
export function getMessageTypeIcon(type: string): string {
  return MESSAGE_TYPE_ICONS[type] || 'help_outline';
}

/**
 * Retourne le label associé au type de message
 */
export function getMessageTypeLabel(type: string): string {
  return MESSAGE_TYPE_LABELS[type] || type;
}

/**
 * Retourne la couleur associée au statut
 */
export function getStatusColor(status: string): string {
  return STATUS_COLORS[status] || 'grey';
}

/**
 * Retourne l'icône associée au statut
 */
export function getStatusIcon(status: string): string {
  return STATUS_ICONS[status] || 'help';
}

/**
 * Retourne le label associé au statut
 */
export function getStatusLabel(status: string): string {
  return STATUS_LABELS[status] || status;
}

/**
 * Vérifie si on peut répondre à un message (fenêtre de 24h)
 */
export function canReply(message: WhatsAppMessageHistory): boolean {
  // On ne peut répondre qu'aux messages entrants
  if (message.direction !== 'INCOMING') {
    return false;
  }
  
  // Vérifier si on est dans la fenêtre de 24h
  const messageDate = new Date(message.timestamp);
  const now = new Date();
  const hoursDiff = (now.getTime() - messageDate.getTime()) / (1000 * 60 * 60);
  
  return hoursDiff <= REPLY_WINDOW_HOURS;
}

/**
 * Calcule le temps restant pour répondre à un message
 */
export function getRemainingReplyTime(message: WhatsAppMessageHistory): string | null {
  if (!canReply(message)) {
    return null;
  }
  
  const messageDate = new Date(message.timestamp);
  const now = new Date();
  const hoursDiff = (now.getTime() - messageDate.getTime()) / (1000 * 60 * 60);
  const remainingHours = REPLY_WINDOW_HOURS - hoursDiff;
  
  if (remainingHours < 1) {
    const remainingMinutes = Math.floor(remainingHours * 60);
    return `${remainingMinutes} minute${remainingMinutes > 1 ? 's' : ''}`;
  }
  
  const hours = Math.floor(remainingHours);
  const minutes = Math.floor((remainingHours - hours) * 60);
  
  return `${hours}h${minutes > 0 ? ` ${minutes}min` : ''}`;
}

/**
 * Détermine si un message contient des médias
 */
export function hasMedia(message: WhatsAppMessageHistory): boolean {
  return ['image', 'video', 'audio', 'document'].includes(message.type) && !!message.mediaId;
}

/**
 * Détermine si un message est un template
 */
export function isTemplate(message: WhatsAppMessageHistory): boolean {
  return message.type === 'template';
}

/**
 * Calcule les statistiques des messages
 */
export function calculateMessageStats(messages: WhatsAppMessageHistory[]) {
  const stats = {
    total: messages.length,
    incoming: 0,
    outgoing: 0,
    delivered: 0,
    read: 0,
    failed: 0
  };
  
  messages.forEach(msg => {
    if (msg.direction === 'INCOMING') {
      stats.incoming++;
    } else {
      stats.outgoing++;
    }
    
    if (msg.status === 'delivered') stats.delivered++;
    if (msg.status === 'read') stats.read++;
    if (msg.status === 'failed') stats.failed++;
  });
  
  return stats;
}