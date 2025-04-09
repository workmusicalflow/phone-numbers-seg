# Importation de contacts et envoi de SMS

Ce dossier contient des scripts pour importer des contacts à partir d'un fichier CSV et les associer à l'utilisateur AfricaQSHE, puis envoyer des SMS à ces contacts.

## Fichier CSV

Le fichier CSV `Copie de contacts.csv` contient les contacts à importer avec les colonnes suivantes:

- First Name
- Last Name
- Organization
- number (numéro de téléphone au format international)

## Scripts disponibles

### 1. Importation des contacts

Deux méthodes sont disponibles pour importer les contacts:

#### a. Utilisation du script PHP

Le script `import_contacts_for_africaqshe.php` utilise directement le repository de contacts pour importer les données:

```bash
php scripts/utils/import_contacts_for_africaqshe.php
```

Ce script:

- Lit le fichier CSV
- Crée des contacts pour l'utilisateur AfricaQSHE (ID 2)
- Utilise le nom de l'organisation comme nom du contact si le nom est vide
- Ajoute les informations d'organisation dans les notes

#### b. Utilisation de curl avec l'API REST

Le script `import_contacts_curl.sh` utilise curl pour envoyer le fichier CSV à l'API REST:

```bash
# IMPORTANT: C'est un script shell, PAS un script PHP
# Rendez-le exécutable d'abord
chmod +x scripts/utils/import_contacts_curl.sh

# Puis exécutez-le
./scripts/utils/import_contacts_curl.sh
```

Ce script:

- Envoie le fichier CSV à l'endpoint `/api.php?endpoint=import-csv`
- Spécifie que la colonne 3 (index 3) contient les numéros de téléphone
- Associe les contacts à l'utilisateur AfricaQSHE (ID 2)

### 2. Envoi de SMS aux contacts

Deux méthodes sont disponibles pour envoyer des SMS aux contacts:

#### a. Envoi aux contacts importés

Le script `send_sms_to_contacts.sh` récupère les contacts de l'utilisateur et leur envoie un SMS:

```bash
# IMPORTANT: C'est un script shell, PAS un script PHP
# Rendez-le exécutable d'abord
chmod +x scripts/utils/send_sms_to_contacts.sh

# Puis exécutez-le
./scripts/utils/send_sms_to_contacts.sh
```

Ce script:

- Récupère les contacts de l'utilisateur AfricaQSHE via l'API REST
- Extrait les numéros de téléphone
- Utilise GraphQL pour envoyer un SMS à tous les contacts

#### b. Envoi direct aux numéros du fichier CSV

Deux scripts sont disponibles pour envoyer directement des SMS aux numéros du fichier CSV:

1. Script utilisant la mutation GraphQL sendSms (recommandé):

```bash
# IMPORTANT: C'est un script shell, PAS un script PHP
# Rendez-le exécutable d'abord
chmod +x scripts/utils/send_sms_direct_graphql.sh

# Puis exécutez-le
./scripts/utils/send_sms_direct_graphql.sh
```

Ce script:

- Utilise la mutation GraphQL sendSms qui a été testée et fonctionne
- Envoie un SMS à chaque numéro individuellement
- Affiche le statut de chaque envoi
- Ne nécessite pas que les contacts soient préalablement importés

2. Script utilisant la mutation GraphQL sendBulkSms:

```bash
# IMPORTANT: C'est un script shell, PAS un script PHP
# Rendez-le exécutable d'abord
chmod +x scripts/utils/send_direct_sms.sh

# Puis exécutez-le
./scripts/utils/send_direct_sms.sh
```

Ce script:

- Contient directement les numéros de téléphone extraits du fichier CSV
- Utilise GraphQL pour envoyer un SMS à tous les numéros en une seule requête
- Ne nécessite pas que les contacts soient préalablement importés

## Vérification des résultats

### 1. Vérification via l'interface utilisateur

Après avoir importé les contacts et envoyé des SMS, vous pouvez vérifier les résultats:

1. Dans l'interface utilisateur, connectez-vous en tant qu'AfricaQSHE
2. Accédez à la section "Contacts" pour voir les contacts importés
3. Accédez à la section "Historique des SMS" pour voir les SMS envoyés

### 2. Vérification via la base de données

Plusieurs méthodes sont disponibles pour vérifier les contacts dans la base de données:

#### a. Utilisation du script shell complet

Le script `check_contacts_in_db.sh` offre une vérification complète et formatée des contacts dans la base de données:

```bash
# Rendez le script exécutable
chmod +x scripts/utils/check_contacts_in_db.sh

# Exécutez le script
./scripts/utils/check_contacts_in_db.sh
```

Ce script:

- Exécute une série de requêtes SQL pour vérifier la présence des contacts
- Affiche un résumé formaté des résultats
- Indique si les numéros spécifiques du fichier CSV sont présents
- Fournit des recommandations basées sur les résultats

#### b. Utilisation du script shell simple

Le script `verify_contacts_import.sh` permet de vérifier directement dans la base de données si les contacts ont bien été importés pour l'utilisateur AfricaQSHE:

```bash
# Rendez le script exécutable
chmod +x scripts/utils/verify_contacts_import.sh

# Exécutez le script
./scripts/utils/verify_contacts_import.sh
```

Ce script:

- Affiche le nombre total de contacts pour l'utilisateur AfricaQSHE
- Liste les 10 contacts les plus récents
- Vérifie spécifiquement si les numéros du fichier CSV sont présents dans la base de données

#### c. Utilisation directe de SQL

Deux scripts SQL sont disponibles pour vérifier les contacts:

1. Script SQL complet:

```bash
# Exécutez le script SQL complet
sqlite3 src/database/database.sqlite < scripts/utils/check_contacts_table.sql
```

Ce script:

- Vérifie l'existence et la structure de la table contacts
- Compte le nombre total de contacts et le nombre par utilisateur
- Affiche les informations sur l'utilisateur AfricaQSHE
- Vérifie les contacts de l'utilisateur AfricaQSHE
- Vérifie la présence des numéros spécifiques du fichier CSV
- Vérifie les permissions d'accès aux contacts dans le système

2. Script SQL simple:

```bash
# Exécutez le script SQL simple
sqlite3 src/database/database.sqlite < scripts/utils/verify_contacts.sql
```

Ce script:

- Affiche le nombre total de contacts pour l'utilisateur AfricaQSHE
- Liste les 10 contacts les plus récents
- Vérifie spécifiquement si les numéros du fichier CSV sont présents dans la base de données

## Remarques

- Assurez-vous que le serveur est en cours d'exécution avant d'utiliser ces scripts
- L'utilisateur AfricaQSHE doit avoir suffisamment de crédits SMS pour envoyer des messages
- Les scripts utilisent l'URL `http://localhost:8000` par défaut, modifiez-la si nécessaire
