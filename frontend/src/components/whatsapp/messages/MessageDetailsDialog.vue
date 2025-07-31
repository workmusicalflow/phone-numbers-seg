<template>
  <q-dialog :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)">
    <q-card style="min-width: 500px; max-width: 800px">
      <q-card-section>
        <div class="text-h6">Détails du message</div>
      </q-card-section>

      <q-card-section v-if="message">
        <q-list>
          <q-item>
            <q-item-section>
              <q-item-label overline>ID WhatsApp</q-item-label>
              <q-item-label>{{ message.wabaMessageId || 'N/A' }}</q-item-label>
            </q-item-section>
          </q-item>
          <q-item>
            <q-item-section>
              <q-item-label overline>Numéro de téléphone</q-item-label>
              <q-item-label>{{ message.phoneNumber }}</q-item-label>
            </q-item-section>
          </q-item>
          <q-item>
            <q-item-section>
              <q-item-label overline>Direction</q-item-label>
              <q-item-label>{{ message.direction }}</q-item-label>
            </q-item-section>
          </q-item>
          <q-item>
            <q-item-section>
              <q-item-label overline>Type</q-item-label>
              <q-item-label>{{ message.type }}</q-item-label>
            </q-item-section>
          </q-item>
          <q-item>
            <q-item-section>
              <q-item-label overline>Statut</q-item-label>
              <q-item-label>{{ message.status }}</q-item-label>
            </q-item-section>
          </q-item>
          <q-item v-if="message.content">
            <q-item-section>
              <q-item-label overline>Contenu</q-item-label>
              <q-item-label class="pre-wrap">{{ message.content }}</q-item-label>
            </q-item-section>
          </q-item>
          <q-item>
            <q-item-section>
              <q-item-label overline>Date d'envoi</q-item-label>
              <q-item-label>{{ formatFullDate(message.timestamp) }}</q-item-label>
            </q-item-section>
          </q-item>
          <q-item>
            <q-item-section>
              <q-item-label overline>Date de création</q-item-label>
              <q-item-label>{{ formatFullDate(message.createdAt) }}</q-item-label>
            </q-item-section>
          </q-item>
          <q-item v-if="message.errorCode">
            <q-item-section>
              <q-item-label overline class="text-negative">Code d'erreur</q-item-label>
              <q-item-label>{{ message.errorCode }}</q-item-label>
            </q-item-section>
          </q-item>
          <q-item v-if="message.errorMessage">
            <q-item-section>
              <q-item-label overline class="text-negative">Message d'erreur</q-item-label>
              <q-item-label>{{ message.errorMessage }}</q-item-label>
            </q-item-section>
          </q-item>
          <q-item v-if="message.conversationId">
            <q-item-section>
              <q-item-label overline>ID de conversation</q-item-label>
              <q-item-label>{{ message.conversationId }}</q-item-label>
            </q-item-section>
          </q-item>
        </q-list>
      </q-card-section>

      <q-card-actions align="right">
        <q-btn flat label="Fermer" color="primary" v-close-popup />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup lang="ts">
import type { WhatsAppMessageHistory } from '../../../stores/whatsappStore';
import { formatFullDate } from './utils/messageFormatters';

interface Props {
  modelValue: boolean;
  message: WhatsAppMessageHistory | null;
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void;
}

defineProps<Props>();
defineEmits<Emits>();
</script>

<style lang="scss" scoped>
.pre-wrap {
  white-space: pre-wrap;
  word-break: break-word;
}
</style>