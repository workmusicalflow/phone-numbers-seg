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
                      (val: string) => val.length > 0 || 'L\'URL est requise',
                      (val: string) => val.startsWith('https://') || 'L\'URL doit commencer par https://'
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
                    :rules="[(val: string) => val.length > 0 || 'L\'ID est requis']"
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
              <div class="text-subtitle1 text-weight-medium q-mb-sm">Variables du message</div>
              
              <div v-for="(variable, index) in bodyVariables" :key="`var-${index}`" class="q-mb-sm">
                <q-input
                  v-model="bodyVariables[index].value"
                  outlined
                  :label="`Variable {{${index + 1}}}`"
                  :hint="getVariableHint(index)"
                  counter
                  :rules="[
                    (val: string) => val.length > 0 || 'Cette variable est requise',
                    (val: string) => val.length <= (variableLimits[index] || 60) || `Maximum ${variableLimits[index] || 60} caractères`
                  ]"
                >
                  <template v-slot:prepend>
                    <q-icon :name="getVariableIcon(variable.type)" color="blue-6" />
                  </template>
                  <template v-slot:append>
                    <q-badge color="primary" :label="`${variable.value.length}/${variableLimits[index] || 60}`" />
                  </template>
                </q-input>
              </div>
            </div>

            <!-- Variables de boutons - Support pour les formats array et object -->
            <div v-if="isButtonVariablesObject || isButtonVariablesArray" class="q-mb-md">
              <div class="text-subtitle1 text-weight-medium q-mb-sm">Variables des boutons</div>
              
              <!-- Cas où buttonVariables est un tableau -->
              <template v-if="isButtonVariablesArray">
                <div v-for="(button, index) in buttonVariables" :key="`btn-array-${index}`" class="q-mb-sm">
                  <q-input
                    v-model="buttonVariables[index].value"
                    outlined
                    :label="`Variable pour ${button.type === 'URL' ? 'lien' : 'réponse rapide'} #${index + 1}`"
                    :hint="button.type === 'URL' ? 'Paramètre d\'URL' : 'Texte pour la réponse rapide'"
                    counter
                    :rules="[
                      (val: string) => button.type === 'URL' ? (val.startsWith('https://') || 'L\'URL doit commencer par https://') : true
                    ]"
                  >
                    <template v-slot:prepend>
                      <q-icon :name="button.type === 'URL' ? 'link' : 'chat'" color="orange-8" />
                    </template>
                  </q-input>
                </div>
              </template>
              
              <!-- Cas où buttonVariables est un objet -->
              <template v-else-if="isButtonVariablesObject">
                <div v-for="(value, key) in buttonVariables" :key="`btn-obj-${key}`" class="q-mb-sm">
                  <q-input
                    :model-value="getButtonValue(key)"
                    @update:model-value="(newVal: string) => setButtonValue(key, newVal)"
                    outlined
                    :label="`URL du bouton #${Number(key) + 1}`"
                    hint="Paramètre d'URL (laissez vide pour utiliser l'URL par défaut)"
                    counter
                  >
                    <template v-slot:prepend>
                      <q-icon name="link" color="orange-8" />
                    </template>
                  </q-input>
                </div>
              </template>
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
                
                <!-- Buttons preview -->
                <div v-if="hasButtons" class="message-preview-buttons">
                  <div v-for="(button, index) in buttons" :key="`preview-btn-${index}`">
                    <q-btn
                      :outline="button.type === 'URL'"
                      :color="button.type === 'URL' ? 'primary' : 'grey-7'"
                      :label="button.text"
                      :icon-right="button.type === 'URL' ? 'open_in_new' : undefined"
                      size="sm"
                      class="full-width q-mb-xs"
                      no-caps
                    />
                  </div>
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
import { useQuasar } from 'quasar';
import { api } from '../../services/api';
import { whatsAppClient } from '../../services/whatsappRestClient';
import { templateParser, templateDataNormalizer } from '../../services/whatsapp';
import {
  WhatsAppTemplateData,
  WhatsAppBodyVariable,
  WhatsAppButtonVariable,
  WhatsAppHeaderMedia,
  HeaderFormat,
  ButtonType,
  VariableType,
  TemplateAnalysisResult,
  ComponentType,
  WhatsAppTemplateSendRequest
} from '../../types/whatsapp-templates';
import { QRejectedEntry } from 'quasar';

export default defineComponent({
  name: 'WhatsAppMessageComposer',
  props: {
    templateData: {
      type: Object as () => WhatsAppTemplateData,
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
    const buttonVariables = ref<WhatsAppButtonVariable[]>([]);
    const variableLimits = ref<number[]>([]);
    const mediaAcceptTypes = ref('');
    const currentTime = ref('');
    const analysisResult = ref<TemplateAnalysisResult | null>(null);
    
    // Options pour la source du média
    const mediaSourceOptions = [
      { label: 'URL', value: 'url' },
      { label: 'Upload', value: 'upload' },
      { label: 'Media ID', value: 'id' }
    ];
    
    // Extraire et analyser les composants du template avec le service templateParser
    const parseTemplate = () => {
      console.log('Analyzing template data:', props.templateData);
      
      try {
        // Utiliser le service templateParser pour analyser le template
        const result = templateParser.analyzeTemplate(props.templateData.template);
        analysisResult.value = result;
        
        console.log('Template analysis result:', result);
        
        // Mettre à jour les variables du corps
        bodyVariables.value = [...result.bodyVariables];
        
        // Mettre à jour les variables des boutons
        buttonVariables.value = [...result.buttonVariables];
        
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
        }
        
        if (result.warnings && result.warnings.length > 0) {
          console.warn('Warnings in template analysis:', result.warnings);
        }
        
      } catch (error: unknown) {
        console.error('Error analyzing template:', error);
      }
      
      // Initialiser l'heure actuelle pour l'aperçu
      updateCurrentTime();
    };
    
    // Mettre à jour l'heure actuelle
    const updateCurrentTime = () => {
      const now = new Date();
      currentTime.value = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    };
    
    // Déterminer le type de variable à partir du contexte
    // Cette fonction est maintenant gérée par le service templateParser
    // mais est conservée à titre de compatibilité et pour les cas spéciaux
    const getVariableTypeFromContext = (index: number, providedBodyText: string | null = null): string => {
      // Si le résultat d'analyse est disponible, l'utiliser en priorité
      if (analysisResult.value && analysisResult.value.bodyVariables[index]) {
        return analysisResult.value.bodyVariables[index].type;
      }
      
      // Logique de secours si le service ne fournit pas le type
      return VariableType.TEXT; // Défaut à 'text'
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
      const failedPropValidation = rejectedEntries.length > 0 ? rejectedEntries[0].failedPropValidation : 'format invalide';
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
        if (mediaFile.value) { // Ensure mediaFile is not null
          formData.append('file', mediaFile.value);
        }
        // Get header component format using the helper method
        const headerComponent = getHeaderComponent();
        if (headerComponent && headerComponent.format) {
          const format = headerComponent.format.toString().toLowerCase();
          formData.append('type', format);
        } else {
          // Default to image if format is missing
          console.warn('Header format is missing for media upload.');
          formData.append('type', 'image');
        }
        
        const response = await api.post('/whatsapp/upload.php', formData, {
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
        } else if (typeof error === 'string') {
          errorMessage = `${errorMessage}: ${error}`;
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
    
    // Utilisation de l'interface WhatsAppTemplateSendRequest importée depuis les types
    
    // Envoyer le message
    const sendMessage = async () => {
      sending.value = true;
      
      try {
        // Préparer les données pour l'API REST
        const requestData: WhatsAppTemplateSendRequest = {
          recipientPhoneNumber: props.recipientPhoneNumber,
          templateName: props.templateData.template.name,
          templateLanguage: props.templateData.template.language,
          bodyVariables: bodyVariables.value.map(v => v.value || ''),
          templateComponentsJsonString: props.templateData.templateComponentsJsonString || undefined,
          buttonVariables: [] // Ajout du champ obligatoire
        };
        
        // Ajouter le média d'en-tête si nécessaire
        if (hasHeaderMedia.value) {
          if (mediaSource.value === 'url' && mediaUrl.value) {
            requestData.headerMediaUrl = mediaUrl.value;
          } else if (mediaSource.value === 'id' && mediaId.value) {
            requestData.headerMediaId = mediaId.value;
          } else if (mediaSource.value === 'upload' && uploadedMediaId.value) {
            requestData.headerMediaId = uploadedMediaId.value;
          }
        }
        
        console.log('Envoi de template avec paramètres (REST):', requestData);
        
        // Appeler la méthode REST directement avec le client
        const response = await whatsAppClient.sendTemplateMessageV2(requestData);
        
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
        } else if (typeof error === 'string') {
          errorMessage = `${errorMessage}: ${error}`;
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
    
    // Obtenir le paramètre média selon la source
    const getMediaParameter = (): { link?: string; id?: string } => {
      if (mediaSource.value === 'url') {
        return { link: mediaUrl.value };
      } else if (mediaSource.value === 'id' || (mediaSource.value === 'upload' && uploadedMediaId.value)) {
        return { id: mediaSource.value === 'id' ? mediaId.value : uploadedMediaId.value };
      }
      return { link: '' };
    };
    
    // Propriétés calculées pour l'aperçu du message
    const getHeaderComponent = () => {
      if (!props.templateData.components || !Array.isArray(props.templateData.components)) {
        return null;
      }
      return props.templateData.components.find(c => c.type === 'HEADER' || c.type === ComponentType.HEADER);
    };
    
    const hasHeader = computed(() => {
      return !!getHeaderComponent();
    });
    
    const headerFormat = computed(() => {
      const header = getHeaderComponent();
      return header ? header.format : null;
    });
    
    const hasHeaderMedia = computed(() => {
      return hasHeader.value && ['IMAGE', 'VIDEO', 'DOCUMENT'].includes(headerFormat.value as string);
    });
    
    const headerText = computed(() => {
      const header = getHeaderComponent();
      return hasHeader.value && headerFormat.value === 'TEXT' && header 
        ? header.text || ''
        : '';
    });
    
    const getFooterComponent = () => {
      if (!props.templateData.components || !Array.isArray(props.templateData.components)) {
        return null;
      }
      return props.templateData.components.find(c => c.type === 'FOOTER' || c.type === ComponentType.FOOTER);
    };
    
    const getButtonsComponent = () => {
      if (!props.templateData.components || !Array.isArray(props.templateData.components)) {
        return null;
      }
      return props.templateData.components.find(c => c.type === 'BUTTONS' || c.type === ComponentType.BUTTONS);
    };
    
    const hasFooter = computed(() => {
      return !!getFooterComponent();
    });
    
    const footerText = computed(() => {
      const footer = getFooterComponent();
      return hasFooter.value && footer && footer.text ? footer.text : '';
    });
    
    const hasButtons = computed(() => {
      const buttonsComponent = getButtonsComponent();
      return !!buttonsComponent && !!buttonsComponent.buttons && buttonsComponent.buttons.length > 0;
    });
    
    const buttons = computed(() => {
      const buttonsComponent = getButtonsComponent();
      return hasButtons.value && buttonsComponent ? buttonsComponent.buttons : [];
    });
    
    // Computed properties pour détecter et gérer différents formats de buttonVariables
    const isButtonVariablesArray = computed(() => {
      return Array.isArray(buttonVariables.value) && buttonVariables.value.length > 0;
    });
    
    const isButtonVariablesObject = computed(() => {
      return !Array.isArray(buttonVariables.value) && 
             typeof buttonVariables.value === 'object' && 
             buttonVariables.value !== null &&
             Object.keys(buttonVariables.value).length > 0;
    });
    
    // Helper pour accéder de manière sécurisée aux variables de bouton en format objet
    const getButtonValue = (key: string | number): string => {
      if (isButtonVariablesObject.value) {
        const btnVars = buttonVariables.value as unknown as Record<string, string>;
        return btnVars[String(key)] || '';
      }
      return '';
    };
    
    // Helper pour définir de manière sécurisée les variables de bouton en format objet
    const setButtonValue = (key: string | number, value: string): void => {
      if (isButtonVariablesObject.value) {
        const btnVars = buttonVariables.value as unknown as Record<string, string>;
        btnVars[String(key)] = value;
      }
    };
    
    const getBodyComponent = () => {
      if (!props.templateData.components || !Array.isArray(props.templateData.components)) {
        return null;
      }
      return props.templateData.components.find(c => c.type === 'BODY' || c.type === ComponentType.BODY);
    };
    
    const previewBodyText = computed(() => {
      const bodyComponent = getBodyComponent();
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
    
    // Vérifier si le formulaire est valide pour l'envoi
    const isFormValid = computed(() => {
      // Vérifier les variables du corps
      const bodyVariablesValid = bodyVariables.value.length === 0 || bodyVariables.value.every(v => v.value && v.value.length > 0);
      
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
      
      // Vérifier les variables de boutons selon leur format
      let buttonVariablesValid = true;
      
      if (isButtonVariablesArray.value) {
        // Format tableau d'objets
        buttonVariablesValid = buttonVariables.value.every(b => {
          if (b.type === 'URL') {
            return b.value.length > 0 && b.value.startsWith('https://');
          }
          return true; // Les boutons de réponse rapide n'ont pas besoin de validation stricte
        });
      } else if (isButtonVariablesObject.value) {
        // Format objet simple - aucune validation spécifique requise pour les URL
        // car les valeurs par défaut des boutons sont déjà valides
        buttonVariablesValid = true;
      }
      
      return bodyVariablesValid && headerMediaValid && buttonVariablesValid;
    });
    
    // Initialiser le composant
    onMounted(() => {
      console.log('WhatsAppMessageComposer mounted with template data:', props.templateData);
      
      // Vérifier si nous avons des variables préparées
      if (props.templateData && props.templateData.bodyVariables) {
        // Convertir le tableau simple en format structuré pour le composant
        if (Array.isArray(props.templateData.bodyVariables) && typeof props.templateData.bodyVariables[0] !== 'object') {
          bodyVariables.value = props.templateData.bodyVariables.map((val, idx): WhatsAppBodyVariable => ({
            index: idx + 1,
            value: typeof val === 'string' ? val || '' : (val as WhatsAppBodyVariable).value || '',
            type: getVariableTypeFromContext(idx)
          }));
          
          // Définir les limites adaptées
          bodyVariables.value.forEach((v, i) => {
            variableLimits.value[i] = getVariableLimitByType(v.type);
          });
          
          console.log('Initialized body variables from props:', bodyVariables.value);
        }
      } else {
        // Si pas de données préparées, analyser le template
        parseTemplate(); // Corrected from parseComponents
      }
      
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
      buttonVariables,
      variableLimits,
      mediaSourceOptions,
      mediaAcceptTypes,
      currentTime,
      
      hasHeader,
      headerFormat,
      hasHeaderMedia,
      headerText,
      hasFooter,
      footerText,
      hasButtons,
      buttons,
      previewBodyText,
      isFormValid,
      isButtonVariablesArray,
      isButtonVariablesObject,
      
      getVariableIcon,
      getVariableHint,
      onFileRejected,
      uploadMedia,
      sendMessage,
      getButtonValue,
      setButtonValue
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

.message-preview-buttons {
  margin-top: 10px;
}

@media (max-width: 767px) {
  .message-preview-container {
    margin-top: 20px;
  }
}
</style>
