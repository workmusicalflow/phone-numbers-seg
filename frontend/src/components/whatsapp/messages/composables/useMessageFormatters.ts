/**
 * Composable pour les fonctions de formatage
 */

import {
  formatPhoneNumber,
  formatTime,
  formatDateOnly,
  formatFullDate,
  truncateContent
} from '../utils/messageFormatters';

import {
  getMessageTypeColor,
  getMessageTypeIcon,
  getMessageTypeLabel,
  getStatusColor,
  getStatusIcon,
  getStatusLabel
} from '../utils/messageHelpers';

export function useMessageFormatters() {
  return {
    // Formatage de dates et texte
    formatPhoneNumber,
    formatTime,
    formatDateOnly,
    formatFullDate,
    truncateContent,
    
    // Helpers pour les types et statuts
    getMessageTypeColor,
    getMessageTypeIcon,
    getMessageTypeLabel,
    getStatusColor,
    getStatusIcon,
    getStatusLabel
  };
}