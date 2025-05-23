<template>
  <q-page padding>
    <div class="home-page">
      <!-- Modern Page Header -->
      <div class="page-header">
        <div class="header-content">
          <div class="header-title-section">
            <div class="title-icon-wrapper">
              <q-icon name="dashboard" size="lg" />
            </div>
            <div class="title-text">
              <h1 class="page-title">Oracle Dashboard</h1>
              <p class="page-subtitle">Gestionnaire de contacts et communication</p>
            </div>
          </div>
          
          <div class="header-stats">
            <div class="stat-card">
              <div class="stat-value">3</div>
              <div class="stat-label">Modules</div>
            </div>
            <div class="stat-card">
              <div class="stat-value">SMS</div>
              <div class="stat-label">Prêt</div>
            </div>
            <div class="stat-card">
              <div class="stat-value">API</div>
              <div class="stat-label">Actif</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Features Section -->
      <div class="features-section">
        <div class="section-header">
          <h2 class="section-title">Fonctionnalités Principales</h2>
          <p class="section-subtitle">Choisissez un module pour commencer</p>
        </div>

        <div class="features-grid">
          <!-- Segmentation individuelle -->
          <div class="feature-card">
            <div class="card-header home-gradient">
              <div class="header-content">
                <q-icon name="phone" size="md" class="header-icon" />
                <div class="header-text">
                  <h3 class="header-title">Segmentation Individuelle</h3>
                  <p class="header-subtitle">Analyse d'un numéro unique</p>
                </div>
              </div>
            </div>
            
            <div class="card-content">
              <div class="feature-description">
                <p>Analysez et segmentez un numéro de téléphone pour identifier l'opérateur, le pays et la région.</p>
                
                <div class="feature-highlights">
                  <div class="highlight-item">
                    <q-icon name="check_circle" size="sm" color="positive" />
                    <span>Identification de l'opérateur</span>
                  </div>
                  <div class="highlight-item">
                    <q-icon name="check_circle" size="sm" color="positive" />
                    <span>Localisation géographique</span>
                  </div>
                  <div class="highlight-item">
                    <q-icon name="check_circle" size="sm" color="positive" />
                    <span>Validation du format</span>
                  </div>
                </div>
              </div>
              
              <div class="card-actions">
                <q-btn
                  color="primary"
                  icon="arrow_forward"
                  label="Accéder"
                  to="/segment"
                  class="action-btn"
                />
              </div>
            </div>
          </div>

          <!-- Traitement par lot -->
          <div class="feature-card">
            <div class="card-header home-gradient">
              <div class="header-content">
                <q-icon name="batch_prediction" size="md" class="header-icon" />
                <div class="header-text">
                  <h3 class="header-title">Traitement par Lot</h3>
                  <p class="header-subtitle">Segmentation massive</p>
                </div>
              </div>
            </div>
            
            <div class="card-content">
              <div class="feature-description">
                <p>Traitez et segmentez plusieurs numéros de téléphone en une seule opération pour optimiser votre workflow.</p>
                
                <div class="feature-highlights">
                  <div class="highlight-item">
                    <q-icon name="check_circle" size="sm" color="positive" />
                    <span>Import CSV/Excel</span>
                  </div>
                  <div class="highlight-item">
                    <q-icon name="check_circle" size="sm" color="positive" />
                    <span>Traitement parallèle</span>
                  </div>
                  <div class="highlight-item">
                    <q-icon name="check_circle" size="sm" color="positive" />
                    <span>Export des résultats</span>
                  </div>
                </div>
              </div>
              
              <div class="card-actions">
                <q-btn
                  color="primary"
                  icon="arrow_forward"
                  label="Accéder"
                  to="/batch"
                  class="action-btn"
                />
              </div>
            </div>
          </div>

          <!-- Gestion des segments -->
          <div class="feature-card">
            <div class="card-header home-gradient">
              <div class="header-content">
                <q-icon name="settings" size="md" class="header-icon" />
                <div class="header-text">
                  <h3 class="header-title">Gestion des Segments</h3>
                  <p class="header-subtitle">Configuration personnalisée</p>
                </div>
              </div>
            </div>
            
            <div class="card-content">
              <div class="feature-description">
                <p>Gérez et configurez les segments personnalisés pour améliorer la précision de vos analyses.</p>
                
                <div class="feature-highlights">
                  <div class="highlight-item">
                    <q-icon name="check_circle" size="sm" color="positive" />
                    <span>Segments custom</span>
                  </div>
                  <div class="highlight-item">
                    <q-icon name="check_circle" size="sm" color="positive" />
                    <span>Règles avancées</span>
                  </div>
                  <div class="highlight-item">
                    <q-icon name="check_circle" size="sm" color="positive" />
                    <span>Historique des modifications</span>
                  </div>
                </div>
              </div>
              
              <div class="card-actions">
                <q-btn
                  color="primary"
                  icon="arrow_forward"
                  label="Accéder"
                  to="/segments"
                  class="action-btn"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions Section -->
      <div class="quick-actions-section">
        <div class="modern-card">
          <div class="card-header home-gradient">
            <div class="header-content">
              <q-icon name="flash_on" size="md" class="header-icon" />
              <div class="header-text">
                <h3 class="header-title">Actions Rapides</h3>
                <p class="header-subtitle">Raccourcis vers les fonctionnalités courantes</p>
              </div>
            </div>
          </div>
          
          <div class="card-content">
            <div class="quick-actions-grid">
              <q-btn
                color="primary"
                icon="contacts"
                label="Gérer les Contacts"
                to="/contacts"
                class="quick-action-btn"
                size="lg"
              />
              <q-btn
                color="secondary"
                icon="sms"
                label="Envoyer SMS"
                to="/sms"
                class="quick-action-btn"
                size="lg"
              />
              <q-btn
                color="accent"
                icon="whatsapp"
                label="WhatsApp"
                to="/whatsapp"
                class="quick-action-btn"
                size="lg"
              />
              <q-btn
                color="info"
                icon="history"
                label="Historique SMS"
                to="/sms/history"
                class="quick-action-btn"
                size="lg"
              />
            </div>
          </div>
        </div>
      </div>

    </div>
  </q-page>
</template>

<script setup lang="ts">
// Home component logic - modernized version
</script>

<style lang="scss" scoped>
// Home Color Palette
$home-primary: #2196f3;
$home-secondary: #03a9f4;
$home-accent: #00bcd4;
$home-light: #e3f2fd;

// Design System Integration
.home-page {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0;
}

// Modern Page Header
.page-header {
  background: linear-gradient(135deg, $home-primary 0%, $home-secondary 100%);
  border-radius: 16px;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 8px 32px rgba(33, 150, 243, 0.2);
  
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
}

// Features Section
.features-section {
  margin-bottom: 2rem;
  
  .section-header {
    text-align: center;
    margin-bottom: 2rem;
    
    .section-title {
      font-size: 1.75rem;
      font-weight: 700;
      color: #333;
      margin: 0 0 0.5rem 0;
    }
    
    .section-subtitle {
      font-size: 1.1rem;
      color: #666;
      margin: 0;
      font-weight: 400;
    }
  }
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 2rem;
  margin-bottom: 2rem;
}

// Modern Card Structure
.modern-card,
.feature-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
  overflow: hidden;
  transition: all 0.3s ease;
  
  &:hover {
    box-shadow: 0 12px 48px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
  }
}

// Home Gradient
.home-gradient {
  background: linear-gradient(135deg, $home-primary 0%, $home-secondary 100%);
}

// Card Header
.card-header {
  padding: 1.5rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  
  .header-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    
    .header-icon {
      color: white;
      opacity: 0.9;
    }
    
    .header-text {
      color: white;
      
      .header-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0 0 0.25rem 0;
        line-height: 1.2;
      }
      
      .header-subtitle {
        font-size: 0.9rem;
        margin: 0;
        opacity: 0.8;
        line-height: 1.1;
      }
    }
  }
}

.card-content {
  padding: 2rem;
}

// Feature Card Specific Styles
.feature-description {
  margin-bottom: 1.5rem;
  
  p {
    font-size: 1rem;
    color: #666;
    line-height: 1.6;
    margin: 0 0 1.5rem 0;
  }
  
  .feature-highlights {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    
    .highlight-item {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      font-size: 0.9rem;
      color: #555;
      
      span {
        font-weight: 500;
      }
    }
  }
}

.card-actions {
  display: flex;
  justify-content: flex-end;
  
  .action-btn {
    background: linear-gradient(135deg, $home-primary 0%, $home-secondary 100%);
    color: white;
    font-weight: 600;
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    text-transform: none;
    
    &:hover {
      box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
    }
  }
}

// Quick Actions Section
.quick-actions-section {
  margin-bottom: 2rem;
  
  .quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    
    .quick-action-btn {
      height: 60px;
      text-transform: none;
      font-weight: 600;
      border-radius: 12px;
      
      &:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
      }
    }
  }
}


// Responsive Design
@media (max-width: 1024px) {
  .header-stats {
    flex-direction: column;
    gap: 0.75rem !important;
  }
  
  .features-grid {
    grid-template-columns: 1fr;
  }
  
  .quick-actions-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .page-header {
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
  
  .card-header {
    padding: 1rem 1.5rem;
  }
  
  .card-content {
    padding: 1.5rem;
  }
  
  .quick-actions-grid {
    grid-template-columns: 1fr;
    
    .quick-action-btn {
      height: 50px;
    }
  }
}

@media (max-width: 480px) {
  .page-header {
    padding: 1rem;
    border-radius: 12px;
    
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
  
  .modern-card,
  .feature-card {
    border-radius: 12px;
  }
  
  .card-content {
    padding: 1rem;
  }
  
  .features-grid {
    gap: 1rem;
  }
  
  .feature-description {
    .feature-highlights {
      .highlight-item {
        font-size: 0.85rem;
      }
    }
  }
}
</style>