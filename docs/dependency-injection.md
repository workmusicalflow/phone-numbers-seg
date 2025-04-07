# Injection de Dépendances avec PHP-DI

Ce document décrit l'implémentation de l'injection de dépendances dans l'application Oracle à l'aide de PHP-DI.

## Vue d'ensemble

L'application utilise désormais PHP-DI comme conteneur d'injection de dépendances, remplaçant l'implémentation précédente `SimpleContainer`. Cette amélioration permet :

- Une gestion plus robuste des dépendances
- L'autowiring (résolution automatique des dépendances)
- Une meilleure performance grâce à la compilation
- Une configuration centralisée des services

## Structure

L'implémentation comprend les composants suivants :

1. **Configuration centralisée** : `src/config/di.php`

   - Définit toutes les dépendances de l'application
   - Utilise des fonctions de fabrique pour créer les instances
   - Associe les interfaces à leurs implémentations

2. **Conteneur DIContainer** : `src/GraphQL/DIContainer.php`

   - Implémente l'interface PSR-11 `ContainerInterface`
   - Charge la configuration depuis `src/config/di.php`
   - Fournit une méthode de compatibilité `set()` pour la transition depuis `SimpleContainer`

3. **Intégration avec GraphQLite** : `src/GraphQL/GraphQLiteConfiguration.php`
   - Utilise `DIContainer` au lieu de `SimpleContainer`
   - Simplifie la création du schéma GraphQL

## Utilisation

### Obtenir une instance du conteneur

```php
$container = new \App\GraphQL\DIContainer();
```

### Récupérer un service

```php
// Par interface
$phoneSegmentationService = $container->get(\App\Services\Interfaces\PhoneSegmentationServiceInterface::class);

// Par classe concrète
$phoneNumberRepository = $container->get(\App\Repositories\PhoneNumberRepository::class);
```

### Ajouter une définition au conteneur

La méthode recommandée est d'ajouter la définition dans `src/config/di.php` :

```php
return [
    // Autres définitions...

    MonService::class => function(ContainerInterface $c) {
        return new MonService(
            $c->get(MaDependance::class)
        );
    },

    // Association interface-implémentation
    MonInterface::class => DI\get(MonService::class),
];
```

Pour la compatibilité avec le code existant, vous pouvez également utiliser la méthode `set()` :

```php
$container->set(MonService::class, $monServiceInstance);
```

## Avantages

### Autowiring

PHP-DI peut résoudre automatiquement les dépendances en fonction des types de paramètres du constructeur :

```php
class MonService {
    public function __construct(MaDependance $dependance) {
        // ...
    }
}

// PHP-DI résoudra automatiquement MaDependance
$service = $container->get(MonService::class);
```

### Performance

En production, PHP-DI peut compiler les définitions pour améliorer les performances :

```php
if (getenv('APP_ENV') === 'production') {
    $builder->enableCompilation(__DIR__ . '/../../var/cache');
    $builder->writeProxiesToFile(true, __DIR__ . '/../../var/cache/proxies');
}
```

### Testabilité

L'injection de dépendances facilite les tests en permettant de remplacer facilement les dépendances par des mocks :

```php
// Dans un test
$mockDependance = $this->createMock(MaDependance::class);
$service = new MonService($mockDependance);
```

## Bonnes pratiques

1. **Préférer l'injection par constructeur** à l'injection par setter ou propriété
2. **Injecter des interfaces** plutôt que des implémentations concrètes
3. **Centraliser les définitions** dans `src/config/di.php`
4. **Utiliser l'autowiring** quand c'est possible
5. **Documenter les dépendances** dans les PHPDoc des constructeurs

## Migration depuis SimpleContainer

Si vous avez du code qui utilise encore `SimpleContainer`, vous pouvez le migrer vers `DIContainer` en remplaçant :

```php
$container = new SimpleContainer();
```

par :

```php
$container = new DIContainer();
```

La méthode `set()` est maintenue pour la compatibilité, mais il est recommandé de migrer vers la configuration centralisée dans `src/config/di.php`.
