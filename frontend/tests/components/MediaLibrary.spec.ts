import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount, flushPromises } from "@vue/test-utils";
import MediaLibrary from "../../src/components/media/MediaLibrary.vue";

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

describe("MediaLibrary", () => {
  beforeEach(() => {
    // Reset mocks before each test
    vi.clearAllMocks();
    localStorageMock.clear();

    // Mock recent media
    localStorageMock.setItem('media-library-recent', JSON.stringify([
      {
        id: "1",
        mediaId: "12345",
        type: "image",
        mimeType: "image/jpeg",
        filename: "test-image.jpg",
        size: 12345,
        timestamp: new Date().toISOString(),
        favorite: false,
        url: "blob:test-url",
        thumbnailUrl: "blob:test-thumbnail"
      },
      {
        id: "2",
        mediaId: "67890",
        type: "document",
        mimeType: "application/pdf",
        filename: "test-document.pdf",
        size: 54321,
        timestamp: new Date().toISOString(),
        favorite: true,
        url: "blob:test-doc-url"
      }
    ]));

    // Mock favorite media
    localStorageMock.setItem('media-library-favorites', JSON.stringify([
      {
        id: "2",
        mediaId: "67890",
        type: "document",
        mimeType: "application/pdf",
        filename: "test-document.pdf",
        size: 54321,
        timestamp: new Date().toISOString(),
        favorite: true,
        url: "blob:test-doc-url"
      }
    ]));
  });

  it("renders properly with tabs", async () => {
    const wrapper = mount(MediaLibrary, {
      global: {
        stubs: {
          QCard: true,
          QCardSection: true,
          QSeparator: true,
          QTabs: true,
          QTab: true,
          QTabPanels: true,
          QTabPanel: true,
          QBtnToggle: true,
          QFile: true,
          QIcon: true,
          QInput: true,
          QBanner: true,
          QBtn: true
        }
      }
    });

    // Check if component renders
    expect(wrapper.exists()).toBe(true);
    
    // Check if the tabs are rendered
    expect(wrapper.findAll('[name="upload"]').length).toBeGreaterThan(0);
    expect(wrapper.findAll('[name="recent"]').length).toBeGreaterThan(0);
    expect(wrapper.findAll('[name="favorites"]').length).toBeGreaterThan(0);
    
    // Default tab should be upload
    expect(wrapper.vm.activeTab).toBe('upload');
  });

  it("loads recent media from localStorage", async () => {
    const wrapper = mount(MediaLibrary, {
      global: {
        stubs: {
          QCard: true,
          QCardSection: true,
          QSeparator: true,
          QTabs: true,
          QTab: true,
          QTabPanels: true,
          QTabPanel: true,
          QBtnToggle: true,
          QFile: true,
          QIcon: true,
          QInput: true,
          QBanner: true,
          QBtn: true,
          QImg: true
        }
      }
    });

    // Simulate mounting completed
    await flushPromises();
    
    // Verify localStorage.getItem was called
    expect(localStorageMock.getItem).toHaveBeenCalledWith('media-library-recent');
    expect(localStorageMock.getItem).toHaveBeenCalledWith('media-library-favorites');
    
    // Verify the recent media was loaded correctly
    expect(wrapper.vm.recentMedia.length).toBe(2);
    expect(wrapper.vm.recentMedia[0].mediaId).toBe('12345');
    expect(wrapper.vm.recentMedia[1].mediaId).toBe('67890');
    
    // Verify the favorite media was loaded correctly
    expect(wrapper.vm.favoriteMedia.length).toBe(1);
    expect(wrapper.vm.favoriteMedia[0].mediaId).toBe('67890');
  });

  it("formats file size correctly", async () => {
    const wrapper = mount(MediaLibrary, {
      global: {
        stubs: {
          QCard: true,
          QCardSection: true,
          QSeparator: true,
          QTabs: true,
          QTab: true,
          QTabPanels: true,
          QTabPanel: true,
          QBtnToggle: true,
          QFile: true,
          QIcon: true,
          QInput: true,
          QBanner: true,
          QBtn: true
        }
      }
    });

    // Test different file sizes
    expect(wrapper.vm.formatFileSize(0)).toBe('0 B');
    expect(wrapper.vm.formatFileSize(500)).toBe('500 B');
    expect(wrapper.vm.formatFileSize(1024)).toBe('1 KB');
    expect(wrapper.vm.formatFileSize(1500)).toBe('1.46 KB');
    expect(wrapper.vm.formatFileSize(1024 * 1024)).toBe('1 MB');
    expect(wrapper.vm.formatFileSize(1024 * 1024 * 1024)).toBe('1 GB');
  });

  it("changes file type when mediaType changes", async () => {
    const wrapper = mount(MediaLibrary, {
      global: {
        stubs: {
          QCard: true,
          QCardSection: true,
          QSeparator: true,
          QTabs: true,
          QTab: true,
          QTabPanels: true,
          QTabPanel: true,
          QBtnToggle: true,
          QFile: true,
          QIcon: true,
          QInput: true,
          QBanner: true,
          QBtn: true
        }
      }
    });

    // Create a mock file
    const mockFile = new File([''], 'test.jpg', { type: 'image/jpeg' });
    wrapper.vm.mediaFile = mockFile;
    
    // Change mediaType
    await wrapper.setData({ mediaType: 'document' });
    
    // File should be reset
    expect(wrapper.vm.mediaFile).toBe(null);
    expect(wrapper.vm.previewUrl).toBe('');
  });

  it("reacts correctly to file selection", async () => {
    const wrapper = mount(MediaLibrary, {
      global: {
        stubs: {
          QCard: true,
          QCardSection: true,
          QSeparator: true,
          QTabs: true,
          QTab: true,
          QTabPanels: true,
          QTabPanel: true,
          QBtnToggle: true,
          QFile: true,
          QIcon: true,
          QInput: true,
          QBanner: true,
          QBtn: true
        }
      }
    });

    // Mock FileReader
    const originalCreateObjectURL = URL.createObjectURL;
    URL.createObjectURL = vi.fn(() => 'mock-blob-url');
    
    // Create a mock file that's too large
    const largeFile = new File([''], 'large.jpg', { type: 'image/jpeg' });
    Object.defineProperty(largeFile, 'size', { value: 10 * 1024 * 1024 }); // 10MB
    
    // Mock wrapper.vm.maxFileSize to return a small value
    vi.spyOn(wrapper.vm, 'maxFileSize', 'get').mockReturnValue(5 * 1024 * 1024); // 5MB
    
    // Mock Quasar notify
    wrapper.vm.$q = { notify: vi.fn() };
    
    // Test with file that's too large
    wrapper.vm.handleFileSelected(largeFile);
    expect(wrapper.vm.$q.notify).toHaveBeenCalled();
    expect(wrapper.vm.mediaFile).toBe(null);
    
    // Restore maxFileSize mock
    vi.restoreAllMocks();
    
    // Create a proper sized mock file
    const validFile = new File([''], 'valid.jpg', { type: 'image/jpeg' });
    Object.defineProperty(validFile, 'size', { value: 2 * 1024 * 1024 }); // 2MB
    
    // Test with valid image file
    wrapper.vm.handleFileSelected(validFile);
    expect(wrapper.vm.mediaFile).toBe(validFile);
    
    // Cleanup
    URL.createObjectURL = originalCreateObjectURL;
  });
});