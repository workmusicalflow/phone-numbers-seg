<template>
  <q-card 
    class="template-card cursor-pointer" 
    :class="{ 'template-card--compact': compact }"
    @click="$emit('click', template)"
  >
    <q-card-section class="q-pb-xs">
      <div class="row items-center justify-between">
        <!-- En-tête avec nom et badge de catégorie -->
        <div class="text-subtitle1 template-name ellipsis" :title="template.name">
          {{ template.name }}
        </div>
        
        <!-- Badge de catégorie -->
        <q-badge 
          :color="getCategoryColor(template.category)" 
          text-color="white" 
          class="q-ml-sm"
          :title="template.category"
        >
          {{ getCategoryLabel(template.category) }}
        </q-badge>
      </div>
      
      <!-- Langue -->
      <div class="text-caption text-grey-7">
        {{ template.language }}
      </div>
    </q-card-section>
    
    <q-separator />
    
    <q-card-section class="q-pt-xs q-pb-xs flex-grow-1">
      <!-- Description -->
      <div class="template-description text-body2" :class="{ 'template-description--compact': compact }">
        {{ template.description }}
      </div>
      
      <!-- Informations sur les variables, boutons et format d'en-tête -->
      <div class="row q-mt-xs items-center text-grey-7 text-caption" v-if="!compact">
        <!-- Type d'en-tête -->
        <div v-if="showHeaderType" class="q-mr-md">
          <q-icon 
            :name="getHeaderTypeIcon(template.headerType)" 
            size="xs" 
            class="q-mr-xs" 
          />
          {{ getHeaderTypeLabel(template.headerType) }}
        </div>
        
        <!-- Nombre de variables -->
        <div v-if="showVariables" class="q-mr-md">
          <q-icon name="code" size="xs" class="q-mr-xs" />
          {{ template.bodyVariablesCount }} 
          {{ template.bodyVariablesCount > 1 ? 'variables' : 'variable' }}
        </div>
        
        <!-- Présence de boutons -->
        <div v-if="showButtons && template.hasButtons" class="q-mr-md">
          <q-icon name="smart_button" size="xs" class="q-mr-xs" />
          {{ template.buttonsCount }} 
          {{ template.buttonsCount > 1 ? 'boutons' : 'bouton' }}
        </div>
        
        <!-- Nombre d'utilisations -->
        <div v-if="showUsageCount && template.usageCount > 0" class="q-mr-md">
          <q-icon name="history" size="xs" class="q-mr-xs" />
          Utilisé {{ template.usageCount }} fois
        </div>
        
        <!-- Template populaire -->
        <q-badge v-if="template.isPopular" color="amber" text-color="black" class="q-ml-auto">
          Populaire
        </q-badge>
      </div>
    </q-card-section>
    
    <q-separator v-if="!compact" />
    
    <q-card-actions align="right" v-if="!compact">
      <!-- Bouton favoris -->
      <q-btn 
        flat 
        round 
        :color="isFavorite ? 'amber' : 'grey'" 
        :icon="isFavorite ? 'star' : 'star_border'"
        size="sm"
        @click.stop="$emit('favorite', template)"
      />
      
      <!-- Bouton sélectionner -->
      <q-btn flat color="primary" label="Sélectionner" size="sm" />
    </q-card-actions>
    
    <!-- Layer pour les templates compacts (pour afficher le bouton favori au survol) -->
    <div v-if="compact" class="compact-actions">
      <q-btn 
        flat 
        round 
        :color="isFavorite ? 'amber' : 'grey'" 
        :icon="isFavorite ? 'star' : 'star_border'"
        size="sm"
        @click.stop="$emit('favorite', template)"
      />
    </div>
  </q-card>
</template>

<script setup lang="ts">
import { defineProps, defineEmits, computed } from 'vue';
import { WhatsAppTemplate } from '@/stores/whatsappTemplateStore';

// Props
const props = defineProps({
  template: {
    type: Object as () => WhatsAppTemplate,
    required: true
  },
  compact: {
    type: Boolean,
    default: false
  },
  isFavorite: {
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
  showUsageCount: {
    type: Boolean,
    default: false
  }
});

// Emits
defineEmits(['click', 'favorite']);

// Méthodes
function getCategoryColor(category: string): string {
  switch (category) {
    case 'AUTHENTICATION':
      return 'deep-purple';
    case 'MARKETING':
      return 'green';
    case 'UTILITY':
      return 'blue';
    default:
      return 'grey';
  }
}

function getCategoryLabel(category: string): string {
  switch (category) {
    case 'AUTHENTICATION':
      return 'Auth';
    case 'MARKETING':
      return 'Marketing';
    case 'UTILITY':
      return 'Utilitaire';
    default:
      return category;
  }
}

function getHeaderTypeIcon(headerType: string | null): string {
  if (!headerType) return 'format_align_left';
  
  switch (headerType) {
    case 'TEXT':
      return 'format_align_left';
    case 'IMAGE':
      return 'image';
    case 'VIDEO':
      return 'videocam';
    case 'DOCUMENT':
      return 'description';
    default:
      return 'format_align_left';
  }
}

function getHeaderTypeLabel(headerType: string | null): string {
  if (!headerType) return 'Texte';
  
  switch (headerType) {
    case 'TEXT':
      return 'Texte';
    case 'IMAGE':
      return 'Image';
    case 'VIDEO':
      return 'Vidéo';
    case 'DOCUMENT':
      return 'Document';
    default:
      return headerType;
  }
}
</script>

<style scoped>
.template-card {
  transition: all 0.2s;
  position: relative;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.template-card:hover {
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.template-description {
  max-height: 3.6em;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.template-description--compact {
  max-height: 1.8em;
  -webkit-line-clamp: 1;
}

.template-card--compact {
  height: 80px;
}

.template-card--compact .template-name {
  font-size: 0.9rem;
}

.compact-actions {
  position: absolute;
  top: 5px;
  right: 5px;
  opacity: 0;
  transition: opacity 0.2s;
}

.template-card--compact:hover .compact-actions {
  opacity: 1;
}
</style>