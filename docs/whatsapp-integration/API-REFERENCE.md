# Référence de l'API WhatsApp

Cette documentation décrit en détail l'API GraphQL pour l'intégration WhatsApp Business dans l'application Oracle.

## Types GraphQL

### WhatsAppMessage

Représente un message WhatsApp reçu ou envoyé.

```graphql
type WhatsAppMessage {
  id: ID!
  messageId: String!
  sender: String!
  recipient: String
  timestamp: Int!
  type: String!
  content: String
  mediaUrl: String
  mediaType: String
  status: String
  createdAt: Int!
  formattedTimestamp: String!
  formattedCreatedAt: String!
}
```

### WhatsAppMessageInput

Type d'entrée pour envoyer un message WhatsApp générique.

```graphql
input WhatsAppMessageInput {
  recipient: String!
  type: String!
  content: String
  mediaUrl: String
  mediaType: String
  templateName: String
  languageCode: String
  templateParams: String
}
```

### WhatsAppTemplateSendInput

Type d'entrée optimisé pour l'envoi de messages template.

```graphql
input WhatsAppTemplateSendInput {
  recipient: String!
  templateName: String!
  languageCode: String!
  headerImageUrl: String
  body1Param: String
  body2Param: String
  body3Param: String
}
```

## Queries

### getWhatsAppMessagesBySender

Récupère les messages WhatsApp envoyés par un expéditeur spécifique.

```graphql
getWhatsAppMessagesBySender(
  sender: String!
  limit: Int = 50
  offset: Int = 0
): [WhatsAppMessage!]!
```

#### Paramètres

- `sender`: Numéro de téléphone de l'expéditeur au format international
- `limit`: Nombre maximum de messages à récupérer (défaut: 50)
- `offset`: Décalage pour la pagination (défaut: 0)

#### Exemple

```graphql
query {
  getWhatsAppMessagesBySender(
    sender: "+2250777104936",
    limit: 10,
    offset: 0
  ) {
    id
    messageId
    content
    type
    formattedTimestamp
  }
}
```

### getWhatsAppMessagesByRecipient

Récupère les messages WhatsApp envoyés à un destinataire spécifique.

```graphql
getWhatsAppMessagesByRecipient(
  recipient: String!
  limit: Int = 50
  offset: Int = 0
): [WhatsAppMessage!]!
```

#### Paramètres

- `recipient`: Numéro de téléphone du destinataire au format international
- `limit`: Nombre maximum de messages à récupérer (défaut: 50)
- `offset`: Décalage pour la pagination (défaut: 0)

#### Exemple

```graphql
query {
  getWhatsAppMessagesByRecipient(
    recipient: "+2250777104936",
    limit: 10,
    offset: 0
  ) {
    id
    messageId
    sender
    content
    type
    formattedTimestamp
  }
}
```

### getWhatsAppMessageById

Récupère un message WhatsApp par son ID Meta.

```graphql
getWhatsAppMessageById(
  messageId: String!
): WhatsAppMessage
```

#### Paramètres

- `messageId`: L'identifiant unique du message fourni par Meta

#### Exemple

```graphql
query {
  getWhatsAppMessageById(
    messageId: "wamid.ABGGFlCGg0tx-12345678901234"
  ) {
    id
    messageId
    sender
    recipient
    content
    type
    formattedTimestamp
  }
}
```

### getWhatsAppMessagesByType

Récupère les messages WhatsApp d'un type spécifique.

```graphql
getWhatsAppMessagesByType(
  type: String!
  limit: Int = 50
  offset: Int = 0
): [WhatsAppMessage!]!
```

#### Paramètres

- `type`: Type de message (text, image, audio, video, document, etc.)
- `limit`: Nombre maximum de messages à récupérer (défaut: 50)
- `offset`: Décalage pour la pagination (défaut: 0)

#### Exemple

```graphql
query {
  getWhatsAppMessagesByType(
    type: "image",
    limit: 10,
    offset: 0
  ) {
    id
    messageId
    sender
    mediaUrl
    mediaType
    formattedTimestamp
  }
}
```

## Mutations

### sendWhatsAppTextMessage

Envoie un message texte WhatsApp.

```graphql
sendWhatsAppTextMessage(
  recipient: String!
  message: String!
): WhatsAppMessageResponse!
```

#### Paramètres

- `recipient`: Numéro de téléphone du destinataire au format international
- `message`: Contenu du message texte

#### Retour

```graphql
type WhatsAppMessageResponse {
  success: Boolean!
  messageId: String
  error: String
}
```

#### Exemple

```graphql
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
```

### sendWhatsAppTemplateMessage

Envoie un message template WhatsApp.

```graphql
sendWhatsAppTemplateMessage(
  input: WhatsAppTemplateSendInput!
): WhatsAppMessageResponse!
```

#### Paramètres

- `input`: Objet contenant les informations du template à envoyer

#### Exemple

```graphql
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

## Types de messages supportés

L'API WhatsApp supporte les types de messages suivants :

### Texte

Messages texte simples.

### Image

Messages avec une image. Requiert une URL d'image publiquement accessible.

### Document

Messages avec un document attaché. Requiert une URL de document publiquement accessible.

### Audio

Messages avec un fichier audio. Requiert une URL audio publiquement accessible.

### Video

Messages avec une vidéo. Requiert une URL vidéo publiquement accessible.

### Template

Messages basés sur des templates pré-approuvés par Meta. Les templates peuvent contenir :
- En-tête (texte, image ou vidéo)
- Corps avec paramètres variables
- Boutons (jusqu'à 3)

## Restrictions et limitations

- **Fenêtre de 24 heures** : Vous ne pouvez envoyer des messages libres qu'aux utilisateurs qui vous ont envoyé un message dans les 24 dernières heures
- **Templates obligatoires** : En dehors de la fenêtre de 24 heures, seuls les messages basés sur des templates approuvés peuvent être envoyés
- **Qualité des médias** : Les médias peuvent être compressés par WhatsApp
- **Taille des médias** : Limites de taille pour chaque type de média :
  - Images : 5 MB
  - Audio : 16 MB
  - Vidéo : 16 MB
  - Documents : 100 MB

## Gestion des erreurs

Les erreurs sont retournées dans le champ `error` des réponses de mutation. Les codes d'erreur courants incluent :

- **130429** : Limite de débit dépassée
- **131047** : Message en dehors de la fenêtre de 24 heures
- **131026** : Template invalide ou non approuvé
- **131042** : Numéro de téléphone non valide ou non enregistré sur WhatsApp

## Bonnes pratiques

1. **Normalisation des numéros** : Toujours utiliser le format international complet (+XXX)
2. **Templates approuvés** : Créer et faire approuver vos templates à l'avance
3. **Gestion des erreurs** : Toujours vérifier le champ `success` dans les réponses
4. **Renouvellement des tokens** : Mettre en place un processus pour renouveler les tokens d'accès tous les 60 jours