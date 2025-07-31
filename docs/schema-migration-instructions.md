# Instructions de migration pour l'historique SMS

Pour que les modifications du système d'historique SMS fonctionnent correctement, il est nécessaire de mettre à jour le schéma de la base de données SQLite. Suivez ces étapes pour effectuer la migration :

## 1. Mise à jour du schéma de la table sms_history

Exécutez le script SQL suivant pour ajouter la colonne `batch_id` à la table `sms_history` :

```bash
sqlite3 /path/to/your/database.sqlite < scripts/update_sms_history_table.sql
```

Où `/path/to/your/database.sqlite` est le chemin vers votre fichier de base de données SQLite.

## 2. Vérification de la migration

Pour vérifier que la migration a été effectuée correctement, vous pouvez exécuter la commande suivante :

```bash
sqlite3 /path/to/your/database.sqlite "PRAGMA table_info(sms_history);"
```

Cette commande affichera les informations de la table `sms_history`, y compris la nouvelle colonne `batch_id`.

## 3. Mise à jour des entités PHP

Les classes PHP ont déjà été mises à jour pour refléter les changements dans le schéma de la base de données. Assurez-vous que les modifications suivantes sont bien en place :

1. Ajout de la propriété `batchId` à l'entité `SMSHistory`
2. Ajout des méthodes getter et setter pour `batchId`
3. Mise à jour de la méthode `toArray()` pour inclure `batchId`

## 4. Redémarrage des services

Une fois les modifications terminées, redémarrez les services suivants :

1. Le service PHP-FPM (si utilisé)
2. Le service cron qui exécute le traitement de la file d'attente SMS

## Remarques importantes

- Cette migration est compatible avec les données existantes. Les SMS déjà enregistrés auront une valeur `NULL` pour la colonne `batch_id`.
- Les nouveaux SMS envoyés via la file d'attente seront automatiquement liés à leur batch_id, permettant un suivi plus précis.