/**
 * Service de journalisation pour les opérations de médias
 * Fournit des fonctionnalités avancées de traçage pour les erreurs et les performances
 */

export enum LogLevel {
  DEBUG = 'debug',
  INFO = 'info',
  WARN = 'warn',
  ERROR = 'error'
}

export interface LogEntry {
  timestamp: string;
  level: LogLevel;
  message: string;
  correlationId?: string;
  data?: any;
  source?: string;
  operation?: string;
}

export class MediaLogger {
  private readonly STORAGE_KEY = 'media-logs';
  private readonly MAX_LOG_ENTRIES = 1000;
  private correlationId: string | null = null;

  constructor() {
    // Nettoyer les anciens logs au démarrage pour éviter de remplir le stockage
    this.cleanupOldLogs();
  }

  /**
   * Génère un nouvel ID de corrélation pour suivre les opérations liées
   */
  createCorrelationId(): string {
    this.correlationId = crypto.randomUUID();
    return this.correlationId;
  }

  /**
   * Définit l'ID de corrélation actuel
   */
  setCorrelationId(id: string): void {
    this.correlationId = id;
  }

  /**
   * Récupère l'ID de corrélation actuel
   */
  getCorrelationId(): string | null {
    return this.correlationId;
  }

  /**
   * Journalise un message de niveau DEBUG
   */
  debug(message: string, data?: any, operation?: string): void {
    this.log(LogLevel.DEBUG, message, data, operation);
  }

  /**
   * Journalise un message de niveau INFO
   */
  info(message: string, data?: any, operation?: string): void {
    this.log(LogLevel.INFO, message, data, operation);
  }

  /**
   * Journalise un message de niveau WARN
   */
  warn(message: string, data?: any, operation?: string): void {
    this.log(LogLevel.WARN, message, data, operation);
  }

  /**
   * Journalise un message de niveau ERROR
   */
  error(message: string, data?: any, operation?: string): void {
    this.log(LogLevel.ERROR, message, data, operation);
  }

  /**
   * Journalise un message avec le niveau spécifié
   */
  private log(level: LogLevel, message: string, data?: any, operation?: string): void {
    // Ne pas stocker de données sensibles ou trop volumineuses
    const safeData = this.sanitizeData(data);
    
    const logEntry: LogEntry = {
      timestamp: new Date().toISOString(),
      level,
      message,
      correlationId: this.correlationId || undefined,
      data: safeData,
      source: 'mediaService',
      operation
    };

    // Toujours afficher dans la console pour le débogage
    this.logToConsole(logEntry);
    
    // Stocker dans localStorage
    this.storeLog(logEntry);
  }

  /**
   * Affiche le log dans la console
   */
  private logToConsole(entry: LogEntry): void {
    const prefix = entry.correlationId ? `[${entry.correlationId.substring(0, 8)}]` : '';
    const operation = entry.operation ? `[${entry.operation}]` : '';
    
    switch (entry.level) {
      case LogLevel.DEBUG:
        console.debug(`${prefix}${operation} ${entry.message}`, entry.data || '');
        break;
      case LogLevel.INFO:
        console.info(`${prefix}${operation} ${entry.message}`, entry.data || '');
        break;
      case LogLevel.WARN:
        console.warn(`${prefix}${operation} ${entry.message}`, entry.data || '');
        break;
      case LogLevel.ERROR:
        console.error(`${prefix}${operation} ${entry.message}`, entry.data || '');
        break;
    }
  }

  /**
   * Stocke le log dans localStorage
   */
  private storeLog(entry: LogEntry): void {
    try {
      let logs = this.getLogs();
      
      // Ajouter le nouveau log et limiter le nombre total
      logs = [entry, ...logs.slice(0, this.MAX_LOG_ENTRIES - 1)];
      
      localStorage.setItem(this.STORAGE_KEY, JSON.stringify(logs));
    } catch (error) {
      console.error('Error storing log:', error);
    }
  }

  /**
   * Récupère tous les logs stockés
   */
  getLogs(): LogEntry[] {
    try {
      const storedLogs = localStorage.getItem(this.STORAGE_KEY);
      return storedLogs ? JSON.parse(storedLogs) : [];
    } catch (error) {
      console.error('Error loading logs:', error);
      return [];
    }
  }

  /**
   * Récupère les logs pour une corrélation spécifique
   */
  getLogsByCorrelationId(correlationId: string): LogEntry[] {
    return this.getLogs().filter(log => log.correlationId === correlationId);
  }

  /**
   * Récupère les logs pour un niveau spécifique
   */
  getLogsByLevel(level: LogLevel): LogEntry[] {
    return this.getLogs().filter(log => log.level === level);
  }

  /**
   * Récupère les logs pour une opération spécifique
   */
  getLogsByOperation(operation: string): LogEntry[] {
    return this.getLogs().filter(log => log.operation === operation);
  }

  /**
   * Vide tous les logs
   */
  clearLogs(): void {
    localStorage.removeItem(this.STORAGE_KEY);
  }

  /**
   * Nettoie les logs plus anciens que la période spécifiée
   */
  cleanupOldLogs(maxAgeDays: number = 3): void {
    try {
      const logs = this.getLogs();
      const now = new Date();
      const cutoffDate = new Date(now.setDate(now.getDate() - maxAgeDays));
      
      const filteredLogs = logs.filter(log => {
        const logDate = new Date(log.timestamp);
        return logDate >= cutoffDate;
      });
      
      if (filteredLogs.length < logs.length) {
        localStorage.setItem(this.STORAGE_KEY, JSON.stringify(filteredLogs));
      }
    } catch (error) {
      console.error('Error cleaning up old logs:', error);
    }
  }

  /**
   * Récupère un diagnostic complet du système
   */
  generateDiagnostics(): object {
    return {
      timestamp: new Date().toISOString(),
      browser: this.getBrowserInfo(),
      networkStatus: navigator.onLine,
      storage: this.getStorageInfo(),
      recentErrors: this.getLogsByLevel(LogLevel.ERROR).slice(0, 10),
      mediaStats: this.getMediaStats()
    };
  }

  /**
   * Récupère des informations sur le navigateur
   */
  private getBrowserInfo(): object {
    return {
      userAgent: navigator.userAgent,
      language: navigator.language,
      platform: navigator.platform,
      vendor: navigator.vendor
    };
  }

  /**
   * Récupère des informations sur le stockage
   */
  private getStorageInfo(): object {
    try {
      const storageSizes = {
        mediaLogs: localStorage.getItem(this.STORAGE_KEY)?.length || 0,
        mediaCache: localStorage.getItem('media-cache')?.length || 0,
        recentMedia: localStorage.getItem('media-library-recent')?.length || 0,
        favoriteMedia: localStorage.getItem('media-library-favorites')?.length || 0
      };
      
      return {
        totalUsed: Object.values(storageSizes).reduce((a, b) => a + b, 0),
        details: storageSizes
      };
    } catch (error) {
      return { error: 'Unable to retrieve storage info' };
    }
  }

  /**
   * Récupère des statistiques sur les médias
   */
  private getMediaStats(): object {
    try {
      const recentMedia = localStorage.getItem('media-library-recent');
      const favoriteMedia = localStorage.getItem('media-library-favorites');
      
      const recentMediaItems = recentMedia ? JSON.parse(recentMedia) : [];
      const favoriteMediaItems = favoriteMedia ? JSON.parse(favoriteMedia) : [];
      
      return {
        recentCount: recentMediaItems.length,
        favoriteCount: favoriteMediaItems.length,
        typeDistribution: this.getMediaTypeDistribution(recentMediaItems)
      };
    } catch (error) {
      return { error: 'Unable to retrieve media stats' };
    }
  }

  /**
   * Calcule la distribution des types de médias
   */
  private getMediaTypeDistribution(mediaItems: any[]): Record<string, number> {
    return mediaItems.reduce((acc: Record<string, number>, item: any) => {
      const type = item.type || 'unknown';
      acc[type] = (acc[type] || 0) + 1;
      return acc;
    }, {});
  }

  /**
   * Nettoie les données sensibles ou trop volumineuses avant enregistrement
   */
  private sanitizeData(data: any): any {
    if (!data) return undefined;
    
    try {
      // Cloner les données pour éviter de modifier l'original
      const clonedData = JSON.parse(JSON.stringify(data));
      
      // Supprimer les champs sensibles ou trop volumineux
      if (typeof clonedData === 'object') {
        // Limiter la taille des données binaires ou volumineuses
        if (clonedData.file instanceof File) {
          clonedData.file = {
            name: clonedData.file.name,
            type: clonedData.file.type,
            size: clonedData.file.size
          };
        }
        
        // Supprimer les blobs, qui ne peuvent pas être sérialisés
        if (clonedData.blob || clonedData.data instanceof Blob) {
          delete clonedData.blob;
          if (clonedData.data instanceof Blob) {
            clonedData.data = {
              type: clonedData.data.type,
              size: clonedData.data.size
            };
          }
        }
        
        // Supprimer les URLs d'objets, qui ne sont pas utiles dans les logs
        if (clonedData.url && clonedData.url.startsWith('blob:')) {
          clonedData.url = '[Blob URL]';
        }
        if (clonedData.thumbnailUrl && clonedData.thumbnailUrl.startsWith('blob:')) {
          clonedData.thumbnailUrl = '[Blob URL]';
        }
      }
      
      return clonedData;
    } catch (error) {
      // En cas d'erreur, retourner une version simplifiée
      if (typeof data === 'object') {
        return { type: typeof data, toString: String(data) };
      }
      return String(data);
    }
  }
}

// Exporter une instance singleton
export const mediaLogger = new MediaLogger();
export default mediaLogger;