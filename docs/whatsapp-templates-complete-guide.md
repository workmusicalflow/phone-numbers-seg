# Guide Complet du Système de Templates WhatsApp

## Table des Matières

1. [Vue d'Ensemble](#vue-densemble)
2. [Architecture du Système](#architecture-du-système)
3. [Configuration Requise](#configuration-requise)
4. [Structure des Fichiers](#structure-des-fichiers)
5. [API Backend](#api-backend)
6. [Frontend Vue.js](#frontend-vuejs)
7. [Types et Interfaces](#types-et-interfaces)
8. [Flux de Données](#flux-de-données)
9. [Gestion des Erreurs](#gestion-des-erreurs)
10. [Tests et Débogage](#tests-et-débogage)
11. [Bonnes Pratiques](#bonnes-pratiques)
12. [Troubleshooting](#troubleshooting)

---

## Vue d'Ensemble

Le système de templates WhatsApp permet l'envoi de messages préapprouvés via l'API Meta WhatsApp Business. Il offre une interface utilisateur intuitive pour sélectionner, personnaliser et envoyer des messages à des contacts individuels ou en masse.

### Fonctionnalités Principales

- **Récupération des templates** : Synchronisation avec l'API Meta pour obtenir les templates approuvés
- **Sélection intuitive** : Interface de sélection avec prévisualisation en temps réel
- **Personnalisation** : Support des variables dynamiques dans le corps, l'en-tête et les boutons
- **Support multimédia** : Envoi d'images, vidéos et documents dans l'en-tête
- **Historique** : Traçabilité complète des messages envoyés
- **Gestion d'erreurs** : Feedback détaillé en cas d'échec

---

## Architecture du Système

### Stack Technique

- **Backend** : PHP 8.3 avec architecture REST
- **Frontend** : Vue.js 3 avec TypeScript et Quasar Framework
- **Base de données** : SQLite avec Doctrine ORM
- **API externe** : Meta WhatsApp Business Cloud API v22.0

### Composants Principaux

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│                 │     │                  │     │                 │
│   Frontend      │────▶│   Backend PHP    │────▶│  Meta API       │
│   Vue.js 3      │     │   REST API       │     │  WhatsApp       │
│                 │     │                  │     │                 │
└─────────────────┘     └──────────────────┘     └─────────────────┘
        │                        │
        │                        │
        ▼                        ▼
┌─────────────────┐     ┌──────────────────┐
│                 │     │                  │
│   Pinia Store   │     │   SQLite DB      │
│   State Mgmt    │     │   (Doctrine)     │
│                 │     │                  │
└─────────────────┘     └──────────────────┘
```

---

## Configuration Requise

### Variables d'Environnement

Créez un fichier `.env` à la racine du projet avec :

```env
# Meta WhatsApp API Configuration
WHATSAPP_ACCESS_TOKEN=your_access_token_here
WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id
WHATSAPP_WABA_ID=your_business_account_id
WHATSAPP_API_VERSION=v22.0
WHATSAPP_APP_ID=your_app_id

# Webhook Configuration
WHATSAPP_WEBHOOK_VERIFY_TOKEN=oracle_whatsapp_webhook_verification_token
WHATSAPP_WEBHOOK_CALLBACK_URL=https://yourdomain.com/whatsapp/webhook.php

# API Configuration
API_BASE_URL=http://localhost:8000/api
```

### Prérequis Système

- PHP 8.3+
- Node.js 22.14.0 LTS
- Composer
- npm/yarn
- SQLite 3

---

## Structure des Fichiers

### Backend

```
/public/api/whatsapp/
├── send-template.php         # Endpoint principal d'envoi
├── upload.php                # Upload de médias
├── status.php                # Vérification de statut
├── templates/
│   └── approved.php          # Récupération des templates
└── README.md                 # Documentation des endpoints

/src/
├── Services/WhatsApp/
│   ├── WhatsAppService.php               # Service principal
│   ├── WhatsAppRestClient.php            # Client REST Meta API
│   ├── WhatsAppTemplateService.php       # Gestion des templates
│   ├── WhatsAppMonitoringService.php     # Monitoring et métriques
│   └── WhatsAppWebhookService.php        # Traitement des webhooks
├── Entities/WhatsApp/
│   ├── WhatsAppTemplate.php              # Entité template
│   ├── WhatsAppMessageHistory.php        # Historique des messages
│   └── WhatsAppQueue.php                 # File d'attente
└── Repositories/WhatsApp/
    └── ...                               # Repositories Doctrine
```

### Frontend

```
/frontend/src/
├── views/
│   ├── WhatsApp.vue                      # Vue principale
│   └── WhatsAppTemplates.vue             # Gestion des templates
├── components/whatsapp/
│   ├── WhatsAppTemplateSelector.vue      # Sélecteur de templates
│   ├── WhatsAppMessageComposer.vue       # Compositeur de messages
│   ├── EnhancedTemplateSelector.vue      # Sélecteur avancé
│   └── WhatsAppTemplateMessage.vue       # Prévisualisation
├── services/
│   ├── whatsappRestClient.ts             # Client API REST
│   └── whatsappApiClient.ts              # Configuration axios
├── stores/
│   ├── whatsappStore.ts                  # Store Pinia principal
│   └── whatsappTemplateStore.ts          # Store des templates
└── types/
    └── whatsapp-templates.ts             # Définitions TypeScript
```

---

## API Backend

### Endpoint Principal : `/api/whatsapp/send-template.php`

#### Requête (POST)

```json
{
  "recipientPhoneNumber": "+2250700000000",
  "templateName": "hello_world",
  "templateLanguage": "fr",
  "bodyVariables": ["John", "Doe"],
  "headerMediaUrl": "https://example.com/image.jpg",
  "headerMediaId": "media_id_123",
  "templateComponentsJsonString": "{...}"
}
```

#### Paramètres

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `recipientPhoneNumber` | string | ✅ | Numéro au format E.164 (+XXX...) |
| `templateName` | string | ✅ | Nom du template approuvé |
| `templateLanguage` | string | ✅ | Code langue (fr, en, etc.) |
| `bodyVariables` | array | ❌ | Variables pour le corps du message |
| `headerMediaUrl` | string | ❌ | URL du média pour l'en-tête |
| `headerMediaId` | string | ❌ | ID du média uploadé |
| `templateComponentsJsonString` | string | ❌ | Composants JSON complets |

#### Réponse Succès (200)

```json
{
  "success": true,
  "messageId": "wamid.HBgMM...",
  "timestamp": "2025-05-27T10:00:00Z"
}
```

#### Réponse Erreur (400/500)

```json
{
  "success": false,
  "error": "Description de l'erreur",
  "errorCode": "invalid_template",
  "details": {
    "field": "templateName",
    "message": "Template not found"
  }
}
```

### Endpoint Templates : `/api/whatsapp/templates/approved.php`

#### Requête (GET)

```
GET /api/whatsapp/templates/approved.php?language=fr&category=MARKETING
```

#### Paramètres Query

| Paramètre | Type | Description |
|-----------|------|-------------|
| `language` | string | Filtrer par langue |
| `category` | string | Filtrer par catégorie |
| `use_cache` | boolean | Utiliser le cache (défaut: true) |
| `force_refresh` | boolean | Forcer la synchronisation |

#### Réponse

```json
{
  "status": "success",
  "templates": [
    {
      "id": "123456789",
      "name": "hello_world",
      "category": "MARKETING",
      "language": "fr",
      "status": "APPROVED",
      "components": [...],
      "bodyVariablesCount": 2,
      "hasMediaHeader": true,
      "hasButtons": true
    }
  ],
  "count": 15,
  "meta": {
    "source": "api",
    "timestamp": "2025-05-27T10:00:00Z"
  }
}
```

---

## Frontend Vue.js

### Composant Principal : WhatsApp.vue

```vue
<template>
  <q-page class="whatsapp-page">
    <!-- Onglets pour navigation -->
    <q-tabs v-model="activeTab">
      <q-tab name="send" label="Envoyer un message" />
      <q-tab name="history" label="Historique" />
      <q-tab name="templates" label="Templates" />
    </q-tabs>

    <!-- Panneau d'envoi -->
    <q-tab-panels v-model="activeTab">
      <q-tab-panel name="send">
        <!-- Workflow en 3 étapes -->
        <div v-if="currentStep === 'recipient'">
          <!-- Sélection du destinataire -->
        </div>
        
        <div v-else-if="currentStep === 'template'">
          <WhatsAppTemplateSelector 
            @select="onTemplateSelected"
            @cancel="currentStep = 'recipient'"
          />
        </div>
        
        <div v-else-if="currentStep === 'customize'">
          <WhatsAppMessageComposer 
            :templateData="selectedTemplateData"
            :recipientPhoneNumber="selectedRecipient"
            @message-sent="onTemplateSent"
            @cancel="currentStep = 'template'"
          />
        </div>
      </q-tab-panel>
    </q-tab-panels>
  </q-page>
</template>
```

### Store Pinia : whatsappStore.ts

```typescript
import { defineStore } from 'pinia';
import { whatsappRestClient } from '@/services/whatsappRestClient';

export const useWhatsAppStore = defineStore('whatsapp', {
  state: () => ({
    templates: [] as WhatsAppTemplate[],
    messages: [] as WhatsAppMessage[],
    loading: false,
    error: null as string | null
  }),

  getters: {
    approvedTemplates: (state) => 
      state.templates.filter(t => t.status === 'APPROVED'),
    
    templatesByCategory: (state) => (category: string) =>
      state.templates.filter(t => t.category === category)
  },

  actions: {
    async fetchTemplates(filters?: TemplateFilters) {
      this.loading = true;
      try {
        const response = await whatsappRestClient.getApprovedTemplates(filters);
        this.templates = response.templates;
      } catch (error) {
        this.error = error.message;
      } finally {
        this.loading = false;
      }
    },

    async sendTemplateMessage(data: WhatsAppTemplateSendRequest) {
      this.loading = true;
      try {
        const response = await whatsappRestClient.sendTemplateMessageV2(data);
        if (response.success) {
          await this.fetchMessages(); // Rafraîchir l'historique
        }
        return response;
      } catch (error) {
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
      }
    }
  }
});
```

### Service REST Client

```typescript
// whatsappRestClient.ts
export class WhatsAppRestClient {
  private api = whatsappApi; // Instance axios configurée

  async sendTemplateMessageV2(data: WhatsAppTemplateSendRequest): Promise<WhatsAppTemplateSendResponse> {
    try {
      // Validation côté client
      this.validateSendRequest(data);
      
      // Envoi au backend
      const response = await this.api.post('/whatsapp/send-template.php', data);
      
      if (response.data.success) {
        return {
          success: true,
          messageId: response.data.messageId,
          timestamp: response.data.timestamp
        };
      }
      
      throw new Error(response.data.error || 'Échec de l\'envoi');
    } catch (error) {
      return {
        success: false,
        error: this.analyzeError(error)
      };
    }
  }

  private validateSendRequest(data: WhatsAppTemplateSendRequest) {
    if (!data.recipientPhoneNumber?.match(/^\+\d{10,15}$/)) {
      throw new Error('Numéro de téléphone invalide');
    }
    
    if (!data.templateName) {
      throw new Error('Nom du template requis');
    }
    
    // Validation des variables selon le template
    if (data.bodyVariables?.length) {
      // Vérifier que le nombre correspond au template
    }
  }
}
```

---

## Types et Interfaces

### Types TypeScript (whatsapp-templates.ts)

```typescript
// Template WhatsApp
export interface WhatsAppTemplate {
  id: string;
  name: string;
  category: 'MARKETING' | 'UTILITY' | 'AUTHENTICATION';
  language: string;
  status: 'APPROVED' | 'PENDING' | 'REJECTED';
  components?: WhatsAppTemplateComponent[];
  bodyVariablesCount?: number;
  hasMediaHeader?: boolean;
  hasButtons?: boolean;
}

// Composant de template
export interface WhatsAppTemplateComponent {
  type: 'HEADER' | 'BODY' | 'FOOTER' | 'BUTTONS';
  format?: 'TEXT' | 'IMAGE' | 'VIDEO' | 'DOCUMENT';
  text?: string;
  buttons?: WhatsAppButton[];
  example?: {
    header_text?: string[];
    body_text?: string[][];
    header_handle?: string[];
  };
}

// Requête d'envoi
export interface WhatsAppTemplateSendRequest {
  recipientPhoneNumber: string;
  templateName: string;
  templateLanguage: string;
  bodyVariables?: string[];
  headerMediaUrl?: string;
  headerMediaId?: string;
  buttonVariables?: Record<string, string>;
  templateComponentsJsonString?: string;
}

// Réponse d'envoi
export interface WhatsAppTemplateSendResponse {
  success: boolean;
  messageId?: string;
  timestamp?: string;
  error?: string;
  errorCode?: string;
  details?: any;
}
```

---

## Flux de Données

### 1. Sélection du Template

```
Utilisateur
    │
    ▼
WhatsApp.vue
    │
    ├─── Appel: whatsappStore.fetchTemplates()
    │         │
    │         ▼
    │    whatsappRestClient.getApprovedTemplates()
    │         │
    │         ▼
    │    GET /api/whatsapp/templates/approved.php
    │         │
    │         ▼
    │    WhatsAppTemplateService::getApprovedTemplates()
    │         │
    │         ├─── Cache? → Retour données cachées
    │         │
    │         └─── API Meta → Synchronisation
    │
    ▼
WhatsAppTemplateSelector.vue
    │
    └─── Affichage des templates
```

### 2. Envoi du Message

```
Utilisateur remplit le formulaire
    │
    ▼
WhatsAppMessageComposer.vue
    │
    ├─── Validation locale
    │
    ├─── Construction WhatsAppTemplateSendRequest
    │
    ▼
whatsappStore.sendTemplateMessage()
    │
    ▼
whatsappRestClient.sendTemplateMessageV2()
    │
    ▼
POST /api/whatsapp/send-template.php
    │
    ├─── Validation serveur
    │
    ├─── WhatsAppService::sendTemplateMessage()
    │
    ├─── Construction payload Meta API
    │
    ▼
Meta WhatsApp API
    │
    ├─── Succès → Sauvegarde en DB
    │             │
    │             └─── WhatsAppMessageHistory
    │
    └─── Échec → Log erreur + retour utilisateur
```

---

## Gestion des Erreurs

### Codes d'Erreur Courants

| Code | Description | Solution |
|------|-------------|----------|
| `recipient_not_found` | Numéro non enregistré sur WhatsApp | Vérifier le numéro |
| `invalid_template` | Template non approuvé ou inexistant | Synchroniser les templates |
| `invalid_variables` | Variables manquantes ou incorrectes | Vérifier le nombre de variables |
| `rate_limited` | Limite de débit atteinte | Ralentir les envois |
| `outside_window` | Hors fenêtre 24h | Utiliser un template |
| `media_error` | Problème avec le média | Vérifier URL/format |
| `access_denied` | Token invalide | Vérifier configuration |

### Gestion Frontend

```typescript
// Dans le composant
try {
  const response = await this.sendMessage();
  if (response.success) {
    this.$q.notify({
      type: 'positive',
      message: 'Message envoyé avec succès'
    });
  }
} catch (error) {
  this.$q.notify({
    type: 'negative',
    message: error.userMessage || 'Erreur lors de l\'envoi',
    caption: error.technicalDetails
  });
}
```

### Logs Backend

Les erreurs sont loggées dans :
- `/var/logs/whatsapp/whatsapp_api.log` : Logs généraux
- `/var/logs/whatsapp/whatsapp_errors.log` : Erreurs uniquement
- `/var/logs/whatsapp/whatsapp_queue.log` : File d'attente

---

## Tests et Débogage

### Tests Unitaires Backend

```bash
# Exécuter tous les tests WhatsApp
vendor/bin/phpunit tests/WhatsApp/

# Test spécifique
vendor/bin/phpunit tests/WhatsApp/WhatsAppRestClientTest.php
```

### Tests Frontend

```bash
# Tests unitaires
npm run test:unit

# Tests E2E
npm run test:e2e
```

### Outils de Débogage

1. **Console navigateur** : Activer les logs détaillés
   ```javascript
   localStorage.setItem('debug', 'whatsapp:*');
   ```

2. **Postman/cURL** : Tester directement les endpoints
   ```bash
   curl -X POST http://localhost:8000/api/whatsapp/send-template.php \
     -H "Content-Type: application/json" \
     -d '{
       "recipientPhoneNumber": "+2250700000000",
       "templateName": "hello_world",
       "templateLanguage": "fr"
     }'
   ```

3. **Logs PHP** : Activer le mode debug
   ```php
   // Dans .env
   APP_DEBUG=true
   WHATSAPP_DEBUG=true
   ```

---

## Bonnes Pratiques

### Sécurité

1. **Validation des entrées** : Toujours valider côté serveur
2. **Tokens sécurisés** : Ne jamais exposer les tokens dans le frontend
3. **Rate limiting** : Implémenter des limites pour éviter les abus
4. **Logs sensibles** : Ne pas logger les tokens ou données personnelles

### Performance

1. **Cache des templates** : Utiliser le cache pour réduire les appels API
2. **Pagination** : Paginer les listes de messages et templates
3. **Lazy loading** : Charger les composants à la demande
4. **Queue système** : Utiliser la file d'attente pour les envois en masse

### Code

1. **TypeScript strict** : Activer le mode strict pour la sécurité des types
2. **Composants réutilisables** : Créer des composants atomiques
3. **Gestion d'état** : Utiliser Pinia pour l'état global
4. **Tests** : Maintenir une couverture de tests > 80%

---

## Troubleshooting

### Problème : "Template not found"

**Causes possibles** :
- Template non synchronisé
- Nom incorrect
- Template non approuvé

**Solution** :
```bash
# Forcer la synchronisation
curl -X GET "http://localhost:8000/api/whatsapp/templates/approved.php?force_refresh=true"
```

### Problème : "Rate limit exceeded"

**Causes** :
- Trop d'envois en peu de temps
- Limite journalière atteinte

**Solution** :
- Implémenter un système de queue
- Espacer les envois (minimum 1 seconde entre chaque)

### Problème : "Invalid phone number"

**Causes** :
- Format incorrect
- Numéro non enregistré sur WhatsApp

**Solution** :
- Vérifier le format E.164 : `+[country][number]`
- Tester avec l'API Meta directement

### Problème : "Media upload failed"

**Causes** :
- Taille du fichier trop grande
- Format non supporté
- URL non accessible

**Solution** :
- Limites : Image 5MB, Video 16MB, Document 100MB
- Formats : JPEG, PNG, MP4, PDF
- URL doit être publiquement accessible

---

## Ressources Supplémentaires

- [Documentation Meta WhatsApp API](https://developers.facebook.com/docs/whatsapp/cloud-api)
- [Vue.js 3 Documentation](https://vuejs.org/)
- [Quasar Framework](https://quasar.dev/)
- [Pinia Documentation](https://pinia.vuejs.org/)

---

## Changelog

### Version 2.0.0 (Mai 2025)
- Migration de `send-template-v2.php` vers `send-template.php`
- Amélioration de la gestion des erreurs
- Ajout du support des médias par ID
- Optimisation du cache des templates

### Version 1.0.0 (Avril 2025)
- Version initiale
- Support des templates texte uniquement
- Interface basique d'envoi

---

*Documentation maintenue par l'équipe Oracle - Dernière mise à jour : 27 Mai 2025*