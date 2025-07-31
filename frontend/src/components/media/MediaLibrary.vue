<template>
  <div class="media-library">
    <q-card flat bordered>
      <q-card-section>
        <div class="text-h6">
          <q-icon name="photo_library" size="28px" class="q-mr-sm" />
          Bibliothèque de médias
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section>
        <!-- Onglets: Upload, Récents, Favoris -->
        <q-tabs
          v-model="activeTab"
          class="text-primary"
          active-color="primary"
          indicator-color="primary"
          align="justify"
          narrow-indicator
        >
          <q-tab name="upload" icon="cloud_upload" label="Upload" />
          <q-tab name="recent" icon="history" label="Récents" />
          <q-tab name="favorites" icon="star" label="Favoris" />
        </q-tabs>

        <q-separator />

        <q-tab-panels v-model="activeTab" animated>
          <!-- Onglet Upload -->
          <q-tab-panel name="upload">
            <!-- Type de média -->
            <div class="q-mb-md">
              <div class="text-subtitle2 q-mb-sm">Type de média</div>
              <q-btn-toggle
                v-model="mediaType"
                spread
                unelevated
                toggle-color="primary"
                :options="[
                  { label: 'Image', value: 'image', icon: 'image' },
                  { label: 'Vidéo', value: 'video', icon: 'videocam' },
                  { label: 'Audio', value: 'audio', icon: 'audiotrack' },
                  { label: 'Document', value: 'document', icon: 'description' }
                ]"
              />
            </div>

            <!-- Sélection du fichier -->
            <div class="q-mb-md">
              <q-file
                v-model="mediaFile"
                label="Sélectionner un fichier"
                outlined
                :accept="acceptedFileTypes"
                :max-file-size="maxFileSize"
                @update:model-value="handleFileSelected"
                @rejected="onFileRejected"
                :disable="uploadState !== 'idle'"
                bottom-slots
                counter
              >
                <template v-slot:prepend>
                  <q-icon name="attach_file" />
                </template>
                <template v-slot:append>
                  <q-icon 
                    v-if="mediaFile && uploadState === 'idle'"
                    name="close" 
                    class="cursor-pointer" 
                    @click.stop.prevent="mediaFile = null"
                  />
                </template>
                <template v-slot:hint>
                  {{ fileHint }}
                </template>
              </q-file>
            </div>

            <!-- Prévisualisation du média -->
            <div v-if="mediaFile && previewUrl" class="media-preview q-mb-md">
              <q-card flat bordered>
                <q-card-section class="text-center">
                  <img 
                    v-if="mediaType === 'image'"
                    :src="previewUrl"
                    alt="Preview"
                    class="preview-image"
                  />
                  <video 
                    v-else-if="mediaType === 'video'"
                    :src="previewUrl"
                    controls
                    class="preview-video"
                  ></video>
                  <audio 
                    v-else-if="mediaType === 'audio'"
                    :src="previewUrl"
                    controls
                    class="preview-audio"
                  ></audio>
                  <div v-else-if="mediaType === 'document'" class="preview-document">
                    <q-icon :name="documentIcon" size="48px" color="primary" />
                    <div class="text-subtitle2 q-mt-sm">{{ mediaFile.name }}</div>
                    <div class="text-caption">{{ formatFileSize(mediaFile.size) }}</div>
                  </div>
                </q-card-section>
              </q-card>
            </div>

            <!-- Options d'optimisation (uniquement pour les images) -->
            <div v-if="mediaType === 'image' && mediaFile" class="q-mb-md">
              <q-expansion-item
                expand-separator
                icon="tune"
                label="Options d'optimisation"
                caption="Redimensionnement, compression, etc."
              >
                <q-card>
                  <q-card-section>
                    <div class="text-subtitle2 q-mb-sm">Qualité de l'image</div>
                    <q-slider
                      v-model="imageQuality"
                      :min="30"
                      :max="100"
                      :step="5"
                      label
                      label-always
                      color="primary"
                    />

                    <div class="text-subtitle2 q-mt-md q-mb-sm">Redimensionnement</div>
                    <q-toggle 
                      v-model="resizeEnabled" 
                      label="Activer le redimensionnement"
                    />
                    
                    <div v-if="resizeEnabled" class="row q-col-gutter-md q-mt-sm">
                      <div class="col-6">
                        <q-input
                          v-model.number="resizeWidth"
                          type="number"
                          outlined
                          dense
                          label="Largeur max (px)"
                          :rules="[val => val > 0 || 'Doit être > 0']"
                        />
                      </div>
                      <div class="col-6">
                        <q-input
                          v-model.number="resizeHeight"
                          type="number"
                          outlined
                          dense
                          label="Hauteur max (px)"
                          :rules="[val => val > 0 || 'Doit être > 0']"
                        />
                      </div>
                    </div>
                  </q-card-section>
                </q-card>
              </q-expansion-item>
            </div>

            <!-- Légende / Description -->
            <div class="q-mb-md">
              <q-input
                v-model="mediaCaption"
                outlined
                label="Légende / Description (optionnel)"
                type="textarea"
                autogrow
                :disable="uploadState !== 'idle'"
              />
            </div>

            <!-- État de l'upload -->
            <q-banner 
              v-if="uploadState !== 'idle'" 
              :class="{
                'bg-info text-white': uploadState === 'processing' || uploadState === 'uploading',
                'bg-positive text-white': uploadState === 'uploaded',
                'bg-negative text-white': uploadState === 'error',
                'bg-warning text-white': uploadState === 'paused'
              }"
              class="q-mb-md"
              rounded
            >
              <template v-slot:avatar>
                <q-spinner-dots v-if="uploadState === 'processing' || uploadState === 'uploading'" color="white" size="24px" />
                <q-icon v-else-if="uploadState === 'uploaded'" name="cloud_done" />
                <q-icon v-else-if="uploadState === 'paused'" name="pause_circle" />
                <q-icon v-else name="error" />
              </template>
              
              <div class="text-weight-medium">
                <template v-if="uploadState === 'processing'">
                  Traitement du fichier en cours...
                </template>
                <template v-else-if="uploadState === 'uploading'">
                  Upload en cours... {{ uploadProgress }}%
                </template>
                <template v-else-if="uploadState === 'uploaded'">
                  Fichier uploadé avec succès !
                </template>
                <template v-else-if="uploadState === 'paused'">
                  Upload en pause - {{ uploadedBytes ? formatFileSize(uploadedBytes) : '0 B' }} / {{ mediaFile ? formatFileSize(mediaFile.size) : '0 B' }}
                </template>
                <template v-else>
                  Erreur lors de l'upload
                </template>
              </div>
              
              <div v-if="uploadState === 'uploaded'" class="text-caption">
                <div>Media ID: {{ uploadedMediaId }}</div>
                <div v-if="uploadedMediaUrl">URL: {{ uploadedMediaUrl }}</div>
              </div>
              <div v-else-if="uploadState === 'error'" class="text-caption">
                {{ uploadError }}
                <div v-if="canResumeUpload" class="q-mt-xs">
                  L'upload peut être repris à partir de {{ uploadedBytes ? formatFileSize(uploadedBytes) : '0 B' }}.
                </div>
              </div>
              <div v-else-if="uploadState === 'paused'" class="text-caption">
                L'upload a été mis en pause. Vous pouvez le reprendre quand vous êtes prêt.
              </div>
              
              <!-- Barre de progression pour l'upload -->
              <q-linear-progress
                v-if="uploadState === 'uploading' || uploadState === 'paused'"
                :value="uploadProgress / 100"
                :color="uploadState === 'paused' ? 'amber-4' : 'white'"
                track-color="blue-3"
                size="6px"
                class="q-mt-sm"
                rounded
              />
              
              <template v-slot:action>
                <q-btn 
                  v-if="uploadState === 'error' && !canResumeUpload"
                  flat 
                  color="white" 
                  label="Réessayer"
                  @click="retryUpload"
                />
                <q-btn 
                  v-if="uploadState === 'error' && canResumeUpload"
                  flat 
                  color="white" 
                  label="Reprendre"
                  @click="resumeUpload"
                />
                <q-btn 
                  v-if="uploadState === 'paused'"
                  flat 
                  color="white" 
                  label="Reprendre"
                  @click="resumeUpload"
                />
                <q-btn 
                  v-if="uploadState === 'uploading'"
                  flat 
                  color="white" 
                  label="Pause"
                  @click="pauseUpload"
                />
                <q-btn 
                  v-if="uploadState === 'uploaded'"
                  flat 
                  color="white" 
                  icon="star"
                  @click="addToFavorites"
                >
                  <q-tooltip>Ajouter aux favoris</q-tooltip>
                </q-btn>
              </template>
            </q-banner>

            <!-- Boutons d'action -->
            <div class="row justify-end q-gutter-sm">
              <q-btn
                v-if="uploadState === 'idle'"
                color="primary"
                label="Uploader"
                icon="cloud_upload"
                :disable="!mediaFile"
                @click="startUpload"
              />
              <q-btn
                v-if="uploadState === 'uploaded'"
                color="primary"
                label="Utiliser ce média"
                icon="check_circle"
                @click="useMedia"
              />
              <q-btn
                v-if="uploadState !== 'idle' && uploadState !== 'uploading'"
                label="Nouveau média"
                color="secondary"
                outline
                icon="add_photo_alternate"
                @click="resetForm"
              />
              <q-btn
                v-if="uploadState === 'idle' || uploadState === 'error'"
                label="Annuler"
                color="grey"
                flat
                @click="$emit('cancel')"
              />
            </div>
          </q-tab-panel>

          <!-- Onglet Médias récents -->
          <q-tab-panel name="recent">
            <div class="q-mb-md">
              <q-input
                v-model="mediaSearchQuery"
                outlined
                dense
                placeholder="Rechercher dans les médias récents"
                clearable
              >
                <template v-slot:prepend>
                  <q-icon name="search" />
                </template>
              </q-input>
            </div>
            
            <div v-if="filteredRecentMedia.length === 0" class="text-center q-pa-lg">
              <template v-if="recentMedia.length === 0">
                <q-icon name="history" size="48px" color="grey-5" />
                <div class="text-h6 text-grey-7 q-mt-md">Aucun média récent</div>
                <div class="text-caption text-grey-6">Les médias que vous uploadez apparaîtront ici</div>
              </template>
              <template v-else>
                <q-icon name="search_off" size="48px" color="grey-5" />
                <div class="text-h6 text-grey-7 q-mt-md">Aucun résultat</div>
                <div class="text-caption text-grey-6">Aucun média ne correspond à votre recherche</div>
              </template>
            </div>

            <div v-else class="row q-col-gutter-md">
              <!-- Filtres de type de média -->
              <div class="col-12 q-mb-sm">
                <q-btn-toggle
                  v-model="mediaTypeFilter"
                  spread
                  unelevated
                  toggle-color="primary"
                  text-color="primary"
                  color="white"
                  class="q-mb-md"
                  :options="[
                    { label: 'Tous', value: 'all' },
                    { label: 'Images', value: 'image', icon: 'image' },
                    { label: 'Vidéos', value: 'video', icon: 'videocam' },
                    { label: 'Audios', value: 'audio', icon: 'audiotrack' },
                    { label: 'Documents', value: 'document', icon: 'description' }
                  ]"
                />
              </div>
              <div 
                v-for="(media, index) in filteredRecentMedia" 
                :key="index"
                class="col-4 col-sm-3 col-md-2"
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
                    <q-btn flat round dense size="sm" icon="star_outline" @click.stop="addToFavorites(media)">
                      <q-tooltip>Ajouter aux favoris</q-tooltip>
                    </q-btn>
                    <q-btn flat round dense size="sm" icon="delete" @click.stop="removeMedia(media)">
                      <q-tooltip>Supprimer</q-tooltip>
                    </q-btn>
                  </q-card-actions>
                </q-card>
              </div>
            </div>
          </q-tab-panel>

          <!-- Onglet Favoris -->
          <q-tab-panel name="favorites">
            <div v-if="favoriteMedia.length === 0" class="text-center q-pa-lg">
              <q-icon name="star" size="48px" color="grey-5" />
              <div class="text-h6 text-grey-7 q-mt-md">Aucun média favori</div>
              <div class="text-caption text-grey-6">Ajoutez des médias aux favoris pour les retrouver facilement</div>
            </div>

            <div v-else class="row q-col-gutter-md">
              <div 
                v-for="(media, index) in favoriteMedia" 
                :key="index"
                class="col-4 col-sm-3 col-md-2"
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
                    <q-btn flat round dense size="sm" icon="star" class="text-warning" @click.stop="removeFromFavorites(media)">
                      <q-tooltip>Retirer des favoris</q-tooltip>
                    </q-btn>
                    <q-btn flat round dense size="sm" icon="delete" @click.stop="removeMedia(media)">
                      <q-tooltip>Supprimer</q-tooltip>
                    </q-btn>
                  </q-card-actions>
                </q-card>
              </div>
            </div>
          </q-tab-panel>
        </q-tab-panels>
      </q-card-section>
    </q-card>

    <!-- Dialogue de détails du média -->
    <q-dialog v-model="mediaDetailDialog">
      <q-card style="min-width: 350px; max-width: 90vw;">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Détails du média</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section v-if="selectedMedia">
          <div class="media-detail-container q-mb-md">
            <img 
              v-if="selectedMedia.type === 'image'"
              :src="selectedMedia.url"
              alt="Preview"
              class="detail-preview"
            />
            <video 
              v-else-if="selectedMedia.type === 'video'"
              :src="selectedMedia.url"
              controls
              class="detail-preview"
            ></video>
            <audio 
              v-else-if="selectedMedia.type === 'audio'"
              :src="selectedMedia.url"
              controls
              class="detail-preview-audio"
            ></audio>
            <div v-else-if="selectedMedia.type === 'document'" class="detail-preview-document">
              <q-icon name="description" size="64px" color="primary" />
              <div class="text-subtitle1 q-mt-sm">{{ selectedMedia.filename }}</div>
            </div>
          </div>

          <q-list bordered separator>
            <q-item>
              <q-item-section>
                <q-item-label overline>Nom</q-item-label>
                <q-item-label>{{ selectedMedia.filename }}</q-item-label>
              </q-item-section>
            </q-item>

            <q-item>
              <q-item-section>
                <q-item-label overline>Type</q-item-label>
                <q-item-label>{{ selectedMedia.mimeType || selectedMedia.type }}</q-item-label>
              </q-item-section>
            </q-item>

            <q-item>
              <q-item-section>
                <q-item-label overline>Date</q-item-label>
                <q-item-label>{{ formatDate(selectedMedia.timestamp) }}</q-item-label>
              </q-item-section>
            </q-item>

            <q-item>
              <q-item-section>
                <q-item-label overline>Media ID</q-item-label>
                <q-item-label class="row items-center">
                  {{ selectedMedia.mediaId }}
                  <q-btn flat round dense size="sm" icon="content_copy" @click="copyToClipboard(selectedMedia.mediaId)">
                    <q-tooltip>Copier</q-tooltip>
                  </q-btn>
                </q-item-label>
              </q-item-section>
            </q-item>

            <q-item v-if="selectedMedia.url">
              <q-item-section>
                <q-item-label overline>URL</q-item-label>
                <q-item-label class="row items-center">
                  <div class="ellipsis" style="max-width: 250px;">{{ selectedMedia.url }}</div>
                  <q-btn flat round dense size="sm" icon="content_copy" @click="copyToClipboard(selectedMedia.url)">
                    <q-tooltip>Copier</q-tooltip>
                  </q-btn>
                </q-item-label>
              </q-item-section>
            </q-item>

            <q-item>
              <q-item-section>
                <q-item-label overline>Description</q-item-label>
                <q-item-label>{{ selectedMedia.caption || 'Aucune description' }}</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat color="grey" label="Fermer" v-close-popup />
          <q-btn color="primary" label="Utiliser ce média" @click="useSelectedMedia" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useQuasar, date } from 'quasar';
import { api } from '@/services/api';

const $q = useQuasar();

// Props & Emits
const props = defineProps({
  allowedTypes: {
    type: Array as () => string[],
    default: () => ['image', 'video', 'audio', 'document']
  },
  initialType: {
    type: String,
    default: 'image'
  },
  showTabs: {
    type: Boolean,
    default: true
  }
});

const emit = defineEmits(['media-selected', 'upload-complete', 'cancel']);

// État du composant
const activeTab = ref('upload');
const mediaType = ref(props.initialType);
const mediaFile = ref<File | null>(null);
const mediaCaption = ref('');
const previewUrl = ref('');
const uploadState = ref<'idle' | 'processing' | 'uploading' | 'uploaded' | 'error' | 'paused'>('idle');
const uploadProgress = ref(0);
const uploadError = ref('');
const uploadedMediaId = ref('');
const uploadedMediaUrl = ref('');
const uploadId = ref('');
const uploadedBytes = ref(0);
const canResumeUpload = ref(false);
const mediaSearchQuery = ref('');
const mediaTypeFilter = ref('all');
const imageQuality = ref(80);
const resizeEnabled = ref(false);
const resizeWidth = ref(1024);
const resizeHeight = ref(1024);

// Données des médias sauvegardés
const recentMedia = ref<any[]>([]);
const favoriteMedia = ref<any[]>([]);
const mediaDetailDialog = ref(false);
const selectedMedia = ref<any>(null);

// Constantes
const MAX_FILE_SIZES = {
  image: 5 * 1024 * 1024, // 5MB
  video: 16 * 1024 * 1024, // 16MB
  audio: 16 * 1024 * 1024, // 16MB
  document: 100 * 1024 * 1024, // 100MB
  sticker: 500 * 1024 // 500KB
};

const ACCEPTED_FILE_TYPES = {
  image: '.jpg,.jpeg,.png,.webp',
  video: '.mp4,.mov,.3gp',
  audio: '.mp3,.aac,.ogg,.amr',
  document: '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt'
};

// Computed properties
const filteredRecentMedia = computed(() => {
  let result = recentMedia.value;
  
  // Filtrer par type de média si un filtre est sélectionné
  if (mediaTypeFilter.value !== 'all') {
    result = result.filter(media => media.type === mediaTypeFilter.value);
  }
  
  // Filtrer par termes de recherche si une recherche est en cours
  if (mediaSearchQuery.value.trim()) {
    const query = mediaSearchQuery.value.toLowerCase().trim();
    result = result.filter(media =>
      (media.filename && media.filename.toLowerCase().includes(query)) ||
      (media.caption && media.caption.toLowerCase().includes(query)) ||
      (media.mimeType && media.mimeType.toLowerCase().includes(query))
    );
  }
  
  return result;
});

// Autres computed properties
const maxFileSize = computed(() => {
  return MAX_FILE_SIZES[mediaType.value as keyof typeof MAX_FILE_SIZES] || 5 * 1024 * 1024;
});

const acceptedFileTypes = computed(() => {
  return ACCEPTED_FILE_TYPES[mediaType.value as keyof typeof ACCEPTED_FILE_TYPES] || '';
});

const fileHint = computed(() => {
  const maxSize = formatFileSize(maxFileSize.value);
  return `Taille maximale: ${maxSize} | Formats acceptés: ${acceptedFileTypes.value}`;
});

const documentIcon = computed(() => {
  if (!mediaFile.value) return 'description';
  
  const fileName = mediaFile.value.name.toLowerCase();
  if (fileName.endsWith('.pdf')) return 'picture_as_pdf';
  if (fileName.endsWith('.doc') || fileName.endsWith('.docx')) return 'description';
  if (fileName.endsWith('.xls') || fileName.endsWith('.xlsx')) return 'table_chart';
  if (fileName.endsWith('.ppt') || fileName.endsWith('.pptx')) return 'slideshow';
  return 'insert_drive_file';
});

// Gestion des fichiers
function handleFileSelected(file: File | null) {
  if (!file) {
    previewUrl.value = '';
    return;
  }

  // Vérifications de sécurité et validation
  if (file.size > maxFileSize.value) {
    $q.notify({
      type: 'negative',
      message: `Fichier trop volumineux (max: ${formatFileSize(maxFileSize.value)})`
    });
    mediaFile.value = null;
    return;
  }

  // Créer une prévisualisation pour les types supportés
  if (file.type.startsWith('image/')) {
    const reader = new FileReader();
    reader.onload = (e) => {
      previewUrl.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
  } else if (file.type.startsWith('video/')) {
    const url = URL.createObjectURL(file);
    previewUrl.value = url;
  } else if (file.type.startsWith('audio/')) {
    const url = URL.createObjectURL(file);
    previewUrl.value = url;
  } else {
    // Pour les documents, pas de prévisualisation réelle
    previewUrl.value = '';
  }
}

function onFileRejected(entry: any) {
  console.error('File rejected:', entry);
  $q.notify({
    type: 'negative',
    message: `Fichier rejeté: ${entry.failedPropValidation}`,
    caption: entry.file?.name || ''
  });
}

// Utilitaires
function formatFileSize(bytes: number): string {
  if (bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
}

function formatDate(dateStr: string): string {
  return date.formatDate(dateStr, 'DD/MM/YYYY HH:mm');
}

function copyToClipboard(text: string) {
  navigator.clipboard.writeText(text).then(() => {
    $q.notify({
      type: 'positive',
      message: 'Copié dans le presse-papier',
      timeout: 1000
    });
  });
}

// Gestion de l'upload
async function startUpload() {
  if (!mediaFile.value) return;
  
  uploadState.value = 'processing';
  uploadProgress.value = 0;
  uploadError.value = '';
  uploadId.value = '';
  uploadedBytes.value = 0;
  canResumeUpload.value = false;
  
  try {
    // Si c'est une image et que l'optimisation est activée
    if (mediaType.value === 'image' && (resizeEnabled.value || imageQuality.value < 100)) {
      await processImageBeforeUpload();
    } else {
      // Upload direct sans optimisation
      await uploadFile(mediaFile.value);
    }
  } catch (error: any) {
    console.error('Error during upload:', error);
    uploadState.value = 'error';
    uploadError.value = error.message || 'Erreur lors de l\'upload';
    
    // Vérifier si l'upload peut être repris (généralement en cas d'erreur réseau)
    if (error.resumable) {
      canResumeUpload.value = true;
      uploadId.value = error.uploadId || '';
      uploadedBytes.value = error.uploadedBytes || 0;
      uploadProgress.value = error.uploadedBytes && error.totalBytes 
        ? Math.round((error.uploadedBytes / error.totalBytes) * 100) 
        : 0;
    }
    
    $q.notify({
      type: 'negative',
      message: 'Erreur lors de l\'upload',
      caption: uploadError.value
    });
  }
}

async function processImageBeforeUpload() {
  if (!mediaFile.value || !mediaFile.value.type.startsWith('image/')) {
    throw new Error('Fichier invalide ou pas une image');
  }
  
  try {
    // Créer une image à partir du fichier
    const img = new Image();
    const imgUrl = URL.createObjectURL(mediaFile.value);
    
    // Attendre que l'image soit chargée
    await new Promise<void>((resolve, reject) => {
      img.onload = () => resolve();
      img.onerror = () => reject(new Error('Erreur lors du chargement de l\'image'));
      img.src = imgUrl;
    });
    
    // Calculer les dimensions pour le redimensionnement si nécessaire
    let width = img.width;
    let height = img.height;
    
    if (resizeEnabled.value) {
      if (width > resizeWidth.value || height > resizeHeight.value) {
        const ratio = Math.min(resizeWidth.value / width, resizeHeight.value / height);
        width = Math.floor(width * ratio);
        height = Math.floor(height * ratio);
      }
    }
    
    // Dessiner l'image sur un canvas pour la compression/redimensionnement
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d');
    
    if (!ctx) {
      throw new Error('Impossible de créer le contexte du canvas');
    }
    
    ctx.drawImage(img, 0, 0, width, height);
    
    // Convertir en blob avec la qualité souhaitée
    const mimeType = mediaFile.value.type;
    const quality = imageQuality.value / 100;
    
    const blob = await new Promise<Blob>((resolve, reject) => {
      canvas.toBlob(
        (result) => {
          if (result) {
            resolve(result);
          } else {
            reject(new Error('Échec de conversion du canvas en blob'));
          }
        },
        mimeType,
        quality
      );
    });
    
    // Créer un nouveau fichier à partir du blob
    const optimizedFile = new File([blob], mediaFile.value.name, {
      type: mimeType,
      lastModified: new Date().getTime()
    });
    
    console.log('Original size:', formatFileSize(mediaFile.value.size));
    console.log('Optimized size:', formatFileSize(optimizedFile.size));
    console.log('Reduction:', Math.round((1 - optimizedFile.size / mediaFile.value.size) * 100) + '%');
    
    // Mettre à jour la prévisualisation
    previewUrl.value = URL.createObjectURL(optimizedFile);
    
    // Upload du fichier optimisé
    await uploadFile(optimizedFile);
    
  } catch (error) {
    console.error('Error processing image:', error);
    throw error;
  }
}

async function uploadFile(file: File) {
  uploadState.value = 'uploading';
  
  const formData = new FormData();
  formData.append('file', file);
  
  try {
    // Importer ici le mediaService pour utiliser la méthode d'upload avec reprise
    const { mediaService } = await import('@/services/mediaService');
    
    const result = await mediaService.uploadFileWithFallback(file, {
      optimizeImage: mediaType.value === 'image' && (resizeEnabled.value || imageQuality.value < 100),
      imageQuality: imageQuality.value,
      maxWidth: resizeEnabled.value ? resizeWidth.value : undefined,
      maxHeight: resizeEnabled.value ? resizeHeight.value : undefined,
      onProgress: (progress) => {
        uploadProgress.value = progress;
      }
    });
    
    if (result.success && result.mediaId) {
      uploadedMediaId.value = result.mediaId;
      uploadedMediaUrl.value = result.url || '';
      uploadState.value = 'uploaded';
      
      // Sauvegarder le média dans l'historique local
      const mediaData = {
        mediaId: result.mediaId,
        type: mediaType.value,
        mimeType: file.type,
        filename: file.name,
        size: file.size,
        caption: mediaCaption.value,
        url: result.url || previewUrl.value,
        thumbnailUrl: mediaType.value === 'image' ? (result.url || previewUrl.value) : undefined,
        timestamp: new Date().toISOString(),
        favorite: false
      };
      
      saveMediaToLocalStorage(mediaData);
      
      // Notifier le succès
      $q.notify({
        type: 'positive',
        message: 'Média uploadé avec succès',
        caption: `Media ID: ${uploadedMediaId.value}`
      });
      
      // Émettre l'événement pour le parent
      emit('upload-complete', {
        mediaId: uploadedMediaId.value,
        mediaType: mediaType.value,
        caption: mediaCaption.value,
        file: file,
        url: result.url || previewUrl.value
      });
    } else if (result.resumable) {
      // L'upload peut être repris ultérieurement
      uploadState.value = 'error';
      uploadError.value = result.error || 'Erreur réseau lors de l\'upload';
      canResumeUpload.value = true;
      uploadId.value = result.uploadId || '';
      uploadedBytes.value = result.uploadedBytes || 0;
      uploadProgress.value = result.uploadedBytes && result.totalBytes 
        ? Math.round((result.uploadedBytes / result.totalBytes) * 100) 
        : 0;
      
      $q.notify({
        type: 'warning',
        message: 'Upload interrompu',
        caption: 'Vous pourrez reprendre l\'upload ultérieurement'
      });
    } else {
      throw new Error(result.error || 'Upload échoué');
    }
  } catch (error: any) {
    // Ancien code d'upload via API PHP directe en cas d'erreur
    try {
      const formData = new FormData();
      formData.append('file', file);
      
      const response = await api.post('/whatsapp/upload.php', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
        onUploadProgress: (progressEvent) => {
          if (progressEvent.total) {
            uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total);
          }
        }
      });
    
      if (response.data.success && response.data.mediaId) {
        uploadedMediaId.value = response.data.mediaId;
        uploadState.value = 'uploaded';
        
        // Sauvegarder le média dans l'historique local
        const mediaData = {
          mediaId: response.data.mediaId,
          type: mediaType.value,
          mimeType: file.type,
          filename: file.name,
          size: file.size,
          caption: mediaCaption.value,
          url: previewUrl.value,
          thumbnailUrl: previewUrl.value,
          timestamp: new Date().toISOString(),
          favorite: false
        };
        
        saveMediaToLocalStorage(mediaData);
        
        // Notifier le succès
        $q.notify({
          type: 'positive',
          message: 'Média uploadé avec succès',
          caption: `Media ID: ${uploadedMediaId.value}`
        });
        
        // Émettre l'événement pour le parent
        emit('upload-complete', {
          mediaId: uploadedMediaId.value,
          mediaType: mediaType.value,
          caption: mediaCaption.value,
          file: file,
          url: previewUrl.value
        });
      } else {
        throw new Error(response.data.error || 'Upload échoué');
      }
    } catch (error: any) {
      console.error('Upload error:', error);
      uploadState.value = 'error';
      uploadError.value = error.message || error.response?.data?.error || 'Erreur lors de l\'upload';
      
      $q.notify({
        type: 'negative',
        message: 'Erreur lors de l\'upload',
        caption: uploadError.value
      });
    }
  }
}

function retryUpload() {
  uploadState.value = 'idle';
  uploadError.value = '';
  uploadId.value = '';
  uploadedBytes.value = 0;
  canResumeUpload.value = false;
  startUpload();
}

function resumeUpload() {
  if (!mediaFile.value) return;
  
  uploadState.value = 'uploading';
  uploadError.value = '';
  
  const resumeUploadOperation = async () => {
    try {
      // Importer le mediaService pour utiliser la méthode d'upload avec reprise
      const { mediaService } = await import('@/services/mediaService');
      
      const result = await mediaService.uploadFileWithFallback(mediaFile.value, {
        optimizeImage: mediaType.value === 'image' && (resizeEnabled.value || imageQuality.value < 100),
        imageQuality: imageQuality.value,
        maxWidth: resizeEnabled.value ? resizeWidth.value : undefined,
        maxHeight: resizeEnabled.value ? resizeHeight.value : undefined,
        onProgress: (progress) => {
          uploadProgress.value = progress;
        },
        resumeUpload: true,
        uploadId: uploadId.value,
        uploadedBytes: uploadedBytes.value
      });
      
      if (result.success && result.mediaId) {
        uploadedMediaId.value = result.mediaId;
        uploadedMediaUrl.value = result.url || '';
        uploadState.value = 'uploaded';
        
        // Sauvegarder le média dans l'historique local
        const mediaData = {
          mediaId: result.mediaId,
          type: mediaType.value,
          mimeType: mediaFile.value.type,
          filename: mediaFile.value.name,
          size: mediaFile.value.size,
          caption: mediaCaption.value,
          url: result.url || previewUrl.value,
          thumbnailUrl: mediaType.value === 'image' ? (result.url || previewUrl.value) : undefined,
          timestamp: new Date().toISOString(),
          favorite: false
        };
        
        saveMediaToLocalStorage(mediaData);
        
        // Notifier le succès
        $q.notify({
          type: 'positive',
          message: 'Média uploadé avec succès',
          caption: `Media ID: ${uploadedMediaId.value}`
        });
        
        // Émettre l'événement pour le parent
        emit('upload-complete', {
          mediaId: uploadedMediaId.value,
          mediaType: mediaType.value,
          caption: mediaCaption.value,
          file: mediaFile.value,
          url: result.url || previewUrl.value
        });
      } else if (result.resumable) {
        // Mise à jour des informations de reprise
        uploadError.value = result.error || 'Erreur réseau lors de l\'upload';
        canResumeUpload.value = true;
        uploadId.value = result.uploadId || '';
        uploadedBytes.value = result.uploadedBytes || 0;
        uploadProgress.value = result.uploadedBytes && result.totalBytes 
          ? Math.round((result.uploadedBytes / result.totalBytes) * 100) 
          : 0;
        
        // Si l'utilisateur a explicitement lancé une reprise, revenir à l'état d'erreur
        uploadState.value = 'error';
        
        $q.notify({
          type: 'warning',
          message: 'Upload interrompu à nouveau',
          caption: 'Vous pourrez réessayer ultérieurement'
        });
      } else {
        throw new Error(result.error || 'Upload échoué');
      }
    } catch (error: any) {
      console.error('Error during upload resume:', error);
      uploadState.value = 'error';
      uploadError.value = error.message || 'Erreur lors de la reprise de l\'upload';
      
      $q.notify({
        type: 'negative',
        message: 'Échec de la reprise',
        caption: uploadError.value
      });
    }
  };
  
  resumeUploadOperation();
}

function pauseUpload() {
  if (uploadState.value === 'uploading') {
    uploadState.value = 'paused';
    
    // Stocker les informations nécessaires pour reprendre l'upload plus tard
    canResumeUpload.value = true;
    if (!uploadId.value) {
      uploadId.value = crypto.randomUUID();
    }
    
    $q.notify({
      type: 'info',
      message: 'Upload mis en pause',
      caption: 'Vous pourrez le reprendre ultérieurement'
    });
  }
}

function resetForm() {
  mediaFile.value = null;
  mediaCaption.value = '';
  previewUrl.value = '';
  uploadedMediaId.value = '';
  uploadedMediaUrl.value = '';
  uploadState.value = 'idle';
  uploadProgress.value = 0;
  uploadError.value = '';
  uploadId.value = '';
  uploadedBytes.value = 0;
  canResumeUpload.value = false;
}

function useMedia() {
  if (!uploadedMediaId.value) return;
  
  emit('media-selected', {
    mediaId: uploadedMediaId.value,
    mediaType: mediaType.value,
    caption: mediaCaption.value,
    url: previewUrl.value,
    file: mediaFile.value
  });
}

// Gestion de la bibliothèque de médias
function loadMediaFromLocalStorage() {
  try {
    // Charger les médias récents
    const storedMedia = localStorage.getItem('media-library-recent');
    if (storedMedia) {
      recentMedia.value = JSON.parse(storedMedia);
    }
    
    // Charger les favoris
    const storedFavorites = localStorage.getItem('media-library-favorites');
    if (storedFavorites) {
      favoriteMedia.value = JSON.parse(storedFavorites);
    }
  } catch (error) {
    console.error('Error loading media from local storage:', error);
  }
}

function saveMediaToLocalStorage(mediaData: any) {
  try {
    // Ajouter aux médias récents
    recentMedia.value = [mediaData, ...recentMedia.value.slice(0, 19)]; // Keep max 20 recent items
    localStorage.setItem('media-library-recent', JSON.stringify(recentMedia.value));
  } catch (error) {
    console.error('Error saving media to local storage:', error);
  }
}

function addToFavorites(media: any = null) {
  const mediaToFavorite = media || {
    mediaId: uploadedMediaId.value,
    type: mediaType.value,
    mimeType: mediaFile.value?.type,
    filename: mediaFile.value?.name,
    size: mediaFile.value?.size,
    caption: mediaCaption.value,
    url: previewUrl.value,
    thumbnailUrl: previewUrl.value,
    timestamp: new Date().toISOString(),
    favorite: true
  };
  
  if (!mediaToFavorite.mediaId) return;
  
  try {
    // Vérifier si le média est déjà dans les favoris
    const existingIndex = favoriteMedia.value.findIndex(m => m.mediaId === mediaToFavorite.mediaId);
    
    if (existingIndex === -1) {
      // Ajouter aux favoris
      favoriteMedia.value = [{ ...mediaToFavorite, favorite: true }, ...favoriteMedia.value];
      localStorage.setItem('media-library-favorites', JSON.stringify(favoriteMedia.value));
      
      $q.notify({
        type: 'positive',
        message: 'Ajouté aux favoris',
        icon: 'star'
      });
    }
  } catch (error) {
    console.error('Error adding to favorites:', error);
  }
}

function removeFromFavorites(media: any) {
  if (!media || !media.mediaId) return;
  
  try {
    // Filtrer le média des favoris
    favoriteMedia.value = favoriteMedia.value.filter(m => m.mediaId !== media.mediaId);
    localStorage.setItem('media-library-favorites', JSON.stringify(favoriteMedia.value));
    
    $q.notify({
      type: 'info',
      message: 'Retiré des favoris',
      icon: 'star_outline'
    });
  } catch (error) {
    console.error('Error removing from favorites:', error);
  }
}

function removeMedia(media: any) {
  if (!media || !media.mediaId) return;
  
  try {
    // Demander confirmation
    $q.dialog({
      title: 'Confirmer la suppression',
      message: 'Êtes-vous sûr de vouloir supprimer ce média ?',
      cancel: true,
      persistent: true
    }).onOk(() => {
      // Supprimer des récents
      recentMedia.value = recentMedia.value.filter(m => m.mediaId !== media.mediaId);
      localStorage.setItem('media-library-recent', JSON.stringify(recentMedia.value));
      
      // Supprimer des favoris si présent
      favoriteMedia.value = favoriteMedia.value.filter(m => m.mediaId !== media.mediaId);
      localStorage.setItem('media-library-favorites', JSON.stringify(favoriteMedia.value));
      
      $q.notify({
        type: 'info',
        message: 'Média supprimé',
        icon: 'delete'
      });
    });
  } catch (error) {
    console.error('Error removing media:', error);
  }
}

function selectMedia(media: any) {
  selectedMedia.value = media;
  mediaDetailDialog.value = true;
}

function useSelectedMedia() {
  if (!selectedMedia.value) return;
  
  emit('media-selected', {
    mediaId: selectedMedia.value.mediaId,
    mediaType: selectedMedia.value.type,
    caption: selectedMedia.value.caption,
    url: selectedMedia.value.url
  });
  
  mediaDetailDialog.value = false;
}

// Initialisation
onMounted(() => {
  loadMediaFromLocalStorage();
});

// Observateurs
watch(mediaType, () => {
  // Réinitialiser le fichier lorsqu'on change de type
  if (mediaFile.value) {
    mediaFile.value = null;
    previewUrl.value = '';
  }
});
</script>

<style lang="scss" scoped>
.media-library {
  max-width: 900px;
  margin: 0 auto;
  
  .preview-image, .preview-video {
    max-width: 100%;
    max-height: 300px;
    margin: 0 auto;
    display: block;
    border-radius: 4px;
  }
  
  .preview-audio {
    width: 100%;
    margin: 20px 0;
  }
  
  .preview-document {
    text-align: center;
    padding: 30px;
  }
  
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
  
  .media-detail-container {
    text-align: center;
    
    .detail-preview {
      max-width: 100%;
      max-height: 50vh;
      margin: 0 auto;
      display: block;
      border-radius: 4px;
    }
    
    .detail-preview-audio {
      width: 100%;
      margin: 20px 0;
    }
    
    .detail-preview-document {
      text-align: center;
      padding: 30px;
    }
  }
}
</style>