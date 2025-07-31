# Contexte Technique - Oracle

## Technologies Utilisées

Oracle est construit en utilisant un ensemble de technologies modernes et éprouvées, choisies pour leur fiabilité, leur performance et leur facilité d'utilisation.

### Backend

#### PHP

PHP est le langage principal utilisé pour le backend de l'application. Nous utilisons PHP 8.3 pour bénéficier des fonctionnalités modernes du langage, notamment :

- Types de retour et types d'arguments
- Classes anonymes
- Opérateur de fusion null (`??`)
- Opérateur de propagation (`...`)
- Fonctions fléchées
- Propriétés typées
- Promotion des propriétés du constructeur
- Enums

#### SQLite

SQLite est utilisé comme système de gestion de base de données pour sa simplicité, sa portabilité et sa fiabilité. Les avantages de SQLite pour ce projet incluent :

- Aucune configuration de serveur nécessaire (base de données fichier)
- Facilité de déploiement et de sauvegarde
- Performance excellente pour des charges modérées
- Support des transactions ACID
- Zéro-configuration et maintenance minimale

Le projet était initialement configuré pour utiliser MySQL, mais a été migré vers SQLite pour simplifier le déploiement et le développement.

#### Composer

Composer est utilisé pour la gestion des dépendances PHP. Les principales dépendances incluent :

- **PHPUnit** : Pour les tests unitaires
- **GraphQLite** : Pour l'implémentation de l'API GraphQL
- **Monolog** : Pour la journalisation
- **Guzzle** : Pour les requêtes HTTP vers l'API Orange SMS
- **Doctrine**: Doctrine ORM en mode autonome (standalone), sans dépendre du FrameworkBundle de Symfony ni de ses commandes CLI. bootstrap Doctrine autonome

### Frontend

#### Vue.js

Vue.js 3 est utilisé pour le frontend moderne de l'application. Nous sommes en train de migrer progressivement de HTMX/Alpine.js vers Vue.js pour bénéficier de :

- Réactivité et performances améliorées
- Composants réutilisables
- Écosystème riche et mature
- Support TypeScript natif
- Composition API pour une meilleure organisation du code

#### Quasar Framework

Quasar est utilisé comme framework UI pour Vue.js, offrant :

- Un ensemble complet de composants UI
- Support pour le développement responsive
- Thèmes et personnalisation avancée
- Performances optimisées
- Support pour les applications mobiles via Capacitor

#### TypeScript

TypeScript est utilisé pour le développement frontend afin d'améliorer la qualité du code et la productivité :

- Typage statique pour détecter les erreurs à la compilation
- Meilleure autocomplétion et documentation dans l'IDE
- Refactoring plus sûr
- Interfaces et types pour une meilleure documentation du code

#### Pinia

Pinia est utilisé comme solution de gestion d'état pour Vue.js, remplaçant Vuex :

- API plus simple et intuitive
- Support TypeScript natif
- Meilleure intégration avec Vue 3 et la Composition API
- Devtools intégrés pour le débogage

#### Vite

Vite est utilisé comme outil de build pour le frontend, offrant :

- Démarrage instantané du serveur de développement
- Rechargement à chaud ultra-rapide
- Optimisation de production efficace
- Support natif pour TypeScript, JSX, CSS et plus

### API

#### REST

Une API REST traditionnelle est implémentée pour la compatibilité avec les clients existants et pour les opérations simples. Les caractéristiques de l'API REST incluent :

- Endpoints RESTful suivant les conventions standard
- Réponses JSON formatées de manière cohérente
- Validation des entrées et gestion des erreurs
- Documentation des endpoints

#### GraphQL

GraphQL est implémenté en parallèle de l'API REST pour offrir plus de flexibilité aux clients. Les avantages de GraphQL pour ce projet incluent :

- Requêtes précises pour éviter le sur-fetching et le sous-fetching
- Requêtes multiples en une seule demande
- Typage fort avec un schéma auto-documenté
- Évolution de l'API sans versionnement

### Intégrations

#### API Orange SMS

L'application intègre l'API Orange SMS pour l'envoi de SMS. Les caractéristiques de cette intégration incluent :

- Authentification OAuth2
- Envoi de SMS individuels et en masse
- Suivi des statuts d'envoi
- Gestion des erreurs et des réponses
- Historique complet des SMS envoyés

## Environnement de Développement

### Node.js

Node.js v22.14.0 (LTS) est utilisé pour l'environnement de développement frontend, offrant :

- Performances améliorées
- Support des dernières fonctionnalités ECMAScript
- Compatibilité avec les outils modernes de développement
- Gestion efficace des dépendances via npm

### npm

npm est utilisé pour la gestion des dépendances JavaScript. Les principales dépendances incluent :

- **Vue.js** : Framework frontend
- **Quasar** : Framework UI
- **TypeScript** : Superset typé de JavaScript
- **Pinia** : Gestion d'état
- **Vite** : Outil de build
- **Vitest** : Framework de test
- **ESLint** : Linting du code
- **Prettier** : Formatage du code

### ESLint et Prettier

ESLint et Prettier sont utilisés pour maintenir la qualité et la cohérence du code :

- ESLint pour l'analyse statique et la détection des problèmes
- Prettier pour le formatage automatique du code
- Configuration personnalisée pour suivre les meilleures pratiques
- Intégration avec l'IDE pour une validation en temps réel

### Vitest

Vitest est utilisé pour les tests unitaires du frontend :

- Compatible avec l'écosystème Vue.js
- Exécution rapide des tests
- Support natif de TypeScript
- API compatible avec Jest
- Intégration avec Vite pour un développement fluide

## Architecture Technique

### Structure du Projet

Le projet est organisé selon une structure modulaire claire :

```
/
├── public/                 # Fichiers accessibles publiquement
│   ├── index.php           # Point d'entrée principal
│   ├── api.php             # Point d'entrée de l'API REST
│   ├── graphql.php         # Point d'entrée de l'API GraphQL
│   └── ...                 # Autres fichiers publics
├── src/                    # Code source PHP
│   ├── Controllers/        # Contrôleurs
│   ├── Models/             # Modèles
│   ├── Repositories/       # Repositories
│   ├── Services/           # Services
│   ├── GraphQL/            # Configuration et types GraphQL
│   └── database/           # Migrations et scripts de base de données
├── tests/                  # Tests unitaires et d'intégration
│   ├── Controllers/        # Tests des contrôleurs
│   ├── Models/             # Tests des modèles
│   ├── Repositories/       # Tests des repositories
│   └── Services/           # Tests des services
├── frontend/               # Code source frontend Vue.js
│   ├── src/                # Code source TypeScript/Vue
│   │   ├── components/     # Composants Vue
│   │   ├── views/          # Pages Vue
│   │   ├── stores/         # Stores Pinia
│   │   ├── services/       # Services frontend
│   │   └── ...             # Autres fichiers frontend
│   ├── tests/              # Tests frontend
│   └── ...                 # Configuration frontend
├── docs/                   # Documentation
├── vendor/                 # Dépendances PHP (via Composer)
└── composer.json           # Configuration Composer
```

### Base de Données

SQLITE

### API GraphQL

Le schéma GraphQL est défini à l'aide de GraphQLite, qui génère automatiquement le schéma à partir des annotations PHP :

```php
/**
 * @Type
 */
class PhoneNumber
{
    /**
     * @Field
     */
    public function id(): ID
    {
        return new ID($this->id);
    }

    /**
     * @Field
     */
    public function number(): string
    {
        return $this->number;
    }

    /**
     * @Field
     */
    public function name(): ?string
    {
        return $this->name;
    }

    /**
     * @Field
     */
    public function civility(): ?string
    {
        return $this->civility;
    }

    /**
     * @Field
     */
    public function firstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @Field
     * @return Segment[]
     */
    public function segments(): array
    {
        return $this->segments;
    }
}
```

## Déploiement

### Serveur Web

L'application est conçue pour être déployée sur un serveur web Apache ou Nginx avec PHP 8.3+.

### Environnements

L'application prend en charge plusieurs environnements :

- **Développement** : Pour le développement local
- **Test** : Pour les tests automatisés et manuels
- **Production** : Pour le déploiement en production

### Configuration

La configuration de l'application est gérée via des variables d'environnement ou des fichiers de configuration selon l'environnement :

- **Développement** : Fichier `.env.local`
- **Test** : Fichier `.env.test`
- **Production** : Variables d'environnement du serveur

## Outils et Pratiques de Développement

### Contrôle de Version

Git est utilisé pour le contrôle de version
