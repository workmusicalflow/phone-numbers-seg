<template>
  <q-dialog v-model="dialogModel" persistent>
    <q-card>
      <q-card-section class="row items-center">
        <q-avatar :icon="icon" :color="color" text-color="white" />
        <span class="q-ml-sm">{{ message }}</span>
      </q-card-section>

      <q-card-actions align="right">
        <q-btn flat :label="cancelLabel" color="primary" @click="onCancel" />
        <q-btn
          flat
          :label="confirmLabel"
          :color="confirmColor"
          @click="onConfirm"
          :loading="loading"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  modelValue: boolean;
  message: string;
  icon?: string;
  color?: string;
  confirmLabel?: string;
  cancelLabel?: string;
  confirmColor?: string;
  loading?: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
  (e: 'confirm'): void;
  (e: 'cancel'): void;
}>();

// Valeurs par défaut pour les props optionnelles
const icon = computed(() => props.icon || 'warning');
const color = computed(() => props.color || 'negative');
const confirmLabel = computed(() => props.confirmLabel || 'Confirmer');
const cancelLabel = computed(() => props.cancelLabel || 'Annuler');
const confirmColor = computed(() => props.confirmColor || 'negative');

// Modèle pour v-model
const dialogModel = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
});

// Méthodes
const onConfirm = () => {
  emit('confirm');
};

const onCancel = () => {
  emit('cancel');
  emit('update:modelValue', false);
};
</script>
