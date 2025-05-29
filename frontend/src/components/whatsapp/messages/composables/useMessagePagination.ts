/**
 * Composable pour la gestion de la pagination
 */

import { ref, computed, Ref } from 'vue';
import { DEFAULT_PAGINATION } from '../utils/messageConstants';

export interface PaginationState {
  rowsPerPage: number;
  page: number;
  rowsNumber: number;
}

export function useMessagePagination<T>(items: Ref<T[]>) {
  // État de la pagination
  const pagination = ref<PaginationState>({
    ...DEFAULT_PAGINATION,
    rowsNumber: items.value.length
  });
  
  // Éléments paginés
  const paginatedItems = computed(() => {
    const start = (pagination.value.page - 1) * pagination.value.rowsPerPage;
    const end = start + pagination.value.rowsPerPage;
    return items.value.slice(start, end);
  });
  
  // Total des pages
  const totalPages = computed(() => {
    return Math.ceil(items.value.length / pagination.value.rowsPerPage);
  });
  
  // Label de pagination
  const paginationLabel = computed(() => {
    const start = (pagination.value.page - 1) * pagination.value.rowsPerPage + 1;
    const end = Math.min(start + pagination.value.rowsPerPage - 1, items.value.length);
    return `${start} - ${end} sur ${items.value.length} éléments`;
  });
  
  // Méthodes
  function updatePage(page: number) {
    pagination.value.page = page;
  }
  
  function updateRowsPerPage(rowsPerPage: number) {
    pagination.value.rowsPerPage = rowsPerPage;
    pagination.value.page = 1; // Retour à la première page
  }
  
  function onRequest(props: { pagination: { page: number; rowsPerPage: number } }) {
    const { page, rowsPerPage } = props.pagination;
    pagination.value.page = page;
    pagination.value.rowsPerPage = rowsPerPage;
  }
  
  function resetPagination() {
    pagination.value = {
      ...DEFAULT_PAGINATION,
      rowsNumber: items.value.length
    };
  }
  
  // Mettre à jour le nombre total d'éléments quand les items changent
  function updateRowsNumber() {
    pagination.value.rowsNumber = items.value.length;
    
    // Si la page actuelle dépasse le nouveau nombre de pages, revenir à la dernière page
    if (pagination.value.page > totalPages.value && totalPages.value > 0) {
      pagination.value.page = totalPages.value;
    }
  }
  
  return {
    // État
    pagination,
    
    // Computed
    paginatedItems,
    totalPages,
    paginationLabel,
    
    // Méthodes
    updatePage,
    updateRowsPerPage,
    onRequest,
    resetPagination,
    updateRowsNumber
  };
}