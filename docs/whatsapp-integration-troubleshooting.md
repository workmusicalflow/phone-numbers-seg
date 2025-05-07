# Résolution des problèmes d'intégration WhatsApp

Ce document décrit les problèmes rencontrés lors de l'intégration de l'API WhatsApp Business Cloud et leurs solutions.

## Problème d'interface dans le repository

### Problème
Incompatibilité entre la signature de la méthode `search` dans `WhatsAppMessageRepository` et `SearchRepositoryInterface`.

**Erreur** :
```
Declaration of App\Repositories\Interfaces\WhatsApp\WhatsAppMessageRepositoryInterface::search(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array must be compatible with App\Repositories\Interfaces\SearchRepositoryInterface::search(string $query, ?array $fields = null, ?int $limit = null, ?int $offset = null): array
```

### Solution
1. Mise à jour de la signature de la méthode `search` dans `WhatsAppMessageRepository` pour accepter un paramètre string au lieu d'un tableau
2. Modification de l'implémentation pour effectuer une recherche textuelle
3. Création d'une nouvelle méthode `findByCriteria` pour conserver la fonctionnalité originale de recherche par critères

## Problème de propriété EntityManager

### Problème
Accès direct à la propriété `entityManager` dans `WhatsAppMessageRepository` au lieu d'utiliser la méthode getter héritée.

**Erreur** :
```
Warning: Undefined property: App\Repositories\Doctrine\WhatsApp\WhatsAppMessageRepository::$entityManager
```

### Solution
Remplacement de toutes les occurrences de `$this->entityManager` par `$this->getEntityManager()` pour utiliser la méthode héritée de `BaseRepository`.

## Dépendance GuzzleHttp manquante

### Problème
La classe `Client` de GuzzleHttp était référencée mais la bibliothèque n'était pas installée.

**Erreur** :
```
Fatal error: Uncaught Error: Class "GuzzleHttp\Client" not found
```

### Solution
Installation de la bibliothèque GuzzleHttp/Guzzle via Composer :
```bash
composer require guzzlehttp/guzzle
```

## Configuration des migrations d'entités

### Problème
Les annotations DocBlock de Doctrine ne fonctionnaient pas avec PHP 8.3 et Doctrine ORM récent.

**Erreur** :
```
Class "App\Entities\WhatsApp\WhatsAppMessage" is not a valid entity or mapped super class.
```

### Solution
Mise à jour de l'entité `WhatsAppMessage` pour utiliser les attributs PHP 8 au lieu des annotations DocBlock :

```php
// Ancien style
/**
 * @ORM\Entity
 * @ORM\Table(name="whatsapp_messages")
 */

// Nouveau style
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: "whatsapp_messages")]
```

## Configuration et lancement du webhook

### Étapes de configuration
1. Création des tables nécessaires avec le script `create-whatsapp-tables.php`
2. Lancement du serveur PHP sur le port 8000
3. Exposition du serveur local via Localtunnel avec la commande :
   ```
   npx localtunnel --port 8000 --subdomain oracle-whatsapp-webhook
   ```
4. Configuration du webhook dans le tableau de bord Meta avec :
   - URL : `https://oracle-whatsapp-webhook.loca.lt/whatsapp/webhook.php`
   - Token de vérification : `oracle_whatsapp_webhook_verification_token`
   - Champs d'abonnement : `messages`

## Améliorations pour le débogage

### Journalisation des webhooks
- Création d'un dossier spécifique pour les logs WhatsApp : `/var/logs/whatsapp`
- Modification du webhook pour toujours journaliser les payloads entrants pendant la phase de développement
- Format de nommage des fichiers log : `webhook_YYYY-MM-DD_HH-II-SS.json`

## Tests effectués avec succès

1. Vérification du webhook (challenge de validation)
2. Réception et traitement de messages texte
3. Persistance des messages en base de données

## Prochaines étapes

1. Configuration des abonnements webhook supplémentaires pour recevoir divers types de notifications
2. Implémentation des fonctionnalités pour envoyer des messages template
3. Intégration avec le système existant de gestion des contacts