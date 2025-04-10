<template>
  <q-card>
    <q-card-section>
      <div class="text-h6">Envoyer un SMS à un numéro</div>
    </q-card-section>

    <q-card-section>
      <q-form @submit.prevent="onSubmit" class="q-gutter-md">
        <q-input
          v-model="singleSmsData.phoneNumber"
          label="Numéro de téléphone"
          :rules="[val => !!val || 'Le numéro est requis']"
          outlined
          dense
        />

        <div class="row q-col-gutter-sm">
          <div class="col-12">
            <!-- Consider extracting TemplateSelector to its own component later -->
            <q-select
              v-model="selectedTemplateId"
              :options="smsTemplateStore.templates"
              option-value="id"
              option-label="title"
              label="Modèle de SMS (optionnel)"
              clearable
              emit-value
              map-options
              outlined
              dense
              @update:model-value="onTemplateSelected"
            >
              <template v-slot:no-option>
                <q-item>
                  <q-item-section class="text-grey">
                    Aucun modèle disponible
                  </q-item-section>
                </q-item>
              </template>
            </q-select>
          </div>

          <div class="col-12">
            <q-input
              v-model="singleSmsData.message"
              type="textarea"
              label="Message"
              :rules="[val => !!val || 'Le message est requis']"
              rows="5"
              outlined
            />
          </div>

          <!-- Champs pour les variables du modèle -->
          <template v-if="templateVariables.length > 0">
            <div class="col-12 q-my-sm">
              <div class="text-subtitle2">Variables du modèle:</div>
            </div>
            <div class="col-12 col-md-6" v-for="variable in templateVariables" :key="variable">
              <q-input
                v-model="templateVariableValues[variable]"
                :label="variable"
                outlined
                dense
                @update:model-value="applyTemplateVariables"
              />
            </div>
          </template>
        </div>

        <div>
          <q-btn
            label="Envoyer SMS"
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
import { ref, computed, watch } from 'vue';
import { useSMSTemplateStore } from '@/stores/smsTemplateStore'; // Adjust path as needed

// Define Interfaces (Matching store definition)
interface SmsTemplate {
  id: string;
  userId: number; // Added
  title: string;
  content: string;
  description: string | null; // Added
  createdAt: string; // Added
  updatedAt: string; // Added
  variables: string[]; // Made non-optional based on store type
}

// Define Props
const props = defineProps<{
  loading: boolean;
  hasInsufficientCredits: boolean;
}>();

// Define Emits
const emit = defineEmits<{
  (e: 'submit-single', payload: { phoneNumber: string; message: string }): void;
  (e: 'reset-form'): void; // Optional: Emit event to signal parent to reset if needed
}>();

// Store
const smsTemplateStore = useSMSTemplateStore();

// Form Data
const singleSmsData = ref({
  phoneNumber: "",
  message: "",
});

// Template Logic
const selectedTemplateId = ref<string | null>(null);
const selectedTemplate = ref<SmsTemplate | null>(null);
const templateVariableValues = ref<Record<string, string>>({});

const templateVariables = computed(() => {
  // Use optional chaining and nullish coalescing for safety
  return selectedTemplate.value?.variables ?? [];
});

function onTemplateSelected(templateId: string | null) {
  selectedTemplate.value = templateId ? smsTemplateStore.templates.find(t => t.id === templateId) ?? null : null;

  if (selectedTemplate.value) {
    templateVariableValues.value = {};
    singleSmsData.value.message = selectedTemplate.value.content;
    // Check if variables array exists and has items
    if (selectedTemplate.value.variables && selectedTemplate.value.variables.length > 0) {
      selectedTemplate.value.variables.forEach((variable: string) => {
        templateVariableValues.value[variable] = ''; // Initialize
      });
    } else {
      // Ensure templateVariableValues is empty if no variables
      templateVariableValues.value = {};
    }
  } else {
    // Keep existing message if template is deselected
    templateVariableValues.value = {};
  }
}

function applyTemplateVariables() {
  if (!selectedTemplate.value) return;
  // Pass the full template object, assuming the store method expects it
  singleSmsData.value.message = smsTemplateStore.applyTemplate(
    selectedTemplate.value, 
    templateVariableValues.value
  );
}

// Submit Handler
const onSubmit = () => {
  emit('submit-single', { ...singleSmsData.value });
};

// Function to reset the form (can be called by parent if needed)
const reset = () => {
    singleSmsData.value = { phoneNumber: "", message: "" };
    selectedTemplateId.value = null;
    selectedTemplate.value = null;
    templateVariableValues.value = {};
    // Reset Quasar form validation if needed (requires ref on q-form)
};

// Expose the reset function if parent needs to call it
defineExpose({ reset });

// Watch for successful submission (indicated by loading prop changing from true to false)
// This is a bit indirect, relying on parent's behavior. Emitting 'reset-form' might be cleaner.
// watch(() => props.loading, (newLoading, oldLoading) => {
//   if (oldLoading === true && newLoading === false) {
//     // Check if the last operation was successful (might need more info from parent)
//     // If successful, call reset()
//   }
// });

</script>
