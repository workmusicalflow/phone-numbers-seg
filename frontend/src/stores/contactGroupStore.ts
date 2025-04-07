import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { api } from '../services/api';

interface ContactGroup {
  id: string;
  name: string;
  description: string | null;
  contactCount: number;
  createdAt: string;
  updatedAt: string;
}

interface ContactGroupCreateData {
  name: string;
  description: string | null;
}

export const useContactGroupStore = defineStore('contactGroup', () => {
  // État
  const groups = ref<ContactGroup[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);
  const currentPage = ref(1);
  const itemsPerPage = ref(10);
  const totalCount = ref(0);
  const searchTerm = ref('');

  // Getters
  const filteredGroups = computed(() => {
    if (!searchTerm.value) {
      return groups.value;
    }
    
    const term = searchTerm.value.toLowerCase();
    return groups.value.filter(group => 
      group.name.toLowerCase().includes(term) ||
      (group.description && group.description.toLowerCase().includes(term))
    );
  });

  const paginatedGroups = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredGroups.value.slice(start, end);
  });

  const pageCount = computed(() => {
    return Math.ceil(filteredGroups.value.length / itemsPerPage.value);
  });

  // Actions
  async function fetchGroups() {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await api.get('/contact-groups', {
        params: {
          page: currentPage.value,
          limit: itemsPerPage.value
        }
      });
      
      groups.value = response.data.groups;
      totalCount.value = response.data.totalCount;
    } catch (err: any) {
      console.error('Erreur lors de la récupération des groupes de contacts:', err);
      error.value = err.message || 'Erreur lors de la récupération des groupes de contacts';
    } finally {
      loading.value = false;
    }
  }

  async function createGroup(groupData: ContactGroupCreateData) {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await api.post('/contact-groups', groupData);
      groups.value.push(response.data);
      totalCount.value++;
      return response.data;
    } catch (err: any) {
      console.error('Erreur lors de la création du groupe de contacts:', err);
      error.value = err.message || 'Erreur lors de la création du groupe de contacts';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function updateGroup(id: string, groupData: Partial<ContactGroupCreateData>) {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await api.put(`/contact-groups/${id}`, groupData);
      const index = groups.value.findIndex(g => g.id === id);
      if (index !== -1) {
        groups.value[index] = response.data;
      }
      return response.data;
    } catch (err: any) {
      console.error('Erreur lors de la mise à jour du groupe de contacts:', err);
      error.value = err.message || 'Erreur lors de la mise à jour du groupe de contacts';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function deleteGroup(id: string) {
    loading.value = true;
    error.value = null;
    
    try {
      await api.delete(`/contact-groups/${id}`);
      groups.value = groups.value.filter(g => g.id !== id);
      totalCount.value--;
    } catch (err: any) {
      console.error('Erreur lors de la suppression du groupe de contacts:', err);
      error.value = err.message || 'Erreur lors de la suppression du groupe de contacts';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  function searchGroups(term: string) {
    searchTerm.value = term;
    currentPage.value = 1; // Réinitialiser à la première page lors d'une recherche
  }

  function setPage(page: number) {
    currentPage.value = page;
    fetchGroups();
  }

  function setItemsPerPage(limit: number) {
    itemsPerPage.value = limit;
    fetchGroups();
  }

  return {
    groups,
    loading,
    error,
    currentPage,
    itemsPerPage,
    totalCount,
    filteredGroups,
    paginatedGroups,
    pageCount,
    fetchGroups,
    createGroup,
    updateGroup,
    deleteGroup,
    searchGroups,
    setPage,
    setItemsPerPage
  };
});
