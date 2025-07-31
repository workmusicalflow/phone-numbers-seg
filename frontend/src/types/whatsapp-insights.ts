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
  messagesByType: {
    text?: number;
    image?: number;
    document?: number;
    video?: number;
    audio?: number;
    template?: number;
    interactive?: number;
  };
  messagesByStatus: {
    sent?: number;
    delivered?: number;
    read?: number;
    failed?: number;
  };
  messagesByMonth: {
    january?: number;
    february?: number;
    march?: number;
    april?: number;
    may?: number;
    june?: number;
    july?: number;
    august?: number;
    september?: number;
    october?: number;
    november?: number;
    december?: number;
  };
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