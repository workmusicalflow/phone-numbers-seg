<template>
  <q-page padding>
    <div class="whatsapp-templates-page">
      <!-- Modern Page Header -->
      <div class="page-header">
        <div class="header-content">
          <div class="header-title-section">
            <div class="title-icon-wrapper">
              <q-icon name="mark_unread_chat_alt" size="md" />
            </div>
            <div class="title-text">
              <h1 class="page-title">Templates WhatsApp</h1>
              <p class="page-subtitle">Envoyez des messages approuvés à vos clients</p>
            </div>
          </div>
          
          <div class="header-stats">
            <div class="stat-card">
              <div class="stat-value">{{ sentMessages.length }}</div>
              <div class="stat-label">Envoyés</div>
            </div>
            <div class="stat-card">
              <div class="stat-value">{{ successfulMessages }}</div>
              <div class="stat-label">Réussis</div>
            </div>
            <div class="stat-card">
              <div class="stat-value">{{ failedMessages }}</div>
              <div class="stat-label">Échoués</div>
            </div>
          </div>
        </div>
      </div>

      <div class="templates-content">
        <!-- Left Column: Recipient & Recent Messages -->
        <div class="left-column">
          <!-- Recipient Selection Card -->
          <div class="recipient-card">
            <div class="modern-card">
              <div class="card-header whatsapp-gradient">
                <div class="header-content">
                  <q-icon name="contact_phone" size="md" class="header-icon" />
                  <div class="header-text">
                    <h3 class="header-title">Destinataire</h3>
                    <p class="header-subtitle">Choisir le contact à contacter</p>
                  </div>
                </div>
              </div>

              <div class="card-content">
                <div class="phone-input-section">
                  <q-input
                    v-model="phoneNumber"
                    label="Numéro de téléphone"
                    outlined
                    clearable
                    class="modern-input"
                    :rules="[phoneValidationRule]"
                  >
                    <template v-slot:prepend>
                      <q-icon name="phone" color="primary" />
                    </template>
                  </q-input>
                  <p class="input-hint">
                    <q-icon name="info" size="sm" class="q-mr-xs" />
                    Format international requis (ex: +225 XX XX XX XX)
                  </p>
                </div>

                <div class="action-section">
                  <q-btn
                    :disable="!phoneNumber || !isPhoneValid"
                    color="primary"
                    size="lg"
                    icon="chat"
                    label="Sélectionner un template"
                    @click="showTemplateSelector = true"
                    class="select-template-btn"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Recent Messages Card -->
          <div v-if="sentMessages.length > 0" class="recent-messages-card">
            <div class="modern-card">
              <div class="card-header whatsapp-gradient">
                <div class="header-content">
                  <q-icon name="history" size="md" class="header-icon" />
                  <div class="header-text">
                    <h3 class="header-title">Messages Récents</h3>
                    <p class="header-subtitle">{{ sentMessages.length }} message{{ sentMessages.length !== 1 ? 's' : '' }} envoyé{{ sentMessages.length !== 1 ? 's' : '' }}</p>
                  </div>
                </div>
                <div class="header-actions">
                  <q-btn
                    color="white"
                    text-color="primary"
                    icon="clear_all"
                    label="Effacer"
                    outline
                    size="sm"
                    @click="clearRecentMessages"
                    class="modern-btn"
                  />
                </div>
              </div>

              <div class="card-content">
                <div class="messages-list">
                  <div
                    v-for="(message, index) in sentMessages.slice(0, 5)"
                    :key="index"
                    class="message-item"
                  >
                    <div class="message-info">
                      <div class="message-template">{{ message.templateName }}</div>
                      <div class="message-details">
                        <span class="phone-number">{{ message.phoneNumber }}</span>
                        <span class="separator">•</span>
                        <span class="timestamp">{{ formatDate(message.timestamp) }}</span>
                      </div>
                    </div>
                    <div class="message-status">
                      <q-chip
                        :class="['status-chip', message.success ? 'status-success' : 'status-error']"
                        text-color="white"
                        size="sm"
                      >
                        <q-icon 
                          :name="message.success ? 'check_circle' : 'error'" 
                          size="xs" 
                          class="q-mr-xs" 
                        />
                        {{ message.success ? 'Envoyé' : 'Échec' }}
                      </q-chip>
                    </div>
                  </div>
                  
                  <div v-if="sentMessages.length > 5" class="show-more">
                    <q-btn
                      flat
                      color="primary"
                      size="sm"
                      label="Voir plus..."
                      @click="showAllMessages = true"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column: Template Selection & Customization -->
        <div class="right-column">
          <!-- Template Selector -->
          <div v-if="showTemplateSelector && !selectedTemplate" class="template-selector-card">
            <div class="modern-card">
              <div class="card-header whatsapp-gradient">
                <div class="header-content">
                  <q-icon name="view_module" size="md" class="header-icon" />
                  <div class="header-text">
                    <h3 class="header-title">Sélection de Template</h3>
                    <p class="header-subtitle">Choisissez un modèle approuvé</p>
                  </div>
                </div>
                <div class="header-actions">
                  <q-btn
                    color="white"
                    text-color="primary"
                    icon="close"
                    round
                    flat
                    size="sm"
                    @click="showTemplateSelector = false"
                    class="close-btn"
                  />
                </div>
              </div>

              <div class="card-content template-selector-content">
                <EnhancedTemplateSelector
                  :title="''"
                  :show-advanced-filters="true"
                  :show-organized-sections="true"
                  :group-by-category="true"
                  @select="template => selectEnhancedTemplate(template, phoneNumber)"
                  @filter-change="handleFilterChange"
                />
              </div>
            </div>
          </div>
          
          <!-- Template Customization -->
          <div v-else-if="showTemplateSelector && selectedTemplate" class="template-customization-card">
            <div class="modern-card">
              <div class="card-header whatsapp-gradient">
                <div class="header-content">
                  <q-icon name="edit" size="md" class="header-icon" />
                  <div class="header-text">
                    <h3 class="header-title">Personnalisation</h3>
                    <p class="header-subtitle">{{ selectedTemplate.name }}</p>
                  </div>
                </div>
                <div class="header-actions">
                  <q-btn
                    color="white"
                    text-color="primary"
                    icon="arrow_back"
                    label="Changer"
                    outline
                    size="sm"
                    @click="selectedTemplate = null"
                    class="modern-btn"
                  />
                </div>
              </div>

              <div class="card-content">
                <WhatsAppMessageComposer
                  :template-data="templateData"
                  :recipient-phone-number="phoneNumber"
                  @message-sent="onTemplateMessageSent"
                  @cancel="showTemplateSelector = false"
                  @change-template="selectedTemplate = null"
                />
              </div>
            </div>
          </div>

          <!-- Info Card -->
          <div v-else class="info-card">
            <div class="modern-card">
              <div class="card-header whatsapp-gradient">
                <div class="header-content">
                  <q-icon name="help" size="md" class="header-icon" />
                  <div class="header-text">
                    <h3 class="header-title">Guide des Templates</h3>
                    <p class="header-subtitle">Informations importantes</p>
                  </div>
                </div>
              </div>

              <div class="card-content">
                <div class="info-content">
                  <div class="info-section">
                    <h4 class="info-title">Qu'est-ce qu'un template WhatsApp ?</h4>
                    <p class="info-text">
                      Les templates WhatsApp sont des modèles de messages pré-approuvés qui vous permettent d'envoyer des messages à vos clients même en dehors de la fenêtre de 24 heures.
                    </p>
                  </div>

                  <div class="info-section">
                    <h4 class="info-title">Points importants</h4>
                    <div class="info-points">
                      <div class="info-point">
                        <q-icon name="verified" color="positive" size="sm" class="point-icon" />
                        <span>Templates approuvés par Meta</span>
                      </div>
                      <div class="info-point">
                        <q-icon name="schedule" color="warning" size="sm" class="point-icon" />
                        <span>Envoi possible hors fenêtre 24h</span>
                      </div>
                      <div class="info-point">
                        <q-icon name="edit" color="info" size="sm" class="point-icon" />
                        <span>Variables personnalisables</span>
                      </div>
                      <div class="info-point">
                        <q-icon name="image" color="primary" size="sm" class="point-icon" />
                        <span>Support médias et boutons</span>
                      </div>
                      <div class="info-point">
                        <q-icon name="category" color="secondary" size="sm" class="point-icon" />
                        <span>Catégories : Marketing, Utilitaire, Authentification</span>
                      </div>
                    </div>
                  </div>

                  <div class="info-section">
                    <h4 class="info-title">Comment commencer ?</h4>
                    <div class="steps-list">
                      <div class="step-item">
                        <div class="step-number">1</div>
                        <div class="step-content">
                          <strong>Saisissez le numéro</strong>
                          <span>Entrez le numéro de téléphone du destinataire</span>
                        </div>
                      </div>
                      <div class="step-item">
                        <div class="step-number">2</div>
                        <div class="step-content">
                          <strong>Sélectionnez un template</strong>
                          <span>Choisissez parmi les modèles approuvés</span>
                        </div>
                      </div>
                      <div class="step-item">
                        <div class="step-number">3</div>
                        <div class="step-content">
                          <strong>Personnalisez et envoyez</strong>
                          <span>Remplissez les variables et envoyez</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modern Notification Dialog -->
    <q-dialog v-model="notification.show">
      <div class="notification-dialog">
        <div class="modern-card">
          <div class="card-header" :class="notification.success ? 'success-gradient' : 'error-gradient'">
            <div class="header-content">
              <q-icon 
                :name="notification.success ? 'check_circle' : 'error'" 
                size="md" 
                class="header-icon" 
              />
              <div class="header-text">
                <h3 class="header-title">
                  {{ notification.success ? 'Succès' : 'Erreur' }}
                </h3>
                <p class="header-subtitle">
                  {{ notification.success ? 'Message envoyé' : 'Échec de l\'envoi' }}
                </p>
              </div>
            </div>
          </div>

          <div class="card-content">
            <p class="notification-message">{{ notification.message }}</p>
          </div>

          <div class="dialog-actions">
            <q-btn
              color="primary"
              label="Fermer"
              v-close-popup
              class="action-btn-primary"
            />
          </div>
        </div>
      </div>
    </q-dialog>

    <!-- All Messages Dialog -->
    <q-dialog v-model="showAllMessages" maximized>
      <div class="all-messages-dialog">
        <div class="modern-card">
          <div class="card-header whatsapp-gradient">
            <div class="header-content">
              <q-icon name="history" size="md" class="header-icon" />
              <div class="header-text">
                <h3 class="header-title">Tous les Messages</h3>
                <p class="header-subtitle">Historique complet des envois</p>
              </div>
            </div>
            <div class="header-actions">
              <q-btn
                color="white"
                text-color="primary"
                icon="close"
                round
                flat
                size="sm"
                v-close-popup
                class="close-btn"
              />
            </div>
          </div>

          <div class="card-content">
            <div class="all-messages-list">
              <div
                v-for="(message, index) in sentMessages"
                :key="index"
                class="message-item"
              >
                <div class="message-info">
                  <div class="message-template">{{ message.templateName }}</div>
                  <div class="message-details">
                    <span class="phone-number">{{ message.phoneNumber }}</span>
                    <span class="separator">•</span>
                    <span class="timestamp">{{ formatDate(message.timestamp) }}</span>
                  </div>
                </div>
                <div class="message-status">
                  <q-chip
                    :class="['status-chip', message.success ? 'status-success' : 'status-error']"
                    text-color="white"
                    size="sm"
                  >
                    <q-icon 
                      :name="message.success ? 'check_circle' : 'error'" 
                      size="xs" 
                      class="q-mr-xs" 
                    />
                    {{ message.success ? 'Envoyé' : 'Échec' }}
                  </q-chip>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </q-dialog>
  </q-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, getCurrentInstance } from 'vue';
import { useQuasar } from 'quasar';
import EnhancedTemplateSelector from '../components/whatsapp/EnhancedTemplateSelector.vue';
import WhatsAppMessageComposer from '../components/whatsapp/WhatsAppMessageComposer.vue';
import { whatsAppClient } from '../services/whatsappRestClient';

console.log('[WhatsAppTemplatesView] Initialisation du composant');
const $q = useQuasar();

const phoneNumber = ref('');
const showTemplateSelector = ref(false);
const selectedTemplate = ref<any>(null);
const templateData = ref<any>(null);
const sentMessages = ref<any[]>([]);
const showAllMessages = ref(false);
const notification = ref({
  show: false,
  success: false,
  message: ''
});

// Validation du numéro de téléphone
const phoneValidationRule = (val: string) => {
  if (!val) return true; // Permet un champ vide
  const phoneRegex = /^\+[1-9]\d{1,14}$/;
  return phoneRegex.test(val) || 'Format international requis (ex: +225 XX XX XX XX)';
};

const isPhoneValid = computed(() => {
  if (!phoneNumber.value) return false;
  const phoneRegex = /^\+[1-9]\d{1,14}$/;
  return phoneRegex.test(phoneNumber.value);
});

// Computed properties for statistics
const successfulMessages = computed(() => {
  return sentMessages.value.filter(msg => msg.success).length;
});

const failedMessages = computed(() => {
  return sentMessages.value.filter(msg => !msg.success).length;
});

// Clear recent messages
const clearRecentMessages = () => {
  $q.dialog({
    title: 'Confirmer',
    message: 'Voulez-vous vraiment effacer tous les messages récents ?',
    cancel: true,
    persistent: true
  }).onOk(() => {
    sentMessages.value = [];
    $q.notify({
      color: 'positive',
      message: 'Messages récents effacés',
      icon: 'check_circle'
    });
  });
};
// Log pendant le montage du composant
onMounted(() => {
  console.log('[WhatsAppTemplatesView] Composant monté');
  console.log('[WhatsAppTemplatesView] Composants enregistrés:', Object.keys(getCurrentInstance()?.appContext.components || {}));
});

// Formater une date
const formatDate = (dateString: string) => {
  const date = new Date(dateString);
  return date.toLocaleString('fr-FR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

// Gérer les changements de filtres
const handleFilterChange = (filters: any) => {
  console.log('[WhatsAppTemplates] Filtre changé:', filters);
  // Ici, vous pourriez faire des actions supplémentaires basées sur les filtres
};

// Sélectionner un template pour personnalisation 
const selectEnhancedTemplate = (template: any, recipientPhoneNumber: string) => {
  console.log('Template sélectionné:', template.name);
  console.log('Template complet:', template);
  
  // Enregistrer le template sélectionné
  selectedTemplate.value = template;
  
  try {
    // Obtenir les données du template depuis le composant
    const bodyVariables: string[] = [];
    const buttonVariables: Record<number, string> = {};
    let components: any[] = [];
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
          
          if (['URL', 'QUICK_REPLY', 'PHONE_NUMBER', 'CALL_TO_ACTION'].includes(buttonType)) {
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
      components: components
    };
    
    console.log('[V1] Template prêt pour personnalisation:', templateData.value);
  } catch (error: any) {
    console.error('Erreur lors de la préparation du template:', error);
    $q.notify({
      type: 'negative',
      message: `Erreur de préparation du template: ${error.message}`,
      position: 'top',
      timeout: 3000
    });
  }
};
// Callback quand un message template a été envoyé
const onTemplateMessageSent = (result: any) => {
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
      language: selectedTemplate.value.language
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
      error: result.error || 'Erreur inconnue'
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
</script>

<style lang="scss" scoped>
// WhatsApp Color Palette
$whatsapp-primary: #25d366;
$whatsapp-secondary: #128c7e;
$whatsapp-accent: #075e54;
$whatsapp-light: #dcf8c6;

// Design System Integration
.whatsapp-templates-page {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0;
}

// Modern Page Header
.page-header {
  background: linear-gradient(135deg, $whatsapp-accent 0%, $whatsapp-secondary 100%);
  border-radius: 16px;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 8px 32px rgba(7, 94, 84, 0.2);
  
  .header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
    
    .header-title-section {
      display: flex;
      align-items: center;
      gap: 1.5rem;
      
      .title-icon-wrapper {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 16px;
        padding: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        
        .q-icon {
          color: white;
        }
      }
      
      .title-text {
        color: white;
        
        .page-title {
          font-size: 2rem;
          font-weight: 700;
          margin: 0 0 0.5rem 0;
          line-height: 1.2;
        }
        
        .page-subtitle {
          font-size: 1.1rem;
          margin: 0;
          opacity: 0.9;
          font-weight: 400;
        }
      }
    }
    
    .header-stats {
      display: flex;
      gap: 1rem;
      
      .stat-card {
        background: rgba(255, 255, 255, 0.15);
        border-radius: 12px;
        padding: 1rem 1.5rem;
        text-align: center;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        min-width: 80px;
        
        .stat-value {
          font-size: 1.5rem;
          font-weight: 700;
          color: white;
          line-height: 1;
          margin-bottom: 0.25rem;
        }
        
        .stat-label {
          font-size: 0.8rem;
          color: rgba(255, 255, 255, 0.8);
          text-transform: uppercase;
          letter-spacing: 0.5px;
          font-weight: 500;
        }
      }
    }
  }
}

// Templates Content Layout
.templates-content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  
  @media (max-width: 1024px) {
    grid-template-columns: 1fr;
  }
}

.left-column,
.right-column {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

// Modern Card Structure
.modern-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
  overflow: hidden;
  transition: all 0.3s ease;
  
  &:hover {
    box-shadow: 0 12px 48px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
  }
}

// WhatsApp Gradient
.whatsapp-gradient {
  background: linear-gradient(135deg, $whatsapp-accent 0%, $whatsapp-secondary 100%);
}

// Success/Error Gradients
.success-gradient {
  background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
}

.error-gradient {
  background: linear-gradient(135deg, #f44336 0%, #ef5350 100%);
}

// Card Header
.card-header {
  padding: 1.5rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  
  .header-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    
    .header-icon {
      color: white;
      opacity: 0.9;
    }
    
    .header-text {
      color: white;
      
      .header-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0 0 0.25rem 0;
        line-height: 1.2;
      }
      
      .header-subtitle {
        font-size: 0.9rem;
        margin: 0;
        opacity: 0.8;
        line-height: 1.1;
      }
    }
  }
  
  .header-actions {
    .modern-btn,
    .close-btn {
      border-radius: 8px;
      font-weight: 500;
      text-transform: none;
      border: 1px solid rgba(255, 255, 255, 0.3);
      
      &:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.5);
      }
    }
  }
}

.card-content {
  padding: 2rem;
}

// Phone Input Section
.phone-input-section {
  margin-bottom: 2rem;
  
  .modern-input {
    .q-field__control {
      border-radius: 12px;
      height: 56px;
    }
    
    .q-field__native {
      font-size: 1rem;
    }
    
    .q-field__label {
      font-weight: 500;
    }
    
    &.q-field--focused {
      .q-field__control {
        box-shadow: 0 0 0 2px rgba(37, 211, 102, 0.2);
      }
    }
  }
  
  .input-hint {
    font-size: 0.875rem;
    color: #666;
    margin-top: 0.5rem;
    display: flex;
    align-items: center;
    
    .q-icon {
      color: $whatsapp-secondary;
    }
  }
}

.action-section {
  text-align: center;
  
  .select-template-btn {
    background: linear-gradient(135deg, $whatsapp-primary 0%, $whatsapp-secondary 100%);
    border-radius: 12px;
    padding: 0.75rem 2rem;
    font-weight: 600;
    text-transform: none;
    min-width: 250px;
    
    &:hover {
      box-shadow: 0 8px 24px rgba(37, 211, 102, 0.3);
    }
    
    &:disabled {
      opacity: 0.6;
      box-shadow: none;
    }
  }
}

// Messages List
.messages-list {
  .message-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-radius: 12px;
    background: #f8f9fa;
    margin-bottom: 0.75rem;
    transition: all 0.2s ease;
    
    &:hover {
      background: #e9ecef;
      transform: translateX(2px);
    }
    
    &:last-child {
      margin-bottom: 0;
    }
    
    .message-info {
      flex: 1;
      
      .message-template {
        font-weight: 600;
        color: #333;
        margin-bottom: 0.25rem;
        font-size: 1rem;
      }
      
      .message-details {
        font-size: 0.875rem;
        color: #666;
        
        .phone-number {
          font-weight: 500;
        }
        
        .separator {
          margin: 0 0.5rem;
          opacity: 0.5;
        }
        
        .timestamp {
          opacity: 0.8;
        }
      }
    }
    
    .message-status {
      flex-shrink: 0;
      
      .status-chip {
        font-weight: 500;
        font-size: 0.75rem;
        padding: 0.25rem 0.75rem;
        border-radius: 6px;
        
        &.status-success {
          background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
        }
        
        &.status-error {
          background: linear-gradient(135deg, #f44336 0%, #ef5350 100%);
        }
      }
    }
  }
  
  .show-more {
    text-align: center;
    padding-top: 1rem;
    border-top: 1px solid #e9ecef;
    margin-top: 1rem;
  }
}

// Template Selector Content
.template-selector-content {
  max-height: 600px;
  overflow-y: auto;
  padding: 0 !important;
}

// Info Content
.info-content {
  .info-section {
    margin-bottom: 2rem;
    
    &:last-child {
      margin-bottom: 0;
    }
    
    .info-title {
      font-size: 1.1rem;
      font-weight: 600;
      color: #333;
      margin: 0 0 1rem 0;
    }
    
    .info-text {
      font-size: 1rem;
      line-height: 1.6;
      color: #666;
      margin: 0 0 1rem 0;
    }
    
    .info-points {
      .info-point {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem 0;
        
        .point-icon {
          flex-shrink: 0;
        }
        
        span {
          font-size: 0.95rem;
          color: #555;
        }
      }
    }
    
    .steps-list {
      .step-item {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 12px;
        margin-bottom: 0.75rem;
        
        &:last-child {
          margin-bottom: 0;
        }
        
        .step-number {
          background: linear-gradient(135deg, $whatsapp-primary 0%, $whatsapp-secondary 100%);
          color: white;
          width: 32px;
          height: 32px;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-weight: 700;
          font-size: 0.875rem;
          flex-shrink: 0;
        }
        
        .step-content {
          flex: 1;
          
          strong {
            display: block;
            color: #333;
            margin-bottom: 0.25rem;
            font-size: 1rem;
          }
          
          span {
            color: #666;
            font-size: 0.875rem;
            line-height: 1.4;
          }
        }
      }
    }
  }
}

// Dialog Styles
.notification-dialog,
.all-messages-dialog {
  .modern-card {
    min-width: 400px;
    max-width: 600px;
    margin: 2rem;
    
    .notification-message {
      font-size: 1rem;
      line-height: 1.5;
      color: #333;
      margin: 0;
    }
  }
}

.all-messages-dialog {
  .modern-card {
    min-width: 80vw;
    max-width: 1000px;
    max-height: 90vh;
    margin: 5vh auto;
    display: flex;
    flex-direction: column;
    
    .card-content {
      flex: 1;
      overflow-y: auto;
    }
    
    .all-messages-list {
      .message-item {
        margin-bottom: 1rem;
        background: white;
        border: 1px solid #e9ecef;
        
        &:hover {
          border-color: $whatsapp-secondary;
          background: #f8f9fa;
        }
      }
    }
  }
}

.dialog-actions {
  padding: 1.5rem 2rem;
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  border-top: 1px solid #e9ecef;
  background: #fafafa;
  
  .action-btn-primary {
    background: linear-gradient(135deg, $whatsapp-primary 0%, $whatsapp-secondary 100%);
    color: white;
    font-weight: 600;
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    text-transform: none;
    
    &:hover {
      box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
    }
  }
}

// Responsive Design
@media (max-width: 1024px) {
  .templates-content {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
  
  .header-stats {
    flex-direction: column;
    gap: 0.75rem !important;
  }
}

@media (max-width: 768px) {
  .page-header {
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    
    .header-content {
      flex-direction: column;
      gap: 1.5rem;
      
      .header-title-section {
        width: 100%;
        
        .title-icon-wrapper {
          padding: 0.75rem;
        }
        
        .title-text {
          .page-title {
            font-size: 1.5rem;
          }
          
          .page-subtitle {
            font-size: 1rem;
          }
        }
      }
      
      .header-stats {
        width: 100%;
        flex-direction: row;
        justify-content: space-around;
        
        .stat-card {
          min-width: auto;
          flex: 1;
          padding: 0.75rem 1rem;
          
          .stat-value {
            font-size: 1.25rem;
          }
          
          .stat-label {
            font-size: 0.75rem;
          }
        }
      }
    }
  }
  
  .card-header {
    padding: 1rem 1.5rem;
    flex-direction: column;
    gap: 1rem;
    
    .header-actions {
      width: 100%;
      text-align: center;
    }
  }
  
  .card-content {
    padding: 1.5rem;
  }
  
  .templates-content {
    gap: 1rem;
  }
  
  .action-section .select-template-btn {
    min-width: auto;
    width: 100%;
  }
  
  .notification-dialog .modern-card,
  .all-messages-dialog .modern-card {
    margin: 1rem;
    min-width: auto;
    max-width: none;
    width: calc(100vw - 2rem);
  }
}

@media (max-width: 480px) {
  .page-header {
    padding: 1rem;
    border-radius: 12px;
    
    .header-title-section {
      gap: 1rem;
      
      .title-text .page-title {
        font-size: 1.25rem;
      }
    }
    
    .header-stats {
      .stat-card {
        padding: 0.5rem 0.75rem;
        
        .stat-value {
          font-size: 1rem;
        }
      }
    }
  }
  
  .modern-card {
    border-radius: 12px;
  }
  
  .card-content {
    padding: 1rem;
  }
  
  .notification-dialog .modern-card {
    margin: 0.5rem;
  }
}

// Deep styles for child components
:deep(.section-header) {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  padding: 1rem;
  border-radius: 8px;
  border-left: 4px solid $whatsapp-secondary;
}

:deep(.template-card) {
  transition: all 0.3s ease;
  border-radius: 12px;
  
  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
  }
}

:deep(.q-badge.MARKETING) {
  background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
}

:deep(.q-badge.UTILITY) {
  background: linear-gradient(135deg, #2196f3 0%, #42a5f5 100%);
}

:deep(.q-badge.AUTHENTICATION) {
  background: linear-gradient(135deg, #ff9800 0%, #ffb74d 100%);
}
</style>