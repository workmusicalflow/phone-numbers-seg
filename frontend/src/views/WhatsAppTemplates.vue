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
          <q-card v-if="showTemplateSelector" class="template-selector-card" flat bordered>
            <q-card-section>
              <WhatsAppTemplateSelector
                :recipient-phone-number="phoneNumber"
                @template-selected="sendTemplate"
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
import WhatsAppTemplateSelector from '../components/whatsapp/WhatsAppTemplateSelector.vue';

export default defineComponent({
  name: 'WhatsAppTemplatesView',
  components: {
    WhatsAppTemplateSelector
  },
  setup() {
    console.log('[WhatsAppTemplatesView] Initialisation du composant');
    
    const phoneNumber = ref('');
    const showTemplateSelector = ref(false);
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

    // Envoyer un template
    const sendTemplate = async (templateData) => {
      try {
        const response = await fetch('/graphql.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            query: `
              mutation SendWhatsAppTemplate($input: SendTemplateInput!) {
                sendWhatsAppTemplateV2(input: $input) {
                  success
                  messageId
                  error
                }
              }
            `,
            variables: {
              input: {
                recipientPhoneNumber: templateData.recipientPhoneNumber,
                templateName: templateData.template.name,
                templateLanguage: templateData.template.language,
                templateComponentsJsonString: templateData.templateComponentsJsonString,
                headerMediaUrl: templateData.headerMediaUrl,
                bodyVariables: templateData.bodyVariables,
                buttonVariables: Object.values(templateData.buttonVariables)
              }
            }
          }),
          credentials: 'include'
        });
        
        const result = await response.json();
        
        if (result.errors) {
          throw new Error(result.errors[0].message);
        }
        
        const sendResult = result.data.sendWhatsAppTemplateV2;
        
        if (sendResult.success) {
          // Ajouter le message à la liste des messages récents
          sentMessages.value.unshift({
            templateName: templateData.template.name,
            phoneNumber: templateData.recipientPhoneNumber,
            timestamp: new Date().toISOString(),
            success: true,
            messageId: sendResult.messageId
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
          throw new Error(sendResult.error || 'Échec de l\'envoi du message');
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

    return {
      phoneNumber,
      showTemplateSelector,
      sentMessages,
      notification,
      formatDate,
      sendTemplate
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
</style>