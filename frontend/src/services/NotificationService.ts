import { createApp, h } from 'vue';
import CustomNotification from '../components/CustomNotification.vue';

interface NotificationOptions {
  message: string;
  color?: string;
  textColor?: string;
  icon?: string;
  timeout?: number;
  autoClose?: boolean;
}

class NotificationService {
  private static instance: NotificationService;
  private activeNotifications: HTMLElement[] = [];

  private constructor() {}

  public static getInstance(): NotificationService {
    if (!NotificationService.instance) {
      NotificationService.instance = new NotificationService();
    }
    return NotificationService.instance;
  }

  public success(message: string, options: Partial<NotificationOptions> = {}): void {
    this.show({
      message,
      color: 'positive',
      icon: 'check_circle',
      ...options
    });
  }

  public error(message: string, options: Partial<NotificationOptions> = {}): void {
    this.show({
      message,
      color: 'negative',
      icon: 'error',
      ...options
    });
  }

  public info(message: string, options: Partial<NotificationOptions> = {}): void {
    this.show({
      message,
      color: 'info',
      icon: 'info',
      ...options
    });
  }

  public warning(message: string, options: Partial<NotificationOptions> = {}): void {
    this.show({
      message,
      color: 'warning',
      icon: 'warning',
      ...options
    });
  }

  private show(options: NotificationOptions): any {
    // Create a container for the notification
    const container = document.createElement('div');
    document.body.appendChild(container);
    this.activeNotifications.push(container);

    // Create the notification component
    const app = createApp({
      render() {
        return h(CustomNotification, options);
      }
    });

    // Mount the notification
    const instance = app.mount(container);

    // Clean up when the notification is closed
    const cleanup = () => {
      try {
        // Check if the app is still mounted and container still exists
        if (container && document.body.contains(container)) {
          app.unmount();
          document.body.removeChild(container);
          this.activeNotifications = this.activeNotifications.filter(n => n !== container);
        }
      } catch (error) {
        console.error('Error during notification cleanup:', error);
      }
    };

    // Add event listener for when the notification is closed
    if (options.autoClose !== false) {
      setTimeout(() => {
        cleanup();
      }, options.timeout || 3000);
    }

    // Return the instance for manual control if needed
    return instance;
  }

  public clearAll(): void {
    this.activeNotifications.forEach(container => {
      if (document.body.contains(container)) {
        document.body.removeChild(container);
      }
    });
    this.activeNotifications = [];
  }
}

export default NotificationService.getInstance();
