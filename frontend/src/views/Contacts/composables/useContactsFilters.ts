/**
 * Composable pour la gestion des filtres de contacts
 * Gère la recherche, les filtres par groupe et le tri
 */

import { computed } from 'vue';
import { useContactStore } from '../../../stores/contactStore';
import { useContactGroupStore } from '../../../stores/contactGroupStore';
import type { ContactsFilters } from '../types/contacts.types';

export function useContactsFilters() {
  // Stores
  const contactStore = useContactStore();
  const contactGroupStore = useContactGroupStore();

  // État des filtres (computed pour synchronisation avec le store)
  const filters = computed<ContactsFilters>({
    get: () => ({
      searchTerm: contactStore.searchTerm,
      groupId: contactStore.currentGroupId,
      sortBy: contactStore.sortBy,
      sortDesc: contactStore.sortDesc
    }),
    set: (_newFilters) => {
      // Cette fonction ne devrait pas être appelée directement
      // Utilisez les fonctions spécifiques ci-dessous
      console.warn('Utilisez les fonctions spécifiques pour mettre à jour les filtres');
    }
  });

  // Options pour les groupes dans les sélecteurs
  const groupOptions = computed(() => {
    const options: Array<{ label: string; value: string | null }> = [
      { label: 'Tous les groupes', value: null }
    ];
    
    if (contactGroupStore.groupsForSelect) {
      const mappedGroups = contactGroupStore.groupsForSelect.map(group => ({
        label: group.name,
        value: String(group.id)
      }));
      options.push(...mappedGroups);
    }
    
    return options;
  });

  // État de chargement des filtres
  const filtersLoading = computed(() => 
    contactStore.loading || contactGroupStore.isLoading
  );

  /**
   * Met à jour le terme de recherche
   */
  async function updateSearchTerm(searchTerm: string): Promise<void> {
    await contactStore.searchContacts(searchTerm);
  }

  /**
   * Met à jour le filtre de groupe
   */
  async function updateGroupFilter(groupId: string | null): Promise<void> {
    await contactStore.filterByGroup(groupId);
  }

  /**
   * Met à jour le tri
   */
  function updateSorting(sortBy: string, descending: boolean): void {
    contactStore.setSorting(sortBy, descending);
  }

  /**
   * Efface tous les filtres
   */
  async function clearAllFilters(): Promise<void> {
    await Promise.all([
      contactStore.searchContacts(''),
      contactStore.filterByGroup(null)
    ]);
  }

  /**
   * Applique des filtres multiples en une fois
   */
  async function applyFilters(newFilters: Partial<ContactsFilters>): Promise<void> {
    const promises: Promise<void>[] = [];

    if (newFilters.searchTerm !== undefined) {
      promises.push(contactStore.searchContacts(newFilters.searchTerm));
    }

    if (newFilters.groupId !== undefined) {
      promises.push(contactStore.filterByGroup(newFilters.groupId));
    }

    if (newFilters.sortBy !== undefined && newFilters.sortDesc !== undefined) {
      contactStore.setSorting(newFilters.sortBy, newFilters.sortDesc);
    }

    await Promise.all(promises);
  }

  /**
   * Vérifie si des filtres sont actifs
   */
  const hasActiveFilters = computed(() => {
    return !!(filters.value.searchTerm || filters.value.groupId);
  });

  /**
   * Obtient un résumé des filtres actifs
   */
  const activeFiltersSummary = computed(() => {
    const summary: string[] = [];
    
    if (filters.value.searchTerm) {
      summary.push(`Recherche: "${filters.value.searchTerm}"`);
    }
    
    if (filters.value.groupId) {
      const group = contactGroupStore.contactGroups?.find(g => g.id === filters.value.groupId);
      if (group) {
        summary.push(`Groupe: ${group.name}`);
      }
    }
    
    return summary;
  });

  return {
    // État
    filters,
    groupOptions,
    filtersLoading,
    hasActiveFilters,
    activeFiltersSummary,
    
    // Actions
    updateSearchTerm,
    updateGroupFilter,
    updateSorting,
    clearAllFilters,
    applyFilters
  };
}