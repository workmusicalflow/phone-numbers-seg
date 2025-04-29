/**
 * Interfaces pour la gestion des contacts
 */

export interface Group {
  id: number | string;
  name: string;
}

export interface SMSHistory {
  id: string;
  message: string;
  status: string;
  createdAt: string;
  sentAt?: string | null;
  deliveredAt?: string | null;
  failedAt?: string | null;
  errorMessage?: string | null;
}

export interface Contact {
  id: string;
  name: string; // Changed from firstName/lastName
  phoneNumber: string;
  email?: string | null;
  groups?: Group[];
  notes?: string | null;
  smsHistory?: SMSHistory[]; // Liste des SMS envoyés à ce contact
  smsTotalCount?: number; // Nombre total de SMS envoyés
  smsSentCount?: number; // Nombre de SMS envoyés avec succès
  smsFailedCount?: number; // Nombre de SMS échoués
  smsScore?: number; // Score basé sur le ratio SENT / Total
}

export interface ContactFormData {
  id: string;
  name: string; // Changed from firstName/lastName
  phoneNumber: string;
  email: string;
  groups: (number | string)[];
  notes: string;
}

export interface ContactCreateData {
  name: string; // Changed from firstName/lastName
  phoneNumber: string;
  email: string | null;
  groups: string[];
  notes: string | null;
}
