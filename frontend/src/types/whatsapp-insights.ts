/**
 * Types TypeScript pour les insights WhatsApp des contacts
 */

export interface WhatsAppContactInsights {
  totalMessages: number;
  outgoingMessages: number;
  incomingMessages: number;
  deliveredMessages: number;
  readMessages: number;
  failedMessages: number;
  lastMessageDate: string | null;
  lastMessageType: string | null;
  lastMessageContent: string | null;
  templatesUsed: string[];
  conversationCount: number;
  messagesByType: Record<string, number>;
  messagesByStatus: Record<string, number>;
  messagesByMonth: Record<string, number>;
  deliveryRate: number;
  readRate: number;
}

export interface WhatsAppContactSummary {
  contactId: string;
  phoneNumber: string;
  totalMessages: number;
  lastMessageDate: string | null;
}

export interface WhatsAppInsightsState {
  insights: Map<string, WhatsAppContactInsights>;
  summaries: Map<string, WhatsAppContactSummary>;
  loading: boolean;
  error: string | null;
}

// Types pour les métriques calculées
export interface WhatsAppMetrics {
  engagement: {
    responseRate: number;
    averageResponseTime: number;
  };
  activity: {
    messagesThisWeek: number;
    messagesLastWeek: number;
    trend: 'up' | 'down' | 'stable';
  };
  templates: {
    mostUsed: string[];
    successRate: number;
  };
}

// Types pour les graphiques et visualisations
export interface ChartDataPoint {
  label: string;
  value: number;
  color?: string;
}

export interface MessageTypeData extends ChartDataPoint {
  type: 'text' | 'template' | 'image' | 'video' | 'audio' | 'document' | 'interactive';
}

export interface MessageStatusData extends ChartDataPoint {
  status: 'sent' | 'delivered' | 'read' | 'failed' | 'received' | 'pending';
}

export interface MonthlyMessageData extends ChartDataPoint {
  month: string; // Format: YYYY-MM
}