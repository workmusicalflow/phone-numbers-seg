/**
 * Utilitaires de formatage pour l'application
 */

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
 * Formate une date en heure seulement
 */
export function formatTime(dateString: string): string {
  const date = new Date(dateString);
  return date.toLocaleTimeString('fr-FR', { 
    hour: '2-digit', 
    minute: '2-digit' 
  });
}

/**
 * Formate une date en format court intelligible
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
 * Formate une date complète avec heure
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
 * Formate un montant en devise
 */
export function formatCurrency(amount: number, currency: string = 'XOF'): string {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: currency,
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount);
}

/**
 * Formate un nombre avec des séparateurs de milliers
 */
export function formatNumber(num: number): string {
  return new Intl.NumberFormat('fr-FR').format(num);
}

/**
 * Formate une taille de fichier
 */
export function formatFileSize(bytes: number): string {
  const units = ['B', 'KB', 'MB', 'GB'];
  let size = bytes;
  let unitIndex = 0;
  
  while (size >= 1024 && unitIndex < units.length - 1) {
    size /= 1024;
    unitIndex++;
  }
  
  return `${size.toFixed(1)} ${units[unitIndex]}`;
}