<template>
  <div class="whatsapp-media-upload">
    <q-card flat bordered>
      <q-card-section>
        <div class="text-h6">
          <q-icon name="attachment" size="28px" class="q-mr-sm" />
          Envoyer un média
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section>
        <!-- Destinataire -->
        <q-input
          v-model="recipient"
          label="Numéro de téléphone du destinataire"
          outlined
          :rules="[val => !!val || 'Le numéro est requis', phoneNumberRule]"
          class="q-mb-md"
          :disable="uploadState !== 'idle' || sendState !== 'idle'"
        >
          <template v-slot:prepend>
            <q-icon name="phone" />
          </template>
        </q-input>

        <!-- Zone d'upload -->
        <div class="q-mb-md">
          <q-file
            v-model="mediaFile"
            label="Sélectionner un fichier"
            outlined
            max-file-size="104857600"
            accept=".jpg,.jpeg,.png,.mp4,.pdf,.doc,.docx,.xls,.xlsx,.mp3,.aac,.ogg,.webp"
            @update:model-value="handleFileChange"
            @rejected="onRejected"
            :disable="uploadState !== 'idle'"
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
          </q-file>
          
          <div v-if="mediaFile" class="text-caption text-grey q-mt-xs">
            {{ formatFileSize(mediaFile.size) }} - {{ mediaFile.type }}
          </div>
        </div>

        <!-- État de l'upload avec feedback visuel -->
        <q-banner 
          v-if="uploadState !== 'idle'" 
          :class="{
            'bg-info text-white': uploadState === 'uploading',
            'bg-positive text-white': uploadState === 'uploaded',
            'bg-negative text-white': uploadState === 'error'
          }"
          class="q-mb-md"
          rounded
        >
          <template v-slot:avatar>
            <q-spinner-dots v-if="uploadState === 'uploading'" color="white" size="24px" />
            <q-icon v-else-if="uploadState === 'uploaded'" name="cloud_done" />
            <q-icon v-else name="error" />
          </template>
          
          <div class="text-weight-medium">
            <template v-if="uploadState === 'uploading'">
              Upload en cours vers les serveurs Meta...
            </template>
            <template v-else-if="uploadState === 'uploaded'">
              Fichier uploadé avec succès
            </template>
            <template v-else>
              Erreur lors de l'upload
            </template>
          </div>
          
          <div v-if="uploadState === 'uploaded'" class="text-caption">
            Media ID: {{ uploadedMediaId }}
          </div>
          <div v-else-if="uploadState === 'error'" class="text-caption">
            {{ uploadError }}
          </div>
          
          <!-- Barre de progression pour l'upload -->
          <q-linear-progress
            v-if="uploadState === 'uploading'"
            :value="uploadProgress / 100"
            color="white"
            track-color="blue-3"
            size="6px"
            class="q-mt-sm"
            rounded
          />
          
          <template v-slot:action>
            <q-btn 
              v-if="uploadState === 'error'"
              flat 
              color="white" 
              label="Réessayer"
              @click="retryUpload"
            />
          </template>
        </q-banner>

        <!-- Zone de légende (seulement si le média supporte les légendes) -->
        <div v-if="uploadState === 'uploaded' && (mediaType === 'image' || mediaType === 'video' || mediaType === 'document')" class="q-mb-md">
          <q-input
            v-model="caption"
            label="Légende (optionnel)"
            type="textarea"
            outlined
            autogrow
            maxlength="1024"
          />
          <div class="text-caption text-grey q-mt-xs">
            {{ caption.length }}/1024 caractères
          </div>
        </div>

        <!-- Prévisualisation du média -->
        <div v-if="mediaPreview && uploadState !== 'idle'" class="media-preview q-mb-md">
          <div class="text-subtitle2 q-mb-sm">Aperçu du média :</div>
          <q-card flat bordered>
            <q-card-section>
              <img v-if="mediaType === 'image'" :src="mediaPreview" alt="Preview" style="max-width: 100%; max-height: 300px;" />
              <video v-else-if="mediaType === 'video'" :src="mediaPreview" controls style="max-width: 100%; max-height: 300px;" />
              <div v-else-if="mediaType === 'audio'" class="audio-preview">
                <q-icon name="audiotrack" size="48px" color="primary" />
                <audio :src="mediaPreview" controls style="width: 100%; margin-top: 10px;" />
              </div>
              <div v-else-if="mediaType === 'document'" class="document-preview text-center q-py-lg">
                <q-icon :name="getDocumentIcon()" size="64px" color="primary" />
                <div class="text-subtitle2 q-mt-sm">{{ mediaFile?.name }}</div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- État d'envoi avec feedback -->
        <q-banner 
          v-if="sendState !== 'idle'" 
          :class="{
            'bg-info text-white': sendState === 'sending',
            'bg-positive text-white': sendState === 'sent',
            'bg-negative text-white': sendState === 'error'
          }"
          class="q-mb-md"
          rounded
        >
          <template v-slot:avatar>
            <q-spinner-dots v-if="sendState === 'sending'" color="white" size="24px" />
            <q-icon v-else-if="sendState === 'sent'" name="done_all" />
            <q-icon v-else name="error" />
          </template>
          
          <div class="text-weight-medium">
            <template v-if="sendState === 'sending'">
              Envoi du message en cours...
            </template>
            <template v-else-if="sendState === 'sent'">
              Message envoyé avec succès !
            </template>
            <template v-else>
              Erreur lors de l'envoi
            </template>
          </div>
          
          <div v-if="sendState === 'error'" class="text-caption">
            {{ sendError }}
          </div>
          
          <template v-slot:action>
            <q-btn 
              v-if="sendState === 'error'"
              flat 
              color="white" 
              label="Réessayer"
              @click="retrySend"
            />
          </template>
        </q-banner>

        <!-- Actions -->
        <div class="row justify-end q-gutter-sm">
          <q-btn
            v-if="sendState === 'sent'"
            label="Nouveau message"
            color="positive"
            icon="refresh"
            @click="resetAll"
          />
          <q-btn
            v-else-if="uploadState === 'idle' && mediaFile"
            label="Uploader le fichier"
            color="primary"
            icon="cloud_upload"
            @click="startUpload"
            :disable="!mediaFile"
          />
          <q-btn
            v-else-if="uploadState === 'uploaded'"
            label="Envoyer le message"
            color="primary"
            icon="send"
            :loading="sendState === 'sending'"
            :disable="!recipient || sendState !== 'idle'"
            @click="sendMediaMessage"
          />
          <q-btn
            v-if="uploadState !== 'idle' && sendState === 'idle'"
            label="Annuler"
            color="negative"
            outline
            @click="cancelUpload"
          />
        </div>
      </q-card-section>
    </q-card>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useQuasar } from 'quasar';
import { api } from '@/services/api';
import { useWhatsAppStore } from '@/stores/whatsappStore';

const $q = useQuasar();
const whatsAppStore = useWhatsAppStore();

// États
type UploadState = 'idle' | 'uploading' | 'uploaded' | 'error';
type SendState = 'idle' | 'sending' | 'sent' | 'error';

// Données locales
const recipient = ref('');
const mediaFile = ref<File | null>(null);
const caption = ref('');
const mediaType = ref<string>('');
const mediaPreview = ref<string>('');
const uploadProgress = ref(0);
const uploadedMediaId = ref<string>('');

// États de l'application
const uploadState = ref<UploadState>('idle');
const sendState = ref<SendState>('idle');
const uploadError = ref<string>('');
const sendError = ref<string>('');

// Règle de validation pour le numéro de téléphone
function phoneNumberRule(val: string) {
  const digitsOnly = val.replace(/\s+/g, '').replace(/^\+/, '');
  return digitsOnly.length >= 10 || 'Numéro de téléphone invalide';
}

// Normaliser le numéro de téléphone
function normalizePhoneNumber(phoneNumber: string): string {
  let number = phoneNumber.replace(/[^0-9]/g, '');
  if (!number.startsWith('225')) {
    number = '225' + number;
  }
  return number;
}

// Gérer le changement de fichier
function handleFileChange(file: File | null) {
  if (!file) {
    mediaType.value = '';
    mediaPreview.value = '';
    return;
  }

  // Déterminer le type de média
  const mimeType = file.type;
  if (mimeType.startsWith('image/')) {
    mediaType.value = mimeType === 'image/webp' ? 'sticker' : 'image';
  } else if (mimeType.startsWith('video/')) {
    mediaType.value = 'video';
  } else if (mimeType.startsWith('audio/')) {
    mediaType.value = 'audio';
  } else {
    mediaType.value = 'document';
  }

  // Créer une prévisualisation pour les images, vidéos et audio
  if (['image', 'video', 'audio', 'sticker'].includes(mediaType.value)) {
    const reader = new FileReader();
    reader.onload = (e) => {
      mediaPreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
  }
}

// Formater la taille du fichier
function formatFileSize(bytes: number): string {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

// Obtenir l'icône du document
function getDocumentIcon(): string {
  if (!mediaFile.value) return 'description';
  
  const fileName = mediaFile.value.name.toLowerCase();
  if (fileName.endsWith('.pdf')) return 'picture_as_pdf';
  if (fileName.endsWith('.doc') || fileName.endsWith('.docx')) return 'description';
  if (fileName.endsWith('.xls') || fileName.endsWith('.xlsx')) return 'table_chart';
  if (fileName.endsWith('.ppt') || fileName.endsWith('.pptx')) return 'slideshow';
  return 'insert_drive_file';
}

// Gérer le rejet de fichier
function onRejected(entries: any) {
  $q.notify({
    type: 'negative',
    message: `Fichier rejeté: ${entries[0]?.failedPropValidation || 'Erreur inconnue'}`
  });
}

// Commencer l'upload
async function startUpload() {
  if (!mediaFile.value) return;
  
  uploadState.value = 'uploading';
  uploadProgress.value = 0;
  uploadError.value = '';
  
  try {
    uploadedMediaId.value = await uploadMedia();
    uploadState.value = 'uploaded';
    $q.notify({
      type: 'positive',
      message: 'Fichier uploadé avec succès',
      caption: `Media ID: ${uploadedMediaId.value}`
    });
  } catch (error: any) {
    uploadState.value = 'error';
    uploadError.value = error.message || 'Erreur lors de l\'upload';
    $q.notify({
      type: 'negative',
      message: 'Erreur lors de l\'upload',
      caption: uploadError.value
    });
  }
}

// Uploader le média
async function uploadMedia(): Promise<string> {
  if (!mediaFile.value) throw new Error('Aucun fichier sélectionné');
  
  console.log('[WhatsApp Upload] Starting upload for file:', mediaFile.value.name);
  console.log('[WhatsApp Upload] File type:', mediaFile.value.type);
  console.log('[WhatsApp Upload] File size:', mediaFile.value.size);
  
  const formData = new FormData();
  formData.append('file', mediaFile.value);
  
  console.log('[WhatsApp Upload] Posting to:', '/whatsapp/upload.php');
  console.log('[WhatsApp Upload] API base URL:', api.defaults.baseURL);
  
  try {
    const response = await api.post('/whatsapp/upload.php', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
      onUploadProgress: (progressEvent) => {
        if (progressEvent.total) {
          uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total);
          console.log('[WhatsApp Upload] Progress:', uploadProgress.value + '%');
        }
      }
    });
    
    console.log('[WhatsApp Upload] Response:', response.data);
    
    if (response.data.success && response.data.mediaId) {
      console.log('[WhatsApp Upload] Media ID:', response.data.mediaId);
      return response.data.mediaId;
    } else {
      throw new Error(response.data.error || 'Upload échoué');
    }
  } catch (error: any) {
    console.error('[WhatsApp Upload] Error:', error);
    console.error('[WhatsApp Upload] Error response:', error.response?.data);
    throw error;
  }
}

// Envoyer le message média
async function sendMediaMessage() {
  if (!recipient.value || !uploadedMediaId.value) return;
  
  sendState.value = 'sending';
  sendError.value = '';
  
  try {
    const normalizedRecipient = normalizePhoneNumber(recipient.value);
    
    // Envoyer le message média
    await whatsAppStore.sendMediaMessage({
      recipient: normalizedRecipient,
      type: mediaType.value === 'sticker' ? 'image' : mediaType.value,
      mediaIdOrUrl: uploadedMediaId.value,
      caption: caption.value || undefined
    });
    
    sendState.value = 'sent';
    $q.notify({
      type: 'positive',
      message: 'Message envoyé avec succès',
      icon: 'done_all'
    });
    
  } catch (error: any) {
    sendState.value = 'error';
    sendError.value = error.message || 'Erreur lors de l\'envoi';
    $q.notify({
      type: 'negative',
      message: 'Erreur lors de l\'envoi',
      caption: sendError.value
    });
  }
}

// Réessayer l'upload
function retryUpload() {
  uploadState.value = 'idle';
  uploadError.value = '';
  startUpload();
}

// Réessayer l'envoi
function retrySend() {
  sendState.value = 'idle';
  sendError.value = '';
  sendMediaMessage();
}

// Annuler l'upload/sélection
function cancelUpload() {
  mediaFile.value = null;
  caption.value = '';
  mediaType.value = '';
  mediaPreview.value = '';
  uploadedMediaId.value = '';
  uploadProgress.value = 0;
  uploadState.value = 'idle';
  uploadError.value = '';
}

// Réinitialiser tout
function resetAll() {
  recipient.value = '';
  mediaFile.value = null;
  caption.value = '';
  mediaType.value = '';
  mediaPreview.value = '';
  uploadedMediaId.value = '';
  uploadProgress.value = 0;
  uploadState.value = 'idle';
  sendState.value = 'idle';
  uploadError.value = '';
  sendError.value = '';
}

// Observer les changements de fichier
watch(mediaFile, handleFileChange);
</script>

<style lang="scss" scoped>
.whatsapp-media-upload {
  max-width: 600px;
  margin: 0 auto;

  .media-preview {
    img, video {
      width: 100%;
      height: auto;
      border-radius: 4px;
    }
    
    .audio-preview,
    .document-preview {
      text-align: center;
      padding: 20px;
    }
  }
  
  .q-banner {
    transition: all 0.3s ease;
  }
}
</style>