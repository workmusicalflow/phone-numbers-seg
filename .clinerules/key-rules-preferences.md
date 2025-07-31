# Oracle Project Key Rules & Preferences

## General Principles

- **Language & Style:** PHP 8+ (SOLID, Clean Code) & Vue.js 3 (Composition API, TypeScript, Quasar). **All documentation/comments in French.**
- **Architecture:** Repository & Service Patterns, Dependency Injection (via PHP-DI), DataLoader Pattern pour GraphQL.
- **ORM:** Doctrine ORM en mode standalone pour la gestion des entités et des relations.
- **Principles:** SOLID, DRY, KISS.
- **Testing:** TDD encouraged, PHPUnit (Backend), Vitest (Frontend). Coverage > 80%. Tests run in CI.
- **Formatting:** Adhere to standards (PSR-12 for PHP, Prettier/ESLint for JS/TS). Use PHP & TypeScript type hints.
- **Database:** Mandatory Migrations, Indexes, Transactions, Timestamps (`created_at`), Soft Deletes. Prepared SQL statements.

## API & GraphQL

- **API (REST/GraphQL):** Input validation, Pagination/Filtering/Sorting, Clear error handling, Documentation (GraphQL strong types).
- **GraphQL Client:** Apollo Client pour les requêtes côté frontend.
- **N+1 Prevention:** Implémentation obligatoire du pattern DataLoader pour optimiser les requêtes GraphQL.
- **Batching:** Regroupement des requêtes similaires pour réduire les appels à la base de données.

## Dependency Injection & ORM

- **PHP-DI:** Configuration centralisée des services via PHP-DI.
- **Conteneur:** Utilisation du conteneur pour l'injection des dépendances dans les constructeurs.
- **Doctrine:** Configuration des entités via annotations ou attributs PHP 8+.
- **Repositories Doctrine:** Utilisation des repositories Doctrine pour l'accès aux données.
- **Transactions:** Gestion explicite des transactions pour les opérations critiques.

## Security

- **Validation:** Systematic input validation, XSS/CSRF/SQL Injection prevention, HTTPS.
- **Sanitization:** Nettoyage systématique des données utilisateur.
- **No sensitive info:** Éviter les informations sensibles dans les logs ou traces. (Auth/Authz/Encryption TBD).

## Performance

- **SQL Optimization:** Indexes, éviter le problème N+1 via DataLoader et Doctrine.
- **Caching:** Mise en cache des requêtes fréquentes et des résultats calculés.
- **Asset Optimization:** Minify, Bundle, Lazy Load, Images optimisées.
- **Batch processing:** Traitement par lots pour les opérations intensives.

## Workflow

- **Git Flow:** Feature branches, Pull Requests + Code Reviews, Descriptive commits linked to Issues.
- **CI/CD:** Tests automatisés, déploiement continu.
- **Environment:** Variables d'environnement pour la configuration.
- **Monitoring:** Centralized Monitoring & Logs, Changelog.

## Documentation

- **Code:** DocBlocks en français, descriptions claires des paramètres et retours.
- **API:** Documentation complète des endpoints GraphQL et REST.
- **Architecture:** Documents en Markdown avec diagrammes Mermaid.
- **Mises à jour:** Documentation maintenue à jour avec le code.

## Summarized Naming Conventions

- **PHP:** Classes `PascalCase`, Methods/Variables `camelCase`, Constants `UPPER_SNAKE_CASE`. Files match classes. PSR-4 Namespaces.
- **Vue:** Components `PascalCase` (`.vue`), Props/Methods `camelCase`. Pinia Stores `camelCaseStore`.
- **CSS/SASS:** Files `kebab-case`.
- **Database:** Tables `snake_case_plural`, Columns `snake_case`.
- **GraphQL:** Types `PascalCase`, Fields `camelCase`.

## PHP-DI & Doctrine Spécifiques

- **Définitions:** Définitions de services dans `config/di.php`.
- **Autowiring:** Privilégier l'autowiring quand possible.
- **Entités:** Namespace `App\Entity`, annotations/attributs pour les mappings.
- **Repositories:** Namespace `App\Repository`, étendre `Doctrine\ORM\EntityRepository`.

## DataLoader Pattern

- **Implémentation:** Utiliser la bibliothèque DataLoader ou créer une implémentation personnalisée.
- **Batch Loading:** Regrouper les requêtes par type d'entité et relation.
- **Contexte:** Dataloader instancié et partagé dans le contexte de la requête GraphQL.
- **Cache:** Mise en cache des entités dans le dataloader pendant la durée de la requête.

## Project Specifics

- **Phone Numbers:** Normalization (+XXX...), RegEx Validation, Segmentation (Country/Operator/Subscriber).
- **SMS:** 160 char limit, Send history.
- **Import/Export:** Flexible CSV (delimiters, encoding), Export CSV/Excel.
- **Custom Segments:** RegEx Validation.
- **User Interface:** Responsive (Quasar), Client-side Validation, Clear Feedback, Loading states, Pagination/Filters/Sorting, Accessibility (WCAG).
