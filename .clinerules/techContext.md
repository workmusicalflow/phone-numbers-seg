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

Le schéma de la base de données MySQL est défini par des migrations SQL :

```sql
-- Exemple de migration pour la table phone_numbers
CREATE TABLE IF NOT EXISTS phone_numbers (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    number VARCHAR(255) NOT NULL,
    name VARCHAR(255),
    civility VARCHAR(255),
    firstName VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Exemple de migration pour la table segments
CREATE TABLE IF NOT EXISTS segments (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    phone_number_id INTEGER,
    type VARCHAR(255) NOT NULL,
    value VARCHAR(255) NOT NULL,
    FOREIGN KEY (phone_number_id) REFERENCES phone_numbers(id) ON DELETE CASCADE
);

-- Exemple de migration pour la table custom_segments
CREATE TABLE IF NOT EXISTS custom_segments (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    pattern TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Exemple de migration pour la table sms_history
CREATE TABLE IF NOT EXISTS sms_history (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    phone_number VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(255) NOT NULL,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Exemple de migration pour la table users
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    sms_credit INTEGER DEFAULT 0,
    sms_limit INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Exemple de migration pour la table sender_names
CREATE TABLE IF NOT EXISTS sender_names (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    user_id INTEGER,
    name VARCHAR(255) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Exemple de migration pour la table sms_orders
CREATE TABLE IF NOT EXISTS sms_orders (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    user_id INTEGER,
    quantity INTEGER NOT NULL,
    status ENUM('pending', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Exemple de migration pour la table orange_api_configs
CREATE TABLE IF NOT EXISTS orange_api_configs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    client_id VARCHAR(255) NOT NULL,
    client_secret VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Exemple de migration pour la table admin_contacts
CREATE TABLE IF NOT EXISTS admin_contacts (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    segment_id INTEGER,
    phone_number VARCHAR(255) NOT NULL,
    name VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (segment_id) REFERENCES custom_segments(id) ON DELETE SET NULL
);
```

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

L'application est conçue pour être déployée sur un serveur web Apache ou Nginx avec PHP 8.1+ et MySQL.

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

Git est utilisé pour le contrôle de version, avec les pratiques suivantes :

- Branches fonctionnelles pour les nouvelles fonctionnalités
- Pull requests pour la revue de code
- Commits atomiques avec messages descriptifs
- Tags pour les versions

### Tests Automatisés

Les tests automatisés sont implémentés à plusieurs niveaux :

- **Tests unitaires** : Pour tester les composants individuels
- **Tests d'intégration** : Pour tester les interactions entre composants
- **Tests fonctionnels** : Pour tester les fonctionnalités de bout en bout

### Documentation

La documentation est maintenue à plusieurs niveaux :

- **Documentation du code** : Commentaires et annotations dans le code
- **Documentation API** : Documentation des endpoints REST et du schéma GraphQL
- **Documentation utilisateur** : Guides et tutoriels pour les utilisateurs
- **Documentation technique** : Architecture, déploiement et maintenance

## Considérations Techniques

### Performance

La performance est optimisée à plusieurs niveaux :

- **Backend** : Requêtes SQL optimisées, mise en cache des résultats fréquents
- **Frontend** : Lazy loading, code splitting, optimisation des assets
- **API** : Requêtes GraphQL précises, pagination pour les grandes collections

### Sécurité

La sécurité est assurée par plusieurs mesures :

- **Validation des entrées** : Toutes les entrées utilisateur sont validées
- **Préparation des requêtes SQL** : Protection contre les injections SQL
- **Gestion des erreurs** : Messages d'erreur génériques pour l'utilisateur
- **HTTPS** : Communication chiffrée

### Évolutivité

L'architecture est conçue pour être évolutive :

- **Architecture modulaire** : Composants indépendants et réutilisables
- **API flexible** : GraphQL pour des requêtes précises et évolutives
- **Tests automatisés** : Pour faciliter les modifications sans régressions
- **Documentation** : Pour faciliter l'onboarding des nouveaux développeurs

## Déploiement sur cPanel/LWS

### Configuration du Serveur

- **PHP 8.1+** : Assurez-vous que la version de PHP est à jour.
- **MySQL** : Créez une base de données MySQL via cPanel.
- **Composer** : Utilisez Composer pour gérer les dépendances PHP.

### Déploiement des Fichiers

- **Téléchargez les fichiers** : Utilisez FTP ou l'interface de cPanel pour télécharger les fichiers de l'application.
- **Configurez le serveur web** : Modifiez le fichier `.htaccess` pour rediriger les requêtes vers le point d'entrée principal (`public/index.php`).

### Configuration de la Base de Données

- **Importez le schéma** : Utilisez phpMyAdmin pour importer le schéma de la base de données MySQL.
- **Configurez les informations de connexion** : Modifiez le fichier de configuration de la base de données pour utiliser les informations de connexion MySQL.

### Tests et Validation

- **Testez l'application** : Assurez-vous que toutes les fonctionnalités fonctionnent correctement.
- **Vérifiez les performances** : Surveillez l'utilisation des ressources du serveur pour identifier les problèmes potentiels.

## Surveillance des Performances

Pour garantir des performances optimales avec environ 100 utilisateurs simultanés, les mesures suivantes seront mises en œuvre :

1. **Mise en Cache des Données**

   - Utilisation de Redis ou Memcached pour mettre en cache les données fréquemment consultées (informations utilisateur, configurations, etc.).
   - Configuration de TTL (Time To Live) appropriés pour éviter les données obsolètes.

2. **Optimisation des Requêtes SQL**

   - Analyse des requêtes SQL les plus lentes et optimisation des index.
   - Utilisation de requêtes préparées pour éviter les injections SQL et améliorer les performances.

3. **Compression des Données**

   - Activation de la compression Gzip pour réduire la taille des réponses HTTP.
   - Utilisation de formats de données efficaces (par exemple, JSON compacté).

4. **Optimisation du Frontend**

   - Minification et concaténation des fichiers CSS et JavaScript.
   - Utilisation de CDN pour les ressources statiques (images, polices, etc.).
   - Lazy loading des images et des composants non critiques.

5. **Surveillance Continue**
   - Utilisation d'outils de surveillance des performances (par exemple, New Relic, cPanel Metrics) pour suivre les temps de réponse, l'utilisation du CPU et de la mémoire, et les erreurs.
   - Configuration d'alertes pour détecter les problèmes de performance et les erreurs critiques.

## Plan d'Implémentation Révisé (avec Exigences de Performance et Déploiement cPanel/LWS)

### Phase 1: Configuration de l'Environnement cPanel et Migration MySQL (2 semaines)

1. **Semaine 1**

   - Configuration de l'environnement cPanel
   - Création de la base de données MySQL
   - Adaptation des repositories pour MySQL
   - Tests de connexion et de requêtes
   - **Implémentation de la journalisation des requêtes SQL pour identifier les goulots d'étranglement**
   - **Configuration des variables d'environnement cPanel pour les informations de connexion à la base de données**

2. **Semaine 2**
   - Développement des scripts de migration
   - Migration des données de test
   - Tests d'intégration
   - Documentation du processus de migration
   - **Optimisation des requêtes SQL pour les opérations courantes**
   - **Mise en place d'un système de sauvegarde et de restauration de la base de données via cPanel**

### Phase 2: Système d'Authentification et Gestion des Utilisateurs (3 semaines)

1. **Semaine 1**

   - Implémentation du système JWT
   - Modèles et repositories utilisateur
   - Tests unitaires
   - **Implémentation d'un système de cache pour les informations utilisateur fréquemment consultées**
   - **Configuration des en-têtes HTTP pour la mise en cache des ressources statiques**

2. **Semaine 2**

   - Interface d'authentification Vue.js
   - Tableau de bord utilisateur
   - Gestion des crédits SMS
   - **Optimisation des composants Vue.js pour réduire le temps de chargement**
   - **Utilisation de CDN pour les bibliothèques JavaScript courantes**

3. **Semaine 3**
   - Système de commande de crédits
   - Gestion des noms d'expéditeur
   - Tests d'intégration
   - **Implémentation de la pagination pour les listes d'utilisateurs et de commandes**
   - **Configuration des redirections HTTP vers HTTPS via .htaccess**

### Phase 3: Administration et Notifications (2 semaines)

1. **Semaine 1**

   - Interface administrateur
   - Gestion des utilisateurs
   - Configuration de l'API Orange
   - **Implémentation de la mise en cache des données de configuration de l'API Orange**
   - **Restriction de l'accès à l'interface d'administration via .htaccess**

2. **Semaine 2**
   - Intégration AWS SES pour les emails
   - Système de notification SMS
   - Tests et finalisation
   - **Optimisation des templates d'email pour réduire la taille des messages**
   - **Configuration des alertes de surveillance des performances via cPanel**

### Phase 4: Déploiement et Tests sur cPanel (1 semaine)

1. **Jours 1-3**

   - Déploiement sur environnement de staging cPanel
   - Tests de performance et de sécurité
   - Correction des problèmes spécifiques à cPanel
   - **Configuration de l'OPcache pour PHP**
   - **Test de la configuration SSL/TLS**

2. **Jours 4-5**
   - Migration finale des données
   - Déploiement en production
   - Documentation utilisateur
   - **Surveillance des performances en temps réel via cPanel**
   - **Optimisation continue des performances en fonction des données de surveillance**

Je suis prêt à affiner davantage ce plan en fonction de vos réponses et à commencer l'implémentation dès que vous serez satisfait de l'approche proposée.

</final_file_content>

IMPORTANT: For any future changes to this file, use the final_file_content shown above as your reference. This content reflects the current state of the file, including any auto-formatting (e.g., if you used single quotes but the formatter converted them to double quotes). Always base your SEARCH/REPLACE operations on this final version to ensure accuracy.<environment_details>

# VSCode Visible Files

memory-bank/productContext.md

# VSCode Open Tabs

.gitignore
composer.json
README.md
src/Repositories/SegmentRepository.php
tests/Models/SegmentTest.php
tests/Services/PhoneSegmentationServiceTest.php
phpunit.xml
tests/Services/BatchSegmentationServiceTest.php
src/database/migrations/create_tables.sql
src/Models/CustomSegment.php
src/Models/Segment.php
src/Repositories/CustomSegmentRepository.php
src/Repositories/TechnicalSegmentRepository.php
src/Services/PhoneSegmentationService.php
../../Library/Application Support/Code/User/globalStorage/saoudrizwan.claude-dev/settings/cline_mcp_settings.json
src/Models/PhoneNumber.php
src/database/migrations/add_civility_firstname_fields.sql
tests/Models/PhoneNumberTest.php
src/database/init_db.php
src/Services/CSVImportService.php
src/GraphQL/SimpleContainer.php
src/GraphQL/Types/PhoneNumberType.php
src/GraphQL/Types/SegmentType.php
src/GraphQL/Types/CustomSegmentType.php
src/GraphQL/Controllers/PhoneNumberController.php
src/GraphQL/Controllers/CustomSegmentController.php
src/GraphQL/Controllers/BatchController.php
public/graphiql.html
memory-bank/vuejs-integration-plan.md
frontend/.eslintrc.js
frontend/.prettierrc.js
frontend/src/quasar-variables.sass
frontend/src/views/Home.vue
frontend/src/views/Batch.vue
frontend/src/views/Segments.vue
frontend/src/shims-vue.d.ts
frontend/tsconfig.json
frontend/tsconfig.node.json
frontend/.gitignore
frontend/index.html
frontend/README.md
frontend/src/stores/segmentStore.ts
frontend/src/components/CustomSegmentForm.vue
frontend/src/assets/global.css
frontend/src/components/BasePagination.vue
frontend/src/components/ConfirmDialog.vue
frontend/src/components/NotificationService.ts
frontend/src/components/LoadingOverlay.vue
frontend/src/components/SearchBar.vue
frontend/vitest.config.ts
frontend/scripts/test-graphql-integration.js
frontend/package.json
frontend/tests/README.md
frontend/tests/components/SearchBar.spec.ts
frontend/tests/stores/phoneStore.spec.ts
frontend/tests/components/PhoneNumberCard.spec.ts
frontend/tests/components/NotificationService.spec.ts
frontend/src/stores/phoneStore.ts
frontend/src/components/PhoneNumberCard.vue
public/index.php
public/segment.html
src/Controllers/PhoneController.php
frontend/vite.config.ts
frontend/src/views/Segment.vue
public/api.php
src/Controllers/ImportExportController.php
src/Services/ExportService.php
src/Repositories/PhoneNumberRepository.php
frontend/src/views/Import.vue
src/GraphQL/Controllers/ImportExportController.php
public/import.html
docs/formats-import.md
docs/formats-export.md
docs/vuejs-standards.md
rest-api.md
src/database/migrations/create_sms_history_table.sql
src/Models/SMSHistory.php
frontend/src/router/index.ts
fix_sms_history_table.sql
src/GraphQL/Controllers/DummyController.php
src/GraphQL/Controllers/TestController.php
src/GraphQL/GraphQLiteConfiguration.php
frontend/src/main.ts
frontend/src/services/NotificationService.ts
frontend/src/components/CustomNotification.vue
src/Services/SMSService.php
frontend/src/views/SMS.vue
frontend/src/App.vue
src/GraphQL/schema.graphql
public/graphql.php
memory-bank/progress.md
src/GraphQL/Controllers/SMSController.php
src/Repositories/SMSHistoryRepository.php
memory-bank/projectbrief.md
frontend/src/views/SMSHistory.vue
memory-bank/activeContext.md
memory-bank/productContext.md
memory-bank/systemPatterns.md
memory-bank/techContext.md
memory-bank/.clinerules
memory-bank/vuejs-frontend.md
memory-bank/graphql-integration.md
memory-bank/import-export-plan.md
public/sms.html
public/batch.html
public/segments.html
src/Services/BatchSegmentationService.php
sms-orange-curl.php
src/Controllers/SMSController.php
tests/Controllers/SMSControllerTest.php
tests/Services/SMSServiceTest.php

# Current Time

01/04/2025 1:14:10 AM (Africa/Abidjan, UTC+0:00)

# Current Mode

ACT MODE
</environment_details>
