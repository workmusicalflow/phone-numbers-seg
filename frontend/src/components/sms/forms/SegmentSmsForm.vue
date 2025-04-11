<template>
  <q-card>
    <q-card-section>
      <div class="text-h6">Envoyer un SMS à un segment</div>
    </q-card-section>

    <q-card-section>
      <q-form @submit.prevent="onSubmit" ref="formRef" class="q-gutter-md">
        <div class="q-mb-md">
          <div class="text-subtitle2 q-mb-sm">
            Sélectionnez un segment
          </div>
          <q-list bordered separator>
            <q-item
              v-for="segment in segments"
              :key="segment.id"
              clickable
              v-ripple
              :active="segmentSmsData.segmentId === segment.id"
              @click="segmentSmsData.segmentId = segment.id"
              :disable="loadingSegments"
            >
              <q-item-section>
                <q-item-label>{{ segment.name }}</q-item-label>
                <q-item-label caption>{{
                  segment.description || "Aucune description"
                }}</q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-badge color="primary"
                  >{{ segment.phoneNumberCount }} numéros</q-badge
                >
              </q-item-section>
            </q-item>
          </q-list>
          <div v-if="!loadingSegments && segments.length === 0" class="text-center q-pa-md text-grey">
            Aucun segment disponible
          </div>
           <div v-if="loadingSegments" class="text-center q-pa-md">
             <q-spinner color="primary" size="2em" />
           </div>
        </div>

        <q-input
          v-model="segmentSmsData.message"
          type="textarea"
          label="Message"
          :rules="[val => !!val || 'Le message est requis']"
          rows="5"
          outlined
        />

        <div>
          <q-btn
            label="Envoyer SMS au segment"
            type="submit"
            color="primary"
            :loading="loading"
            :disable="!segmentSmsData.segmentId || hasInsufficientCredits"
          />
          <div v-if="hasInsufficientCredits" class="text-negative q-mt-sm">
            <q-icon name="warning" /> Crédits SMS insuffisants
          </div>
           <div v-if="!segmentSmsData.segmentId && !hasInsufficientCredits" class="text-warning q-mt-sm">
             <q-icon name="info" /> Veuillez sélectionner un segment.
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

// Define Segment type locally or import if available globally
interface Segment {
  id: number;
  name: string;
  description?: string;
  phoneNumberCount: number;
}

// Define Props
const props = defineProps<{
  loading: boolean; // Loading state for the submit button
  loadingSegments: boolean; // Loading state for the segments list
  hasInsufficientCredits: boolean;
  segments: Segment[]; // Pass segments data as prop
}>();

// Define Emits
const emit = defineEmits<{
  (e: 'submit-segment', payload: { segmentId: number; message: string }): void;
}>();

const $q = useQuasar();

// Form Data
const segmentSmsData = ref<{ segmentId: number | null; message: string }>({
  segmentId: null,
  message: "",
});
const formRef = ref<QForm | null>(null);

// Submit Handler
const onSubmit = () => {
  if (!segmentSmsData.value.segmentId) {
    $q.notify({ type: 'warning', message: "Veuillez sélectionner un segment." });
    return;
  }
  emit('submit-segment', {
    segmentId: segmentSmsData.value.segmentId,
    message: segmentSmsData.value.message
  });
};

// Function to reset the form
const reset = async () => {
    console.log('Reset called on SegmentSmsForm');
    
    // 1. Réinitialiser les données
    segmentSmsData.value = { segmentId: null, message: "" };
    
    // 2. Attendre le prochain cycle de rendu
    await nextTick();
    
    // 3. Réinitialiser la validation
    if (formRef.value) {
        formRef.value.resetValidation();
        console.log('Validation reset completed for SegmentSmsForm');
    } else {
        console.warn('formRef not available during reset in SegmentSmsForm');
    }
};

// Expose the reset function
defineExpose({ reset });
</script>
