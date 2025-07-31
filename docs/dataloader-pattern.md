# Pattern DataLoader - Documentation

## Introduction

Le pattern DataLoader a été implémenté pour résoudre le problème N+1 dans notre API GraphQL, en particulier pour les requêtes qui impliquent des relations entre entités, comme les contacts et leurs groupes.

## Le Problème N+1

Le problème N+1 est un anti-pattern courant dans les APIs GraphQL (et plus généralement dans les ORMs) où, pour récupérer une liste d'entités et leurs relations, le système effectue :
1. Une requête initiale pour récupérer N entités principales
2. N requêtes supplémentaires (une pour chaque entité) pour récupérer les relations associées

Par exemple, avec notre requête GraphQL `contacts { groups { ... } }` :
- 1 requête pour récupérer tous les contacts
- N requêtes individuelles (une par contact) pour récupérer les groupes de chaque contact

Ce problème génère une charge excessive sur la base de données et dégrade les performances.

## La Solution : DataLoader

Le pattern DataLoader, popularisé par Facebook, offre une solution élégante à ce problème en:
1. Regroupant (batching) plusieurs requêtes individuelles en une seule requête optimisée
2. Mettant en cache les résultats pendant la durée d'une requête GraphQL

Dans notre implémentation :
1. Pour chaque résolution de champ `groups` dans un objet Contact, nous utilisons un DataLoader
2. Au lieu d'exécuter immédiatement la requête, le DataLoader ajoute l'ID du contact à une file d'attente
3. À la fin du cycle d'exécution JavaScript (ou dans notre cas PHP, immédiatement), le DataLoader exécute une seule requête optimisée avec une clause `WHERE contact_id IN (...)` pour récupérer les groupes de tous les contacts en une seule fois
4. Les résultats sont ensuite réorganisés pour correspondre à chaque contact d'origine

## Notre Implémentation

Notre implémentation comprend :

1. **Classe DataLoader de base** (`App\GraphQL\DataLoaders\DataLoader`)
   - Une implémentation générique du pattern DataLoader
   - Fournit les fonctions `load()` et `loadMany()` pour le batching
   - Maintient un cache par requête

2. **DataLoader spécifique** (`App\GraphQL\DataLoaders\ContactGroupDataLoader`)
   - Implémentation spécifique pour les groupes de contacts
   - S'appuie sur les repositories existants mais optimise les requêtes

3. **Intégration au DI Container**
   - Configuration dans `src/config/di/dataloaders.php`
   - Assure qu'une seule instance est utilisée par requête GraphQL

4. **Utilisation dans le Resolver**
   - Injection du DataLoader dans `ContactResolver`
   - Remplacement de l'implémentation non optimisée par l'appel au DataLoader

## Bénéfices

L'implémentation du pattern DataLoader apporte plusieurs avantages :

1. **Performance améliorée** : Réduction drastique du nombre de requêtes à la base de données
2. **Mise à l'échelle** : Le système peut désormais gérer efficacement des charges plus importantes
3. **Réduction de la charge sur la base de données** : Moins de connexions et de requêtes
4. **Temps de réponse plus rapide** : Les utilisateurs bénéficient d'une API plus réactive

## Exemples d'Utilisation

### Avant (Problème N+1)

```php
// Dans le resolver de champ Contact.groups
public function resolveContactGroups(array $contact): array {
    $contactId = $contact['id'];
    $memberships = $this->membershipRepository->findByContactId($contactId); // Une requête par contact
    $groupIds = array_map(fn($m) => $m->getGroupId(), $memberships);
    $groups = $this->groupRepository->findByIds($groupIds, $userId);
    return $this->formatter->formatGroups($groups);
}
```

### Après (Avec DataLoader)

```php
// Dans le resolver de champ Contact.groups
public function resolveContactGroups(array $contact): array {
    $contactId = $contact['id'];
    $this->contactGroupDataLoader->setUserId($userId);
    return $this->contactGroupDataLoader->load($contactId); // Batching automatique
}
```

## Conclusion

Le pattern DataLoader est une solution puissante pour résoudre les problèmes de performance liés aux requêtes N+1 dans les API GraphQL. Notre implémentation personnalisée offre une solution adaptée à notre architecture, sans dépendances externes supplémentaires.

Nous envisageons d'étendre cette approche à d'autres relations dans notre schéma GraphQL qui pourraient bénéficier de cette optimisation.