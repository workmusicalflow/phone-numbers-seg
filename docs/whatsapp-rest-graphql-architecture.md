# Architecture mixte REST-GraphQL pour l'intégration WhatsApp

## Problématique

L'intégration actuelle avec l'API Cloud de Meta pour WhatsApp Business via GraphQL présente plusieurs défis :

1. Problèmes de robustesse face aux erreurs et aux indisponibilités de l'API Meta
2. Erreurs GraphQL difficiles à déboguer : `Cannot return null for non-nullable field "Query.fetchApprovedWhatsAppTemplates"`
3. Complexité importante liée aux multiples couches d'abstraction
4. Difficulté à garantir des valeurs non-nulles dans le schéma GraphQL

## Solution proposée : Architecture mixte REST-GraphQL

### Principes fondamentaux

1. **REST pour les interactions externes** : Utiliser une API REST comme couche d'abstraction pour toutes les communications avec Meta
2. **GraphQL pour l'interface utilisateur** : Conserver GraphQL pour la logique métier et l'interface avec le frontend
3. **Garantie de non-nullité** : S'assurer que l'API REST renvoie toujours des structures valides, même vides
4. **Gestion d'erreur robuste** : Capture des erreurs au niveau REST avant qu'elles n'atteignent GraphQL

### Avantages

- Séparation claire des responsabilités
- Meilleure résilience face aux problèmes de l'API Meta
- Facilité de débogage (REST est plus simple à tester)
- Possibilité de mise en cache des réponses de l'API Meta
- Réduction des erreurs GraphQL liées aux valeurs nulles

## Architecture technique

### 1. API REST WhatsApp

Créer une nouvelle couche d'API REST dédiée à WhatsApp avec les endpoints suivants :

```
GET    /api/whatsapp/templates            # Récupérer tous les templates
GET    /api/whatsapp/templates/{id}       # Récupérer un template spécifique
POST   /api/whatsapp/send                 # Envoyer un message WhatsApp
POST   /api/whatsapp/send-template        # Envoyer un template WhatsApp
POST   /api/whatsapp/upload-media         # Uploader un média pour WhatsApp
GET    /api/whatsapp/media/{id}           # Récupérer un média
```

### 2. Service d'abstraction

Implémenter un service d'abstraction qui :
- Communique avec l'API Meta Cloud
- Gère les erreurs et timeouts
- Garantit des réponses valides même en cas d'erreur
- Met en cache les templates pour améliorer la performance et la disponibilité

### 3. Resolvers GraphQL simplifiés

Modifier les resolvers GraphQL pour qu'ils :
- Appellent l'API REST interne au lieu de l'API Meta directement
- N'aient plus à se soucier de la gestion des erreurs d'API externe
- Garantissent des types non-null conformes au schéma

### 4. Mécanisme de fallback

Implémenter un système de fallback qui :
- Stocke localement une copie des templates récents
- Fournit ces templates en cas d'indisponibilité de l'API Meta
- Signale clairement à l'utilisateur quand des données de secours sont utilisées

## Plan d'implémentation

### Phase 1 : Mise en place de l'API REST

1. Créer le contrôleur REST WhatsApp
2. Implémenter les endpoints de base pour les templates et l'envoi de messages
3. Ajouter un système de mise en cache des réponses
4. Mettre en place la gestion d'erreur robuste

### Phase 2 : Refonte des Resolvers GraphQL

1. Modifier les resolvers pour utiliser la nouvelle API REST
2. Ajuster le schéma GraphQL si nécessaire
3. Mettre à jour les tests

### Phase 3 : Nettoyage et optimisation

1. Supprimer les solutions de contournement temporaires
2. Optimiser les performances
3. Améliorer la documentation

## Exemple d'implémentation

### Contrôleur REST

```php
<?php

namespace App\Controllers\API;

use App\Services\WhatsApp\WhatsAppTemplateService;
use Psr\Log\LoggerInterface;

class WhatsAppRestController
{
    private WhatsAppTemplateService $templateService;
    private LoggerInterface $logger;

    public function __construct(
        WhatsAppTemplateService $templateService,
        LoggerInterface $logger
    ) {
        $this->templateService = $templateService;
        $this->logger = $logger;
    }

    public function getTemplates(): array
    {
        try {
            $templates = $this->templateService->getApprovedTemplates();
            
            return [
                'success' => true,
                'data' => $templates ?: [] // Garantit un tableau même vide
            ];
        } catch (\Exception $e) {
            // Log l'erreur mais renvoie une réponse valide
            $this->logger->error("Erreur API Meta: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            // Tenter de récupérer des templates de secours
            $fallbackTemplates = $this->templateService->getFallbackTemplates();
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => $fallbackTemplates, // Templates de secours
                'isFallback' => true
            ];
        }
    }
    
    public function sendTemplate(array $data): array
    {
        try {
            $result = $this->templateService->sendTemplate(
                $data['recipient'],
                $data['templateName'],
                $data['language'],
                $data['components'] ?? []
            );
            
            return [
                'success' => true,
                'messageId' => $result['messageId'] ?? null,
                'error' => null
            ];
        } catch (\Exception $e) {
            $this->logger->error("Erreur envoi template: " . $e->getMessage());
            
            return [
                'success' => false,
                'messageId' => null,
                'error' => $e->getMessage()
            ];
        }
    }
}
```

### Resolver GraphQL

```php
<?php

namespace App\GraphQL\Resolvers\WhatsApp;

use App\Services\WhatsApp\WhatsAppRestClient;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class WhatsAppTemplateResolver
{
    private WhatsAppRestClient $restClient;
    
    public function __construct(WhatsAppRestClient $restClient)
    {
        $this->restClient = $restClient;
    }
    
    #[Query(name: "fetchApprovedWhatsAppTemplates")]
    #[Logged]
    public function fetchApprovedWhatsAppTemplates(): array
    {
        // Appel au service REST interne
        $response = $this->restClient->get('/api/whatsapp/templates');
        
        // Convertir les modèles de données REST en types GraphQL
        return array_map(
            fn($template) => new WhatsAppTemplateSafeType($template),
            $response['data'] ?? []
        );
    }
}
```

## Monitoring et métriques

Pour suivre les performances et la fiabilité de cette nouvelle architecture :

1. **Métriques de performance** :
   - Temps de réponse de l'API Meta
   - Taux de succès/échec des appels à l'API Meta
   - Utilisation du cache et ratio de hit/miss

2. **Alertes** :
   - Notification en cas d'indisponibilité prolongée de l'API Meta
   - Avertissement si le taux d'utilisation du fallback dépasse un seuil

3. **Journalisation** :
   - Enregistrement détaillé des erreurs d'API
   - Traçage des requêtes pour faciliter le débogage

## Conclusion

Cette architecture mixte REST-GraphQL offre le meilleur des deux mondes :
- La puissance et la flexibilité de GraphQL pour l'interface utilisateur
- La simplicité et la robustesse de REST pour l'intégration avec des API externes

Ce design permet une meilleure séparation des préoccupations, facilite la maintenance et améliore significativement la résilience face aux problèmes potentiels de l'API Meta.