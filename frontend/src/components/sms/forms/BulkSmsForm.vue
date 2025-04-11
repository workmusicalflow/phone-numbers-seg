<template>
  <q-card>
    <q-card-section>
      <div class="text-h6">Envoyer un SMS à plusieurs numéros</div>
    </q-card-section>

    <q-card-section>
      <q-form @submit.prevent="onSubmit" ref="formRef" class="q-gutter-md">
        <q-input
          v-model="bulkSmsData.phoneNumbers"
          type="textarea"
          label="Numéros de téléphone (séparés par des virgules, espaces ou sauts de ligne)"
          :rules="[val => !!val || 'Les numéros sont requis']"
          rows="5"
          hint="Exemple: +2250777104936, +2250141399354, +2250546560953"
          outlined
        />

        <q-input
          v-model="bulkSmsData.message"
          type="textarea"
          label="Message"
          :rules="[val => !!val || 'Le message est requis']"
          rows="5"
          outlined
        />

        <div>
          <q-btn
            label="Envoyer SMS en masse"
            type="submit"
            color="primary"
            :loading="loading"
            :disable="hasInsufficientCredits"
          />
          <div v-if="hasInsufficientCredits" class="text-negative q-mt-sm">
            <q-icon name="warning" /> Crédits SMS insuffisants
          </div>
        </div>
      </q-form>
    </q-card-section>
  </q-card>
</template>

<script setup lang="ts">
import { ref, nextTick } from 'vue';
import { useQuasar } from 'quasar';
import type { QForm } from 'quasar';

// Define Props
const props = defineProps<{
  loading: boolean;
  hasInsufficientCredits: boolean;
}>();

// Define Emits
const emit = defineEmits<{
  (e: 'submit-bulk', payload: { phoneNumbers: string[]; message: string }): void;
}>();

const $q = useQuasar();

// Form Data
const bulkSmsData = ref({
  phoneNumbers: "", // Keep as raw string input
  message: "",
});
const formRef = ref<QForm | null>(null);

// Submit Handler
const onSubmit = () => {
  // Process phone numbers before emitting
  const phoneNumbers = bulkSmsData.value.phoneNumbers
    .split(/[\s,;]+/) // Split by space, comma, or semicolon
    .map((num) => num.trim())
    .filter((num) => num.length > 0); // Remove empty strings

  if (phoneNumbers.length === 0) {
    $q.notify({ type: 'warning', message: "Aucun numéro valide trouvé dans la liste." });
    return;
  }

  emit('submit-bulk', {
    phoneNumbers,
    message: bulkSmsData.value.message
  });
};

// Function to reset the form
const reset = async () => {
    console.log('Reset called on BulkSmsForm');
    
    // 1. Réinitialiser les données
    bulkSmsData.value = { phoneNumbers: "", message: "" };
    
    // 2. Attendre le prochain cycle de rendu
    await nextTick();
    
    // 3. Réinitialiser la validation
    if (formRef.value) {
        formRef.value.resetValidation();
        console.log('Validation reset completed for BulkSmsForm');
    } else {
        console.warn('formRef not available during reset in BulkSmsForm');
    }
};

// Expose the reset function
defineExpose({ reset });
</script>
