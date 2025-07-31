import { apolloClient, gql } from './api';
import { mediaCache } from './mediaCache';
import { mediaLogger } from './mediaLogger';

/**
 * Interface pour les médias
 */
export interface Media {
  id: string;
  mediaId: string;
  type: 'image' | 'video' | 'audio' | 'document' | 'sticker';
  mimeType: string;
  filename: string;
  size: number;
  caption?: string;
  url?: string;
  thumbnailUrl?: string;
  timestamp: string;
  favorite: boolean;
}

/**
 * Interface pour le résultat d'upload
 */
export interface UploadResult {
  success: boolean;
  mediaId?: string;
  url?: string;
  error?: string;
  resumable?: boolean;
  uploadId?: string;
  uploadedBytes?: number;
  totalBytes?: number;
}

/**
 * Service de gestion des médias
 */
class MediaService {
  private readonly STORAGE_KEY_RECENT = 'media-library-recent';
  private readonly STORAGE_KEY_FAVORITES = 'media-library-favorites';
  private readonly MAX_RECENT_ITEMS = 30;
  
  /**
   * Charge les médias récents depuis le stockage local
   */
  getRecentMedia(): Media[] {
    try {
      const storedMedia = localStorage.getItem(this.STORAGE_KEY_RECENT);
      const result = storedMedia ? JSON.parse(storedMedia) : [];
      mediaLogger.debug('Loaded recent media', { count: result.length }, 'getRecentMedia');
      return result;
    } catch (error) {
      mediaLogger.error('Error loading recent media', error, 'getRecentMedia');
      return [];
    }
  }
  
  /**
   * Charge les médias favoris depuis le stockage local
   */
  getFavoriteMedia(): Media[] {
    try {
      const storedFavorites = localStorage.getItem(this.STORAGE_KEY_FAVORITES);
      const result = storedFavorites ? JSON.parse(storedFavorites) : [];
      mediaLogger.debug('Loaded favorite media', { count: result.length }, 'getFavoriteMedia');
      return result;
    } catch (error) {
      mediaLogger.error('Error loading favorite media', error, 'getFavoriteMedia');
      return [];
    }
  }
  
  /**
   * Ajoute un média aux récents
   */
  addToRecentMedia(media: Media): void {
    try {
      let recentMedia = this.getRecentMedia();
      
      // Vérifier si le média existe déjà
      const existingIndex = recentMedia.findIndex(m => m.mediaId === media.mediaId);
      
      if (existingIndex !== -1) {
        // Mettre à jour le média existant
        recentMedia[existingIndex] = { ...media, timestamp: new Date().toISOString() };
        mediaLogger.debug('Updated existing media in recent list', { mediaId: media.mediaId }, 'addToRecentMedia');
      } else {
        // Ajouter le nouveau média et limiter le nombre total
        recentMedia = [media, ...recentMedia.slice(0, this.MAX_RECENT_ITEMS - 1)];
        mediaLogger.info('Added new media to recent list', { mediaId: media.mediaId, type: media.type }, 'addToRecentMedia');
      }
      
      localStorage.setItem(this.STORAGE_KEY_RECENT, JSON.stringify(recentMedia));
    } catch (error) {
      mediaLogger.error('Error adding to recent media', error, 'addToRecentMedia');
    }
  }
  
  /**
   * Ajoute un média aux favoris
   */
  addToFavorites(media: Media): void {
    try {
      let favoriteMedia = this.getFavoriteMedia();
      
      // Vérifier si le média existe déjà
      const existingIndex = favoriteMedia.findIndex(m => m.mediaId === media.mediaId);
      
      if (existingIndex === -1) {
        // Ajouter aux favoris
        favoriteMedia = [{ ...media, favorite: true }, ...favoriteMedia];
        localStorage.setItem(this.STORAGE_KEY_FAVORITES, JSON.stringify(favoriteMedia));
      }
    } catch (error) {
      console.error('Error adding to favorites:', error);
    }
  }
  
  /**
   * Retire un média des favoris
   */
  removeFromFavorites(mediaId: string): void {
    try {
      const favoriteMedia = this.getFavoriteMedia().filter(m => m.mediaId !== mediaId);
      localStorage.setItem(this.STORAGE_KEY_FAVORITES, JSON.stringify(favoriteMedia));
    } catch (error) {
      console.error('Error removing from favorites:', error);
    }
  }
  
  /**
   * Supprime un média des récents et des favoris
   */
  removeMedia(mediaId: string): void {
    try {
      // Supprimer des récents
      const recentMedia = this.getRecentMedia().filter(m => m.mediaId !== mediaId);
      localStorage.setItem(this.STORAGE_KEY_RECENT, JSON.stringify(recentMedia));
      
      // Supprimer des favoris
      this.removeFromFavorites(mediaId);
    } catch (error) {
      console.error('Error removing media:', error);
    }
  }
  
  /**
   * Upload d'un fichier avec optimisation optionnelle pour les images
   */
  async uploadFile(
    file: File,
    options: {
      optimizeImage?: boolean;
      imageQuality?: number;
      maxWidth?: number;
      maxHeight?: number;
      onProgress?: (progress: number) => void;
      skipCache?: boolean;
    } = {}
  ): Promise<UploadResult> {
    const correlationId = mediaLogger.getCorrelationId() || mediaLogger.createCorrelationId();
    mediaLogger.info('Starting file upload', { 
      fileName: file.name, 
      fileSize: file.size, 
      fileType: file.type,
      options: { ...options, onProgress: options.onProgress ? '[Function]' : undefined }
    }, 'uploadFile');
    try {
      // Vérifier d'abord dans le cache si on ne doit pas l'ignorer
      if (!options.skipCache) {
        mediaLogger.debug('Checking cache for file', { fileName: file.name }, 'uploadFile');
        const cachedMedia = await mediaCache.findInCache(file);
        if (cachedMedia) {
          mediaLogger.info('Media found in cache', { cachedMedia }, 'uploadFile');
          
          // Simuler un progrès d'upload pour l'interface utilisateur
          if (options.onProgress) {
            options.onProgress(100);
          }
          
          // Créer l'entrée pour le stockage local avec les données du cache
          const mediaEntry: Media = {
            id: crypto.randomUUID(),
            mediaId: cachedMedia.mediaId,
            type: this.getMediaTypeFromFile(file),
            mimeType: cachedMedia.mimeType,
            filename: cachedMedia.filename,
            size: cachedMedia.size,
            timestamp: new Date().toISOString(),
            favorite: false
          };
          
          // Si c'est une image, tenter de créer une URL pour la prévisualisation
          if (file.type.startsWith('image/')) {
            mediaEntry.url = URL.createObjectURL(file);
            mediaEntry.thumbnailUrl = mediaEntry.url;
          }
          
          // Ajouter aux médias récents
          this.addToRecentMedia(mediaEntry);
          
          return {
            success: true,
            mediaId: cachedMedia.mediaId,
            url: mediaEntry.url
          };
        }
      }
      
      let fileToUpload = file;
      
      // Optimisation d'image si demandée et si c'est une image
      if (options.optimizeImage && file.type.startsWith('image/')) {
        fileToUpload = await this.optimizeImage(file, {
          quality: options.imageQuality || 80,
          maxWidth: options.maxWidth || 1024,
          maxHeight: options.maxHeight || 1024
        });
      }
      
      // Préparation du FormData
      const formData = new FormData();
      formData.append('file', fileToUpload);
      
      // Upload vers le serveur
      const { data } = await apolloClient.mutate({
        mutation: gql`
          mutation UploadWhatsAppMedia($file: Upload!) {
            uploadWhatsAppMedia(file: $file) {
              success
              mediaId
              error
            }
          }
        `,
        variables: { file: fileToUpload },
        context: {
          fetchOptions: {
            onUploadProgress: (progressEvent: any) => {
              if (progressEvent.total && options.onProgress) {
                const progress = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                options.onProgress(progress);
              }
            }
          }
        }
      });
      
      if (data?.uploadWhatsAppMedia?.success) {
        const mediaId = data.uploadWhatsAppMedia.mediaId;
        
        // Créer l'entrée pour le stockage local
        const mediaEntry: Media = {
          id: crypto.randomUUID(),
          mediaId: mediaId,
          type: this.getMediaTypeFromFile(file),
          mimeType: file.type,
          filename: file.name,
          size: file.size,
          url: URL.createObjectURL(fileToUpload),
          thumbnailUrl: file.type.startsWith('image/') ? URL.createObjectURL(fileToUpload) : undefined,
          timestamp: new Date().toISOString(),
          favorite: false
        };
        
        // Ajouter aux médias récents
        this.addToRecentMedia(mediaEntry);
        
        // Ajouter au cache pour les futurs uploads
        await mediaCache.addToCache(file, mediaId);
        
        return {
          success: true,
          mediaId: mediaId,
          url: mediaEntry.url
        };
      } else {
        return {
          success: false,
          error: data?.uploadWhatsAppMedia?.error || 'Erreur inconnue lors de l\'upload'
        };
      }
    } catch (error: any) {
      mediaLogger.error('Upload error', { 
        errorMessage: error.message,
        errorName: error.name,
        errorCode: error.code,
        stack: error.stack
      }, 'uploadFile');
      
      // Générer un rapport de diagnostic pour les erreurs
      const diagnostics = mediaLogger.generateDiagnostics();
      mediaLogger.error('Upload diagnostics', diagnostics, 'uploadFile');
      
      return {
        success: false,
        error: error.message || 'Erreur lors de l\'upload'
      };
    }
  }
  
  /**
   * Upload de média avec fallback à l'endpoint API PHP si l'upload GraphQL échoue
   */
  /**
   * Upload de média avec fallback à l'endpoint API PHP si l'upload GraphQL échoue
   * Supporte également la reprise d'upload en cas d'échec temporaire
   */
  async uploadFileWithFallback(
    file: File,
    options: {
      optimizeImage?: boolean;
      imageQuality?: number;
      maxWidth?: number;
      maxHeight?: number;
      onProgress?: (progress: number) => void;
      skipCache?: boolean;
      resumeUpload?: boolean;
      uploadId?: string;
      uploadedBytes?: number;
    } = {}
  ): Promise<UploadResult> {
    // Créer un ID de corrélation pour suivre cette opération d'upload
    const correlationId = mediaLogger.createCorrelationId();
    mediaLogger.info('Starting file upload with fallback', { 
      fileName: file.name, 
      fileSize: file.size, 
      fileType: file.type,
      options: { ...options, onProgress: options.onProgress ? '[Function]' : undefined }
    }, 'uploadFileWithFallback');
    try {
      // Vérifier d'abord dans le cache si on ne doit pas l'ignorer
      if (!options.skipCache) {
        mediaLogger.debug('Checking cache for file', { fileName: file.name }, 'uploadFileWithFallback');
        const cachedMedia = await mediaCache.findInCache(file);
        if (cachedMedia) {
          mediaLogger.info('Media found in cache', { cachedMedia }, 'uploadFileWithFallback');
          
          // Simuler un progrès d'upload pour l'interface utilisateur
          if (options.onProgress) {
            options.onProgress(100);
            mediaLogger.debug('Triggered 100% progress for cached media', null, 'uploadFileWithFallback');
          }
          
          // Créer l'entrée pour le stockage local avec les données du cache
          const mediaEntry: Media = {
            id: crypto.randomUUID(),
            mediaId: cachedMedia.mediaId,
            type: this.getMediaTypeFromFile(file),
            mimeType: cachedMedia.mimeType,
            filename: cachedMedia.filename,
            size: cachedMedia.size,
            timestamp: new Date().toISOString(),
            favorite: false
          };
          
          // Si c'est une image, tenter de créer une URL pour la prévisualisation
          if (file.type.startsWith('image/')) {
            mediaEntry.url = URL.createObjectURL(file);
            mediaEntry.thumbnailUrl = mediaEntry.url;
          }
          
          // Ajouter aux médias récents
          this.addToRecentMedia(mediaEntry);
          
          const result = {
            success: true,
            mediaId: cachedMedia.mediaId,
            url: mediaEntry.url
          };
          mediaLogger.info('Returned cached media successfully', { mediaId: cachedMedia.mediaId }, 'uploadFileWithFallback');
          return result;
        }
      }
      
      // On active l'option skipCache pour éviter de vérifier le cache à nouveau dans uploadFile
      const modifiedOptions = { ...options, skipCache: true };
      
      mediaLogger.debug('Cache miss or skip requested, attempting GraphQL upload', null, 'uploadFileWithFallback');
      // Essayer d'abord l'upload GraphQL
      const result = await this.uploadFile(file, modifiedOptions);
      
      if (result.success) {
        mediaLogger.info('GraphQL upload successful', { mediaId: result.mediaId }, 'uploadFileWithFallback');
        return result;
      }
      
      mediaLogger.warn('GraphQL upload failed', { error: result.error }, 'uploadFileWithFallback');
      
      // Si l'échec est dû à une erreur réseau et que le fichier est suffisamment grand, on peut proposer de reprendre l'upload
      if (file.size > 1024 * 1024 && (result.error?.includes('network') || result.error?.includes('timeout') || result.error?.includes('connection'))) {
        const resumableResult = {
          success: false,
          error: `Erreur réseau: ${result.error}`,
          resumable: true,
          uploadId: crypto.randomUUID(),
          uploadedBytes: 0,
          totalBytes: file.size
        };
        
        mediaLogger.warn('Network error during upload, marking as resumable', { 
          error: result.error,
          uploadId: resumableResult.uploadId 
        }, 'uploadFileWithFallback');
        
        return resumableResult;
      }
      
      // Fallback à l'API PHP directe si l'upload GraphQL échoue
      mediaLogger.info('GraphQL upload failed, falling back to PHP endpoint', { error: result.error }, 'uploadFileWithFallback');
      
      let fileToUpload = file;
      
      // Optimisation d'image si demandée et si c'est une image
      if (options.optimizeImage && file.type.startsWith('image/')) {
        fileToUpload = await this.optimizeImage(file, {
          quality: options.imageQuality || 80,
          maxWidth: options.maxWidth || 1024,
          maxHeight: options.maxHeight || 1024
        });
      }
      
      // Préparation du FormData
      const formData = new FormData();
      formData.append('file', fileToUpload);
      
      // Import dynamique d'axios pour éviter les dépendances circulaires
      const { api } = await import('./api');
      
      // Si on essaie de reprendre un upload précédent
      if (options.resumeUpload && options.uploadId && options.uploadedBytes) {
        formData.append('uploadId', options.uploadId);
        formData.append('uploadedBytes', options.uploadedBytes.toString());
        formData.append('totalBytes', file.size.toString());
        mediaLogger.info('Attempting to resume upload', { 
          uploadId: options.uploadId,
          uploadedBytes: options.uploadedBytes,
          totalBytes: file.size,
          progress: ((options.uploadedBytes / file.size) * 100).toFixed(2) + '%'
        }, 'uploadFileWithFallback');
      }
      
      // Upload vers l'endpoint PHP
      const response = await api.post('/whatsapp/upload.php', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
        onUploadProgress: (progressEvent: any) => {
          if (progressEvent.total && options.onProgress) {
            const progress = Math.round((progressEvent.loaded * 100) / progressEvent.total);
            options.onProgress(progress);
          }
        }
      });
      
      if (response.data.success && response.data.mediaId) {
        const mediaId = response.data.mediaId;
        
        mediaLogger.info('PHP fallback upload successful', { mediaId }, 'uploadFileWithFallback');
        
        // Créer l'entrée pour le stockage local
        const mediaEntry: Media = {
          id: crypto.randomUUID(),
          mediaId: mediaId,
          type: this.getMediaTypeFromFile(file),
          mimeType: file.type,
          filename: file.name,
          size: file.size,
          url: URL.createObjectURL(fileToUpload),
          thumbnailUrl: file.type.startsWith('image/') ? URL.createObjectURL(fileToUpload) : undefined,
          timestamp: new Date().toISOString(),
          favorite: false
        };
        
        // Ajouter aux médias récents
        this.addToRecentMedia(mediaEntry);
        mediaLogger.debug('Added to recent media after successful upload', { mediaId }, 'uploadFileWithFallback');
        
        // Ajouter au cache pour les futurs uploads
        await mediaCache.addToCache(file, mediaId);
        mediaLogger.debug('Added to cache after successful upload', { mediaId }, 'uploadFileWithFallback');
        
        return {
          success: true,
          mediaId: mediaId,
          url: mediaEntry.url
        };
      } else {
        const errorMsg = response.data.error || 'Erreur inconnue lors de l\'upload';
        mediaLogger.error('PHP fallback upload failed', { error: errorMsg }, 'uploadFileWithFallback');
        return {
          success: false,
          error: errorMsg
        };
      }
    } catch (error: any) {
      mediaLogger.error('Upload with fallback error', { 
        errorMessage: error.message,
        errorName: error.name,
        errorCode: error.code,
        stack: error.stack,
        onLine: navigator.onLine
      }, 'uploadFileWithFallback');
      
      // Si l'erreur est liée au réseau et que le fichier est suffisamment grand, on peut proposer de reprendre l'upload
      if (file.size > 1024 * 1024 && (
        error.message?.includes('network') ||
        error.message?.includes('timeout') ||
        error.message?.includes('connection') ||
        error.code === 'ECONNABORTED' ||
        error.name === 'NetworkError' ||
        !navigator.onLine
      )) {
        const uploadId = options.uploadId || crypto.randomUUID();
        const uploadedBytes = options.uploadedBytes || 0;
        
        mediaLogger.warn('Network error during upload, marking as resumable', { 
          error: error.message,
          uploadId,
          uploadedBytes
        }, 'uploadFileWithFallback');
        
        return {
          success: false,
          error: `Erreur réseau: ${error.message}`,
          resumable: true,
          uploadId,
          uploadedBytes,
          totalBytes: file.size
        };
      }
      
      // Générer un rapport de diagnostic pour les erreurs non réseau
      const diagnostics = mediaLogger.generateDiagnostics();
      mediaLogger.error('Final upload failure', { 
        error: error.message, 
        diagnostics 
      }, 'uploadFileWithFallback');
      
      return {
        success: false,
        error: error.message || 'Erreur lors de l\'upload'
      };
    }
  }
  
  /**
   * Optimise une image avant upload
   */
  private async optimizeImage(
    file: File,
    options: {
      quality?: number;
      maxWidth?: number;
      maxHeight?: number;
    }
  ): Promise<File> {
    mediaLogger.debug('Starting image optimization', { 
      fileName: file.name, 
      fileSize: file.size,
      options
    }, 'optimizeImage');
    return new Promise((resolve, reject) => {
      try {
        // Créer une image à partir du fichier
        const img = new Image();
        const imgUrl = URL.createObjectURL(file);
        
        img.onload = () => {
          // Libérer l'URL
          URL.revokeObjectURL(imgUrl);
          
          // Calculer les dimensions pour le redimensionnement
          let width = img.width;
          let height = img.height;
          
          if (options.maxWidth && options.maxHeight) {
            if (width > options.maxWidth || height > options.maxHeight) {
              const ratio = Math.min(options.maxWidth / width, options.maxHeight / height);
              width = Math.floor(width * ratio);
              height = Math.floor(height * ratio);
            }
          }
          
          // Dessiner l'image sur un canvas pour la compression/redimensionnement
          const canvas = document.createElement('canvas');
          canvas.width = width;
          canvas.height = height;
          const ctx = canvas.getContext('2d');
          
          if (!ctx) {
            reject(new Error('Impossible de créer le contexte du canvas'));
            return;
          }
          
          ctx.drawImage(img, 0, 0, width, height);
          
          // Convertir en blob avec la qualité souhaitée
          const mimeType = file.type;
          const quality = (options.quality || 80) / 100;
          
          canvas.toBlob(
            (blob) => {
              if (!blob) {
                reject(new Error('Échec de conversion du canvas en blob'));
                return;
              }
              
              // Créer un nouveau fichier à partir du blob
              const optimizedFile = new File([blob], file.name, {
                type: mimeType,
                lastModified: new Date().getTime()
              });
              
              const optimizationStats = {
                original: { width: img.width, height: img.height, size: file.size },
                optimized: { width, height, size: optimizedFile.size },
                reduction: Math.round((1 - optimizedFile.size / file.size) * 100) + '%'
              };
              
              mediaLogger.info('Image optimization complete', optimizationStats, 'optimizeImage');
              
              resolve(optimizedFile);
            },
            mimeType,
            quality
          );
        };
        
        img.onerror = () => {
          URL.revokeObjectURL(imgUrl);
          const errorMsg = 'Erreur lors du chargement de l\'image';
          mediaLogger.error('Image load error during optimization', { fileName: file.name }, 'optimizeImage');
          reject(new Error(errorMsg));
        };
        
        img.src = imgUrl;
      } catch (error) {
        reject(error);
      }
    });
  }
  
  /**
   * Détermine le type de média à partir du fichier
   */
  private getMediaTypeFromFile(file: File): Media['type'] {
    const mimeType = file.type;
    
    if (mimeType.startsWith('image/')) {
      return mimeType === 'image/webp' ? 'sticker' : 'image';
    } else if (mimeType.startsWith('video/')) {
      return 'video';
    } else if (mimeType.startsWith('audio/')) {
      return 'audio';
    } else {
      return 'document';
    }
  }
  
  /**
   * Récupère les infos de média depuis l'API
   */
  async getMediaInfo(mediaId: string): Promise<any> {
    try {
      mediaLogger.debug('Fetching media info', { mediaId }, 'getMediaInfo');
      const { data } = await apolloClient.query({
        query: gql`
          query GetWhatsAppMediaInfo($mediaId: String!) {
            getWhatsAppMediaInfo(mediaId: $mediaId) {
              id
              url
              mimeType
              sha256
              fileSize
            }
          }
        `,
        variables: { mediaId },
        fetchPolicy: 'network-only'
      });
      
      const mediaInfo = data?.getWhatsAppMediaInfo;
      if (mediaInfo) {
        mediaLogger.debug('Successfully retrieved media info', { mediaId, info: mediaInfo }, 'getMediaInfo');
      } else {
        mediaLogger.warn('Media info not found', { mediaId }, 'getMediaInfo');
      }
      return mediaInfo;
    } catch (error) {
      mediaLogger.error('Error getting media info', { mediaId, error }, 'getMediaInfo');
      return null;
    }
  }
  
  /**
   * Récupère l'URL d'un média
   */
  async getMediaUrl(mediaId: string): Promise<string | null> {
    try {
      mediaLogger.debug('Fetching media URL', { mediaId }, 'getMediaUrl');
      const { data } = await apolloClient.query({
        query: gql`
          query DownloadWhatsAppMedia($mediaId: String!) {
            downloadWhatsAppMedia(mediaId: $mediaId) {
              url
            }
          }
        `,
        variables: { mediaId },
        fetchPolicy: 'network-only'
      });
      
      const url = data?.downloadWhatsAppMedia?.url || null;
      if (url) {
        mediaLogger.debug('Successfully retrieved media URL', { mediaId }, 'getMediaUrl');
      } else {
        mediaLogger.warn('Media URL not found', { mediaId }, 'getMediaUrl');
      }
      return url;
    } catch (error) {
      mediaLogger.error('Error getting media URL', { mediaId, error }, 'getMediaUrl');
      return null;
    }
  }
}

export const mediaService = new MediaService();
export default mediaService;