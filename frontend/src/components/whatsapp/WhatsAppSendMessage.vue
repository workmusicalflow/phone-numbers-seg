<template>
  <div class="whatsapp-send-message">
    <q-card flat bordered>
      <q-card-section>
        <div class="text-h6">Envoyer un message WhatsApp</div>
      </q-card-section>

      <q-separator />

      <q-card-section>
        <div class="row q-col-gutter-md">
          <div class="col-12">
            <q-tabs
              v-model="messageType"
              dense
              class="text-grey"
              active-color="primary"
              indicator-color="primary"
              align="justify"
              narrow-indicator
            >
              <q-tab name="text" label="Message texte" />
              <q-tab name="template" label="Message template" />
            </q-tabs>

            <q-separator />

            <q-tab-panels v-model="messageType" animated>
              <!-- Panneau de message texte -->
              <q-tab-panel name="text">
                <q-form ref="textForm">
                <q-input
                  v-model="recipient"
                  label="Numéro de téléphone du destinataire"
                  outlined
                  :rules="[val => !!val || 'Le numéro est requis', phoneNumberRule]"
                >
                  <template v-slot:prepend>
                    <q-icon name="phone" />
                  </template>
                </q-input>

                <q-input
                  v-model="textMessage"
                  label="Message"
                  type="textarea"
                  outlined
                  class="q-mt-md"
                  :rules="[val => !!val || 'Le message est requis']"
                  autogrow
                />

                <div class="text-caption text-grey q-mt-sm">
                  {{ textMessage.length }}/1000 caractères
                </div>

                <div class="row justify-between q-mt-md">
                  <q-btn
                    outline
                    color="secondary"
                    label="Continuer avec les templates"
                    icon-right="arrow_forward"
                    :disable="!recipient"
                    @click="selectRecipient"
                  />
                  <q-btn
                    label="Envoyer message texte"
                    color="primary"
                    :loading="sending"
                    @click="sendTextMessage"
                    :disable="!recipient || !textMessage"
                  />
                </div>
                </q-form>
              </q-tab-panel>

              <!-- Panneau de message template -->
              <q-tab-panel name="template">
                <q-input
                  v-model="recipient"
                  label="Numéro de téléphone du destinataire"
                  outlined
                  :rules="[val => !!val || 'Le numéro est requis', phoneNumberRule]"
                >
                  <template v-slot:prepend>
                    <q-icon name="phone" />
                  </template>
                </q-input>

                <q-select
                  v-model="selectedTemplate"
                  :options="templateOptions"
                  label="Template"
                  outlined
                  class="q-mt-md"
                  emit-value
                  map-options
                />

                <q-select
                  v-model="templateLanguage"
                  :options="languageOptions"
                  label="Langue"
                  outlined
                  class="q-mt-md"
                  emit-value
                  map-options
                />

                <q-input
                  v-model="headerImageUrl"
                  label="URL de l'image d'en-tête (optionnel)"
                  outlined
                  class="q-mt-md"
                >
                  <template v-slot:prepend>
                    <q-icon name="image" />
                  </template>
                </q-input>

                <div class="row q-col-gutter-md q-mt-md">
                  <div class="col-12 col-md-4">
                    <q-input
                      v-model="param1"
                      label="Paramètre 1"
                      outlined
                    />
                  </div>
                  <div class="col-12 col-md-4">
                    <q-input
                      v-model="param2"
                      label="Paramètre 2"
                      outlined
                    />
                  </div>
                  <div class="col-12 col-md-4">
                    <q-input
                      v-model="param3"
                      label="Paramètre 3"
                      outlined
                    />
                  </div>
                </div>

                <div class="row justify-end q-mt-md">
                  <q-btn
                    label="Envoyer le template"
                    color="primary"
                    :loading="sending"
                    @click="sendTemplateMessage"
                    :disable="!recipient || !selectedTemplate || !templateLanguage"
                  />
                </div>
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
  max-width: 800px;
  margin: 0 auto;
}
</style>