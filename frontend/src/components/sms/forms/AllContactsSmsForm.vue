<template>
  <q-card>
    <q-card-section>
      <div class="text-h6">Envoyer un SMS à tous vos contacts</div>
    </q-card-section>

    <q-card-section>
      <q-form @submit.prevent="onSubmit" ref="formRef" class="q-gutter-md">
         <q-input
          v-model="message"
          type="textarea"
          label="Message"
          :rules="[val => !!val || 'Le message est requis']"
          rows="5"
          outlined
        />

        <div>
          <q-btn
            label="Envoyer à Tous les Contacts"
            type="submit"
            color="primary"
            :loading="loading"
            :disable="hasInsufficientCredits" 
            icon="send"
          />
           <div v-if="hasInsufficientCredits" class="text-negative q-mt-sm">
            <q-icon name="warning" /> Crédits SMS insuffisants (vérification basique, le nombre exact de contacts sera vérifié à l'envoi)
          </div>
          <q-banner inline-actions rounded class="bg-orange text-white q-mt-md">
            <q-icon name="warning" color="white" class="q-mr-sm" />
            Attention : L'envoi à tous les contacts peut consommer un nombre important de crédits. Le nombre exact sera vérifié avant l'envoi final.
          </q-banner>
        </div>
      </q-form>
    </q-card-section>
  </q-card>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import type { QForm } from 'quasar';

// Define Props
const props = defineProps<{
  loading: boolean;
  hasInsufficientCredits: boolean;
}>();

// Define Emits
const emit = defineEmits<{
  (e: 'submit-all-contacts', payload: { message: string }): void;
}>();

// Form Data
const message = ref("");
const formRef = ref<QForm | null>(null); // Ref for the form

// Submit Handler
const onSubmit = () => {
  emit('submit-all-contacts', { message: message.value });
};

// Function to reset the form
const reset = () => {
    message.value = "";
    formRef.value?.resetValidation(); // Reset Quasar form validation
};

// Expose the reset function
defineExpose({ reset });
</script>
