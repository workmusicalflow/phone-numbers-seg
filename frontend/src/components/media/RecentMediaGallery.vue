<template>
  <div class="recent-media-gallery">
    <div class="q-mb-md">
      <q-input
        v-model="searchQuery"
        outlined
        dense
        placeholder="Rechercher un média"
        clearable
      >
        <template v-slot:prepend>
          <q-icon name="search" />
        </template>
      </q-input>
    </div>
    
    <!-- Filtres de type de média -->
    <div class="q-mb-md">
      <q-btn-toggle
        v-model="typeFilter"
        spread
        unelevated
        toggle-color="primary"
        text-color="primary"
        color="white"
        :options="[
          { label: 'Tous', value: 'all' },
          { label: 'Images', value: 'image', icon: 'image' },
          { label: 'Vidéos', value: 'video', icon: 'videocam' },
          { label: 'Audios', value: 'audio', icon: 'audiotrack' },
          { label: 'Documents', value: 'document', icon: 'description' }
        ]"
      />
    </div>
    
    <!-- Affichage des médias -->
    <div v-if="filteredMedia.length === 0" class="text-center q-pa-lg">
      <template v-if="mediaItems.length === 0">
        <q-icon name="history" size="48px" color="grey-5" />
        <div class="text-h6 text-grey-7 q-mt-md">Aucun média récent</div>
        <div class="text-caption text-grey-6">Les médias que vous utilisez apparaîtront ici</div>
      </template>
      <template v-else>
        <q-icon name="search_off" size="48px" color="grey-5" />
        <div class="text-h6 text-grey-7 q-mt-md">Aucun résultat</div>
        <div class="text-caption text-grey-6">Aucun média ne correspond à votre recherche</div>
      </template>
    </div>

    <div v-else class="row q-col-gutter-md">
      <div 
        v-for="(media, index) in filteredMedia" 
        :key="index"
        class="col-3 col-sm-2 col-md-2"
      >
        <q-card class="media-item cursor-pointer" @click="selectMedia(media)">
          <q-img
            v-if="media.type === 'image'"
            :src="media.thumbnailUrl || media.url"
            :ratio="1"
            spinner-color="primary"
            spinner-size="40px"
            fit="cover"
          />
          <div v-else-if="media.type === 'video'" class="media-card-placeholder video-placeholder">
            <q-icon name="videocam" size="36px" color="primary" />
          </div>
          <div v-else-if="media.type === 'audio'" class="media-card-placeholder audio-placeholder">
            <q-icon name="audiotrack" size="36px" color="primary" />
          </div>
          <div v-else class="media-card-placeholder document-placeholder">
            <q-icon name="description" size="36px" color="primary" />
          </div>

          <q-card-section class="q-pa-xs">
            <div class="text-caption text-weight-medium text-center ellipsis">
              {{ media.caption || media.filename || 'Sans titre' }}
            </div>
            <div class="text-caption text-grey text-center">{{ formatDate(media.timestamp) }}</div>
          </q-card-section>

          <q-card-actions align="right" class="q-pa-xs">
            <q-btn 
              flat round dense size="sm" 
              :icon="media.favorite ? 'star' : 'star_outline'" 
              :class="media.favorite ? 'text-warning' : ''"
              @click.stop="toggleFavorite(media)"
            >
              <q-tooltip>{{ media.favorite ? 'Retirer des favoris' : 'Ajouter aux favoris' }}</q-tooltip>
            </q-btn>
            <q-btn flat round dense size="sm" icon="content_copy" @click.stop="useMedia(media)">
              <q-tooltip>Utiliser ce média</q-tooltip>
            </q-btn>
          </q-card-actions>
        </q-card>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useQuasar, date } from 'quasar';
import { mediaService, type Media } from '@/services/mediaService';

const $q = useQuasar();

// Props & Emits
const props = defineProps({
  maxItems: {
    type: Number,
    default: 20
  },
  showFavorites: {
    type: Boolean,
    default: false
  },
  initialFilter: {
    type: String,
    default: 'all'
  }
});

const emit = defineEmits(['media-selected']);

// État local
const mediaItems = ref<Media[]>([]);
const searchQuery = ref('');
const typeFilter = ref(props.initialFilter);

// Computed
const filteredMedia = computed(() => {
  let result = props.showFavorites 
    ? mediaItems.value.filter(item => item.favorite)
    : mediaItems.value;
  
  // Filtrer par type de média si un filtre est sélectionné
  if (typeFilter.value !== 'all') {
    result = result.filter(media => media.type === typeFilter.value);
  }
  
  // Filtrer par termes de recherche si une recherche est en cours
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase().trim();
    result = result.filter(media =>
      (media.filename && media.filename.toLowerCase().includes(query)) ||
      (media.caption && media.caption.toLowerCase().includes(query)) ||
      (media.mimeType && media.mimeType.toLowerCase().includes(query))
    );
  }
  
  // Limiter le nombre d'éléments affichés
  return result.slice(0, props.maxItems);
});

// Méthodes
function loadMedia() {
  if (props.showFavorites) {
    mediaItems.value = mediaService.getFavoriteMedia();
  } else {
    mediaItems.value = mediaService.getRecentMedia();
  }
}

function refreshMedia() {
  loadMedia();
}

function formatDate(dateStr: string): string {
  return date.formatDate(dateStr, 'DD/MM/YYYY HH:mm');
}

function selectMedia(media: Media) {
  // Ouvrir la boîte de dialogue de détails du média
  emit('media-selected', media);
}

function useMedia(media: Media) {
  // Émettre l'événement pour utiliser ce média
  emit('media-selected', media);
  
  // Notifier l'utilisateur
  $q.notify({
    type: 'positive',
    message: 'Média sélectionné',
    caption: media.caption || media.filename || 'Sans titre'
  });
}

function toggleFavorite(media: Media) {
  if (media.favorite) {
    // Retirer des favoris
    mediaService.removeFromFavorites(media.mediaId);
    // Mettre à jour l'état local
    media.favorite = false;
    
    $q.notify({
      type: 'info',
      message: 'Retiré des favoris',
      icon: 'star_outline'
    });
  } else {
    // Ajouter aux favoris
    mediaService.addToFavorites(media);
    // Mettre à jour l'état local
    media.favorite = true;
    
    $q.notify({
      type: 'positive',
      message: 'Ajouté aux favoris',
      icon: 'star'
    });
  }
  
  // Rafraîchir la liste
  refreshMedia();
}

// Hooks
onMounted(() => {
  loadMedia();
});

// Observateurs
watch(() => props.showFavorites, () => {
  loadMedia();
});
</script>

<style lang="scss" scoped>
.recent-media-gallery {
  .media-card-placeholder {
    height: 0;
    padding-bottom: 100%;
    position: relative;
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: #f0f0f0;
    
    .q-icon {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
    }
  }
  
  .media-item {
    transition: all 0.2s ease-in-out;
    
    &:hover {
      transform: translateY(-3px);
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    }
  }
}
</style>