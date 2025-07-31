# Tests Frontend pour Oracle SMS

Ce répertoire contient les tests pour le frontend de l'application Oracle SMS. Il comprend à la fois des tests automatisés et un plan de tests manuels pour valider les fonctionnalités essentielles de l'application.

## Structure des Tests

```
tests/
├── e2e/                  # Tests end-to-end avec Playwright
│   └── essential-operations.spec.js  # Tests des opérations essentielles
├── manual-test-plan.md   # Plan de tests manuels
└── README.md             # Ce fichier
```

## Tests Automatisés (E2E)

Les tests E2E utilisent [Playwright](https://playwright.dev/) pour automatiser les interactions utilisateur et valider le comportement de l'application dans un navigateur réel.

### Prérequis

- Node.js (v14 ou supérieur)
- npm (v6 ou supérieur)

### Installation

```bash
# Installer les dépendances
npm install --save-dev @playwright/test

# Installer les navigateurs nécessaires
npx playwright install --with-deps
```

### Exécution des Tests

```bash
# Exécuter tous les tests E2E
npx playwright test

# Exécuter un test spécifique
npx playwright test essential-operations.spec.js

# Exécuter les tests en mode UI
npx playwright test --ui

# Exécuter les tests et générer un rapport HTML
npx playwright test --reporter=html
```

### Opérations Testées

Les tests E2E couvrent les opérations essentielles suivantes:

1. **Gestion de l'historique SMS**
   - Vider l'historique SMS

2. **Gestion des contacts**
   - Ajouter un contact
   - Modifier un contact
   - Supprimer un contact

3. **Gestion des groupes**
   - Créer un groupe
   - Modifier un groupe
   - Supprimer un groupe

4. **Gestion des contacts dans les groupes**
   - Ajouter un contact à un groupe
   - Supprimer un contact d'un groupe

Ces tests sont exécutés pour les deux utilisateurs principaux: Admin et AfricaQSHE.

## Tests Manuels

Le fichier `manual-test-plan.md` contient un plan détaillé pour tester manuellement les mêmes fonctionnalités. Ce plan est utile pour:

- Valider les fonctionnalités qui sont difficiles à automatiser
- Effectuer des tests exploratoires
- Vérifier l'expérience utilisateur globale
- Compléter les tests automatisés avec des vérifications visuelles

## Intégration avec les Tests Backend

Ces tests frontend complètent les tests backend qui valident l'implémentation Doctrine ORM. Ensemble, ils assurent que:

1. Les opérations CRUD fonctionnent correctement au niveau de la base de données
2. Les API GraphQL exposent correctement ces opérations
3. L'interface utilisateur permet d'effectuer ces opérations de manière intuitive

## Maintenance des Tests

Pour maintenir les tests à jour:

1. Mettre à jour les sélecteurs CSS dans les tests E2E lorsque la structure HTML change
2. Ajouter de nouveaux tests lorsque de nouvelles fonctionnalités sont ajoutées
3. Mettre à jour le plan de tests manuels pour refléter les changements dans l'interface utilisateur

## Résolution des Problèmes

Si les tests échouent, vérifier:

1. Que le serveur frontend est en cours d'exécution sur le port 8080
2. Que le serveur backend est en cours d'exécution et accessible
3. Que la base de données contient les données nécessaires
4. Que les utilisateurs de test (Admin et AfricaQSHE) existent avec les bons mots de passe
