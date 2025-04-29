# Plan d'implémentation d'un système de file d'attente pour l'envoi SMS

## Résumé

Suite à notre audit du système d'envoi de SMS, nous avons identifié des problèmes significatifs lors de l'envoi en masse de SMS (en groupe, en bloc, ou à tous les contacts). Les erreurs du type "Error: Unable to obtain access token" et autres problèmes de fiabilité justifient la mise en place d'un système robuste de file d'attente.

Ce document présente un plan d'implémentation étape par étape pour introduire un système de file d'attente d'envoi SMS, avec pour objectifs:
- Améliorer la fiabilité des envois en masse
- Optimiser l'utilisation de l'API Orange
- Introduire des mécanismes de reprise sur erreur
- Maintenir la compatibilité avec les interfaces existantes

## Analyse du problème

### Problèmes identifiés

1. **Surcharge des requêtes d'authentification**: Chaque appel à `sendSMS()` génère une nouvelle demande de token à l'API Orange (`getAccessToken()`).
2. **Absence de mise en cache des tokens**: Le token est demandé pour chaque SMS, alors qu'il reste valide pendant un certain temps.
3. **Envoi synchrone massif**: Les SMS sont envoyés en boucle de manière synchrone, sans limitation de débit ni gestion avancée des erreurs.
4. **Absence de mécanisme de reprise**: En cas d'erreur pendant un envoi massif, les SMS restants ne sont pas automatiquement reprogrammés.

### Architecture actuelle

La chaîne d'appel pour l'envoi de SMS est actuellement:

```
GraphQL (Resolver) → SMSService.sendBulkSMS() → OrangeAPIClient.sendSMS() → OrangeAPIClient.getAccessToken()
```

Chaque appel à `OrangeAPIClient.sendSMS()` génère une nouvelle demande de token via `getAccessToken()`, créant un goulot d'étranglement et risquant de déclencher des limitations d'API.

### Types d'envois massifs concernés

1. Envoi groupé à une liste spécifique de numéros (`sendBulkSMS`)
2. Envoi à tous les numéros d'un segment (`sendSMSToSegment`)
3. Envoi à tous les contacts d'un utilisateur (`sendToAllContacts`)

## Architecture proposée

Nous proposons de mettre en place une architecture de file d'attente persistante qui:

1. Met en cache les tokens d'authentification
2. Enregistre les SMS à envoyer dans une file d'attente
3. Traite les envois de manière asynchrone avec un worker dédié
4. Gère les erreurs et retente les envois avec une stratégie de backoff exponentiel
5. Fournit des statistiques et une interface d'administration

![Architecture SMS Queue](https://via.placeholder.com/800x400?text=Architecture+SMS+Queue)

## Plan d'implémentation

### Phase 1: Conception du modèle de file d'attente et de l'entité

| ID | Tâche | Description | Priorité | Estimation |
|----|------|-------------|----------|------------|
| 1.1 | Créer l'entité SMSQueue | Implémenter l'entité avec les champs: id, phoneNumber, message, userId, status, createdAt, lastAttemptAt, nextAttemptAt, attempts, priority, segmentId, errorMessage | Haute | 2h |
| 1.2 | Définir les états de la file d'attente | Définir les statuts (PENDING, PROCESSING, SENT, FAILED, CANCELLED) et leurs transitions | Haute | 1h |
| 1.3 | Créer l'interface Repository | Implémenter l'interface avec les méthodes: save, findById, findByStatus, updateStatus, increaseAttemptCount, findExpiredProcessing, findNextBatch | Haute | 2h |
| 1.4 | Implémenter le Repository Doctrine | Créer l'implémentation concrète du repository avec Doctrine ORM | Haute | 3h |
| 1.5 | Créer les migrations de base de données | Préparer les scripts de migration pour la nouvelle table sms_queue | Haute | 2h |

### Phase 2: Service de gestion de cache des tokens

| ID | Tâche | Description | Priorité | Estimation |
|----|------|-------------|----------|------------|
| 2.1 | Créer l'interface TokenCacheInterface | Définir l'interface avec les méthodes: getToken, storeToken, isTokenValid, invalidateToken | Haute | 1h |
| 2.2 | Implémenter le FileTokenCache | Créer une implémentation basée sur des fichiers pour le cache de tokens | Haute | 2h |
| 2.3 | Modifier OrangeAPIClient | Mettre à jour le client pour utiliser le service de cache de tokens | Haute | 2h |
| 2.4 | Tests unitaires du TokenCache | S'assurer que le cache fonctionne correctement dans différents scénarios | Haute | 2h |
| 2.5 | Intégrer le TokenCache dans l'injection de dépendances | Mettre à jour la configuration DI | Moyenne | 1h |

### Phase 3: Service de file d'attente SMS

| ID | Tâche | Description | Priorité | Estimation |
|----|------|-------------|----------|------------|
| 3.1 | Créer l'interface SMSQueueServiceInterface | Définir les méthodes: enqueue, enqueueBulk, enqueueSegment, enqueueAllContacts, processNextBatch | Haute | 2h |
| 3.2 | Implémenter le SMSQueueService | Créer le service qui gère l'ajout et le traitement des SMS dans la file d'attente | Haute | 4h |
| 3.3 | Mettre à jour SMSService | Modifier les méthodes existantes pour utiliser la file d'attente | Haute | 3h |
| 3.4 | Intégrer la journalisation | Ajouter une journalisation complète pour le suivi des opérations | Moyenne | 2h |
| 3.5 | Tests d'intégration du service | Vérifier l'interaction entre SMSService et SMSQueueService | Haute | 3h |

### Phase 4: Worker pour le traitement de la file d'attente

| ID | Tâche | Description | Priorité | Estimation |
|----|------|-------------|----------|------------|
| 4.1 | Créer le SMSQueueWorker | Développer la classe qui effectue le traitement des SMS en attente | Haute | 4h |
| 4.2 | Implémenter la stratégie de backoff | Ajouter une logique de backoff exponentiel pour les réessais | Haute | 2h |
| 4.3 | Ajouter la limitation de débit | Mettre en place un mécanisme de rate limiting pour respecter les limites de l'API | Haute | 2h |
| 4.4 | Développer le script CLI | Créer un script exécutable pour le worker | Haute | 2h |
| 4.5 | Configurer les tâches cron | Mettre en place la configuration cron pour l'exécution périodique | Moyenne | 1h |
| 4.6 | Mécanisme de verrouillage | Implémenter un verrou pour éviter les exécutions concurrentes | Moyenne | 2h |

### Phase 5: Interface d'administration et statistiques

| ID | Tâche | Description | Priorité | Estimation |
|----|------|-------------|----------|------------|
| 5.1 | Créer les types GraphQL | Définir les types pour la file d'attente SMS | Basse | 2h |
| 5.2 | Implémenter les résolveurs | Ajouter les résolveurs pour l'administration de la file d'attente | Basse | 3h |
| 5.3 | Ajouter des statistiques | Implémenter des méthodes pour collecter des statistiques | Basse | 3h |
| 5.4 | Interface de gestion | Ajouter une interface pour visualiser et gérer la file d'attente | Basse | 4h |
| 5.5 | Documentation utilisateur | Documenter le fonctionnement et les APIs | Basse | 2h |

## Plan de mise en œuvre progressive

Pour minimiser les risques et garantir une mise en œuvre sans régression, nous recommandons une approche progressive:

### Étape 1: Mise en cache des tokens (Gain rapide)
- Implémenter le TokenCacheService
- Mettre à jour OrangeAPIClient
- Tester en production avec le code existant

Cette première étape aura un impact minimal sur le code existant tout en résolvant une partie importante du problème.

### Étape 2: Infrastructure de file d'attente
- Créer l'entité SMSQueue et son repository
- Implémenter le SMSQueueService de base
- Préparer le SMSQueueWorker initial
- Effectuer des tests approfondis dans un environnement de staging

### Étape 3: Déploiement progressif
- Mode hybride: envoi direct pour les SMS uniques, file d'attente pour les envois en masse
- Transition progressive vers la file d'attente par type d'envoi
- Surveillance étroite des performances et des erreurs

### Étape 4: Déploiement complet
- Transition complète vers le système de file d'attente
- Mise à jour de tous les points d'entrée (GraphQL, API, etc.)
- Formation des utilisateurs sur les nouvelles fonctionnalités

### Étape 5: Optimisations et évolutivité
- Amélioration des stratégies de reprise
- Optimisation des performances du worker
- Développement des outils d'administration

## Mesures de traçabilité

Pour chaque tâche du plan d'implémentation, nous mettrons en place:

1. **Gestion de branches Git**: Création d'une branche dédiée par fonctionnalité
2. **Tests automatisés**: Tests unitaires et d'intégration pour chaque composant
3. **Documentation**: Mise à jour de la documentation technique
4. **Revues de code**: Validation par un autre développeur avant fusion
5. **Métriques de performance**: Mesures avant/après pour quantifier les améliorations

## Critères de succès

Le succès de l'implémentation sera mesuré par:

1. Réduction de 95% des erreurs "Unable to obtain access token"
2. Amélioration du taux de réussite des envois en masse à >99%
3. Temps de réponse perçu par l'utilisateur réduit de 50% pour les opérations d'envoi massif
4. Capacité à traiter des volumes 3x plus importants sans dégradation des performances

## Prochaines étapes

1. Validation du plan par l'équipe technique
2. Allocation des ressources nécessaires
3. Planification détaillée du sprint
4. Début de la Phase 1

---

## Annexe: Code d'exemple pour les implémentations clés

### TokenCacheService

```php
<?php

namespace App\Services;

class TokenCacheService implements TokenCacheInterface
{
    private string $cacheFile;
    private int $tokenLifetime = 3600; // 1 heure par défaut

    public function __construct(string $cacheDir = null)
    {
        $this->cacheFile = ($cacheDir ?? sys_get_temp_dir()) . '/orange_api_token.cache';
    }

    public function getToken(): ?string
    {
        if (!file_exists($this->cacheFile)) {
            return null;
        }

        $data = json_decode(file_get_contents($this->cacheFile), true);
        if (!$data || !isset($data['token']) || !isset($data['expires_at'])) {
            return null;
        }

        // Vérifier si le token est toujours valide (avec une marge de sécurité de 5 minutes)
        if ($data['expires_at'] - 300 < time()) {
            return null;
        }

        return $data['token'];
    }

    public function storeToken(string $token, int $expiresIn): void
    {
        $data = [
            'token' => $token,
            'expires_at' => time() + $expiresIn,
        ];

        file_put_contents($this->cacheFile, json_encode($data));
    }

    public function isTokenValid(): bool
    {
        return $this->getToken() !== null;
    }

    public function invalidateToken(): void
    {
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }
}
```

### SMSQueue Entity

```php
<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="sms_queue")
 */
class SMSQueue
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string")
     */
    private string $phoneNumber;

    /**
     * @ORM\Column(type="text")
     */
    private string $message;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $userId;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $segmentId;

    /**
     * @ORM\Column(type="string")
     */
    private string $status = 'PENDING'; // PENDING, PROCESSING, SENT, FAILED, CANCELLED

    /**
     * @ORM\Column(type="datetime")
     */
    private \DateTime $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $lastAttemptAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $nextAttemptAt;

    /**
     * @ORM\Column(type="integer")
     */
    private int $attempts = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private int $priority = 0;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $errorMessage;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $messageId;

    // Getters and setters...
}
```

### Worker Script

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\GraphQL\DIContainer;
use App\Services\SMSQueueService;
use App\Services\SMSQueueWorker;

// Éviter les exécutions concurrentes
$lockFile = sys_get_temp_dir() . '/sms_queue_worker.lock';
if (file_exists($lockFile)) {
    $pid = file_get_contents($lockFile);
    if (posix_kill($pid, 0)) {
        echo "Worker already running with PID: $pid\n";
        exit(0);
    }
}

// Créer le fichier de verrou avec le PID actuel
file_put_contents($lockFile, getmypid());

try {
    // Initialiser le conteneur DI
    $container = new DIContainer();
    $worker = $container->get(SMSQueueWorker::class);
    
    // Options de ligne de commande
    $options = getopt('', ['batch-size:', 'max-runtime:', 'delay:']);
    $batchSize = $options['batch-size'] ?? 50;
    $maxRuntime = $options['max-runtime'] ?? 300; // 5 minutes par défaut
    $delay = $options['delay'] ?? 1; // 1 seconde entre les lots
    
    // Exécuter le worker
    $worker->run($batchSize, $maxRuntime, $delay);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
} finally {
    // Nettoyer le verrou à la fin
    if (file_exists($lockFile)) {
        unlink($lockFile);
    }
}
```