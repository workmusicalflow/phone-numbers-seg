<template>
  <div class="whatsapp-send-message">
    <q-card class="modern-card">
      <q-card-section class="card-header">
        <div class="row items-center">
          <q-icon name="chat" size="md" color="green" class="q-mr-md" />
          <div>
            <div class="text-h5 text-weight-medium">Envoyer un message WhatsApp</div>
            <div class="text-caption text-grey-7">Choisissez le type de message à envoyer</div>
          </div>
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section class="q-pa-none">
        <div class="row q-col-gutter-md">
          <div class="col-12">
            <q-tabs
              v-model="messageType"
              class="modern-tabs"
              active-color="green"
              indicator-color="green"
              align="left"
              no-caps
            >
              <q-tab name="text" class="tab-item">
                <div class="tab-content">
                  <q-icon name="message" size="sm" class="q-mr-sm" />
                  <div>
                    <div class="tab-label">Message texte</div>
                    <div class="tab-caption">Envoi rapide</div>
                  </div>
                </div>
              </q-tab>
              <q-tab name="template" class="tab-item">
                <div class="tab-content">
                  <q-icon name="dashboard_customize" size="sm" class="q-mr-sm" />
                  <div>
                    <div class="tab-label">Message template</div>
                    <div class="tab-caption">Formats prédéfinis</div>
                  </div>
                </div>
              </q-tab>
            </q-tabs>

            <q-separator />

            <q-tab-panels v-model="messageType" animated class="modern-panels">
              <!-- Panneau de message texte -->
              <q-tab-panel name="text" class="panel-content">
                <div class="panel-header q-mb-md">
                  <q-icon name="message" color="green" class="q-mr-sm" />
                  <span class="text-h6">Message texte simple</span>
                </div>
                
                <q-form ref="textForm" class="form-container">
                  <div class="input-group">
                    <label class="input-label">
                      <q-icon name="person" class="q-mr-xs" />
                      Destinataire
                    </label>
                    <q-input
                      v-model="recipient"
                      placeholder="Numéro de téléphone (ex: +225 XX XX XX XX)"
                      outlined
                      class="modern-input"
                      :rules="[val => !!val || 'Le numéro est requis', phoneNumberRule]"
                    >
                      <template v-slot:prepend>
                        <q-icon name="phone" color="green" />
                      </template>
                    </q-input>
                  </div>

                  <div class="input-group">
                    <label class="input-label">
                      <q-icon name="edit" class="q-mr-xs" />
                      Votre message
                    </label>
                    <q-input
                      v-model="textMessage"
                      placeholder="Rédigez votre message ici..."
                      type="textarea"
                      outlined
                      class="modern-input message-input"
                      :rules="[val => !!val || 'Le message est requis']"
                      autogrow
                      :maxlength="1000"
                    />
                    <div class="character-count">
                      {{ textMessage.length }}/1000 caractères
                    </div>
                  </div>

                  <div class="action-buttons">
                    <q-btn
                      outline
                      color="grey-7"
                      class="action-btn secondary-btn"
                      icon="dashboard_customize"
                      label="Utiliser un template"
                      :disable="!recipient"
                      @click="selectRecipient"
                    />
                    <q-btn
                      class="action-btn primary-btn"
                      color="green"
                      icon="send"
                      label="Envoyer le message"
                      :loading="sending"
                      @click="sendTextMessage"
                      :disable="!recipient || !textMessage"
                    />
                  </div>
                </q-form>
              </q-tab-panel>

              <!-- Panneau de message template -->
              <q-tab-panel name="template" class="panel-content">
                <div class="panel-header q-mb-md">
                  <q-icon name="dashboard_customize" color="green" class="q-mr-sm" />
                  <span class="text-h6">Message avec template</span>
                </div>

                <q-banner class="info-banner q-mb-md">
                  <template v-slot:avatar>
                    <q-icon name="info" color="blue" />
                  </template>
                  <div class="text-body2">
                    Les templates sont des formats pré-approuvés par WhatsApp Business pour envoyer des messages promotionnels ou informatifs.
                  </div>
                </q-banner>
                
                <q-form class="form-container">
                  <div class="input-group">
                    <label class="input-label">
                      <q-icon name="person" class="q-mr-xs" />
                      Destinataire
                    </label>
                    <q-input
                      v-model="recipient"
                      placeholder="Numéro de téléphone (ex: +225 XX XX XX XX)"
                      outlined
                      class="modern-input"
                      :rules="[val => !!val || 'Le numéro est requis', phoneNumberRule]"
                    >
                      <template v-slot:prepend>
                        <q-icon name="phone" color="green" />
                      </template>
                    </q-input>
                  </div>

                  <div class="row q-col-gutter-md">
                    <div class="col-12 col-md-6">
                      <div class="input-group">
                        <label class="input-label">
                          <q-icon name="inventory" class="q-mr-xs" />
                          Template disponible
                        </label>
                        <q-select
                          v-model="selectedTemplate"
                          :options="templateOptions"
                          outlined
                          class="modern-input"
                          emit-value
                          map-options
                          :loading="loadingTemplates"
                        >
                          <template v-slot:prepend>
                            <q-icon name="dashboard_customize" color="green" />
                          </template>
                        </q-select>
                      </div>
                    </div>
                    
                    <div class="col-12 col-md-6">
                      <div class="input-group">
                        <label class="input-label">
                          <q-icon name="language" class="q-mr-xs" />
                          Langue du template
                        </label>
                        <q-select
                          v-model="templateLanguage"
                          :options="languageOptions"
                          outlined
                          class="modern-input"
                          emit-value
                          map-options
                        >
                          <template v-slot:prepend>
                            <q-icon name="translate" color="green" />
                          </template>
                        </q-select>
                      </div>
                    </div>
                  </div>

                  <div class="input-group">
                    <label class="input-label">
                      <q-icon name="image" class="q-mr-xs" />
                      Image d'en-tête (optionnel)
                    </label>
                    <q-input
                      v-model="headerImageUrl"
                      placeholder="URL de l'image (https://...)"
                      outlined
                      class="modern-input"
                    >
                      <template v-slot:prepend>
                        <q-icon name="image" color="green" />
                      </template>
                    </q-input>
                  </div>

                  <div class="parameters-section">
                    <label class="section-label">
                      <q-icon name="tune" class="q-mr-xs" />
                      Paramètres du template
                    </label>
                    <div class="row q-col-gutter-md">
                      <div class="col-12 col-md-4">
                        <q-input
                          v-model="param1"
                          label="Paramètre 1"
                          outlined
                          class="modern-input"
                          placeholder="Variable 1"
                        />
                      </div>
                      <div class="col-12 col-md-4">
                        <q-input
                          v-model="param2"
                          label="Paramètre 2"
                          outlined
                          class="modern-input"
                          placeholder="Variable 2"
                        />
                      </div>
                      <div class="col-12 col-md-4">
                        <q-input
                          v-model="param3"
                          label="Paramètre 3"
                          outlined
                          class="modern-input"
                          placeholder="Variable 3"
                        />
                      </div>
                    </div>
                  </div>

                  <div class="action-buttons single-action">
                    <q-btn
                      class="action-btn primary-btn"
                      color="green"
                      icon="send"
                      label="Envoyer le template"
                      :loading="sending"
                      @click="sendTemplateMessage"
                      :disable="!recipient || !selectedTemplate || !templateLanguage"
                    />
                  </div>
                </q-form>
              </q-tab-panel>
            </q-tab-panels>
          </div>
        </div>
      </q-card-section>
    </q-card>

    <!-- Prévisualisation du message (pourrait être implémentée ultérieurement) -->
    <!-- 
    <q-card flat bordered class="q-mt-md">
      <q-card-section>
        <div class="text-h6">Prévisualisation</div>
      </q-card-section>
      <q-card-section>
        Affichage de la prévisualisation du message ici
      </q-card-section>
    </q-card>
    -->

    <!-- Historique des messages récents (pourrait être implémenté ultérieurement) -->
    <!-- 
    <q-card flat bordered class="q-mt-md">
      <q-card-section>
        <div class="text-h6">Messages récents</div>
      </q-card-section>
      <q-card-section>
        Liste des messages récents ici
      </q-card-section>
    </q-card>
    -->
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, defineEmits } from 'vue';
import { useQuasar } from 'quasar';
import { useWhatsAppStore } from '@/stores/whatsappStore';

const $q = useQuasar();
const whatsAppStore = useWhatsAppStore();

// Définir les événements émis par le composant
const emit = defineEmits(['message-sent', 'recipient-selected']);

// Références
const textForm = ref(null);

// État local
const messageType = ref('text');
const recipient = ref('');
const textMessage = ref('');

// État pour les templates
const selectedTemplate = ref('hello_world');
const templateLanguage = ref('fr');
const headerImageUrl = ref('');
const param1 = ref('');
const param2 = ref('');
const param3 = ref('');

// État de chargement
const sending = ref(false);
const loadingTemplates = ref(false);

// Computed properties for dynamic values
const templateOptions = computed(() => {
  return whatsAppStore.userTemplates.map(template => ({
    label: template.name,
    value: template.template_id
  }));
});

// Options pour les langues
const languageOptions = [
  { label: 'Français', value: 'fr' },
  { label: 'Anglais', value: 'en_US' }
];

// Load templates on mount
onMounted(async () => {
  loadingTemplates.value = true;
  try {
    await whatsAppStore.loadUserTemplates();
  } catch (error) {
    console.error('Error loading templates:', error);
  } finally {
    loadingTemplates.value = false;
  }
});

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

// Fonction pour sélectionner le destinataire et passer à la sélection de template
function selectRecipient() {
  if (!recipient.value) {
    $q.notify({
      type: 'warning',
      message: 'Veuillez entrer un numéro de téléphone valide'
    });
    return;
  }
  
  const normalizedRecipient = normalizePhoneNumber(recipient.value);
  
  // Formater le numéro pour l'afficher avec le format international
  let formattedNumber = normalizedRecipient;
  if (!formattedNumber.startsWith('+')) {
    formattedNumber = '+' + formattedNumber;
  }
  
  // Emettre l'événement pour indiquer que le destinataire a été sélectionné
  emit('recipient-selected', { 
    phoneNumber: formattedNumber,
    original: recipient.value
  });
}

// Actions pour envoyer les messages
async function sendTextMessage() {
  if (!recipient.value || !textMessage.value) {
    return;
  }
  
  sending.value = true;
  
  try {
    const normalizedRecipient = normalizePhoneNumber(recipient.value);
    
    await whatsAppStore.sendMessage({
      recipient: normalizedRecipient,
      type: 'text',
      content: textMessage.value
    });
    
    $q.notify({
      type: 'positive',
      message: 'Message envoyé avec succès'
    });
    
    // Emettre l'événement pour indiquer que le message a été envoyé
    emit('message-sent', {
      phoneNumber: normalizedRecipient,
      type: 'text',
      content: textMessage.value
    });
    
    // Réinitialiser les champs après envoi réussi
    textMessage.value = '';
    recipient.value = '';
    
    // Réinitialiser la validation du formulaire
    if (textForm.value) {
      textForm.value.resetValidation();
    }
    
    // Recharger les messages pour voir le nouveau message
    await whatsAppStore.fetchMessages();
  } catch (error: any) {
    $q.notify({
      type: 'negative',
      message: `Erreur lors de l'envoi: ${error.message || 'Erreur inconnue'}`
    });
  } finally {
    sending.value = false;
  }
}

async function sendTemplateMessage() {
  if (!recipient.value || !selectedTemplate.value || !templateLanguage.value) {
    return;
  }
  
  sending.value = true;
  
  try {
    const normalizedRecipient = normalizePhoneNumber(recipient.value);
    
    // Construire les composants du template
    const components: any[] = [];
    
    // Ajouter le header si une image est fournie
    if (headerImageUrl.value) {
      components.push({
        type: 'header',
        parameters: [{
          type: 'image',
          image: {
            link: headerImageUrl.value
          }
        }]
      });
    }
    
    // Ajouter le body avec les paramètres
    const bodyParams = [];
    if (param1.value) bodyParams.push({ type: 'text', text: param1.value });
    if (param2.value) bodyParams.push({ type: 'text', text: param2.value });
    if (param3.value) bodyParams.push({ type: 'text', text: param3.value });
    
    if (bodyParams.length > 0) {
      components.push({
        type: 'body',
        parameters: bodyParams
      });
    }
    
    await whatsAppStore.sendTemplate({
      recipient: normalizedRecipient,
      templateName: selectedTemplate.value,
      languageCode: templateLanguage.value,
      components: components.length > 0 ? components : undefined
    });
    
    $q.notify({
      type: 'positive',
      message: 'Template envoyé avec succès'
    });
    
    // Emettre l'événement pour indiquer que le message a été envoyé
    emit('message-sent', {
      phoneNumber: normalizedRecipient,
      type: 'template',
      templateName: selectedTemplate.value,
      languageCode: templateLanguage.value
    });
    
    // Réinitialiser tous les champs après envoi réussi
    recipient.value = '';
    param1.value = '';
    param2.value = '';
    param3.value = '';
    
    // Recharger les messages pour voir le nouveau message
    await whatsAppStore.fetchMessages();
  } catch (error: any) {
    $q.notify({
      type: 'negative',
      message: `Erreur lors de l'envoi: ${error.message || 'Erreur inconnue'}`
    });
  } finally {
    sending.value = false;
  }
}
</script>

<style lang="scss" scoped>
.whatsapp-send-message {
  max-width: 900px;
  margin: 0 auto;
  padding: 16px;
}

// Modern card styling
.modern-card {
  background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
  border-radius: 16px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(229, 231, 235, 0.8);
  overflow: hidden;
  transition: all 0.3s ease;

  &:hover {
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
  }
}

// Card header
.card-header {
  background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
  color: white;
  padding: 24px;
  border-bottom: none;

  .text-h5 {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }

  .text-caption {
    margin: 8px 0 0 0;
    opacity: 0.9;
    font-size: 0.95rem;
  }
}

// Modern tabs
.modern-tabs {
  background: #f8fafc;
  border-bottom: 1px solid #e5e7eb;
  padding: 0 24px;

  :deep(.q-tab) {
    padding: 16px 24px;
    font-weight: 500;
    color: #6b7280;
    transition: all 0.2s ease;
    border-radius: 8px 8px 0 0;
    margin-right: 4px;
    min-height: auto;

    &.q-tab--active {
      background: white;
      color: #25d366;
      border-bottom: 2px solid #25d366;
    }

    &:hover:not(.q-tab--active) {
      background: #f1f5f9;
      color: #475569;
    }
  }
}

.tab-content {
  display: flex;
  align-items: center;
  gap: 8px;
}

.tab-label {
  font-weight: 600;
  font-size: 0.95rem;
}

.tab-caption {
  font-size: 0.8rem;
  opacity: 0.7;
  margin-top: 2px;
}

// Panel content
.modern-panels {
  background: white;

  :deep(.q-tab-panel) {
    padding: 32px 24px;
  }
}

.panel-content {
  max-width: 800px;
  margin: 0 auto;
}

.panel-header {
  display: flex;
  align-items: center;
  margin-bottom: 24px;
  
  .text-h6 {
    font-weight: 600;
    color: #374151;
    margin: 0;
  }
}

// Info banner
.info-banner {
  background: linear-gradient(135deg, #e0f2fe 0%, #f0f9ff 100%);
  border: 1px solid #25d366;
  border-radius: 12px;
  padding: 16px 20px;
  margin-bottom: 24px;
  position: relative;
  overflow: hidden;

  &::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: #25d366;
  }

  :deep(.q-icon) {
    color: #25d366;
  }
}

// Form styling
.form-container {
  .input-group {
    margin-bottom: 24px;

    .input-label {
      display: flex;
      align-items: center;
      font-weight: 600;
      color: #374151;
      margin-bottom: 8px;
      font-size: 0.95rem;
    }
  }
}

.modern-input {
  :deep(.q-field__control) {
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    border: 1px solid #e5e7eb;
    transition: all 0.2s ease;

    &:hover {
      border-color: #d1d5db;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    &:focus-within {
      border-color: #25d366;
      box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.1);
    }
  }

  :deep(.q-field__native) {
    padding: 12px 16px;
  }
}

.message-input {
  :deep(.q-field__native) {
    min-height: 80px;
    resize: vertical;
  }
}

.character-count {
  font-size: 0.8rem;
  color: #6b7280;
  text-align: right;
  margin-top: 4px;
}

// Parameters section
.parameters-section {
  background: #f8fafc;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 24px;
  border: 1px solid #f0f0f0;

  .section-label {
    display: flex;
    align-items: center;
    font-weight: 600;
    color: #374151;
    margin-bottom: 16px;
    font-size: 1.1rem;
  }
}

// Action buttons
.action-buttons {
  display: flex;
  gap: 16px;
  justify-content: flex-end;
  margin-top: 32px;
  padding-top: 24px;
  border-top: 1px solid #f3f4f6;

  &.single-action {
    justify-content: center;
  }

  .action-btn {
    border-radius: 12px;
    font-weight: 600;
    padding: 12px 24px;
    text-transform: none;
    transition: all 0.2s ease;
    min-width: 160px;

    &.primary-btn {
      background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
      box-shadow: 0 4px 16px rgba(37, 211, 102, 0.3);

      &:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(37, 211, 102, 0.4);
      }
    }

    &.secondary-btn {
      border: 2px solid #e5e7eb;
      color: #6b7280;

      &:hover {
        border-color: #d1d5db;
        background: #f9fafb;
      }
    }
  }
}

// Responsive design
@media (max-width: 768px) {
  .whatsapp-send-message {
    padding: 8px;
  }

  .card-header {
    padding: 20px 16px;
  }

  .modern-panels :deep(.q-tab-panel) {
    padding: 24px 16px;
  }

  .modern-tabs {
    padding: 0 16px;

    :deep(.q-tab) {
      padding: 12px 16px;
      font-size: 0.9rem;
    }
  }

  .tab-content {
    flex-direction: column;
    gap: 4px;
    align-items: flex-start;
  }

  .action-buttons {
    flex-direction: column;

    .action-btn {
      width: 100%;
      min-width: auto;
    }
  }

  .parameters-section {
    padding: 16px;
  }
}

@media (max-width: 480px) {
  .card-header {
    padding: 16px 12px;

    .text-h5 {
      font-size: 1.3rem;
    }
  }

  .modern-panels :deep(.q-tab-panel) {
    padding: 20px 12px;
  }

  .modern-tabs {
    padding: 0 12px;
  }
}
</style>