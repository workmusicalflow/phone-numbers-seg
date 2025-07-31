/**
 * Fonctions de formatage pour les messages WhatsApp
 */

import type { WhatsAppMessageHistory } from '../../../../stores/whatsappStore';
import { CSV_HEADERS } from './messageConstants';

/**
 * Formate un numéro de téléphone pour l'affichage
 */
export function formatPhoneNumber(phoneNumber: string): string {
  if (!phoneNumber) return '';
  
  // Nettoyer le numéro
  const cleaned = phoneNumber.replace(/\D/g, '');
  
  // Format Côte d'Ivoire: +225 XX XX XX XX XX
  if (cleaned.startsWith('225') && cleaned.length === 13) {
    return `+${cleaned.slice(0, 3)} ${cleaned.slice(3, 5)} ${cleaned.slice(5, 7)} ${cleaned.slice(7, 9)} ${cleaned.slice(9, 11)} ${cleaned.slice(11)}`;
  }
  
  // Format international générique
  if (phoneNumber.startsWith('+')) {
    const parts = [];
    let remaining = phoneNumber.substring(1);
    
    // Code pays (1-4 chiffres)
    parts.push('+' + remaining.substring(0, 3));
    remaining = remaining.substring(3);
    
    // Grouper par 2
    while (remaining.length > 0) {
      parts.push(remaining.substring(0, 2));
      remaining = remaining.substring(2);
    }
    
    return parts.join(' ');
  }
  
  return phoneNumber;
}

/**
 * Formate l'heure d'un timestamp
 */
export function formatTime(dateString: string): string {
  const date = new Date(dateString);
  return date.toLocaleTimeString('fr-FR', { 
    hour: '2-digit', 
    minute: '2-digit' 
  });
}

/**
 * Formate uniquement la date
 */
export function formatDateOnly(dateString: string): string {
  const date = new Date(dateString);
  const now = new Date();
  
  // Aujourd'hui
  if (isSameDay(date, now)) {
    return "Aujourd'hui";
  }
  
  // Hier
  const yesterday = new Date(now);
  yesterday.setDate(yesterday.getDate() - 1);
  if (isSameDay(date, yesterday)) {
    return "Hier";
  }
  
  // Cette semaine
  const weekAgo = new Date(now);
  weekAgo.setDate(weekAgo.getDate() - 7);
  if (date > weekAgo) {
    return date.toLocaleDateString('fr-FR', { weekday: 'long' });
  }
  
  // Date complète
  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined
  });
}

/**
 * Formate la date complète avec l'heure
 */
export function formatFullDate(dateString: string): string {
  return new Date(dateString).toLocaleString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  });
}

/**
 * Vérifie si deux dates sont le même jour
 */
export function isSameDay(date1: Date, date2: Date): boolean {
  return date1.getFullYear() === date2.getFullYear() &&
         date1.getMonth() === date2.getMonth() &&
         date1.getDate() === date2.getDate();
}

/**
 * Tronque le contenu avec ellipsis
 */
export function truncateContent(content: string, maxLength: number = 50): string {
  if (content.length <= maxLength) return content;
  return content.substring(0, maxLength) + '...';
}

/**
 * Génère le contenu CSV à partir des messages
 */
export function generateCSV(messages: WhatsAppMessageHistory[]): string {
  const rows = messages.map(msg => [
    formatFullDate(msg.timestamp),
    msg.direction,
    msg.phoneNumber,
    msg.type,
    msg.content ? `"${msg.content.replace(/"/g, '""')}"` : '',
    msg.status,
    msg.wabaMessageId || ''
  ]);
  
  return [CSV_HEADERS, ...rows].map(row => row.join(',')).join('\n');
}

/**
 * Exporte les messages au format CSV
 */
export function exportMessagesToCSV(messages: WhatsAppMessageHistory[], filename?: string): void {
  const csvContent = generateCSV(messages);
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  
  const defaultFilename = `whatsapp_messages_${new Date().toISOString().split('T')[0]}.csv`;
  
  link.setAttribute('href', url);
  link.setAttribute('download', filename || defaultFilename);
  link.style.visibility = 'hidden';
  
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}