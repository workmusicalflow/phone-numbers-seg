import { describe, it, expect, vi } from "vitest";
import { mount } from "@vue/test-utils";
import SearchBar from "../../src/components/SearchBar.vue";

// Mock Quasar components and functions
vi.mock("quasar", () => ({
  debounce: (fn: Function) => fn,
  QInput: {
    name: "QInput",
    props: {
      modelValue: {},
      placeholder: {},
      clearable: { type: Boolean },
      outlined: { type: Boolean },
      dense: { type: Boolean },
    },
    setup: (props: any, { slots, emit }: any) => {
      return () => {
        // Simplified render function that just renders slots
        return slots.default ? slots.default() : null;
      };
    },
  },
  QIcon: {
    name: "QIcon",
    props: {
      name: {},
    },
    setup: () => {
      return () => null; // Render nothing
    },
  },
}));

describe("SearchBar", () => {
  it("renders correctly with default props", () => {
    const wrapper = mount(SearchBar, {
      global: {
        stubs: {
          QInput: true,
          QIcon: true,
        },
      },
    });

    const input = wrapper.find("q-input-stub");
    expect(input.exists()).toBe(true);
    expect(input.attributes("placeholder")).toBe("Rechercher...");
  });

  it("renders with custom placeholder", () => {
    const customPlaceholder = "Rechercher un numéro...";
    const wrapper = mount(SearchBar, {
      props: {
        placeholder: customPlaceholder,
      },
      global: {
        stubs: {
          QInput: true,
          QIcon: true,
        },
      },
    });

    const input = wrapper.find("q-input-stub");
    expect(input.attributes("placeholder")).toBe(customPlaceholder);
  });

  it("initializes with the provided value", () => {
    const initialValue = "+22507XXXXXXX";
    const wrapper = mount(SearchBar, {
      props: {
        initialValue,
      },
      global: {
        stubs: {
          QInput: true,
          QIcon: true,
        },
      },
    });

    // Access the internal state
    expect(wrapper.vm.searchQuery).toBe(initialValue);
  });

  it("emits search event when input changes", async () => {
    const wrapper = mount(SearchBar, {
      global: {
        stubs: {
          QInput: true,
          QIcon: true,
        },
      },
    });

    // Simulate input change
    await wrapper.vm.onSearch("test");

    // Check if the search event was emitted with the correct value
    expect(wrapper.emitted("search")).toBeTruthy();
    expect(wrapper.emitted("search")![0]).toEqual(["test"]);
  });

  it("emits clear event when cleared", async () => {
    const wrapper = mount(SearchBar, {
      props: {
        initialValue: "test",
      },
      global: {
        stubs: {
          QInput: true,
          QIcon: true,
        },
      },
    });

    // Simulate clear
    await wrapper.vm.onClear();

    // Check if the clear event was emitted
    expect(wrapper.emitted("clear")).toBeTruthy();
    expect(wrapper.vm.searchQuery).toBe("");
  });

  it("exposes the correct methods", () => {
    const wrapper = mount(SearchBar, {
      global: {
        stubs: {
          QInput: true,
          QIcon: true,
        },
      },
    });

    // Check if the exposed methods exist
    expect(typeof wrapper.vm.clear).toBe("function");
    expect(typeof wrapper.vm.setValue).toBe("function");

    // Test the setValue method
    wrapper.vm.setValue("new value");
    expect(wrapper.vm.searchQuery).toBe("new value");
  });

  it("updates searchQuery when initialValue prop changes", async () => {
    const wrapper = mount(SearchBar, {
      props: {
        initialValue: "initial",
      },
      global: {
        stubs: {
          QInput: true,
          QIcon: true,
        },
      },
    });

    // Initial value
    expect(wrapper.vm.searchQuery).toBe("initial");

    // Update the prop
    await wrapper.setProps({ initialValue: "updated" });

    // Check if the internal state was updated
    expect(wrapper.vm.searchQuery).toBe("updated");
  });
});
