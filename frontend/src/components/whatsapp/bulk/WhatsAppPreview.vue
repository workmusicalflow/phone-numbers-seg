<template>
  <div class="whatsapp-preview">
    <div class="phone-frame">
      <div class="phone-header">
        <div class="header-content">
          <q-avatar size="32px" class="business-avatar">
            <q-icon name="business" />
          </q-avatar>
          <div class="business-info">
            <div class="business-name">{{ businessName || 'Votre Entreprise' }}</div>
            <div class="business-status">en ligne</div>
          </div>
        </div>
      </div>
      
      <div class="chat-area">
        <div class="message-bubble business-message">
          <!-- Template header -->
          <div v-if="templateHeader" class="message-header">
            <div v-if="templateHeader.type === 'TEXT'" class="header-text">
              {{ templateHeader.text }}
            </div>
            <div v-else-if="templateHeader.type === 'IMAGE'" class="header-media">
              <img v-if="headerMediaUrl || templateHeader.exampleUrl" 
                   :src="headerMediaUrl || templateHeader.exampleUrl" 
                   alt="Header media" 
                   class="header-image"
                   @error="handleImageError" />
              <div v-else class="media-placeholder">
                <q-icon name="image" size="24px" />
                <span>Image d'en-tête</span>
              </div>
            </div>
            <div v-else-if="templateHeader.type === 'VIDEO'" class="header-media">
              <div class="media-placeholder">
                <q-icon name="videocam" size="24px" />
                <span>Vidéo d'en-tête</span>
              </div>
            </div>
            <div v-else-if="templateHeader.type === 'DOCUMENT'" class="header-media">
              <div class="media-placeholder">
                <q-icon name="description" size="24px" />
                <span>Document d'en-tête</span>
              </div>
            </div>
          </div>
          
          <!-- Template body -->
          <div v-if="templateBody" class="message-body" v-html="formatWhatsAppText(templateBody)">
          </div>
          
          <!-- Template footer -->
          <div v-if="templateFooter" class="message-footer">
            {{ templateFooter }}
          </div>
          
          <!-- Template buttons -->
          <div v-if="templateButtons && templateButtons.length > 0" class="message-buttons">
            <div 
              v-for="(button, index) in templateButtons" 
              :key="index"
              class="template-button"
              :class="button.type.toLowerCase()"
            >
              <q-icon 
                v-if="button.type === 'PHONE_NUMBER'" 
                name="phone" 
                size="14px" 
                class="q-mr-xs"
              />
              <q-icon 
                v-else-if="button.type === 'URL'" 
                name="open_in_new" 
                size="14px" 
                class="q-mr-xs"
              />
              {{ button.text }}
            </div>
          </div>
          
          <div class="message-time">
            {{ currentTime }}
            <q-icon name="done_all" size="14px" class="message-status" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface TemplateHeader {
  type: 'TEXT' | 'IMAGE' | 'VIDEO' | 'DOCUMENT'
  text?: string
  exampleUrl?: string
}

interface TemplateButton {
  type: 'QUICK_REPLY' | 'URL' | 'PHONE_NUMBER'
  text: string
  url?: string
  phone_number?: string
}

interface Props {
  templateHeader?: TemplateHeader | null
  templateBody?: string
  templateFooter?: string
  templateButtons?: TemplateButton[]
  headerMediaUrl?: string
  businessName?: string
}

const props = withDefaults(defineProps<Props>(), {
  templateHeader: null,
  templateBody: '',
  templateFooter: '',
  templateButtons: () => [],
  headerMediaUrl: '',
  businessName: 'Votre Entreprise'
})

const currentTime = computed(() => {
  return new Date().toLocaleTimeString('fr-FR', { 
    hour: '2-digit', 
    minute: '2-digit' 
  })
})

const handleImageError = (event: Event) => {
  console.error('Erreur de chargement de l\'image:', event)
  // L'image sera remplacée par le placeholder
}

const formatWhatsAppText = (text: string): string => {
  if (!text) return ''
  
  // Échapper les caractères HTML dangereux
  let formatted = text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;')
  
  // Remplacer les formatages WhatsApp
  // Bold: *text* -> <strong>text</strong>
  formatted = formatted.replace(/\*([^*]+)\*/g, '<strong>$1</strong>')
  
  // Italic: _text_ -> <em>text</em>
  formatted = formatted.replace(/_([^_]+)_/g, '<em>$1</em>')
  
  // Strikethrough: ~text~ -> <del>text</del>
  formatted = formatted.replace(/~([^~]+)~/g, '<del>$1</del>')
  
  // Monospace: ```text``` -> <code>text</code>
  formatted = formatted.replace(/```([^`]+)```/g, '<code>$1</code>')
  
  // Line breaks
  formatted = formatted.replace(/\n/g, '<br>')
  
  return formatted
}
</script>

<style lang="scss" scoped>
.whatsapp-preview {
  max-width: 300px;
  margin: 0 auto;

  .phone-frame {
    background: linear-gradient(135deg, #128C7E 0%, #075E54 100%);
    border-radius: 20px;
    padding: 8px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    
    .phone-header {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 12px 12px 0 0;
      padding: 12px 16px;
      
      .header-content {
        display: flex;
        align-items: center;
        gap: 12px;
        
        .business-avatar {
          background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
          color: white;
        }
        
        .business-info {
          flex: 1;
          
          .business-name {
            font-weight: 600;
            font-size: 16px;
            color: var(--q-dark);
            margin-bottom: 2px;
          }
          
          .business-status {
            font-size: 12px;
            color: var(--q-primary);
          }
        }
      }
    }
    
    .chat-area {
      background: #E5DDD5;
      min-height: 200px;
      padding: 16px;
      border-radius: 0 0 12px 12px;
      background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
      
      .message-bubble {
        background: white;
        border-radius: 8px 8px 8px 2px;
        padding: 8px 12px;
        margin-bottom: 8px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        max-width: 240px;
        
        &.business-message {
          margin-left: auto;
          margin-right: 0;
          background: #DCF8C6;
          border-radius: 8px 8px 2px 8px;
        }
        
        .message-header {
          margin-bottom: 8px;
          
          .header-text {
            font-weight: 600;
            color: var(--q-dark);
            margin-bottom: 4px;
          }
          
          .header-media {
            .header-image {
              width: 100%;
              max-width: 200px;
              border-radius: 6px;
              margin-bottom: 4px;
            }
            
            .media-placeholder {
              display: flex;
              align-items: center;
              gap: 8px;
              padding: 12px;
              background: rgba(0, 0, 0, 0.05);
              border-radius: 6px;
              color: var(--q-grey-6);
              font-size: 14px;
            }
          }
        }
        
        .message-body {
          color: var(--q-dark);
          line-height: 1.4;
          margin-bottom: 8px;
          white-space: pre-wrap;
          
          :deep(strong) {
            font-weight: 700;
          }
          
          :deep(em) {
            font-style: italic;
          }
          
          :deep(del) {
            text-decoration: line-through;
          }
          
          :deep(code) {
            background: rgba(0, 0, 0, 0.05);
            padding: 2px 4px;
            border-radius: 3px;
            font-family: monospace;
            font-size: 0.9em;
          }
        }
        
        .message-footer {
          font-size: 12px;
          color: var(--q-grey-6);
          font-style: italic;
          margin-bottom: 8px;
        }
        
        .message-buttons {
          border-top: 1px solid rgba(0, 0, 0, 0.1);
          margin-top: 8px;
          padding-top: 8px;
          
          .template-button {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            margin: 4px 0;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
            
            &.quick_reply {
              background: rgba(37, 211, 102, 0.1);
              color: #075E54;
              border: 1px solid rgba(37, 211, 102, 0.3);
              
              &:hover {
                background: rgba(37, 211, 102, 0.2);
              }
            }
            
            &.url, &.phone_number {
              background: rgba(0, 123, 255, 0.1);
              color: #0056b3;
              border: 1px solid rgba(0, 123, 255, 0.3);
              
              &:hover {
                background: rgba(0, 123, 255, 0.2);
              }
            }
          }
        }
        
        .message-time {
          display: flex;
          align-items: center;
          justify-content: flex-end;
          gap: 4px;
          font-size: 11px;
          color: var(--q-grey-6);
          margin-top: 4px;
          
          .message-status {
            color: #25D366;
          }
        }
      }
    }
  }
}

// Responsive design
@media (max-width: 768px) {
  .whatsapp-preview {
    max-width: 280px;
    
    .phone-frame .chat-area .message-bubble {
      max-width: 220px;
    }
  }
}
</style>