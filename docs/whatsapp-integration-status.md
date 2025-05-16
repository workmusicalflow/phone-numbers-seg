# État de l'intégration WhatsApp

## Tables de base de données

### 1. `whatsapp_messages` (ancienne)
- Table originale probablement d'une première implémentation
- Actuellement vide
- À migrer vers `whatsapp_message_history`

### 2. `whatsapp_message_history` 
- Table principale pour l'historique des messages
- Structure complète avec support pour :
  - Messages entrants et sortants
  - Différents types de messages (TEXT, IMAGE, etc.)
  - Suivi du statut (SENT, DELIVERED, READ, FAILED)
  - Association avec utilisateurs et contacts
- Contient actuellement des données de test

### 3. `whatsapp_queue`
- File d'attente pour l'envoi de messages
- Structure différente de l'entité créée :
  - Utilise `recipient_phone` au lieu de `phone_number`
  - Utilise `message_content` au lieu de `payload`
  - Utilise `attempts/maxAttempts` au lieu de `retry_count/max_retries`
- Gère les priorités et les tentatives de réenvoi

### 4. `whatsapp_templates`
- Templates globaux du système
- Structure simple avec nom, langue, catégorie et composants
- Contient des templates de test

### 5. `whatsapp_user_templates`
- Templates personnalisés par utilisateur
- Actuellement vide
- Permet la personnalisation par utilisateur

## Entités et Repositories créés

### Entités
- `WhatsAppMessageHistory` ✅
- `WhatsAppTemplate` ✅
- `WhatsAppQueue` ✅ (mais incompatible avec le schéma de table actuel)

### Repositories
- `WhatsAppMessageHistoryRepository` ✅
- `WhatsAppTemplateRepository` ✅
- `WhatsAppQueueRepository` ✅

### Services
- `WhatsAppApiClient` ✅
- `WhatsAppService` ✅

## Migration nécessaire

La table `whatsapp_messages` est vide mais pourrait contenir des données à migrer vers `whatsapp_message_history` dans le futur.

## Problèmes identifiés

1. **Incompatibilité de schéma** : L'entité `WhatsAppQueue` ne correspond pas au schéma de la table `whatsapp_queue`
2. **Entités non enregistrées** : Les entités WhatsApp ne sont pas correctement enregistrées dans Doctrine

## Prochaines étapes

1. Corriger l'entité `WhatsAppQueue` pour correspondre au schéma existant
2. Enregistrer correctement les entités WhatsApp dans Doctrine
3. Compléter l'intégration GraphQL (tâche #6)
4. Créer l'interface frontend pour WhatsApp

## Données de test créées

- 2 templates dans `whatsapp_templates`
- 2 messages dans `whatsapp_message_history`
- 1 message dans `whatsapp_queue`
- 0 templates dans `whatsapp_user_templates`