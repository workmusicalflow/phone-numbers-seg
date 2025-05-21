<template>
  <div class="whatsapp-template-selector">
    <div class="template-selector-header">
      <h3>Sélection de template</h3>
      <q-btn
        flat
        color="primary"
        icon="refresh"
        @click="loadTemplates"
        :loading="loading"
        size="sm"
      />
    </div>

    <div class="filters-container q-mb-md">
      <div class="row q-col-gutter-md">
        <div class="col-12 col-md-4">
          <q-input
            outlined
            dense
            v-model="filters.search"
            label="Rechercher un template"
            clearable
            @update:model-value="filterTemplates"
          >
            <template v-slot:append>
              <q-icon name="search" />
            </template>
          </q-input>
        </div>
        <div class="col-12 col-md-4">
          <q-select
            outlined
            dense
            v-model="filters.category"
            :options="categoryOptions"
            label="Catégorie"
            emit-value
            map-options
            clearable
            @update:model-value="filterTemplates"
          />
        </div>
        <div class="col-12 col-md-4">
          <q-select
            outlined
            dense
            v-model="filters.language"
            :options="languageOptions"
            label="Langue"
            emit-value
            map-options
            clearable
            @update:model-value="filterTemplates"
          />
        </div>
      </div>
    </div>

    <q-card
      v-if="!selectedTemplate"
      class="template-list-container"
      flat
      bordered
    >
      <q-card-section>
        <div v-if="loading" class="text-center q-pa-md">
          <q-spinner color="primary" size="3em" />
          <p>Chargement des templates...</p>
        </div>

        <div v-else-if="filteredTemplates.length === 0" class="text-center q-pa-md">
          <q-icon name="info" color="grey" size="3em" />
          <p>Aucun template trouvé. Veuillez ajuster vos filtres ou vérifier que des templates sont disponibles dans votre compte WhatsApp Business.</p>
        </div>

        <q-list v-else separator>
          <q-item
            v-for="template in filteredTemplates"
            :key="template.id"
            clickable
            v-ripple
            @click="selectTemplate(template)"
          >
            <q-item-section>
              <q-item-label>{{ template.name }}</q-item-label>
              <q-item-label caption>
                {{ template.description }}
              </q-item-label>
              <div class="template-details q-mt-xs">
                <q-badge outline :color="getCategoryColor(template.category)">
                  {{ template.category }}
                </q-badge>
                <q-badge outline color="grey" class="q-ml-sm">
                  {{ template.language }}
                </q-badge>
                <q-badge
                  v-if="template.hasMediaHeader"
                  outline
                  color="purple"
                  class="q-ml-sm"
                >
                  {{ template.headerType }}
                </q-badge>
                <q-badge
                  v-if="template.bodyVariablesCount > 0"
                  outline
                  color="blue"
                  class="q-ml-sm"
                >
                  {{ template.bodyVariablesCount }} variable(s)
                </q-badge>
                <q-badge
                  v-if="template.hasButtons"
                  outline
                  color="orange"
                  class="q-ml-sm"
                >
                  {{ template.buttonsCount }} bouton(s)
                </q-badge>
              </div>
            </q-item-section>
            <q-item-section side>
              <q-btn
                color="primary"
                flat
                round
                icon="arrow_forward"
              />
            </q-item-section>
          </q-item>
        </q-list>
      </q-card-section>
    </q-card>

    <div v-else class="template-config-container">
      <div class="template-header q-mb-md">
        <div>
          <h4 class="q-mt-none q-mb-xs">{{ selectedTemplate.name }}</h4>
          <div class="template-badges">
            <q-badge outline :color="getCategoryColor(selectedTemplate.category)">
              {{ selectedTemplate.category }}
            </q-badge>
            <q-badge outline color="grey" class="q-ml-sm">
              {{ selectedTemplate.language }}
            </q-badge>
          </div>
        </div>
        <q-btn
          outline
          color="primary"
          label="Changer de template"
          icon="arrow_back"
          @click="selectedTemplate = null"
        />
      </div>

      <q-card flat bordered>
        <q-card-section>
          <div class="text-h6">Configuration du template</div>
          
          <!-- Header Media (si applicable) -->
          <div 
            v-if="templateComponents.header && ['IMAGE', 'VIDEO', 'DOCUMENT'].includes(templateComponents.header.format)"
            class="q-mt-md"
          >
            <div class="text-subtitle2">En-tête {{ templateComponents.header.format }}</div>
            
            <!-- Sélection du type de référence (URL ou Media ID) -->
            <q-tabs
              v-model="headerMediaType"
              dense
              class="text-grey"
              active-color="primary"
              indicator-color="primary"
              align="justify"
              narrow-indicator
            >
              <q-tab name="url" label="Par URL" />
              <q-tab name="upload" label="Par upload" />
              <q-tab name="id" label="Par Media ID" />
            </q-tabs>
            
            <q-tab-panels v-model="headerMediaType" animated>
              <!-- Option URL -->
              <q-tab-panel name="url" class="q-pa-none q-mt-sm">
                <q-input
                  outlined
                  v-model="headerMediaUrl"
                  :label="`URL du média (${templateComponents.header.format.toLowerCase()})`"
                  hint="URL HTTPS vers l'image, vidéo ou document"
                  :rules="[val => !val || val.startsWith('https://') || 'L\'URL doit commencer par https://']" 
                >
                  <template v-slot:append>
                    <q-icon v-if="headerMediaUrl" name="cancel" @click="headerMediaUrl = ''" class="cursor-pointer" />
                  </template>
                </q-input>
                
                <!-- Prévisualisation basique si URL présente -->
                <div v-if="headerMediaUrl && headerMediaUrl.startsWith('https://')" class="q-mt-sm media-preview-container">
                  <div class="text-caption">Aperçu:</div>
                  <img 
                    v-if="templateComponents.header.format === 'IMAGE'"
                    :src="headerMediaUrl"
                    class="media-preview"
                    @error="mediaPreviewError = true"
                    v-show="!mediaPreviewError"
                  />
                  <div v-if="templateComponents.header.format === 'IMAGE' && mediaPreviewError" class="media-error">
                    <q-icon name="error" color="negative" size="sm" />
                    <span class="q-ml-xs">Impossible de charger l'aperçu</span>
                  </div>
                  <div v-if="templateComponents.header.format === 'VIDEO'" class="media-placeholder video-placeholder">
                    <q-icon name="video_library" size="24px" />
                    <div>{{ getFilenameFromUrl(headerMediaUrl) }}</div>
                  </div>
                  <div v-if="templateComponents.header.format === 'DOCUMENT'" class="media-placeholder doc-placeholder">
                    <q-icon name="description" size="24px" />
                    <div>{{ getFilenameFromUrl(headerMediaUrl) }}</div>
                  </div>
                </div>
              </q-tab-panel>
              
              <!-- Option Upload -->
              <q-tab-panel name="upload" class="q-pa-none q-mt-sm">
                <div class="upload-container">
                  <!-- Sélection du fichier -->
                  <q-file
                    v-model="mediaFile"
                    :label="`Sélectionner un ${templateComponents.header.format.toLowerCase()}`"
                    outlined
                    :accept="getAcceptedFileTypes(templateComponents.header.format)"
                    :disable="uploadState === 'uploading'"
                    max-file-size="16000000"
                    @update:model-value="handleFileSelected"
                    @rejected="onFileRejected"
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
                  
                  <!-- Informations sur le fichier -->
                  <div v-if="mediaFile" class="text-caption q-mt-xs">
                    {{ formatFileSize(mediaFile.size) }} - {{ mediaFile.type }}
                  </div>
                  
                  <!-- Bouton d'upload et état -->
                  <div class="q-mt-sm">
                    <q-btn 
                      v-if="uploadState === 'idle' && mediaFile"
                      label="Uploader le fichier"
                      color="primary"
                      @click="uploadMedia"
                      icon="cloud_upload"
                      :disable="!mediaFile"
                    />
                    
                    <!-- État de l'upload -->
                    <q-banner 
                      v-if="uploadState !== 'idle'" 
                      :class="{
                        'bg-blue-2': uploadState === 'uploading',
                        'bg-positive': uploadState === 'uploaded',
                        'bg-negative': uploadState === 'error'
                      }"
                      class="q-mt-sm"
                      dense
                      rounded
                    >
                      <template v-slot:avatar>
                        <q-spinner-dots v-if="uploadState === 'uploading'" color="primary" />
                        <q-icon v-else-if="uploadState === 'uploaded'" name="cloud_done" color="positive" />
                        <q-icon v-else name="error" color="negative" />
                      </template>
                      
                      <div class="text-weight-medium text-body2">
                        <template v-if="uploadState === 'uploading'">
                          Upload en cours... {{ uploadProgress }}%
                        </template>
                        <template v-else-if="uploadState === 'uploaded'">
                          Fichier uploadé avec succès
                        </template>
                        <template v-else>
                          Erreur lors de l'upload
                        </template>
                      </div>
                      
                      <div v-if="uploadState === 'uploaded'" class="text-caption">
                        <span class="text-weight-bold">Media ID:</span> {{ uploadedMediaId }}
                      </div>
                      <div v-else-if="uploadState === 'error'" class="text-caption">
                        {{ uploadError }}
                      </div>
                      
                      <template v-slot:action>
                        <q-btn 
                          v-if="uploadState === 'error'"
                          flat 
                          dense
                          color="negative" 
                          label="Réessayer"
                          @click="uploadMedia"
                        />
                        <q-btn 
                          v-if="uploadState !== 'uploading' && uploadState !== 'idle'"
                          flat 
                          dense
                          label="Fermer"
                          @click="uploadState = 'idle'"
                        />
                      </template>
                    </q-banner>
                    
                    <!-- Barre de progression -->
                    <q-linear-progress 
                      v-if="uploadState === 'uploading'"
                      :value="uploadProgress / 100"
                      class="q-mt-sm"
                    />
                  </div>
                  
                  <!-- Prévisualisation du média uploadé -->
                  <div v-if="uploadState === 'uploaded' && mediaPreviewUrl" class="q-mt-md media-preview-container">
                    <div class="text-caption q-mb-xs">Aperçu:</div>
                    <img 
                      v-if="templateComponents.header.format === 'IMAGE'"
                      :src="mediaPreviewUrl"
                      class="media-preview"
                    />
                    <div v-if="templateComponents.header.format === 'VIDEO'" class="media-placeholder video-placeholder">
                      <q-icon name="video_library" size="24px" />
                      <div>{{ mediaFile?.name }}</div>
                    </div>
                    <div v-if="templateComponents.header.format === 'DOCUMENT'" class="media-placeholder doc-placeholder">
                      <q-icon name="description" size="24px" />
                      <div>{{ mediaFile?.name }}</div>
                    </div>
                  </div>
                </div>
              </q-tab-panel>
              
              <!-- Option Media ID -->
              <q-tab-panel name="id" class="q-pa-none q-mt-sm">
                <q-input
                  outlined
                  v-model="headerMediaId"
                  label="ID du média"
                  hint="ID fourni par l'API Meta après un upload de média"
                >
                  <template v-slot:append>
                    <q-icon v-if="headerMediaId" name="cancel" @click="headerMediaId = ''" class="cursor-pointer" />
                  </template>
                </q-input>
              </q-tab-panel>
            </q-tab-panels>
          </div>

          <!-- Variables de corps -->
          <div v-if="bodyVariables.length > 0" class="q-mt-md">
            <div class="text-subtitle2">Variables du corps</div>
            <div 
              v-for="(variable, index) in bodyVariables" 
              :key="`body-var-${index}`"
              class="q-mt-sm"
            >
              <q-input
                outlined
                v-model="bodyVariables[index].value"
                :label="`Variable {{${index + 1}}}`"
                :hint="getVariableHint(index)"
                counter
                :rules="[val => bodyVariableLimits[index] ? val.length <= bodyVariableLimits[index] : true || `Limite: ${bodyVariableLimits[index]} caractères`]"
              >
                <template v-slot:append>
                  <q-badge color="primary" :label="`${bodyVariables[index].value.length}${bodyVariableLimits[index] ? '/' + bodyVariableLimits[index] : ''}`" />
                </template>
                <template v-slot:hint>
                  <div class="row justify-between">
                    <span>{{ getVariableHint(index) }}</span>
                    <span v-if="variableTypes[index]" class="text-primary">{{ variableTypes[index] }}</span>
                  </div>
                </template>
              </q-input>
              <q-select
                v-if="!variableTypes[index]"
                v-model="variableTypes[index]"
                :options="variableTypeOptions"
                label="Type de variable"
                dense
                outlined
                class="q-mt-xs"
                emit-value
                map-options
                clearable
                style="max-width: 50%"
              />
            </div>
          </div>

          <!-- Variables de boutons (si applicable) -->
          <div v-if="buttonVariables.length > 0" class="q-mt-md">
            <div class="text-subtitle2">Variables de boutons</div>
            <div
              v-for="(button, index) in buttonVariables"
              :key="`button-var-${index}`"
              class="q-mt-sm"
            >
              <q-input
                outlined
                v-model="buttonVariables[index].value"
                :label="`Variable pour bouton ${index + 1} (${button.type})`"
                :hint="button.type === 'URL' ? 'Partie variable de l\'URL' : 'Payload pour réponse rapide'"
                counter
                :rules="[val => val.length <= 1000 || 'Maximum 1000 caractères']"
              >
                <template v-slot:append>
                  <q-badge color="primary" :label="`${buttonVariables[index].value.length}/1000`" />
                </template>
              </q-input>
            </div>
          </div>
        </q-card-section>

        <q-card-section>
          <div class="text-h6">Aperçu du message</div>
          <div class="message-preview q-mt-sm q-pa-md">
            <!-- Header preview -->
            <div v-if="templateComponents.header" class="preview-header q-mb-sm">
              <template v-if="templateComponents.header.format === 'TEXT'">
                <div class="preview-header-text">{{ templateComponents.header.text || 'Texte d\'en-tête' }}</div>
              </template>
              <template v-else-if="(headerMediaType === 'url' && headerMediaUrl) || (headerMediaType === 'upload' && uploadState === 'uploaded') || (headerMediaType === 'id' && headerMediaId)">
                <div class="preview-header-media">
                  <div v-if="templateComponents.header.format === 'IMAGE'" class="image-placeholder">
                    <q-icon name="image" size="2rem" color="grey" />
                    <div class="text-caption">
                      <template v-if="headerMediaType === 'url'">{{ getFilenameFromUrl(headerMediaUrl) }}</template>
                      <template v-else-if="headerMediaType === 'upload'">{{ mediaFile?.name || 'Image uploadée' }}</template>
                      <template v-else>Media ID: {{ headerMediaId.substring(0, 8) }}...</template>
                    </div>
                  </div>
                  <div v-else-if="templateComponents.header.format === 'VIDEO'" class="video-placeholder">
                    <q-icon name="videocam" size="2rem" color="grey" />
                    <div class="text-caption">
                      <template v-if="headerMediaType === 'url'">{{ getFilenameFromUrl(headerMediaUrl) }}</template>
                      <template v-else-if="headerMediaType === 'upload'">{{ mediaFile?.name || 'Vidéo uploadée' }}</template>
                      <template v-else>Media ID: {{ headerMediaId.substring(0, 8) }}...</template>
                    </div>
                  </div>
                  <div v-else-if="templateComponents.header.format === 'DOCUMENT'" class="document-placeholder">
                    <q-icon name="description" size="2rem" color="grey" />
                    <div class="text-caption">
                      <template v-if="headerMediaType === 'url'">{{ getFilenameFromUrl(headerMediaUrl) }}</template>
                      <template v-else-if="headerMediaType === 'upload'">{{ mediaFile?.name || 'Document uploadé' }}</template>
                      <template v-else>Media ID: {{ headerMediaId.substring(0, 8) }}...</template>
                    </div>
                  </div>
                </div>
              </template>
              <template v-else>
                <div class="preview-header-placeholder">
                  En-tête {{ templateComponents.header.format }}
                  <div class="text-caption text-italic">Ajoutez une référence au média</div>
                </div>
              </template>
            </div>

            <!-- Body preview -->
            <div v-if="templateComponents.body" class="preview-body q-mb-sm">
              {{ getPreviewBodyText() }}
            </div>

            <!-- Footer preview -->
            <div v-if="templateComponents.footer" class="preview-footer text-caption text-grey q-mb-sm">
              {{ templateComponents.footer.text }}
            </div>

            <!-- Buttons preview -->
            <div v-if="templateComponents.buttons && templateComponents.buttons.buttons?.length > 0" class="preview-buttons">
              <div
                v-for="(button, index) in templateComponents.buttons.buttons"
                :key="`preview-button-${index}`"
                class="preview-button q-my-xs"
              >
                <q-btn
                  :outline="button.type === 'URL'"
                  :color="button.type === 'URL' ? 'primary' : 'grey'"
                  :label="button.text"
                  :icon="button.type === 'URL' ? 'open_in_new' : undefined"
                  :icon-right="button.type === 'URL' ? 'open_in_new' : undefined"
                  no-caps
                  class="full-width"
                  size="sm"
                />
              </div>
            </div>
          </div>
        </q-card-section>

        <q-separator />

        <q-card-actions align="right">
          <q-btn flat label="Annuler" color="negative" v-close-popup @click="cancelSelection" />
          <q-btn unelevated label="Utiliser ce template" color="primary" @click="useTemplate" />
        </q-card-actions>
      </q-card>
    </div>
  </div>
</template>

<script>
import { defineComponent, ref, computed, onMounted, watch } from 'vue';
import { useQuasar } from 'quasar';
import { api } from '@/services/api';
import { whatsAppClient } from '@/services/whatsappRestClient';

export default defineComponent({
  name: 'WhatsAppTemplateSelector',
  props: {
    recipientPhoneNumber: {
      type: String,
      required: true
    }
  },
  emits: ['template-selected', 'cancel'],
  setup(props, { emit }) {
    const $q = useQuasar();
    const loading = ref(false);
    const templates = ref([]);
    const selectedTemplate = ref(null);
    const templateComponents = ref({});
    const headerMediaUrl = ref('');
    const headerMediaId = ref('');
    const headerMediaType = ref('url'); // 'url', 'upload', ou 'id'
    const bodyVariables = ref([]);
    const buttonVariables = ref([]);
    const error = ref(null);
    
    // Variables pour l'upload de média
    const mediaFile = ref(null);
    const mediaPreviewUrl = ref('');
    const mediaPreviewError = ref(false);
    const uploadState = ref('idle'); // 'idle', 'uploading', 'uploaded', 'error'
    const uploadProgress = ref(0);
    const uploadedMediaId = ref('');
    const uploadError = ref('');

    // Filtres
    const filters = ref({
      search: '',
      category: null,
      language: null
    });

    // Options pour les filtres
    const categoryOptions = computed(() => {
      const categories = [...new Set(templates.value.map(t => t.category))];
      return categories.map(category => ({
        label: category,
        value: category
      }));
    });

    const languageOptions = computed(() => {
      const languages = [...new Set(templates.value.map(t => t.language))];
      return languages.map(language => ({
        label: language,
        value: language
      }));
    });

    // Templates filtrés
    const filteredTemplates = computed(() => {
      return templates.value.filter(template => {
        // Filtre par recherche
        if (filters.value.search && !template.name.toLowerCase().includes(filters.value.search.toLowerCase()) &&
            !template.description.toLowerCase().includes(filters.value.search.toLowerCase())) {
          return false;
        }
        
        // Filtre par catégorie
        if (filters.value.category && template.category !== filters.value.category) {
          return false;
        }
        
        // Filtre par langue
        if (filters.value.language && template.language !== filters.value.language) {
          return false;
        }
        
        return true;
      });
    });

    // Couleur selon la catégorie
    const getCategoryColor = (category) => {
      const colors = {
        'MARKETING': 'green',
        'UTILITY': 'blue',
        'AUTHENTICATION': 'orange',
        'ISSUE_RESOLUTION': 'red'
      };
      
      return colors[category] || 'grey';
    };

    // Types de variables possibles pour les suggestions
    const variableTypes = ref([]);
    
    // Limites de caractères pour chaque variable
    const bodyVariableLimits = ref([]);
    
    // Options pour les types de variables
    const variableTypeOptions = [
      { label: 'Texte', value: 'text' },
      { label: 'Numérique', value: 'number' },
      { label: 'Date', value: 'date' },
      { label: 'Heure', value: 'time' },
      { label: 'Prix', value: 'currency' },
      { label: 'Email', value: 'email' },
      { label: 'Téléphone', value: 'phone' },
      { label: 'Référence', value: 'reference' }
    ];
    
    // Obtenir un indice pour la variable en fonction de son type
    const getVariableHint = (index) => {
      // Si un type est défini, utiliser un exemple approprié
      const type = variableTypes.value[index];
      
      if (type) {
        switch (type) {
          case 'text': return 'ex: John Doe';
          case 'number': return 'ex: 42';
          case 'date': return 'ex: 25/12/2023';
          case 'time': return 'ex: 14h30';
          case 'currency': return 'ex: 29.99 €';
          case 'email': return 'ex: contact@example.com';
          case 'phone': return 'ex: +225 XX XX XX XX';
          case 'reference': return 'ex: REF-12345';
          default: return 'ex: Valeur';
        }
      }
      
      // Sinon, générer un exemple en fonction de l'index
      const examples = [
        'ex: John Doe',
        'ex: 25/12/2023',
        'ex: 29.99 €',
        'ex: 14h30',
        'ex: PROD123'
      ];
      
      return examples[index % examples.length];
    };

    // Types de fichiers acceptés selon le format
    const getAcceptedFileTypes = (format) => {
      switch (format) {
        case 'IMAGE': return '.jpg,.jpeg,.png,.webp';
        case 'VIDEO': return '.mp4,.mov';
        case 'DOCUMENT': return '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx';
        default: return '';
      }
    };
    
    // Formater la taille du fichier
    const formatFileSize = (bytes) => {
      if (!bytes) return '0 Bytes';
      const k = 1024;
      const sizes = ['Bytes', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    };
    
    // Gérer la sélection de fichier
    const handleFileSelected = (file) => {
      if (!file) {
        mediaPreviewUrl.value = '';
        return;
      }
      
      // Créer une URL pour la prévisualisation
      if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = (e) => {
          mediaPreviewUrl.value = e.target.result;
        };
        reader.readAsDataURL(file);
      } else {
        mediaPreviewUrl.value = '';
      }
    };
    
    // Gérer les rejets de fichiers
    const onFileRejected = ({ failedPropValidation }) => {
      $q.notify({
        type: 'negative',
        message: `Fichier rejeté: ${failedPropValidation}`,
        position: 'top'
      });
    };
    
    // Uploader le média
    const uploadMedia = async () => {
      if (!mediaFile.value) return;
      
      uploadState.value = 'uploading';
      uploadProgress.value = 0;
      uploadError.value = '';
      
      const formData = new FormData();
      formData.append('file', mediaFile.value);
      
      try {
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
          headerMediaId.value = response.data.mediaId;
          
          // Notification de succès
          $q.notify({
            type: 'positive',
            message: 'Média uploadé avec succès',
            position: 'top'
          });
        } else {
          throw new Error(response.data.error || 'Erreur lors de l\'upload');
        }
      } catch (error) {
        uploadState.value = 'error';
        uploadError.value = error.message || 'Erreur lors de l\'upload';
        
        $q.notify({
          type: 'negative',
          message: 'Erreur lors de l\'upload: ' + uploadError.value,
          position: 'top'
        });
      }
    };

    // Charger les templates depuis l'API REST
    const loadTemplates = async () => {
      loading.value = true;
      error.value = null;
      
      try {
        // Utiliser le client REST pour récupérer les templates
        const response = await whatsAppClient.getApprovedTemplates();
        
        if (response.status !== 'success') {
          throw new Error(response.message || 'Erreur lors du chargement des templates');
        }
        
        // Log des métadonnées pour monitoring
        console.log(`Templates récupérés depuis: ${response.meta.source}`);
        console.log(`Utilisation du fallback: ${response.meta.usedFallback}`);
        console.log(`Timestamp: ${response.meta.timestamp}`);
        
        // Adapter les templates au format attendu par le composant
        templates.value = response.templates.map(template => {
          // Analyser les composants JSON pour déterminer les détails du template
          const componentsJson = template.componentsJson || JSON.stringify(template.components || []);
          let components = template.components || [];
          
          if (!Array.isArray(components)) {
            try {
              components = JSON.parse(componentsJson);
            } catch (e) {
              console.error('Erreur lors du parsing des composants JSON:', e);
              components = [];
            }
          }
          
          // Déterminer si le template a des boutons et combien
          let hasButtons = template.hasButtons || false;
          let buttonsCount = template.buttonsCount || 0;
          
          if (!hasButtons && Array.isArray(components)) {
            const buttonsComponent = components.find(c => c.type === 'BUTTONS');
            if (buttonsComponent && buttonsComponent.buttons) {
              hasButtons = true;
              buttonsCount = Array.isArray(buttonsComponent.buttons) ? buttonsComponent.buttons.length : 0;
            }
          }
          
          // Déterminer si le template a un footer
          let hasFooter = template.hasFooter || false;
          
          if (!hasFooter && Array.isArray(components)) {
            hasFooter = components.some(c => c.type === 'FOOTER' && c.text);
          }
          
          // Déterminer le type d'en-tête (TEXT, IMAGE, VIDEO, DOCUMENT)
          let headerType = 'TEXT';
          let hasMediaHeader = template.hasMediaHeader || false;
          
          if (Array.isArray(components)) {
            const headerComponent = components.find(c => c.type === 'HEADER');
            if (headerComponent && headerComponent.format) {
              headerType = headerComponent.format;
              hasMediaHeader = headerType !== 'TEXT';
            }
          }
          
          return {
            id: template.id,
            name: template.name,
            language: template.language,
            status: template.status,
            category: template.category || 'UTILITY',
            description: template.description || `Template ${template.name}`,
            componentsJson: componentsJson,
            hasMediaHeader: hasMediaHeader,
            headerType: headerType,
            bodyVariablesCount: template.bodyVariablesCount || 0,
            hasButtons: hasButtons,
            buttonsCount: buttonsCount,
            hasFooter: hasFooter
          };
        });
        
        console.log("Templates récupérés avec succès:", templates.value);
        
        // Notification sur la source des données si un fallback a été utilisé
        if (response.meta.usedFallback) {
          $q.notify({
            color: 'warning',
            position: 'top',
            message: `Templates récupérés depuis ${response.meta.source} (fallback)`,
            icon: 'info',
            timeout: 3000
          });
        }
      } catch (err) {
        console.error('Erreur lors du chargement des templates:', err);
        error.value = err.message;
        $q.notify({
          color: 'negative',
          position: 'top',
          message: `Erreur: ${err.message}`,
          icon: 'error'
        });
      } finally {
        loading.value = false;
      }
    };

    // Sélectionner un template
    const selectTemplate = (template) => {
      selectedTemplate.value = template;
      
      // Analyser les composants du template
      try {
        // Essayer de parser les composants du template
        const parsedComponents = JSON.parse(template.componentsJson || '{}');
        
        // Préparer la structure des composants dans le format attendu par le composant
        const formattedComponents = {};
        
        // Si les composants sont un tableau (format brut de Meta)
        if (Array.isArray(parsedComponents)) {
          parsedComponents.forEach(component => {
            if (component.type === 'HEADER') {
              formattedComponents.header = {
                format: component.format || 'TEXT',
                text: component.text || ''
              };
            } else if (component.type === 'BODY') {
              formattedComponents.body = {
                text: component.text || ''
              };
            } else if (component.type === 'FOOTER') {
              formattedComponents.footer = {
                text: component.text || ''
              };
            } else if (component.type === 'BUTTONS') {
              formattedComponents.buttons = {
                buttons: component.buttons || []
              };
            }
          });
        } else {
          // Si c'est déjà au format attendu
          formattedComponents.header = parsedComponents.header;
          formattedComponents.body = parsedComponents.body;
          formattedComponents.footer = parsedComponents.footer;
          formattedComponents.buttons = parsedComponents.buttons;
        }
        
        templateComponents.value = formattedComponents;
      } catch (e) {
        console.error('Erreur lors du parsing des composants du template:', e);
        templateComponents.value = {};
      }
      
      // Réinitialiser les variables
      headerMediaUrl.value = '';
      headerMediaId.value = '';
      headerMediaType.value = 'url';
      mediaFile.value = null;
      mediaPreviewUrl.value = '';
      mediaPreviewError.value = false;
      uploadState.value = 'idle';
      uploadProgress.value = 0;
      uploadedMediaId.value = '';
      uploadError.value = '';
      
      // Préparer les variables du corps
      bodyVariables.value = [];
      variableTypes.value = [];
      bodyVariableLimits.value = [];
      
      if (template.bodyVariablesCount > 0) {
        for (let i = 0; i < template.bodyVariablesCount; i++) {
          bodyVariables.value.push({ 
            index: i + 1,
            value: ''
          });
          
          // Par défaut, pas de type défini et limite de 60 caractères
          // Ces valeurs peuvent être ajustées en fonction des templates
          variableTypes.value[i] = null;
          bodyVariableLimits.value[i] = 60;
          
          // Si nous avons des informations sur les composants, essayer d'analyser
          // le contexte pour suggérer un type et une limite
          if (templateComponents.value.body && templateComponents.value.body.text) {
            const bodyText = templateComponents.value.body.text;
            const placeholder = `{{${i + 1}}}`;
            const position = bodyText.indexOf(placeholder);
            
            if (position !== -1) {
              // Analyser le contexte avant et après le placeholder
              const before = bodyText.substring(Math.max(0, position - 20), position).toLowerCase();
              const after = bodyText.substring(position + placeholder.length, Math.min(bodyText.length, position + placeholder.length + 20)).toLowerCase();
              
              // Suggérer un type en fonction du contexte
              if (before.includes('date') || after.includes('date')) {
                variableTypes.value[i] = 'date';
              } else if (before.includes('heure') || after.includes('heure') || before.includes('horaire')) {
                variableTypes.value[i] = 'time';
              } else if (before.includes('prix') || before.includes('montant') || before.includes('tarif') || 
                        before.includes('€') || after.includes('€') || before.includes('euro') || 
                        before.includes('fcfa') || after.includes('fcfa')) {
                variableTypes.value[i] = 'currency';
              } else if (before.includes('référence') || before.includes('ref') || before.includes('code')) {
                variableTypes.value[i] = 'reference';
              } else if (before.includes('email') || before.includes('e-mail') || before.includes('mail') || 
                        before.includes('@') || after.includes('@')) {
                variableTypes.value[i] = 'email';
              } else if (before.includes('téléphone') || before.includes('tel') || before.includes('portable') || 
                        before.includes('contact')) {
                variableTypes.value[i] = 'phone';
              } else if (before.includes('nombre') || before.includes('numéro') || after.includes('nombre')) {
                variableTypes.value[i] = 'number';
              }
              
              // Ajuster les limites en fonction du type
              switch (variableTypes.value[i]) {
                case 'date': bodyVariableLimits.value[i] = 20; break;
                case 'time': bodyVariableLimits.value[i] = 10; break;
                case 'currency': bodyVariableLimits.value[i] = 15; break;
                case 'email': bodyVariableLimits.value[i] = 100; break;
                case 'phone': bodyVariableLimits.value[i] = 20; break;
                case 'reference': bodyVariableLimits.value[i] = 30; break;
                case 'number': bodyVariableLimits.value[i] = 10; break;
                default: bodyVariableLimits.value[i] = 60;
              }
            }
          }
        }
      }
      
      // Préparer les variables de boutons
      buttonVariables.value = [];
      if (templateComponents.value.buttons && templateComponents.value.buttons.buttons) {
        templateComponents.value.buttons.buttons.forEach((button, index) => {
          if (button.type === 'URL') {
            buttonVariables.value.push({
              index: index,
              type: 'URL',
              value: ''
            });
          } else if (button.type === 'QUICK_REPLY') {
            buttonVariables.value.push({
              index: index,
              type: 'QUICK_REPLY',
              value: ''
            });
          }
        });
      }
      
      console.log('Template sélectionné:', template.name, 'Structure des composants:', templateComponents.value);
    };

    // Filtrer les templates
    const filterTemplates = () => {
      // La logique est déjà dans le computed filteredTemplates
    };

    // Extraire le nom de fichier d'une URL
    const getFilenameFromUrl = (url) => {
      if (!url) return '';
      
      try {
        const urlObj = new URL(url);
        const pathname = urlObj.pathname;
        return pathname.substring(pathname.lastIndexOf('/') + 1);
      } catch (e) {
        return url.substring(url.lastIndexOf('/') + 1);
      }
    };

    // Obtenir le texte du corps avec variables remplacées
    const getPreviewBodyText = () => {
      if (!templateComponents.value.body || !templateComponents.value.body.text) {
        return '';
      }
      
      let text = templateComponents.value.body.text;
      
      // Remplacer les variables {{N}} par les valeurs saisies
      bodyVariables.value.forEach(variable => {
        const placeholder = `{{${variable.index}}}`;
        const value = variable.value || `[Variable ${variable.index}]`;
        text = text.replace(placeholder, value);
      });
      
      return text;
    };

    // Annuler la sélection
    const cancelSelection = () => {
      selectedTemplate.value = null;
      emit('cancel');
    };

    // Utiliser le template sélectionné
    const useTemplate = () => {
      // Vérifier si le template a un header média et si une référence est fournie
      if (
        templateComponents.value.header &&
        ['IMAGE', 'VIDEO', 'DOCUMENT'].includes(templateComponents.value.header.format)
      ) {
        if (headerMediaType.value === 'url' && !headerMediaUrl.value) {
          $q.notify({
            type: 'warning',
            message: `L'URL du média d'en-tête est requise.`,
            position: 'top'
          });
          return;
        }
        
        if (headerMediaType.value === 'id' && !headerMediaId.value) {
          $q.notify({
            type: 'warning',
            message: `L'ID du média d'en-tête est requis.`,
            position: 'top'
          });
          return;
        }
        
        if (headerMediaType.value === 'upload' && uploadState.value !== 'uploaded') {
          $q.notify({
            type: 'warning',
            message: `Veuillez uploader un fichier pour l'en-tête.`,
            position: 'top'
          });
          return;
        }
      }
      
      // Préparer les données du template
      const templateData = {
        template: selectedTemplate.value,
        recipientPhoneNumber: props.recipientPhoneNumber,
        components: templateComponents.value,
        templateComponentsJsonString: selectedTemplate.value.componentsJson,
        bodyVariables: bodyVariables.value.map(v => v.value),
        buttonVariables: buttonVariables.value.reduce((acc, v) => {
          acc[v.index] = v.value;
          return acc;
        }, {}),
        headerMediaType: headerMediaType.value,
        headerMediaUrl: headerMediaType.value === 'url' ? headerMediaUrl.value : null,
        headerMediaId: headerMediaType.value === 'id' ? headerMediaId.value : 
                       headerMediaType.value === 'upload' ? uploadedMediaId.value : null
      };
      
      // Émettre l'événement avec les données
      emit('template-selected', templateData);
    };

    // Charger les templates au montage du composant
    onMounted(() => {
      console.log('[WhatsAppTemplateSelector] Composant monté');
      console.log('[WhatsAppTemplateSelector] Numéro de téléphone du destinataire:', props.recipientPhoneNumber);
      loadTemplates();
    });

    return {
      loading,
      templates,
      filteredTemplates,
      selectedTemplate,
      templateComponents,
      headerMediaUrl,
      headerMediaId,
      headerMediaType,
      bodyVariables,
      buttonVariables,
      variableTypes,
      variableTypeOptions,
      bodyVariableLimits,
      mediaFile,
      mediaPreviewUrl,
      mediaPreviewError,
      uploadState,
      uploadProgress,
      uploadedMediaId,
      uploadError,
      filters,
      categoryOptions,
      languageOptions,
      error,
      
      loadTemplates,
      selectTemplate,
      filterTemplates,
      getCategoryColor,
      getVariableHint,
      getFilenameFromUrl,
      getPreviewBodyText,
      cancelSelection,
      useTemplate,
      formatFileSize,
      getAcceptedFileTypes,
      handleFileSelected,
      onFileRejected,
      uploadMedia
    };
  }
});
</script>

<style scoped>
.whatsapp-template-selector {
  width: 100%;
}

.template-selector-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.template-list-container {
  max-height: 450px;
  overflow-y: auto;
}

.template-details {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.template-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.message-preview {
  border: 1px solid #ddd;
  border-radius: 8px;
  background-color: #f8f8f8;
  padding: 12px;
  max-width: 350px;
  margin: 0 auto;
}

.preview-header-placeholder,
.image-placeholder,
.video-placeholder,
.document-placeholder,
.media-placeholder {
  background-color: #e0e0e0;
  border-radius: 4px;
  padding: 12px;
  text-align: center;
  margin-bottom: 8px;
}

.preview-header-text {
  font-weight: bold;
  margin-bottom: 8px;
}

.preview-body {
  white-space: pre-line;
}

.preview-buttons {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-top: 12px;
}

.media-preview-container {
  max-width: 100%;
  overflow: hidden;
  margin-top: 12px;
}

.media-preview {
  max-width: 100%;
  max-height: 200px;
  border-radius: 4px;
  display: block;
  margin: 8px auto;
}

.media-error {
  color: var(--q-negative);
  padding: 8px;
  font-size: 0.8rem;
  background-color: rgba(var(--q-negative-rgb), 0.1);
  border-radius: 4px;
  display: flex;
  align-items: center;
}

.upload-container {
  padding-bottom: 8px;
}
</style>