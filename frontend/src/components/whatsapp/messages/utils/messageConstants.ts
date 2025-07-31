/**
 * Constantes pour les messages WhatsApp
 */

// Options de filtrage
export const STATUS_OPTIONS = [
  { label: 'Envoyé', value: 'sent' },
  { label: 'Livré', value: 'delivered' },
  { label: 'Lu', value: 'read' },
  { label: 'Échoué', value: 'failed' },
  { label: 'Reçu', value: 'received' }
] as const;

export const DIRECTION_OPTIONS = [
  { label: 'Entrant', value: 'INCOMING' },
  { label: 'Sortant', value: 'OUTGOING' }
] as const;

// Couleurs des types de messages
export const MESSAGE_TYPE_COLORS: Record<string, string> = {
  'text': 'primary',
  'image': 'green',
  'document': 'blue',
  'audio': 'deep-purple',
  'video': 'red',
  'template': 'info',
  'sticker': 'amber',
  'location': 'teal'
};

// Icônes des types de messages
export const MESSAGE_TYPE_ICONS: Record<string, string> = {
  'text': 'message',
  'image': 'image',
  'document': 'description',
  'audio': 'audio_file',
  'video': 'video_file',
  'template': 'article',
  'sticker': 'sentiment_satisfied',
  'location': 'location_on'
};

// Labels des types de messages
export const MESSAGE_TYPE_LABELS: Record<string, string> = {
  'text': 'Texte',
  'image': 'Image',
  'document': 'Document',
  'audio': 'Audio',
  'video': 'Vidéo',
  'template': 'Modèle',
  'sticker': 'Sticker',
  'location': 'Position'
};

// Couleurs des statuts
export const STATUS_COLORS: Record<string, string> = {
  'sent': 'blue',
  'delivered': 'green',
  'read': 'deep-purple',
  'failed': 'negative',
  'received': 'primary',
  'pending': 'orange'
};

// Icônes des statuts
export const STATUS_ICONS: Record<string, string> = {
  'sent': 'done',
  'delivered': 'done_all',
  'read': 'visibility',
  'failed': 'error',
  'received': 'inbox',
  'pending': 'schedule'
};

// Labels des statuts
export const STATUS_LABELS: Record<string, string> = {
  'sent': 'Envoyé',
  'delivered': 'Livré',
  'read': 'Lu',
  'failed': 'Échoué',
  'received': 'Reçu',
  'pending': 'En attente'
};

// Configuration de la pagination
export const DEFAULT_PAGINATION = {
  rowsPerPage: 20,
  page: 1,
  rowsNumber: 0
};

export const ROWS_PER_PAGE_OPTIONS = [10, 20, 50, 100];

// Configuration du rafraîchissement
export const REFRESH_INTERVAL = 30000; // 30 secondes

// Configuration des colonnes de la table
export const TABLE_COLUMNS = [
  {
    name: 'direction',
    required: true,
    label: '',
    align: 'center' as const,
    field: 'direction',
    sortable: true,
    style: 'width: 40px'
  },
  {
    name: 'phoneNumber',
    required: true,
    label: 'Numéro',
    align: 'left' as const,
    field: 'phoneNumber',
    sortable: true
  },
  {
    name: 'type',
    required: true,
    label: 'Type',
    align: 'center' as const,
    field: 'type',
    sortable: true
  },
  {
    name: 'content',
    required: true,
    label: 'Contenu',
    align: 'left' as const,
    field: 'content',
    sortable: false
  },
  {
    name: 'status',
    required: true,
    label: 'Statut',
    align: 'center' as const,
    field: 'status',
    sortable: true
  },
  {
    name: 'timestamp',
    required: true,
    label: 'Date/Heure',
    align: 'center' as const,
    field: 'timestamp',
    sortable: true
  },
  {
    name: 'actions',
    required: true,
    label: 'Actions',
    align: 'center' as const,
    field: 'actions',
    sortable: false
  }
];

// Limites et constantes
export const MAX_CONTENT_LENGTH = 50;
export const REPLY_WINDOW_HOURS = 24;
export const CSV_HEADERS = [
  'Date/Heure',
  'Direction',
  'Numéro',
  'Type',
  'Contenu',
  'Statut',
  'ID WhatsApp'
];