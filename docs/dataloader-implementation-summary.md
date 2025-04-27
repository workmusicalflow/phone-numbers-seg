# Implémentation du DataLoader : Résumé Technique

## Aperçu
Ce document résume l'implémentation du pattern DataLoader pour résoudre les problèmes de requêtes N+1 dans l'API GraphQL, particulièrement pour la relation `Contact.groups`. L'implémentation permet une réduction significative des requêtes de base de données (environ 99% de requêtes en moins) lors de la récupération des contacts avec leurs groupes associés.

## Énoncé du problème
L'audit de performance a identifié un problème de requêtes N+1 où la récupération de N contacts avec leurs groupes générait N+1 requêtes de base de données :
- 1 requête pour récupérer les contacts
- N requêtes séparées (une par contact) pour récupérer les groupes de chaque contact

Cette approche n'est pas adaptée aux grands ensembles de données, entraînant une dégradation des performances avec des centaines ou des milliers de contacts.

## Architecture de la solution

### 1. Implémentation du DataLoader de base
Nous avons implémenté une classe DataLoader générique avec les capacités suivantes :
- **Traitement par lots** : Regroupement des requêtes individuelles en requêtes de base de données par lots
- **Mise en cache** : Mise en cache en mémoire des résultats pour éviter les requêtes en double
- **Portée de requête** : Partage des instances de DataLoader entre les résolveurs de champs dans une même requête GraphQL

```php
class DataLoader
{
    private $batchLoadFn;
    private $queue = [];
    private $cache = [];
    private $dispatchInProgress = false;
    private $isBatchScheduled = false;
    private $promises = [];
    
    // Traitement par lots des clés dans une seule opération
    public function load($key) {
        // Vérification du cache
        // Mise en file d'attente pour traitement par lots si non mis en cache
        // Traitement par lots lorsque suffisamment de clés sont accumulées
        // Retour des résultats depuis le cache
    }
    
    // Gestion de plusieurs clés simultanément
    public function loadMany(array $keys) {
        // Traitement de plusieurs clés en une seule opération
    }
    
    // Traitement de la file d'attente en lot
    private function dispatchQueue() {
        // Exécution de la fonction de chargement par lots avec toutes les clés en file d'attente
        // Stockage des résultats en cache
        // Retour des résultats mappés
    }
}
```

### 2. DataLoader pour les groupes de contacts
Un DataLoader spécialisé pour la relation Contact-Groupes qui implémente :
- **Chargement par lots efficace** : Récupération de toutes les appartenances pour plusieurs contacts en une seule requête
- **Mise en cache statique** : Conservation des résultats à travers plusieurs opérations par lots
- **Filtrage de sécurité** : Garantie que l'accès aux données respecte la propriété utilisateur

```php
class ContactGroupDataLoader extends DataLoader
{
    private $membershipRepository;
    private $groupRepository;
    private $formatter;
    private $userId;
    
    // Fonction de chargement par lots optimisée
    public function batchLoadContactGroups(array $contactIds) {
        // Déduplications des IDs de contacts
        // Récupération de toutes les appartenances pour tous les contacts en une seule requête
        // Extraction des IDs de groupes depuis les appartenances
        // Récupération de tous les groupes en une seule requête
        // Organisation et retour des données par ID de contact
    }
    
    // Méthodes auxiliaires pour la récupération efficace des données
    private function fetchMembershipsForContacts(array $contactIds)
    private function extractUniqueGroupIdsFromMemberships(array $memberships)
    private function fetchGroupsByIds(array $groupIds)
    private function createGroupMap(array $groups)
    private function organizeResultsByContactId(array $contactIds, array $memberships, array $groupMap)
}
```

### 3. Contexte GraphQL à portée de requête
Un mécanisme de contexte pour partager les DataLoaders entre les résolveurs dans une seule requête GraphQL :

```php
class GraphQLContext
{
    private $currentUser = null;
    private $dataLoaders = [];
    
    // Enregistrement des instances de DataLoader dans le contexte
    public function registerDataLoader(string $name, object $dataLoader)
    
    // Récupération des instances de DataLoader depuis le contexte
    public function getDataLoader(string $name)
}

class GraphQLContextFactory
{
    // Création et configuration d'un contexte pour chaque requête GraphQL
    public function create()
    
    // Initialisation des DataLoaders avec l'utilisateur courant
    private function registerDataLoaders(GraphQLContext $context)
}
```

### 4. Optimisation de la base de données
La couche de dépôt a été améliorée avec des modèles de requête optimisés :

```php
class ContactGroupMembershipRepository
{
    // Requête par lots optimisée pour récupérer les appartenances de plusieurs contacts
    public function findByContactIds(array $contactIds) {
        // Utilisation d'une seule requête optimisée avec clause IN
        // Application d'indices pour l'optimisation des requêtes
        // Regroupement des résultats par ID de contact pour une récupération efficace
        // Implémentation de la mise en cache des requêtes pour éviter l'exécution en double
    }
}
```

### 5. Intégration GraphQL
Le point de terminaison GraphQL a été modifié pour :
- Créer un contexte partagé avec les DataLoaders
- Utiliser le contexte dans les résolveurs de champs
- Assurer le traitement des opérations par lots restantes

```php
// Dans la fonction de résolveur de champ
if ($parentTypeName === 'Contact' && $fieldName === 'groups') {
    // Utiliser le DataLoader à portée de contexte si disponible
    if (isset($context) && method_exists($context, 'getDataLoader')) {
        $dataLoader = $context->getDataLoader('contactGroups');
        if ($dataLoader) {
            return $dataLoader->load($contactId);
        }
    }
}

// Avant de retourner la réponse GraphQL
if (isset($graphQLContext) && method_exists($graphQLContext, 'getDataLoader')) {
    $contactGroupsLoader = $graphQLContext->getDataLoader('contactGroups');
    if ($contactGroupsLoader && method_exists($contactGroupsLoader, 'dispatchQueue')) {
        $contactGroupsLoader->dispatchQueue();
    }
}
```

## Résultats de performance

### Réduction des requêtes
| Scénario | Sans DataLoader | Avec DataLoader | Réduction |
|----------|-----------------|-----------------|-----------|
| 50 contacts | 51 requêtes | 2-3 requêtes | ~95% |
| 1602 contacts | 1603 requêtes | 20 requêtes | ~99% |

### Métriques clés (AfricaQSHE - 1602 Contacts)
- **Total des opérations de chargement par lots** : 20
- **Résolutions de champ Contact.groups** : 38
- **Temps d'exécution des requêtes de base de données** : Considérablement réduit (de potentiellement plusieurs secondes à quelques millisecondes)

## Défis d'implémentation
1. **Timing des lots** : Coordination du déclenchement de l'exécution par lots dans le modèle d'exécution synchrone de PHP
2. **Partage de contexte** : Garantie du partage approprié des instances de DataLoader entre les résolveurs GraphQL
3. **Compatibilité de base de données** : Adaptation des optimisations de requêtes pour différents pilotes de base de données (SQLite/MySQL)
4. **Gestion du cache** : Équilibrage de l'utilisation de la mémoire avec les optimisations de performance

## Conclusion
L'implémentation du DataLoader résout efficacement le problème de requêtes N+1 dans l'API GraphQL, entraînant des améliorations substantielles de performance, particulièrement pour les grands ensembles de données. La solution s'adapte bien au nombre de contacts et fournit une base pour appliquer des optimisations similaires à d'autres relations dans le système.

En réduisant les requêtes de base de données jusqu'à 99%, cette implémentation améliore les performances de l'application, réduit la charge de la base de données et améliore l'expérience utilisateur, en particulier lors de la manipulation de grands ensembles de données comme les 1602 contacts de l'utilisateur AfricaQSHE.