import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import PhoneNumberCard from "../../src/components/PhoneNumberCard.vue";
import { PhoneNumber } from "../../src/stores/phoneStore";

describe("PhoneNumberCard", () => {
  // Mock data
  const mockPhoneNumber: PhoneNumber = {
    id: "1",
    number: "+2250777104936",
    createdAt: "2025-03-30T12:00:00Z",
    segments: [
      { id: "1", type: "country_code", value: "225" },
      { id: "2", type: "operator_code", value: "07" },
      { id: "3", type: "subscriber_number", value: "77104936" },
      { id: "4", type: "operator_name", value: "Orange CI" },
    ],
  };

  it("renders the phone number correctly", () => {
    const wrapper = mount(PhoneNumberCard, {
      props: {
        phoneNumber: mockPhoneNumber,
      },
      global: {
        stubs: {
          QCard: {
            template: "<div><slot /></div>",
          },
          QCardSection: {
            template: "<div><slot /></div>",
          },
          QCardActions: {
            template: "<div><slot /></div>",
          },
          QList: {
            template: "<div><slot /></div>",
          },
          QItem: {
            template: "<div><slot /></div>",
          },
          QItemSection: {
            template: "<div><slot /></div>",
          },
          QItemLabel: {
            template: "<div><slot /></div>",
          },
        },
      },
    });

    // Vérifier que le numéro de téléphone est affiché
    expect(wrapper.text()).toContain(mockPhoneNumber.number);

    // Vérifier que la date est formatée et affichée
    expect(wrapper.text()).toContain("Ajouté le");

    // Vérifier que tous les segments sont affichés
    mockPhoneNumber.segments.forEach((segment) => {
      expect(wrapper.text()).toContain(segment.type);
      expect(wrapper.text()).toContain(segment.value);
    });
  });

  it('renders the "no segments" message when there are no segments', () => {
    const phoneNumberWithoutSegments: PhoneNumber = {
      ...mockPhoneNumber,
      segments: [],
    };

    const wrapper = mount(PhoneNumberCard, {
      props: {
        phoneNumber: phoneNumberWithoutSegments,
      },
      global: {
        stubs: {
          QCard: {
            template: "<div><slot /></div>",
          },
          QCardSection: {
            template: "<div><slot /></div>",
          },
          QCardActions: {
            template: "<div><slot /></div>",
          },
          QList: {
            template: "<div><slot /></div>",
          },
          QItem: {
            template: "<div><slot /></div>",
          },
          QItemSection: {
            template: "<div><slot /></div>",
          },
          QItemLabel: {
            template: "<div><slot /></div>",
          },
        },
      },
    });

    expect(wrapper.text()).toContain("Aucun segment trouvé");
  });

  it("renders slot content correctly", () => {
    const wrapper = mount(PhoneNumberCard, {
      props: {
        phoneNumber: mockPhoneNumber,
      },
      slots: {
        actions: "<button>Test Button</button>",
      },
      global: {
        stubs: {
          QCard: {
            template: "<div><slot /></div>",
          },
          QCardSection: {
            template: "<div><slot /></div>",
          },
          QCardActions: {
            template: '<div><slot name="default" /></div>',
          },
          QList: {
            template: "<div><slot /></div>",
          },
          QItem: {
            template: "<div><slot /></div>",
          },
          QItemSection: {
            template: "<div><slot /></div>",
          },
          QItemLabel: {
            template: "<div><slot /></div>",
          },
        },
      },
    });

    expect(wrapper.html()).toContain("<button>Test Button</button>");
  });
});
