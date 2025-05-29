/**
 * Types spécifiques pour la vue Contacts
 */

import type { Contact, ContactFormData, ContactCreateData } from '../../../types/contact';

// Re-export des types principaux pour la cohérence
export type { Contact, ContactFormData, ContactCreateData };

// Types pour les stats du header
export interface ContactsStats {
  total: number;
  active: number;
  groups: number;
}

// Types pour les filtres
export interface ContactsFilters {
  searchTerm: string;
  groupId: string | null;
  sortBy: string;
  sortDesc: boolean;
}

// Types pour la pagination
export interface ContactsPagination {
  page: number;
  rowsPerPage: number;
  sortBy: string;
  descending: boolean;
}

// Types pour les actions CRUD
export interface ContactActionState {
  saving: boolean;
  deleting: boolean;
  error: string | null;
}

// Types pour l'import
export interface ImportState {
  importing: boolean;
  file: File | null;
  progress: number;
  error: string | null;
}

// Types pour les modals
export interface ContactModalsState {
  contactDialog: boolean;
  deleteDialog: boolean;
  importDialog: boolean;
  detailModal: boolean;
}

// Types pour les événements entre composants
export interface ContactEvents {
  'contact-created': Contact;
  'contact-updated': Contact;
  'contact-deleted': string; // ID du contact
  'contacts-imported': number; // Nombre de contacts importés
  'view-details': Contact;
  'send-sms': Contact;
  'send-whatsapp': Contact;
}

// Types pour les props des composants
export interface ContactsHeaderProps {
  stats: ContactsStats;
  loading?: boolean;
}

export interface ContactsFiltersProps {
  filters: ContactsFilters;
  groupOptions: Array<{ label: string; value: string | null }>;
  loading?: boolean;
}

export interface ContactsListProps {
  contacts: Contact[];
  pagination: ContactsPagination;
  totalCount: number;
  loading?: boolean;
  viewMode?: 'list' | 'grid';
}

export interface ContactDetailModalProps {
  contact: Contact | null;
  visible: boolean;
  loading?: boolean;
}

export interface ContactImportDialogProps {
  visible: boolean;
  state: ImportState;
}

// Type pour le mode d'affichage
export type ViewMode = 'list' | 'grid';

// Type pour les actions de contact
export type ContactAction = 'create' | 'update' | 'delete' | 'view' | 'import';