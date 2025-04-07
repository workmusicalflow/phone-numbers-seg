import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { NotificationService } from "../../src/components/NotificationService";

// Définir le mock avant l'importation
vi.mock("quasar", async () => {
  return {
    Notify: {
      create: vi.fn(),
    },
  };
});

// Importer le mock après sa définition
const mockNotify = vi.mocked(await import("quasar")).Notify;

describe("NotificationService", () => {
  beforeEach(() => {
    // Reset the mock before each test
    vi.clearAllMocks();
  });

  it("calls success notification with correct parameters", () => {
    const message = "Opération réussie";
    NotificationService.success(message);

    expect(mockNotify.create).toHaveBeenCalledTimes(1);
    expect(mockNotify.create).toHaveBeenCalledWith(
      expect.objectContaining({
        type: "positive",
        message,
        icon: "check_circle",
        position: "top-right",
        timeout: 3000,
      }),
    );
  });

  it("calls error notification with correct parameters", () => {
    const message = "Une erreur est survenue";
    NotificationService.error(message);

    expect(mockNotify.create).toHaveBeenCalledTimes(1);
    expect(mockNotify.create).toHaveBeenCalledWith(
      expect.objectContaining({
        type: "negative",
        message,
        icon: "error",
        position: "top-right",
        timeout: 5000,
      }),
    );
  });

  it("calls warning notification with correct parameters", () => {
    const message = "Attention";
    NotificationService.warning(message);

    expect(mockNotify.create).toHaveBeenCalledTimes(1);
    expect(mockNotify.create).toHaveBeenCalledWith(
      expect.objectContaining({
        type: "warning",
        message,
        icon: "warning",
        position: "top-right",
        timeout: 4000,
      }),
    );
  });

  it("calls info notification with correct parameters", () => {
    const message = "Information";
    NotificationService.info(message);

    expect(mockNotify.create).toHaveBeenCalledTimes(1);
    expect(mockNotify.create).toHaveBeenCalledWith(
      expect.objectContaining({
        type: "info",
        message,
        icon: "info",
        position: "top-right",
        timeout: 3000,
      }),
    );
  });

  it("merges custom options with defaults", () => {
    const message = "Message personnalisé";
    const customOptions = {
      timeout: 10000,
      position: "bottom-right" as const,
    };

    NotificationService.success(message, customOptions);

    expect(mockNotify.create).toHaveBeenCalledTimes(1);
    expect(mockNotify.create).toHaveBeenCalledWith(
      expect.objectContaining({
        type: "positive",
        message,
        icon: "check_circle",
        position: "bottom-right", // Custom position
        timeout: 10000, // Custom timeout
      }),
    );
  });
});
