<template>
  <div class="whatsapp-message-composer">
    <q-card flat bordered class="message-composer-card">
      <q-card-section>
        <div class="row items-center q-mb-md">
          <div class="col-grow">
            <h5 class="q-my-none text-primary">Personnalisation du message</h5>
            <p class="q-my-sm text-grey-7 text-caption">
              Template: <strong>{{ templateData.template.name }}</strong> - {{ templateData.template.category }}
            </p>
            <div v-if="analysisResult && analysisResult.warnings && analysisResult.warnings.length > 0" class="text-warning text-caption">
              <q-icon name="warning" size="xs" /> {{ analysisResult.warnings[0] }}
              <span v-if="analysisResult.warnings.length > 1">+{{ analysisResult.warnings.length - 1 }} autres</span>
            </div>
          </div>
          <div class="col-auto">
            <q-btn 
              outline 
              color="primary" 
              icon-right="edit" 
              label="Changer de template" 
              @click="$emit('change-template')" 
              class="q-mr-sm"
              size="sm"
            />
            <q-btn 
              outline 
              color="negative" 
              icon-right="cancel" 
              label="Annuler" 
              @click="$emit('cancel')" 
              size="sm"
            />
          </div>
        </div>

        <q-separator class="q-mb-md" />

        <div class="row q-col-gutter-md">
          <!-- Colonne pour les formulaires -->
          <div class="col-12 col-md-6">
            <!-- En-tête -->
            <div v-if="hasHeaderMedia" class="q-mb-md">
              <div class="text-subtitle1 text-weight-medium q-mb-sm">En-tête média</div>
              
              <q-option-group
                v-model="mediaSource"
                :options="mediaSourceOptions"
                color="primary"
                inline
              />

              <div class="q-mt-sm">
                <!-- URL média -->
                <template v-if="mediaSource === 'url'">
                  <q-input
                    v-model="mediaUrl"
                    outlined
                    label="URL du média"
                    hint="URL HTTPS vers le média"
                    :rules="[
                      val => val.length > 0 || 'L\'URL est requise',
                      val => val.startsWith('https://') || 'L\'URL doit commencer par https://'
                    ]"
                  >
                    <template v-slot:append>
                      <q-icon name="link" color="primary" />
                    </template>
                  </q-input>
                </template>

                <!-- Upload de fichier -->
                <template v-else-if="mediaSource === 'upload'">
                  <div class="q-mb-sm">
                    <q-file
                      v-model="mediaFile"
                      outlined
                      label="Sélectionner un fichier"
                      :accept="mediaAcceptTypes"
                      counter
                      max-file-size="16000000"
                      @rejected="onFileRejected"
                    >
                      <template v-slot:prepend>
                        <q-icon name="attach_file" />
                      </template>
                      <template v-slot:append v-if="mediaFile">
                        <q-icon name="close" @click.stop="mediaFile = null" class="cursor-pointer" />
                      </template>
                    </q-file>
                  </div>
                  
                  <div v-if="mediaFile" class="q-mt-sm">
                    <q-btn
                      color="primary"
                      :loading="uploading"
                      :disable="uploading"
                      icon="cloud_upload"
                      label="Uploader le fichier"
                      @click="uploadMedia"
                    />
                    
                    <div v-if="uploadedMediaId" class="q-mt-sm bg-green-1 q-pa-sm rounded-borders">
                      <div class="text-subtitle2 text-green">Média uploadé avec succès</div>
                      <div class="text-caption">ID: {{ uploadedMediaId }}</div>
                    </div>
                  </div>
                </template>

                <!-- Media ID -->
                <template v-else-if="mediaSource === 'id'">
                  <q-input
                    v-model="mediaId"
                    outlined
                    label="ID du média"
                    hint="ID fourni par WhatsApp/Meta"
                    :rules="[val => val.length > 0 || 'L\'ID est requis']"
                  >
                    <template v-slot:append>
                      <q-icon name="numbers" color="primary" />
                    </template>
                  </q-input>
                </template>
              </div>
            </div>

            <!-- Variables du corps -->
            <div v-if="bodyVariables.length > 0" class="q-mb-md">
              <div class="text-subtitle1 text-weight-medium q-mb-sm">
                Variables du message 
                <q-badge color="primary" :label="`${bodyVariables.length}`" />
              </div>
              
              <div 
                v-for="(variable, index) in bodyVariables" 
                :key="`var-${index}`" 
                class="q-mb-md q-pa-sm rounded-borders"
                :class="{'bg-blue-1': variable.type === 'text', 'bg-amber-1': variable.type === 'currency', 'bg-green-1': variable.type === 'date'}"
              >
                <div class="text-caption q-mb-xs">
                  <q-icon :name="getVariableIcon(variable.type)" color="blue-6" />
                  Variable #{{ index + 1 }}
                  <q-badge :color="getBadgeColor(variable.type)" :label="getVariableTypeName(variable.type)" />
                </div>
                
                <q-input
                  v-model="bodyVariables[index].value"
                  outlined
                  dense
                  :label="`Variable {{${index + 1}}}`"
                  :hint="getVariableHint(index)"
                  counter
                  :rules="[
                    val => val.length > 0 || 'Cette variable est requise',
                    val => val.length <= (variable.maxLength || 60) || `Maximum ${variable.maxLength || 60} caractères`
                  ]"
                >
                  <template v-slot:append>
                    <q-badge color="primary" :label="`${variable.value.length}/${variable.maxLength || 60}`" />
                  </template>
                </q-input>
              </div>
            </div>

            <!-- Contrôles d'envoi -->
            <div class="q-mt-lg">
              <q-btn
                :disable="!isFormValid"
                color="primary"
                size="lg"
                class="full-width"
                :loading="sending"
                icon-right="send"
                label="Envoyer le message"
                @click="sendMessage"
              />
              
              <div class="text-caption text-grey q-mt-xs text-center">
                Destinataire: {{ recipientPhoneNumber }}
              </div>
            </div>
          </div>

          <!-- Colonne pour l'aperçu -->
          <div class="col-12 col-md-6">
            <div class="text-subtitle1 text-weight-medium q-mb-sm">Aperçu du message</div>
            <div class="message-preview-container">
              <div class="message-preview-sender text-caption text-grey-8">WhatsApp Business</div>
              <div class="message-preview">
                <!-- Header preview -->
                <div v-if="hasHeader" class="message-preview-header">
                  <!-- Text header -->
                  <div v-if="headerFormat === 'TEXT'" class="message-preview-header-text">
                    {{ headerText }}
                  </div>
                  
                  <!-- Media header -->
                  <div v-else class="message-preview-header-media">
                    <div v-if="headerFormat === 'IMAGE'" class="message-preview-media-placeholder">
                      <template v-if="mediaSource === 'url' && mediaUrl">
                        <img 
                          :src="mediaUrl" 
                          class="message-preview-image" 
                          @error="mediaError = true"
                          v-if="!mediaError"
                        />
                        <div v-else class="message-preview-media-error">
                          <q-icon name="broken_image" size="2rem" color="grey-6" />
                          <div class="text-caption q-mt-xs">Image non disponible</div>
                        </div>
                      </template>
                      <template v-else-if="mediaSource === 'upload' && uploadedMediaId">
                        <div class="message-preview-media-placeholder">
                          <q-icon name="image" size="2rem" color="grey-6" />
                          <div class="text-caption q-mt-xs">Image uploadée</div>
                        </div>
                      </template>
                      <template v-else-if="mediaSource === 'id' && mediaId">
                        <div class="message-preview-media-placeholder">
                          <q-icon name="image" size="2rem" color="grey-6" />
                          <div class="text-caption q-mt-xs">Image par ID: {{ mediaId }}</div>
                        </div>
                      </template>
                      <template v-else>
                        <div class="message-preview-media-placeholder">
                          <q-icon name="image" size="2rem" color="grey-6" />
                          <div class="text-caption q-mt-xs">Aperçu de l'image</div>
                        </div>
                      </template>
                    </div>
                    
                    <div v-else-if="headerFormat === 'VIDEO'" class="message-preview-media-placeholder">
                      <q-icon name="videocam" size="2rem" color="grey-6" />
                      <div class="text-caption q-mt-xs">Aperçu vidéo</div>
                    </div>
                    
                    <div v-else-if="headerFormat === 'DOCUMENT'" class="message-preview-media-placeholder">
                      <q-icon name="description" size="2rem" color="grey-6" />
                      <div class="text-caption q-mt-xs">Aperçu document</div>
                    </div>
                  </div>
                </div>
                
                <!-- Body preview -->
                <div class="message-preview-body">
                  {{ previewBodyText }}
                </div>
                
                <!-- Footer preview -->
                <div v-if="hasFooter" class="message-preview-footer text-caption text-grey-7">
                  {{ footerText }}
                </div>
                
                <!-- API Preview -->
                <div v-if="showApiPreview" class="message-preview-api q-mt-md">
                  <div class="text-subtitle2 text-weight-medium">Aperçu API</div>
                  <q-btn 
                    outline 
                    dense 
                    color="grey" 
                    label="Afficher/Masquer" 
                    @click="toggleApiPreview" 
                    icon-right="code"
                    size="sm"
                    class="q-mb-sm" 
                  />
                  <q-slide-transition>
                    <div v-show="apiPreviewExpanded">
                      <q-card flat bordered class="bg-grey-1">
                        <q-card-section class="q-pa-sm">
                          <pre class="text-caption">{{ apiPreviewJson }}</pre>
                        </q-card-section>
                      </q-card>
                    </div>
                  </q-slide-transition>
                </div>
              </div>
              <div class="message-preview-time text-caption text-grey-7">
                {{ currentTime }}
              </div>
            </div>
          </div>
        </div>
      </q-card-section>
    </q-card>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, computed, onMounted, watch } from 'vue';
import { useQuasar, QRejectedEntry } from 'quasar';
import { api } from '../../services/api';
import { whatsAppClientV2 } from '../../services/whatsappRestClientV2';
import { templateParserV2, templateDataNormalizerV2, whatsAppTemplateServiceV2 } from '../../services/whatsapp/index-v2';
import {
  WhatsAppTemplateMessage,
  ParameterType,
  ComponentType as ParameterComponentType,
  createTextParameter,
  createImageParameter,
  createVideoParameter,
  createDocumentParameter
} from '../../types/whatsapp-parameters';

import {
  WhatsAppTemplate,
  WhatsAppBodyVariable,
  HeaderFormat,
  VariableType
} from '../../types/whatsapp-templates';

// Interface locale pour le résultat d'analyse d'un template
interface TemplateAnalysisResult {
  bodyVariables: WhatsAppBodyVariable[];
  buttonVariables: any[];
  headerMedia: {
    type: HeaderFormat | string;
    url?: string;
    id?: string;
  };
  hasFooter: boolean;
  footerText?: string;
  errors: string[];
  warnings: string[];
}

export default defineComponent({
  name: 'WhatsAppMessageComposerV2',
  props: {
    templateData: {
      type: Object,
      required: true
    },
    recipientPhoneNumber: {
      type: String,
      required: true
    }
  },
  emits: ['change-template', 'cancel', 'message-sent'],
  setup(props, { emit }) {
    const $q = useQuasar();
    
    // État local
    const sending = ref(false);
    const mediaSource = ref('url'); // 'url', 'upload', 'id'
    const mediaUrl = ref('');
    const mediaId = ref('');
    const mediaFile = ref<File | null>(null);
    const uploading = ref(false);
    const uploadedMediaId = ref('');
    const mediaError = ref(false);
    const bodyVariables = ref<WhatsAppBodyVariable[]>([]);
    const variableLimits = ref<number[]>([]);
    const mediaAcceptTypes = ref('');
    const currentTime = ref('');
    const analysisResult = ref<TemplateAnalysisResult | null>(null);
    
    // État pour l'aperçu API
    const showApiPreview = ref(true);
    const apiPreviewExpanded = ref(false);
    
    // Options pour la source du média
    const mediaSourceOptions = [
      { label: 'URL', value: 'url' },
      { label: 'Upload', value: 'upload' },
      { label: 'Media ID', value: 'id' }
    ];
    
    // Analyser le template avec le nouveau parser
    const analyzeTemplate = () => {
      console.log('Analyzing template data with v2 parser:', props.templateData);
      
      try {
        // Utiliser le service templateParser pour analyser le template
        const result = templateParserV2.analyzeTemplate(props.templateData.template);
        analysisResult.value = result;
        
        console.log('Template analysis result:', result);
        
        // Mettre à jour les variables du corps
        bodyVariables.value = [...result.bodyVariables];
        
        // Initialiser les limites de caractères pour chaque variable
        variableLimits.value = bodyVariables.value.map(v => v.maxLength || getVariableLimitByType(v.type));
        
        // Définir le type de média accepté selon le format d'en-tête
        if (result.headerMedia && result.headerMedia.type) {
          const headerFormat = result.headerMedia.type;
          
          console.log('Header format detected:', headerFormat);
          
          switch (headerFormat) {
            case HeaderFormat.IMAGE:
              mediaAcceptTypes.value = '.jpg,.jpeg,.png,.webp';
              break;
            case HeaderFormat.VIDEO:
              mediaAcceptTypes.value = '.mp4,.mov';
              break;
            case HeaderFormat.DOCUMENT:
              mediaAcceptTypes.value = '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx';
              break;
          }
          
          // Si une URL ou ID de média est déjà présent dans le résultat, l'utiliser
          if (result.headerMedia.url) {
            mediaUrl.value = result.headerMedia.url;
          }
          
          if (result.headerMedia.id) {
            mediaId.value = result.headerMedia.id;
          }
        }
        
        // Vérifier s'il y a des erreurs ou avertissements
        if (result.errors && result.errors.length > 0) {
          console.error('Errors in template analysis:', result.errors);
          $q.notify({
            type: 'negative',
            message: `Erreur d'analyse du template: ${result.errors[0]}`,
            position: 'bottom-right',
            timeout: 3000
          });
        }
        
      } catch (error: unknown) {
        console.error('Error analyzing template:', error);
        let errorMessage = 'Erreur d\'analyse du template';
        
        if (error instanceof Error) {
          errorMessage = `${errorMessage}: ${error.message}`;
        }
        
        $q.notify({
          type: 'negative',
          message: errorMessage,
          position: 'bottom-right',
          timeout: 3000
        });
      }
      
      // Initialiser l'heure actuelle pour l'aperçu
      updateCurrentTime();
    };
    
    // Mettre à jour l'heure actuelle
    const updateCurrentTime = () => {
      const now = new Date();
      currentTime.value = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    };
    
    // Obtenir la limite de caractères selon le type
    const getVariableLimitByType = (type: string): number => {
      switch (type) {
        case VariableType.DATE: return 20;
        case VariableType.TIME: return 10;
        case VariableType.CURRENCY: return 15;
        case VariableType.EMAIL: return 100;
        case VariableType.PHONE: return 20;
        case VariableType.REFERENCE: return 30;
        case VariableType.NUMBER: return 10;
        case VariableType.LINK: return 2000;
        default: return 60;
      }
    };
    
    // Obtenir l'icône selon le type de variable
    const getVariableIcon = (type: string): string => {
      switch (type) {
        case VariableType.DATE: return 'event';
        case VariableType.TIME: return 'schedule';
        case VariableType.CURRENCY: return 'payments';
        case VariableType.EMAIL: return 'email';
        case VariableType.PHONE: return 'phone';
        case VariableType.REFERENCE: return 'tag';
        case VariableType.NUMBER: return 'pin';
        case VariableType.LINK: return 'link';
        default: return 'text_fields';
      }
    };
    
    // Obtenir une couleur de badge selon le type
    const getBadgeColor = (type: string): string => {
      switch (type) {
        case VariableType.DATE: 
        case VariableType.TIME: 
          return 'green';
        case VariableType.CURRENCY: 
          return 'amber-8';
        case VariableType.EMAIL: 
        case VariableType.LINK: 
          return 'blue';
        case VariableType.PHONE: 
          return 'purple';
        case VariableType.REFERENCE: 
        case VariableType.NUMBER: 
          return 'deep-orange';
        default: 
          return 'primary';
      }
    };
    
    // Obtenir le nom lisible du type de variable
    const getVariableTypeName = (type: string): string => {
      switch (type) {
        case VariableType.DATE: return 'Date';
        case VariableType.TIME: return 'Heure';
        case VariableType.CURRENCY: return 'Montant';
        case VariableType.EMAIL: return 'Email';
        case VariableType.PHONE: return 'Téléphone';
        case VariableType.REFERENCE: return 'Référence';
        case VariableType.NUMBER: return 'Nombre';
        case VariableType.LINK: return 'Lien';
        default: return 'Texte';
      }
    };
    
    // Obtenir un indice pour la variable
    const getVariableHint = (index: number): string => {
      const type = bodyVariables.value[index]?.type;
      
      switch (type) {
        case VariableType.DATE: return 'ex: 25/12/2023';
        case VariableType.TIME: return 'ex: 14h30';
        case VariableType.CURRENCY: return 'ex: 29.99 €';
        case VariableType.EMAIL: return 'ex: contact@example.com';
        case VariableType.PHONE: return 'ex: +225 XX XX XX XX';
        case VariableType.REFERENCE: return 'ex: REF-12345';
        case VariableType.NUMBER: return 'ex: 42';
        case VariableType.LINK: return 'ex: https://example.com';
        default: return 'ex: Texte personnalisé';
      }
    };
    
    // Gérer le rejet de fichier
    const onFileRejected = (rejectedEntries: QRejectedEntry[]) => {
      const failedPropValidation = rejectedEntries.length > 0 
        ? rejectedEntries[0].failedPropValidation 
        : 'format invalide';
        
      $q.notify({
        type: 'negative',
        message: `Fichier rejeté: ${failedPropValidation}`,
        position: 'top'
      });
    };
    
    // Uploader le média
    const uploadMedia = async () => {
      if (!mediaFile.value) return;
      
      uploading.value = true;
      uploadedMediaId.value = '';
      
      try {
        const formData = new FormData();
        formData.append('file', mediaFile.value);
        
        // Déterminer le type de média
        let mediaType = 'image';
        if (headerFormat.value === 'VIDEO') {
          mediaType = 'video';
        } else if (headerFormat.value === 'DOCUMENT') {
          mediaType = 'document';
        }
        
        formData.append('type', mediaType);
        
        const response = await api.post('/whatsapp/upload-media.php', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        });
        
        if (response.data.success && response.data.mediaId) {
          uploadedMediaId.value = response.data.mediaId;
          mediaId.value = response.data.mediaId;
          
          $q.notify({
            type: 'positive',
            message: 'Média uploadé avec succès',
            position: 'top'
          });
        } else {
          throw new Error(response.data.error || 'Erreur lors de l\'upload');
        }
      } catch (error: unknown) {
        let errorMessage = 'Erreur lors de l\'upload';
        if (error instanceof Error) {
          errorMessage = `${errorMessage}: ${error.message}`;
        }
        
        $q.notify({
          type: 'negative',
          message: errorMessage,
          position: 'top'
        });
      } finally {
        uploading.value = false;
      }
    };
    
    /**
     * Fonction utilitaire pour convertir les valeurs des variables en tableau compatible
     * @param stringValues Tableau de valeurs à convertir
     * @returns Tableau de chaînes de caractères avec le bon type
     */
    const convertToCompatibleArray = (stringValues: any[]): any => {
      // Cette fonction existe uniquement pour éviter les problèmes de typage
      // Elle ne modifie pas les données, mais change simplement leur "vue"
      return stringValues;
    };
    
    // Préparer les données pour l'API Meta
    const prepareApiData = () => {
      // Extraire les valeurs des variables du corps
      const bodyValues = bodyVariables.value.map(v => v.value || '');
      
      // Préparer les données du média d'en-tête
      let headerMedia: { 
        type: string; 
        value: string; 
        isId: boolean 
      } | undefined;
      
      if (hasHeaderMedia.value) {
        if (mediaSource.value === 'url' && mediaUrl.value) {
          headerMedia = {
            type: headerFormat.value || 'IMAGE',
            value: mediaUrl.value,
            isId: false
          };
        } else if (mediaSource.value === 'id' && mediaId.value) {
          headerMedia = {
            type: headerFormat.value || 'IMAGE',
            value: mediaId.value,
            isId: true
          };
        } else if (mediaSource.value === 'upload' && uploadedMediaId.value) {
          headerMedia = {
            type: headerFormat.value || 'IMAGE',
            value: uploadedMediaId.value,
            isId: true
          };
        }
      }
      
      return {
        bodyValues: convertToCompatibleArray(bodyValues),
        headerMedia
      };
    };
    
    // Générer l'aperçu API
    const generateApiPreview = () => {
      try {
        // Préparer les données pour l'API
        const { bodyValues, headerMedia } = prepareApiData();
        
        // Utiliser directement le tableau compatible
        return whatsAppTemplateServiceV2.prepareApiMessage(
          props.recipientPhoneNumber,
          props.templateData.template,
          bodyValues,
          headerMedia
        );
      } catch (error: unknown) {
        console.error('Error generating API preview:', error);
        let errorMessage = 'Erreur lors de la génération de l\'aperçu API';
        if (error instanceof Error) {
          errorMessage = error.message;
        }
        return {
          error: errorMessage
        };
      }
    };
    
    // Afficher/masquer l'aperçu API
    const toggleApiPreview = () => {
      apiPreviewExpanded.value = !apiPreviewExpanded.value;
    };
    
    // Envoyer le message
    const sendMessage = async () => {
      sending.value = true;
      
      try {
        // Préparer les données pour l'API
        const { bodyValues, headerMedia } = prepareApiData();
        
        console.log('Envoi de template avec paramètres (v2):', {
          recipient: props.recipientPhoneNumber,
          template: props.templateData.template.name,
          bodyValues,
          headerMedia
        });
        
        // Appeler la méthode d'envoi du client WhatsApp v2
        const response = await whatsAppClientV2.sendTemplate(
          props.recipientPhoneNumber,
          props.templateData.template,
          bodyValues,
          headerMedia
        );
        
        if (response.success) {
          $q.notify({
            type: 'positive',
            message: `Message envoyé avec succès à ${props.recipientPhoneNumber}`,
            position: 'top'
          });
          emit('message-sent', {
            success: true,
            messageId: response.messageId,
            recipientPhoneNumber: props.recipientPhoneNumber,
            templateName: props.templateData.template.name,
            timestamp: response.timestamp
          });
        } else {
          throw new Error(response.error || 'Erreur lors de l\'envoi du message');
        }
      } catch (error: unknown) {
        let errorMessage = 'Erreur lors de l\'envoi';
        if (error instanceof Error) {
          errorMessage = `${errorMessage}: ${error.message}`;
        }
        
        $q.notify({
          type: 'negative',
          message: errorMessage,
          position: 'top'
        });
        emit('message-sent', {
          success: false,
          error: errorMessage,
          recipientPhoneNumber: props.recipientPhoneNumber
        });
      } finally {
        sending.value = false;
      }
    };
    
    // Propriétés calculées pour l'aperçu du message
    const hasHeader = computed(() => {
      return props.templateData.components && (
        props.templateData.components.find((c: any) => 
          c.type.toUpperCase() === 'HEADER'
        )
      );
    });
    
    const headerFormat = computed(() => {
      if (!hasHeader.value) return null;
      
      const headerComponent = props.templateData.components.find(
        (c: any) => c.type.toUpperCase() === 'HEADER'
      );
      
      return headerComponent.format || 'TEXT';
    });
    
    const hasHeaderMedia = computed(() => {
      return hasHeader.value && ['IMAGE', 'VIDEO', 'DOCUMENT'].includes(headerFormat.value);
    });
    
    const headerText = computed(() => {
      if (!hasHeader.value || headerFormat.value !== 'TEXT') return '';
      
      const headerComponent = props.templateData.components.find(
        (c: any) => c.type.toUpperCase() === 'HEADER'
      );
      
      return headerComponent.text || '';
    });
    
    const hasFooter = computed(() => {
      return analysisResult.value?.hasFooter || false;
    });
    
    const footerText = computed(() => {
      return analysisResult.value?.footerText || '';
    });
    
    const previewBodyText = computed(() => {
      // Trouver le composant body
      const bodyComponent = props.templateData.components && 
        props.templateData.components.find((c: any) => c.type.toUpperCase() === 'BODY');
      
      if (!bodyComponent || !bodyComponent.text) {
        return '';
      }
      
      let text = bodyComponent.text;
      
      // Remplacer les variables
      bodyVariables.value.forEach(variable => {
        const placeholder = `{{${variable.index}}}`;
        const value = variable.value || `[Variable ${variable.index}]`;
        text = text.replace(new RegExp(placeholder, 'g'), value);
      });
      
      return text;
    });
    
    // Aperçu de la structure API
    const apiPreviewJson = computed(() => {
      const apiData = generateApiPreview();
      return JSON.stringify(apiData, null, 2);
    });
    
    // Vérifier si le formulaire est valide pour l'envoi
    const isFormValid = computed(() => {
      // Vérifier les variables du corps
      const bodyVariablesValid = bodyVariables.value.length === 0 || 
        bodyVariables.value.every(v => v.value && v.value.length > 0);
      
      // Vérifier l'en-tête média si nécessaire
      let headerMediaValid = true;
      if (hasHeaderMedia.value) {
        if (mediaSource.value === 'url') {
          headerMediaValid = mediaUrl.value.length > 0 && mediaUrl.value.startsWith('https://');
        } else if (mediaSource.value === 'id') {
          headerMediaValid = mediaId.value.length > 0;
        } else if (mediaSource.value === 'upload') {
          headerMediaValid = uploadedMediaId.value.length > 0;
        }
      }
      
      return bodyVariablesValid && headerMediaValid;
    });
    
    // Watch pour mettre à jour l'aperçu API quand les données changent
    watch([bodyVariables, mediaUrl, mediaId, uploadedMediaId, mediaSource], () => {
      // L'aperçu API sera recalculé automatiquement
    }, { deep: true });
    
    // Initialiser le composant
    onMounted(() => {
      console.log('WhatsAppMessageComposerV2 mounted with template data:', props.templateData);
      
      // Analyser le template
      analyzeTemplate();
      
      // Mettre à jour l'heure toutes les minutes
      const timer = setInterval(updateCurrentTime, 60000);
      
      // Nettoyer le timer
      return () => clearInterval(timer);
    });
    
    return {
      sending,
      mediaSource,
      mediaUrl,
      mediaId,
      mediaFile,
      uploading,
      uploadedMediaId,
      mediaError,
      bodyVariables,
      variableLimits,
      mediaSourceOptions,
      mediaAcceptTypes,
      currentTime,
      analysisResult,
      
      hasHeader,
      headerFormat,
      hasHeaderMedia,
      headerText,
      hasFooter,
      footerText,
      previewBodyText,
      isFormValid,
      
      showApiPreview,
      apiPreviewExpanded,
      apiPreviewJson,
      toggleApiPreview,
      
      getVariableIcon,
      getVariableHint,
      getBadgeColor,
      getVariableTypeName,
      onFileRejected,
      uploadMedia,
      sendMessage
    };
  }
});
</script>

<style scoped>
.whatsapp-message-composer {
  width: 100%;
}

.message-composer-card {
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.message-preview-container {
  background-color: #f6f6f6;
  border-radius: 8px;
  padding: 16px;
  max-width: 400px;
  margin: 0 auto;
}

.message-preview {
  background-color: white;
  border-radius: 8px;
  padding: 10px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
  margin: 8px 0;
}

.message-preview-sender {
  font-weight: 500;
  padding-left: 8px;
}

.message-preview-time {
  text-align: right;
  padding-right: 8px;
}

.message-preview-header {
  margin-bottom: 8px;
}

.message-preview-header-text {
  font-weight: bold;
  margin-bottom: 4px;
}

.message-preview-body {
  white-space: pre-line;
  margin-bottom: 8px;
  font-size: 0.9rem;
  line-height: 1.4;
}

.message-preview-footer {
  margin-top: 8px;
  font-style: italic;
}

.message-preview-media-placeholder {
  background-color: #f0f0f0;
  border-radius: 4px;
  height: 150px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  margin-bottom: 8px;
}

.message-preview-image {
  max-width: 100%;
  max-height: 150px;
  border-radius: 4px;
  display: block;
}

.message-preview-media-error {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
}

.message-preview-api {
  border-top: 1px dashed #ddd;
  padding-top: 10px;
}

.message-preview-api pre {
  margin: 0;
  white-space: pre-wrap;
  overflow-x: auto;
  font-size: 11px;
  max-height: 200px;
  overflow-y: auto;
}

@media (max-width: 767px) {
  .message-preview-container {
    margin-top: 20px;
  }
}
</style>