import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { apolloClient, gql } from '@/services/api';
import { whatsAppClient, WhatsAppTemplate as RestWhatsAppTemplate, TemplateFilters } from '@/services/whatsappRestClient';

// Types pour les templates WhatsApp enrichis
export interface WhatsAppTemplate {
  id: string;
  name: string;
  category: string;
  language: string;
  status: string;
  componentsJson: string;
  description: string;
  headerType: string | null;
  hasMediaHeader: boolean;
  bodyVariablesCount: number;
  buttonsCount: number;
  hasButtons: boolean;
  hasFooter: boolean;
  qualityScore: number | null;
  headerFormat: string | null;
  fullBodyText: string | null;
  footerText: string | null;
  buttonsDetailsJson: string | null;
  rejectionReason: string | null;
  usageCount: number;
  lastUsedAt: string | null;
  isPopular: boolean;
}

// Types pour les filtres de templates
export interface TemplateFilterInput {
  name?: string | null;
  language?: string | null;
  category?: string | null;
  status?: string | null;
  headerFormat?: string | null;
  hasHeaderMedia?: boolean | null;
  minVariables?: number | null;
  maxVariables?: number | null;
  hasButtons?: boolean | null;
  buttonCount?: number | null;
  hasFooter?: boolean | null;
  bodyText?: string | null;
  minUsageCount?: number | null;
  orderBy?: string | null;
  orderDirection?: string | null;
}

// Type pour l'historique d'utilisation des templates
export interface TemplateUsageHistory {
  templateId: string;
  templateName: string;
  recipientPhone: string;
  usedAt: string;
  parameters: Record<string, any>;
  messageId: string;
}

// Type complet pour un composant de template
export interface TemplateComponent {
  type: 'HEADER' | 'BODY' | 'FOOTER' | 'BUTTONS';
  format?: string;
  text?: string;
  buttons?: TemplateButton[];
}

// Type pour un bouton de template
export interface TemplateButton {
  type: string;
  text: string;
  payload?: string;
  url?: string;
}

// Type pour les favoris de templates
export interface TemplateFavorite {
  templateId: string;
  templateName: string;
  addedAt: string;
}

export const useWhatsAppTemplateStore = defineStore('whatsappTemplate', () => {
  // État
  const templates = ref<WhatsAppTemplate[]>([]);
  const favoriteTemplates = ref<TemplateFavorite[]>([]);
  const recentlyUsedTemplates = ref<WhatsAppTemplate[]>([]);
  const templateUsageHistory = ref<TemplateUsageHistory[]>([]);
  const isLoading = ref(false);
  const error = ref<string | null>(null);
  
  // Filtres
  const currentFilter = ref<TemplateFilterInput>({});
  const currentPage = ref(1);
  const pageSize = ref(10);
  const totalCount = ref(0);
  
  // Getters
  
  // Templates par catégorie
  const templatesByCategory = computed(() => {
    const categorized: Record<string, WhatsAppTemplate[]> = {};
    
    templates.value.forEach(template => {
      if (!categorized[template.category]) {
        categorized[template.category] = [];
      }
      categorized[template.category].push(template);
    });
    
    return categorized;
  });
  
  // Liste des catégories disponibles
  const availableCategories = computed(() => {
    return Object.keys(templatesByCategory.value).sort();
  });
  
  // Templates avec média d'en-tête
  const templatesWithMediaHeader = computed(() => {
    return templates.value.filter(t => t.hasMediaHeader);
  });
  
  // Templates avec boutons
  const templatesWithButtons = computed(() => {
    return templates.value.filter(t => t.hasButtons);
  });
  
  // Templates les plus utilisés
  const mostUsedTemplates = computed(() => {
    return [...templates.value]
      .filter(t => t.usageCount > 0)
      .sort((a, b) => b.usageCount - a.usageCount)
      .slice(0, 5);
  });
  
  // Templates récemment utilisés
  const recentTemplates = computed(() => {
    return recentlyUsedTemplates.value.slice(0, 5);
  });
  
  // Templates filtrés par utilisateur
  const filteredTemplates = computed(() => {
    let result = [...templates.value];
    
    if (currentFilter.value.name) {
      result = result.filter(t => 
        t.name.toLowerCase().includes(currentFilter.value.name?.toLowerCase() || '')
      );
    }
    
    if (currentFilter.value.category) {
      result = result.filter(t => t.category === currentFilter.value.category);
    }
    
    if (currentFilter.value.language) {
      result = result.filter(t => t.language === currentFilter.value.language);
    }
    
    if (currentFilter.value.status) {
      result = result.filter(t => t.status === currentFilter.value.status);
    }
    
    if (currentFilter.value.headerFormat) {
      result = result.filter(t => t.headerFormat === currentFilter.value.headerFormat);
    }
    
    if (currentFilter.value.hasHeaderMedia !== null) {
      result = result.filter(t => t.hasMediaHeader === currentFilter.value.hasHeaderMedia);
    }
    
    if (currentFilter.value.minVariables !== null) {
      result = result.filter(t => t.bodyVariablesCount >= (currentFilter.value.minVariables || 0));
    }
    
    if (currentFilter.value.maxVariables !== null) {
      result = result.filter(t => t.bodyVariablesCount <= (currentFilter.value.maxVariables || 0));
    }
    
    if (currentFilter.value.hasButtons !== null) {
      result = result.filter(t => t.hasButtons === currentFilter.value.hasButtons);
    }
    
    if (currentFilter.value.buttonCount !== null) {
      result = result.filter(t => t.buttonsCount === currentFilter.value.buttonCount);
    }
    
    if (currentFilter.value.hasFooter !== null) {
      result = result.filter(t => t.hasFooter === currentFilter.value.hasFooter);
    }
    
    if (currentFilter.value.bodyText) {
      result = result.filter(t => 
        t.fullBodyText?.toLowerCase().includes(currentFilter.value.bodyText?.toLowerCase() || '') || 
        t.description.toLowerCase().includes(currentFilter.value.bodyText?.toLowerCase() || '')
      );
    }
    
    if (currentFilter.value.minUsageCount !== null) {
      result = result.filter(t => t.usageCount >= (currentFilter.value.minUsageCount || 0));
    }
    
    // Appliquer le tri
    if (currentFilter.value.orderBy) {
      const field = currentFilter.value.orderBy;
      const direction = currentFilter.value.orderDirection === 'DESC' ? -1 : 1;
      
      result.sort((a, b) => {
        // @ts-ignore - Dynamically access property
        let valueA = a[field];
        // @ts-ignore - Dynamically access property
        let valueB = b[field];
        
        // Gérer les nombres
        if (typeof valueA === 'number' && typeof valueB === 'number') {
          return (valueA - valueB) * direction;
        }
        
        // Gérer les dates
        if (field === 'lastUsedAt') {
          valueA = valueA ? new Date(valueA).getTime() : 0;
          valueB = valueB ? new Date(valueB).getTime() : 0;
          return (valueA - valueB) * direction;
        }
        
        // Gérer les chaînes
        if (typeof valueA === 'string' && typeof valueB === 'string') {
          return valueA.localeCompare(valueB) * direction;
        }
        
        // Gérer les booléens
        if (typeof valueA === 'boolean' && typeof valueB === 'boolean') {
          return ((valueA === valueB) ? 0 : valueA ? 1 : -1) * direction;
        }
        
        return 0;
      });
    }
    
    return result;
  });
  
  // Templates paginés pour l'affichage
  const paginatedTemplates = computed(() => {
    const start = (currentPage.value - 1) * pageSize.value;
    const end = start + pageSize.value;
    return filteredTemplates.value.slice(start, end);
  });
  
  // Nombre total de pages
  const totalPages = computed(() => {
    return Math.ceil(filteredTemplates.value.length / pageSize.value);
  });
  
  // Actions
  
  // Charger tous les templates disponibles
  async function fetchTemplates() {
    isLoading.value = true;
    error.value = null;
    
    try {
      // Utiliser le client REST au lieu de GraphQL
      const response = await whatsAppClient.getApprovedTemplates();
      
      if (response.status === 'success' && Array.isArray(response.templates)) {
        // Transformer les templates REST en format compatible avec le store
        templates.value = response.templates.map(template => {
          return {
            id: template.id,
            name: template.name,
            category: template.category,
            language: template.language,
            status: template.status,
            componentsJson: template.componentsJson || JSON.stringify(template.components || []),
            description: template.description || '',
            headerType: extractHeaderType(template),
            hasMediaHeader: template.hasMediaHeader || false,
            bodyVariablesCount: template.bodyVariablesCount || 0,
            buttonsCount: template.buttonsCount || 0,
            hasButtons: template.hasButtons || false,
            hasFooter: template.hasFooter || false,
            qualityScore: null,
            headerFormat: extractHeaderFormat(template),
            fullBodyText: extractBodyText(template),
            footerText: extractFooterText(template),
            buttonsDetailsJson: extractButtonsJson(template),
            rejectionReason: null,
            usageCount: 0,
            lastUsedAt: null,
            isPopular: false
          } as WhatsAppTemplate;
        });
        
        totalCount.value = response.count || templates.value.length;
        
        // Log metadata pour monitoring
        console.log(`Templates chargés depuis ${response.meta.source}`, {
          usedFallback: response.meta.usedFallback,
          timestamp: response.meta.timestamp,
          count: response.count
        });
      } else if (response.status === 'error') {
        throw new Error(response.message || 'Erreur lors du chargement des templates');
      }
    } catch (err: any) {
      console.error('Erreur lors du chargement des templates:', err);
      error.value = err.message || 'Erreur lors du chargement des templates';
    } finally {
      isLoading.value = false;
    }
  }
  
  // Utilitaires pour extraire les informations des templates
  function extractHeaderType(template: RestWhatsAppTemplate): string | null {
    if (!template.components) return null;
    
    const header = template.components.find(comp => comp.type === 'HEADER');
    return header ? header.format || null : null;
  }
  
  function extractHeaderFormat(template: RestWhatsAppTemplate): string | null {
    if (!template.components) return null;
    
    const header = template.components.find(comp => comp.type === 'HEADER');
    return header ? header.format || null : null;
  }
  
  function extractBodyText(template: RestWhatsAppTemplate): string | null {
    if (!template.components) return null;
    
    const body = template.components.find(comp => comp.type === 'BODY');
    return body ? body.text || null : null;
  }
  
  function extractFooterText(template: RestWhatsAppTemplate): string | null {
    if (!template.components) return null;
    
    const footer = template.components.find(comp => comp.type === 'FOOTER');
    return footer ? footer.text || null : null;
  }
  
  function extractButtonsJson(template: RestWhatsAppTemplate): string | null {
    if (!template.components) return null;
    
    const buttons = template.components.find(comp => comp.type === 'BUTTONS');
    return buttons ? JSON.stringify(buttons) : null;
  }
  
  // Recherche avancée de templates avec filtrage serveur
  async function searchTemplates(filter: TemplateFilterInput, page: number = 1, limit: number = 10) {
    isLoading.value = true;
    error.value = null;
    currentFilter.value = filter;
    currentPage.value = page;
    pageSize.value = limit;
    
    try {
      // Convertir le filtre GraphQL en filtre REST
      const restFilters: TemplateFilters = {
        name: filter.name || undefined,
        language: filter.language || undefined,
        category: filter.category || undefined,
        status: filter.status || undefined,
        use_cache: true,  // Utiliser le cache par défaut
        force_refresh: false // Ne pas forcer le rafraîchissement par défaut
      };
      
      // Utiliser le client REST
      const response = await whatsAppClient.getApprovedTemplates(restFilters);
      
      if (response.status === 'success' && Array.isArray(response.templates)) {
        // Transformer les templates REST
        const mappedTemplates = response.templates.map(template => ({
          id: template.id,
          name: template.name,
          category: template.category,
          language: template.language,
          status: template.status,
          componentsJson: template.componentsJson || JSON.stringify(template.components || []),
          description: template.description || '',
          headerType: extractHeaderType(template),
          hasMediaHeader: template.hasMediaHeader || false,
          bodyVariablesCount: template.bodyVariablesCount || 0,
          buttonsCount: template.buttonsCount || 0,
          hasButtons: template.hasButtons || false,
          hasFooter: template.hasFooter || false,
          qualityScore: null,
          headerFormat: extractHeaderFormat(template),
          fullBodyText: extractBodyText(template),
          footerText: extractFooterText(template),
          buttonsDetailsJson: extractButtonsJson(template),
          rejectionReason: null,
          usageCount: 0,
          lastUsedAt: null,
          isPopular: false
        })) as WhatsAppTemplate[];
        
        // Appliquer les filtres avancés côté client
        let filteredResults = [...mappedTemplates];
        
        // Appliquer les filtres qui ne sont pas gérés par le backend REST
        if (filter.headerFormat) {
          filteredResults = filteredResults.filter(t => t.headerFormat === filter.headerFormat);
        }
        
        if (filter.hasHeaderMedia !== null) {
          filteredResults = filteredResults.filter(t => t.hasMediaHeader === filter.hasHeaderMedia);
        }
        
        if (filter.minVariables !== null) {
          filteredResults = filteredResults.filter(t => t.bodyVariablesCount >= (filter.minVariables || 0));
        }
        
        if (filter.maxVariables !== null) {
          filteredResults = filteredResults.filter(t => t.bodyVariablesCount <= (filter.maxVariables || 0));
        }
        
        if (filter.hasButtons !== null) {
          filteredResults = filteredResults.filter(t => t.hasButtons === filter.hasButtons);
        }
        
        if (filter.buttonCount !== null) {
          filteredResults = filteredResults.filter(t => t.buttonsCount === filter.buttonCount);
        }
        
        if (filter.hasFooter !== null) {
          filteredResults = filteredResults.filter(t => t.hasFooter === filter.hasFooter);
        }
        
        if (filter.bodyText) {
          filteredResults = filteredResults.filter(t => 
            t.fullBodyText?.toLowerCase().includes(filter.bodyText?.toLowerCase() || '') || 
            t.description.toLowerCase().includes(filter.bodyText?.toLowerCase() || '')
          );
        }
        
        if (filter.minUsageCount !== null) {
          filteredResults = filteredResults.filter(t => t.usageCount >= (filter.minUsageCount || 0));
        }
        
        // Appliquer le tri
        if (filter.orderBy) {
          const field = filter.orderBy;
          const direction = filter.orderDirection === 'DESC' ? -1 : 1;
          
          filteredResults.sort((a, b) => {
            // @ts-ignore - Dynamically access property
            let valueA = a[field];
            // @ts-ignore - Dynamically access property
            let valueB = b[field];
            
            // Gérer les nombres
            if (typeof valueA === 'number' && typeof valueB === 'number') {
              return (valueA - valueB) * direction;
            }
            
            // Gérer les dates
            if (field === 'lastUsedAt') {
              valueA = valueA ? new Date(valueA).getTime() : 0;
              valueB = valueB ? new Date(valueB).getTime() : 0;
              return (valueA - valueB) * direction;
            }
            
            // Gérer les chaînes
            if (typeof valueA === 'string' && typeof valueB === 'string') {
              return valueA.localeCompare(valueB) * direction;
            }
            
            // Gérer les booléens
            if (typeof valueA === 'boolean' && typeof valueB === 'boolean') {
              return ((valueA === valueB) ? 0 : valueA ? 1 : -1) * direction;
            }
            
            return 0;
          });
        }
        
        // Mise à jour des templates avec les résultats filtrés
        templates.value = filteredResults;
        totalCount.value = filteredResults.length;
      } else if (response.status === 'error') {
        throw new Error(response.message || 'Erreur lors de la recherche des templates');
      }
    } catch (err: any) {
      console.error('Erreur lors de la recherche des templates:', err);
      error.value = err.message || 'Erreur lors de la recherche des templates';
    } finally {
      isLoading.value = false;
    }
  }
  
  // Récupérer les templates les plus utilisés
  async function fetchMostUsedTemplates(limit: number = 5) {
    try {
      // Pour l'instant, nous utilisons l'API REST pour obtenir tous les templates
      // puis filtrer côté client
      // Note: Idéalement, l'API REST devrait avoir un endpoint dédié pour cela
      const response = await whatsAppClient.getApprovedTemplates({ 
        status: 'APPROVED',
        use_cache: true
      });
      
      if (response.status === 'success' && Array.isArray(response.templates)) {
        // Transformer les templates REST
        const mappedTemplates = response.templates
          .map(template => ({
            id: template.id,
            name: template.name,
            category: template.category,
            language: template.language,
            status: template.status,
            description: template.description || '',
            headerType: extractHeaderType(template),
            hasMediaHeader: template.hasMediaHeader || false,
            bodyVariablesCount: template.bodyVariablesCount || 0,
            usageCount: 0, // Pour l'instant, pas de données d'utilisation dans l'API REST
            lastUsedAt: null
          })) as WhatsAppTemplate[];
        
        // Trier par usageCount (simulé pour l'instant)
        // Quand l'API REST supportera les stats d'utilisation, mettre à jour ici
        return mappedTemplates
          .sort((a, b) => (b.usageCount || 0) - (a.usageCount || 0))
          .slice(0, limit);
      }
      
      return [];
    } catch (err: any) {
      console.error('Erreur lors du chargement des templates les plus utilisés:', err);
      return [];
    }
  }
  
  // Récupérer les templates par format d'en-tête
  async function fetchTemplatesByHeaderFormat(headerFormat: string, status: string = 'APPROVED') {
    try {
      // Obtenir tous les templates puis filtrer par format d'en-tête côté client
      const response = await whatsAppClient.getApprovedTemplates({ 
        status, 
        use_cache: true 
      });
      
      if (response.status === 'success' && Array.isArray(response.templates)) {
        // Transformer et filtrer les templates
        return response.templates
          .filter(template => {
            const header = template.components?.find(comp => comp.type === 'HEADER');
            return header && header.format === headerFormat;
          })
          .map(template => ({
            id: template.id,
            name: template.name,
            category: template.category,
            language: template.language,
            status: template.status,
            description: template.description || '',
            headerType: extractHeaderType(template),
            hasMediaHeader: template.hasMediaHeader || false,
            bodyVariablesCount: template.bodyVariablesCount || 0,
            usageCount: 0
          })) as WhatsAppTemplate[];
      }
      
      return [];
    } catch (err: any) {
      console.error(`Erreur lors du chargement des templates avec format d'en-tête ${headerFormat}:`, err);
      return [];
    }
  }
  
  // Charger l'historique d'utilisation des templates
  async function fetchTemplateUsageHistory() {
    try {
      // Note: Pour l'instant, nous devons utiliser GraphQL car l'API REST
      // n'a pas encore d'endpoint pour l'historique d'utilisation
      // TODO: Ajouter un endpoint REST pour l'historique et mettre à jour cette méthode
      
      const result = await apolloClient.query({
        query: gql`
          query GetWhatsAppTemplateUsageHistory {
            getWhatsAppTemplateUsageHistory {
              templateId
              templateName
              recipientPhone
              usedAt
              parameters
              messageId
            }
          }
        `,
        fetchPolicy: 'network-only'
      });
      
      if (result.data?.getWhatsAppTemplateUsageHistory) {
        templateUsageHistory.value = result.data.getWhatsAppTemplateUsageHistory;
      }
    } catch (err: any) {
      console.error('Erreur lors du chargement de l\'historique d\'utilisation des templates:', err);
      // En cas d'erreur, nous conservons les données existantes
      // TODO: Ajouter un état d'erreur spécifique pour l'historique
    }
  }
  
  // Ajouter un template aux favoris
  function addTemplateToFavorites(template: WhatsAppTemplate) {
    // Vérifier si le template est déjà dans les favoris
    const existingIndex = favoriteTemplates.value.findIndex(fav => fav.templateId === template.id);
    
    if (existingIndex === -1) {
      favoriteTemplates.value.push({
        templateId: template.id,
        templateName: template.name,
        addedAt: new Date().toISOString()
      });
      
      // Sauvegarder dans le localStorage
      localStorage.setItem('whatsappFavoriteTemplates', JSON.stringify(favoriteTemplates.value));
    }
  }
  
  // Retirer un template des favoris
  function removeTemplateFromFavorites(templateId: string) {
    favoriteTemplates.value = favoriteTemplates.value.filter(fav => fav.templateId !== templateId);
    
    // Mettre à jour le localStorage
    localStorage.setItem('whatsappFavoriteTemplates', JSON.stringify(favoriteTemplates.value));
  }
  
  // Vérifier si un template est dans les favoris
  function isTemplateFavorite(templateId: string): boolean {
    return favoriteTemplates.value.some(fav => fav.templateId === templateId);
  }
  
  // Ajouter un template récemment utilisé
  function addRecentlyUsedTemplate(template: WhatsAppTemplate) {
    // Retirer le template s'il existait déjà
    recentlyUsedTemplates.value = recentlyUsedTemplates.value.filter(t => t.id !== template.id);
    
    // Ajouter au début du tableau
    recentlyUsedTemplates.value.unshift(template);
    
    // Limiter à 10 templates
    if (recentlyUsedTemplates.value.length > 10) {
      recentlyUsedTemplates.value = recentlyUsedTemplates.value.slice(0, 10);
    }
    
    // Sauvegarder dans le localStorage
    localStorage.setItem('whatsappRecentlyUsedTemplates', JSON.stringify(recentlyUsedTemplates.value));
  }
  
  // Charger les favoris et récemment utilisés depuis le localStorage
  function loadSavedTemplatesPreferences() {
    try {
      const savedFavorites = localStorage.getItem('whatsappFavoriteTemplates');
      if (savedFavorites) {
        favoriteTemplates.value = JSON.parse(savedFavorites);
      }
      
      const savedRecents = localStorage.getItem('whatsappRecentlyUsedTemplates');
      if (savedRecents) {
        recentlyUsedTemplates.value = JSON.parse(savedRecents);
      }
    } catch (err) {
      console.error('Erreur lors du chargement des préférences de templates:', err);
    }
  }
  
  // Pagination
  function setPage(page: number) {
    currentPage.value = page;
  }
  
  function setPageSize(size: number) {
    pageSize.value = size;
    currentPage.value = 1;
  }
  
  // Réinitialiser les filtres
  function resetFilters() {
    currentFilter.value = {};
    currentPage.value = 1;
  }
  
  // Initialisation
  function initialize() {
    loadSavedTemplatesPreferences();
    fetchTemplates();
  }
  
  return {
    // État
    templates,
    favoriteTemplates,
    recentlyUsedTemplates,
    templateUsageHistory,
    isLoading,
    error,
    currentFilter,
    currentPage,
    pageSize,
    totalCount,
    
    // Getters
    templatesByCategory,
    availableCategories,
    templatesWithMediaHeader,
    templatesWithButtons,
    mostUsedTemplates,
    recentTemplates,
    filteredTemplates,
    paginatedTemplates,
    totalPages,
    
    // Actions
    fetchTemplates,
    searchTemplates,
    fetchMostUsedTemplates,
    fetchTemplatesByHeaderFormat,
    fetchTemplateUsageHistory,
    addTemplateToFavorites,
    removeTemplateFromFavorites,
    isTemplateFavorite,
    addRecentlyUsedTemplate,
    loadSavedTemplatesPreferences,
    setPage,
    setPageSize,
    resetFilters,
    initialize
  };
});