import { Media } from './mediaService';

/**
 * Interface pour une entrée de cache de média
 */
export interface MediaCacheEntry {
  fileHash: string;
  mediaId: string;
  mimeType: string;
  filename: string;
  size: number;
  timestamp: string;
  lastUsed: string;
  useCount: number;
}

/**
 * Interface pour une clé de cache
 */
interface MediaCacheKey {
  hash: string;
  mimeType: string;
}

/**
 * Service de mise en cache des médias uploadés
 */
class MediaCacheService {
  private readonly STORAGE_KEY = 'media-upload-cache';
  private readonly MAX_CACHE_ENTRIES = 100;
  private readonly MAX_CACHE_AGE_DAYS = 30; // Durée de vie des entrées en jours
  
  /**
   * Récupère toutes les entrées du cache
   */
  private getAllCacheEntries(): Record<string, MediaCacheEntry> {
    try {
      const cacheData = localStorage.getItem(this.STORAGE_KEY);
      return cacheData ? JSON.parse(cacheData) : {};
    } catch (error) {
      console.error('Error reading media cache:', error);
      return {};
    }
  }
  
  /**
   * Sauvegarde toutes les entrées du cache
   */
  private saveAllCacheEntries(entries: Record<string, MediaCacheEntry>): void {
    try {
      localStorage.setItem(this.STORAGE_KEY, JSON.stringify(entries));
    } catch (error) {
      console.error('Error saving media cache:', error);
    }
  }
  
  /**
   * Génère une clé unique pour un fichier
   */
  private async generateCacheKey(file: File): Promise<MediaCacheKey> {
    // Générer un hash du contenu du fichier pour l'identifier de manière unique
    const arrayBuffer = await file.arrayBuffer();
    const hashBuffer = await crypto.subtle.digest('SHA-256', arrayBuffer);
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    
    return {
      hash: hashHex,
      mimeType: file.type
    };
  }
  
  /**
   * Convertit une clé de cache en chaîne
   */
  private stringifyCacheKey(key: MediaCacheKey): string {
    return `${key.hash}:${key.mimeType}`;
  }
  
  /**
   * Cherche un média dans le cache
   * @returns L'entrée de cache si elle existe, null sinon
   */
  async findInCache(file: File): Promise<MediaCacheEntry | null> {
    try {
      const cacheKey = await this.generateCacheKey(file);
      const entries = this.getAllCacheEntries();
      const keyString = this.stringifyCacheKey(cacheKey);
      
      const entry = entries[keyString];
      if (!entry) {
        return null;
      }
      
      // Vérifier si l'entrée n'est pas expirée
      const entryDate = new Date(entry.timestamp);
      const now = new Date();
      const ageInDays = (now.getTime() - entryDate.getTime()) / (1000 * 60 * 60 * 24);
      
      if (ageInDays > this.MAX_CACHE_AGE_DAYS) {
        // Entrée expirée, la supprimer du cache
        delete entries[keyString];
        this.saveAllCacheEntries(entries);
        return null;
      }
      
      // Mettre à jour la date de dernière utilisation et le compteur
      entry.lastUsed = new Date().toISOString();
      entry.useCount += 1;
      entries[keyString] = entry;
      this.saveAllCacheEntries(entries);
      
      return entry;
    } catch (error) {
      console.error('Error finding media in cache:', error);
      return null;
    }
  }
  
  /**
   * Ajoute un média au cache
   */
  async addToCache(file: File, mediaId: string): Promise<void> {
    try {
      const cacheKey = await this.generateCacheKey(file);
      const entries = this.getAllCacheEntries();
      const keyString = this.stringifyCacheKey(cacheKey);
      
      const now = new Date().toISOString();
      
      // Créer la nouvelle entrée
      const newEntry: MediaCacheEntry = {
        fileHash: cacheKey.hash,
        mediaId,
        mimeType: file.type,
        filename: file.name,
        size: file.size,
        timestamp: now,
        lastUsed: now,
        useCount: 1
      };
      
      // Ajouter l'entrée au cache
      entries[keyString] = newEntry;
      
      // Si le cache dépasse la taille maximale, supprimer les entrées les plus anciennes
      this.pruneCache(entries);
      
      // Sauvegarder le cache
      this.saveAllCacheEntries(entries);
    } catch (error) {
      console.error('Error adding media to cache:', error);
    }
  }
  
  /**
   * Supprime les entrées les plus anciennes si le cache dépasse la taille maximale
   */
  private pruneCache(entries: Record<string, MediaCacheEntry>): void {
    const entriesArray = Object.entries(entries);
    
    if (entriesArray.length <= this.MAX_CACHE_ENTRIES) {
      return;
    }
    
    // Trier les entrées par date de dernière utilisation (plus anciennes d'abord)
    entriesArray.sort((a, b) => {
      return new Date(a[1].lastUsed).getTime() - new Date(b[1].lastUsed).getTime();
    });
    
    // Supprimer les entrées les plus anciennes
    const toRemove = entriesArray.length - this.MAX_CACHE_ENTRIES;
    
    for (let i = 0; i < toRemove; i++) {
      delete entries[entriesArray[i][0]];
    }
  }
  
  /**
   * Nettoie le cache en supprimant les entrées expirées
   */
  async cleanCache(): Promise<void> {
    try {
      const entries = this.getAllCacheEntries();
      const now = new Date();
      let modified = false;
      
      for (const [key, entry] of Object.entries(entries)) {
        const entryDate = new Date(entry.timestamp);
        const ageInDays = (now.getTime() - entryDate.getTime()) / (1000 * 60 * 60 * 24);
        
        if (ageInDays > this.MAX_CACHE_AGE_DAYS) {
          delete entries[key];
          modified = true;
        }
      }
      
      if (modified) {
        this.saveAllCacheEntries(entries);
      }
    } catch (error) {
      console.error('Error cleaning media cache:', error);
    }
  }
  
  /**
   * Récupère les statistiques du cache
   */
  getCacheStats(): {
    totalEntries: number;
    totalSize: number;
    oldestEntry: string | null;
    newestEntry: string | null;
  } {
    try {
      const entries = this.getAllCacheEntries();
      const entriesArray = Object.values(entries);
      
      if (entriesArray.length === 0) {
        return {
          totalEntries: 0,
          totalSize: 0,
          oldestEntry: null,
          newestEntry: null
        };
      }
      
      // Calculer les statistiques
      const totalSize = entriesArray.reduce((sum, entry) => sum + entry.size, 0);
      
      // Trier par date de création
      const sortedByDate = [...entriesArray].sort(
        (a, b) => new Date(a.timestamp).getTime() - new Date(b.timestamp).getTime()
      );
      
      return {
        totalEntries: entriesArray.length,
        totalSize,
        oldestEntry: sortedByDate[0]?.timestamp || null,
        newestEntry: sortedByDate[sortedByDate.length - 1]?.timestamp || null
      };
    } catch (error) {
      console.error('Error getting cache stats:', error);
      return {
        totalEntries: 0,
        totalSize: 0,
        oldestEntry: null,
        newestEntry: null
      };
    }
  }
  
  /**
   * Vide complètement le cache
   */
  clearCache(): void {
    try {
      localStorage.removeItem(this.STORAGE_KEY);
    } catch (error) {
      console.error('Error clearing media cache:', error);
    }
  }
}

export const mediaCache = new MediaCacheService();
export default mediaCache;