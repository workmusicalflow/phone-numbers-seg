import { describe, it, expect, vi, beforeEach } from "vitest";
import { setActivePinia, createPinia } from "pinia";
import { usePhoneStore, PhoneNumber } from "../../src/stores/phoneStore";

// Create mock functions
const mockQuery = vi.fn();
const mockMutate = vi.fn();

// Mock Apollo Client
vi.mock("@vue/apollo-composable", () => ({
  useApolloClient: () => ({
    client: {
      query: mockQuery,
      mutate: mockMutate,
    },
  }),
}));

describe("phoneStore", () => {
  // Sample data
  const mockPhoneNumbers: PhoneNumber[] = [
    {
      id: "1",
      number: "+2250777104936",
      createdAt: "2025-03-30T12:00:00Z",
      segments: [
        { id: "1", type: "country_code", value: "225" },
        { id: "2", type: "operator_code", value: "07" },
        { id: "3", type: "subscriber_number", value: "77104936" },
        { id: "4", type: "operator_name", value: "Orange CI" },
      ],
    },
    {
      id: "2",
      number: "+2250141399354",
      createdAt: "2025-03-30T12:30:00Z",
      segments: [
        { id: "5", type: "country_code", value: "225" },
        { id: "6", type: "operator_code", value: "01" },
        { id: "7", type: "subscriber_number", value: "41399354" },
        { id: "8", type: "operator_name", value: "Moov Africa" },
      ],
    },
  ];

  beforeEach(() => {
    // Create a fresh Pinia instance for each test
    setActivePinia(createPinia());

    // Reset mock functions
    mockQuery.mockReset();
    mockMutate.mockReset();
  });

  it("initializes with empty state", () => {
    const store = usePhoneStore();
    expect(store.phoneNumbers).toEqual([]);
    expect(store.loading).toBe(false);
    expect(store.error).toBe(null);
  });

  it("fetches phone numbers successfully", async () => {
    const store = usePhoneStore();

    // Mock the Apollo query response
    mockQuery.mockResolvedValue({
      data: {
        phoneNumbers: mockPhoneNumbers,
      },
    });

    await store.fetchPhoneNumbers();

    expect(store.phoneNumbers).toEqual(mockPhoneNumbers);
    expect(store.loading).toBe(false);
    expect(store.error).toBe(null);
    expect(mockQuery).toHaveBeenCalledTimes(1);
  });

  it("handles error when fetching phone numbers", async () => {
    const store = usePhoneStore();
    const error = new Error("Failed to fetch phone numbers");

    // Mock the Apollo query to throw an error
    mockQuery.mockRejectedValue(error);

    await store.fetchPhoneNumbers();

    expect(store.phoneNumbers).toEqual([]);
    expect(store.loading).toBe(false);
    expect(store.error).toEqual(error);
    expect(mockQuery).toHaveBeenCalledTimes(1);
  });

  it("adds a phone number successfully", async () => {
    const store = usePhoneStore();
    const newPhoneNumber = mockPhoneNumbers[0];

    // Mock the Apollo mutation response
    mockMutate.mockResolvedValue({
      data: {
        createPhoneNumber: newPhoneNumber,
      },
    });

    const result = await store.addPhoneNumber(newPhoneNumber.number);

    expect(result).toEqual(newPhoneNumber);
    // Use deep equality instead of reference equality
    expect(store.phoneNumbers).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          id: newPhoneNumber.id,
          number: newPhoneNumber.number,
        }),
      ]),
    );
    expect(store.loading).toBe(false);
    expect(store.error).toBe(null);
    expect(mockMutate).toHaveBeenCalledTimes(1);
    expect(mockMutate).toHaveBeenCalledWith(
      expect.objectContaining({
        variables: { number: newPhoneNumber.number },
      }),
    );
  });

  it("deletes a phone number successfully", async () => {
    const store = usePhoneStore();
    store.phoneNumbers = [...mockPhoneNumbers];

    // Mock the Apollo mutation response
    mockMutate.mockResolvedValue({
      data: {
        deletePhoneNumber: true,
      },
    });

    const result = await store.deletePhoneNumber("1");

    expect(result).toBe(true);
    expect(store.phoneNumbers).toHaveLength(1);
    expect(store.phoneNumbers[0].id).toBe("2");
    expect(store.loading).toBe(false);
    expect(store.error).toBe(null);
    expect(mockMutate).toHaveBeenCalledTimes(1);
    expect(mockMutate).toHaveBeenCalledWith(
      expect.objectContaining({
        variables: { id: "1" },
      }),
    );
  });
});
