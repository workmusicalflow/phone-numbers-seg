/**
 * Composable pour la gestion des données de contacts
 * Gère l'état, le chargement et la pagination
 */

import { ref, computed } from 'vue';
import { useContactStore } from '../../../stores/contactStore';
import { useContactGroupStore } from '../../../stores/contactGroupStore';
import type { ContactsStats, ContactsPagination } from '../types/contacts.types';

export function useContactsData() {
  // Stores
  const contactStore = useContactStore();
  const contactGroupStore = useContactGroupStore();

  // État local pour le cache des stats
  const contactsCount = ref(0);
  const currentPage = ref(1);

  // Computed properties pour les stats
  const stats = computed<ContactsStats>(() => ({
    total: contactStore.totalCount || contactsCount.value,
    active: contactStore.contacts?.filter(contact => 
      contact.phoneNumber && contact.phoneNumber.trim() !== ''
    ).length || 0,
    groups: contactGroupStore.contactGroups?.length || 0
  }));

  // Computed pour la pagination
  const pagination = computed<ContactsPagination>(() => ({
    page: contactStore.currentPage,
    rowsPerPage: contactStore.itemsPerPage,
    sortBy: contactStore.sortBy,
    descending: contactStore.sortDesc
  }));

  // État de chargement global
  const loading = computed(() => contactStore.loading || contactGroupStore.isLoading);

  // Données des contacts
  const contacts = computed(() => contactStore.contacts || []);
  const totalCount = computed(() => contactStore.totalCount || 0);

  // Erreurs
  const error = computed(() => contactStore.error);

  /**
   * Initialise les données
   */
  async function initializeData(): Promise<void> {
    try {
      // Charger les groupes d'abord pour les filtres
      await contactGroupStore.fetchContactGroups();
      
      // Charger les contacts
      await contactStore.fetchContacts();
      
      // Mettre à jour le cache des stats
      contactsCount.value = contactStore.totalCount || 0;
    } catch (err) {
      console.error('Erreur lors de l\'initialisation des données:', err);
    }
  }

  /**
   * Rafraîchit tous les données
   */
  async function refreshAllData(): Promise<void> {
    await Promise.all([
      contactStore.fetchContacts(),
      contactGroupStore.fetchContactGroups()
    ]);
    contactsCount.value = contactStore.totalCount || 0;
  }

  /**
   * Rafraîchit seulement les contacts
   */
  async function refreshContacts(): Promise<void> {
    await contactStore.fetchContacts();
    contactsCount.value = contactStore.totalCount || 0;
  }

  /**
   * Rafraîchit le nombre de contacts
   */
  async function refreshContactsCount(): Promise<number> {
    const count = await contactStore.fetchContactsCount();
    contactsCount.value = count;
    return count;
  }

  /**
   * Change la page
   */
  function setPage(page: number): void {
    currentPage.value = page;
    contactStore.setPage(page);
  }

  /**
   * Change le nombre d'éléments par page
   */
  function setItemsPerPage(itemsPerPage: number): void {
    contactStore.setItemsPerPage(itemsPerPage);
    currentPage.value = 1; // Reset à la première page
  }

  /**
   * Change le tri
   */
  function setSorting(sortBy: string, descending: boolean): void {
    contactStore.setSorting(sortBy, descending);
  }

  /**
   * Gère les changements de pagination depuis QTable
   */
  function handlePaginationRequest(paginationPayload: {
    page: number;
    rowsPerPage: number;
    sortBy: string;
    descending: boolean;
  }): void {
    const { page, rowsPerPage, sortBy, descending } = paginationPayload;
    
    // Mettre à jour tous les paramètres de pagination
    contactStore.setPage(page);
    contactStore.setItemsPerPage(rowsPerPage);
    contactStore.setSorting(sortBy, descending);
  }

  return {
    // État
    stats,
    pagination,
    loading,
    contacts,
    totalCount,
    error,
    currentPage,
    
    // Actions
    initializeData,
    refreshAllData,
    refreshContacts,
    refreshContactsCount,
    setPage,
    setItemsPerPage,
    setSorting,
    handlePaginationRequest
  };
}