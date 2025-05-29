# Système d'Envoi en Masse WhatsApp

## Vue d'ensemble

Le système d'envoi en masse WhatsApp permet d'envoyer des messages template personnalisés à plusieurs destinataires de manière efficace et fiable. Il utilise l'API Meta Business Cloud et implémente une architecture basée sur le pattern Command avec gestion d'événements et monitoring en temps réel.

## Table des matières

1. [Architecture](#architecture)
2. [Configuration](#configuration)
3. [Composants principaux](#composants-principaux)
4. [Utilisation](#utilisation)
5. [API REST](#api-rest)
6. [Interface utilisateur](#interface-utilisateur)
7. [Gestion des erreurs](#gestion-des-erreurs)
8. [Tests](#tests)
9. [Dépannage](#dépannage)

## Architecture

### Vue d'ensemble de l'architecture

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│   Frontend      │────▶│  API REST        │────▶│  Command Bus    │
│   (Vue.js)      │     │  bulk-send.php   │     │                 │
└─────────────────┘     └──────────────────┘     └────────┬────────┘
                                                           │
                              ┌────────────────────────────┼────────────────────┐
                              │                            ▼                    │
                        ┌─────▼──────┐     ┌───────────────────┐     ┌─────────▼────────┐
                        │ BulkSend   │     │  EventDispatcher  │     │ WhatsApp Service │
                        │ Handler    │────▶│                   │     │ (Meta API)       │
                        └────────────┘     └───────────────────┘     └──────────────────┘
```

### Pattern Command

Le système utilise le pattern Command pour encapsuler les requêtes d'envoi en masse :

- **Command** : `BulkSendTemplateCommand` - Contient toutes les données nécessaires pour l'envoi
- **Handler** : `BulkSendHandler` - Traite la commande et orchestre l'envoi
- **CommandBus** : Middleware qui route les commandes vers les handlers appropriés
- **Result** : `BulkSendResult` - Encapsule les résultats de l'envoi

### Événements

Le système émet plusieurs événements durant le processus :

- `BulkSendStartedEvent` - Début de l'envoi en masse
- `BulkSendProgressEvent` - Progression de l'envoi
- `BatchProcessedEvent` - Batch de messages traité
- `BulkSendCompletedEvent` - Fin de l'envoi

## Configuration

### 1. Variables d'environnement (.env)

```env
# Meta WhatsApp Business API
WHATSAPP_APP_ID=1193922949108494
WHATSAPP_PHONE_NUMBER_ID=660953787095211
WHATSAPP_WABA_ID=664409593123173
WHATSAPP_API_VERSION=v22.0
WHATSAPP_ACCESS_TOKEN=EAAQ93dlFUw4BOZCu6...

# Application
APP_ENV=development
APP_URL=http://localhost:8000
VITE_API_BASE_URL=http://localhost:8000/api
VITE_GRAPHQL_ENDPOINT=http://localhost:8000/graphql.php
```

### 2. Configuration DI (Dependency Injection)

Le système utilise PHP-DI pour la gestion des dépendances. Configuration dans `src/config/di/whatsapp-bulk-send.php` :

```php
return [
    'whatsapp.command_bus.bulk' => \DI\factory(function(ContainerInterface $container) {
        $commandBus = new CommandBus($container->get(\Psr\Log\LoggerInterface::class));
        $commandBus->addMiddleware(new LoggingMiddleware(...));
        $commandBus->registerHandler(new BulkSendHandler(...));
        return $commandBus;
    }),
];
```

### 3. Configuration CORS

Important pour les requêtes avec credentials depuis le frontend :

```php
// public/api/whatsapp/bulk-send.php
CorsHelper::enableCors('http://localhost:5173'); // Origine spécifique requise
```

## Composants principaux

### Backend (PHP)

#### 1. BulkSendTemplateCommand

```php
class BulkSendTemplateCommand implements CommandInterface
{
    public function __construct(
        private User $user,
        private array $recipients,
        private string $templateName,
        private string $templateLanguage = 'fr',
        private array $bodyVariables = [],
        private array $headerVariables = [],
        private ?string $headerMediaUrl = null,
        private ?string $headerMediaId = null,
        private array $defaultParameters = [],
        private array $recipientParameters = [],
        private array $options = []
    ) {}
}
```

#### 2. BulkSendHandler

Le handler principal qui :
- Valide les destinataires
- Charge le template WhatsApp
- Envoie les messages par batch
- Gère les erreurs et retry
- Émet des événements de progression

```php
class BulkSendHandler implements HandlerInterface
{
    public function handle(CommandInterface $command): BulkSendResult
    {
        // 1. Validation
        // 2. Chargement du template
        // 3. Envoi par batch avec gestion d'erreurs
        // 4. Agrégation des résultats
    }
}
```

#### 3. WhatsApp Service

Service principal pour l'interaction avec l'API Meta :

```php
interface WhatsAppServiceInterface
{
    public function sendTemplate(
        string $phoneNumber,
        string $templateName,
        string $languageCode = 'en',
        array $components = []
    ): array;
}
```

### Frontend (Vue.js)

#### 1. BulkSendDialog.vue

Composant principal pour l'interface d'envoi en masse :

```vue
<template>
  <div class="bulk-send-dialog">
    <!-- Sélection du template -->
    <!-- Gestion des destinataires -->
    <!-- Personnalisation du message -->
    <!-- Monitoring de progression -->
  </div>
</template>
```

#### 2. whatsappBulkService.ts

Service TypeScript pour l'appel API :

```typescript
export class WhatsAppBulkService {
  async bulkSend(request: BulkSendRequest): Promise<BulkSendResponse> {
    return axios.post(API.WHATSAPP.BULK_SEND(), request, {
      withCredentials: true,
      headers: { 'Content-Type': 'application/json' }
    });
  }
}
```

#### 3. useBulkSend.ts

Composable Vue pour la logique d'envoi :

```typescript
export function useBulkSend() {
  // Gestion des destinataires
  // Validation des données
  // Suivi de progression
  // Gestion des erreurs
}
```

## Utilisation

### 1. Interface utilisateur

1. **Accéder à la page WhatsApp** : `/whatsapp`
2. **Cliquer sur "Envoi en masse"**
3. **Sélectionner un template** approuvé
4. **Ajouter des destinataires** :
   - Saisie manuelle
   - Import CSV
   - Sélection de groupes/segments
5. **Personnaliser le message** (si le template a des variables)
6. **Lancer l'envoi**

### 2. API REST

#### Endpoint : POST /api/whatsapp/bulk-send.php

**Requête** :
```json
{
  "recipients": ["+2250123456789", "+2250987654321"],
  "templateName": "hello_world",
  "templateLanguage": "fr",
  "bodyVariables": ["John", "Doe"],
  "headerVariables": [],
  "headerMediaUrl": "https://example.com/image.jpg",
  "defaultParameters": {},
  "recipientParameters": {
    "+2250123456789": {
      "name": "Alice"
    }
  },
  "options": {
    "batchSize": 10,
    "batchDelay": 1000,
    "continueOnError": true,
    "includeDetails": true
  }
}
```

**Réponse** :
```json
{
  "success": true,
  "message": "Envoi terminé",
  "data": {
    "totalSent": 2,
    "totalFailed": 0,
    "totalAttempted": 2,
    "successRate": 100,
    "errorSummary": {},
    "failedRecipients": []
  }
}
```

### 3. Exemple d'utilisation programmatique

```php
// Créer la commande
$command = new BulkSendTemplateCommand(
    $user,
    ['+2250123456789', '+2250987654321'],
    'welcome_message',
    'fr',
    ['Variable1', 'Variable2']
);

// Exécuter via CommandBus
$result = $commandBus->handle($command);

// Vérifier les résultats
if ($result->isSuccess()) {
    echo "Envoi réussi : {$result->getTotalSent()} messages envoyés";
} else {
    echo "Erreurs : " . json_encode($result->getErrorSummary());
}
```

## Gestion des erreurs

### Types d'erreurs

1. **Erreurs de validation** :
   - Numéro de téléphone invalide
   - Template non trouvé
   - Paramètres manquants

2. **Erreurs API Meta** :
   - Token expiré
   - Limite de taux dépassée
   - Template non approuvé

3. **Erreurs système** :
   - Problème de connexion
   - Timeout
   - Erreur serveur

### Stratégie de retry

Le système implémente un retry automatique pour certaines erreurs :

```php
private const MAX_RETRIES = 3;
private const RETRY_DELAY = 1000; // ms

// Retry pour les erreurs temporaires
if ($this->isRetryableError($error)) {
    sleep($this->calculateBackoff($attempt));
    return $this->sendMessage($recipient, $template, $attempt + 1);
}
```

## Tests

### Tests unitaires

```bash
# Exécuter tous les tests
vendor/bin/phpunit

# Tests spécifiques au bulk send
vendor/bin/phpunit tests/Services/WhatsApp/BulkSendHandlerTest.php
```

### Tests d'intégration

```bash
# Test complet avec API mock
vendor/bin/phpunit tests/Integration/BulkSendBasicTest.php
```

### Test manuel

1. Utiliser le fichier CSV de test : `docs/sample-bulk-recipients.csv`
2. Suivre le guide : `docs/test-manual-bulk-send.md`

## Dépannage

### Problèmes courants

#### 1. Erreur CORS

**Symptôme** : `Response to preflight request doesn't pass access control check`

**Solution** :
```php
// Vérifier que l'origine est spécifiée
CorsHelper::enableCors('http://localhost:5173'); // PAS '*'
```

#### 2. Erreur d'authentification

**Symptôme** : `{"error":"Non authentifié"}`

**Solution** :
- Vérifier la connexion dans le frontend
- Vérifier les cookies de session
- S'assurer que `withCredentials: true` est défini

#### 3. Template non trouvé

**Symptôme** : `Template 'xxx' not found or not approved`

**Solution** :
- Synchroniser les templates : `php scripts/sync-whatsapp-templates.php`
- Vérifier l'approbation dans Meta Business Suite

### Logs et debugging

Les logs sont disponibles dans :
- `logs/whatsapp_bulk_send.log` - Logs spécifiques aux envois en masse
- `logs/whatsapp_api.log` - Logs des appels API Meta
- `logs/bootstrap-rest-debug.log` - Logs du conteneur DI

### Scripts utiles

```bash
# Tester l'authentification
php test-bulk-send-with-auth.php

# Tester le système complet
php test-bulk-send-full.php

# Synchroniser les templates
php scripts/sync-whatsapp-templates.php
```

## Performances et limites

### Limites Meta API

- **Messages par seconde** : 80 msg/s (Business API)
- **Messages par jour** : Selon le tier de votre compte
- **Taille du batch** : Recommandé 10-50 messages

### Optimisations

1. **Batching** : Envoi par lots configurables
2. **Délai entre batches** : Évite la saturation
3. **Queue asynchrone** : Pour les gros volumes (à implémenter)

### Monitoring

Le système fournit des métriques en temps réel :
- Progression globale
- Taux de réussite/échec
- Débit (messages/minute)
- Temps restant estimé

## Évolutions futures

1. **Queue asynchrone** avec Redis/RabbitMQ
2. **Webhooks** pour les statuts de livraison
3. **Planification** d'envois différés
4. **Analytics** avancées
5. **A/B Testing** de templates

---

*Documentation maintenue à jour le 29/05/2025*