# Guide de Résilience WhatsApp

## Vue d'ensemble

Le système WhatsApp a été amélioré avec des mécanismes de résilience pour garantir une haute disponibilité et une meilleure expérience utilisateur, même en cas de défaillances temporaires de l'API Meta.

## Composants de Résilience

### 1. Circuit Breaker Pattern

Le Circuit Breaker protège contre les pannes en cascade et évite d'appeler un service défaillant.

#### États du Circuit Breaker

- **CLOSED** (Fermé) : Fonctionnement normal, toutes les requêtes passent
- **OPEN** (Ouvert) : Service considéré comme défaillant, toutes les requêtes sont rejetées immédiatement
- **HALF_OPEN** (Semi-ouvert) : Phase de test pour vérifier si le service est revenu

#### Configuration

```php
// Configuration par défaut
$circuitBreaker = new CircuitBreaker(
    name: 'whatsapp_api',
    failureThreshold: 5,     // Ouvre après 5 échecs consécutifs
    successThreshold: 2,     // Ferme après 2 succès en HALF_OPEN
    timeout: 60              // Tente de rouvrir après 60 secondes
);
```

#### Comportement

1. **État initial** : CLOSED
2. **Après 5 échecs consécutifs** : Passe à OPEN
3. **En état OPEN** : Rejette toutes les requêtes pendant 60 secondes
4. **Après 60 secondes** : Passe à HALF_OPEN lors de la prochaine requête
5. **En HALF_OPEN** :
   - Si échec : Retourne immédiatement à OPEN
   - Si 2 succès consécutifs : Retourne à CLOSED

### 2. Retry Policy

Le mécanisme de retry gère les erreurs temporaires avec un backoff exponentiel.

#### Configuration

```php
$retryPolicy = new RetryPolicy(
    maxAttempts: 3,          // Maximum 3 tentatives
    baseDelayMs: 1000,       // Délai initial de 1 seconde
    multiplier: 2.0,         // Double le délai à chaque tentative
    maxDelayMs: 10000        // Maximum 10 secondes de délai
);
```

#### Stratégie de Retry

1. **1ère tentative** : Immédiate
2. **2ème tentative** : Après ~1 seconde (± jitter)
3. **3ème tentative** : Après ~2 secondes (± jitter)

#### Exceptions Retryables

- `RuntimeException`
- `GuzzleHttp\Exception\ConnectException`
- `GuzzleHttp\Exception\ServerException`
- Codes HTTP : 429 (Rate Limit), 502, 503, 504

### 3. ResilientWhatsAppClient

Intègre Circuit Breaker et Retry Policy pour tous les appels API.

```php
$resilientClient = new ResilientWhatsAppClient(
    httpClient: $guzzleClient,
    circuitBreaker: $circuitBreaker,
    retryPolicy: $retryPolicy,
    logger: $logger
);
```

## Utilisation

### Service avec Résilience

```php
// Injection dans le service
$whatsappService = new WhatsAppServiceWithResilience(
    resilientClient: $resilientClient,
    // ... autres dépendances
);

// Utilisation normale
try {
    $messageHistory = $whatsappService->sendTemplateMessage(
        user: $user,
        recipient: '+33612345678',
        templateName: 'welcome_message',
        languageCode: 'fr'
    );
} catch (CircuitBreakerOpenException $e) {
    // Le service est temporairement indisponible
    echo "Service WhatsApp temporairement indisponible";
}
```

### Gestion des Templates avec Cache

```php
// Récupère les templates avec fallback automatique sur cache
$templates = $whatsappService->getApprovedTemplates();

// En cas d'indisponibilité, retourne automatiquement
// les templates en cache (jusqu'à 24h)
```

## Monitoring et Logs

### Logs Générés

```log
// Circuit Breaker ouvert
[CRITICAL] WhatsApp service is down (circuit open) {
    "user_id": 123,
    "recipient": "+33612345678",
    "template": "welcome_message"
}

// Retry en cours
[WARNING] Retrying operation {
    "attempt": 2,
    "delay_ms": 2000,
    "error": "Connection timeout"
}

// Utilisation du cache
[WARNING] Using cached templates due to circuit breaker open
```

### Métriques à Surveiller

1. **Circuit Breaker State** : Nombre de fois où le circuit s'ouvre
2. **Retry Count** : Nombre de retries effectués
3. **Success Rate** : Taux de succès après retries
4. **Response Time** : Temps de réponse incluant les retries

## Configuration Avancée

### Personnalisation par Environnement

```php
// Production : Plus tolérant
$productionConfig = [
    'circuit_breaker' => [
        'failure_threshold' => 10,
        'success_threshold' => 3,
        'timeout' => 120
    ],
    'retry' => [
        'max_attempts' => 5,
        'base_delay_ms' => 2000
    ]
];

// Développement : Plus strict
$devConfig = [
    'circuit_breaker' => [
        'failure_threshold' => 3,
        'success_threshold' => 1,
        'timeout' => 30
    ],
    'retry' => [
        'max_attempts' => 2,
        'base_delay_ms' => 500
    ]
];
```

### Stockage Persistant du Circuit Breaker

Pour un environnement multi-serveurs :

```php
class RedisCircuitBreakerStore implements CircuitBreakerStateStore
{
    private Redis $redis;
    
    public function getState(string $name): CircuitBreakerState
    {
        $data = $this->redis->get("circuit_breaker:$name");
        return $data ? CircuitBreakerState::fromArray(json_decode($data, true)) : new CircuitBreakerState();
    }
    
    public function setState(string $name, CircuitBreakerState $state): void
    {
        $this->redis->setex(
            "circuit_breaker:$name",
            300, // TTL de 5 minutes
            json_encode($state->toArray())
        );
    }
}
```

## Bonnes Pratiques

### 1. Ne Pas Désactiver en Production

Les mécanismes de résilience protègent l'infrastructure. Ne jamais les désactiver complètement.

### 2. Ajuster les Seuils

Commencer avec des valeurs conservatrices et ajuster selon les métriques observées.

### 3. Tester les Scénarios d'Échec

```php
// Test unitaire
public function testHandlesApiOutage(): void
{
    // Simuler une panne de l'API
    $this->mockClient->shouldReceive('post')
        ->andThrow(new ConnectException('Connection failed'));
    
    // Vérifier que le circuit s'ouvre après le seuil
    for ($i = 0; $i < 5; $i++) {
        try {
            $this->service->sendTemplateMessage(...);
        } catch (\Exception $e) {
            // Expected
        }
    }
    
    // La prochaine tentative devrait échouer immédiatement
    $this->expectException(CircuitBreakerOpenException::class);
    $this->service->sendTemplateMessage(...);
}
```

### 4. Implémenter des Fallbacks

```php
// Exemple de fallback pour les templates
if ($templates === []) {
    // Utiliser des templates par défaut codés en dur
    $templates = [
        ['name' => 'welcome_message', 'language' => 'fr'],
        ['name' => 'order_confirmation', 'language' => 'fr']
    ];
}
```

## Dépannage

### Circuit Breaker Toujours Ouvert

1. Vérifier les logs pour identifier la cause racine
2. Tester manuellement l'API Meta
3. Vérifier les credentials et permissions
4. Réinitialiser manuellement si nécessaire : `$circuitBreaker->reset()`

### Trop de Retries

1. Réduire `maxAttempts` si les délais deviennent trop longs
2. Ajuster les exceptions retryables
3. Implémenter un timeout global

### Performance Dégradée

1. Vérifier que le cache des templates fonctionne
2. Monitorer les temps de réponse avec retries
3. Considérer l'augmentation des ressources serveur

## Évolutions Futures

1. **Bulkhead Pattern** : Isoler les ressources par type d'opération
2. **Rate Limiting** : Limiter le nombre de requêtes par utilisateur
3. **Adaptive Retry** : Ajuster dynamiquement les paramètres selon les conditions
4. **Health Checks** : Endpoints de santé pour monitoring externe