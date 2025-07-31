<template>
  <div class="whatsapp-send-message">
    <q-card class="modern-card">
      <q-card-section class="card-header">
        <div class="row items-center">
          <q-icon name="chat" size="md" color="green" class="q-mr-md" />
          <div>
            <div class="text-h5 text-weight-medium">Envoyer un message WhatsApp</div>
            <div class="text-caption text-grey-9">Choisissez le type de message à envoyer</div>
          </div>
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section class="content-section">
        <div class="panel-header q-mb-lg">
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

          <!-- Bouton d'action vers les templates -->
          <div class="template-redirect-section q-mb-lg">
            <q-banner class="template-banner">
              <template v-slot:avatar>
                <q-icon name="dashboard_customize" color="purple" />
              </template>
              <div class="text-body2">
                <strong>Besoin d'un message plus élaboré ?</strong><br>
                Utilisez nos templates préformatés pour des messages professionnels.
              </div>
              <template v-slot:action>
                <q-btn 
                  flat 
                  color="purple" 
                  label="Voir les templates" 
                  icon="arrow_forward"
                  @click="goToTemplates"
                />
              </template>
            </q-banner>
          </div>

          <div class="action-buttons">
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
import { ref, defineEmits } from 'vue';
import { useQuasar } from 'quasar';
import { useRouter } from 'vue-router';
import { useWhatsAppStore } from '@/stores/whatsappStore';

const $q = useQuasar();
const router = useRouter();
const whatsAppStore = useWhatsAppStore();

// Définir les événements émis par le composant
const emit = defineEmits(['message-sent']);

// Références
const textForm = ref(null);

// État local
const recipient = ref('');
const textMessage = ref('');

// État de chargement
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

// Fonction pour rediriger vers la page des templates
function goToTemplates() {
  router.push('/whatsapp-templates');
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

// Content section
.content-section {
  padding: 32px 24px;
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

// Template banner
.template-banner {
  background: linear-gradient(135deg, #f3e8ff 0%, #faf5ff 100%);
  border: 1px solid #a855f7;
  border-radius: 12px;
  position: relative;
  overflow: hidden;

  &::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: #a855f7;
  }

  :deep(.q-icon) {
    color: #a855f7;
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


// Action buttons
.action-buttons {
  display: flex;
  gap: 16px;
  justify-content: center;
  margin-top: 32px;
  padding-top: 24px;
  border-top: 1px solid #f3f4f6;

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

  .content-section {
    padding: 24px 16px;
  }

  .action-buttons {
    flex-direction: column;

    .action-btn {
      width: 100%;
      min-width: auto;
    }
  }
}

@media (max-width: 480px) {
  .card-header {
    padding: 16px 12px;

    .text-h5 {
      font-size: 1.3rem;
    }
  }

  .content-section {
    padding: 20px 12px;
  }
}
</style>