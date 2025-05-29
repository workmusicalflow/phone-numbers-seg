<template>
  <q-dialog :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)" persistent>
    <q-card style="min-width: 450px">
      <q-card-section>
        <div class="text-h6">Répondre à {{ formatPhoneNumber(message?.phoneNumber || '') }}</div>
        <div class="text-caption text-warning q-mt-sm">
          <q-icon name="warning" />
          Vous devez répondre dans les 24h suivant le dernier message reçu
        </div>
      </q-card-section>

      <q-card-section>
        <q-input
          v-model="replyContent"
          autofocus
          outlined
          type="textarea"
          label="Message"
          :rules="[val => !!val || 'Le message est requis']"
          counter
          maxlength="1000"
        />
        <div class="text-caption text-grey-6 q-mt-sm">
          Caractères : {{ replyContent.length }} / 1000
        </div>
      </q-card-section>

      <q-card-actions align="right">
        <q-btn flat label="Annuler" color="negative" @click="handleCancel" />
        <q-btn
          unelevated
          label="Envoyer"
          color="primary"
          :loading="loading"
          :disable="!replyContent.trim()"
          @click="handleSend"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import type { WhatsAppMessageHistory } from '../../../stores/whatsappStore';
import { formatPhoneNumber } from './utils/messageFormatters';

interface Props {
  modelValue: boolean;
  message: WhatsAppMessageHistory | null;
  loading?: boolean;
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void;
  (e: 'send', content: string): void;
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
});

const emit = defineEmits<Emits>();

const replyContent = ref('');

// Reset content when dialog opens
watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    replyContent.value = '';
  }
});

function handleCancel() {
  emit('update:modelValue', false);
}

function handleSend() {
  if (replyContent.value.trim()) {
    emit('send', replyContent.value);
  }
}
</script>

<style lang="scss" scoped>
// Pas de styles spécifiques nécessaires
</style>