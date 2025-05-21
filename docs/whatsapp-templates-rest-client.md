# Client REST WhatsApp pour le Frontend

## Contexte

Suite aux problèmes de fiabilité rencontrés avec les resolvers GraphQL pour les templates WhatsApp, nous avons mis en place une API REST robuste avec des mécanismes de fallback. Ce document décrit le client TypeScript qui permet au frontend d'interagir avec cette nouvelle API.

## Architecture

Le client REST WhatsApp pour le frontend est une couche d'abstraction qui permet de:

1. **Communiquer avec l'API REST** - Fournit une interface TypeScript typée pour le frontend
2. **Gérer les erreurs** - Offre une gestion d'erreur cohérente et prévisible
3. **Transformer les données** - Adapte les réponses REST au format attendu par les composants

## Implémentation

### WhatsAppRestClient

La classe principale `WhatsAppRestClient` est implémentée dans `frontend/src/services/whatsappRestClient.ts` et fournit les méthodes suivantes:

- `getApprovedTemplates(filters)` - Récupère les templates WhatsApp approuvés avec filtrage
- `getTemplateById(templateId)` - Récupère un template spécifique par son ID
- `sendTemplateMessage(data)` - Envoie un message template WhatsApp
- `sendTemplateMessageWithComponents(data)` - Envoie un message template avancé
- `getTemplateUsageHistory(limit, offset)` - Récupère l'historique d'utilisation des templates

### Intégration avec le Store Pinia

Le client est intégré au store Pinia `whatsappTemplateStore.ts` pour:

1. Remplacer les requêtes GraphQL par des appels à l'API REST
2. Maintenir la compatibilité avec l'interface existante
3. Assurer une transition transparente pour les composants frontend

### Gestion des Erreurs

Le client REST implémente une stratégie de gestion d'erreur en trois niveaux:

1. **Niveau API** - Capture les erreurs HTTP et réseau
2. **Niveau Réponse** - Vérifie que les réponses sont au format attendu
3. **Niveau Client** - Fournit des réponses de fallback en cas d'erreur

Toutes les méthodes retournent soit des données valides, soit une structure d'erreur cohérente, jamais `null` ou `undefined`.

## Format des Réponses

### Templates Approuvés

```typescript
interface ApprovedTemplatesResponse {
  status: string;            // 'success' ou 'error'
  templates: WhatsAppTemplate[];
  count: number;             // Nombre total de templates
  meta: {
    source: 'api' | 'cache' | 'fallback';  // Source des données
    usedFallback: boolean;   // Indique si un fallback a été utilisé
    timestamp: string;       // Horodatage de la réponse
  };
  message?: string;          // Présent uniquement en cas d'erreur
}
```

### Template Spécifique

```typescript
interface TemplateResponse {
  status: string;            // 'success' ou 'error'
  template: WhatsAppTemplate | null;
  message?: string;          // Présent uniquement en cas d'erreur
}
```

### Historique d'Utilisation

```typescript
interface TemplateHistoryResponse {
  status: string;            // 'success' ou 'error'
  history: TemplateUsageHistory[];
  count: number;             // Nombre total d'entrées
  message?: string;          // Présent uniquement en cas d'erreur
}
```

## Tests

Un script de test est disponible pour vérifier le fonctionnement du client REST:
`scripts/test-whatsapp-rest-client.js`

Il permet de tester:
- La récupération de tous les templates
- Le filtrage des templates
- Le forçage du rafraîchissement
- La récupération d'un template spécifique

## Prochaines Étapes

1. **Couverture complète des fonctionnalités** - Ajouter des endpoints REST pour toutes les fonctionnalités GraphQL
2. **Métriques et monitoring** - Ajouter des métriques et du monitoring pour l'API REST
3. **Tests unitaires** - Ajouter des tests unitaires pour le client REST
4. **Typages avancés** - Améliorer les typages TypeScript pour une meilleure sécurité et auto-complétion