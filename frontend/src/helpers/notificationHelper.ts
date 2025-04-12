/**
 * Simple notification helper that can be used outside of Vue components.
 * Currently logs to console as a fallback.
 * TODO: Integrate with a proper global notification system if available.
 */

interface NotifyOptions {
  message: string;
  type?: 'positive' | 'negative' | 'warning' | 'info' | 'ongoing';
  // Add other common options if needed (e.g., timeout, icon)
}

function showNotification(options: NotifyOptions | string): void {
  const message = typeof options === 'string' ? options : options.message;
  const type = typeof options === 'string' ? 'info' : options.type || 'info';

  console.log(`[Notification][${type.toUpperCase()}]: ${message}`);

  // Placeholder for integration with a real notification library/system
  // Example: EventBus.$emit('show-notification', { message, type });
  // Example: window.globalNotificationSystem.show({ message, type });
}

export const notificationHelper = {
  show: showNotification,
  showSuccess: (message: string) => showNotification({ message, type: 'positive' }),
  showError: (message: string) => showNotification({ message, type: 'negative' }),
  showWarning: (message: string) => showNotification({ message, type: 'warning' }),
  showInfo: (message: string) => showNotification({ message, type: 'info' }),
};

// Ensure it's treated as a module
export {};
