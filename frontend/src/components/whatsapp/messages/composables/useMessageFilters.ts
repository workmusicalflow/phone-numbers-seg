/**
 * Composable pour la gestion des filtres de messages
 */

import { ref, computed, Ref } from 'vue';
import type { WhatsAppMessageHistory } from '../../../../stores/whatsappStore';
import { STATUS_OPTIONS, DIRECTION_OPTIONS } from '../utils/messageConstants';

export interface FilterState {
  phoneFilter: Ref<string>;
  statusFilter: Ref<string>;
  directionFilter: Ref<string>;
  dateFilter: Ref<string>;
}

export interface ActiveFilter {
  type: string;
  label: string;
  value: string;
}

export function useMessageFilters(messages: Ref<WhatsAppMessageHistory[]>) {
  // État des filtres
  const phoneFilter = ref('');
  const statusFilter = ref('');
  const directionFilter = ref('');
  const dateFilter = ref('');
  
  // Filtres actifs
  const activeFilters = computed<ActiveFilter[]>(() => {
    const filters: ActiveFilter[] = [];
    
    if (phoneFilter.value) {
      filters.push({ type: 'phone', label: 'Numéro', value: phoneFilter.value });
    }
    
    if (statusFilter.value) {
      const statusOption = STATUS_OPTIONS.find(opt => opt.value === statusFilter.value);
      filters.push({ 
        type: 'status', 
        label: 'Statut', 
        value: statusOption?.label || statusFilter.value 
      });
    }
    
    if (directionFilter.value) {
      const directionOption = DIRECTION_OPTIONS.find(opt => opt.value === directionFilter.value);
      filters.push({ 
        type: 'direction', 
        label: 'Direction', 
        value: directionOption?.label || directionFilter.value 
      });
    }
    
    if (dateFilter.value) {
      filters.push({ 
        type: 'date', 
        label: 'Date', 
        value: formatDateForDisplay(dateFilter.value) 
      });
    }
    
    return filters;
  });
  
  // Indicateur de filtres actifs
  const hasActiveFilters = computed(() => activeFilters.value.length > 0);
  
  // Messages filtrés
  const filteredMessages = computed(() => {
    let filtered = [...messages.value];
    
    // Filtre par numéro de téléphone
    if (phoneFilter.value) {
      const normalizedFilter = phoneFilter.value.replace(/\s/g, '');
      filtered = filtered.filter(msg => 
        msg.phoneNumber.replace(/\s/g, '').includes(normalizedFilter)
      );
    }
    
    // Filtre par statut
    if (statusFilter.value) {
      filtered = filtered.filter(msg => msg.status === statusFilter.value);
    }
    
    // Filtre par direction
    if (directionFilter.value) {
      filtered = filtered.filter(msg => msg.direction === directionFilter.value);
    }
    
    // Filtre par date
    if (dateFilter.value) {
      const filterDate = new Date(dateFilter.value);
      filterDate.setHours(0, 0, 0, 0);
      const nextDay = new Date(filterDate);
      nextDay.setDate(nextDay.getDate() + 1);
      
      filtered = filtered.filter(msg => {
        const msgDate = new Date(msg.timestamp);
        return msgDate >= filterDate && msgDate < nextDay;
      });
    }
    
    // Tri par date décroissante
    filtered.sort((a, b) => 
      new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime()
    );
    
    return filtered;
  });
  
  // Méthodes
  function clearFilter(type: string) {
    switch (type) {
      case 'phone':
        phoneFilter.value = '';
        break;
      case 'status':
        statusFilter.value = '';
        break;
      case 'direction':
        directionFilter.value = '';
        break;
      case 'date':
        dateFilter.value = '';
        break;
    }
  }
  
  function clearAllFilters() {
    phoneFilter.value = '';
    statusFilter.value = '';
    directionFilter.value = '';
    dateFilter.value = '';
  }
  
  function applyFilters() {
    // Cette fonction peut être utilisée pour déclencher une action après l'application des filtres
    // Par exemple, réinitialiser la pagination
  }
  
  function filterByPhone(phone: string) {
    phoneFilter.value = phone;
    applyFilters();
  }
  
  return {
    // État
    phoneFilter,
    statusFilter,
    directionFilter,
    dateFilter,
    
    // Computed
    activeFilters,
    hasActiveFilters,
    filteredMessages,
    
    // Méthodes
    clearFilter,
    clearAllFilters,
    applyFilters,
    filterByPhone
  };
}

// Fonction utilitaire privée
function formatDateForDisplay(dateString: string): string {
  const date = new Date(dateString);
  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  });
}