<template>
  <q-dialog v-model="showDialog" persistent>
    <q-card>
      <q-card-section class="row items-center">
        <q-avatar icon="warning" color="warning" text-color="white" />
        <span class="q-ml-sm">{{ title }}</span>
      </q-card-section>

      <q-card-section>
        {{ message }}
      </q-card-section>

      <q-card-actions align="right">
        <q-btn
          flat
          :label="cancelLabel"
          color="grey"
          v-close-popup
          @click="onCancel"
        />
        <q-btn
          :label="confirmLabel"
          :color="confirmColor"
          v-close-popup
          @click="onConfirm"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup lang="ts">
import { ref } from "vue";

const props = withDefaults(
  defineProps<{
    title?: string;
    message: string;
    confirmLabel?: string;
    cancelLabel?: string;
    confirmColor?: string;
  }>(),
  {
    title: "Confirmation",
    confirmLabel: "Confirmer",
    cancelLabel: "Annuler",
    confirmColor: "negative",
  },
);

const emit = defineEmits<{
  (e: "confirm"): void;
  (e: "cancel"): void;
}>();

const showDialog = ref(false);

const open = () => {
  showDialog.value = true;
};

const onConfirm = () => {
  emit("confirm");
};

const onCancel = () => {
  emit("cancel");
};

// Exposer les méthodes au composant parent
defineExpose({
  open,
});
</script>
