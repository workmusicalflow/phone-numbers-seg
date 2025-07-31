<template>
  <div class="whatsapp-template-configurator">
    <q-card flat bordered>
      <!-- En-tête du template avec informations basiques -->
      <q-card-section class="q-pb-none">
        <div class="row items-center justify-between">
          <div>
            <div class="text-h6">{{ template.name }}</div>
            <div class="template-info q-mt-xs">
              <q-badge :color="getCategoryColor(template.category)">
                {{ template.category }}
              </q-badge>
              <q-badge outline color="grey" class="q-ml-sm">
                {{ template.language }}
              </q-badge>
            </div>
          </div>
          <q-btn
            flat
            round
            color="grey"
            icon="close"
            @click="$emit('cancel')"
          />
        </div>
      </q-card-section>
      
      <!-- Onglets de configuration -->
      <q-card-section class="q-pa-none q-mt-md">
        <q-tabs
          v-model="activeTab"
          dense
          class="text-primary"
          active-color="primary"
          indicator-color="primary"
          align="justify"
          narrow-indicator
        >
          <q-tab name="content" icon="edit" label="Contenu" />
          <q-tab name="preview" icon="visibility" label="Aperçu" />
          <q-tab v-if="hasHistoryData" name="history" icon="history" label="Historique" />
        </q-tabs>
        
        <q-separator />
        
        <q-tab-panels v-model="activeTab" animated>
          <!-- Onglet Contenu -->
          <q-tab-panel name="content">
            <!-- Formulaire adaptatif pour le média d'en-tête (si applicable) -->
            <div v-if="hasHeaderMedia" class="header-media-section q-mb-lg">
              <div class="text-subtitle1 text-weight-medium q-mb-sm">
                En-tête {{ getHeaderFormat() }}
                <q-badge outline color="primary" class="q-ml-sm">
                  {{ isHeaderMediaRequired ? 'Obligatoire' : 'Optionnel' }}
                </q-badge>
              </div>
              
              <!-- Sélection du type de référence pour le média -->
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
                    :label="`URL du média (${getHeaderFormat().toLowerCase()})`"
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
                      v-if="getHeaderFormat() === 'IMAGE'"
                      :src="headerMediaUrl"
                      class="media-preview"
                      @error="mediaPreviewError = true"
                      v-show="!mediaPreviewError"
                    />
                    <div v-if="getHeaderFormat() === 'IMAGE' && mediaPreviewError" class="media-error">
                      <q-icon name="error" color="negative" size="sm" />
                      <span class="q-ml-xs">Impossible de charger l'aperçu</span>
                    </div>
                    <div v-if="getHeaderFormat() === 'VIDEO'" class="media-placeholder video-placeholder">
                      <q-icon name="video_library" size="24px" />
                      <div>{{ getFilenameFromUrl(headerMediaUrl) }}</div>
                    </div>
                    <div v-if="getHeaderFormat() === 'DOCUMENT'" class="media-placeholder doc-placeholder">
                      <q-icon name="description" size="24px" />
                      <div>{{ getFilenameFromUrl(headerMediaUrl) }}</div>
                    </div>
                  </div>
                </q-tab-panel>
                
                <!-- Option Upload -->
                <q-tab-panel name="upload" class="q-pa-none q-mt-sm">
                  <div class="upload-container">
                    <!-- Galerie de médias récents -->
                    <whatsapp-media-gallery
                      :selectedMediaId="uploadedMediaId"
                      @media-selected="onRecentMediaSelected"
                    />
                    
                    <!-- Sélection du fichier -->
                    <q-file
                      v-model="mediaFile"
                      :label="`Sélectionner un ${getHeaderFormat().toLowerCase()}`"
                      outlined
                      :accept="getAcceptedFileTypes(getHeaderFormat())"
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
            
            <!-- Variables du corps (dynamiques) -->
            <div v-if="bodyVariables.length > 0" class="body-variables-section q-mb-lg">
              <div class="text-subtitle1 text-weight-medium q-mb-sm">
                Variables du corps ({{ bodyVariables.length }})
                <q-badge
                  v-if="isMissingRequiredBodyVariables"
                  color="negative"
                  class="q-ml-sm"
                >
                  Variables obligatoires manquantes
                </q-badge>
              </div>
              
              <!-- Liste des variables avec formulaires adaptés au type -->
              <div
                v-for="(variable, index) in bodyVariables"
                :key="`body-var-${index}`"
                class="variable-input-container q-mb-md"
              >
                <div class="variable-input-header">
                  <div>
                    <span class="text-weight-medium">Variable {{index + 1}}</span>
                    <q-badge
                      v-if="variableTypes[index]"
                      :color="getTypeColor(variableTypes[index])"
                      class="q-ml-sm"
                    >
                      {{ getTypeLabel(variableTypes[index]) }}
                    </q-badge>
                  </div>
                  
                  <!-- Types disponibles pour cette variable -->
                  <q-btn-dropdown
                    flat
                    dense
                    round
                    color="grey"
                    icon="format_list_bulleted"
                  >
                    <q-list>
                      <q-item
                        v-for="type in variableTypeOptions"
                        :key="type.value"
                        clickable
                        v-close-popup
                        @click="variableTypes[index] = type.value"
                      >
                        <q-item-section avatar>
                          <q-icon :name="getTypeIcon(type.value)" :color="getTypeColor(type.value)" />
                        </q-item-section>
                        <q-item-section>
                          <q-item-label>{{ type.label }}</q-item-label>
                        </q-item-section>
                      </q-item>
                    </q-list>
                  </q-btn-dropdown>
                </div>
                
                <!-- Composant d'input adapté au type de variable -->
                <component
                  :is="getVariableInputComponent(variableTypes[index])"
                  v-model="bodyVariables[index].value"
                  :label="`Variable {{${index + 1}}}`"
                  :hint="getVariableHint(index)"
                  :placeholder="getVariablePlaceholder(index)"
                  :rules="getVariableRules(index)"
                  outlined
                  dense
                  class="q-mt-xs"
                  counter
                  :maxlength="bodyVariableLimits[index]"
                />
                
                <!-- Suggestions basées sur l'historique -->
                <div v-if="getVariableSuggestions(index).length > 0" class="variable-suggestions q-mt-xs">
                  <div class="text-caption text-grey q-mb-xs">Suggestions:</div>
                  <q-chip
                    v-for="(suggestion, sIdx) in getVariableSuggestions(index)"
                    :key="`suggestion-${index}-${sIdx}`"
                    size="sm"
                    clickable
                    @click="bodyVariables[index].value = suggestion"
                  >
                    {{ suggestion }}
                  </q-chip>
                </div>
              </div>
            </div>
            
            <!-- Variables de boutons (si applicable) -->
            <div v-if="buttonVariables.length > 0" class="button-variables-section q-mb-lg">
              <div class="text-subtitle1 text-weight-medium q-mb-sm">
                Variables de boutons ({{ buttonVariables.length }})
              </div>
              
              <div
                v-for="(button, index) in buttonVariables"
                :key="`button-var-${index}`"
                class="variable-input-container q-mb-md"
              >
                <div class="variable-input-header">
                  <div>
                    <span class="text-weight-medium">
                      Bouton #{{index + 1}} ({{ button.type }})
                    </span>
                    <q-badge
                      :color="button.type === 'URL' ? 'purple' : 'blue'"
                      class="q-ml-sm"
                    >
                      {{ button.type === 'URL' ? 'Lien' : 'Réponse rapide' }}
                    </q-badge>
                  </div>
                </div>
                
                <q-input
                  outlined
                  dense
                  v-model="button.value"
                  :label="`Variable pour bouton ${index + 1}`"
                  :hint="button.type === 'URL' ? 'Partie variable de l\'URL' : 'Texte à envoyer comme réponse'"
                  counter
                  :rules="[val => val.length <= 1000 || 'Maximum 1000 caractères']"
                />
              </div>
            </div>
          </q-tab-panel>
          
          <!-- Onglet Aperçu -->
          <q-tab-panel name="preview">
            <div class="text-subtitle1 text-weight-medium q-mb-sm">Aperçu du message</div>
            
            <!-- Sélecteur d'appareil pour l'aperçu -->
            <div class="device-selector q-mb-md">
              <q-btn-toggle
                v-model="previewDevice"
                toggle-color="primary"
                spread
                unelevated
                :options="[
                  { label: 'Android', value: 'android', icon: 'smartphone' },
                  { label: 'iPhone', value: 'iphone', icon: 'phone_iphone' }
                ]"
              />
            </div>
            
            <!-- Aperçu avec style adapté au device -->
            <div 
              class="message-preview-container"
              :class="[`${previewDevice}-preview`, `${template.category.toLowerCase()}-theme`]"
            >
              <div class="message-preview-header">
                <div class="preview-app-bar">
                  <span>{{ previewDevice === 'android' ? 'WhatsApp' : 'Messages' }}</span>
                </div>
                
                <!-- Corps du message avec composants dynamiques -->
                <div class="message-preview q-pa-md">
                  <!-- Affichage de l'en-tête -->
                  <div v-if="hasHeaderComponent" class="preview-header q-mb-sm">
                    <!-- En-tête texte -->
                    <template v-if="headerFormat === 'TEXT'">
                      <div class="preview-header-text">{{ headerText || 'Texte d\'en-tête' }}</div>
                    </template>
                    
                    <!-- En-tête média -->
                    <template v-else-if="hasHeaderMedia && hasHeaderMediaValue">
                      <div class="preview-header-media">
                        <div v-if="headerFormat === 'IMAGE'" class="image-placeholder">
                          <q-icon name="image" size="2rem" color="grey" />
                          <div class="text-caption">
                            <template v-if="headerMediaType === 'url'">{{ getFilenameFromUrl(headerMediaUrl) }}</template>
                            <template v-else-if="headerMediaType === 'upload'">{{ mediaFile?.name || 'Image uploadée' }}</template>
                            <template v-else>Media ID: {{ headerMediaId.substring(0, 8) }}...</template>
                          </div>
                        </div>
                        <div v-else-if="headerFormat === 'VIDEO'" class="video-placeholder">
                          <q-icon name="videocam" size="2rem" color="grey" />
                          <div class="text-caption">
                            <template v-if="headerMediaType === 'url'">{{ getFilenameFromUrl(headerMediaUrl) }}</template>
                            <template v-else-if="headerMediaType === 'upload'">{{ mediaFile?.name || 'Vidéo uploadée' }}</template>
                            <template v-else>Media ID: {{ headerMediaId.substring(0, 8) }}...</template>
                          </div>
                        </div>
                        <div v-else-if="headerFormat === 'DOCUMENT'" class="document-placeholder">
                          <q-icon name="description" size="2rem" color="grey" />
                          <div class="text-caption">
                            <template v-if="headerMediaType === 'url'">{{ getFilenameFromUrl(headerMediaUrl) }}</template>
                            <template v-else-if="headerMediaType === 'upload'">{{ mediaFile?.name || 'Document uploadé' }}</template>
                            <template v-else>Media ID: {{ headerMediaId.substring(0, 8) }}...</template>
                          </div>
                        </div>
                      </div>
                    </template>
                    <template v-else-if="hasHeaderMedia">
                      <div class="preview-header-placeholder">
                        En-tête {{ headerFormat }}
                        <div class="text-caption text-italic">Ajoutez une référence au média</div>
                      </div>
                    </template>
                  </div>
                  
                  <!-- Affichage du corps avec variables remplacées -->
                  <div v-if="bodyText" class="preview-body q-mb-sm">
                    {{ getPreviewBodyText() }}
                  </div>
                  
                  <!-- Affichage du pied de page -->
                  <div v-if="footerText" class="preview-footer text-caption text-grey q-mb-sm">
                    {{ footerText }}
                  </div>
                  
                  <!-- Affichage des boutons -->
                  <div v-if="hasButtons" class="preview-buttons">
                    <div
                      v-for="(button, index) in buttons"
                      :key="`preview-button-${index}`"
                      class="preview-button q-my-xs"
                    >
                      <q-btn
                        :outline="button.type === 'URL'"
                        :color="button.type === 'URL' ? 'primary' : 'grey'"
                        :label="button.text"
                        :icon-right="button.type === 'URL' ? 'open_in_new' : undefined"
                        no-caps
                        class="full-width"
                        size="sm"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </q-tab-panel>
          
          <!-- Onglet Historique -->
          <q-tab-panel v-if="hasHistoryData" name="history">
            <div class="text-subtitle1 text-weight-medium q-mb-sm">Historique d'utilisation</div>
            
            <!-- Liste des derniers envois avec ce template -->
            <q-list bordered separator>
              <q-item v-for="(item, index) in historyData" :key="`history-${index}`">
                <q-item-section>
                  <q-item-label>{{ item.recipientPhone }}</q-item-label>
                  <q-item-label caption>
                    Envoyé le {{ formatDate(item.usedAt) }}
                  </q-item-label>
                </q-item-section>
                
                <q-item-section side>
                  <q-btn
                    flat
                    dense
                    round
                    icon="content_copy"
                    @click="applyHistoricValues(item)"
                    size="sm"
                  >
                    <q-tooltip>Réutiliser ces valeurs</q-tooltip>
                  </q-btn>
                </q-item-section>
              </q-item>
              
              <q-item v-if="historyData.length === 0">
                <q-item-section>
                  <q-item-label class="text-center text-grey">
                    Aucun historique disponible pour ce template
                  </q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-tab-panel>
        </q-tab-panels>
      </q-card-section>
      
      <q-separator />
      
      <q-card-actions align="right">
        <q-btn flat label="Annuler" color="negative" @click="$emit('cancel')" />
        <q-btn unelevated label="Utiliser ce template" color="primary" @click="useTemplate" />
      </q-card-actions>
    </q-card>
  </div>
</template>

<script>
import { defineComponent, ref, computed, onMounted, watch } from 'vue';
import { useQuasar, date } from 'quasar';
import { api } from '@/services/api';
import { mediaService } from '@/services/mediaService';
import WhatsAppMediaGallery from './WhatsAppMediaGallery.vue';
import {
  WhatsAppTemplateVariableDateInput,
  WhatsAppTemplateVariableTimeInput,
  WhatsAppTemplateVariableCurrencyInput,
  WhatsAppTemplateVariableEmailInput,
  WhatsAppTemplateVariablePhoneInput,
  WhatsAppTemplateVariableReferenceInput,
  WhatsAppTemplateVariableNumberInput,
  WhatsAppTemplateVariableTextInput,
  WhatsAppTemplateVariableLimits
} from '../inputs';

export default defineComponent({
  name: 'WhatsAppTemplateConfigurator',
  components: {
    WhatsAppTemplateVariableDateInput,
    WhatsAppTemplateVariableTimeInput,
    WhatsAppTemplateVariableCurrencyInput,
    WhatsAppTemplateVariableEmailInput,
    WhatsAppTemplateVariablePhoneInput,
    WhatsAppTemplateVariableReferenceInput,
    WhatsAppTemplateVariableNumberInput,
    WhatsAppTemplateVariableTextInput,
    WhatsAppMediaGallery
  },
  props: {
    // Template à configurer
    template: {
      type: Object,
      required: true
    },
    // Numéro de téléphone du destinataire
    recipientPhoneNumber: {
      type: String,
      required: true
    },
    // Données d'historique d'utilisation du template (optionnel)
    historyData: {
      type: Array,
      default: () => []
    },
    // Suggestions de paramètres basées sur l'historique d'utilisation
    parameterSuggestions: {
      type: Object,
      default: () => ({})
    }
  },
  emits: ['use-template', 'cancel'],
  setup(props, { emit }) {
    const $q = useQuasar();
    
    // État de l'interface
    const activeTab = ref('content');
    const previewDevice = ref('android');
    
    // État des variables
    const bodyVariables = ref([]);
    const buttonVariables = ref([]);
    const variableTypes = ref([]);
    const bodyVariableLimits = ref([]);
    
    // État du média d'en-tête
    const headerMediaType = ref('url');
    const headerMediaUrl = ref('');
    const headerMediaId = ref('');
    const mediaFile = ref(null);
    const mediaPreviewUrl = ref('');
    const mediaPreviewError = ref(false);
    const uploadState = ref('idle'); // 'idle', 'uploading', 'uploaded', 'error'
    const uploadProgress = ref(0);
    const uploadedMediaId = ref('');
    const uploadError = ref('');
    
    // Cache des composants du template analysés
    const parsedComponents = ref({
      header: null,
      body: null,
      footer: null,
      buttons: null
    });
    
    // Options pour les types de variables
    const variableTypeOptions = [
      { label: 'Texte', value: 'text', icon: 'format_align_left' },
      { label: 'Numérique', value: 'number', icon: 'numbers' },
      { label: 'Date', value: 'date', icon: 'event' },
      { label: 'Heure', value: 'time', icon: 'schedule' },
      { label: 'Prix', value: 'currency', icon: 'paid' },
      { label: 'Email', value: 'email', icon: 'mail' },
      { label: 'Téléphone', value: 'phone', icon: 'phone' },
      { label: 'Référence', value: 'reference', icon: 'tag' }
    ];
    
    // COMPUTED PROPERTIES
    
    // Vérifier s'il manque des variables obligatoires
    const isMissingRequiredBodyVariables = computed(() => {
      return bodyVariables.value.some(v => v.required && !v.value);
    });
    
    // Vérifier si on a des données d'historique
    const hasHistoryData = computed(() => {
      return props.historyData && props.historyData.length > 0;
    });
    
    // Getters pour les composants du template
    const hasHeaderComponent = computed(() => !!parsedComponents.value.header);
    const headerFormat = computed(() => {
      return parsedComponents.value.header?.format || 'TEXT';
    });
    const headerText = computed(() => {
      return parsedComponents.value.header?.text || '';
    });
    const bodyText = computed(() => {
      return parsedComponents.value.body?.text || '';
    });
    const footerText = computed(() => {
      return parsedComponents.value.footer?.text || '';
    });
    const buttons = computed(() => {
      return parsedComponents.value.buttons?.buttons || [];
    });
    
    // Vérifier si le template a un en-tête média
    const hasHeaderMedia = computed(() => {
      return hasHeaderComponent.value && ['IMAGE', 'VIDEO', 'DOCUMENT'].includes(headerFormat.value);
    });
    
    // Vérifier si le template a des boutons
    const hasButtons = computed(() => {
      return buttons.value.length > 0;
    });
    
    // Vérifier si une valeur de média d'en-tête est spécifiée
    const hasHeaderMediaValue = computed(() => {
      return (headerMediaType.value === 'url' && headerMediaUrl.value) ||
             (headerMediaType.value === 'id' && headerMediaId.value) ||
             (headerMediaType.value === 'upload' && uploadState.value === 'uploaded');
    });
    
    // Vérifier si l'en-tête média est requis
    const isHeaderMediaRequired = computed(() => {
      return hasHeaderMedia.value;
    });
    
    // MÉTHODES
    
    // Analyser les composants du template
    const parseTemplateComponents = () => {
      try {
        const componentsJson = props.template.componentsJson || '{}';
        const components = JSON.parse(componentsJson);
        
        // Réinitialiser les composants
        parsedComponents.value = {
          header: null,
          body: null,
          footer: null,
          buttons: null
        };
        
        if (Array.isArray(components)) {
          // Format brut de WhatsApp API
          components.forEach(component => {
            if (component.type === 'HEADER') {
              parsedComponents.value.header = component;
            } else if (component.type === 'BODY') {
              parsedComponents.value.body = component;
            } else if (component.type === 'FOOTER') {
              parsedComponents.value.footer = component;
            } else if (component.type === 'BUTTONS') {
              parsedComponents.value.buttons = component;
            }
          });
        } else {
          // Format organisé
          parsedComponents.value.header = components.header;
          parsedComponents.value.body = components.body;
          parsedComponents.value.footer = components.footer;
          parsedComponents.value.buttons = components.buttons;
        }
        
        // Préparer les variables du corps
        prepareBodyVariables();
        
        // Préparer les variables des boutons
        prepareButtonVariables();
        
        console.log('Composants du template analysés:', parsedComponents.value);
      } catch (e) {
        console.error('Erreur lors de l\'analyse des composants du template:', e);
      }
    };
    
    // Préparer les variables du corps
    const prepareBodyVariables = () => {
      bodyVariables.value = [];
      variableTypes.value = [];
      bodyVariableLimits.value = [];
      
      const bodyComponent = parsedComponents.value.body;
      if (!bodyComponent || !bodyComponent.text) return;
      
      const text = bodyComponent.text;
      const regex = /{{(\d+)}}/g;
      let match;
      
      // Extraire toutes les variables du texte
      const foundVariables = [];
      while ((match = regex.exec(text)) !== null) {
        const index = parseInt(match[1], 10);
        if (!foundVariables.includes(index)) {
          foundVariables.push(index);
        }
      }
      
      // Trier les indices et créer les variables
      foundVariables.sort((a, b) => a - b);
      foundVariables.forEach(variableIndex => {
        const placeholder = `{{${variableIndex}}}`;
        const position = text.indexOf(placeholder);
        
        // Créer l'objet variable
        bodyVariables.value.push({
          index: variableIndex,
          value: '',
          required: true // Par défaut, toutes les variables sont requises
        });
        
        // Déterminer le type et les limites en fonction du contexte
        if (position !== -1) {
          // Analyser le texte avant et après la variable pour détecter le type
          const before = text.substring(Math.max(0, position - 30), position).toLowerCase();
          const after = text.substring(position + placeholder.length, Math.min(text.length, position + placeholder.length + 30)).toLowerCase();
          
          let detectedType = 'text';
          
          // Détection des dates
          if (before.includes('date') || after.includes('date') || 
              before.includes('jour') || after.includes('jour')) {
            detectedType = 'date';
          }
          // Détection des heures
          else if (before.includes('heure') || after.includes('heure') || 
                   before.includes('horaire') || after.includes('horaire')) {
            detectedType = 'time';
          }
          // Détection des montants
          else if (before.includes('prix') || after.includes('prix') || 
                   before.includes('montant') || after.includes('montant') || 
                   before.includes('tarif') || after.includes('tarif') || 
                   before.includes('€') || after.includes('€') || 
                   before.includes('fcfa') || after.includes('fcfa')) {
            detectedType = 'currency';
          }
          // Détection des références
          else if (before.includes('référence') || after.includes('référence') || 
                   before.includes('ref') || after.includes('ref') || 
                   before.includes('code') || after.includes('code')) {
            detectedType = 'reference';
          }
          // Détection des emails
          else if (before.includes('email') || after.includes('email') || 
                   before.includes('e-mail') || after.includes('e-mail') || 
                   before.includes('mail') || after.includes('mail') || 
                   before.includes('@') || after.includes('@')) {
            detectedType = 'email';
          }
          // Détection des téléphones
          else if (before.includes('téléphone') || after.includes('téléphone') || 
                   before.includes('tel') || after.includes('tel') || 
                   before.includes('portable') || after.includes('portable') || 
                   before.includes('contact') || after.includes('contact')) {
            detectedType = 'phone';
          }
          // Détection des nombres
          else if (before.includes('nombre') || after.includes('nombre') || 
                   before.includes('numéro') || after.includes('numéro') || 
                   before.includes('quantité') || after.includes('quantité')) {
            detectedType = 'number';
          }
          
          // Ajouter le type détecté et les limites correspondantes
          variableTypes.value.push(detectedType);
          bodyVariableLimits.value.push(WhatsAppTemplateVariableLimits[detectedType] || 60);
        } else {
          // Type par défaut si le placeholder n'est pas trouvé
          variableTypes.value.push('text');
          bodyVariableLimits.value.push(60);
        }
      });
    };
    
    // Préparer les variables des boutons
    const prepareButtonVariables = () => {
      buttonVariables.value = [];
      
      const buttonsComponent = parsedComponents.value.buttons;
      if (!buttonsComponent || !buttonsComponent.buttons) return;
      
      buttonsComponent.buttons.forEach((button, index) => {
        buttonVariables.value.push({
          index,
          type: button.type,
          text: button.text,
          value: button.type === 'URL' ? button.url || '' : button.payload || ''
        });
      });
    };
    
    // Obtenir le format de l'en-tête
    const getHeaderFormat = () => {
      return headerFormat.value;
    };
    
    // Obtenir un nom de fichier à partir d'une URL
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
    
    // Formater la taille d'un fichier
    const formatFileSize = (bytes) => {
      if (!bytes) return '0 Bytes';
      const k = 1024;
      const sizes = ['Bytes', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    };
    
    // Obtenir les types de fichiers acceptés selon le format
    const getAcceptedFileTypes = (format) => {
      switch (format) {
        case 'IMAGE': return '.jpg,.jpeg,.png,.webp';
        case 'VIDEO': return '.mp4,.mov';
        case 'DOCUMENT': return '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx';
        default: return '';
      }
    };
    
    // Gérer la sélection d'un média récent
    const onRecentMediaSelected = (media) => {
      if (!media || !media.mediaId) return;
      
      // Mettre à jour l'ID du média uploadé
      uploadedMediaId.value = media.mediaId;
      headerMediaId.value = media.mediaId;
      
      // Mettre à jour l'état
      uploadState.value = 'uploaded';
      
      // Notification de succès
      $q.notify({
        type: 'positive',
        message: 'Média sélectionné',
        position: 'top'
      });
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
      
      try {
        // Utiliser le service de média avec gestion de reprise
        const result = await mediaService.uploadFileWithFallback(mediaFile.value, {
          optimizeImage: mediaFile.value.type.startsWith('image/'),
          imageQuality: 80,
          maxWidth: 1024,
          maxHeight: 1024,
          onProgress: (progress) => {
            uploadProgress.value = progress;
          }
        });
        
        if (result.success && result.mediaId) {
          uploadedMediaId.value = result.mediaId;
          uploadState.value = 'uploaded';
          headerMediaId.value = result.mediaId;
          
          // Notification de succès
          $q.notify({
            type: 'positive',
            message: 'Média uploadé avec succès',
            position: 'top'
          });
        } else if (result.resumable) {
          uploadState.value = 'error';
          uploadError.value = result.error || 'Upload interrompu, mais peut être repris';
          
          $q.notify({
            type: 'warning',
            message: 'Upload interrompu',
            caption: 'Vous pouvez reprendre l\'upload en cliquant sur "Réessayer"',
            position: 'top'
          });
        } else {
          throw new Error(result.error || 'Erreur lors de l\'upload');
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
    
    // Obtenir un indice pour la variable en fonction de son type
    const getVariableHint = (index) => {
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
      
      return 'ex: Valeur';
    };
    
    // Obtenir un placeholder pour la variable en fonction de son type
    const getVariablePlaceholder = (index) => {
      const type = variableTypes.value[index];
      
      if (type) {
        switch (type) {
          case 'text': return 'Texte...';
          case 'number': return '0';
          case 'date': return 'JJ/MM/AAAA';
          case 'time': return 'HH:MM';
          case 'currency': return '0,00 €';
          case 'email': return 'email@exemple.com';
          case 'phone': return '+225 XX XX XX XX';
          case 'reference': return 'REF-XXXXX';
          default: return 'Saisir une valeur...';
        }
      }
      
      return 'Saisir une valeur...';
    };
    
    // Obtenir les règles de validation pour une variable
    const getVariableRules = (index) => {
      const type = variableTypes.value[index];
      const rules = [];
      
      // Règle commune: obligatoire
      rules.push(val => !!val || 'Ce champ est obligatoire');
      
      // Règles spécifiques au type
      if (type) {
        switch (type) {
          case 'email':
            rules.push(val => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val) || 'Email invalide');
            break;
          case 'phone':
            rules.push(val => /^\+[0-9\s]{6,}$/.test(val) || 'Format +XXX XX XX XX XX requis');
            break;
          case 'number':
            rules.push(val => /^-?\d+$/.test(val) || 'Nombre entier requis');
            break;
          case 'currency':
            rules.push(val => /^-?\d+([,.]\d{1,2})?(\s*[€$])?$/.test(val) || 'Format monétaire invalide');
            break;
          case 'date':
            rules.push(val => /^\d{1,2}\/\d{1,2}\/\d{4}$/.test(val) || 'Format JJ/MM/AAAA requis');
            break;
          case 'time':
            rules.push(val => /^\d{1,2}[h:]\d{2}$/.test(val) || 'Format HH:MM ou HHhMM requis');
            break;
        }
      }
      
      return rules;
    };
    
    // Obtenir le composant d'input en fonction du type de variable
    const getVariableInputComponent = (type) => {
      switch (type) {
        case 'date': return WhatsAppTemplateVariableDateInput;
        case 'time': return WhatsAppTemplateVariableTimeInput;
        case 'currency': return WhatsAppTemplateVariableCurrencyInput;
        case 'email': return WhatsAppTemplateVariableEmailInput;
        case 'phone': return WhatsAppTemplateVariablePhoneInput;
        case 'reference': return WhatsAppTemplateVariableReferenceInput;
        case 'number': return WhatsAppTemplateVariableNumberInput;
        default: return WhatsAppTemplateVariableTextInput;
      }
    };
    
    // Obtenir la couleur d'un type de variable
    const getTypeColor = (type) => {
      switch (type) {
        case 'text': return 'blue';
        case 'number': return 'purple';
        case 'date': return 'green';
        case 'time': return 'teal';
        case 'currency': return 'deep-orange';
        case 'email': return 'indigo';
        case 'phone': return 'cyan';
        case 'reference': return 'amber';
        default: return 'grey';
      }
    };
    
    // Obtenir l'icône d'un type de variable
    const getTypeIcon = (type) => {
      switch (type) {
        case 'text': return 'format_align_left';
        case 'number': return 'numbers';
        case 'date': return 'event';
        case 'time': return 'schedule';
        case 'currency': return 'paid';
        case 'email': return 'mail';
        case 'phone': return 'phone';
        case 'reference': return 'tag';
        default: return 'text_fields';
      }
    };
    
    // Obtenir le label d'un type de variable
    const getTypeLabel = (type) => {
      switch (type) {
        case 'text': return 'Texte';
        case 'number': return 'Nombre';
        case 'date': return 'Date';
        case 'time': return 'Heure';
        case 'currency': return 'Montant';
        case 'email': return 'Email';
        case 'phone': return 'Téléphone';
        case 'reference': return 'Référence';
        default: return 'Texte';
      }
    };
    
    // Obtenir la couleur selon la catégorie
    const getCategoryColor = (category) => {
      switch (category) {
        case 'MARKETING': return 'green';
        case 'UTILITY': return 'blue';
        case 'AUTHENTICATION': return 'orange';
        case 'ISSUE_RESOLUTION': return 'red';
        default: return 'grey';
      }
    };
    
    // Obtenir le texte du corps avec variables remplacées
    const getPreviewBodyText = () => {
      if (!parsedComponents.value.body || !parsedComponents.value.body.text) {
        return '';
      }
      
      let text = parsedComponents.value.body.text;
      
      // Remplacer les variables {{N}} par les valeurs saisies
      bodyVariables.value.forEach(variable => {
        const placeholder = `{{${variable.index}}}`;
        const value = variable.value || `[Variable ${variable.index}]`;
        text = text.replace(new RegExp(placeholder, 'g'), value);
      });
      
      return text;
    };
    
    // Formater une date pour l'affichage
    const formatDate = (dateString) => {
      return date.formatDate(dateString, 'DD/MM/YYYY HH:mm');
    };
    
    // Obtenir des suggestions pour une variable en fonction de l'historique
    const getVariableSuggestions = (index) => {
      const suggestions = new Set();
      
      // D'abord essayer d'obtenir les suggestions à partir des paramètres préchargés
      if (props.parameterSuggestions && Object.keys(props.parameterSuggestions).length > 0) {
        // Identifier la variable dans le texte ({{1}}, {{2}}, etc.)
        const varIndex = bodyVariables.value[index]?.index;
        if (varIndex) {
          const varPlaceholder = `{{${varIndex}}}`;
          
          // Chercher des suggestions pour cette variable dans les paramètres
          if (props.parameterSuggestions[varPlaceholder] && Array.isArray(props.parameterSuggestions[varPlaceholder])) {
            // Ajouter les suggestions préchargées
            props.parameterSuggestions[varPlaceholder].forEach(value => {
              if (value && typeof value === 'string' && value.trim() !== '') {
                suggestions.add(value);
              }
            });
          }
        }
      }
      
      // Si on a des données d'historique, ajouter des suggestions à partir de l'historique
      if (props.historyData && props.historyData.length > 0) {
        props.historyData.forEach(item => {
          if (item.parameters && Array.isArray(item.parameters)) {
            const value = item.parameters[index];
            if (value && typeof value === 'string' && value.trim() !== '') {
              suggestions.add(value);
            }
          }
        });
      }
      
      // Limiter à 5 suggestions
      return Array.from(suggestions).slice(0, 5);
    };
    
    // Appliquer les valeurs d'un envoi historique
    const applyHistoricValues = (historyItem) => {
      if (historyItem.parameters && Array.isArray(historyItem.parameters)) {
        historyItem.parameters.forEach((value, index) => {
          if (index < bodyVariables.value.length) {
            bodyVariables.value[index].value = value || '';
          }
        });
      }
      
      activeTab.value = 'content';
      $q.notify({
        type: 'positive',
        message: 'Valeurs appliquées depuis l\'historique',
        position: 'top',
        timeout: 2000
      });
    };
    
    // Utiliser le template configuré
    const useTemplate = () => {
      // Vérifier si les variables obligatoires sont remplies
      if (isMissingRequiredBodyVariables.value) {
        $q.notify({
          type: 'negative',
          message: 'Veuillez remplir toutes les variables obligatoires',
          position: 'top'
        });
        return;
      }
      
      // Vérifier si un média d'en-tête est requis mais non fourni
      if (isHeaderMediaRequired.value && !hasHeaderMediaValue.value) {
        $q.notify({
          type: 'negative',
          message: `L'en-tête ${getHeaderFormat()} est obligatoire`,
          position: 'top'
        });
        return;
      }
      
      // Préparer les données du template
      const templateData = {
        template: props.template,
        recipientPhoneNumber: props.recipientPhoneNumber,
        templateComponentsJsonString: props.template.componentsJson,
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
      emit('use-template', templateData);
    };
    
    // Observer les changements de template
    watch(() => props.template, () => {
      // Analyser les composants du nouveau template
      parseTemplateComponents();
    }, { immediate: true });
    
    return {
      // État
      activeTab,
      previewDevice,
      bodyVariables,
      buttonVariables,
      variableTypes,
      bodyVariableLimits,
      headerMediaType,
      headerMediaUrl,
      headerMediaId,
      mediaFile,
      mediaPreviewUrl,
      mediaPreviewError,
      uploadState,
      uploadProgress,
      uploadedMediaId,
      uploadError,
      parsedComponents,
      variableTypeOptions,
      
      // Computed
      isMissingRequiredBodyVariables,
      hasHistoryData,
      hasHeaderComponent,
      headerFormat,
      headerText,
      bodyText,
      footerText,
      buttons,
      hasHeaderMedia,
      hasButtons,
      hasHeaderMediaValue,
      isHeaderMediaRequired,
      
      // Méthodes
      getHeaderFormat,
      getFilenameFromUrl,
      formatFileSize,
      getAcceptedFileTypes,
      handleFileSelected,
      onFileRejected,
      uploadMedia,
      getVariableHint,
      getVariablePlaceholder,
      getVariableRules,
      getVariableInputComponent,
      getTypeColor,
      getTypeIcon,
      getTypeLabel,
      getCategoryColor,
      getPreviewBodyText,
      formatDate,
      getVariableSuggestions,
      applyHistoricValues,
      useTemplate
    };
  }
});
</script>

<style scoped>
.whatsapp-template-configurator {
  max-width: 900px;
  margin: 0 auto;
}

.template-info {
  display: flex;
  align-items: center;
}

/* Styles pour les conteneurs de variables */
.variable-input-container {
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 12px;
  background-color: #f9f9f9;
}

.variable-input-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 4px;
}

.variable-suggestions {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  margin-top: 8px;
}

/* Styles pour l'aperçu sur différents appareils */
.message-preview-container {
  max-width: 350px;
  margin: 0 auto;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
}

.message-preview-header {
  border-top-left-radius: 12px;
  border-top-right-radius: 12px;
  overflow: hidden;
}

.preview-app-bar {
  padding: 12px;
  font-weight: 500;
  text-align: center;
}

.android-preview .preview-app-bar {
  background-color: #128C7E;
  color: white;
}

.iphone-preview .preview-app-bar {
  background-color: #F6F6F6;
  color: #128C7E;
}

.message-preview {
  padding: 12px;
  background-color: #f9f9f9;
}

/* Styles pour l'aperçu des différents composants */
.preview-header-text {
  font-weight: bold;
  margin-bottom: 8px;
}

.preview-body {
  white-space: pre-line;
  margin-bottom: 8px;
}

.preview-footer {
  font-size: 0.8rem;
  color: #666;
  margin-bottom: 8px;
}

.preview-buttons {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-top: 12px;
}

/* Placeholders pour les médias */
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

.media-preview {
  max-width: 100%;
  max-height: 200px;
  border-radius: 4px;
  display: block;
  margin: 8px auto;
}

.media-error {
  color: #f44336;
  padding: 8px;
  font-size: 0.8rem;
  background-color: rgba(244, 67, 54, 0.1);
  border-radius: 4px;
  display: flex;
  align-items: center;
}

/* Styles spécifiques aux catégories de templates */
.marketing-theme .message-preview {
  background-color: #EEFCEF;
}

.utility-theme .message-preview {
  background-color: #F2F8FF;
}

.authentication-theme .message-preview {
  background-color: #FFF8E1;
}
</style>