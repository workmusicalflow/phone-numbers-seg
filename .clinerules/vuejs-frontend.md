# Vue.js Frontend pour l'Application de Segmentation de Numéros de Téléphone

## Vue d'ensemble

Le frontend de l'application est développé avec Vue.js 3, TypeScript, Quasar Framework et Apollo Client pour GraphQL. Cette architecture moderne permet de créer une interface utilisateur réactive et performante qui communique efficacement avec le backend PHP via GraphQL.

## Structure du projet

```
frontend/
├── public/             # Fichiers statiques
├── src/
│   ├── assets/         # CSS global et autres ressources
│   ├── components/     # Composants Vue réutilisables
│   ├── router/         # Configuration de Vue Router
│   ├── stores/         # Stores Pinia pour la gestion d'état
│   ├── views/          # Composants de page
│   ├── App.vue         # Composant racine
│   └── main.ts         # Point d'entrée de l'application
├── .eslintrc.js        # Configuration ESLint
├── .prettierrc.js      # Configuration Prettier
├── index.html          # Page HTML principale
├── package.json        # Dépendances et scripts
├── tsconfig.json       # Configuration TypeScript
└── vite.config.ts      # Configuration Vite
```

## Technologies clés

- **Vue.js 3** avec Composition API pour une meilleure organisation du code et une réutilisation plus facile
- **TypeScript** pour un typage statique et une meilleure maintenabilité
- **Quasar Framework** pour des composants UI riches et une expérience utilisateur cohérente
- **Pinia** pour une gestion d'état simple et efficace
- **Vue Router** pour la navigation entre les différentes vues
- **Apollo Client** pour les requêtes GraphQL vers le backend

## Composants principaux

### Stores

- **phoneStore.ts** : Gestion des numéros de téléphone (récupération, ajout, suppression)
  - Interface `PhoneNumber` mise à jour pour inclure les champs `civility`, `firstName`, `name`, `company`, `sector` et `notes`
  - Requêtes GraphQL mises à jour pour inclure ces nouveaux champs
  - Méthode `addPhoneNumber` étendue pour accepter ces nouveaux champs en paramètres
- **segmentStore.ts** : Gestion des segments personnalisés (récupération, ajout, mise à jour, suppression)

### Composants réutilisables

- **PhoneNumberCard.vue** : Affichage d'un numéro de téléphone et de ses segments
  - Affichage des informations de contact (civilité, prénom, nom, entreprise)
  - Formatage intelligent des informations de contact
  - Affichage conditionnel basé sur la présence d'informations
- **CustomSegmentForm.vue** : Formulaire pour ajouter ou modifier un segment personnalisé
- **SearchBar.vue** : Barre de recherche réutilisable avec debounce
- **BasePagination.vue** : Composant de pagination pour les listes
- **ConfirmDialog.vue** : Dialogue de confirmation pour les actions importantes
- **NotificationService.ts** : Service pour afficher des notifications à l'utilisateur
- **LoadingOverlay.vue** : Overlay de chargement pour les opérations longues

### Vues

- **Home.vue** : Page d'accueil avec présentation des fonctionnalités
- **Segment.vue** : Segmentation individuelle d'un numéro de téléphone
  - Formulaire mis à jour pour inclure les champs civilité, prénom, nom et entreprise
  - Utilisation de la mutation GraphQL pour créer un numéro avec ces informations
- **Batch.vue** : Traitement par lot de plusieurs numéros
- **Segments.vue** : Gestion des segments personnalisés
- **SMS.vue** : Envoi de SMS
- **Import.vue** : Import/Export de données
  - Documentation mise à jour pour indiquer les colonnes CSV supportées (incluant civilité et prénom)

## Communication avec le backend

L'application communique avec le backend PHP via GraphQL. Les requêtes et mutations sont définies dans les stores Pinia et utilisées par les composants Vue. Apollo Client gère le cache et les requêtes réseau.

Exemple de requête GraphQL pour récupérer les numéros de téléphone avec les nouveaux champs :

```graphql
query GetPhoneNumbers {
  phoneNumbers {
    id
    number
    createdAt
    civility
    firstName
    name
    company
    sector
    notes
    segments {
      id
      type
      value
    }
  }
}
```

Exemple de mutation pour créer un numéro de téléphone avec les nouveaux champs :

```graphql
mutation CreatePhoneNumber(
  $number: String!
  $civility: String
  $firstName: String
  $name: String
  $company: String
  $sector: String
  $notes: String
) {
  createPhoneNumber(
    number: $number
    civility: $civility
    firstName: $firstName
    name: $name
    company: $company
    sector: $sector
    notes: $notes
  ) {
    id
    number
    createdAt
    civility
    firstName
    name
    company
    sector
    notes
    segments {
      id
      type
      value
    }
  }
}
```

## Fonctionnalités implémentées

- Interface utilisateur complète avec Quasar Framework
- Navigation entre les différentes vues avec Vue Router
- Gestion d'état avec Pinia
- Communication avec le backend via GraphQL
- Formulaires pour la saisie et la validation des données
- Affichage des numéros de téléphone et de leurs segments
- Gestion des segments personnalisés
- Composants réutilisables pour l'interface utilisateur
- Tests unitaires pour les composants et les stores
- Support complet pour les champs civilité et prénom dans tous les composants pertinents

## Tests

Les tests unitaires ont été implémentés pour les composants et les stores principaux :

### Tests de composants

- `PhoneNumberCard.spec.ts` : Teste le rendu et le comportement du composant d'affichage des numéros de téléphone
- `SearchBar.spec.ts` : Teste les fonctionnalités de recherche et les événements émis
- `NotificationService.spec.ts` : Teste le service de notification pour les différents types de messages

### Tests de stores

- `phoneStore.spec.ts` : Teste les fonctionnalités du store de gestion des numéros de téléphone (récupération, ajout, suppression)

Les tests utilisent Vitest comme framework de test et Vue Test Utils pour tester les composants Vue. Tous les tests ont été corrigés et passent avec succès.

## Bonnes pratiques implémentées

- **Typage strict** : Utilisation de TypeScript pour définir des interfaces claires pour les données
- **Composition API** : Organisation du code en composables réutilisables
- **Séparation des préoccupations** : Séparation claire entre la logique métier (stores) et l'interface utilisateur (composants)
- **Tests unitaires** : Couverture de tests pour les composants et les stores principaux
- **Gestion d'état centralisée** : Utilisation de Pinia pour gérer l'état global de l'application
- **Composants réutilisables** : Création de composants génériques pour éviter la duplication de code
- **Validation des données** : Validation des entrées utilisateur avant envoi au serveur
- **Gestion des erreurs** : Affichage de messages d'erreur clairs en cas de problème

## Prochaines étapes

- Amélioration de l'interface utilisateur
- Augmentation de la couverture des tests
- Optimisation des performances (lazy loading, code splitting)
- Ajout de fonctionnalités avancées (filtrage, tri, recherche)
- Internationalisation (i18n) pour supporter plusieurs langues
- Amélioration de l'accessibilité
- Implémentation de l'export des données en CSV et Excel
