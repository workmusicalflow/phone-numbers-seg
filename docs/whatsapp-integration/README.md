# Intégration de l'API WhatsApp Business Cloud

Cette documentation décrit l'implémentation de l'API WhatsApp Business Cloud dans l'application Oracle. L'intégration permet de recevoir et d'envoyer des messages WhatsApp à travers la plateforme.

## Table des matières

1. [Architecture](#architecture)
2. [Configuration](#configuration)
3. [Webhook](#webhook)
4. [Entités et base de données](#entités-et-base-de-données)
5. [API GraphQL](#api-graphql)
6. [Messages templates](#messages-templates)
7. [Procédure d'installation](#procédure-dinstallation)
8. [Tests](#tests)
9. [Dépannage](#dépannage)

## Architecture

L'intégration WhatsApp est construite en suivant les mêmes principes architecturaux que le reste de l'application Oracle:

```
┌───────────────┐      ┌──────────────┐      ┌────────────────┐
│ Meta WhatsApp │      │  Webhook API │      │  Controllers   │
│   Cloud API   │ <──> │  (webhook.php)│ <──> │               │
└───────────────┘      └──────────────┘      └────────┬───────┘
                                                      │
                                                      ▼
┌───────────────┐      ┌──────────────┐      ┌────────────────┐
│   Frontend    │      │  GraphQL API │      │   Services     │
│   Vue.js      │ <──> │   Resolvers  │ <──> │               │
└───────────────┘      └──────────────┘      └────────┬───────┘
                                                      │
                                                      ▼
                                              ┌────────────────┐
                                              │  Repositories  │
                                              │               │
                                              └────────┬───────┘
                                                      │
                                                      ▼
                                              ┌────────────────┐
                                              │ Base de données│
                                              │   (SQLite)    │
                                              └────────────────┘
```

## Configuration

La configuration de l'API WhatsApp se trouve dans le fichier `src/config/whatsapp.php`. Ce fichier contient :

- Les identifiants de l'API Meta (app_id, phone_number_id, etc.)
- Le token d'accès à l'API
- Le token de vérification du webhook
- La configuration des templates disponibles

## Webhook

Le système utilise un webhook pour recevoir les notifications de messages WhatsApp entrants. Le point d'entrée est :

```
/public/whatsapp/webhook.php
```

Ce webhook gère deux types de requêtes :
- **GET** : Pour la vérification initiale du webhook par Meta
- **POST** : Pour la réception des notifications de messages et statuts

### Vérification du webhook

Pour configurer le webhook sur la console Meta for Developers :

1. Accédez à votre application sur [Meta for Developers](https://developers.facebook.com/)
2. Allez dans "WhatsApp" > "Configuration"
3. Dans la section "Webhooks", configurez l'URL du webhook : `https://votre-domaine.com/whatsapp/webhook.php`
4. Utilisez le token de vérification défini dans `src/config/whatsapp.php`
5. Sélectionnez les événements à recevoir (au minimum "messages")

## Entités et base de données

### Base de données

L'intégration utilise une nouvelle table dans la base de données SQLite :

```sql
CREATE TABLE whatsapp_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    message_id TEXT UNIQUE NOT NULL,
    sender TEXT NOT NULL,
    recipient TEXT,
    timestamp INTEGER NOT NULL,
    type TEXT NOT NULL,
    content TEXT,
    raw_data TEXT NOT NULL,
    media_url TEXT,
    media_type TEXT,
    status TEXT,
    created_at INTEGER NOT NULL
);
```

### Entité

L'entité `WhatsAppMessage` est définie dans `src/Entities/WhatsApp/WhatsAppMessage.php` et utilise les annotations Doctrine ORM pour le mapping objet-relationnel.

## API GraphQL

### Types GraphQL

L'intégration définit les types GraphQL suivants :

- `WhatsAppMessage` : Représentation d'un message WhatsApp
- `WhatsAppMessageInput` : Type d'entrée pour envoyer un message
- `WhatsAppTemplateSendInput` : Type d'entrée pour envoyer un message template

### Queries

```graphql
# Récupérer les messages par expéditeur
query {
  getWhatsAppMessagesBySender(sender: "+2250777104936", limit: 10, offset: 0) {
    id
    messageId
    sender
    content
    type
    formattedTimestamp
  }
}

# Récupérer les messages par type
query {
  getWhatsAppMessagesByType(type: "text", limit: 10, offset: 0) {
    id
    messageId
    sender
    content
    formattedTimestamp
  }
}
```

### Mutations

```graphql
# Envoyer un message texte
mutation {
  sendWhatsAppTextMessage(
    recipient: "+2250777104936",
    message: "Bonjour, ceci est un message de test."
  ) {
    success
    messageId
    error
  }
}

# Envoyer un message template
mutation {
  sendWhatsAppTemplateMessage(input: {
    recipient: "+2250777104936",
    templateName: "qshe_invitation1",
    languageCode: "fr",
    headerImageUrl: "https://events-qualitas-ci.com/public/images/banner/QSHEf2025-1024.jpg",
    body1Param: "QSHE 2024",
    body2Param: "15-16 Mai 2024"
  }) {
    success
    messageId
    error
  }
}
```

## Messages templates

Les messages templates doivent être approuvés par Meta avant utilisation. Voici les étapes pour créer un template :

1. Accédez à [Meta Business Suite](https://business.facebook.com/)
2. Allez dans "WhatsApp" > "Templates"
3. Cliquez sur "Créer un template"
4. Suivez les instructions pour créer votre template
5. Soumettez le template pour approbation

Une fois approuvé, ajoutez le template à la configuration dans `src/config/whatsapp.php` :

```php
'templates' => [
    'mon_nouveau_template' => [
        'name' => 'mon_nouveau_template',
        'language' => 'fr',
        'components' => [
            // Définition des composants du template
        ]
    ]
]
```

## Procédure d'installation

Suivez ces étapes pour installer l'intégration WhatsApp :

1. Exécutez le script de migration pour créer la table :
   ```
   php scripts/migrate_whatsapp_messages.php
   ```

2. Mettez à jour le fichier de configuration avec vos identifiants Meta :
   - Éditez `src/config/whatsapp.php` avec vos identifiants
   - Remplacez la valeur de `access_token` par votre token valide

3. Configurez le webhook sur la console Meta for Developers

4. Si nécessaire, utilisez ngrok pour les tests en développement local :
   ```
   ngrok http 8000
   ```

## Tests

Pour tester manuellement l'intégration :

1. Vérifiez que votre webhook répond correctement aux requêtes de vérification
2. Envoyez un message WhatsApp au numéro configuré
3. Vérifiez que le message est reçu et stocké dans la base de données
4. Testez l'envoi d'un message via l'API GraphQL

## Dépannage

### Problèmes de webhook

- Vérifiez que le token de vérification est correctement configuré
- Assurez-vous que le webhook est accessible publiquement
- Consultez les logs dans `var/logs/` pour plus d'informations

### Problèmes d'envoi de messages

- Vérifiez que le token d'accès est valide (validité de 60 jours)
- Assurez-vous que le numéro de téléphone est au format correct
- Vérifiez que les templates utilisés sont approuvés par Meta