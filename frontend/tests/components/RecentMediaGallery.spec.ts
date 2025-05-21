import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount, flushPromises } from "@vue/test-utils";
import RecentMediaGallery from "../../src/components/media/RecentMediaGallery.vue";
import { mediaService } from "../../src/services/mediaService";

// Mock mediaService
vi.mock("../../src/services/mediaService", () => {
  return {
    mediaService: {
      getRecentMedia: vi.fn(),
      getFavoriteMedia: vi.fn(),
      removeFromFavorites: vi.fn(),
      addToFavorites: vi.fn()
    }
  };
});

describe("RecentMediaGallery", () => {
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

    // Mock the mediaService methods
    vi.mocked(mediaService.getRecentMedia).mockReturnValue(mockRecentMedia);
    vi.mocked(mediaService.getFavoriteMedia).mockReturnValue([mockRecentMedia[1]]);
  });

  it("renders properly with initial data", async () => {
    const wrapper = mount(RecentMediaGallery, {
      props: {
        maxItems: 10,
        showFavorites: false,
        initialFilter: 'all'
      },
      global: {
        stubs: {
          QInput: true,
          QIcon: true,
          QBtnToggle: true,
          QCard: true,
          QImg: true,
          QCardSection: true,
          QCardActions: true,
          QBtn: true,
          QTooltip: true
        }
      }
    });

    // Wait for mounted to be called
    await flushPromises();

    // Verify mediaService was called correctly
    expect(mediaService.getRecentMedia).toHaveBeenCalled();
    expect(mediaService.getFavoriteMedia).not.toHaveBeenCalled(); // Should not be called if showFavorites is false

    // Verify component props and data
    expect(wrapper.props().maxItems).toBe(10);
    expect(wrapper.props().showFavorites).toBe(false);
    expect(wrapper.props().initialFilter).toBe('all');

    // Verify media items are loaded
    expect(wrapper.vm.mediaItems.length).toBe(2);
  });

  it("displays favorites when showFavorites is true", async () => {
    const wrapper = mount(RecentMediaGallery, {
      props: {
        maxItems: 10,
        showFavorites: true,
        initialFilter: 'all'
      },
      global: {
        stubs: {
          QInput: true,
          QIcon: true,
          QBtnToggle: true,
          QCard: true,
          QImg: true,
          QCardSection: true,
          QCardActions: true,
          QBtn: true,
          QTooltip: true
        }
      }
    });

    // Wait for mounted to be called
    await flushPromises();

    // Verify correct service method was called
    expect(mediaService.getRecentMedia).not.toHaveBeenCalled();
    expect(mediaService.getFavoriteMedia).toHaveBeenCalled();

    // Verify only favorites are shown
    expect(wrapper.vm.mediaItems.length).toBe(1);
    expect(wrapper.vm.mediaItems[0].favorite).toBe(true);
  });

  it("filters media items by type", async () => {
    const wrapper = mount(RecentMediaGallery, {
      props: {
        maxItems: 10,
        showFavorites: false,
        initialFilter: 'all'
      },
      global: {
        stubs: {
          QInput: true,
          QIcon: true,
          QBtnToggle: true,
          QCard: true,
          QImg: true,
          QCardSection: true,
          QCardActions: true,
          QBtn: true,
          QTooltip: true
        }
      }
    });

    await flushPromises();

    // Change filter to 'image'
    await wrapper.setData({ typeFilter: 'image' });
    
    // Only image items should be shown
    expect(wrapper.vm.filteredMedia.length).toBe(1);
    expect(wrapper.vm.filteredMedia[0].type).toBe('image');

    // Change filter to 'document'
    await wrapper.setData({ typeFilter: 'document' });
    
    // Only document items should be shown
    expect(wrapper.vm.filteredMedia.length).toBe(1);
    expect(wrapper.vm.filteredMedia[0].type).toBe('document');
  });

  it("filters media items by search query", async () => {
    const wrapper = mount(RecentMediaGallery, {
      props: {
        maxItems: 10,
        showFavorites: false,
        initialFilter: 'all'
      },
      global: {
        stubs: {
          QInput: true,
          QIcon: true,
          QBtnToggle: true,
          QCard: true,
          QImg: true,
          QCardSection: true,
          QCardActions: true,
          QBtn: true,
          QTooltip: true
        }
      }
    });

    await flushPromises();

    // Search for 'image'
    await wrapper.setData({ searchQuery: 'image' });
    
    // Only items with 'image' in their filename should be shown
    expect(wrapper.vm.filteredMedia.length).toBe(1);
    expect(wrapper.vm.filteredMedia[0].filename).toContain('image');

    // Search for 'document'
    await wrapper.setData({ searchQuery: 'document' });
    
    // Only items with 'document' in their filename should be shown
    expect(wrapper.vm.filteredMedia.length).toBe(1);
    expect(wrapper.vm.filteredMedia[0].filename).toContain('document');

    // Search for non-existent term
    await wrapper.setData({ searchQuery: 'nonexistent' });
    
    // No items should be shown
    expect(wrapper.vm.filteredMedia.length).toBe(0);
  });

  it("emits an event when a media is selected", async () => {
    const wrapper = mount(RecentMediaGallery, {
      props: {
        maxItems: 10,
        showFavorites: false,
        initialFilter: 'all'
      },
      global: {
        stubs: {
          QInput: true,
          QIcon: true,
          QBtnToggle: true,
          QCard: true,
          QImg: true,
          QCardSection: true,
          QCardActions: true,
          QBtn: true,
          QTooltip: true
        },
        mocks: {
          $q: {
            notify: vi.fn()
          }
        }
      }
    });

    await flushPromises();

    // Simulate selecting a media
    const firstMedia = wrapper.vm.mediaItems[0];
    wrapper.vm.useMedia(firstMedia);
    
    // Verify the event was emitted
    expect(wrapper.emitted('media-selected')).toBeTruthy();
    expect(wrapper.emitted('media-selected')![0][0]).toBe(firstMedia);
  });

  it("toggles favorite status", async () => {
    const wrapper = mount(RecentMediaGallery, {
      props: {
        maxItems: 10,
        showFavorites: false,
        initialFilter: 'all'
      },
      global: {
        stubs: {
          QInput: true,
          QIcon: true,
          QBtnToggle: true,
          QCard: true,
          QImg: true,
          QCardSection: true,
          QCardActions: true,
          QBtn: true,
          QTooltip: true
        },
        mocks: {
          $q: {
            notify: vi.fn()
          }
        }
      }
    });

    await flushPromises();

    // Mock for refreshMedia
    vi.spyOn(wrapper.vm, 'refreshMedia').mockImplementation(() => {});

    // Toggle favorite for non-favorite item (add to favorites)
    const nonFavoriteMedia = wrapper.vm.mediaItems[0];
    expect(nonFavoriteMedia.favorite).toBe(false);
    wrapper.vm.toggleFavorite(nonFavoriteMedia);
    
    // Verify mediaService was called
    expect(mediaService.addToFavorites).toHaveBeenCalledWith(nonFavoriteMedia);
    expect(mediaService.removeFromFavorites).not.toHaveBeenCalled();
    
    // Toggle favorite for favorite item (remove from favorites)
    const favoriteMedia = { ...wrapper.vm.mediaItems[1], favorite: true };
    wrapper.vm.toggleFavorite(favoriteMedia);
    
    // Verify mediaService was called
    expect(mediaService.removeFromFavorites).toHaveBeenCalledWith(favoriteMedia.mediaId);
  });

  it("reloads media when showFavorites changes", async () => {
    const wrapper = mount(RecentMediaGallery, {
      props: {
        maxItems: 10,
        showFavorites: false,
        initialFilter: 'all'
      },
      global: {
        stubs: {
          QInput: true,
          QIcon: true,
          QBtnToggle: true,
          QCard: true,
          QImg: true,
          QCardSection: true,
          QCardActions: true,
          QBtn: true,
          QTooltip: true
        }
      }
    });

    await flushPromises();
    
    // Clear call counts
    vi.clearAllMocks();
    
    // Change showFavorites prop
    await wrapper.setProps({ showFavorites: true });
    
    // Verify mediaService was called again
    expect(mediaService.getFavoriteMedia).toHaveBeenCalled();
  });
});