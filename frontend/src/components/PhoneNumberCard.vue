<template>
  <q-card class="phone-card">
    <q-card-section>
      <div class="row justify-between items-center">
        <div class="text-h6">{{ phoneNumber.number }}</div>
        <contact-count-badge 
          v-if="phoneNumber.contactCount !== undefined"
          :count="phoneNumber.contactCount" 
          color="primary" 
          icon="contacts"
          :tooltip-text="`${phoneNumber.contactCount} contact${phoneNumber.contactCount !== 1 ? 's' : ''} associé${phoneNumber.contactCount !== 1 ? 's' : ''}`"
          :compact="$q.screen.lt.md"
        />
      </div>
      <div class="text-subtitle2" v-if="hasContactInfo">
        {{ formatContactInfo }}
      </div>
      <div class="text-caption">Ajouté le {{ formattedDate }}</div>
    </q-card-section>

    <q-card-section>
      <div class="text-subtitle2 q-mb-sm">Segments</div>
      <q-list bordered separator>
        <q-item v-for="segment in phoneNumber.segments" :key="segment.id">
          <q-item-section>
            <q-item-label>{{ segment.type }}</q-item-label>
            <q-item-label caption>{{ segment.value }}</q-item-label>
          </q-item-section>
        </q-item>
        <q-item v-if="phoneNumber.segments.length === 0">
          <q-item-section>
            <q-item-label>Aucun segment trouvé</q-item-label>
          </q-item-section>
        </q-item>
      </q-list>
    </q-card-section>

    <q-card-actions align="right">
      <slot name="actions"></slot>
    </q-card-actions>
  </q-card>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { PhoneNumber } from "../stores/phoneStore";
import ContactCountBadge from "./common/ContactCountBadge.vue";
import { useQuasar } from "quasar";

const $q = useQuasar();

const props = defineProps<{
  phoneNumber: PhoneNumber;
}>();

const formattedDate = computed(() => {
  if (!props.phoneNumber.createdAt) return "";

  const date = new Date(props.phoneNumber.createdAt);
  return date.toLocaleDateString("fr-FR", {
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
});

// Vérifier si des informations de contact sont disponibles
const hasContactInfo = computed(() => {
  return !!(
    props.phoneNumber.civility ||
    props.phoneNumber.firstName ||
    props.phoneNumber.name ||
    props.phoneNumber.company
  );
});

// Formater les informations de contact
const formatContactInfo = computed(() => {
  const parts = [];

  if (props.phoneNumber.civility) {
    parts.push(props.phoneNumber.civility);
  }

  if (props.phoneNumber.firstName) {
    parts.push(props.phoneNumber.firstName);
  }

  if (props.phoneNumber.name) {
    parts.push(props.phoneNumber.name);
  }

  let result = parts.join(" ");

  if (props.phoneNumber.company) {
    if (result) {
      result += ` - ${props.phoneNumber.company}`;
    } else {
      result = props.phoneNumber.company;
    }
  }

  return result;
});
</script>

<style scoped>
.phone-card {
  width: 100%;
  margin-bottom: 16px;
}
</style>
