# Conversion des numéros de téléphone en contacts

Ce document explique comment utiliser les scripts de conversion des numéros de téléphone en contacts.

## Contexte

Le système gère deux types de données distinctes :

1. **Numéros de téléphone** (table `phone_numbers`) - Stockés sans lien avec un utilisateur spécifique
2. **Contacts** (table `contacts`) - Liés à un utilisateur spécifique via `user_id`

Lors de l'importation CSV, les numéros sont enregistrés dans la table `phone_numbers`, mais ne sont pas automatiquement convertis en contacts liés à l'utilisateur qui a effectué l'importation. Les scripts fournis permettent de convertir ces numéros en contacts.

## Scripts disponibles

### 1. Script en ligne de commande

Le script `convert_phone_numbers_to_contacts.php` permet de convertir les numéros de téléphone en contacts via la ligne de commande.

#### Utilisation

```bash
php scripts/utils/convert_phone_numbers_to_contacts.php [options]
```

#### Options

- `--user-id=X` : ID de l'utilisateur auquel associer les contacts (par défaut : 2 pour AfricaQSHE)
- `--dry-run` : Mode simulation (n'effectue aucune modification dans la base de données)
- `--limit=X` : Limite le nombre de numéros à traiter
- `--offset=X` : Commence le traitement à partir du X-ième numéro

#### Exemples

```bash
# Simulation pour l'utilisateur AfricaQSHE (ID 2)
php scripts/utils/convert_phone_numbers_to_contacts.php --dry-run

# Conversion réelle pour l'utilisateur AfricaQSHE (ID 2)
php scripts/utils/convert_phone_numbers_to_contacts.php

# Conversion pour un autre utilisateur (ID 3)
php scripts/utils/convert_phone_numbers_to_contacts.php --user-id=3

# Conversion limitée aux 100 premiers numéros
php scripts/utils/convert_phone_numbers_to_contacts.php --limit=100
```

### 2. Interface web

L'interface web `convert-phone-numbers.php` permet de convertir les numéros de téléphone en contacts via un navigateur.

#### Accès

Accédez à l'URL suivante dans votre navigateur :

```
http://votre-domaine/convert-phone-numbers.php
```

#### Fonctionnalités

- Sélection de l'utilisateur cible
- Mode simulation (dry-run)
- Limitation du nombre de numéros à traiter
- Affichage des résultats en temps réel

## Fonctionnement

Le processus de conversion suit les étapes suivantes :

1. Récupération des numéros de téléphone depuis la table `phone_numbers`
2. Pour chaque numéro :
   - Vérification si un contact avec ce numéro existe déjà pour l'utilisateur cible
   - Si non, création d'un nouveau contact avec les informations du numéro
   - Si oui, le numéro est ignoré (évite les doublons)
3. Génération d'un rapport détaillé sur les opérations effectuées

## Résolution des problèmes

### Erreurs courantes

- **Erreur d'authentification** : Assurez-vous d'être connecté avant d'utiliser l'interface web
- **Erreur de permission** : Vérifiez que l'utilisateur PHP a les droits d'écriture sur la base de données
- **Numéros non trouvés** : Vérifiez que des numéros existent bien dans la table `phone_numbers`

### Vérification des résultats

Après la conversion, vous pouvez vérifier les résultats en :

1. Accédant à la page des contacts dans l'interface utilisateur
2. Consultant directement la table `contacts` dans la base de données

## Recommandations

1. **Exécutez toujours en mode simulation d'abord** pour vérifier les résultats attendus
2. **Utilisez la limitation** pour traiter les numéros par lots si vous avez un grand nombre de numéros
3. **Sauvegardez votre base de données** avant d'effectuer une conversion réelle

## Améliorations futures

Pour les futures versions, il serait utile de :

1. Intégrer la conversion automatique lors de l'importation CSV
2. Ajouter une option pour associer les numéros à des groupes de contacts
3. Améliorer la gestion des doublons avec une option de mise à jour des contacts existants
