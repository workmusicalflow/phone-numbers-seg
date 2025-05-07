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

                <div class="row justify-end q-mt-md">
                  <q-btn
                    label="Envoyer"
                    color="primary"
                    :loading="sending"
                    @click="sendTextMessage"
                    :disable="!recipient || !textMessage"
                  />
                </div>
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
import { ref, computed } from 'vue';
import { useQuasar } from 'quasar';
import { useWhatsAppStore, type SendTemplateInput } from '@/stores/whatsappStore';

const $q = useQuasar();
const whatsAppStore = useWhatsAppStore();

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

// Options pour les templates
// Note: Ces options devraient idéalement être récupérées depuis le backend
const templateOptions = [
  { label: 'Hello World', value: 'hello_world' },
  { label: 'QSHE Invitation', value: 'qshe_invitation1' }
];

// Options pour les langues
const languageOptions = [
  { label: 'Français', value: 'fr' },
  { label: 'Anglais', value: 'en_US' },
  { label: 'Espagnol', value: 'es' }
];

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

// Actions pour envoyer les messages
async function sendTextMessage() {
  if (!recipient.value || !textMessage.value) {
    return;
  }
  
  sending.value = true;
  
  try {
    const normalizedRecipient = normalizePhoneNumber(recipient.value);
    const response = await whatsAppStore.sendTextMessage(normalizedRecipient, textMessage.value);
    
    if (response.success) {
      $q.notify({
        type: 'positive',
        message: 'Message envoyé avec succès'
      });
      textMessage.value = ''; // Réinitialiser le message après envoi réussi
    } else {
      $q.notify({
        type: 'negative',
        message: `Erreur lors de l'envoi: ${response.error || 'Erreur inconnue'}`
      });
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erreur lors de l\'envoi du message'
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
    
    const templateInput: SendTemplateInput = {
      recipient: normalizedRecipient,
      templateName: selectedTemplate.value,
      languageCode: templateLanguage.value
    };
    
    // Ajouter les paramètres optionnels s'ils sont fournis
    if (headerImageUrl.value) {
      templateInput.headerImageUrl = headerImageUrl.value;
    }
    
    if (param1.value) {
      templateInput.body1Param = param1.value;
    }
    
    if (param2.value) {
      templateInput.body2Param = param2.value;
    }
    
    if (param3.value) {
      templateInput.body3Param = param3.value;
    }
    
    const response = await whatsAppStore.sendTemplateMessage(templateInput);
    
    if (response.success) {
      $q.notify({
        type: 'positive',
        message: 'Template envoyé avec succès'
      });
      // Réinitialiser certains champs après envoi réussi
      param1.value = '';
      param2.value = '';
      param3.value = '';
    } else {
      $q.notify({
        type: 'negative',
        message: `Erreur lors de l'envoi: ${response.error || 'Erreur inconnue'}`
      });
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erreur lors de l\'envoi du template'
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