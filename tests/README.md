# Tests pour le projet Oracle

Ce répertoire contient les tests automatisés pour le projet Oracle.

## Structure des tests

Les tests sont organisés par catégorie :

- `Models/` : Tests pour les modèles (entités)
- `Services/` : Tests pour les services
- `Repositories/` : Tests pour les repositories
- `WhatsApp/` : Tests spécifiques aux fonctionnalités WhatsApp
- `Integration/` : Tests d'intégration

## Configuration

La configuration des tests se trouve dans les fichiers suivants :

- `phpunit.xml` : Configuration de PHPUnit
- `bootstrap.php` : Script d'initialisation des tests
- `TestCase.php` : Classe de base pour tous les tests

## Exécution des tests

Pour exécuter tous les tests :

```bash
vendor/bin/phpunit
```

Pour exécuter une suite de tests spécifique :

```bash
vendor/bin/phpunit --testsuite WhatsApp
```

Pour exécuter un fichier de test spécifique :

```bash
vendor/bin/phpunit tests/WhatsApp/WhatsAppMonitoringServiceTest.php
```

## Fixtures

Les fixtures de test se trouvent dans le répertoire `Fixtures/`. Elles fournissent des données de test réutilisables.

## Utilitaires

Le répertoire `Utils/` contient des traits et classes utilitaires pour les tests, comme des assertions personnalisées.

## Mocks et services de test

Le répertoire `Services/` contient des services spécifiques aux tests, comme un service de base de données en mémoire.

## Base de données de test

Les tests utilisent une base de données SQLite en mémoire pour les tests d'intégration. La base de données est recréée pour chaque test.

## Couverture de code

Pour générer un rapport de couverture de code :

```bash
vendor/bin/phpunit --coverage-html coverage
```

Le rapport sera généré dans le répertoire `coverage/`.