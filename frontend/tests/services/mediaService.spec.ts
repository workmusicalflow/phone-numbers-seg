import { describe, it, expect, vi, beforeEach } from "vitest";
import { mediaService, type UploadResult } from "../../src/services/mediaService";
import { mediaCache } from "../../src/services/mediaCache";

// Mock dependencies
vi.mock("../../src/services/mediaCache", () => {
  return {
    mediaCache: {
      findInCache: vi.fn(),
      addToCache: vi.fn()
    }
  };
});

vi.mock("../../src/services/api", () => {
  return {
    apolloClient: {
      mutate: vi.fn(),
      query: vi.fn()
    },
    api: {
      post: vi.fn()
    }
  };
});

// Mock localStorage
const localStorageMock = (() => {
  let store: Record<string, string> = {};
  return {
    getItem: vi.fn((key: string) => store[key] || null),
    setItem: vi.fn((key: string, value: string) => {
      store[key] = value.toString();
    }),
    removeItem: vi.fn((key: string) => {
      delete store[key];
    }),
    clear: vi.fn(() => {
      store = {};
    }),
    store
  };
})();

Object.defineProperty(window, 'localStorage', {
  value: localStorageMock
});

describe("mediaService", () => {
  beforeEach(() => {
    // Reset mocks
    vi.clearAllMocks();
    localStorageMock.clear();

    // Setup mock responses for api and apolloClient
    const { apolloClient, api } = require("../../src/services/api");
    
    // Mock apolloClient.mutate to return a successful response
    vi.mocked(apolloClient.mutate).mockResolvedValue({
      data: {
        uploadWhatsAppMedia: {
          success: true,
          mediaId: "mock-media-id-12345",
          error: null
        }
      }
    });
    
    // Mock api.post to return a successful response
    vi.mocked(api.post).mockResolvedValue({
      data: {
        success: true,
        mediaId: "mock-media-id-67890",
        error: null
      }
    });
  });

  it("getRecentMedia loads media from localStorage", () => {
    const mockMedias = [
      {
        id: "1",
        mediaId: "12345",
        type: "image",
        mimeType: "image/jpeg",
        filename: "test-image.jpg",
        size: 12345,
        timestamp: new Date().toISOString(),
        favorite: false
      }
    ];
    localStorageMock.setItem('media-library-recent', JSON.stringify(mockMedias));
    
    const result = mediaService.getRecentMedia();
    
    expect(result).toEqual(mockMedias);
    expect(localStorageMock.getItem).toHaveBeenCalledWith('media-library-recent');
  });

  it("getFavoriteMedia loads favorites from localStorage", () => {
    const mockFavorites = [
      {
        id: "2",
        mediaId: "67890",
        type: "document",
        mimeType: "application/pdf",
        filename: "test-document.pdf",
        size: 54321,
        timestamp: new Date().toISOString(),
        favorite: true
      }
    ];
    localStorageMock.setItem('media-library-favorites', JSON.stringify(mockFavorites));
    
    const result = mediaService.getFavoriteMedia();
    
    expect(result).toEqual(mockFavorites);
    expect(localStorageMock.getItem).toHaveBeenCalledWith('media-library-favorites');
  });

  it("addToRecentMedia adds a media to recent list", () => {
    // Setup empty recent media list
    localStorageMock.setItem('media-library-recent', JSON.stringify([]));
    
    // Create mock media object
    const media = {
      id: "1",
      mediaId: "12345",
      type: "image",
      mimeType: "image/jpeg",
      filename: "test-image.jpg",
      size: 12345,
      timestamp: new Date().toISOString(),
      favorite: false
    };
    
    // Add to recent media
    mediaService.addToRecentMedia(media);
    
    // Verify localStorage was updated
    expect(localStorageMock.setItem).toHaveBeenCalled();
    
    // Get updated recent media
    const savedMedia = JSON.parse(localStorageMock.store['media-library-recent']);
    expect(savedMedia).toEqual([media]);
  });

  it("addToFavorites adds a media to favorites list", () => {
    // Setup empty favorites list
    localStorageMock.setItem('media-library-favorites', JSON.stringify([]));
    
    // Create mock media object
    const media = {
      id: "1",
      mediaId: "12345",
      type: "image",
      mimeType: "image/jpeg",
      filename: "test-image.jpg",
      size: 12345,
      timestamp: new Date().toISOString(),
      favorite: false
    };
    
    // Add to favorites
    mediaService.addToFavorites(media);
    
    // Verify localStorage was updated
    expect(localStorageMock.setItem).toHaveBeenCalled();
    
    // Get updated favorites
    const savedFavorites = JSON.parse(localStorageMock.store['media-library-favorites']);
    expect(savedFavorites[0].favorite).toBe(true);
    expect(savedFavorites[0].mediaId).toBe(media.mediaId);
  });

  it("removeFromFavorites removes a media from favorites list", () => {
    // Setup favorites list with one item
    const initialFavorites = [
      {
        id: "1",
        mediaId: "12345",
        type: "image",
        mimeType: "image/jpeg",
        filename: "test-image.jpg",
        size: 12345,
        timestamp: new Date().toISOString(),
        favorite: true
      }
    ];
    localStorageMock.setItem('media-library-favorites', JSON.stringify(initialFavorites));
    
    // Remove from favorites
    mediaService.removeFromFavorites("12345");
    
    // Verify localStorage was updated
    expect(localStorageMock.setItem).toHaveBeenCalled();
    
    // Get updated favorites, should be empty
    const savedFavorites = JSON.parse(localStorageMock.store['media-library-favorites']);
    expect(savedFavorites).toEqual([]);
  });

  it("uploadFile with cached media returns success", async () => {
    // Mock a file
    const mockFile = new File(['test'], 'test.jpg', { type: 'image/jpeg' });
    
    // Mock cache to return a hit
    vi.mocked(mediaCache.findInCache).mockResolvedValue({
      fileHash: 'mock-hash',
      mediaId: 'cached-media-id',
      mimeType: 'image/jpeg',
      filename: 'test.jpg',
      size: 1024,
      timestamp: new Date().toISOString(),
      lastUsed: new Date().toISOString(),
      useCount: 5
    });
    
    // Mock createObjectURL
    const originalCreateObjectURL = URL.createObjectURL;
    URL.createObjectURL = vi.fn(() => 'mock-blob-url');
    
    // Mock crypto.randomUUID
    const originalRandomUUID = crypto.randomUUID;
    crypto.randomUUID = vi.fn(() => 'mock-uuid');
    
    // Call uploadFile
    const result = await mediaService.uploadFile(mockFile, {
      onProgress: vi.fn()
    });
    
    // Verify cache was checked
    expect(mediaCache.findInCache).toHaveBeenCalledWith(mockFile);
    
    // Verify apolloClient.mutate was not called (cached hit)
    const { apolloClient } = require("../../src/services/api");
    expect(apolloClient.mutate).not.toHaveBeenCalled();
    
    // Verify result
    expect(result.success).toBe(true);
    expect(result.mediaId).toBe('cached-media-id');
    
    // Cleanup
    URL.createObjectURL = originalCreateObjectURL;
    crypto.randomUUID = originalRandomUUID;
  });

  it("uploadFileWithFallback handles network errors and allows resuming", async () => {
    // Mock a file
    const mockFile = new File(['test'], 'test.jpg', { type: 'image/jpeg' });
    Object.defineProperty(mockFile, 'size', { value: 2 * 1024 * 1024 }); // 2MB
    
    // Mock no cache hit
    vi.mocked(mediaCache.findInCache).mockResolvedValue(null);
    
    // Mock apolloClient.mutate to throw a network error
    const { apolloClient } = require("../../src/services/api");
    vi.mocked(apolloClient.mutate).mockRejectedValue(new Error('Network error'));
    
    // Mock crypto.randomUUID
    const originalRandomUUID = crypto.randomUUID;
    crypto.randomUUID = vi.fn(() => 'mock-upload-id');
    
    // Call uploadFileWithFallback
    const result = await mediaService.uploadFileWithFallback(mockFile, {
      onProgress: vi.fn()
    });
    
    // Verify result indicates resumable upload
    expect(result.success).toBe(false);
    expect(result.error).toContain('Network error');
    expect(result.resumable).toBe(true);
    expect(result.uploadId).toBe('mock-upload-id');
    expect(result.uploadedBytes).toBe(0);
    expect(result.totalBytes).toBe(mockFile.size);
    
    // Cleanup
    crypto.randomUUID = originalRandomUUID;
  });

  it("uploadFileWithFallback can resume an interrupted upload", async () => {
    // Mock a file
    const mockFile = new File(['test'], 'test.jpg', { type: 'image/jpeg' });
    Object.defineProperty(mockFile, 'size', { value: 2 * 1024 * 1024 }); // 2MB
    
    // Mock no cache hit
    vi.mocked(mediaCache.findInCache).mockResolvedValue(null);
    
    // Mock successful API response
    const { api } = require("../../src/services/api");
    vi.mocked(api.post).mockResolvedValue({
      data: {
        success: true,
        mediaId: 'resumed-upload-media-id',
        error: null
      }
    });
    
    // Mock URL.createObjectURL
    const originalCreateObjectURL = URL.createObjectURL;
    URL.createObjectURL = vi.fn(() => 'mock-blob-url');
    
    // Mock crypto.randomUUID
    const originalRandomUUID = crypto.randomUUID;
    crypto.randomUUID = vi.fn(() => 'mock-uuid');
    
    // Call uploadFileWithFallback with resume parameters
    const result = await mediaService.uploadFileWithFallback(mockFile, {
      onProgress: vi.fn(),
      resumeUpload: true,
      uploadId: 'existing-upload-id',
      uploadedBytes: 1024 * 1024 // 1MB already uploaded
    });
    
    // Verify FormData was prepared with resume info
    expect(api.post).toHaveBeenCalled();
    
    // Verify result
    expect(result.success).toBe(true);
    expect(result.mediaId).toBe('resumed-upload-media-id');
    
    // Verify caching
    expect(mediaCache.addToCache).toHaveBeenCalledWith(mockFile, 'resumed-upload-media-id');
    
    // Cleanup
    URL.createObjectURL = originalCreateObjectURL;
    crypto.randomUUID = originalRandomUUID;
  });
});