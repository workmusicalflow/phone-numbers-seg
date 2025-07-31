<template>
  <div class="whatsapp-media-gallery">
    <q-card flat bordered class="q-mb-md">
      <q-card-section>
        <div class="text-subtitle1 row justify-between items-center">
          <div>
            <q-icon name="photo_library" class="q-mr-sm" />
            Médias récemment utilisés
          </div>
          <q-btn flat round size="sm" icon="developer_board" color="grey-7" @click="showDiagnostics = true">
            <q-tooltip>Outils de diagnostic</q-tooltip>
          </q-btn>
        </div>
      </q-card-section>
      
      <q-separator />
      
      <q-card-section>
        <div v-if="recentMedia.length === 0" class="text-center q-pa-md">
          <q-icon name="image_not_supported" size="32px" color="grey-5" />
          <div class="text-body2 text-grey-7 q-mt-sm">Aucun média récent</div>
        </div>
        
        <div v-else class="row q-col-gutter-sm q-mb-md">
          <div 
            v-for="(media, index) in recentMedia" 
            :key="index"
            class="col-2 col-sm-1"
          >
            <q-card 
              class="media-thumbnail cursor-pointer" 
              @click="selectMedia(media)"
              :class="{ 'media-selected': isSelected(media) }"
            >
              <q-img
                v-if="media.type === 'image'"
                :src="media.thumbnailUrl || media.url"
                :ratio="1"
                spinner-color="primary"
                fit="cover"
              />
              <div v-else-if="media.type === 'video'" class="media-placeholder video-placeholder">
                <q-icon name="videocam" size="24px" color="primary" />
              </div>
              <div v-else-if="media.type === 'audio'" class="media-placeholder audio-placeholder">
                <q-icon name="audiotrack" size="24px" color="primary" />
              </div>
              <div v-else class="media-placeholder document-placeholder">
                <q-icon name="description" size="24px" color="primary" />
              </div>
              
              <q-tooltip>
                {{ media.caption || media.filename || 'Sans titre' }}
              </q-tooltip>
            </q-card>
          </div>
        </div>
        
        <div class="row justify-center q-mt-md" v-if="recentMedia.length > 0">
          <q-btn 
            outline 
            color="primary" 
            size="sm" 
            icon="add_photo_alternate" 
            label="Ajouter un nouveau média" 
            @click="openMediaLibrary"
          />
        </div>
      </q-card-section>
    </q-card>
    
    <!-- Boîte de dialogue MediaLibrary -->
    <q-dialog v-model="mediaLibraryDialog" maximized>
      <q-card>
        <q-card-section class="row items-center">
          <div class="text-h6">Bibliothèque de médias</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>
        
        <q-card-section class="q-pa-none">
          <MediaLibrary 
            @media-selected="onMediaLibrarySelect" 
            @cancel="mediaLibraryDialog = false"
          />
        </q-card-section>
      </q-card>
    </q-dialog>
    
    <!-- Outils de diagnostic -->
    <MediaDiagnostics v-model="showDiagnostics" />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { mediaService, type Media } from '@/services/mediaService';
import MediaLibrary from '@/components/media/MediaLibrary.vue';
import MediaDiagnostics from '@/components/media/MediaDiagnostics.vue';

// Props & Emits
const props = defineProps({
  selectedMediaId: {
    type: String,
    default: ''
  },
  maxItems: {
    type: Number,
    default: 12
  }
});

const emit = defineEmits(['media-selected']);

// État du composant
const recentMedia = ref<Media[]>([]);
const mediaLibraryDialog = ref(false);
const showDiagnostics = ref(false);

// Computed properties
const filteredRecentMedia = computed(() => {
  return recentMedia.value.slice(0, props.maxItems);
});

// Méthodes
function loadRecentMedia() {
  recentMedia.value = mediaService.getRecentMedia();
}

function selectMedia(media: Media) {
  emit('media-selected', media);
}

function isSelected(media: Media): boolean {
  return media.mediaId === props.selectedMediaId;
}

function openMediaLibrary() {
  mediaLibraryDialog.value = true;
}

function onMediaLibrarySelect(media: any) {
  emit('media-selected', media);
  mediaLibraryDialog.value = false;
  
  // Rafraîchir la liste des médias récents
  loadRecentMedia();
}

// Hooks
onMounted(() => {
  loadRecentMedia();
});
</script>

<style lang="scss" scoped>
.whatsapp-media-gallery {
  .media-thumbnail {
    overflow: hidden;
    transition: all 0.2s ease;
    border: 2px solid transparent;
    
    &:hover {
      transform: scale(1.05);
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    &.media-selected {
      border-color: var(--q-primary);
    }
  }
  
  .media-placeholder {
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
}
</style>