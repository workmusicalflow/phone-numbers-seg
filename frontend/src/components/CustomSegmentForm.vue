<template>
  <q-form @submit="onSubmit" class="q-gutter-md">
    <q-input
      v-model="form.name"
      label="Nom du segment"
      :rules="[(val) => !!val || 'Le nom est requis']"
    />

    <q-input
      v-model="form.pattern"
      label="Motif (regex)"
      :rules="[(val) => !!val || 'Le motif est requis']"
    />

    <q-input
      v-model="form.description"
      type="textarea"
      label="Description"
      rows="3"
    />

    <div>
      <q-btn
        :label="submitLabel"
        type="submit"
        color="primary"
        :loading="loading"
      />
      <q-btn
        v-if="showCancel"
        label="Annuler"
        flat
        color="grey"
        class="q-ml-sm"
        @click="$emit('cancel')"
      />
    </div>
  </q-form>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { CustomSegment } from "../stores/segmentStore";

const props = defineProps<{
  segment?: CustomSegment;
  loading?: boolean;
  submitLabel?: string;
  showCancel?: boolean;
}>();

const emit = defineEmits<{
  (
    e: "submit",
    segment: { name: string; pattern: string; description: string },
  ): void;
  (e: "cancel"): void;
}>();

const form = ref({
  name: "",
  pattern: "",
  description: "",
});

onMounted(() => {
  if (props.segment) {
    form.value.name = props.segment.name;
    form.value.pattern = props.segment.pattern;
    form.value.description = props.segment.description;
  }
});

const onSubmit = () => {
  emit("submit", {
    name: form.value.name,
    pattern: form.value.pattern,
    description: form.value.description,
  });
};
</script>
