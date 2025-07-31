<template>
  <q-dialog
    v-model="dialogVisible"
    persistent
    :maximized="$q.screen.lt.sm"
    transition-show="scale"
    transition-hide="scale"
  >
    <q-card style="min-width: 350px">
      <q-card-section>
        <div class="text-h6">{{ isEditing ? 'Modifier le groupe' : 'Créer un groupe' }}</div>
      </q-card-section>

      <q-card-section>
        <q-form @submit="onSubmit" class="q-gutter-md">
          <q-input
            v-model="form.name"
            label="Nom du groupe *"
            :rules="[val => !!val || 'Le nom est requis']"
            autofocus
            ref="nameInput"
          />

          <q-input
            v-model="form.description"
            label="Description"
            type="textarea"
            hint="Description optionnelle du groupe"
          />
        </q-form>
      </q-card-section>

      <q-card-actions align="right">
        <q-btn
          flat
          label="Annuler"
          color="negative"
          v-close-popup
          @click="onCancel"
        />
        <q-btn
          flat
          :label="isEditing ? 'Enregistrer' : 'Créer'"
          color="primary"
          :loading="isSubmitting"
          @click="onSubmit"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue';
import { useQuasar } from 'quasar';
import { useContactGroupStore } from '@/stores/contactGroupStore';
import type { ContactGroup } from '@/types/contactGroup';

const $q = useQuasar();
const store = useContactGroupStore();

// Props
const props = defineProps<{
  modelValue: boolean;
  group?: ContactGroup | null; // Pass a group for editing mode
}>();

// Emits
const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
  (e: 'group-saved', group: ContactGroup): void;
}>();

// Computed
const dialogVisible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
});

const isEditing = computed(() => !!props.group);

// Form state
const form = ref({
  name: '',
  description: '',
});

const nameInput = ref<HTMLElement | null>(null);
const isSubmitting = ref(false);

// Watch for changes in the dialog visibility or group prop
watch(
  () => [props.modelValue, props.group],
  ([visible, group]) => {
    if (visible) {
      // Initialize form with group data if editing, or reset if creating
      if (group) {
        form.value.name = group.name;
        form.value.description = group.description || '';
      } else {
        form.value.name = '';
        form.value.description = '';
      }
      
      // Focus the name input after the dialog is shown
      nextTick(() => {
        if (nameInput.value) {
          // Access the native input element and focus it
          const inputEl = nameInput.value as any;
          if (inputEl.focus) {
            inputEl.focus();
          }
        }
      });
    }
  },
  { immediate: true }
);

// Methods
async function onSubmit() {
  if (!form.value.name.trim()) {
    $q.notify({
      color: 'negative',
      message: 'Le nom du groupe est requis',
      icon: 'warning',
    });
    return;
  }

  isSubmitting.value = true;
  try {
    let savedGroup: ContactGroup | null;

    if (isEditing.value && props.group) {
      // Update existing group
      savedGroup = await store.updateContactGroup({
        id: props.group.id,
        name: form.value.name.trim(),
        description: form.value.description.trim() || null,
      });
    } else {
      // Create new group
      savedGroup = await store.createContactGroup({
        name: form.value.name.trim(),
        description: form.value.description.trim() || null,
      });
    }

    if (savedGroup) {
      emit('group-saved', savedGroup);
      dialogVisible.value = false;
    }
  } catch (error) {
    console.error('Error saving contact group:', error);
  } finally {
    isSubmitting.value = false;
  }
}

function onCancel() {
  // Reset form and close dialog
  form.value.name = '';
  form.value.description = '';
  dialogVisible.value = false;
}
</script>
