import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount, flushPromises } from "@vue/test-utils";
import WhatsAppMediaGallery from "../../src/components/whatsapp/WhatsAppMediaGallery.vue";
import { mediaService } from "../../src/services/mediaService";

// Mock mediaService
vi.mock("../../src/services/mediaService", () => {
  return {
    mediaService: {
      getRecentMedia: vi.fn()
    }
  };
});

// Mock MediaLibrary component
vi.mock("../../src/components/media/MediaLibrary.vue", () => ({
  default: {
    name: "MediaLibrary",
    render: () => null,
    emits: ['media-selected', 'cancel']
  }
}));

describe("WhatsAppMediaGallery", () => {
  beforeEach(() => {
    // Reset mocks
    vi.clearAllMocks();

    // Setup mock data
    const mockRecentMedia = [
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
    ];

    // Mock the mediaService method
    vi.mocked(mediaService.getRecentMedia).mockReturnValue(mockRecentMedia);
  });

  it("renders properly with default props", async () => {
    const wrapper = mount(WhatsAppMediaGallery, {
      props: {
        selectedMediaId: '',
        maxItems: 12
      },
      global: {
        stubs: {
          QCard: true,
          QCardSection: true,
          QSeparator: true,
          QIcon: true,
          QBtn: true,
          QImg: true,
          QTooltip: true,
          QDialog: true,
          QSpace: true,
          'media-library': true
        }
      }
    });

    // Wait for mounted to be called
    await flushPromises();

    // Verify mediaService was called
    expect(mediaService.getRecentMedia).toHaveBeenCalled();

    // Verify component props
    expect(wrapper.props().selectedMediaId).toBe('');
    expect(wrapper.props().maxItems).toBe(12);

    // Verify media library dialog is initially closed
    expect(wrapper.vm.mediaLibraryDialog).toBe(false);
  });

  it("limits the number of media items based on maxItems prop", async () => {
    // Create a lot of mock media items
    const manyMediaItems = Array.from({ length: 20 }, (_, i) => ({
      id: `id-${i}`,
      mediaId: `media-${i}`,
      type: i % 2 === 0 ? 'image' : 'document',
      mimeType: i % 2 === 0 ? 'image/jpeg' : 'application/pdf',
      filename: `file-${i}.${i % 2 === 0 ? 'jpg' : 'pdf'}`,
      size: 1000 * i,
      timestamp: new Date().toISOString(),
      favorite: i % 5 === 0,
      url: `blob:url-${i}`
    }));

    // Mock the service to return many items
    vi.mocked(mediaService.getRecentMedia).mockReturnValue(manyMediaItems);

    // Mount with limited maxItems
    const wrapper = mount(WhatsAppMediaGallery, {
      props: {
        selectedMediaId: '',
        maxItems: 5
      },
      global: {
        stubs: {
          QCard: true,
          QCardSection: true,
          QSeparator: true,
          QIcon: true,
          QBtn: true,
          QImg: true,
          QTooltip: true,
          QDialog: true,
          QSpace: true,
          'media-library': true
        }
      }
    });

    await flushPromises();

    // Verify the computed property limits to maxItems
    expect(wrapper.vm.filteredRecentMedia.length).toBe(5);
  });

  it("detects selected media correctly", async () => {
    // Mount with a selectedMediaId that matches one of the mock items
    const wrapper = mount(WhatsAppMediaGallery, {
      props: {
        selectedMediaId: '12345', // This matches the first mock item
        maxItems: 12
      },
      global: {
        stubs: {
          QCard: true,
          QCardSection: true,
          QSeparator: true,
          QIcon: true,
          QBtn: true,
          QImg: true,
          QTooltip: true,
          QDialog: true,
          QSpace: true,
          'media-library': true
        }
      }
    });

    await flushPromises();

    // Verify isSelected returns true for the matching media
    const firstMedia = wrapper.vm.recentMedia[0];
    expect(wrapper.vm.isSelected(firstMedia)).toBe(true);

    // And false for non-matching media
    const secondMedia = wrapper.vm.recentMedia[1];
    expect(wrapper.vm.isSelected(secondMedia)).toBe(false);
  });

  it("opens the media library dialog", async () => {
    const wrapper = mount(WhatsAppMediaGallery, {
      props: {
        selectedMediaId: '',
        maxItems: 12
      },
      global: {
        stubs: {
          QCard: true,
          QCardSection: true,
          QSeparator: true,
          QIcon: true,
          QBtn: true,
          QImg: true,
          QTooltip: true,
          QDialog: {
            template: '<div><slot /></div>',
            props: ['modelValue']
          },
          QSpace: true,
          'media-library': true
        }
      }
    });

    await flushPromises();

    // Initially the dialog should be closed
    expect(wrapper.vm.mediaLibraryDialog).toBe(false);

    // Open the dialog
    wrapper.vm.openMediaLibrary();
    expect(wrapper.vm.mediaLibraryDialog).toBe(true);
  });

  it("emits media-selected event when selectMedia is called", async () => {
    const wrapper = mount(WhatsAppMediaGallery, {
      props: {
        selectedMediaId: '',
        maxItems: 12
      },
      global: {
        stubs: {
          QCard: true,
          QCardSection: true,
          QSeparator: true,
          QIcon: true,
          QBtn: true,
          QImg: true,
          QTooltip: true,
          QDialog: true,
          QSpace: true,
          'media-library': true
        }
      }
    });

    await flushPromises();

    // Select a media
    const media = wrapper.vm.recentMedia[0];
    wrapper.vm.selectMedia(media);

    // Verify the event was emitted with the correct media
    expect(wrapper.emitted('media-selected')).toBeTruthy();
    expect(wrapper.emitted('media-selected')![0][0]).toBe(media);
  });

  it("handles media selection from MediaLibrary component", async () => {
    const wrapper = mount(WhatsAppMediaGallery, {
      props: {
        selectedMediaId: '',
        maxItems: 12
      },
      global: {
        stubs: {
          QCard: true,
          QCardSection: true,
          QSeparator: true,
          QIcon: true,
          QBtn: true,
          QImg: true,
          QTooltip: true,
          QDialog: true,
          QSpace: true,
          'media-library': true
        }
      }
    });

    await flushPromises();

    // Open the dialog first
    wrapper.vm.mediaLibraryDialog = true;

    // Mock refreshing the recent media list
    const originalLoadRecentMedia = wrapper.vm.loadRecentMedia;
    wrapper.vm.loadRecentMedia = vi.fn();

    // Simulate selecting media from MediaLibrary
    const media = { mediaId: 'new-media-123', type: 'image' };
    wrapper.vm.onMediaLibrarySelect(media);

    // Verify the dialog was closed
    expect(wrapper.vm.mediaLibraryDialog).toBe(false);

    // Verify the event was emitted with the correct media
    expect(wrapper.emitted('media-selected')).toBeTruthy();
    expect(wrapper.emitted('media-selected')![0][0]).toBe(media);

    // Verify loadRecentMedia was called to refresh the list
    expect(wrapper.vm.loadRecentMedia).toHaveBeenCalled();

    // Restore original method
    wrapper.vm.loadRecentMedia = originalLoadRecentMedia;
  });
});