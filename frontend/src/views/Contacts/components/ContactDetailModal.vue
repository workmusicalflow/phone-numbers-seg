<template>
  <q-dialog
    :model-value="visible"
    @update:model-value="handleVisibilityChange"
    persistent
    maximized
    transition-show="slide-up"
    transition-hide="slide-down"
  >
    <q-card class="contact-detail-modal">
      <!-- Modal Header -->
      <q-card-section class="modal-header contacts-gradient">
        <div class="header-content">
          <div class="header-info">
            <q-icon name="person" size="lg" class="header-icon" />
            <div class="header-text">
              <h2 class="contact-name">{{ contact?.name || 'Détails du Contact' }}</h2>
              <p class="contact-phone">{{ contact?.phoneNumber }}</p>
            </div>
          </div>
          <div class="header-actions">
            <q-btn
              flat
              round
              color="white"
              icon="edit"
              @click="$emit('edit-contact', contact)"
              class="action-btn"
            >
              <q-tooltip>Modifier</q-tooltip>
            </q-btn>
            <q-btn
              flat
              round
              color="white"
              icon="close"
              @click="handleClose"
              class="action-btn"
            >
              <q-tooltip>Fermer</q-tooltip>
            </q-btn>
          </div>
        </div>
      </q-card-section>

      <!-- Modal Content -->
      <q-card-section class="modal-content">
        <div v-if="loading" class="loading-wrapper">
          <q-spinner-dots size="2rem" color="primary" />
          <p class="loading-text">Chargement des détails...</p>
        </div>

        <div v-else-if="!contact" class="error-wrapper">
          <q-icon name="error" size="3rem" color="negative" />
          <p class="error-text">Contact non trouvé</p>
          <q-btn
            color="primary"
            label="Fermer"
            @click="handleClose"
            class="error-action"
          />
        </div>

        <div v-else class="contact-details">
          <!-- Quick Actions Bar -->
          <div class="quick-actions-bar">
            <q-btn
              color="primary"
              icon="message"
              label="Envoyer SMS"
              @click="$emit('send-sms', contact)"
              class="quick-action-btn"
            />
            <q-btn
              color="positive"
              icon="whatsapp"
              label="WhatsApp"
              @click="$emit('send-whatsapp', contact)"
              class="quick-action-btn"
            />
            <q-btn
              color="info"
              icon="history"
              label="Historique WhatsApp"
              @click="$emit('view-whatsapp-history', contact)"
              class="quick-action-btn"
            />
            <q-btn
              color="negative"
              icon="delete"
              label="Supprimer"
              @click="$emit('delete-contact', contact)"
              class="quick-action-btn"
            />
          </div>

          <!-- Contact Detail View -->
          <div class="detail-sections">
            <ContactDetailView
              :contact="contact"
              :loading="loading"
              @edit="$emit('edit-contact', $event)"
              @delete="$emit('delete-contact', $event)"
              @send-sms="$emit('send-sms', $event)"
              @view-sms-details="$emit('view-sms-details', $event)"
              @send-whatsapp="$emit('send-whatsapp', $event)"
              @view-whatsapp-history="$emit('view-whatsapp-history', $event)"
              class="detail-view"
            />
          </div>
        </div>
      </q-card-section>

      <!-- Modal Footer (Optional) -->
      <q-card-actions v-if="contact && !loading" align="right" class="modal-footer">
        <q-btn
          flat
          color="grey-7"
          label="Fermer"
          @click="handleClose"
        />
        <q-btn
          color="primary"
          icon="edit"
          label="Modifier"
          @click="$emit('edit-contact', contact)"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup lang="ts">
import ContactDetailView from '../../../components/contacts/ContactDetailView.vue';
import type { ContactDetailModalProps, Contact } from '../types/contacts.types';

// Props
const props = withDefaults(defineProps<ContactDetailModalProps>(), {
  loading: false
});

// Events
const emit = defineEmits<{
  'update:visible': [visible: boolean];
  'close': [];
  'edit-contact': [contact: Contact];
  'delete-contact': [contact: Contact];
  'send-sms': [contact: Contact];
  'send-whatsapp': [contact: Contact];
  'view-whatsapp-history': [contact: Contact];
  'view-sms-details': [sms: any];
}>();

// Methods
function handleVisibilityChange(visible: boolean): void {
  emit('update:visible', visible);
  if (!visible) {
    emit('close');
  }
}

function handleClose(): void {
  emit('update:visible', false);
  emit('close');
}
</script>

<style lang="scss" scoped>
// Contacts Color Palette
$contacts-primary: #673ab7;
$contacts-secondary: #9c27b0;

.contact-detail-modal {
  width: 100%;
  height: 100%;
  max-width: none;
  max-height: none;
  display: flex;
  flex-direction: column;
}

// Modal Header
.modal-header {
  &.contacts-gradient {
    background: linear-gradient(135deg, $contacts-primary 0%, $contacts-secondary 100%);
  }
  
  .header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    
    .header-info {
      display: flex;
      align-items: center;
      gap: 1rem;
      
      .header-icon {
        color: white;
        opacity: 0.9;
      }
      
      .header-text {
        color: white;
        
        .contact-name {
          font-size: 1.5rem;
          font-weight: 600;
          margin: 0 0 0.25rem 0;
          line-height: 1.2;
        }
        
        .contact-phone {
          font-size: 1rem;
          margin: 0;
          opacity: 0.8;
          font-family: monospace;
        }
      }
    }
    
    .header-actions {
      display: flex;
      gap: 0.5rem;
      
      .action-btn {
        border-radius: 8px;
        transition: all 0.2s ease;
        
        &:hover {
          background: rgba(255, 255, 255, 0.1);
        }
      }
    }
  }
}

// Modal Content
.modal-content {
  flex: 1;
  overflow: auto;
  padding: 0;
  
  .loading-wrapper,
  .error-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 300px;
    gap: 1rem;
    
    .loading-text,
    .error-text {
      font-size: 1.1rem;
      color: #666;
    }
    
    .error-action {
      margin-top: 1rem;
    }
  }
  
  .contact-details {
    height: 100%;
    display: flex;
    flex-direction: column;
    
    .quick-actions-bar {
      background: #f8f9fa;
      border-bottom: 1px solid #e9ecef;
      padding: 1rem 2rem;
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
      
      .quick-action-btn {
        border-radius: 8px;
        font-weight: 500;
        text-transform: none;
        flex: 1;
        min-width: 140px;
        
        @media (max-width: 768px) {
          min-width: auto;
          flex: 1 1 calc(50% - 0.5rem);
        }
        
        @media (max-width: 480px) {
          flex: 1 1 100%;
        }
      }
    }
    
    .detail-sections {
      flex: 1;
      overflow: auto;
      padding: 2rem;
      
      .detail-view {
        max-width: 1200px;
        margin: 0 auto;
      }
    }
  }
}

// Modal Footer
.modal-footer {
  border-top: 1px solid #e9ecef;
  background: #fafafa;
  padding: 1rem 2rem;
}

// Responsive Design
@media (max-width: 768px) {
  .modal-header {
    .header-content {
      flex-direction: column;
      gap: 1rem;
      text-align: center;
      
      .header-info {
        flex-direction: column;
        gap: 0.5rem;
        
        .header-text {
          text-align: center;
          
          .contact-name {
            font-size: 1.25rem;
          }
        }
      }
      
      .header-actions {
        order: -1;
        align-self: flex-end;
      }
    }
  }
  
  .modal-content {
    .contact-details {
      .quick-actions-bar {
        padding: 1rem;
      }
      
      .detail-sections {
        padding: 1rem;
      }
    }
  }
  
  .modal-footer {
    padding: 1rem;
    flex-direction: column;
    gap: 0.5rem;
    
    .q-btn {
      width: 100%;
    }
  }
}

@media (max-width: 480px) {
  .modal-header {
    .header-content {
      .header-info {
        .header-text {
          .contact-name {
            font-size: 1.1rem;
          }
          
          .contact-phone {
            font-size: 0.9rem;
          }
        }
      }
    }
  }
}

// Animation overrides pour une meilleure UX
:deep(.q-dialog__inner) {
  padding: 0;
}

:deep(.q-card) {
  border-radius: 0;
  
  @media (min-width: 1024px) {
    border-radius: 16px;
    margin: 2rem;
    max-height: calc(100vh - 4rem);
  }
}
</style>