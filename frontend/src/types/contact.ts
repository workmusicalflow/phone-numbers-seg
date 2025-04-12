/**
 * Interfaces pour la gestion des contacts
 */

export interface Group {
  id: number | string;
  name: string;
}

export interface Contact {
  id: string;
  firstName: string;
  lastName: string;
  phoneNumber: string;
  email?: string | null;
  groups?: Group[];
  notes?: string | null;
}

export interface ContactFormData {
  id: string;
  firstName: string;
  lastName: string;
  phoneNumber: string;
  email: string;
  groups: (number | string)[];
  notes: string;
}

export interface ContactCreateData {
  firstName: string;
  lastName: string;
  phoneNumber: string;
  email: string | null;
  groups: string[];
  notes: string | null;
}
