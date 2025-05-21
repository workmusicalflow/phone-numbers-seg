<template>
  <div class="whatsapp-template-message">
    <q-card flat bordered>
      <q-card-section class="q-pb-none">
        <div class="text-h6">Configuration et envoi d'un message WhatsApp</div>
        <div class="text-caption">Utilisez les templates disponibles pour envoyer des messages structurés</div>
      </q-card-section>

      <q-separator />

      <q-card-section>
        <div class="row q-col-gutter-md">
          <!-- Sélection du destinataire -->
          <div class="col-12">
            <q-input
              v-model="recipient"
              label="Numéro de téléphone du destinataire"
              outlined
              :rules="[val => !!val || 'Le numéro est requis', phoneNumberRule]"
              use-input
              fill-input
              hide-selected
              input-debounce="0"
              :options="suggestionData.frequentRecipients.map(num => ({label: formatPhoneNumber(num), value: num}))"
              @filter="filterFn"
              @input-value="setRecipientValue"
            >
              <template v-slot:prepend>
                <q-icon name="phone" />
              </template>
              <template v-slot:after v-if="suggestionData.frequentRecipients.length > 0">
                <q-btn round dense flat icon="history" size="sm">
                  <q-tooltip>Destinataires récents</q-tooltip>
                  <q-menu>
                    <q-list style="min-width: 200px">
                      <q-item-label header>Destinataires fréquents</q-item-label>
                      <q-item 
                        v-for="phone in suggestionData.frequentRecipients" 
                        :key="phone"
                        clickable 
                        v-close-popup
                        @click="recipient = phone"
                      >
                        <q-item-section>
                          <q-item-label>{{ formatPhoneNumber(phone) }}</q-item-label>
                        </q-item-section>
                      </q-item>
                    </q-list>
                  </q-menu>
                </q-btn>
              </template>
            </q-input>
          </div>
        </div>
      </q-card-section>

      <!-- Interface adaptative en trois étapes -->
      <q-card-section>
        <q-stepper
          v-model="currentStep"
          vertical
          color="primary"
          animated
        >
          <!-- Étape 1: Sélection du template -->
          <q-step
            :name="1"
            title="Sélection du template"
            icon="style"
            :done="currentStep > 1"
          >
            <div v-if="currentStep === 1">
              <p>Sélectionnez un template parmi ceux disponibles dans votre compte WhatsApp Business.</p>
              
              <!-- Composant de sélection avancée -->
              <enhanced-template-selector
                v-if="!selectedTemplate"
                @select="onTemplateSelected"
                showPagination
              />
              
              <!-- Affichage du template sélectionné -->
              <div v-else class="selected-template q-pa-md">
                <div class="row items-center justify-between q-mb-md">
                  <div>
                    <div class="text-h6">{{ selectedTemplate.name }}</div>
                    <div class="template-info">
                      <q-badge :color="getCategoryColor(selectedTemplate.category)">
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
                    icon="restart_alt"
                    @click="selectedTemplate = null"
                  />
                </div>
                
                <q-card flat bordered>
                  <q-card-section>
                    <template-card
                      :template="selectedTemplate"
                      :is-favorite="false"
                      :show-buttons="true"
                      :show-variables="true"
                      :show-header-type="true"
                    />
                  </q-card-section>
                </q-card>
                
                <div class="row justify-end q-mt-md">
                  <q-btn
                    color="primary"
                    label="Passer à la configuration"
                    icon-right="arrow_forward"
                    @click="currentStep = 2"
                  />
                </div>
              </div>
            </div>
            
            <q-stepper-navigation v-if="currentStep !== 1">
              <q-btn
                flat
                color="primary"
                label="Revenir à la sélection"
                @click="currentStep = 1"
              />
            </q-stepper-navigation>
          </q-step>

          <!-- Étape 2: Configuration des variables -->
          <q-step
            :name="2"
            title="Configuration des variables"
            icon="settings"
            :done="currentStep > 2"
          >
            <div v-if="currentStep === 2">
              <p>Configurez les variables et les options du template pour personnaliser votre message.</p>
              
              <!-- Composant de configuration -->
              <whatsapp-template-configurator
                v-if="selectedTemplate"
                :template="selectedTemplate"
                :recipientPhoneNumber="recipient"
                :historyData="templateHistoryData"
                :parameterSuggestions="suggestionData.parameterValues"
                @use-template="onTemplateConfigured"
                @cancel="currentStep = 1"
              />
            </div>
            
            <q-stepper-navigation v-if="currentStep === 3">
              <q-btn
                flat
                color="primary"
                label="Revenir à la configuration"
                @click="currentStep = 2"
              />
            </q-stepper-navigation>
          </q-step>

          <!-- Étape 3: Aperçu et envoi -->
          <q-step
            :name="3"
            title="Aperçu et envoi"
            icon="send"
          >
            <p>Vérifiez votre message avant de l'envoyer.</p>
            
            <q-card flat bordered>
              <q-card-section>
                <div class="text-h6">Résumé du message</div>
                
                <div class="row q-col-gutter-md q-mt-md">
                  <div class="col-12 col-md-6">
                    <q-list bordered separator>
                      <q-item>
                        <q-item-section>
                          <q-item-label overline>Destinataire</q-item-label>
                          <q-item-label>{{ formatPhoneNumber(recipient) }}</q-item-label>
                        </q-item-section>
                      </q-item>
                      
                      <q-item>
                        <q-item-section>
                          <q-item-label overline>Template</q-item-label>
                          <q-item-label>{{ selectedTemplate?.name }}</q-item-label>
                          <q-item-label caption>{{ selectedTemplate?.language }}</q-item-label>
                        </q-item-section>
                      </q-item>
                      
                      <q-item v-if="configuredTemplateData && configuredTemplateData.bodyVariables">
                        <q-item-section>
                          <q-item-label overline>Variables</q-item-label>
                          <q-item-label>
                            <div v-for="(val, idx) in configuredTemplateData.bodyVariables" :key="`var-${idx}`">
                              Variable {{idx + 1}}: <strong>{{ val }}</strong>
                            </div>
                          </q-item-label>
                        </q-item-section>
                      </q-item>
                      
                      <q-item v-if="configuredTemplateData && configuredTemplateData.headerMediaType">
                        <q-item-section>
                          <q-item-label overline>Média d'en-tête</q-item-label>
                          <q-item-label>
                            <div v-if="configuredTemplateData.headerMediaType === 'url'">
                              URL: <a :href="configuredTemplateData.headerMediaUrl" target="_blank">{{ configuredTemplateData.headerMediaUrl }}</a>
                            </div>
                            <div v-else-if="configuredTemplateData.headerMediaType === 'id'">
                              Media ID: {{ configuredTemplateData.headerMediaId }}
                            </div>
                          </q-item-label>
                        </q-item-section>
                      </q-item>
                    </q-list>
                  </div>
                  
                  <div class="col-12 col-md-6">
                    <!-- Aperçu stylisé du message -->
                    <div class="message-preview q-pa-md">
                      <div class="message-preview-header">
                        <div class="preview-app-bar">
                          <span>WhatsApp Business</span>
                        </div>
                      </div>
                      
                      <div class="message-content q-pa-md">
                        <!-- Message content will go here -->
                        <div class="text-caption text-italic text-center">
                          Aperçu du message tel qu'il apparaîtra dans WhatsApp
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </q-card-section>
              
              <q-separator />
              
              <q-card-actions align="right">
                <q-btn
                  flat
                  color="grey"
                  label="Retour à la configuration"
                  @click="currentStep = 2"
                />
                <q-btn
                  color="primary"
                  :loading="sending"
                  label="Envoyer le message"
                  icon-right="send"
                  @click="sendTemplateMessage"
                />
              </q-card-actions>
            </q-card>
          </q-step>
        </q-stepper>
      </q-card-section>
    </q-card>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useQuasar } from 'quasar';
import { useWhatsAppStore } from '@/stores/whatsappStore';
import { useWhatsAppTemplateStore } from '@/stores/whatsappTemplateStore';
import EnhancedTemplateSelector from './EnhancedTemplateSelector.vue';
import WhatsAppTemplateConfigurator from './WhatsAppTemplateConfigurator.vue';
import TemplateCard from './TemplateCard.vue';

const $q = useQuasar();
const whatsAppStore = useWhatsAppStore();
const templateStore = useWhatsAppTemplateStore();

// Navigation par étapes
const currentStep = ref(1);

// Destinataire et validation
const recipient = ref('');

// Sélection et configuration du template
const selectedTemplate = ref(null);
const configuredTemplateData = ref(null);
const templateHistoryData = ref([]);
const suggestionData = ref({
  parameterValues: {},
  frequentRecipients: []
});

// État d'envoi
const sending = ref(false);

// Validation du numéro de téléphone
function phoneNumberRule(val: string) {
  // Accepte les formats : +XXXXXXXXXXXX, XXXXXXXXXXXX, ou avec des espaces
  // Minimum 10 chiffres après le code pays
  const digitsOnly = val.replace(/\s+/g, '').replace(/^\+/, '');
  return digitsOnly.length >= 10 || 'Numéro de téléphone invalide';
}

// Fonction pour normaliser le numéro de téléphone
function normalizePhoneNumber(phoneNumber: string): string {
  // Supprimer tous les caractères non numériques
  let number = phoneNumber.replace(/[^0-9]/g, '');
  
  // S'assurer que le numéro commence par le code pays (225 pour la Côte d'Ivoire)
  if (!number.startsWith('225')) {
    number = '225' + number;
  }
  
  return number;
}

// Formatter le numéro de téléphone pour l'affichage
function formatPhoneNumber(phone: string): string {
  if (!phone) return '';
  
  // Normaliser d'abord
  const normalized = normalizePhoneNumber(phone);
  
  // Ajouter le '+' et formater avec des espaces pour la lisibilité
  const countryCode = normalized.substring(0, 3);
  const rest = normalized.substring(3);
  let formatted = '+' + countryCode;
  
  // Grouper le reste par deux chiffres
  for (let i = 0; i < rest.length; i += 2) {
    formatted += ' ' + rest.substring(i, i + 2);
  }
  
  return formatted;
}

// Obtenir la couleur selon la catégorie
function getCategoryColor(category: string): string {
  switch (category) {
    case 'MARKETING': return 'green';
    case 'UTILITY': return 'blue';
    case 'AUTHENTICATION': return 'orange';
    case 'ISSUE_RESOLUTION': return 'red';
    default: return 'grey';
  }
}

// Gestion des événements
function onTemplateSelected(template) {
  selectedTemplate.value = template;
  // Charger les données d'historique pour ce template
  loadTemplateHistory(template.id);
}

function onTemplateConfigured(templateData) {
  configuredTemplateData.value = templateData;
  currentStep.value = 3;
}

// Charger l'historique d'utilisation d'un template
async function loadTemplateHistory(templateId) {
  try {
    console.log('Chargement de l\'historique pour le template:', templateId);
    
    // Réinitialiser
    templateHistoryData.value = [];
    suggestionData.value = {
      parameterValues: {},
      frequentRecipients: []
    };
    
    // Récupérer l'historique des templates depuis le store
    await whatsAppStore.fetchTemplateHistory();
    
    // Filtrer l'historique pour ce template spécifique
    const filteredHistory = whatsAppStore.templateHistory.filter(
      record => record.templateId === templateId || 
               record.templateName === selectedTemplate.value.name
    );
    
    templateHistoryData.value = filteredHistory;
    
    // Récupérer les paramètres communs pour ce template
    await whatsAppStore.fetchCommonParameterValues();
    
    // Extraire les valeurs de paramètres couramment utilisées pour ce template
    const commonParamsData = whatsAppStore.commonParameters.find(
      param => param.templateName === selectedTemplate.value.name
    );
    
    if (commonParamsData) {
      suggestionData.value.parameterValues = commonParamsData.parameterValues;
    }
    
    // Extraire les destinataires fréquents pour ce template
    if (filteredHistory.length > 0) {
      const recipientCounts = {};
      
      filteredHistory.forEach(record => {
        if (record.recipient) {
          recipientCounts[record.recipient] = (recipientCounts[record.recipient] || 0) + 1;
        }
      });
      
      // Trier les destinataires par fréquence d'utilisation
      suggestionData.value.frequentRecipients = Object.entries(recipientCounts)
        .sort((a, b) => b[1] - a[1])
        .slice(0, 5)
        .map(([recipient]) => recipient);
    }
    
    console.log('Données de suggestion chargées:', suggestionData.value);
  } catch (error) {
    console.error('Erreur lors du chargement de l\'historique du template:', error);
  }
}

// Fonctions pour l'autocomplétion du destinataire
function filterFn(val, update) {
  if (val === '') {
    update(() => {
      // Si le champ est vide, on affiche les destinataires fréquents
      const options = suggestionData.value.frequentRecipients.map(num => ({
        label: formatPhoneNumber(num),
        value: num
      }));
      
      return options;
    });
    return;
  }
  
  update(() => {
    const needle = val.toLowerCase();
    const options = suggestionData.value.frequentRecipients
      .filter(phone => formatPhoneNumber(phone).toLowerCase().indexOf(needle) > -1)
      .map(num => ({
        label: formatPhoneNumber(num),
        value: num
      }));
    
    return options;
  });
}

function setRecipientValue(val) {
  recipient.value = val;
}

// Envoyer le message template
async function sendTemplateMessage() {
  if (!recipient.value || !selectedTemplate.value || !configuredTemplateData.value) {
    $q.notify({
      type: 'negative',
      message: 'Informations manquantes pour l\'envoi'
    });
    return;
  }
  
  sending.value = true;
  
  try {
    const normalizedRecipient = normalizePhoneNumber(recipient.value);
    
    // Construire les composants du template
    const components = [];
    
    // Ajouter le header si une référence de média est fournie
    if (configuredTemplateData.value.headerMediaType && 
        (configuredTemplateData.value.headerMediaUrl || configuredTemplateData.value.headerMediaId)) {
      const headerComponent = {
        type: 'header',
        parameters: [{
          type: getHeaderMediaType(configuredTemplateData.value.headerMediaType),
          [getHeaderMediaType(configuredTemplateData.value.headerMediaType)]: getHeaderMediaValue(configuredTemplateData.value)
        }]
      };
      components.push(headerComponent);
    }
    
    // Ajouter le body avec les paramètres
    if (configuredTemplateData.value.bodyVariables && configuredTemplateData.value.bodyVariables.length > 0) {
      const bodyParams = configuredTemplateData.value.bodyVariables.map(value => ({
        type: 'text',
        text: value
      }));
      
      if (bodyParams.length > 0) {
        components.push({
          type: 'body',
          parameters: bodyParams
        });
      }
    }
    
    // Ajouter les variables de boutons si présentes
    if (configuredTemplateData.value.buttonVariables) {
      const buttonComponent = {
        type: 'buttons',
        parameters: []
      };
      
      // Convertir les variables de boutons en paramètres
      for (const [index, value] of Object.entries(configuredTemplateData.value.buttonVariables)) {
        if (value) {
          buttonComponent.parameters.push({
            type: 'text',
            text: value
          });
        }
      }
      
      if (buttonComponent.parameters.length > 0) {
        components.push(buttonComponent);
      }
    }
    
    // Envoyer le template via le store
    await whatsAppStore.sendTemplate({
      recipient: normalizedRecipient,
      templateName: selectedTemplate.value.template_id || selectedTemplate.value.name,
      languageCode: selectedTemplate.value.language,
      components: components.length > 0 ? components : undefined
    });
    
    // Ajouter aux templates récemment utilisés
    templateStore.addRecentlyUsedTemplate(selectedTemplate.value);
    
    // Incrémenter le compteur d'utilisation
    templateStore.incrementTemplateUsage(selectedTemplate.value.id);
    
    $q.notify({
      type: 'positive',
      message: 'Message template envoyé avec succès'
    });
    
    // Réinitialiser les champs
    currentStep.value = 1;
    recipient.value = '';
    selectedTemplate.value = null;
    configuredTemplateData.value = null;
    
    // Recharger les messages pour voir le nouveau message
    await whatsAppStore.fetchMessages();
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: `Erreur lors de l'envoi: ${error.message || 'Erreur inconnue'}`
    });
  } finally {
    sending.value = false;
  }
}

// Fonctions utilitaires pour la gestion des médias
function getHeaderMediaType(mediaType) {
  if (mediaType === 'url' || mediaType === 'id') {
    return 'image'; // Par défaut, on considère que c'est une image
  }
  return 'image';
}

function getHeaderMediaValue(templateData) {
  if (templateData.headerMediaType === 'url') {
    return { link: templateData.headerMediaUrl };
  } else if (templateData.headerMediaType === 'id') {
    return { id: templateData.headerMediaId };
  }
  return { link: '' };
}

// Fonction pour traiter l'événement de réutilisation de template
function handleReuseTemplate(event) {
  const templateData = event.detail;
  if (!templateData) return;
  
  // Rechercher le template dans le store
  templateStore.fetchTemplates().then(() => {
    const template = templateStore.templates.find(t => 
      t.template_id === templateData.templateName || 
      t.name === templateData.templateName
    );
    
    if (template) {
      // Définir le template et le destinataire
      selectedTemplate.value = template;
      if (templateData.recipient) {
        recipient.value = templateData.recipient;
      }
      
      // Passer à l'étape de configuration
      currentStep.value = 2;
      
      // Charger les données d'historique pour le template
      loadTemplateHistory(template.id);
      
      // Préremplir les variables s'il y en a
      if (templateData.bodyVariables && templateData.bodyVariables.length > 0) {
        const templateConfig = {
          bodyVariables: templateData.bodyVariables,
          headerMediaType: templateData.headerMediaType || null,
          headerMediaUrl: templateData.headerMediaUrl || null,
          headerMediaId: templateData.headerMediaId || null
        };
        
        // On garde la configuration pour l'étape suivante
        setTimeout(() => {
          configuredTemplateData.value = templateConfig;
        }, 500); // Petit délai pour laisser le temps au composant de charger
      }
    }
  });
}

// Initialisation
onMounted(async () => {
  // Initialiser le store des templates si nécessaire
  if (!templateStore.initialized) {
    templateStore.initialize();
  }
  
  // Écouter l'événement de réutilisation de template
  document.addEventListener('reuse-whatsapp-template', handleReuseTemplate);
});

// Nettoyage à la destruction du composant
onUnmounted(() => {
  document.removeEventListener('reuse-whatsapp-template', handleReuseTemplate);
});
</script>

<style scoped lang="scss">
.whatsapp-template-message {
  max-width: 1000px;
  margin: 0 auto;
}

.template-info {
  display: flex;
  align-items: center;
  margin-top: 4px;
}

.message-preview {
  border: 1px solid #e0e0e0;
  border-radius: 12px;
  background-color: #f8f8f8;
  overflow: hidden;
}

.message-preview-header {
  background-color: #128C7E; // WhatsApp green
  color: white;
  padding: 8px 12px;
  text-align: center;
  font-weight: 500;
}

.message-content {
  background-color: #E8ECEF;
  border-radius: 8px;
  min-height: 200px;
}

// Style spécifique pour le mode mobile
@media (max-width: 600px) {
  .whatsapp-template-message {
    max-width: 100%;
  }
}
</style>