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
              <div class="text-subtitle1 text-weight-medium q-mb-sm">Variables du message</div>
              
              <div v-for="(variable, index) in bodyVariables" :key="`var-${index}`" class="q-mb-sm">
                <q-input
                  v-model="bodyVariables[index].value"
                  outlined
                  :label="`Variable {{${index + 1}}}`"
                  :hint="getVariableHint(index)"
                  counter
                  :rules="[
                    val => val.length > 0 || 'Cette variable est requise',
                    val => val.length <= (variableLimits[index] || 60) || `Maximum ${variableLimits[index] || 60} caractères`
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

            <!-- Variables de boutons -->
            <div v-if="buttonVariables.length > 0" class="q-mb-md">
              <div class="text-subtitle1 text-weight-medium q-mb-sm">Variables des boutons</div>
              
              <div v-for="(button, index) in buttonVariables" :key="`btn-${index}`" class="q-mb-sm">
                <q-input
                  v-model="buttonVariables[index].value"
                  outlined
                  :label="`Variable pour ${button.type === 'URL' ? 'lien' : 'réponse rapide'} #${index + 1}`"
                  :hint="button.type === 'URL' ? 'Paramètre d\'URL' : 'Texte pour la réponse rapide'"
                  counter
                  :rules="[
                    val => button.type === 'URL' ? (val.startsWith('https://') || 'L\'URL doit commencer par https://') : true
                  ]"
                >
                  <template v-slot:prepend>
                    <q-icon :name="button.type === 'URL' ? 'link' : 'chat'" color="orange-8" />
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

<script>
import { defineComponent, ref, computed, onMounted } from 'vue';
import { useQuasar } from 'quasar';
import { api } from '@/services/api';
import { whatsAppClient } from '@/services/whatsappRestClient';

export default defineComponent({
  name: 'WhatsAppMessageComposer',
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
    const mediaFile = ref(null);
    const uploading = ref(false);
    const uploadedMediaId = ref('');
    const mediaError = ref(false);
    const bodyVariables = ref([]);
    const buttonVariables = ref([]);
    const variableLimits = ref([]);
    const mediaAcceptTypes = ref('');
    const currentTime = ref('');
    
    // Options pour la source du média
    const mediaSourceOptions = [
      { label: 'URL', value: 'url' },
      { label: 'Upload', value: 'upload' },
      { label: 'Media ID', value: 'id' }
    ];
    
    // Extraire les composants du template
    const parseComponents = () => {
      const templateComponents = props.templateData.components;
      
      // Préparer les variables du corps
      bodyVariables.value = [];
      if (props.templateData.bodyVariables) {
        props.templateData.bodyVariables.forEach((v, i) => {
          bodyVariables.value.push({
            index: i + 1,
            value: v || '',
            type: getVariableTypeFromContext(i)
          });
          
          // Définir des limites adaptées au type
          variableLimits.value[i] = getVariableLimitByType(getVariableTypeFromContext(i));
        });
      }
      
      // Préparer les variables des boutons
      buttonVariables.value = [];
      if (props.templateData.buttonVariables) {
        Object.keys(props.templateData.buttonVariables).forEach(key => {
          const buttonComponent = templateComponents.buttons;
          if (buttonComponent && buttonComponent.buttons && buttonComponent.buttons[key]) {
            buttonVariables.value.push({
              index: parseInt(key),
              type: buttonComponent.buttons[key].type,
              value: props.templateData.buttonVariables[key] || ''
            });
          }
        });
      }
      
      // Définir le type de média accepté selon le format d'en-tête
      if (templateComponents.header && templateComponents.header.format) {
        switch (templateComponents.header.format) {
          case 'IMAGE':
            mediaAcceptTypes.value = '.jpg,.jpeg,.png,.webp';
            break;
          case 'VIDEO':
            mediaAcceptTypes.value = '.mp4,.mov';
            break;
          case 'DOCUMENT':
            mediaAcceptTypes.value = '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx';
            break;
        }
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
    const getVariableTypeFromContext = (index) => {
      if (!props.templateData.components.body || !props.templateData.components.body.text) {
        return 'text';
      }
      
      const bodyText = props.templateData.components.body.text;
      const placeholder = `{{${index + 1}}}`;
      const position = bodyText.indexOf(placeholder);
      
      if (position === -1) return 'text';
      
      // Analyser le contexte
      const before = bodyText.substring(Math.max(0, position - 20), position).toLowerCase();
      const after = bodyText.substring(position + placeholder.length, Math.min(bodyText.length, position + placeholder.length + 20)).toLowerCase();
      
      if (before.includes('date') || after.includes('date')) {
        return 'date';
      } else if (before.includes('heure') || after.includes('heure') || before.includes('horaire')) {
        return 'time';
      } else if (before.includes('prix') || before.includes('montant') || before.includes('tarif') || 
                before.includes('€') || after.includes('€') || before.includes('euro') || 
                before.includes('fcfa') || after.includes('fcfa')) {
        return 'currency';
      } else if (before.includes('référence') || before.includes('ref') || before.includes('code')) {
        return 'reference';
      } else if (before.includes('email') || before.includes('e-mail') || before.includes('mail') || 
                before.includes('@') || after.includes('@')) {
        return 'email';
      } else if (before.includes('téléphone') || before.includes('tel') || before.includes('portable') || 
                before.includes('contact')) {
        return 'phone';
      } else if (before.includes('nombre') || before.includes('numéro') || after.includes('nombre')) {
        return 'number';
      }
      
      return 'text';
    };
    
    // Obtenir la limite de caractères selon le type
    const getVariableLimitByType = (type) => {
      switch (type) {
        case 'date': return 20;
        case 'time': return 10;
        case 'currency': return 15;
        case 'email': return 100;
        case 'phone': return 20;
        case 'reference': return 30;
        case 'number': return 10;
        default: return 60;
      }
    };
    
    // Obtenir l'icône selon le type de variable
    const getVariableIcon = (type) => {
      switch (type) {
        case 'date': return 'event';
        case 'time': return 'schedule';
        case 'currency': return 'payments';
        case 'email': return 'email';
        case 'phone': return 'phone';
        case 'reference': return 'tag';
        case 'number': return 'pin';
        default: return 'text_fields';
      }
    };
    
    // Obtenir un indice pour la variable
    const getVariableHint = (index) => {
      const type = bodyVariables.value[index]?.type;
      
      switch (type) {
        case 'date': return 'ex: 25/12/2023';
        case 'time': return 'ex: 14h30';
        case 'currency': return 'ex: 29.99 €';
        case 'email': return 'ex: contact@example.com';
        case 'phone': return 'ex: +225 XX XX XX XX';
        case 'reference': return 'ex: REF-12345';
        case 'number': return 'ex: 42';
        default: return 'ex: Texte personnalisé';
      }
    };
    
    // Gérer le rejet de fichier
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
      
      uploading.value = true;
      uploadedMediaId.value = '';
      
      try {
        const formData = new FormData();
        formData.append('file', mediaFile.value);
        formData.append('type', props.templateData.components.header.format.toLowerCase());
        
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
      } catch (error) {
        $q.notify({
          type: 'negative',
          message: `Erreur lors de l'upload: ${error.message}`,
          position: 'top'
        });
      } finally {
        uploading.value = false;
      }
    };
    
    // Envoyer le message
    const sendMessage = async () => {
      sending.value = true;
      
      try {
        // Préparer les données pour l'API REST
        const requestData = {
          recipientPhoneNumber: props.recipientPhoneNumber,
          templateName: props.templateData.template.name,
          templateLanguage: props.templateData.template.language,
          bodyVariables: bodyVariables.value.map(v => v.value),
          templateComponentsJsonString: props.templateData.templateComponentsJsonString || null
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
        
        // Ajouter les variables des boutons si nécessaire
        if (buttonVariables.value.length > 0) {
          requestData.buttonVariables = buttonVariables.value.map(btn => btn.value);
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
      } catch (error) {
        $q.notify({
          type: 'negative',
          message: `Erreur lors de l'envoi: ${error.message}`,
          position: 'top'
        });
        emit('message-sent', {
          success: false,
          error: error.message,
          recipientPhoneNumber: props.recipientPhoneNumber
        });
      } finally {
        sending.value = false;
      }
    };
    
    // Obtenir le paramètre média selon la source
    const getMediaParameter = () => {
      if (mediaSource.value === 'url') {
        return { link: mediaUrl.value };
      } else if (mediaSource.value === 'id' || (mediaSource.value === 'upload' && uploadedMediaId.value)) {
        return { id: mediaSource.value === 'id' ? mediaId.value : uploadedMediaId.value };
      }
      return { link: '' };
    };
    
    // Propriétés calculées pour l'aperçu du message
    const hasHeader = computed(() => {
      return props.templateData.components && props.templateData.components.header;
    });
    
    const headerFormat = computed(() => {
      return hasHeader.value ? props.templateData.components.header.format : null;
    });
    
    const hasHeaderMedia = computed(() => {
      return hasHeader.value && ['IMAGE', 'VIDEO', 'DOCUMENT'].includes(headerFormat.value);
    });
    
    const headerText = computed(() => {
      return hasHeader.value && headerFormat.value === 'TEXT' 
        ? props.templateData.components.header.text 
        : '';
    });
    
    const hasFooter = computed(() => {
      return props.templateData.components && props.templateData.components.footer;
    });
    
    const footerText = computed(() => {
      return hasFooter.value ? props.templateData.components.footer.text : '';
    });
    
    const hasButtons = computed(() => {
      return props.templateData.components && 
             props.templateData.components.buttons && 
             props.templateData.components.buttons.buttons && 
             props.templateData.components.buttons.buttons.length > 0;
    });
    
    const buttons = computed(() => {
      return hasButtons.value ? props.templateData.components.buttons.buttons : [];
    });
    
    const previewBodyText = computed(() => {
      if (!props.templateData.components || !props.templateData.components.body) {
        return '';
      }
      
      let text = props.templateData.components.body.text;
      
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
      const bodyVariablesValid = bodyVariables.value.every(v => v.value.length > 0);
      
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
      
      // Vérifier les variables de boutons
      const buttonVariablesValid = buttonVariables.value.every(b => {
        if (b.type === 'URL') {
          return b.value.length > 0 && b.value.startsWith('https://');
        }
        return true; // Les boutons de réponse rapide n'ont pas besoin de validation stricte
      });
      
      return bodyVariablesValid && headerMediaValid && buttonVariablesValid;
    });
    
    // Initialiser le composant
    onMounted(() => {
      parseComponents();
      
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
      
      getVariableIcon,
      getVariableHint,
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

.message-preview-buttons {
  margin-top: 10px;
}

@media (max-width: 767px) {
  .message-preview-container {
    margin-top: 20px;
  }
}
</style>