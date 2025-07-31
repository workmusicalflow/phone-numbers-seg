# Guide de Déploiement via cPanel et FileZilla

Ce document fournit des instructions détaillées pour déployer l'application SMS sur un serveur web via cPanel et FileZilla. Suivez attentivement chaque étape pour assurer un déploiement réussi sans erreurs.

## Table des matières

1. [Prérequis](#prérequis)
2. [Structure des fichiers à déployer](#structure-des-fichiers-à-déployer)
3. [Préparation avant déploiement](#préparation-avant-déploiement)
4. [Configuration de l'environnement](#configuration-de-lenvironnement)
5. [Processus de déploiement via FileZilla](#processus-de-déploiement-via-filezilla)
6. [Configuration de la base de données](#configuration-de-la-base-de-données)
7. [Initialisation et migration de la base de données](#initialisation-et-migration-de-la-base-de-données)
8. [Configuration du serveur web](#configuration-du-serveur-web)
9. [Vérification post-déploiement](#vérification-post-déploiement)
10. [Bonnes pratiques](#bonnes-pratiques)
11. [Problèmes courants et solutions](#problèmes-courants-et-solutions)
12. [Mises à jour et maintenance](#mises-à-jour-et-maintenance)

## Prérequis

Avant de commencer le déploiement, assurez-vous de disposer des éléments suivants :

- Accès à cPanel pour votre hébergement
- Logiciel FileZilla installé sur votre ordinateur
- Informations de connexion FTP (hôte, nom d'utilisateur, mot de passe, port)
- Accès à phpMyAdmin ou à un autre gestionnaire de base de données MySQL
- PHP 7.4 ou supérieur installé sur le serveur
- Extensions PHP requises : PDO, PDO_MySQL, mbstring, json, xml, fileinfo, openssl
- Composer installé sur le serveur (ou possibilité d'exécuter des commandes composer)
- Node.js et npm installés localement pour la compilation des assets frontend

## Structure des fichiers à déployer

Voici la structure des fichiers et dossiers qui doivent être déployés sur le serveur :

```
/
├── composer.json
├── composer.lock
├── .env                  # À configurer avant le déploiement
├── public/               # Point d'entrée de l'application
│   ├── index.php
│   ├── graphql.php
│   ├── api.php
│   └── assets/           # Fichiers statiques (CSS, JS, images)
├── src/                  # Code source PHP
│   ├── API/
│   ├── config/
│   ├── Controllers/
│   ├── Entities/
│   ├── Exceptions/
│   ├── GraphQL/
│   ├── Middleware/
│   ├── Models/
│   ├── Repositories/
│   └── Services/
├── templates/            # Templates pour emails et SMS
│   ├── emails/
│   └── sms/
├── var/                  # Dossier pour les fichiers générés (cache, logs, base de données SQLite)
│   └── database.sqlite   # Si vous utilisez SQLite
└── vendor/               # Dépendances PHP (généré par Composer)
```

**Note importante** : Les dossiers suivants ne doivent PAS être déployés sur le serveur de production :

- `scripts/` - Contient des scripts de développement et de test
- `tests/` - Contient les tests unitaires et fonctionnels
- `frontend/node_modules/` - Dépendances npm (très volumineux)
- `.git/` - Dossier de contrôle de version
- `.vscode/` - Configuration de l'éditeur
- `memory-bank/` - Documentation interne

## Préparation avant déploiement

### 1. Compilation des assets frontend

Avant de déployer, vous devez compiler les assets frontend pour la production :

```bash
cd frontend
npm install
npm run build
```

Cette commande générera les fichiers optimisés dans le dossier `frontend/dist/`. Le contenu de ce dossier devra être copié dans `public/assets/` sur le serveur.

### 2. Configuration des URLs

Assurez-vous que les URLs sont correctement configurées pour l'environnement de production :

1. Modifiez le fichier `frontend/src/config/urls.ts` pour utiliser les URLs de production
2. Modifiez le fichier `src/config/urls.php` pour utiliser les URLs de production

### 3. Préparation du fichier .env

Créez une copie du fichier `.env.example` et nommez-la `.env`. Configurez les variables d'environnement pour la production :

```
# Base de données
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=nom_de_votre_base_de_donnees
DB_USERNAME=votre_utilisateur
DB_PASSWORD=votre_mot_de_passe

# Configuration de l'application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://votre-domaine.com

# Configuration Orange API
ORANGE_API_KEY=votre_cle_api
ORANGE_API_SECRET=votre_secret_api

# Autres configurations
MAIL_HOST=votre_serveur_smtp
MAIL_PORT=587
MAIL_USERNAME=votre_email
MAIL_PASSWORD=votre_mot_de_passe
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@votre-domaine.com
MAIL_FROM_NAME="Nom de votre application"
```

## Configuration de l'environnement

### 1. Configuration PHP

Assurez-vous que PHP est correctement configuré sur votre serveur. Voici les paramètres recommandés à définir dans le fichier `php.ini` ou via cPanel :

```
memory_limit = 256M
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 300
max_input_time = 300
display_errors = Off
log_errors = On
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
date.timezone = "Africa/Abidjan"
```

### 2. Configuration des répertoires

Assurez-vous que les répertoires suivants sont accessibles en écriture par le serveur web :

```
var/
public/assets/
```

Vous pouvez définir les permissions avec la commande suivante (via SSH) :

```bash
chmod -R 755 public/
chmod -R 775 var/
```

## Processus de déploiement via FileZilla

### 1. Connexion à votre serveur via FileZilla

1. Ouvrez FileZilla
2. Entrez les informations de connexion FTP fournies par votre hébergeur :
   - Hôte : ftp.votre-domaine.com
   - Nom d'utilisateur : votre_nom_utilisateur
   - Mot de passe : votre_mot_de_passe
   - Port : 21 (ou le port spécifié par votre hébergeur)
3. Cliquez sur "Connexion rapide"

### 2. Navigation vers le répertoire de destination

Dans le panneau de droite (serveur distant), naviguez vers le répertoire où vous souhaitez déployer l'application. Généralement, il s'agit de :

- `public_html/` pour le domaine principal
- `public_html/sous-dossier/` pour un sous-dossier

### 3. Transfert des fichiers

1. **Méthode recommandée** : Transférez d'abord les fichiers de base, puis les dossiers plus volumineux :

   a. Transférez les fichiers à la racine :

   - `.env` (configuré pour la production)
   - `composer.json`
   - `composer.lock`

   b. Créez et transférez les dossiers principaux :

   - `public/`
   - `src/`
   - `templates/`
   - `var/` (assurez-vous qu'il est vide ou ne contient que les fichiers nécessaires)

   c. Transférez le dossier `vendor/` (cette étape peut prendre du temps en raison du nombre de fichiers)

2. **Alternative** : Si vous avez accès SSH, vous pouvez compresser le projet, le transférer, puis le décompresser sur le serveur :

   ```bash
   # Sur votre machine locale
   zip -r projet.zip . -x "node_modules/*" "tests/*" ".git/*" "scripts/*" ".vscode/*" "memory-bank/*"

   # Transférez projet.zip via FileZilla

   # Sur le serveur (via SSH)
   unzip projet.zip -d /chemin/vers/destination
   ```

### 4. Points d'attention lors du transfert

- **Mode de transfert** : Utilisez le mode binaire pour tous les fichiers
- **Résolution des conflits** : En cas de mise à jour, choisissez "Écraser" pour les fichiers modifiés
- **Vérification des permissions** : Après le transfert, vérifiez que les permissions des fichiers sont correctes

## Configuration de la base de données

### 1. Création de la base de données via cPanel

1. Connectez-vous à cPanel
2. Recherchez la section "Bases de données" et cliquez sur "MySQL Databases"
3. Créez une nouvelle base de données :
   - Nom de la base de données : `votre_prefixe_nom_db`
4. Créez un nouvel utilisateur :
   - Nom d'utilisateur : `votre_prefixe_utilisateur`
   - Mot de passe : Utilisez un mot de passe fort
5. Ajoutez l'utilisateur à la base de données et accordez-lui tous les privilèges

### 2. Configuration de la connexion à la base de données

Assurez-vous que le fichier `.env` contient les informations correctes pour la connexion à la base de données :

```
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=votre_prefixe_nom_db
DB_USERNAME=votre_prefixe_utilisateur
DB_PASSWORD=votre_mot_de_passe
```

## Initialisation et migration de la base de données

### 1. Exécution des migrations

Si vous avez accès SSH, exécutez les commandes suivantes :

```bash
cd /chemin/vers/votre/application
php scripts/migration/run-all-migrations.php
```

Si vous n'avez pas accès SSH, vous pouvez :

1. Créer un script PHP temporaire dans le dossier public pour exécuter les migrations
2. Accéder à ce script via le navigateur
3. Supprimer le script après utilisation

Exemple de script temporaire (`public/run-migrations.php`) :

```php
<?php
// Définir un mot de passe pour sécuriser l'accès
$securityPassword = 'votre_mot_de_passe_securise';

// Vérifier le mot de passe
if (!isset($_GET['password']) || $_GET['password'] !== $securityPassword) {
    die('Accès non autorisé');
}

// Exécuter les migrations
require_once __DIR__ . '/../scripts/migration/run-all-migrations.php';

echo 'Migrations terminées avec succès';
```

Accédez ensuite à `https://votre-domaine.com/run-migrations.php?password=votre_mot_de_passe_securise`

**IMPORTANT** : Supprimez ce fichier immédiatement après utilisation !

### 2. Création de l'utilisateur administrateur initial

Créez un script temporaire similaire pour créer le premier utilisateur administrateur :

```php
<?php
// Définir un mot de passe pour sécuriser l'accès
$securityPassword = 'votre_mot_de_passe_securise';

// Vérifier le mot de passe
if (!isset($_GET['password']) || $_GET['password'] !== $securityPassword) {
    die('Accès non autorisé');
}

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../src/bootstrap-doctrine.php';

// Créer l'utilisateur administrateur
$userRepository = $entityManager->getRepository('App\Entities\User');

// Vérifier si l'utilisateur existe déjà
$existingUser = $userRepository->findOneBy(['username' => 'admin']);
if ($existingUser) {
    die('L\'utilisateur administrateur existe déjà');
}

// Créer le nouvel utilisateur
$user = new \App\Entities\User();
$user->setUsername('admin');
$user->setEmail('admin@votre-domaine.com');
$user->setPassword(password_hash('mot_de_passe_initial', PASSWORD_BCRYPT));
$user->setIsAdmin(true);
$user->setSmsCredit(1000);
$user->setSmsLimit(10000);
$user->setCreatedAt(new \DateTime());
$user->setUpdatedAt(new \DateTime());

$entityManager->persist($user);
$entityManager->flush();

echo 'Utilisateur administrateur créé avec succès';
```

Accédez à `https://votre-domaine.com/create-admin.php?password=votre_mot_de_passe_securise`

**IMPORTANT** : Supprimez ce fichier immédiatement après utilisation et changez le mot de passe de l'administrateur dès la première connexion !

## Configuration du serveur web

### 1. Configuration du point d'entrée

Assurez-vous que le document root pointe vers le dossier `public/` de votre application. Si vous utilisez cPanel, vous pouvez configurer cela via :

1. Connectez-vous à cPanel
2. Recherchez "Domaines" ou "Sous-domaines"
3. Modifiez le document root pour pointer vers le dossier `public/`

### 2. Configuration des règles de réécriture (htaccess)

Créez ou modifiez le fichier `.htaccess` dans le dossier `public/` :

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

# PHP settings
<IfModule mod_php7.c>
    php_value upload_max_filesize 64M
    php_value post_max_size 64M
    php_value max_execution_time 300
    php_value max_input_time 300
</IfModule>

# CORS Headers
<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "*"
    Header set Access-Control-Allow-Methods "GET, POST, OPTIONS, PUT, DELETE"
    Header set Access-Control-Allow-Headers "Content-Type, Authorization"
</IfModule>

# Disable directory browsing
Options -Indexes

# Deny access to .htaccess
<Files .htaccess>
    Order allow,deny
    Deny from all
</Files>

# Deny access to files with extensions .env, .log, .sql, .md
<FilesMatch ".(env|log|sql|md)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

## Vérification post-déploiement

Après avoir déployé l'application, effectuez les vérifications suivantes :

### 1. Vérification de l'accès à l'application

1. Accédez à votre domaine dans un navigateur : `https://votre-domaine.com`
2. Vérifiez que la page de connexion s'affiche correctement
3. Connectez-vous avec les identifiants administrateur créés précédemment

### 2. Vérification des fonctionnalités principales

Testez les fonctionnalités principales de l'application :

1. Connexion et déconnexion
2. Création et modification d'utilisateurs
3. Création et modification de contacts
4. Création et modification de groupes de contacts
5. Envoi de SMS à un contact
6. Envoi de SMS à un groupe
7. Vérification de l'historique des SMS

### 3. Vérification des logs

Vérifiez les logs pour détecter d'éventuelles erreurs :

1. Logs PHP : généralement dans `/var/log/php-errors.log` ou configurés dans votre `.env`
2. Logs de l'application : dans le dossier `var/logs/` de votre application

## Bonnes pratiques

### Sécurité

1. **Protégez les informations sensibles** :

   - Ne stockez jamais les mots de passe en clair
   - Protégez le fichier `.env` (il ne doit pas être accessible publiquement)
   - Utilisez HTTPS pour toutes les communications

2. **Mettez à jour régulièrement** :

   - Les dépendances PHP (via Composer)
   - Les dépendances JavaScript (via npm)
   - Le système d'exploitation du serveur

3. **Limitez les accès** :
   - Utilisez des permissions de fichiers restrictives
   - Limitez l'accès SSH et FTP aux adresses IP nécessaires
   - Utilisez l'authentification à deux facteurs lorsque c'est possible

### Performance

1. **Optimisez les assets** :

   - Minifiez les fichiers CSS et JavaScript
   - Compressez les images
   - Utilisez la mise en cache du navigateur

2. **Utilisez la mise en cache** :

   - Mettez en cache les requêtes API fréquentes
   - Utilisez un système de cache pour les requêtes de base de données

3. **Surveillez les performances** :
   - Utilisez des outils de surveillance des performances
   - Identifiez et corrigez les goulots d'étranglement

### Maintenance

1. **Sauvegardez régulièrement** :

   - La base de données
   - Les fichiers de l'application
   - Les configurations du serveur

2. **Documentez les modifications** :

   - Tenez un journal des modifications apportées
   - Documentez les procédures de déploiement spécifiques

3. **Planifiez les mises à jour** :
   - Effectuez les mises à jour pendant les périodes de faible trafic
   - Testez les mises à jour dans un environnement de staging avant de les déployer en production

## Problèmes courants et solutions

### Problème : Erreur 500 après déploiement

**Solutions possibles** :

1. Vérifiez les logs PHP pour identifier l'erreur spécifique
2. Assurez-vous que les permissions des fichiers sont correctes
3. Vérifiez que toutes les extensions PHP requises sont installées
4. Assurez-vous que le fichier `.env` est correctement configuré

### Problème : Impossible de se connecter à la base de données

**Solutions possibles** :

1. Vérifiez les informations de connexion dans le fichier `.env`
2. Assurez-vous que l'utilisateur de la base de données a les permissions nécessaires
3. Vérifiez que la base de données est accessible depuis le serveur web

### Problème : Les assets (CSS, JS) ne se chargent pas

**Solutions possibles** :

1. Vérifiez que les fichiers sont bien présents dans le dossier `public/assets/`
2. Assurez-vous que les chemins vers les assets sont corrects
3. Vérifiez les erreurs de console dans le navigateur

### Problème : Erreurs CORS lors des requêtes API

**Solutions possibles** :

1. Vérifiez la configuration CORS dans le fichier `.htaccess`
2. Assurez-vous que les en-têtes CORS sont correctement définis
3. Vérifiez que les domaines autorisés sont correctement configurés

## Mises à jour et maintenance

### Processus de mise à jour

1. **Sauvegarde préalable** :

   - Effectuez une sauvegarde complète de la base de données
   - Sauvegardez tous les fichiers de l'application

2. **Déploiement des mises à jour** :

   - Transférez les fichiers modifiés via FileZilla
   - Exécutez les migrations de base de données si nécessaire
   - Mettez à jour les dépendances si nécessaire

3. **Vérification post-mise à jour** :
   - Testez les fonctionnalités principales
   - Vérifiez les logs pour détecter d'éventuelles erreurs

### Disponibilité pour les mises à jour

Notre équipe est disponible pour effectuer des mises à jour sans friction. Nous proposons :

1. **Mises à jour planifiées** :

   - Mises à jour régulières selon un calendrier prédéfini
   - Notification préalable des changements à venir

2. **Support d'urgence** :

   - Intervention rapide en cas de problème critique
   - Assistance pour la résolution des problèmes

3. **Améliorations continues** :
   - Suggestions d'améliorations basées sur l'utilisation
   - Implémentation de nouvelles fonctionnalités selon les besoins

Pour toute assistance concernant le déploiement ou les mises à jour, veuillez contacter notre équipe de support à l'adresse support@votre-domaine.com ou par téléphone au +XX XXX XXX XXX.

---

Ce guide est maintenu à jour régulièrement pour refléter les meilleures pratiques et les procédures les plus récentes. Dernière mise à jour : 18/04/2025.
