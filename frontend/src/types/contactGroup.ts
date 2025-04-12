import type { Contact } from './contact'; // Assuming contact types are defined here

export interface ContactGroup {
  id: string; // GraphQL ID is typically a string
  userId: string;
  name: string;
  description: string | null;
  createdAt: string; // ISO Date string
  updatedAt: string; // ISO Date string
  contactCount: number;
}

export interface ContactGroupMembership {
  id: string;
  contact: Contact;
  group: ContactGroup;
  createdAt: string;
}

export interface AddContactsToGroupError {
  contactId: string;
  message: string;
}

export interface AddContactsToGroupResult {
  status: 'success' | 'partial';
  message: string;
  successful: number;
  failed: number;
  memberships: ContactGroupMembership[];
  errors: AddContactsToGroupError[];
}

// Input types for mutations (optional but good practice)
export interface CreateContactGroupInput {
  name: string;
  description?: string | null;
}

export interface UpdateContactGroupInput {
  id: string;
  name?: string;
  description?: string | null;
}
