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
                    <q-badge :color="message.success ? 'positive' : 'negative'">
                      {{ message.success ? 'Envoyé' : 'Échec' }}
                    </q-badge>
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
              
              <WhatsAppMessageComposer
                :template-data="templateData"
                :recipient-phone-number="phoneNumber"
                @message-sent="onTemplateMessageSent"
                @cancel="showTemplateSelector = false"
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
import { defineComponent, ref, onMounted, getCurrentInstance } from 'vue';
import EnhancedTemplateSelector from '../components/whatsapp/EnhancedTemplateSelector.vue';
import WhatsAppMessageComposer from '../components/whatsapp/WhatsAppMessageComposer.vue';
import { whatsAppClient } from '@/services/whatsappRestClient';

export default defineComponent({
  name: 'WhatsAppTemplatesView',
  components: {
    EnhancedTemplateSelector,
    WhatsAppMessageComposer
  },
  setup() {
    console.log('[WhatsAppTemplatesView] Initialisation du composant');
    
    const phoneNumber = ref('');
    const showTemplateSelector = ref(false);
    const selectedTemplate = ref(null);
    const templateData = ref(null);
    const sentMessages = ref([]);
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
        // Préparer les variables pour l'envoi
        const requestData = {
          recipientPhoneNumber: templateData.recipientPhoneNumber,
          templateName: templateData.template.name,
          templateLanguage: templateData.template.language,
          templateComponentsJsonString: templateData.templateComponentsJsonString,
          bodyVariables: templateData.bodyVariables,
          buttonVariables: Object.values(templateData.buttonVariables)
        };
        
        // Gérer le média d'en-tête selon le type sélectionné
        if (templateData.headerMediaType === 'url' && templateData.headerMediaUrl) {
          requestData.headerMediaUrl = templateData.headerMediaUrl;
        } else if ((templateData.headerMediaType === 'id' || templateData.headerMediaType === 'upload') && templateData.headerMediaId) {
          requestData.headerMediaId = templateData.headerMediaId;
        }
        
        console.log('[WhatsAppTemplates] Envoi du template avec REST client:', requestData);
        
        // Utiliser le client REST pour envoyer le template
        const response = await whatsAppClient.sendTemplateMessageV2(requestData);
        
        if (response.success) {
          // Ajouter le message à la liste des messages récents
          sentMessages.value.unshift({
            templateName: templateData.template.name,
            phoneNumber: templateData.recipientPhoneNumber,
            timestamp: response.timestamp || new Date().toISOString(),
            success: true,
            messageId: response.messageId
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
          error: error.message
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
      
      // Enregistrer le template sélectionné
      selectedTemplate.value = template;
      
      // Obtenir les données du template depuis le composant
      const bodyVariables = [];
      const buttonVariables = {};
      
      // Extraire les informations des composants
      try {
        const componentsJson = template.componentsJson || '{}';
        const components = JSON.parse(componentsJson);
        
        // Récupérer les informations du corps
        const bodyComponent = Array.isArray(components) 
          ? components.find(c => c.type === 'BODY')
          : components.body;
        
        if (bodyComponent && bodyComponent.text) {
          // Compter les variables dans le texte du corps
          const regex = /{{(\d+)}}/g;
          let match;
          while ((match = regex.exec(bodyComponent.text)) !== null) {
            const index = parseInt(match[1], 10) - 1;
            if (index >= 0 && index >= bodyVariables.length) {
              // Remplir avec des valeurs vides jusqu'à l'index
              while (bodyVariables.length <= index) {
                bodyVariables.push('');
              }
            }
          }
        }
        
        // Récupérer les informations des boutons
        const buttonsComponent = Array.isArray(components)
          ? components.find(c => c.type === 'BUTTONS')
          : components.buttons;
        
        if (buttonsComponent && buttonsComponent.buttons) {
          buttonsComponent.buttons.forEach((button, index) => {
            if (button.type === 'URL') {
              buttonVariables[index] = '';
            } else if (button.type === 'QUICK_REPLY') {
              buttonVariables[index] = '';
            }
          });
        }
      } catch (e) {
        console.error('Erreur lors de l\'analyse des composants:', e);
      }
      
      // Créer l'objet templateData pour le WhatsAppMessageComposer
      templateData.value = {
        recipientPhoneNumber,
        template,
        templateComponentsJsonString: template.componentsJson,
        bodyVariables,
        buttonVariables,
        headerMediaType: 'url',
        headerMediaUrl: '',
        headerMediaId: ''
      };
      
      console.log('Template prêt pour personnalisation:', templateData.value);
    };
    
    // Pour compatibilité, conserver sendEnhancedTemplate mais rediriger vers le nouveau processus
    const sendEnhancedTemplate = async (template, recipientPhoneNumber) => {
      // Plutôt que d'envoyer directement, passer par l'étape de personnalisation
      selectEnhancedTemplate(template, recipientPhoneNumber);
    };
    
    // Callback quand un message template a été envoyé
    const onTemplateMessageSent = (result) => {
      console.log('Message template envoyé:', result);
      
      if (result.success) {
        // Ajouter le message à la liste des messages récents
        sentMessages.value.unshift({
          templateName: selectedTemplate.value.name,
          phoneNumber: phoneNumber.value,
          timestamp: result.timestamp || new Date().toISOString(),
          success: true,
          messageId: result.messageId
        });
        
        // Afficher une notification de succès
        notification.value = {
          show: true,
          success: true,
          message: 'Le message WhatsApp a été envoyé avec succès !'
        };
        
        // Réinitialiser l'interface
        selectedTemplate.value = null;
        templateData.value = null;
        showTemplateSelector.value = false;
      } else {
        // Afficher une notification d'erreur
        notification.value = {
          show: true,
          success: false,
          message: `Erreur: ${result.error}`
        };
      }
    };

    return {
      phoneNumber,
      showTemplateSelector,
      selectedTemplate,
      templateData,
      sentMessages,
      notification,
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