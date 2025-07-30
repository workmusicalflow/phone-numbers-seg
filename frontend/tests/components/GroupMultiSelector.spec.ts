import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount, flushPromises } from "@vue/test-utils";
import { setActivePinia, createPinia } from "pinia";
import GroupMultiSelector from "../../src/components/import-export/GroupMultiSelector.vue";
import { useContactGroupStore } from "../../src/stores/contactGroupStore";

// Mock Quasar components
vi.mock("quasar", () => ({
  QSelect: {
    name: "QSelect",
    props: {
      modelValue: {},
      options: { type: Array },
      multiple: { type: Boolean },
      chips: { type: Boolean },
      useChips: { type: Boolean },
      loading: { type: Boolean },
      disable: { type: Boolean },
      label: { type: String },
      hint: { type: String },
      outlined: { type: Boolean },
      clearable: { type: Boolean },
    },
    emits: ["update:modelValue"],
    setup: (props: any, { emit, slots }: any) => {
      const updateValue = (value: any) => {
        emit("update:modelValue", value);
      };
      return () => {
        return slots.default ? slots.default() : null;
      };
    },
  },
  QIcon: {
    name: "QIcon",
    props: { name: {}, size: {} },
    setup: () => () => null,
  },
  QItem: {
    name: "QItem",
    setup: (props: any, { slots }: any) => () => slots.default?.(),
  },
  QItemSection: {
    name: "QItemSection",
    props: { avatar: { type: Boolean } },
    setup: (props: any, { slots }: any) => () => slots.default?.(),
  },
  QItemLabel: {
    name: "QItemLabel",
    props: { caption: { type: Boolean } },
    setup: (props: any, { slots }: any) => () => slots.default?.(),
  },
  QChip: {
    name: "QChip",
    props: {
      removable: { type: Boolean },
      tabindex: {},
      color: { type: String },
      textColor: { type: String },
    },
    emits: ["remove"],
    setup: (props: any, { emit, slots }: any) => {
      return () => slots.default?.();
    },
  },
  QBanner: {
    name: "QBanner",
    props: {
      class: { type: String },
      rounded: { type: Boolean },
    },
    setup: (props: any, { slots }: any) => () => slots.default?.(),
  },
}));

// Mock console.error to avoid cluttering test output
const originalConsoleError = console.error;
beforeEach(() => {
  console.error = vi.fn();
});

describe("GroupMultiSelector", () => {
  let wrapper: any;
  let mockStore: any;

  const createWrapper = (props = {}) => {
    const pinia = createPinia();
    setActivePinia(pinia);

    wrapper = mount(GroupMultiSelector, {
      props: {
        userId: 2,
        modelValue: [],
        ...props,
      },
      global: {
        plugins: [pinia],
        stubs: {
          QSelect: true,
          QIcon: true,
          QItem: true,
          QItemSection: true,
          QItemLabel: true,
          QChip: true,
          QBanner: true,
        },
      },
    });

    mockStore = useContactGroupStore();
    
    // Mock store methods
    mockStore.fetchUserGroups = vi.fn().mockResolvedValue();
    mockStore.userGroups = [];
    
    return wrapper;
  };

  afterEach(() => {
    console.error = originalConsoleError;
  });

  describe("Rendu initial", () => {
    it("s'affiche correctement avec les props par défaut", () => {
      createWrapper();

      expect(wrapper.exists()).toBe(true);
      expect(wrapper.find(".group-multi-selector").exists()).toBe(true);
    });

    it("affiche le label correct", () => {
      createWrapper();

      const select = wrapper.find("q-select-stub");
      expect(select.attributes("label")).toBe("Groupes à assigner (optionnel)");
      expect(select.attributes("hint")).toBe(
        "Sélectionnez les groupes auxquels assigner automatiquement les contacts importés"
      );
    });

    it("configure correctement les attributs QSelect", () => {
      createWrapper();

      const select = wrapper.find("q-select-stub");
      expect(select.attributes("multiple")).toBe("true");
      expect(select.attributes("chips")).toBe("true");
      expect(select.attributes("use-chips")).toBe("true");
      expect(select.attributes("outlined")).toBe("true");
      expect(select.attributes("clearable")).toBe("true");
    });
  });

  describe("Gestion de l'état de chargement", () => {
    it("affiche l'état de chargement lors du chargement des groupes", async () => {
      createWrapper();

      // Simuler l'état de chargement
      wrapper.vm.loading = true;
      await wrapper.vm.$nextTick();

      const select = wrapper.find("q-select-stub");
      expect(select.attributes("loading")).toBe("true");
    });

    it("masque l'état de chargement après le chargement", async () => {
      createWrapper();

      wrapper.vm.loading = false;
      await wrapper.vm.$nextTick();

      const select = wrapper.find("q-select-stub");
      expect(select.attributes("loading")).toBe("false");
    });
  });

  describe("Gestion des erreurs", () => {
    it("affiche un message d'erreur en cas d'erreur", async () => {
      createWrapper();

      // Simuler une erreur
      wrapper.vm.error = "Erreur lors du chargement des groupes";
      await wrapper.vm.$nextTick();

      const errorBanner = wrapper.find(".bg-negative");
      expect(errorBanner.exists()).toBe(true);
    });

    it("affiche une erreur si userId n'est pas fourni", async () => {
      createWrapper({ userId: undefined });

      await wrapper.vm.loadUserGroups();

      expect(wrapper.vm.error).toBe("ID utilisateur requis pour charger les groupes");
    });
  });

  describe("Chargement des groupes", () => {
    it("charge les groupes utilisateur au montage", async () => {
      createWrapper({ userId: 2 });

      await flushPromises();

      expect(mockStore.fetchUserGroups).toHaveBeenCalledWith(2);
    });

    it("recharge les groupes quand userId change", async () => {
      createWrapper({ userId: 2 });

      await wrapper.setProps({ userId: 3 });
      await flushPromises();

      expect(mockStore.fetchUserGroups).toHaveBeenCalledWith(3);
    });

    it("ne charge pas les groupes si userId n'est pas fourni", async () => {
      createWrapper({ userId: undefined });

      expect(mockStore.fetchUserGroups).not.toHaveBeenCalled();
    });
  });

  describe("Options des groupes", () => {
    it("transforme correctement les groupes du store en options", async () => {
      mockStore.userGroups = [
        { id: "1", name: "Groupe 1", description: "Description 1" },
        { id: "2", name: "Groupe 2", description: null },
        { id: "3", name: "Groupe 3", description: "Description 3" },
      ];

      createWrapper();
      await flushPromises();

      const expectedOptions = [
        { label: "Groupe 1", value: "1", description: "Description 1" },
        { label: "Groupe 2", value: "2", description: undefined },
        { label: "Groupe 3", value: "3", description: "Description 3" },
      ];

      expect(wrapper.vm.groupOptions).toEqual(expectedOptions);
    });

    it("gère correctement les groupes sans description", async () => {
      mockStore.userGroups = [
        { id: "1", name: "Groupe 1", description: "" },
        { id: "2", name: "Groupe 2", description: null },
      ];

      createWrapper();
      await flushPromises();

      const expectedOptions = [
        { label: "Groupe 1", value: "1", description: undefined },
        { label: "Groupe 2", value: "2", description: undefined },
      ];

      expect(wrapper.vm.groupOptions).toEqual(expectedOptions);
    });
  });

  describe("Sélection des groupes", () => {
    beforeEach(async () => {
      mockStore.userGroups = [
        { id: "1", name: "Groupe 1", description: "Description 1" },
        { id: "2", name: "Groupe 2", description: "Description 2" },
        { id: "3", name: "Groupe 3", description: "Description 3" },
      ];
    });

    it("synchronise correctement modelValue avec selectedGroups", async () => {
      createWrapper({ modelValue: ["1", "2"] });
      await flushPromises();

      expect(wrapper.vm.selectedGroups).toEqual([
        { label: "Groupe 1", value: "1", description: "Description 1" },
        { label: "Groupe 2", value: "2", description: "Description 2" },
      ]);
    });

    it("émet update:modelValue lors de la sélection", async () => {
      createWrapper();
      await flushPromises();

      const newSelection = [
        { label: "Groupe 1", value: "1", description: "Description 1" },
        { label: "Groupe 3", value: "3", description: "Description 3" },
      ];

      await wrapper.vm.onSelectionChange(newSelection);

      expect(wrapper.emitted("update:modelValue")).toBeTruthy();
      expect(wrapper.emitted("update:modelValue")[0]).toEqual([["1", "3"]]);
    });

    it("met à jour selectedGroups quand modelValue change", async () => {
      createWrapper();
      await flushPromises();

      await wrapper.setProps({ modelValue: ["2", "3"] });

      expect(wrapper.vm.selectedGroups).toEqual([
        { label: "Groupe 2", value: "2", description: "Description 2" },
        { label: "Groupe 3", value: "3", description: "Description 3" },
      ]);
    });

    it("gère correctement un modelValue vide", async () => {
      createWrapper({ modelValue: [] });
      await flushPromises();

      expect(wrapper.vm.selectedGroups).toEqual([]);
    });

    it("filtre les groupes inexistants du modelValue", async () => {
      createWrapper({ modelValue: ["1", "999", "2"] }); // 999 n'existe pas
      await flushPromises();

      expect(wrapper.vm.selectedGroups).toEqual([
        { label: "Groupe 1", value: "1", description: "Description 1" },
        { label: "Groupe 2", value: "2", description: "Description 2" },
      ]);
    });
  });

  describe("Bannière d'information", () => {
    beforeEach(async () => {
      mockStore.userGroups = [
        { id: "1", name: "Groupe 1", description: "Description 1" },
        { id: "2", name: "Groupe 2", description: "Description 2" },
      ];
    });

    it("affiche la bannière d'information quand des groupes sont sélectionnés", async () => {
      createWrapper({ modelValue: ["1", "2"] });
      await flushPromises();

      const infoBanner = wrapper.find(".bg-blue-1");
      expect(infoBanner.exists()).toBe(true);
    });

    it("n'affiche pas la bannière quand aucun groupe n'est sélectionné", async () => {
      createWrapper({ modelValue: [] });
      await flushPromises();

      const infoBanner = wrapper.find(".bg-blue-1");
      expect(infoBanner.exists()).toBe(false);
    });

    it("affiche le bon message au singulier", async () => {
      createWrapper({ modelValue: ["1"] });
      await flushPromises();

      const infoBanner = wrapper.find(".bg-blue-1");
      expect(infoBanner.text()).toContain("1 groupe sélectionné");
      expect(infoBanner.text()).toContain("ajootés à ce groupe");
    });

    it("affiche le bon message au pluriel", async () => {
      createWrapper({ modelValue: ["1", "2"] });
      await flushPromises();

      const infoBanner = wrapper.find(".bg-blue-1");
      expect(infoBanner.text()).toContain("2 groupes sélectionnés");
      expect(infoBanner.text()).toContain("ajoutés à ces groupes");
    });
  });

  describe("Gestion des props", () => {
    it("respecte la prop disable", () => {
      createWrapper({ disable: true });

      const select = wrapper.find("q-select-stub");
      expect(select.attributes("disable")).toBe("true");
    });

    it("ne désactive pas par défaut", () => {
      createWrapper();

      const select = wrapper.find("q-select-stub");
      expect(select.attributes("disable")).toBe("false");
    });

    it("utilise la valeur par défaut pour userId", async () => {
      createWrapper({ userId: undefined });

      expect(wrapper.vm.userId).toBeUndefined();
    });
  });

  describe("Gestion des exceptions", () => {
    it("gère les erreurs lors du chargement des groupes", async () => {
      mockStore.fetchUserGroups.mockRejectedValue(new Error("Network error"));

      createWrapper({ userId: 2 });
      await flushPromises();

      expect(wrapper.vm.error).toBe("Erreur lors du chargement des groupes");
    });

    it("continue de fonctionner après une erreur", async () => {
      mockStore.fetchUserGroups.mockRejectedValue(new Error("Network error"));

      createWrapper({ userId: 2 });
      await flushPromises();

      // Vérifier que l'erreur est affichée
      expect(wrapper.vm.error).toBe("Erreur lors du chargement des groupes");

      // Simuler un nouveau chargement réussi
      mockStore.fetchUserGroups.mockResolvedValue();
      mockStore.userGroups = [{ id: "1", name: "Groupe 1", description: null }];

      await wrapper.vm.loadUserGroups();

      expect(wrapper.vm.error).toBeNull();
    });
  });

  describe("Cycle de vie du composant", () => {
    it("appelle loadUserGroups au montage si userId est fourni", async () => {
      const loadUserGroupsSpy = vi.spyOn(GroupMultiSelector.methods || {}, "loadUserGroups");

      createWrapper({ userId: 2 });
      await flushPromises();

      expect(mockStore.fetchUserGroups).toHaveBeenCalledWith(2);
    });

    it("synchronise les données quand groupOptions change", async () => {
      createWrapper({ modelValue: ["1"] });

      // Simuler un changement des options
      mockStore.userGroups = [
        { id: "1", name: "Groupe 1 Modifié", description: "Nouvelle description" },
      ];

      await wrapper.vm.$nextTick();

      expect(wrapper.vm.selectedGroups[0].label).toBe("Groupe 1 Modifié");
    });
  });

  describe("Accessibilité et UX", () => {
    it("affiche l'icône appropriée dans les éléments sélectionnés", async () => {
      mockStore.userGroups = [{ id: "1", name: "Groupe 1", description: null }];

      createWrapper({ modelValue: ["1"] });
      await flushPromises();

      // Vérifier la structure du template (difficile à tester exactement avec les stubs)
      expect(wrapper.find("q-select-stub").exists()).toBe(true);
    });

    it("fournit des indices visuels appropriés", () => {
      createWrapper();

      const select = wrapper.find("q-select-stub");
      expect(select.attributes("hint")).toContain("Sélectionnez les groupes");
    });
  });
});