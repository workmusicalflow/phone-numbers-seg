<template>
  <q-page padding>
    <div class="q-pa-md">
      <h1 class="text-h4">Templates WhatsApp</h1>
      <p class="q-mb-lg">Envoyez des messages WhatsApp en utilisant les templates approuvés de votre compte WhatsApp Business.</p>

      <div class="row q-col-gutter-md">
        <div class="col-12 col-md-6">
          <q-card class="recipient-selector-card" flat bordered>
            <q-card-section>
              <div class="text-h6">Destinataire</div>
              <p class="text-caption q-mt-sm">
                Saisissez le numéro de téléphone du destinataire pour envoyer un message WhatsApp.
              </p>

              <q-input
                outlined
                v-model="phoneNumber"
                label="Numéro de téléphone"
                hint="Format international requis (ex: +225 XX XX XX XX)"
                class="q-mt-sm"
              >
                <template v-slot:prepend>
                  <q-icon name="phone" />
                </template>
              </q-input>

              <div class="text-center q-mt-md">
                <q-btn
                  color="primary"
                  :disable="!phoneNumber"
                  label="Sélectionner un template"
                  @click="showTemplateSelector = true"
                />
                
                <!-- Sélecteur de version API -->
                <div class="q-mt-md">
                  <q-item tag="label" class="q-py-sm">
                    <q-item-section>
                      <q-item-label>Format API WhatsApp</q-item-label>
                      <q-item-label caption>Sélectionner la version de l'API</q-item-label>
                    </q-item-section>
                    <q-item-section side>
                      <q-toggle
                        v-model="useV2Components"
                        color="primary"
                        keep-color
                      />
                    </q-item-section>
                  </q-item>
                  
                  <q-badge :color="useV2Components ? 'green' : 'blue'">
                    Format API {{ apiVersion }}
                  </q-badge>
                  <q-tooltip v-if="useV2Components">
                    Format conforme aux spécifications Meta Cloud API
                  </q-tooltip>
                  <q-tooltip v-else>
                    Format historique compatible
                  </q-tooltip>
                </div>
              </div>
            </q-card-section>
          </q-card>

          <q-card v-if="sentMessages.length > 0" class="q-mt-md" flat bordered>
            <q-card-section>
              <div class="text-h6">Messages récents</div>
              <q-list separator>
                <q-item v-for="(message, index) in sentMessages" :key="index">
                  <q-item-section>
                    <q-item-label>{{ message.templateName }}</q-item-label>
                    <q-item-label caption>
                      {{ message.phoneNumber }} - {{ formatDate(message.timestamp) }}
                    </q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <div class="row items-center">
                      <q-badge :color="message.success ? 'positive' : 'negative'" class="q-mr-xs">
                        {{ message.success ? 'Envoyé' : 'Échec' }}
                      </q-badge>
                      <q-badge v-if="message.version" :color="message.version === 'v2' ? 'green' : 'blue'">
                        {{ message.version }}
                      </q-badge>
                    </div>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-md-6">
          <q-card v-if="showTemplateSelector && !selectedTemplate" class="template-selector-card" flat bordered>
            <q-card-section>
              <EnhancedTemplateSelector
                :title="'Sélection de Template WhatsApp'"
                :show-advanced-filters="true"
                :show-organized-sections="true"
                :group-by-category="true"
                @select="template => selectEnhancedTemplate(template, phoneNumber)"
                @filter-change="handleFilterChange"
              />
            </q-card-section>
          </q-card>
          
          <!-- Étape de personnalisation du template -->
          <q-card v-else-if="showTemplateSelector && selectedTemplate" class="template-customization-card" flat bordered>
            <q-card-section>
              <div class="row justify-between items-center q-mb-md">
                <div class="text-h6">Personnalisation du template</div>
                <q-btn flat color="primary" icon="arrow_back" label="Changer de template" @click="selectedTemplate = null" />
              </div>
              
              <!-- Version v1 (existante) -->
              <WhatsAppMessageComposer
                v-if="!useV2Components"
                :template-data="templateData"
                :recipient-phone-number="phoneNumber"
                @message-sent="onTemplateMessageSent"
                @cancel="showTemplateSelector = false"
                @change-template="selectedTemplate = null"
              />
              
              <!-- Nouvelle version v2 -->
              <WhatsAppMessageComposerV2
                v-else
                :template-data="templateData"
                :recipient-phone-number="phoneNumber"
                @message-sent="onTemplateMessageSent"
                @cancel="showTemplateSelector = false"
                @change-template="selectedTemplate = null"
              />
            </q-card-section>
          </q-card>

          <q-card v-else class="message-info-card" flat bordered>
            <q-card-section>
              <div class="text-h6">À propos des templates WhatsApp</div>
              <p>
                Les templates WhatsApp sont des modèles de messages pré-approuvés que vous pouvez utiliser pour envoyer des messages à vos clients. Ils vous permettent d'envoyer des messages même en dehors de la fenêtre de 24 heures.
              </p>
              <p>
                Voici quelques points importants à connaître :
              </p>
              <ul>
                <li>Les templates doivent être approuvés par Meta avant de pouvoir être utilisés.</li>
                <li>Les messages texte standard ne peuvent être envoyés que dans les 24 heures suivant la dernière interaction d'un utilisateur avec votre numéro WhatsApp.</li>
                <li>Les templates peuvent contenir des variables personnalisées, un média en-tête et des boutons interactifs.</li>
                <li>Ils sont regroupés par catégories : Marketing, Utility (Utilitaire), Authentication (Authentification), etc.</li>
              </ul>
              <p>
                Pour utiliser un template, saisissez d'abord le numéro de téléphone du destinataire, puis cliquez sur "Sélectionner un template".
              </p>
            </q-card-section>
          </q-card>
        </div>
      </div>
    </div>

    <!-- Notification de succès/échec -->
    <q-dialog v-model="notification.show">
      <q-card :class="notification.success ? 'bg-positive' : 'bg-negative'">
        <q-card-section class="row items-center">
          <div class="text-white">
            <q-icon :name="notification.success ? 'check_circle' : 'error'" size="2rem" />
          </div>
          <div class="text-white q-ml-md">
            {{ notification.message }}
          </div>
        </q-card-section>
        <q-card-actions align="right" class="bg-white">
          <q-btn flat label="Fermer" color="primary" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import { defineComponent, ref, onMounted, getCurrentInstance, watch } from 'vue';
import { useQuasar } from 'quasar';
import EnhancedTemplateSelector from '../components/whatsapp/EnhancedTemplateSelector.vue';
import WhatsAppMessageComposer from '../components/whatsapp/WhatsAppMessageComposer.vue';
import WhatsAppMessageComposerV2 from '../components/whatsapp/WhatsAppMessageComposerV2.vue';
import { whatsAppClient } from '../services/whatsappRestClient';
import { whatsAppClientV2 } from '../services/whatsappRestClientV2';
import { templateParserV2, whatsAppTemplateServiceV2 } from '../services/whatsapp/index-v2';

export default defineComponent({
  name: 'WhatsAppTemplatesView',
  components: {
    EnhancedTemplateSelector,
    WhatsAppMessageComposer,
    WhatsAppMessageComposerV2
  },
  setup() {
    console.log('[WhatsAppTemplatesView] Initialisation du composant');
    const $q = useQuasar();
    
    const phoneNumber = ref('');
    const showTemplateSelector = ref(false);
    const selectedTemplate = ref(null);
    const templateData = ref(null);
    const sentMessages = ref([]);
    const useV2Components = ref(true); // Par défaut, utiliser la nouvelle version
    const apiVersion = ref('v2'); // Pour le suivi dans l'historique
    const notification = ref({
      show: false,
      success: false,
      message: ''
    });
    
    // Log pendant le montage du composant
    onMounted(() => {
      console.log('[WhatsAppTemplatesView] Composant monté');
      console.log('[WhatsAppTemplatesView] Composants enregistrés:', Object.keys(getCurrentInstance().appContext.components));
    });

    // Formater une date
    const formatDate = (dateString) => {
      const date = new Date(dateString);
      return date.toLocaleString();
    };

    // Envoyer un template (utilisant le client REST)
    const sendTemplate = async (templateData) => {
      try {
        // Log des données du template pour le débogage
        console.log('[WhatsAppTemplates] Préparation de l\'envoi du template:', {
          name: templateData.template.name,
          language: templateData.template.language,
          bodyVars: templateData.bodyVariables,
          buttonVars: templateData.buttonVariables,
          headerMedia: templateData.headerMediaType
        });
        
        // Préparer les variables pour l'envoi
        const requestData = {
          recipientPhoneNumber: templateData.recipientPhoneNumber,
          templateName: templateData.template.name,
          templateLanguage: templateData.template.language,
          templateComponentsJsonString: templateData.templateComponentsJsonString,
          bodyVariables: Array.isArray(templateData.bodyVariables) 
            ? templateData.bodyVariables 
            : []
        };
        
        // Gérer les variables de boutons selon leur format (objet ou tableau)
        if (Array.isArray(templateData.buttonVariables)) {
          // Format tableau - extraire les valeurs
          requestData.buttonVariables = templateData.buttonVariables.map(btn => btn.value || '');
        } else if (typeof templateData.buttonVariables === 'object' && templateData.buttonVariables !== null) {
          // Format objet - extraire les valeurs
          requestData.buttonVariables = Object.values(templateData.buttonVariables);
        } else {
          requestData.buttonVariables = [];
        }
        
        // Gérer le média d'en-tête selon le type sélectionné
        if (templateData.headerMediaType === 'url' && templateData.headerMediaUrl) {
          requestData.headerMediaUrl = templateData.headerMediaUrl;
          console.log('[WhatsAppTemplates] Ajout du média d\'en-tête par URL:', templateData.headerMediaUrl);
        } else if ((templateData.headerMediaType === 'id' || templateData.headerMediaType === 'upload') && templateData.headerMediaId) {
          requestData.headerMediaId = templateData.headerMediaId;
          console.log('[WhatsAppTemplates] Ajout du média d\'en-tête par ID:', templateData.headerMediaId);
        }
        
        console.log('[WhatsAppTemplates] Envoi du template avec REST client:', requestData);
        
        // Utiliser le client REST pour envoyer le template (v1 ou v2 selon le mode)
        const response = useV2Components.value
          ? await whatsAppClientV2.sendTemplateMessageV2(requestData)
          : await whatsAppClient.sendTemplateMessageV2(requestData);
        
        if (response.success) {
          console.log('[WhatsAppTemplates] Envoi réussi, réponse API:', response);
          
          // Ajouter le message à la liste des messages récents
          sentMessages.value.unshift({
            templateName: templateData.template.name,
            phoneNumber: templateData.recipientPhoneNumber,
            timestamp: response.timestamp || new Date().toISOString(),
            success: true,
            messageId: response.messageId,
            version: apiVersion.value
          });
          
          // Afficher une notification de succès
          notification.value = {
            show: true,
            success: true,
            message: 'Le message WhatsApp a été envoyé avec succès !'
          };
          
          // Fermer le sélecteur de template
          showTemplateSelector.value = false;
        } else {
          throw new Error(response.error || 'Échec de l\'envoi du message');
        }
      } catch (error) {
        console.error('Erreur lors de l\'envoi du template:', error);
        
        // Ajouter le message à la liste des messages récents (avec erreur)
        sentMessages.value.unshift({
          templateName: templateData.template.name,
          phoneNumber: templateData.recipientPhoneNumber,
          timestamp: new Date().toISOString(),
          success: false,
          error: error.message,
          version: apiVersion.value
        });
        
        // Afficher une notification d'erreur
        notification.value = {
          show: true,
          success: false,
          message: `Erreur: ${error.message}`
        };
      }
    };

    // Gérer les changements de filtres
    const handleFilterChange = (filters) => {
      console.log('[WhatsAppTemplates] Filtre changé:', filters);
      // Ici, vous pourriez faire des actions supplémentaires basées sur les filtres
    };

    // Sélectionner un template pour personnalisation 
    const selectEnhancedTemplate = (template, recipientPhoneNumber) => {
      console.log('Template sélectionné:', template.name);
      console.log('Template complet:', template);
      
      // Enregistrer le template sélectionné
      selectedTemplate.value = template;
      
      try {
        // Déterminer quelle version du service utiliser en fonction du mode sélectionné
        if (useV2Components.value) {
          console.log('[V2] Utilisation du service V2 pour préparer le template');
          
          // Utiliser le service V2 pour analyser et préparer le template
          templateData.value = whatsAppTemplateServiceV2.processTemplate(
            template,
            recipientPhoneNumber
          );
          
          console.log('[V2] Template prêt pour personnalisation:', templateData.value);
          return; // Sortir de la fonction après traitement V2
        }
        
        // CODE V1 (mode historique) - Inchangé
        // Obtenir les données du template depuis le composant
        const bodyVariables = [];
        const buttonVariables = {};
        let components = [];
        let headerMediaInfo = {
          type: 'url',
          url: '',
          id: ''
        };
        
        // Extraire les informations des composants
        const componentsJson = template.componentsJson || '{}';
        console.log('[V1] Components JSON string:', componentsJson);
        components = JSON.parse(componentsJson);
        
        console.log('[V1] Components JSON parsed:', components);
        
        // Vérifier si components est un tableau
        if (!Array.isArray(components)) {
          console.log('[V1] Components is not an array, trying to convert from object format');
          // Certains templates peuvent avoir un format d'objet au lieu d'un tableau
          const componentsArray = [];
          for (const key in components) {
            if (Object.prototype.hasOwnProperty.call(components, key)) {
              componentsArray.push({
                type: key.toUpperCase(),
                ...components[key]
              });
            }
          }
          components = componentsArray;
          console.log('[V1] Converted components to array:', components);
        }
        
        // Analyser chaque composant pour comprendre la structure complète du template
        components.forEach((component, idx) => {
          const type = (component.type || '').toString().toUpperCase();
          console.log(`[V1] Component ${idx + 1}: ${type}`, component);
          
          // Analyser les en-têtes
          if (type === 'HEADER') {
            console.log('[V1] Found HEADER component:', component);
            
            // Détecter le format de l'en-tête
            if (component.format) {
              headerMediaInfo.type = component.format.toLowerCase();
              console.log(`[V1] Header format detected: ${headerMediaInfo.type}`);
              
              // Si l'en-tête contient des variables pour les images/vidéos/documents
              if (['IMAGE', 'VIDEO', 'DOCUMENT'].includes(component.format.toUpperCase()) && 
                  component.example && component.example.header_handle) {
                headerMediaInfo.id = component.example.header_handle[0];
                console.log(`[V1] Found header media ID: ${headerMediaInfo.id}`);
              }
            }
          }
          
          // Analyser le corps pour les variables
          if (type === 'BODY' && component.text) {
            console.log('[V1] Analyzing BODY component text for variables:', component.text);
            
            // Extraire toutes les variables du texte du corps avec pattern {{N}}
            const regex = /{{(\d+)}}/g;
            let match;
            
            while ((match = regex.exec(component.text)) !== null) {
              const index = parseInt(match[1], 10) - 1;
              console.log(`[V1] Found variable placeholder: ${match[0]} at index ${index}`);
              
              if (index >= 0) {
                // Assurer que le tableau a la bonne taille
                while (bodyVariables.length <= index) {
                  bodyVariables.push('');
                }
                console.log(`[V1] Added body variable at position ${index + 1}`);
              }
            }
          }
          
          // Analyser les boutons pour les variables
          if (type === 'BUTTONS' && component.buttons && Array.isArray(component.buttons)) {
            console.log('[V1] Analyzing BUTTONS component:', component.buttons.length, 'buttons found');
            
            component.buttons.forEach((button, index) => {
              const buttonType = (button.type || '').toString().toUpperCase();
              console.log(`[V1] Button ${index} type: ${buttonType}`);
              
              if (buttonType === 'URL') {
                // Pour les boutons URL, il faut une variable pour l'URL
                buttonVariables[index] = '';
                console.log(`[V1] Added URL button variable at index ${index}`);
                
                // Extraire l'exemple ou le texte de l'URL si disponible
                if (button.url) {
                  const urlMatch = button.url.match(/{{(\d+)}}/);
                  if (urlMatch) {
                    console.log(`[V1] URL button ${index} has variable placeholder: ${urlMatch[0]}`);
                  }
                }
              } else if (buttonType === 'QUICK_REPLY') {
                // Pour les boutons de réponse rapide
                buttonVariables[index] = '';
                console.log(`[V1] Added QUICK_REPLY button variable at index ${index}`);
              } else if (buttonType === 'PHONE_NUMBER' || buttonType === 'CALL_TO_ACTION') {
                // Pour les autres types de boutons avec des actions
                buttonVariables[index] = '';
                console.log(`[V1] Added ${buttonType} button variable at index ${index}`);
              }
            });
          }
        });
        
        // Log final des variables extraites
        console.log('[V1] Final extraction results:');
        console.log('[V1] - Header media type:', headerMediaInfo.type);
        console.log('[V1] - Body variables:', bodyVariables);
        console.log('[V1] - Button variables:', buttonVariables);
        
        // Créer l'objet templateData pour le WhatsAppMessageComposer
        templateData.value = {
          recipientPhoneNumber,
          template,
          templateComponentsJsonString: template.componentsJson,
          bodyVariables,
          buttonVariables,
          headerMediaType: headerMediaInfo.type || 'url',
          headerMediaUrl: headerMediaInfo.url || '',
          headerMediaId: headerMediaInfo.id || '',
          components: components  // Ajout des composants pour référence directe
        };
        
        console.log('[V1] Template prêt pour personnalisation:', templateData.value);
      } catch (error) {
        console.error('Erreur lors de la préparation du template:', error);
        $q.notify({
          type: 'negative',
          message: `Erreur de préparation du template: ${error.message}`,
          position: 'top',
          timeout: 3000
        });
      }
    };
    
    // Pour compatibilité, conserver sendEnhancedTemplate mais rediriger vers le nouveau processus
    const sendEnhancedTemplate = async (template, recipientPhoneNumber) => {
      // Plutôt que d'envoyer directement, passer par l'étape de personnalisation
      selectEnhancedTemplate(template, recipientPhoneNumber);
    };
    
    // Observer les changements de mode API
    watch(useV2Components, (newValue) => {
      apiVersion.value = newValue ? 'v2' : 'v1';
      console.log(`[WhatsAppTemplates] Mode API changé: ${apiVersion.value}`);
      
      // Si un template est déjà sélectionné, le retraiter avec la bonne version
      if (selectedTemplate.value) {
        selectEnhancedTemplate(selectedTemplate.value, phoneNumber.value);
      }
    });
    
    // Callback quand un message template a été envoyé
    const onTemplateMessageSent = (result) => {
      console.log('[WhatsAppTemplates] Message template envoyé - résultat:', result);
      
      if (result.success) {
        // Ajouter le message à la liste des messages récents avec toutes les informations pertinentes
        sentMessages.value.unshift({
          templateName: selectedTemplate.value.name,
          phoneNumber: phoneNumber.value,
          timestamp: result.timestamp || new Date().toISOString(),
          success: true,
          messageId: result.messageId || '',
          template: selectedTemplate.value.name,
          language: selectedTemplate.value.language,
          version: apiVersion.value
        });
        
        // Afficher une notification de succès avec plus de détails
        notification.value = {
          show: true,
          success: true,
          message: `Le message WhatsApp (template: ${selectedTemplate.value.name}) a été envoyé avec succès !`
        };
        
        // Réinitialiser l'interface
        selectedTemplate.value = null;
        templateData.value = null;
        showTemplateSelector.value = false;
      } else {
        // Ajouter le message échoué à la liste des messages récents
        sentMessages.value.unshift({
          templateName: selectedTemplate.value?.name || 'Template inconnu',
          phoneNumber: phoneNumber.value,
          timestamp: new Date().toISOString(),
          success: false,
          error: result.error || 'Erreur inconnue',
          version: apiVersion.value
        });
        
        // Afficher une notification d'erreur détaillée
        notification.value = {
          show: true,
          success: false,
          message: `Erreur lors de l'envoi du template: ${result.error || 'Raison inconnue'}`
        };
        
        console.error('[WhatsAppTemplates] Échec de l\'envoi du template:', {
          templateName: selectedTemplate.value?.name,
          error: result.error,
          phoneNumber: phoneNumber.value
        });
      }
    };

    return {
      phoneNumber,
      showTemplateSelector,
      selectedTemplate,
      templateData,
      sentMessages,
      notification,
      useV2Components,
      apiVersion,
      formatDate,
      sendTemplate,
      sendEnhancedTemplate,
      selectEnhancedTemplate,
      onTemplateMessageSent,
      handleFilterChange
    };
  }
});
</script>

<style scoped>
.recipient-selector-card,
.template-selector-card,
.message-info-card {
  height: 100%;
}

.template-selector-card {
  overflow-y: auto;
  max-height: 90vh;
}

/* Style pour les sections de templates */
:deep(.section-header) {
  background-color: #f5f5f5;
  padding: 8px;
  border-radius: 4px;
}

/* Style pour les cartes de templates */
:deep(.template-card) {
  transition: transform 0.2s ease-in-out;
}

:deep(.template-card:hover) {
  transform: translateY(-2px);
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
}

/* Style pour les badges par catégorie */
:deep(.q-badge.MARKETING) {
  background-color: #4caf50;
}

:deep(.q-badge.UTILITY) {
  background-color: #2196f3;
}

:deep(.q-badge.AUTHENTICATION) {
  background-color: #ff9800;
}

/* Style pour la page */
.q-page {
  max-width: 1400px;
  margin: 0 auto;
}
</style>