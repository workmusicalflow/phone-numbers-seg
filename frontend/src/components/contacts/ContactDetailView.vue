<template>
  <div class="contact-detail-view">
    <!-- Contact Header Info -->
    <div class="contact-header q-mb-lg">
      <q-card class="contact-info-card">
        <q-card-section>
          <div class="row items-center q-col-gutter-md">
            <!-- Avatar -->
            <div class="col-auto">
              <q-avatar size="80px" color="primary" text-color="white">
                {{ contactInitials }}
              </q-avatar>
            </div>
            
            <!-- Basic Info -->
            <div class="col-grow">
              <div class="text-h4">{{ contact.name }}</div>
              <div class="row q-mt-sm">
                <div class="col-12 col-md-6">
                  <div class="row q-gutter-sm">
                    <q-icon name="phone" size="sm" />
                    <div class="text-subtitle1">{{ contact.phoneNumber }}</div>
                  </div>
                </div>
                <div class="col-12 col-md-6" v-if="contact.email">
                  <div class="row q-gutter-sm">
                    <q-icon name="email" size="sm" />
                    <div class="text-subtitle1">{{ contact.email }}</div>
                  </div>
                </div>
              </div>
              
              <!-- Groups -->
              <div class="q-mt-sm" v-if="contact.groups && contact.groups.length > 0">
                <div class="row items-center">
                  <q-icon name="group" size="sm" class="q-mr-sm" />
                  <div>
                    <q-chip
                      v-for="group in contact.groups"
                      :key="group.id"
                      size="sm"
                      color="secondary"
                      text-color="white"
                      class="q-ma-xs"
                    >
                      {{ group.name }}
                    </q-chip>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="col-12 col-md-auto">
              <div class="row q-gutter-sm justify-end">
                <q-btn
                  flat
                  round
                  color="primary"
                  icon="edit"
                  @click="$emit('edit', contact)"
                >
                  <q-tooltip>Modifier</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  round
                  color="secondary"
                  icon="message"
                  @click="$emit('send-sms', contact)"
                >
                  <q-tooltip>Envoyer SMS</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  round
                  color="negative"
                  icon="delete"
                  @click="$emit('delete', contact)"
                >
                  <q-tooltip>Supprimer</q-tooltip>
                </q-btn>
              </div>
            </div>
          </div>
          
          <!-- Notes -->
          <div class="q-mt-md" v-if="contact.notes">
            <q-separator class="q-my-sm" />
            <div class="text-subtitle2 q-mb-xs text-weight-medium">Notes</div>
            <div class="text-body1">{{ contact.notes }}</div>
          </div>
        </q-card-section>
      </q-card>
    </div>
    
    <!-- SMS Statistics -->
    <ContactSMSStats 
      :contact="contact"
      class="q-mb-md"
    />
    
    <!-- WhatsApp Insights -->
    <WhatsAppContactInsights
      :contact-id="contact.id"
      :auto-load="true"
      @send-whatsapp="$emit('send-whatsapp', contact)"
      @view-history="$emit('view-whatsapp-history', contact)"
      class="q-mb-md"
    />
    
    <!-- SMS History -->
    <ContactSMSHistory 
      :contact="contact" 
      :loading="loading"
      @view-details="$emit('view-sms-details', $event)"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Contact } from '../../types/contact';
import ContactSMSStats from './ContactSMSStats.vue';
import ContactSMSHistory from './ContactSMSHistory.vue';
import WhatsAppContactInsights from './WhatsAppContactInsights.vue';

const props = defineProps<{
  contact: Contact;
  loading?: boolean;
}>();

const emit = defineEmits<{
  (e: 'edit', contact: Contact): void;
  (e: 'delete', contact: Contact): void;
  (e: 'send-sms', contact: Contact): void;
  (e: 'view-sms-details', sms: any): void;
  (e: 'send-whatsapp', contact: Contact): void;
  (e: 'view-whatsapp-history', contact: Contact): void;
}>();

// Get contact initials for avatar
const contactInitials = computed(() => {
  if (!props.contact.name) return '?';
  
  // Split the name by spaces and get the first letter of each part
  return props.contact.name
    .split(' ')
    .map(part => part.charAt(0).toUpperCase())
    .slice(0, 2) // Take only first two initials
    .join('');
});
</script>

<style scoped>
.contact-detail-view {
  width: 100%;
}

.contact-info-card {
  border-left: 4px solid var(--q-primary);
}

@media (max-width: 600px) {
  .contact-info-card {
    border-left: none;
    border-top: 4px solid var(--q-primary);
  }
}
</style>