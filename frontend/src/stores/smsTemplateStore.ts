import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { useNotification } from '../components/NotificationService';
import { useAuthStore } from './authStore';
import { useUserStore } from './userStore';

interface SMSTemplate {
  id: string;
  userId: number;
  title: string;
  content: string;
  description: string | null;
  createdAt: string;
  updatedAt: string;
  variables: string[];
}

interface SMSTemplateInput {
  title: string;
  content: string;
  description?: string | null;
}

export const useSMSTemplateStore = defineStore('smsTemplate', () => {
  // État
  const templates = ref<SMSTemplate[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);
  const totalCount = ref(0);
  const currentPage = ref(1);
  const itemsPerPage = ref(10);
  const searchQuery = ref('');
  const selectedTemplate = ref<SMSTemplate | null>(null);

  // Notifications
  const notification = useNotification();
  
  // User store pour l'ID utilisateur
  const userStore = useUserStore();

  // Getters
  const filteredTemplates = computed(() => {
    if (!searchQuery.value) return templates.value;
    
    const query = searchQuery.value.toLowerCase();
    return templates.value.filter(template => 
      template.title.toLowerCase().includes(query) || 
      template.content.toLowerCase().includes(query) ||
      (template.description && template.description.toLowerCase().includes(query))
    );
  });

  const paginatedTemplates = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredTemplates.value.slice(start, end);
  });

  const pageCount = computed(() => {
    return Math.ceil(filteredTemplates.value.length / itemsPerPage.value);
  });

  // Méthode pour appliquer un modèle et remplacer les variables
  function applyTemplate(template: SMSTemplate, variables: Record<string, string> = {}): string {
    if (!template) return '';
    
    let content = template.content;
    
    // Remplacer les variables dans le contenu
    for (const [key, value] of Object.entries(variables)) {
      const regex = new RegExp(`\\{\\{${key}\\}\\}`, 'g');
      content = content.replace(regex, value);
    }
    
    return content;
  }

  // Actions
  async function fetchTemplates() {
    if (!userStore.currentUser) return;
    
    loading.value = true;
    error.value = null;
    
    try {
      const query = `
        query GetSMSTemplates($userId: Int!, $limit: Int!, $offset: Int!) {
          getSMSTemplatesByUserId(userId: $userId, limit: $limit, offset: $offset) {
            id
            userId
            title
            content
            description
            createdAt
            updatedAt
            variables
          }
          countSMSTemplatesByUserId(userId: $userId)
        }
      `;
      
      const variables = {
        userId: userStore.currentUser.id,
        limit: 100, // Récupérer un grand nombre pour la pagination côté client
        offset: 0
      };
      
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
        },
        body: JSON.stringify({
          query,
          variables
        })
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      templates.value = result.data.getSMSTemplatesByUserId;
      totalCount.value = result.data.countSMSTemplatesByUserId;
    } catch (err: any) {
      error.value = err.message;
      notification.error('Erreur lors du chargement des modèles de SMS', err.message);
    } finally {
      loading.value = false;
    }
  }

  async function searchTemplates(search: string) {
    if (!userStore.currentUser) return;
    
    loading.value = true;
    error.value = null;
    searchQuery.value = search;
    
    try {
      const query = `
        query SearchSMSTemplates($userId: Int!, $search: String!, $limit: Int!, $offset: Int!) {
          searchSMSTemplates(userId: $userId, search: $search, limit: $limit, offset: $offset) {
            id
            userId
            title
            content
            description
            createdAt
            updatedAt
            variables
          }
        }
      `;
      
      const variables = {
        userId: userStore.currentUser.id,
        search,
        limit: 100,
        offset: 0
      };
      
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
        },
        body: JSON.stringify({
          query,
          variables
        })
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      templates.value = result.data.searchSMSTemplates;
    } catch (err: any) {
      error.value = err.message;
      notification.error('Erreur lors de la recherche des modèles de SMS', err.message);
    } finally {
      loading.value = false;
    }
  }

  async function getTemplateById(id: string) {
    if (!userStore.currentUser) return null;
    
    loading.value = true;
    error.value = null;
    
    try {
      const query = `
        query GetSMSTemplate($id: ID!) {
          getSMSTemplate(id: $id) {
            id
            userId
            title
            content
            description
            createdAt
            updatedAt
            variables
          }
        }
      `;
      
      const variables = {
        id
      };
      
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
        },
        body: JSON.stringify({
          query,
          variables
        })
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      selectedTemplate.value = result.data.getSMSTemplate;
      return result.data.getSMSTemplate;
    } catch (err: any) {
      error.value = err.message;
      notification.error('Erreur lors du chargement du modèle de SMS', err.message);
      return null;
    } finally {
      loading.value = false;
    }
  }

  async function createTemplate(templateData: SMSTemplateInput) {
    if (!userStore.currentUser) return null;
    
    loading.value = true;
    error.value = null;
    
    try {
      const query = `
        mutation CreateSMSTemplate($title: String!, $content: String!, $userId: Int!, $description: String) {
          createSMSTemplate(title: $title, content: $content, userId: $userId, description: $description) {
            id
            userId
            title
            content
            description
            createdAt
            updatedAt
            variables
          }
        }
      `;
      
      const variables = {
        title: templateData.title,
        content: templateData.content,
        userId: userStore.currentUser.id,
        description: templateData.description || null
      };
      
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
        },
        body: JSON.stringify({
          query,
          variables
        })
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      const newTemplate = result.data.createSMSTemplate;
      templates.value.push(newTemplate);
      notification.success('Modèle de SMS créé', 'Le modèle de SMS a été créé avec succès.');
      
      return newTemplate;
    } catch (err: any) {
      error.value = err.message;
      notification.error('Erreur lors de la création du modèle de SMS', err.message);
      return null;
    } finally {
      loading.value = false;
    }
  }

  async function updateTemplate(id: string, templateData: SMSTemplateInput) {
    if (!userStore.currentUser) return null;
    
    loading.value = true;
    error.value = null;
    
    try {
      const query = `
        mutation UpdateSMSTemplate($id: ID!, $title: String!, $content: String!, $userId: Int!, $description: String) {
          updateSMSTemplate(id: $id, title: $title, content: $content, userId: $userId, description: $description) {
            id
            userId
            title
            content
            description
            createdAt
            updatedAt
            variables
          }
        }
      `;
      
      const variables = {
        id,
        title: templateData.title,
        content: templateData.content,
        userId: userStore.currentUser.id,
        description: templateData.description || null
      };
      
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
        },
        body: JSON.stringify({
          query,
          variables
        })
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      const updatedTemplate = result.data.updateSMSTemplate;
      
      // Mettre à jour le template dans la liste
      const index = templates.value.findIndex(t => t.id === id);
      if (index !== -1) {
        templates.value[index] = updatedTemplate;
      }
      
      notification.success('Modèle de SMS mis à jour', 'Le modèle de SMS a été mis à jour avec succès.');
      
      return updatedTemplate;
    } catch (err: any) {
      error.value = err.message;
      notification.error('Erreur lors de la mise à jour du modèle de SMS', err.message);
      return null;
    } finally {
      loading.value = false;
    }
  }

  async function deleteTemplate(id: string) {
    if (!userStore.currentUser) return false;
    
    loading.value = true;
    error.value = null;
    
    try {
      const query = `
        mutation DeleteSMSTemplate($id: ID!) {
          deleteSMSTemplate(id: $id)
        }
      `;
      
      const variables = {
        id
      };
      
      const response = await fetch('/graphql.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
        },
        body: JSON.stringify({
          query,
          variables
        })
      });
      
      const result = await response.json();
      
      if (result.errors) {
        throw new Error(result.errors[0].message);
      }
      
      const success = result.data.deleteSMSTemplate;
      
      if (success) {
        // Supprimer le template de la liste
        templates.value = templates.value.filter(t => t.id !== id);
        notification.success('Modèle de SMS supprimé', 'Le modèle de SMS a été supprimé avec succès.');
      }
      
      return success;
    } catch (err: any) {
      error.value = err.message;
      notification.error('Erreur lors de la suppression du modèle de SMS', err.message);
      return false;
    } finally {
      loading.value = false;
    }
  }

  function setPage(page: number) {
    currentPage.value = page;
  }

  function setItemsPerPage(items: number) {
    itemsPerPage.value = items;
    currentPage.value = 1; // Réinitialiser à la première page
  }

  function clearSearch() {
    searchQuery.value = '';
  }

  function selectTemplate(template: SMSTemplate | null) {
    selectedTemplate.value = template;
  }

  // Initialisation
  function init() {
    fetchTemplates();
  }

  return {
    // État
    templates,
    loading,
    error,
    totalCount,
    currentPage,
    itemsPerPage,
    searchQuery,
    selectedTemplate,
    
    // Getters
    filteredTemplates,
    paginatedTemplates,
    pageCount,
    
    // Actions
    fetchTemplates,
    searchTemplates,
    getTemplateById,
    createTemplate,
    updateTemplate,
    deleteTemplate,
    setPage,
    setItemsPerPage,
    clearSearch,
    selectTemplate,
    applyTemplate,
    init
  };
});
