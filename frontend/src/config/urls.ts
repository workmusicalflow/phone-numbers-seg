/**
 * URL Configuration
 * 
 * This file defines all URL constants used throughout the frontend application.
 * URLs are read from environment variables when available, with fallbacks to default values.
 * 
 * Usage:
 * - Always use these constants instead of hardcoding URLs
 * - Import from this file: import { API, ROUTES } from '@/config/urls'
 */

import { getEnvironmentVariable } from './environment';

/**
 * API endpoints
 */
export const API = {
  /**
   * Base API URL
   */
  BASE: getEnvironmentVariable('API_BASE_URL', 'http://localhost:8000/api'),

  /**
   * GraphQL endpoint
   */
  GRAPHQL: getEnvironmentVariable('GRAPHQL_ENDPOINT', 'http://localhost:8000/graphql.php'),

  /**
   * User-related endpoints
   */
  USERS: {
    /**
     * Base users endpoint
     */
    BASE: () => `${API.BASE}/users`,
    
    /**
     * Get a specific user by ID
     */
    GET: (id: string) => `${API.USERS.BASE()}/${id}`,
    
    /**
     * User authentication endpoint
     */
    AUTH: () => `${API.USERS.BASE()}/auth`,
    
    /**
     * User login endpoint
     */
    LOGIN: () => `${API.USERS.AUTH()}/login`,
    
    /**
     * User logout endpoint
     */
    LOGOUT: () => `${API.USERS.AUTH()}/logout`,
    
    /**
     * Password reset request endpoint
     */
    RESET_PASSWORD: () => `${API.USERS.AUTH()}/reset-password`,
  },

  /**
   * Contact-related endpoints
   */
  CONTACTS: {
    /**
     * Base contacts endpoint
     */
    BASE: () => `${API.BASE}/contacts`,
    
    /**
     * Get a specific contact by ID
     */
    GET: (id: string) => `${API.CONTACTS.BASE()}/${id}`,
    
    /**
     * Search contacts endpoint
     */
    SEARCH: () => `${API.CONTACTS.BASE()}/search`,
    
    /**
     * Import contacts endpoint
     */
    IMPORT: () => `${API.CONTACTS.BASE()}/import`,
    
    /**
     * Export contacts endpoint
     */
    EXPORT: () => `${API.CONTACTS.BASE()}/export`,
  },

  /**
   * Contact group-related endpoints
   */
  CONTACT_GROUPS: {
    /**
     * Base contact groups endpoint
     */
    BASE: () => `${API.BASE}/contact-groups`,
    
    /**
     * Get a specific contact group by ID
     */
    GET: (id: string) => `${API.CONTACT_GROUPS.BASE()}/${id}`,
    
    /**
     * Get contacts in a specific group
     */
    CONTACTS: (groupId: string) => `${API.CONTACT_GROUPS.GET(groupId)}/contacts`,
    
    /**
     * Add contacts to a group
     */
    ADD_CONTACTS: (groupId: string) => `${API.CONTACT_GROUPS.GET(groupId)}/add-contacts`,
    
    /**
     * Remove a contact from a group
     */
    REMOVE_CONTACT: (groupId: string, contactId: string) => 
      `${API.CONTACT_GROUPS.GET(groupId)}/contacts/${contactId}`,
  },

  /**
   * SMS-related endpoints
   */
  SMS: {
    /**
     * Base SMS endpoint
     */
    BASE: () => `${API.BASE}/sms`,
    
    /**
     * Send SMS endpoint
     */
    SEND: () => `${API.SMS.BASE()}/send`,
    
    /**
     * Send bulk SMS endpoint
     */
    SEND_BULK: () => `${API.SMS.BASE()}/send-bulk`,
    
    /**
     * Send SMS to segment endpoint
     */
    SEND_TO_SEGMENT: () => `${API.SMS.BASE()}/send-to-segment`,
    
    /**
     * Send SMS to all contacts endpoint
     */
    SEND_TO_ALL_CONTACTS: () => `${API.SMS.BASE()}/send-to-all-contacts`,
    
    /**
     * SMS history endpoint
     */
    HISTORY: () => `${API.SMS.BASE()}/history`,
    
    /**
     * Retry sending an SMS
     */
    RETRY: (id: string) => `${API.SMS.BASE()}/retry/${id}`,
  },

  /**
   * WhatsApp-related endpoints
   */
  WHATSAPP: {
    /**
     * Base WhatsApp endpoint
     */
    BASE: () => `${API.BASE}/whatsapp`,
    
    /**
     * Send template message endpoint
     * @see /docs/whatsapp-api-endpoints-clarification.md for API details
     */
    SEND_TEMPLATE: () => `${API.WHATSAPP.BASE()}/send-template.php`,
    
    /**
     * Upload media endpoint
     */
    UPLOAD_MEDIA: () => `${API.WHATSAPP.BASE()}/upload.php`,
    
    /**
     * Webhook endpoint for Meta callbacks
     * Note: Webhook is located in /whatsapp/ not /api/whatsapp/
     */
    WEBHOOK: () => `${API.BASE.replace('/api', '')}/whatsapp/webhook.php`,
    
    /**
     * Check message status
     */
    STATUS: () => `${API.WHATSAPP.BASE()}/status.php`,
    
    /**
     * Get approved templates
     */
    TEMPLATES_APPROVED: () => `${API.WHATSAPP.BASE()}/templates/approved.php`,
  },
};

/**
 * Frontend routes
 */
export const ROUTES = {
  /**
   * Home page
   */
  HOME: '/',
  
  /**
   * Login page
   */
  LOGIN: '/login',
  
  /**
   * Dashboard page
   */
  DASHBOARD: '/dashboard',
  
  /**
   * Admin dashboard page
   */
  ADMIN_DASHBOARD: '/admin/dashboard',
  
  /**
   * Contacts page
   */
  CONTACTS: '/contacts',
  
  /**
   * Contact details page
   */
  CONTACT_DETAILS: (id: string) => `/contacts/${id}`,
  
  /**
   * Contact groups page
   */
  CONTACT_GROUPS: '/contact-groups',
  
  /**
   * Contact group details page
   */
  CONTACT_GROUP_DETAILS: (id: string) => `/contact-groups/${id}`,
  
  /**
   * SMS page
   */
  SMS: '/sms',
  
  /**
   * SMS history page
   */
  SMS_HISTORY: '/sms/history',
  
  /**
   * Import page
   */
  IMPORT: '/import',
  
  /**
   * User management page
   */
  USERS: '/users',
  
  /**
   * User details page
   */
  USER_DETAILS: (id: string) => `/users/${id}`,
};

/**
 * External service URLs
 */
export const EXTERNAL = {
  /**
   * Orange API base URL
   */
  ORANGE_API: getEnvironmentVariable('ORANGE_API_URL', 'https://api.orange.com'),
  
  /**
   * Documentation URL
   */
  DOCUMENTATION: getEnvironmentVariable('DOCUMENTATION_URL', 'https://docs.example.com'),
  
  /**
   * Support URL
   */
  SUPPORT: getEnvironmentVariable('SUPPORT_URL', 'https://support.example.com'),
};
