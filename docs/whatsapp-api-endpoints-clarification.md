# WhatsApp API Endpoints - Clarification et Architecture

## État Actuel (Mai 2025) - MIGRATION TERMINÉE ✅

### Migration effectuée le 27 Mai 2025

La migration a été complétée avec succès :
- ✅ `send-template-v2.php` a été renommé en `send-template.php`
- ✅ L'ancien `send-template.php` a été archivé dans `/archive/whatsapp/api/send-template-v1.php`
- ✅ `send-template-simple.php` a été déplacé dans `/tests/mocks/whatsapp/send-template-mock.php`
- ✅ La configuration frontend a été mise à jour pour pointer vers `send-template.php`
- ✅ La documentation a été mise à jour

### Historique des endpoints WhatsApp

### 1. `/api/whatsapp/send-template-simple.php` ❌ (À SUPPRIMER)
- **Statut** : Mock/Test uniquement
- **But** : Simuler l'envoi sans vraie intégration
- **Utilisation** : Ne devrait PAS être utilisé en production
- **Action** : À déplacer dans un dossier de tests ou supprimer

### 2. `/api/whatsapp/send-template.php` ❌ (OBSOLÈTE)
- **Statut** : Ancienne version, non fonctionnelle
- **Problèmes** :
  - Utilise l'ancien bootstrap.php qui n'existe plus
  - Structure de données incompatible avec le frontend actuel
  - Paramètres de méthode incorrects
- **Action** : À archiver ou supprimer

### 3. `/api/whatsapp/send-template-v2.php` ✅ (VERSION ACTUELLE)
- **Statut** : Version de production active
- **Caractéristiques** :
  - Support complet des composants WhatsApp
  - Gestion des médias (URL et ID)
  - Logging détaillé
  - Compatible avec le frontend actuel
- **Utilisation** : C'est l'endpoint à utiliser

## Architecture Recommandée

### Restructuration Immédiate

1. **Renommer pour plus de clarté** :
   ```
   /api/whatsapp/send-template-v2.php → /api/whatsapp/send-template.php
   ```

2. **Archiver les anciennes versions** :
   ```
   /api/whatsapp/send-template.php → /archive/whatsapp/send-template-v1.php
   /api/whatsapp/send-template-simple.php → /tests/mocks/whatsapp-send-template-mock.php
   ```

3. **Créer une structure claire** :
   ```
   /public/api/whatsapp/
   ├── send-template.php          # Endpoint principal (ex v2)
   ├── upload.php                 # Upload de médias
   ├── webhook.php                # Webhook Meta
   └── status.php                 # Vérification de statut
   ```

### Configuration Frontend

Ajouter dans `/frontend/src/config/urls.ts` :

```typescript
/**
 * WhatsApp-related endpoints
 */
WHATSAPP: {
  /**
   * Base WhatsApp endpoint
   */
  BASE: () => `${API.BASE}/whatsapp`,
  
  /**
   * Send template message endpoint
   */
  SEND_TEMPLATE: () => `${API.WHATSAPP.BASE()}/send-template.php`,
  
  /**
   * Upload media endpoint
   */
  UPLOAD_MEDIA: () => `${API.WHATSAPP.BASE()}/upload.php`,
  
  /**
   * Webhook endpoint for Meta callbacks
   */
  WEBHOOK: () => `${API.WHATSAPP.BASE()}/webhook.php`,
  
  /**
   * Check message status
   */
  STATUS: () => `${API.WHATSAPP.BASE()}/status.php`,
}
```

## Format de Requête Standardisé

### Envoi de Template WhatsApp

**Endpoint** : `POST /api/whatsapp/send-template.php`

**Requête** :
```json
{
  "recipientPhoneNumber": "+2250700000000",
  "templateName": "hello_world",
  "templateLanguage": "fr",
  "bodyVariables": ["John", "Doe"],
  "headerMediaUrl": "https://example.com/image.jpg",
  "headerMediaId": "media_id_from_upload",
  "templateComponentsJsonString": "{...}" 
}
```

**Réponse** :
```json
{
  "success": true,
  "messageId": "wamid.xxxxx",
  "timestamp": "2025-05-27T10:00:00Z"
}
```

## Migration Plan

1. **Phase 1** : Documentation et tests (Immédiat)
   - Documenter le nouvel endpoint
   - Créer des tests d'intégration

2. **Phase 2** : Renommage et archivage (1 semaine)
   - Renommer send-template-v2.php → send-template.php
   - Archiver les anciennes versions
   - Mettre à jour les références frontend

3. **Phase 3** : Nettoyage (2 semaines)
   - Supprimer les fichiers obsolètes après validation
   - Mettre à jour la documentation complète

## Notes de Sécurité

- Tous les endpoints doivent vérifier l'authentification
- Valider les numéros de téléphone au format E.164
- Limiter le taux de requêtes pour éviter le spam
- Logger toutes les tentatives d'envoi pour l'audit