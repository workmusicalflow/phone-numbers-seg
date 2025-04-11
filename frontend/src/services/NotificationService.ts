import { useQuasar, QNotifyCreateOptions } from 'quasar';

/**
 * Composable pour gérer les notifications dans l'application
 * À utiliser uniquement dans les composants Vue (pas dans les stores)
 */
export function useNotification() {
  const $q = useQuasar(); // Fonctionne car appelé depuis un composant Vue

  const show = (options: QNotifyCreateOptions | string) => {
    if (!$q) {
      console.error("Quasar notify ($q) non disponible. Appel hors contexte setup?");
      // Fallback console log
      const message = typeof options === 'string' ? options : options.message;
      console.log(`[NOTIFY FALLBACK] ${message}`);
      return;
    }
    $q.notify(typeof options === 'string' ? { message: options } : options);
  };

  const showSuccess = (message: string) => show({ type: 'positive', message });
  const showError = (message: string) => show({ type: 'negative', message });
  const showWarning = (message: string) => show({ type: 'warning', message });
  const showInfo = (message: string) => show({ type: 'info', message });

  return { showSuccess, showError, showWarning, showInfo, show };
}
