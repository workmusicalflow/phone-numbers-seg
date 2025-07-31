<template>
  <div class="whatsapp-media-upload">
    <q-card class="modern-card">
      <q-card-section class="card-header">
        <div class="header-content">
          <div class="header-icon-wrapper">
            <q-icon name="attachment" size="md" />
          </div>
          <div class="header-text">
            <h3 class="card-title">Envoyer un média WhatsApp</h3>
            <p class="card-subtitle">Partagez des images, vidéos, documents et fichiers audio</p>
          </div>
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section class="content-section">
        <!-- Destinataire -->
        <div class="input-group">
          <label class="input-label">
            <q-icon name="person" class="q-mr-xs" />
            Destinataire
          </label>
          <q-input
            v-model="recipient"
            placeholder="Numéro de téléphone (ex: +225 XX XX XX XX)"
            outlined
            class="modern-input"
            :rules="[val => !!val || 'Le numéro est requis', phoneNumberRule]"
            :disable="uploadState !== 'idle' || sendState !== 'idle'"
          >
            <template v-slot:prepend>
              <q-icon name="phone" color="green" />
            </template>
          </q-input>
        </div>

        <!-- Zone d'upload -->
        <div class="input-group">
          <label class="input-label">
            <q-icon name="attachment" class="q-mr-xs" />
            Fichier à envoyer
          </label>
          
          <!-- Zone de drag & drop moderne -->
          <div class="file-upload-area" :class="{ 'file-selected': mediaFile, 'upload-disabled': uploadState !== 'idle' }">
            <q-file
              v-model="mediaFile"
              outlined
              max-file-size="104857600"
              accept=".jpg,.jpeg,.png,.mp4,.pdf,.doc,.docx,.xls,.xlsx,.mp3,.aac,.ogg,.webp"
              @update:model-value="handleFileChange"
              @rejected="onRejected"
              :disable="uploadState !== 'idle'"
              class="modern-file-input"
            >
              <template v-slot:prepend>
                <q-icon name="attach_file" color="green" />
              </template>
              <template v-slot:append>
                <q-icon 
                  v-if="mediaFile && uploadState === 'idle'"
                  name="close" 
                  class="cursor-pointer text-negative" 
                  @click.stop.prevent="mediaFile = null"
                />
              </template>
            </q-file>
            
            <!-- Zone visuelle de drag & drop -->
            <div v-if="!mediaFile" class="upload-placeholder">
              <q-icon name="cloud_upload" size="3rem" color="grey-5" />
              <p class="upload-text">Cliquez ou glissez votre fichier ici</p>
              <p class="upload-formats">Images, vidéos, documents, audio (max 100MB)</p>
            </div>
          </div>
          
          <div v-if="mediaFile" class="file-info">
            <q-icon :name="getFileIcon()" color="green" class="q-mr-sm" />
            <span class="file-name">{{ mediaFile.name }}</span>
            <span class="file-size">{{ formatFileSize(mediaFile.size) }}</span>
          </div>
        </div>

        <!-- État de l'upload avec feedback visuel -->
        <div v-if="uploadState !== 'idle'" class="status-section">
          <q-card 
            class="status-card"
            :class="{
              'upload-status': uploadState === 'uploading',
              'success-status': uploadState === 'uploaded',
              'error-status': uploadState === 'error'
            }"
          >
            <q-card-section class="status-content">
              <div class="status-header">
                <div class="status-icon">
                  <q-spinner-dots v-if="uploadState === 'uploading'" color="white" size="md" />
                  <q-icon v-else-if="uploadState === 'uploaded'" name="cloud_done" size="md" />
                  <q-icon v-else name="error" size="md" />
                </div>
                <div class="status-text">
                  <h4 class="status-title">
                    <template v-if="uploadState === 'uploading'">
                      Upload en cours
                    </template>
                    <template v-else-if="uploadState === 'uploaded'">
                      Fichier uploadé
                    </template>
                    <template v-else>
                      Erreur d'upload
                    </template>
                  </h4>
                  <p class="status-subtitle">
                    <template v-if="uploadState === 'uploading'">
                      Envoi vers les serveurs WhatsApp...
                    </template>
                    <template v-else-if="uploadState === 'uploaded'">
                      Prêt à être envoyé
                    </template>
                    <template v-else>
                      {{ uploadError }}
                    </template>
                  </p>
                </div>
              </div>
              
              <!-- Barre de progression pour l'upload -->
              <div v-if="uploadState === 'uploading'" class="progress-section">
                <q-linear-progress
                  :value="uploadProgress / 100"
                  color="white"
                  track-color="rgba(255,255,255,0.3)"
                  size="8px"
                  rounded
                />
                <span class="progress-text">{{ uploadProgress }}%</span>
              </div>
              
              <div v-if="uploadState === 'uploaded'" class="media-id-info">
                <q-icon name="tag" class="q-mr-xs" />
                Media ID: {{ uploadedMediaId }}
              </div>
              
              <div v-if="uploadState === 'error'" class="error-actions">
                <q-btn 
                  flat 
                  color="white" 
                  label="Réessayer"
                  icon="refresh"
                  @click="retryUpload"
                />
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- Zone de légende (seulement si le média supporte les légendes) -->
        <div v-if="uploadState === 'uploaded' && (mediaType === 'image' || mediaType === 'video' || mediaType === 'document')" class="input-group">
          <label class="input-label">
            <q-icon name="edit" class="q-mr-xs" />
            Légende (optionnel)
          </label>
          <q-input
            v-model="caption"
            placeholder="Ajoutez une légende à votre média..."
            type="textarea"
            outlined
            class="modern-input caption-input"
            autogrow
            maxlength="1024"
          />
          <div class="character-count">
            {{ caption.length }}/1024 caractères
          </div>
        </div>

        <!-- Prévisualisation du média -->
        <div v-if="mediaPreview && uploadState !== 'idle'" class="preview-section">
          <label class="input-label">
            <q-icon name="preview" class="q-mr-xs" />
            Aperçu du média
          </label>
          <q-card class="preview-card">
            <q-card-section class="preview-content">
              <img 
                v-if="mediaType === 'image'" 
                :src="mediaPreview" 
                alt="Preview" 
                class="preview-image"
              />
              <video 
                v-else-if="mediaType === 'video'" 
                :src="mediaPreview" 
                controls 
                class="preview-video"
              />
              <div v-else-if="mediaType === 'audio'" class="preview-audio">
                <div class="audio-icon">
                  <q-icon name="audiotrack" size="3rem" color="green" />
                </div>
                <audio :src="mediaPreview" controls class="audio-player" />
                <p class="audio-filename">{{ mediaFile?.name }}</p>
              </div>
              <div v-else-if="mediaType === 'document'" class="preview-document">
                <div class="document-icon">
                  <q-icon :name="getDocumentIcon()" size="4rem" color="green" />
                </div>
                <div class="document-info">
                  <h4 class="document-name">{{ mediaFile?.name }}</h4>
                  <p class="document-size">{{ formatFileSize(mediaFile?.size || 0) }}</p>
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- État d'envoi avec feedback -->
        <div v-if="sendState !== 'idle'" class="status-section">
          <q-card 
            class="status-card"
            :class="{
              'sending-status': sendState === 'sending',
              'success-status': sendState === 'sent',
              'error-status': sendState === 'error'
            }"
          >
            <q-card-section class="status-content">
              <div class="status-header">
                <div class="status-icon">
                  <q-spinner-dots v-if="sendState === 'sending'" color="white" size="md" />
                  <q-icon v-else-if="sendState === 'sent'" name="done_all" size="md" />
                  <q-icon v-else name="error" size="md" />
                </div>
                <div class="status-text">
                  <h4 class="status-title">
                    <template v-if="sendState === 'sending'">
                      Envoi en cours
                    </template>
                    <template v-else-if="sendState === 'sent'">
                      Message envoyé !
                    </template>
                    <template v-else>
                      Erreur d'envoi
                    </template>
                  </h4>
                  <p class="status-subtitle">
                    <template v-if="sendState === 'sending'">
                      Transmission du message via WhatsApp...
                    </template>
                    <template v-else-if="sendState === 'sent'">
                      Votre média a été livré avec succès
                    </template>
                    <template v-else>
                      {{ sendError }}
                    </template>
                  </p>
                </div>
              </div>
              
              <div v-if="sendState === 'error'" class="error-actions">
                <q-btn 
                  flat 
                  color="white" 
                  label="Réessayer"
                  icon="refresh"
                  @click="retrySend"
                />
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- Actions -->
        <div class="action-buttons">
          <q-btn
            v-if="sendState === 'sent'"
            class="action-btn success-btn"
            color="positive"
            icon="refresh"
            label="Nouveau message"
            @click="resetAll"
          />
          <q-btn
            v-else-if="uploadState === 'idle' && mediaFile"
            class="action-btn primary-btn"
            color="green"
            icon="cloud_upload"
            label="Uploader le fichier"
            @click="startUpload"
            :disable="!mediaFile"
          />
          <q-btn
            v-else-if="uploadState === 'uploaded'"
            class="action-btn primary-btn"
            color="green"
            icon="send"
            label="Envoyer le message"
            :loading="sendState === 'sending'"
            :disable="!recipient || sendState !== 'idle'"
            @click="sendMediaMessage"
          />
          <q-btn
            v-if="uploadState !== 'idle' && sendState === 'idle'"
            class="action-btn secondary-btn"
            color="grey-7"
            outline
            icon="close"
            label="Annuler"
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

// Obtenir l'icône du fichier selon son type
function getFileIcon(): string {
  if (!mediaFile.value) return 'attachment';
  
  const mimeType = mediaFile.value.type;
  const fileName = mediaFile.value.name.toLowerCase();
  
  if (mimeType.startsWith('image/')) return 'image';
  if (mimeType.startsWith('video/')) return 'videocam';
  if (mimeType.startsWith('audio/')) return 'audiotrack';
  
  // Documents spécifiques
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
  max-width: 800px;
  margin: 0 auto;
  padding: 16px;
}

// Modern card styling
.modern-card {
  background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
  border-radius: 16px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(229, 231, 235, 0.8);
  overflow: hidden;
  transition: all 0.3s ease;

  &:hover {
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
  }
}

// Card header
.card-header {
  background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
  color: white;
  padding: 24px;

  .header-content {
    display: flex;
    align-items: center;
    gap: 16px;

    .header-icon-wrapper {
      width: 56px;
      height: 56px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;

      .q-icon {
        font-size: 1.75rem;
      }
    }

    .header-text {
      flex: 1;

      .card-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin: 0 0 4px 0;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      }

      .card-subtitle {
        font-size: 0.95rem;
        opacity: 0.9;
        margin: 0;
      }
    }
  }
}

// Content section
.content-section {
  padding: 32px 24px;
}

// Input groups
.input-group {
  margin-bottom: 24px;

  .input-label {
    display: flex;
    align-items: center;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
    font-size: 0.95rem;
  }
}

.modern-input {
  :deep(.q-field__control) {
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    border: 1px solid #e5e7eb;
    transition: all 0.2s ease;

    &:hover {
      border-color: #d1d5db;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    &:focus-within {
      border-color: #25d366;
      box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.1);
    }
  }

  :deep(.q-field__native) {
    padding: 12px 16px;
  }
}

// File upload area
.file-upload-area {
  position: relative;
  border: 2px dashed #d1d5db;
  border-radius: 12px;
  background: #fafbfc;
  min-height: 140px;
  transition: all 0.2s ease;
  overflow: hidden;

  &:hover {
    border-color: #25d366;
    background: #f0fff4;
  }

  &.file-selected {
    border-color: #25d366;
    background: #f0fff4;
  }

  &.upload-disabled {
    opacity: 0.6;
    pointer-events: none;
  }

  .modern-file-input {
    position: relative;
    z-index: 2;

    :deep(.q-field__control) {
      border: none;
      box-shadow: none;
      background: transparent;
    }
  }

  .upload-placeholder {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    min-height: 120px;
    pointer-events: none;
    z-index: 1;

    .upload-text {
      font-size: 1.1rem;
      font-weight: 600;
      color: #374151;
      margin: 12px 0 4px 0;
    }

    .upload-formats {
      font-size: 0.9rem;
      color: #6b7280;
      margin: 0;
    }
  }
}

.file-info {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 8px;
  padding: 12px 16px;
  background: #f0fff4;
  border-radius: 8px;
  border: 1px solid #25d366;

  .file-name {
    flex: 1;
    font-weight: 500;
    color: #374151;
    word-break: break-all;
  }

  .file-size {
    font-size: 0.85rem;
    color: #6b7280;
    white-space: nowrap;
  }
}

.character-count {
  font-size: 0.8rem;
  color: #6b7280;
  text-align: right;
  margin-top: 4px;
}

// Status sections
.status-section {
  margin-bottom: 24px;
}

.status-card {
  border-radius: 12px;
  overflow: hidden;
  border: none;

  &.upload-status {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
  }

  &.sending-status {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
  }

  &.success-status {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
  }

  &.error-status {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
  }

  .status-content {
    padding: 20px 24px;
  }

  .status-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 16px;

    .status-icon {
      width: 48px;
      height: 48px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .status-text {
      flex: 1;

      .status-title {
        font-size: 1.2rem;
        font-weight: 600;
        margin: 0 0 4px 0;
      }

      .status-subtitle {
        font-size: 0.9rem;
        opacity: 0.9;
        margin: 0;
      }
    }
  }

  .progress-section {
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 12px;

    .q-linear-progress {
      flex: 1;
    }

    .progress-text {
      font-size: 0.9rem;
      font-weight: 600;
      white-space: nowrap;
    }
  }

  .media-id-info {
    font-size: 0.85rem;
    opacity: 0.8;
    display: flex;
    align-items: center;
  }

  .error-actions {
    margin-top: 12px;
  }
}

// Preview section
.preview-section {
  margin-bottom: 24px;
}

.preview-card {
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  overflow: hidden;

  .preview-content {
    padding: 20px;
  }

  .preview-image,
  .preview-video {
    width: 100%;
    max-height: 400px;
    object-fit: contain;
    border-radius: 8px;
  }

  .preview-audio {
    text-align: center;
    padding: 20px;

    .audio-icon {
      margin-bottom: 16px;
    }

    .audio-player {
      width: 100%;
      margin-bottom: 12px;
    }

    .audio-filename {
      font-size: 0.9rem;
      color: #6b7280;
      margin: 0;
    }
  }

  .preview-document {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;

    .document-icon {
      flex-shrink: 0;
    }

    .document-info {
      flex: 1;

      .document-name {
        font-size: 1.1rem;
        font-weight: 600;
        color: #374151;
        margin: 0 0 4px 0;
        word-break: break-all;
      }

      .document-size {
        font-size: 0.9rem;
        color: #6b7280;
        margin: 0;
      }
    }
  }
}

// Action buttons
.action-buttons {
  display: flex;
  gap: 16px;
  justify-content: center;
  margin-top: 32px;
  padding-top: 24px;
  border-top: 1px solid #f3f4f6;

  .action-btn {
    border-radius: 12px;
    font-weight: 600;
    padding: 12px 24px;
    text-transform: none;
    transition: all 0.2s ease;
    min-width: 160px;

    &.primary-btn {
      background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
      box-shadow: 0 4px 16px rgba(37, 211, 102, 0.3);

      &:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(37, 211, 102, 0.4);
      }
    }

    &.success-btn {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      box-shadow: 0 4px 16px rgba(16, 185, 129, 0.3);

      &:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(16, 185, 129, 0.4);
      }
    }

    &.secondary-btn {
      border: 2px solid #e5e7eb;
      color: #6b7280;

      &:hover {
        border-color: #d1d5db;
        background: #f9fafb;
      }
    }
  }
}

// Responsive design
@media (max-width: 768px) {
  .whatsapp-media-upload {
    padding: 8px;
  }

  .card-header {
    padding: 20px 16px;

    .header-content {
      flex-direction: column;
      text-align: center;
      gap: 12px;

      .header-icon-wrapper {
        width: 48px;
        height: 48px;
      }

      .card-title {
        font-size: 1.3rem;
      }
    }
  }

  .content-section {
    padding: 24px 16px;
  }

  .action-buttons {
    flex-direction: column;

    .action-btn {
      width: 100%;
      min-width: auto;
    }
  }

  .status-card .status-header {
    flex-direction: column;
    text-align: center;
    gap: 12px;
  }

  .preview-document {
    flex-direction: column;
    text-align: center;
    gap: 12px;
  }
}

@media (max-width: 480px) {
  .card-header {
    padding: 16px 12px;
  }

  .content-section {
    padding: 20px 12px;
  }

  .file-upload-area {
    min-height: 120px;
  }

  .upload-placeholder {
    padding: 25px 15px;
    min-height: 100px;

    .upload-text {
      font-size: 1rem;
    }

    .upload-formats {
      font-size: 0.8rem;
    }
  }
}
</style>