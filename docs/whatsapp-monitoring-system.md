# Système de Monitoring et Métriques WhatsApp

Ce document décrit le système de monitoring et de métriques pour l'API WhatsApp implémenté dans Oracle.

## Vue d'ensemble

Le système de monitoring WhatsApp permet de :

1. Collecter et stocker des métriques de performance pour les opérations de l'API WhatsApp
2. Suivre l'utilisation des templates WhatsApp
3. Analyser les erreurs et problèmes les plus fréquents
4. Fournir un tableau de bord pour la visualisation des données
5. Générer des alertes pour les problèmes critiques

## Architecture

Le système est composé des éléments suivants :

- **WhatsAppMonitoringService** : Service principal qui fournit des méthodes pour collecter et analyser les métriques
- **WhatsAppApiMetric** : Entité qui stocke les métriques de performance de l'API
- **WhatsAppApiMetricRepository** : Repository pour persister et récupérer les métriques
- **WhatsAppMonitoringController** : Contrôleur GraphQL qui expose les métriques via l'API

## Métriques collectées

### 1. Métriques de performance API

Ces métriques mesurent la performance des opérations de l'API WhatsApp :

- Durée d'exécution (en ms)
- Taux de réussite
- Distribution par opération
- Percentiles de durée (P95, P99)

### 2. Métriques d'utilisation des templates

Ces métriques suivent l'utilisation des templates WhatsApp :

- Nombre total d'utilisations
- Utilisation par template
- Utilisation par catégorie
- Utilisation par langue
- Taux de réussite par template

### 3. Métriques d'erreur

Ces métriques permettent d'analyser les erreurs de l'API :

- Nombre total d'erreurs
- Distribution par type d'erreur
- Distribution par opération
- Erreurs critiques

## Implémentation des métriques

Les métriques sont collectées automatiquement dans les clients et services WhatsApp grâce à l'ajout de code de monitoring dans les méthodes clés :

```php
try {
    // Code d'opération
    $success = true;
} catch (\Exception $e) {
    $success = false;
    $errorMessage = $e->getMessage();
    throw $e;
} finally {
    // Enregistrer les métriques de performance
    $this->monitoringService->recordApiPerformance(
        $user,
        'operationName',
        $duration,
        $success,
        $success ? null : $errorMessage
    );
}
```

## Gestion des erreurs et alertes

Le système peut générer plusieurs types d'alertes :

1. **Taux d'erreur élevé** : Alerte lorsque le taux d'erreur dépasse 10% sur une période
2. **Latence élevée** : Alerte lorsque le P95 de la durée dépasse 2000ms
3. **Erreurs critiques** : Alerte pour toute erreur critique (authentification, dépassement de quota, etc.)
4. **Taux de réussite des messages bas** : Alerte lorsque le taux de succès est inférieur à 90%

## API GraphQL

Les métriques peuvent être consultées via l'API GraphQL avec les requêtes suivantes :

```graphql
# Métriques d'utilisation des templates
query {
  getWhatsAppTemplateUsageMetrics(
    startDate: "2025-05-01T00:00:00Z",
    endDate: "2025-05-21T23:59:59Z"
  ) {
    total_usage
    unique_templates
    template_usage {
      template_id
      template_name
      count
      success_rate
    }
    by_language
    by_category
  }
}

# Métriques de performance API
query {
  getWhatsAppApiPerformanceMetrics(
    startDate: "2025-05-01T00:00:00Z"
  ) {
    total_operations
    overall_success_rate
    avg_duration
    p95_duration
    by_operation {
      operation
      count
      avg_duration
      success_rate
    }
  }
}

# Dashboard complet
query {
  getWhatsAppMonitoringDashboard(period: "week") {
    key_metrics {
      message_success_rate
      api_success_rate
      total_messages
      total_templates_used
      critical_errors
    }
    top_templates
    templates_by_category
    api_errors_by_type
    recent_errors
  }
}

# Alertes actives
query {
  getWhatsAppActiveAlerts {
    type
    level
    message
    details
  }
}
```

## Configuration

Le système de monitoring est configuré via le système d'injection de dépendances dans le fichier `src/config/di/whatsapp.php`.

## Rétention des données

Les métriques API sont conservées pendant 30 jours par défaut. Les données plus anciennes sont automatiquement archivées ou supprimées pour éviter une croissance excessive de la base de données.

## Extension future

Le système peut être étendu avec :

1. Intégration avec des systèmes de monitoring externes (Prometheus, Datadog, etc.)
2. Visualisation des données via des tableaux de bord interactifs
3. Configuration des alertes par l'utilisateur
4. Rapports périodiques automatiques par email

## Dépannage

En cas de problèmes avec le système de monitoring :

1. Vérifier que le service `WhatsAppMonitoringService` est correctement injecté
2. S'assurer que les tables de métriques existent dans la base de données
3. Consulter les logs du système pour détecter les erreurs éventuelles