<template>
  <div class="contacts-header">
    <div class="header-content">
      <div class="header-title-section">
        <div class="title-icon-wrapper">
          <q-icon name="contacts" size="md" />
        </div>
        <div class="title-text">
          <h1 class="page-title">Gestion des Contacts</h1>
          <p class="page-subtitle">Organisez et gérez votre carnet d'adresses</p>
        </div>
      </div>
      
      <div class="header-stats">
        <div class="stat-card">
          <div class="stat-value">{{ stats.total }}</div>
          <div class="stat-label">Total</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ stats.active }}</div>
          <div class="stat-label">Actifs</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ stats.groups }}</div>
          <div class="stat-label">Groupes</div>
        </div>
      </div>
    </div>

    <!-- Loading overlay pour les stats -->
    <div v-if="loading" class="stats-loading">
      <q-spinner-dots size="sm" color="white" />
    </div>
  </div>
</template>

<script setup lang="ts">
import type { ContactsHeaderProps } from '../types/contacts.types';

// Props
// eslint-disable-next-line @typescript-eslint/no-unused-vars
const props = withDefaults(defineProps<ContactsHeaderProps>(), {
  loading: false
});
</script>

<style lang="scss" scoped>
// Contacts Color Palette
$contacts-primary: #673ab7;
$contacts-secondary: #9c27b0;

.contacts-header {
  position: relative;
  background: linear-gradient(135deg, $contacts-primary 0%, $contacts-secondary 100%);
  border-radius: 16px;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 8px 32px rgba(103, 58, 183, 0.2);
  
  .header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
    
    .header-title-section {
      display: flex;
      align-items: center;
      gap: 1.5rem;
      
      .title-icon-wrapper {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 16px;
        padding: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        
        .q-icon {
          color: white;
        }
      }
      
      .title-text {
        color: white;
        
        .page-title {
          font-size: 2rem;
          font-weight: 700;
          margin: 0 0 0.5rem 0;
          line-height: 1.2;
        }
        
        .page-subtitle {
          font-size: 1.1rem;
          margin: 0;
          opacity: 0.9;
          font-weight: 400;
        }
      }
    }
    
    .header-stats {
      display: flex;
      gap: 1rem;
      
      .stat-card {
        background: rgba(255, 255, 255, 0.15);
        border-radius: 12px;
        padding: 1rem 1.5rem;
        text-align: center;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        min-width: 80px;
        transition: all 0.3s ease;
        
        &:hover {
          background: rgba(255, 255, 255, 0.2);
          transform: translateY(-2px);
        }
        
        .stat-value {
          font-size: 1.5rem;
          font-weight: 700;
          color: white;
          line-height: 1;
          margin-bottom: 0.25rem;
        }
        
        .stat-label {
          font-size: 0.8rem;
          color: rgba(255, 255, 255, 0.8);
          text-transform: uppercase;
          letter-spacing: 0.5px;
          font-weight: 500;
        }
      }
    }
  }

  .stats-loading {
    position: absolute;
    top: 50%;
    right: 2rem;
    transform: translateY(-50%);
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 0.5rem;
    backdrop-filter: blur(5px);
  }
}

// Responsive Design
@media (max-width: 1024px) {
  .contacts-header {
    .header-content {
      .header-stats {
        flex-direction: column;
        gap: 0.75rem;
      }
    }
  }
}

@media (max-width: 768px) {
  .contacts-header {
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    
    .header-content {
      flex-direction: column;
      gap: 1.5rem;
      
      .header-title-section {
        width: 100%;
        
        .title-icon-wrapper {
          padding: 0.75rem;
        }
        
        .title-text {
          .page-title {
            font-size: 1.5rem;
          }
          
          .page-subtitle {
            font-size: 1rem;
          }
        }
      }
      
      .header-stats {
        width: 100%;
        flex-direction: row;
        justify-content: space-around;
        
        .stat-card {
          min-width: auto;
          flex: 1;
          padding: 0.75rem 1rem;
          
          .stat-value {
            font-size: 1.25rem;
          }
          
          .stat-label {
            font-size: 0.75rem;
          }
        }
      }
    }
  }
}

@media (max-width: 480px) {
  .contacts-header {
    padding: 1rem;
    border-radius: 12px;
    
    .header-content {
      .header-title-section {
        gap: 1rem;
        
        .title-text .page-title {
          font-size: 1.25rem;
        }
      }
      
      .header-stats {
        .stat-card {
          padding: 0.5rem 0.75rem;
          
          .stat-value {
            font-size: 1rem;
          }
        }
      }
    }
  }
}
</style>