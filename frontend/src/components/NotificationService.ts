import { reactive } from 'vue';
import { Notify } from 'quasar';

interface NotificationOptions {
  message: string;
  caption?: string;
  color?: string;
  icon?: string;
  position?: 'top-left' | 'top-right' | 'bottom-left' | 'bottom-right' | 'top' | 'bottom' | 'left' | 'right' | 'center';
  timeout?: number;
  actions?: Array<{
    label: string;
    color?: string;
    handler: () => void;
  }>;
}

// Singleton pour le service de notification
const notificationService = () => {
  // Configuration par défaut
  const defaultOptions = reactive({
    position: 'top-right' as const,
    timeout: 5000,
  });

  // Méthode pour afficher une notification de succès
  const success = (title: string, message: string, options?: Partial<NotificationOptions>) => {
    Notify.create({
      message: title,
      caption: message,
      color: 'positive',
      icon: 'check_circle',
      ...defaultOptions,
      ...options
    });
  };

  // Méthode pour afficher une notification d'erreur
  const error = (title: string, message: string, options?: Partial<NotificationOptions>) => {
    Notify.create({
      message: title,
      caption: message,
      color: 'negative',
      icon: 'error',
      ...defaultOptions,
      ...options
    });
  };

  // Méthode pour afficher une notification d'avertissement
  const warning = (title: string, message: string, options?: Partial<NotificationOptions>) => {
    Notify.create({
      message: title,
      caption: message,
      color: 'warning',
      icon: 'warning',
      ...defaultOptions,
      ...options
    });
  };

  // Méthode pour afficher une notification d'information
  const info = (title: string, message: string, options?: Partial<NotificationOptions>) => {
    Notify.create({
      message: title,
      caption: message,
      color: 'info',
      icon: 'info',
      ...defaultOptions,
      ...options
    });
  };

  // Méthode pour afficher une notification personnalisée
  const custom = (options: NotificationOptions) => {
    Notify.create({
      ...defaultOptions,
      ...options
    });
  };

  // Méthode pour configurer les options par défaut
  const configure = (options: Partial<typeof defaultOptions>) => {
    Object.assign(defaultOptions, options);
  };

  return {
    success,
    error,
    warning,
    info,
    custom,
    configure
  };
};

// Créer une instance unique du service
const notificationInstance = notificationService();

// Hook composable pour utiliser le service de notification
export const useNotification = () => {
  return notificationInstance;
};
