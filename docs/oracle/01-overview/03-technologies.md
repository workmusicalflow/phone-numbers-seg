# Technologies utilisées dans le projet Oracle

Oracle est construit en utilisant un ensemble de technologies modernes et éprouvées, choisies pour leur fiabilité, leur performance et leur facilité d'utilisation. Cette section présente les principales technologies utilisées dans le projet.

## Backend

### PHP 8.1

PHP est le langage principal utilisé pour le backend de l'application. Nous utilisons PHP 8.1 pour bénéficier des fonctionnalités modernes du langage, notamment :

- Types de retour et types d'arguments
- Classes anonymes
- Opérateur de fusion null (`??`)
- Opérateur de propagation (`...`)
- Fonctions fléchées
- Propriétés typées
- Promotion des propriétés du constructeur
- Enums

### SQLite

SQLite est utilisé comme système de gestion de base de données pour sa simplicité, sa portabilité et sa fiabilité. Les avantages de SQLite pour ce projet incluent :

- Aucune configuration de serveur nécessaire (base de données fichier)
- Facilité de déploiement et de sauvegarde
- Performance excellente pour des charges modérées
- Support des transactions ACID
- Zéro-configuration et maintenance minimale

Le projet était initialement configuré pour utiliser MySQL, mais a été migré vers SQLite pour simplifier le déploiement et le développement.

### Composer

Composer est utilisé pour la gestion des dépendances PHP. Les principales dépendances incluent :

- **PHPUnit** : Pour les tests unitaires
- **GraphQLite** : Pour l'implémentation de l'API GraphQL
- **Monolog** : Pour la journalisation
- **Guzzle** : Pour les requêtes HTTP vers l'API Orange SMS

## Frontend

### Vue.js

Vue.js 3 est utilisé pour le frontend moderne de l'application. Nous sommes en train de migrer progressivement de HTMX/Alpine.js vers Vue.js pour bénéficier de :

- Réactivité et performances améliorées
- Composants réutilisables
- Écosystème riche et mature
- Support TypeScript natif
- Composition API pour une meilleure organisation du code

### Quasar Framework

Quasar est utilisé comme framework UI pour Vue.js, offrant :

- Un ensemble complet de composants UI
- Support pour le développement responsive
- Thèmes et personnalisation avancée
- Performances optimisées

### TypeScript

TypeScript est utilisé pour le développement frontend afin d'améliorer la qualité du code et la productivité :

- Typage statique pour détecter les erreurs à la compilation
- Meilleure autocomplétion et documentation dans l'IDE
- Refactoring plus sûr
- Interfaces et types pour une meilleure documentation du code

### Pinia

Pinia est utilisé comme solution de gestion d'état pour Vue.js, remplaçant Vuex :

- API plus simple et intuitive
- Support TypeScript natif
- Meilleure intégration avec Vue 3 et la Composition API
- Devtools intégrés pour le débogage

### Vite

Vite est utilisé comme outil de build pour le frontend, offrant :

- Démarrage instantané du serveur de développement
- Rechargement à chaud ultra-rapide
- Optimisation de production efficace
- Support natif pour TypeScript, JSX, CSS et plus

## API

### REST

Une API REST traditionnelle est implémentée pour la compatibilité avec les clients existants et pour les opérations simples. Les caractéristiques de l'API REST incluent :

- Endpoints RESTful suivant les conventions standard
- Réponses JSON formatées de manière cohérente
- Validation des entrées et gestion des erreurs
- Documentation des endpoints

### GraphQL

GraphQL est implémenté en parallèle de l'API REST pour offrir plus de flexibilité aux clients. Les avantages de GraphQL pour ce projet incluent :

- Requêtes précises pour éviter le sur-fetching et le sous-fetching
- Requêtes multiples en une seule demande
- Typage fort avec un schéma auto-documenté
- Évolution de l'API sans versionnement

## Intégrations

### API Orange SMS

L'application intègre l'API Orange SMS pour l'envoi de SMS. Les caractéristiques de cette intégration incluent :

- Authentification OAuth2
- Envoi de SMS individuels et en masse
- Suivi des statuts d'envoi
- Gestion des erreurs et des réponses
- Historique complet des SMS envoyés

### AWS SES

L'application intègre AWS SES pour l'envoi d'emails. Les caractéristiques de cette intégration incluent :

- Envoi d'emails transactionnels et en masse
- Gestion des rebonds et des plaintes
- Suivi des statistiques d'envoi
- Utilisation de templates HTML

## Outils de développement

### Git

Git est utilisé pour le contrôle de version, avec les pratiques suivantes :

- Branches fonctionnelles pour les nouvelles fonctionnalités
- Pull requests pour la revue de code
- Commits atomiques avec messages descriptifs
- Tags pour les versions

### PHPUnit

PHPUnit est utilisé pour les tests unitaires du backend :

- Tests des services et repositories
- Mocks et stubs pour isoler les composants
- Assertions pour vérifier le comportement attendu
- Couverture de code pour mesurer la qualité des tests

### Vitest

Vitest est utilisé pour les tests unitaires du frontend :

- Compatible avec l'écosystème Vue.js
- Exécution rapide des tests
- Support natif de TypeScript
- API compatible avec Jest

### ESLint et Prettier

ESLint et Prettier sont utilisés pour maintenir la qualité et la cohérence du code :

- ESLint pour l'analyse statique et la détection des problèmes
- Prettier pour le formatage automatique du code
- Configuration personnalisée pour suivre les meilleures pratiques

## Conclusion

Le choix des technologies pour Oracle a été guidé par plusieurs principes :

1. **Modernité** : Utilisation des dernières versions des frameworks et bibliothèques
2. **Maintenabilité** : Choix de technologies avec une bonne documentation et une communauté active
3. **Performance** : Optimisation pour une expérience utilisateur fluide
4. **Évolutivité** : Architecture permettant l'ajout facile de nouvelles fonctionnalités
5. **Simplicité** : Préférence pour des solutions simples et éprouvées

Ces technologies forment une base solide pour le développement et l'évolution du projet Oracle, permettant de répondre efficacement aux besoins actuels et futurs.
