# Premiers pas avec Oracle

Ce guide vous aidera à démarrer rapidement avec le système Oracle de gestion de SMS et de segmentation de numéros de téléphone.

## Prérequis

Avant de commencer à utiliser Oracle, assurez-vous de disposer des éléments suivants :

- Un compte utilisateur Oracle valide
- Un navigateur web moderne (Chrome, Firefox, Safari ou Edge)
- Une connexion Internet stable

## Connexion à la plateforme

1. Ouvrez votre navigateur et accédez à l'URL de la plateforme Oracle : `https://votre-domaine.com`
2. Sur la page d'accueil, cliquez sur le bouton "Connexion" en haut à droite
3. Saisissez votre adresse e-mail et votre mot de passe
4. Cliquez sur le bouton "Se connecter"

![Page de connexion](../assets/login-page.png)

Si vous avez oublié votre mot de passe, cliquez sur le lien "Mot de passe oublié ?" et suivez les instructions pour le réinitialiser.

## Interface utilisateur

Une fois connecté, vous accédez au tableau de bord principal d'Oracle. L'interface est organisée de la manière suivante :

### Barre de navigation principale

Située en haut de l'écran, elle vous permet d'accéder aux différentes sections de l'application :

- **Tableau de bord** : Vue d'ensemble de votre activité
- **Numéros** : Gestion des numéros de téléphone
- **Segments** : Gestion des segments personnalisés
- **SMS** : Envoi et suivi des SMS
- **Contacts** : Gestion de vos contacts et groupes
- **Paramètres** : Configuration de votre compte

### Barre latérale

Située à gauche de l'écran, elle affiche les sous-sections de la section principale sélectionnée.

### Zone de contenu principal

Occupe la majeure partie de l'écran et affiche le contenu de la section sélectionnée.

### Barre d'état

Située en bas de l'écran, elle affiche des informations sur votre compte, comme votre solde de crédits SMS.

## Tableau de bord

Le tableau de bord est la première page que vous voyez après vous être connecté. Il vous donne une vue d'ensemble de votre activité et de vos statistiques :

- **Solde de crédits** : Nombre de crédits SMS disponibles
- **Numéros de téléphone** : Nombre total de numéros enregistrés
- **SMS envoyés** : Statistiques d'envoi de SMS
- **Activité récente** : Dernières actions effectuées

![Tableau de bord](../assets/dashboard.png)

## Gestion des numéros de téléphone

La section "Numéros" vous permet de gérer vos numéros de téléphone et de les segmenter.

### Ajouter un numéro de téléphone

1. Dans la barre de navigation, cliquez sur "Numéros"
2. Cliquez sur le bouton "Ajouter un numéro"
3. Saisissez le numéro de téléphone dans le format international (ex: +22507123456)
4. Cliquez sur "Segmenter" pour analyser automatiquement le numéro
5. Vérifiez les segments détectés (pays, opérateur, etc.)
6. Cliquez sur "Enregistrer"

![Ajout d'un numéro](../assets/add-phone-number.png)

### Importer des numéros en masse

1. Dans la section "Numéros", cliquez sur "Importer"
2. Téléchargez le modèle CSV si nécessaire
3. Préparez votre fichier CSV avec les numéros à importer
4. Cliquez sur "Parcourir" et sélectionnez votre fichier CSV
5. Configurez les options d'importation
6. Cliquez sur "Importer"
7. Vérifiez le rapport d'importation

![Importation de numéros](../assets/import-numbers.png)

### Rechercher des numéros

1. Dans la section "Numéros", utilisez la barre de recherche en haut
2. Saisissez un numéro complet ou partiel
3. Utilisez les filtres pour affiner votre recherche (pays, opérateur, etc.)
4. Cliquez sur un numéro dans les résultats pour voir ses détails

## Segmentation personnalisée

La section "Segments" vous permet de créer et gérer des segments personnalisés pour catégoriser vos numéros de téléphone.

### Créer un segment personnalisé

1. Dans la barre de navigation, cliquez sur "Segments"
2. Cliquez sur "Nouveau segment"
3. Saisissez un nom pour le segment
4. Définissez un motif (pattern) pour la correspondance
5. Ajoutez une description (facultatif)
6. Cliquez sur "Enregistrer"

![Création d'un segment](../assets/create-segment.png)

### Appliquer des segments personnalisés

1. Dans la section "Numéros", sélectionnez un ou plusieurs numéros
2. Cliquez sur "Actions" puis "Appliquer des segments"
3. Sélectionnez les segments à appliquer
4. Cliquez sur "Appliquer"

## Envoi de SMS

La section "SMS" vous permet d'envoyer des messages SMS à vos contacts.

### Envoyer un SMS simple

1. Dans la barre de navigation, cliquez sur "SMS"
2. Cliquez sur "Nouveau SMS"
3. Sélectionnez un nom d'expéditeur dans la liste déroulante
4. Saisissez les numéros de téléphone des destinataires ou sélectionnez des contacts
5. Rédigez votre message
6. Vérifiez le nombre de crédits nécessaires
7. Cliquez sur "Envoyer"

![Envoi de SMS](../assets/send-sms.png)

### Envoyer un SMS à un groupe

1. Dans la section "SMS", cliquez sur "Nouveau SMS"
2. Sélectionnez un nom d'expéditeur
3. Cliquez sur l'onglet "Groupes"
4. Sélectionnez un ou plusieurs groupes de contacts
5. Rédigez votre message
6. Vérifiez le nombre de crédits nécessaires
7. Cliquez sur "Envoyer"

### Programmer un SMS

1. Dans la section "SMS", cliquez sur "Nouveau SMS"
2. Configurez votre message comme d'habitude
3. Cochez la case "Programmer l'envoi"
4. Sélectionnez la date et l'heure d'envoi
5. Cliquez sur "Programmer"

![Programmation de SMS](../assets/schedule-sms.png)

### Utiliser un modèle de SMS

1. Dans la section "SMS", cliquez sur "Nouveau SMS"
2. Cliquez sur "Utiliser un modèle"
3. Sélectionnez un modèle dans la liste
4. Personnalisez le message si nécessaire
5. Configurez les destinataires
6. Cliquez sur "Envoyer"

## Gestion des contacts

La section "Contacts" vous permet de gérer vos contacts et de les organiser en groupes.

### Ajouter un contact

1. Dans la barre de navigation, cliquez sur "Contacts"
2. Cliquez sur "Nouveau contact"
3. Saisissez les informations du contact (nom, numéro de téléphone, etc.)
4. Cliquez sur "Enregistrer"

![Ajout d'un contact](../assets/add-contact.png)

### Créer un groupe de contacts

1. Dans la section "Contacts", cliquez sur l'onglet "Groupes"
2. Cliquez sur "Nouveau groupe"
3. Saisissez un nom et une description pour le groupe
4. Cliquez sur "Enregistrer"
5. Pour ajouter des contacts au groupe, cliquez sur le groupe
6. Cliquez sur "Ajouter des contacts"
7. Sélectionnez les contacts à ajouter
8. Cliquez sur "Ajouter au groupe"

![Création d'un groupe](../assets/create-group.png)

## Suivi des SMS

La section "SMS" > "Historique" vous permet de suivre l'état de vos envois de SMS.

### Consulter l'historique des SMS

1. Dans la barre de navigation, cliquez sur "SMS"
2. Cliquez sur l'onglet "Historique"
3. Utilisez les filtres pour affiner votre recherche (date, statut, etc.)
4. Cliquez sur un SMS dans la liste pour voir ses détails

![Historique des SMS](../assets/sms-history.png)

### Comprendre les statuts des SMS

- **En attente** : Le SMS est en cours de traitement
- **Envoyé** : Le SMS a été envoyé à l'opérateur
- **Livré** : Le SMS a été livré au destinataire
- **Échec** : L'envoi du SMS a échoué

## Gestion des noms d'expéditeur

La section "SMS" > "Noms d'expéditeur" vous permet de gérer les noms qui apparaîtront comme expéditeurs de vos SMS.

### Demander un nouveau nom d'expéditeur

1. Dans la section "SMS", cliquez sur l'onglet "Noms d'expéditeur"
2. Cliquez sur "Nouveau nom d'expéditeur"
3. Saisissez le nom souhaité (11 caractères maximum, lettres et chiffres uniquement)
4. Cliquez sur "Soumettre"

![Demande de nom d'expéditeur](../assets/sender-name-request.png)

### Comprendre les statuts des noms d'expéditeur

- **En attente** : La demande est en cours d'examen
- **Approuvé** : Le nom d'expéditeur est approuvé et peut être utilisé
- **Rejeté** : La demande a été rejetée

## Paramètres du compte

La section "Paramètres" vous permet de configurer votre compte et vos préférences.

### Modifier votre profil

1. Dans la barre de navigation, cliquez sur "Paramètres"
2. Cliquez sur l'onglet "Profil"
3. Modifiez vos informations personnelles
4. Cliquez sur "Enregistrer"

### Changer votre mot de passe

1. Dans la section "Paramètres", cliquez sur l'onglet "Sécurité"
2. Saisissez votre mot de passe actuel
3. Saisissez votre nouveau mot de passe
4. Confirmez votre nouveau mot de passe
5. Cliquez sur "Changer le mot de passe"

### Configurer les notifications

1. Dans la section "Paramètres", cliquez sur l'onglet "Notifications"
2. Activez ou désactivez les différentes notifications
3. Cliquez sur "Enregistrer"

## Achat de crédits SMS

Pour envoyer des SMS, vous avez besoin de crédits. Voici comment en acheter :

1. Dans la barre de navigation, cliquez sur "Crédits"
2. Sélectionnez un pack de crédits ou saisissez un montant personnalisé
3. Choisissez votre mode de paiement
4. Suivez les instructions pour finaliser le paiement
5. Vos crédits seront ajoutés à votre compte immédiatement après confirmation du paiement

![Achat de crédits](../assets/buy-credits.png)

## Assistance et support

Si vous rencontrez des problèmes ou avez des questions, plusieurs options s'offrent à vous :

### Centre d'aide

1. Cliquez sur l'icône "?" en haut à droite de l'écran
2. Parcourez les catégories ou utilisez la recherche pour trouver des réponses

### Contacter le support

1. Dans le centre d'aide, cliquez sur "Contacter le support"
2. Remplissez le formulaire en décrivant votre problème
3. Cliquez sur "Envoyer"

Notre équipe de support vous répondra dans les plus brefs délais.

## Prochaines étapes

Maintenant que vous connaissez les bases d'Oracle, vous pouvez explorer les fonctionnalités avancées :

- [Segmentation avancée](02-advanced-segmentation.md)
- [Personnalisation des SMS](03-sms-personalization.md)
- [Automatisation des envois](04-automation.md)
- [Analyse des performances](05-analytics.md)
- [Intégration avec d'autres systèmes](06-integration.md)
