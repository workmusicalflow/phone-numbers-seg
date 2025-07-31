/**
 * Constantes pour le module WhatsApp
 */

export const MESSAGE_CONSTANTS = {
  // Options de statut
  statusOptions: [
    { label: 'Envoyé', value: 'sent' },
    { label: 'Livré', value: 'delivered' },
    { label: 'Lu', value: 'read' },
    { label: 'Échoué', value: 'failed' },
    { label: 'Reçu', value: 'received' },
    { label: 'En attente', value: 'pending' }
  ],
  
  // Options de direction
  directionOptions: [
    { label: 'Entrant', value: 'INCOMING' },
    { label: 'Sortant', value: 'OUTGOING' }
  ],
  
  // Couleurs des types de message
  getMessageTypeColor(type: string): string {
    const colors: Record<string, string> = {
      'text': 'primary',
      'image': 'green',
      'document': 'blue',
      'audio': 'deep-purple',
      'video': 'red',
      'template': 'info',
      'sticker': 'amber',
      'location': 'teal',
      'contact': 'indigo',
      'reaction': 'pink'
    };
    return colors[type] || 'grey';
  },
  
  // Icônes des types de message
  getMessageTypeIcon(type: string): string {
    const icons: Record<string, string> = {
      'text': 'message',
      'image': 'image',
      'document': 'description',
      'audio': 'audio_file',
      'video': 'video_file',
      'template': 'article',
      'sticker': 'sentiment_satisfied',
      'location': 'location_on',
      'contact': 'contact_phone',
      'reaction': 'favorite'
    };
    return icons[type] || 'help_outline';
  },
  
  // Labels des types de message
  getMessageTypeLabel(type: string): string {
    const labels: Record<string, string> = {
      'text': 'Texte',
      'image': 'Image',
      'document': 'Document',
      'audio': 'Audio',
      'video': 'Vidéo',
      'template': 'Modèle',
      'sticker': 'Sticker',
      'location': 'Position',
      'contact': 'Contact',
      'reaction': 'Réaction'
    };
    return labels[type] || type;
  },
  
  // Couleurs des statuts
  getStatusColor(status: string): string {
    const colors: Record<string, string> = {
      'sent': 'blue',
      'delivered': 'green',
      'read': 'deep-purple',
      'failed': 'negative',
      'received': 'primary',
      'pending': 'orange',
      'processing': 'amber'
    };
    return colors[status] || 'grey';
  },
  
  // Icônes des statuts
  getStatusIcon(status: string): string {
    const icons: Record<string, string> = {
      'sent': 'done',
      'delivered': 'done_all',
      'read': 'visibility',
      'failed': 'error',
      'received': 'inbox',
      'pending': 'schedule',
      'processing': 'hourglass_empty'
    };
    return icons[status] || 'help';
  },
  
  // Labels des statuts
  getStatusLabel(status: string): string {
    const labels: Record<string, string> = {
      'sent': 'Envoyé',
      'delivered': 'Livré',
      'read': 'Lu',
      'failed': 'Échoué',
      'received': 'Reçu',
      'pending': 'En attente',
      'processing': 'En cours'
    };
    return labels[status] || status;
  }
};

// Limites et contraintes WhatsApp
export const WHATSAPP_LIMITS = {
  CONVERSATION_WINDOW_HOURS: 24,
  MESSAGE_MAX_LENGTH: 4096,
  CAPTION_MAX_LENGTH: 1024,
  MEDIA_MAX_SIZE: {
    image: 5 * 1024 * 1024, // 5MB
    video: 16 * 1024 * 1024, // 16MB
    audio: 16 * 1024 * 1024, // 16MB
    document: 100 * 1024 * 1024, // 100MB
  },
  SUPPORTED_IMAGE_FORMATS: ['jpeg', 'jpg', 'png'],
  SUPPORTED_VIDEO_FORMATS: ['mp4', '3gp'],
  SUPPORTED_AUDIO_FORMATS: ['aac', 'mp4', 'amr', 'mp3', 'ogg', 'opus'],
  SUPPORTED_DOCUMENT_FORMATS: ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt']
};

// Configuration des templates
export const TEMPLATE_CONFIG = {
  SUPPORTED_LANGUAGES: [
    { code: 'fr', label: 'Français' },
    { code: 'en', label: 'Anglais' },
    { code: 'es', label: 'Espagnol' },
    { code: 'pt', label: 'Portugais' }
  ],
  COMPONENT_TYPES: ['HEADER', 'BODY', 'FOOTER', 'BUTTON'],
  PARAMETER_TYPES: ['text', 'currency', 'date_time', 'image', 'document', 'video']
};

// Types de webhooks
export const WEBHOOK_TYPES = {
  MESSAGE_STATUS: 'message_status',
  INCOMING_MESSAGE: 'messages',
  MEDIA_DOWNLOAD: 'media',
  TEMPLATE_STATUS: 'template_status_update'
};

// Codes d'erreur WhatsApp courants
export const ERROR_CODES = {
  INVALID_TOKEN: 190,
  RATE_LIMIT: 4,
  TEMPLATE_NOT_FOUND: 132005,
  INVALID_PHONE: 132000,
  OUTSIDE_WINDOW: 131026,
  MEDIA_NOT_FOUND: 132016,
  TEMPLATE_PARAM_MISMATCH: 132012
};

// Messages d'erreur personnalisés
export const ERROR_MESSAGES: Record<number, string> = {
  [ERROR_CODES.INVALID_TOKEN]: 'Token d\'authentification invalide. Veuillez vous reconnecter.',
  [ERROR_CODES.RATE_LIMIT]: 'Limite de débit atteinte. Veuillez réessayer dans quelques instants.',
  [ERROR_CODES.TEMPLATE_NOT_FOUND]: 'Modèle de message introuvable.',
  [ERROR_CODES.INVALID_PHONE]: 'Numéro de téléphone invalide.',
  [ERROR_CODES.OUTSIDE_WINDOW]: 'Message hors de la fenêtre de conversation de 24h. Utilisez un modèle.',
  [ERROR_CODES.MEDIA_NOT_FOUND]: 'Média introuvable.',
  [ERROR_CODES.TEMPLATE_PARAM_MISMATCH]: 'Les paramètres du modèle ne correspondent pas.'
};