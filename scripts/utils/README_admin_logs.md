# Outils de consultation des journaux d'actions administrateur

Ce répertoire contient des scripts pour consulter les journaux des actions effectuées par les administrateurs dans l'application.

## Scripts disponibles

### 1. `view_admin_logs.php`

Ce script utilise le service `AdminActionLogger` pour récupérer et afficher les journaux d'actions administrateur.

#### Utilisation

```bash
php scripts/utils/view_admin_logs.php [options]
```

#### Options

- `--limit=N` : Limite le nombre de journaux à afficher (défaut: 20)
- `--admin=ID` : Filtre par ID d'administrateur
- `--action=TYPE` : Filtre par type d'action (ex: user_creation, user_update, etc.)
- `--target=ID` : Filtre par ID de cible
- `--target-type=TYPE` : Filtre par type de cible (ex: user, sender_name, etc.)
- `--format=FORMAT` : Format de sortie (text, json) (défaut: text)
- `--help` : Affiche l'aide

#### Exemples

Afficher les 10 derniers journaux :

```bash
php scripts/utils/view_admin_logs.php --limit=10
```

Afficher les journaux d'un administrateur spécifique :

```bash
php scripts/utils/view_admin_logs.php --admin=1
```

Afficher les journaux d'un type d'action spécifique :

```bash
php scripts/utils/view_admin_logs.php --action=user_update
```

Afficher les journaux au format JSON :

```bash
php scripts/utils/view_admin_logs.php --format=json
```

### 2. `view_admin_logs_sql.php`

Ce script utilise directement des requêtes SQL pour récupérer et afficher les journaux d'actions administrateur. Il est utile si vous rencontrez des problèmes avec le script précédent.

#### Utilisation

```bash
php scripts/utils/view_admin_logs_sql.php [options]
```

#### Options

Les mêmes options que pour `view_admin_logs.php` sont disponibles.

## Types d'actions journalisées

Les actions suivantes sont actuellement journalisées dans le système :

- `user_creation` : Création d'un utilisateur
- `user_update` : Mise à jour d'un utilisateur
- `user_deletion` : Suppression d'un utilisateur
- `password_change` : Changement de mot de passe
- `credit_addition` : Ajout de crédits SMS

## Structure des journaux

Chaque journal contient les informations suivantes :

- `id` : Identifiant unique du journal
- `admin_id` : Identifiant de l'administrateur qui a effectué l'action
- `admin_username` : Nom d'utilisateur de l'administrateur
- `action_type` : Type d'action effectuée
- `target_id` : Identifiant de la cible de l'action (ex: ID de l'utilisateur modifié)
- `target_type` : Type de la cible (ex: user, sender_name, etc.)
- `details` : Détails spécifiques à l'action (au format JSON)
- `created_at` : Date et heure de l'action

## Implémentation future

Pour une solution complète, une interface utilisateur graphique sera implémentée dans le frontend Vue.js. Cette interface permettra aux administrateurs de consulter et filtrer les journaux d'actions de manière plus conviviale.
