<template>
  <div class="enhanced-template-selector q-pa-md">
    <!-- En-tête avec options de filtrage avancé -->
    <div class="selector-header q-mb-md">
      <div class="row items-center justify-between q-mb-sm">
        <div class="text-h6">{{ title || 'Sélection de Template WhatsApp' }}</div>
        <q-btn 
          v-if="showAdvancedFilters" 
          flat 
          color="primary" 
          icon="tune" 
          @click="advancedFiltersVisible = !advancedFiltersVisible"
          :label="advancedFiltersVisible ? 'Masquer les filtres' : 'Filtres avancés'"
        />
      </div>
      
      <!-- Barre de recherche principale -->
      <q-input 
        v-model="searchQuery" 
        outlined 
        dense
        placeholder="Rechercher un template..." 
        clearable
        class="q-mb-sm"
      >
        <template v-slot:prepend>
          <q-icon name="search" />
        </template>
        <template v-slot:append>
          <q-btn 
            v-if="searchQuery" 
            flat 
            round 
            dense 
            icon="close" 
            @click="searchQuery = ''"
          />
        </template>
      </q-input>
    </div>
    
    <!-- Filtres avancés -->
    <q-slide-transition>
      <div v-show="advancedFiltersVisible" class="advanced-filters q-mb-md q-pa-md rounded-borders bg-grey-2">
        <div class="row q-col-gutter-md">
          <!-- Filtre par catégorie -->
          <div class="col-12 col-md-4">
            <q-select
              v-model="selectedCategory"
              :options="availableCategories"
              outlined
              dense
              label="Catégorie"
              emit-value
              map-options
              clearable
              :options-dense="true"
            />
          </div>
          
          <!-- Filtre par langue -->
          <div class="col-12 col-md-4">
            <q-select
              v-model="selectedLanguage"
              :options="availableLanguages"
              outlined
              dense
              label="Langue"
              emit-value
              map-options
              clearable
              :options-dense="true"
            />
          </div>
          
          <!-- Filtre par type d'en-tête -->
          <div class="col-12 col-md-4">
            <q-select
              v-model="selectedHeaderType"
              :options="headerTypeOptions"
              outlined
              dense
              label="Type d'en-tête"
              emit-value
              map-options
              clearable
              :options-dense="true"
            />
          </div>
          
          <!-- Filtre par nombre de variables -->
          <div class="col-12 col-md-4">
            <q-select
              v-model="selectedVariablesRange"
              :options="variablesRangeOptions"
              outlined
              dense
              label="Nombre de variables"
              emit-value
              map-options
              clearable
              :options-dense="true"
            />
          </div>
          
          <!-- Toggle pour boutons -->
          <div class="col-12 col-md-4 q-pt-md">
            <q-toggle
              v-model="hasButtons"
              label="Avec boutons"
            />
          </div>
          
          <!-- Toggle pour média d'en-tête -->
          <div class="col-12 col-md-4 q-pt-md">
            <q-toggle
              v-model="hasMediaHeader"
              label="Avec média d'en-tête"
            />
          </div>
        </div>
        
        <!-- Boutons d'action pour les filtres -->
        <div class="row justify-end q-mt-md">
          <q-btn flat color="grey" label="Réinitialiser" @click="resetFilters" class="q-mr-sm" />
          <q-btn color="primary" label="Appliquer" @click="applyFilters" />
        </div>
      </div>
    </q-slide-transition>
    
    <!-- Sections organisées (Récents, Favoris, Populaires) -->
    <div v-if="showOrganizedSections && !hideOrganizedSections" class="organized-sections q-mb-md">
      <!-- Section Templates Récents -->
      <div v-if="recentTemplates.length > 0" class="section-recent q-mb-md">
        <div class="section-header row items-center q-mb-sm">
          <div class="text-subtitle1 text-weight-medium">Récemment utilisés</div>
          <q-space />
          <q-btn flat dense round icon="chevron_right" @click="showAllRecents" />
        </div>
        
        <q-scroll-area style="height: 140px;">
          <div class="row q-col-gutter-sm">
            <div v-for="template in recentTemplates" :key="template.id" class="col-12 col-md-4 col-lg-3">
              <template-card 
                :template="template" 
                compact 
                @click="selectTemplate(template)" 
                @favorite="toggleFavorite(template)"
                :is-favorite="isTemplateFavorite(template.id)"
              />
            </div>
          </div>
        </q-scroll-area>
      </div>
      
      <!-- Section Templates Favoris -->
      <div v-if="favoriteTemplates.length > 0" class="section-favorites q-mb-md">
        <div class="section-header row items-center q-mb-sm">
          <div class="text-subtitle1 text-weight-medium">Mes favoris</div>
          <q-space />
          <q-btn flat dense round icon="chevron_right" @click="showAllFavorites" />
        </div>
        
        <q-scroll-area style="height: 140px;">
          <div class="row q-col-gutter-sm">
            <div v-for="template in favoriteTemplates" :key="template.id" class="col-12 col-md-4 col-lg-3">
              <template-card 
                :template="template" 
                compact 
                @click="selectTemplate(template)" 
                @favorite="toggleFavorite(template)"
                :is-favorite="true"
              />
            </div>
          </div>
        </q-scroll-area>
      </div>
      
      <!-- Section Templates Populaires -->
      <div v-if="popularTemplates.length > 0" class="section-popular q-mb-md">
        <div class="section-header row items-center q-mb-sm">
          <div class="text-subtitle1 text-weight-medium">Templates populaires</div>
          <q-space />
          <q-btn flat dense round icon="chevron_right" @click="showAllPopular" />
        </div>
        
        <q-scroll-area style="height: 140px;">
          <div class="row q-col-gutter-sm">
            <div v-for="template in popularTemplates" :key="template.id" class="col-12 col-md-4 col-lg-3">
              <template-card 
                :template="template" 
                compact 
                @click="selectTemplate(template)" 
                @favorite="toggleFavorite(template)"
                :is-favorite="isTemplateFavorite(template.id)"
                :show-usage-count="true"
              />
            </div>
          </div>
        </q-scroll-area>
      </div>
    </div>
    
    <!-- Liste principale des templates (filtrée) -->
    <div class="templates-list">
      <div v-if="!loading && filteredTemplates.length === 0" class="text-center q-pa-md">
        <q-icon name="search_off" size="2rem" color="grey-7" />
        <div class="text-grey-7 q-mt-sm">Aucun template correspondant aux critères de recherche</div>
      </div>
      
      <div v-else-if="loading" class="text-center q-pa-md">
        <q-spinner color="primary" size="2rem" />
        <div class="text-grey-7 q-mt-sm">Chargement des templates...</div>
      </div>
      
      <template v-else>
        <!-- Affichage par catégorie -->
        <div v-if="groupByCategory">
          <div v-for="(templates, category) in groupedTemplates" :key="category" class="q-mb-lg">
            <div class="text-subtitle1 text-weight-medium q-mb-sm">{{ category }}</div>
            <div class="row q-col-gutter-md">
              <div v-for="template in templates" :key="template.id" class="col-12 col-md-4 col-lg-3">
                <template-card 
                  :template="template" 
                  @click="selectTemplate(template)" 
                  @favorite="toggleFavorite(template)"
                  :is-favorite="isTemplateFavorite(template.id)"
                  :show-buttons="showButtons"
                  :show-variables="showVariables"
                  :show-header-type="showHeaderType"
                />
              </div>
            </div>
          </div>
        </div>
        
        <!-- Affichage en liste plate -->
        <div v-else class="row q-col-gutter-md">
          <div v-for="template in filteredTemplates" :key="template.id" class="col-12 col-md-4 col-lg-3">
            <template-card 
              :template="template" 
              @click="selectTemplate(template)" 
              @favorite="toggleFavorite(template)"
              :is-favorite="isTemplateFavorite(template.id)"
              :show-buttons="showButtons"
              :show-variables="showVariables"
              :show-header-type="showHeaderType"
            />
          </div>
        </div>
        
        <!-- Pagination -->
        <div v-if="showPagination && filteredTemplates.length > 0" class="row justify-center q-mt-md">
          <q-pagination
            v-model="currentPage"
            :max="totalPages"
            :max-pages="6"
            direction-links
            boundary-links
          />
        </div>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, defineProps, defineEmits } from 'vue';
import { useWhatsAppTemplateStore, WhatsAppTemplate } from '@/stores/whatsappTemplateStore';
import TemplateCard from './TemplateCard.vue';

// Props
const props = defineProps({
  title: {
    type: String,
    default: ''
  },
  showAdvancedFilters: {
    type: Boolean,
    default: true
  },
  showOrganizedSections: {
    type: Boolean,
    default: true
  },
  showPagination: {
    type: Boolean,
    default: true
  },
  groupByCategory: {
    type: Boolean,
    default: false
  },
  showButtons: {
    type: Boolean,
    default: true
  },
  showVariables: {
    type: Boolean,
    default: true
  },
  showHeaderType: {
    type: Boolean,
    default: true
  },
  preselectedCategory: {
    type: String,
    default: ''
  },
  preselectedLanguage: {
    type: String,
    default: ''
  }
});

// Emits
const emit = defineEmits(['select', 'filter-change']);

// Store
const store = useWhatsAppTemplateStore();

// État local
const searchQuery = ref('');
const advancedFiltersVisible = ref(false);
const currentPage = ref(1);
const loading = ref(false);
const hideOrganizedSections = ref(false);

// Filtres avancés
const selectedCategory = ref(props.preselectedCategory || '');
const selectedLanguage = ref(props.preselectedLanguage || '');
const selectedHeaderType = ref('');
const selectedVariablesRange = ref('');
const hasButtons = ref(false);
const hasMediaHeader = ref(false);

// Options pour les filtres
const headerTypeOptions = [
  { label: 'Texte', value: 'TEXT' },
  { label: 'Image', value: 'IMAGE' },
  { label: 'Vidéo', value: 'VIDEO' },
  { label: 'Document', value: 'DOCUMENT' }
];

const variablesRangeOptions = [
  { label: 'Aucune variable', value: 'none' },
  { label: '1 variable', value: '1' },
  { label: '2 variables', value: '2' },
  { label: '3 variables', value: '3' },
  { label: '4+ variables', value: 'many' }
];

// Computed values
const availableCategories = computed(() => {
  const categories = store.availableCategories;
  return categories.map(category => ({
    label: category,
    value: category
  }));
});

const availableLanguages = computed(() => {
  // Extraire les langues uniques des templates
  const languages = new Set<string>();
  store.templates.forEach(template => {
    languages.add(template.language);
  });
  
  return Array.from(languages).sort().map(lang => ({
    label: lang,
    value: lang
  }));
});

const recentTemplates = computed(() => {
  return store.recentTemplates;
});

const favoriteTemplates = computed(() => {
  // Récupérer les templates correspondant aux IDs des favoris
  const favoriteIds = store.favoriteTemplates.map(fav => fav.templateId);
  return store.templates.filter(template => favoriteIds.includes(template.id));
});

const popularTemplates = computed(() => {
  return store.mostUsedTemplates;
});

const filteredTemplates = computed(() => {
  let filtered = [...store.templates];
  
  // Filtre par recherche
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(template => 
      template.name.toLowerCase().includes(query) || 
      template.description.toLowerCase().includes(query) ||
      (template.fullBodyText && template.fullBodyText.toLowerCase().includes(query))
    );
  }
  
  // Appliquer les filtres avancés localement pour une réponse rapide
  if (selectedCategory.value) {
    filtered = filtered.filter(template => template.category === selectedCategory.value);
  }
  
  if (selectedLanguage.value) {
    filtered = filtered.filter(template => template.language === selectedLanguage.value);
  }
  
  if (selectedHeaderType.value) {
    filtered = filtered.filter(template => template.headerFormat === selectedHeaderType.value);
  }
  
  if (hasButtons.value) {
    filtered = filtered.filter(template => template.hasButtons);
  }
  
  if (hasMediaHeader.value) {
    filtered = filtered.filter(template => template.hasMediaHeader);
  }
  
  if (selectedVariablesRange.value) {
    if (selectedVariablesRange.value === 'none') {
      filtered = filtered.filter(template => template.bodyVariablesCount === 0);
    } else if (selectedVariablesRange.value === 'many') {
      filtered = filtered.filter(template => template.bodyVariablesCount >= 4);
    } else {
      const count = parseInt(selectedVariablesRange.value);
      filtered = filtered.filter(template => template.bodyVariablesCount === count);
    }
  }
  
  return filtered;
});

const totalPages = computed(() => {
  return Math.ceil(filteredTemplates.value.length / 10);
});

const groupedTemplates = computed(() => {
  // Groupe les templates par catégorie
  const grouped: Record<string, WhatsAppTemplate[]> = {};
  
  filteredTemplates.value.forEach(template => {
    const category = template.category || 'Non catégorisé';
    if (!grouped[category]) {
      grouped[category] = [];
    }
    grouped[category].push(template);
  });
  
  return grouped;
});

// Méthodes
function selectTemplate(template: WhatsAppTemplate) {
  // Ajouter le template aux récemment utilisés
  store.addRecentlyUsedTemplate(template);
  
  // Émettre l'événement de sélection
  emit('select', template);
}

function toggleFavorite(template: WhatsAppTemplate) {
  if (store.isTemplateFavorite(template.id)) {
    store.removeTemplateFromFavorites(template.id);
  } else {
    store.addTemplateToFavorites(template);
  }
}

function isTemplateFavorite(templateId: string): boolean {
  return store.isTemplateFavorite(templateId);
}

function resetFilters() {
  searchQuery.value = '';
  selectedCategory.value = '';
  selectedLanguage.value = '';
  selectedHeaderType.value = '';
  selectedVariablesRange.value = '';
  hasButtons.value = false;
  hasMediaHeader.value = false;
  
  // Émettre l'événement
  emit('filter-change', {});
}

function applyFilters() {
  // Construire l'objet de filtre
  const filters = {
    name: searchQuery.value || undefined,
    category: selectedCategory.value || undefined,
    language: selectedLanguage.value || undefined,
    headerFormat: selectedHeaderType.value || undefined,
    hasHeaderMedia: hasMediaHeader.value || undefined,
    hasButtons: hasButtons.value || undefined
  };
  
  // Ajouter le filtre de variables
  if (selectedVariablesRange.value) {
    if (selectedVariablesRange.value === 'none') {
      filters.minVariables = 0;
      filters.maxVariables = 0;
    } else if (selectedVariablesRange.value === 'many') {
      filters.minVariables = 4;
    } else {
      const count = parseInt(selectedVariablesRange.value);
      filters.minVariables = count;
      filters.maxVariables = count;
    }
  }
  
  // Émettre l'événement
  emit('filter-change', filters);
  
  // Fermer le panneau des filtres
  advancedFiltersVisible.value = false;
  
  // Masquer les sections organisées pour avoir plus d'espace
  if (filteredTemplates.value.length > 0) {
    hideOrganizedSections.value = true;
  }
}

function showAllRecents() {
  // Mettre à jour les filtres pour n'afficher que les templates récents
  selectedCategory.value = '';
  selectedLanguage.value = '';
  searchQuery.value = '';
  hideOrganizedSections.value = true;
  
  // Filtrer pour n'afficher que les templates récents
  const recentIds = store.recentTemplates.map(t => t.id);
  const filters = {
    templateIds: recentIds
  };
  
  emit('filter-change', filters);
}

function showAllFavorites() {
  // Mettre à jour les filtres pour n'afficher que les templates favoris
  selectedCategory.value = '';
  selectedLanguage.value = '';
  searchQuery.value = '';
  hideOrganizedSections.value = true;
  
  // Filtrer pour n'afficher que les templates favoris
  const favoriteIds = store.favoriteTemplates.map(fav => fav.templateId);
  const filters = {
    templateIds: favoriteIds
  };
  
  emit('filter-change', filters);
}

function showAllPopular() {
  // Mettre à jour les filtres pour n'afficher que les templates populaires
  selectedCategory.value = '';
  selectedLanguage.value = '';
  searchQuery.value = '';
  hideOrganizedSections.value = true;
  
  // Filtrer pour n'afficher que les templates populaires
  const filters = {
    minUsageCount: 5
  };
  
  emit('filter-change', filters);
}

// Observateurs
watch(searchQuery, (newValue) => {
  if (!newValue) {
    // Réafficher les sections organisées quand on efface la recherche
    hideOrganizedSections.value = false;
  }
});

// Cycle de vie
onMounted(async () => {
  loading.value = true;
  
  // Initialiser le store
  store.initialize();
  
  // Si des préférences ont été spécifiées via les props, les appliquer
  if (props.preselectedCategory || props.preselectedLanguage) {
    const filters = {
      category: props.preselectedCategory || undefined,
      language: props.preselectedLanguage || undefined
    };
    
    // Appliquer les filtres
    await store.searchTemplates(filters);
    
    // Émettre l'événement
    emit('filter-change', filters);
  } else {
    // Sinon, charger tous les templates
    await store.fetchTemplates();
  }
  
  loading.value = false;
});
</script>

<style scoped>
.enhanced-template-selector {
  max-width: 1200px;
  margin: 0 auto;
}

.advanced-filters {
  border-radius: 8px;
  border: 1px solid #e0e0e0;
}

.section-header {
  border-bottom: 1px solid #f0f0f0;
  padding-bottom: 4px;
}
</style>