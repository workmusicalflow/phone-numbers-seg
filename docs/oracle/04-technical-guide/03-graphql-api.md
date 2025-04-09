# API GraphQL

Cette page documente l'API GraphQL du projet Oracle, qui permet d'interagir avec le système de manière programmatique.

## Introduction à GraphQL

GraphQL est un langage de requête pour les API et un environnement d'exécution pour répondre à ces requêtes avec vos données existantes. Oracle utilise GraphQL comme principale interface d'API pour offrir une flexibilité maximale aux développeurs.

Contrairement aux API REST traditionnelles, GraphQL permet aux clients de demander exactement les données dont ils ont besoin, rien de plus, rien de moins. Cela réduit la quantité de données transférées sur le réseau et améliore les performances.

## Point d'entrée de l'API

L'API GraphQL d'Oracle est accessible via le point d'entrée suivant :

```
https://votre-domaine.com/graphql.php
```

Pour les environnements de développement local, l'URL sera généralement :

```
http://localhost/graphql.php
```

## Authentification

L'API GraphQL d'Oracle utilise l'authentification JWT (JSON Web Token) pour sécuriser les requêtes. Pour accéder aux endpoints protégés, vous devez inclure un token JWT valide dans l'en-tête `Authorization` de vos requêtes HTTP :

```
Authorization: Bearer <votre-token-jwt>
```

Pour obtenir un token JWT, utilisez la mutation `login` comme décrit dans la section Authentification ci-dessous.

## Explorateur GraphQL

Oracle inclut un explorateur GraphQL interactif accessible à l'adresse :

```
https://votre-domaine.com/graphiql.html
```

Cet outil vous permet d'explorer le schéma GraphQL, de construire et de tester des requêtes, et de consulter la documentation intégrée.

## Schéma GraphQL

Le schéma GraphQL d'Oracle est organisé autour des types principaux suivants :

- **Query** : Points d'entrée pour récupérer des données
- **Mutation** : Points d'entrée pour modifier des données
- **Types** : Définitions des objets manipulés par l'API

### Types principaux

Voici les principaux types d'objets disponibles dans l'API GraphQL d'Oracle :

#### PhoneNumber

Représente un numéro de téléphone avec ses segments.

```graphql
type PhoneNumber {
  id: ID!
  number: String!
  normalizedNumber: String!
  countryCode: String
  operatorCode: String
  subscriberNumber: String
  segments: [Segment!]
  createdAt: DateTime!
  updatedAt: DateTime
}
```

#### Segment

Représente un segment de numéro de téléphone.

```graphql
type Segment {
  id: ID!
  phoneNumberId: ID!
  type: String!
  value: String!
  description: String
  createdAt: DateTime!
}
```

#### CustomSegment

Représente un segment personnalisé défini par l'utilisateur.

```graphql
type CustomSegment {
  id: ID!
  name: String!
  pattern: String!
  description: String
  isActive: Boolean!
  createdAt: DateTime!
  updatedAt: DateTime
}
```

#### User

Représente un utilisateur du système.

```graphql
type User {
  id: ID!
  email: String!
  name: String!
  isAdmin: Boolean!
  createdAt: DateTime!
  updatedAt: DateTime
}
```

#### SenderName

Représente un nom d'expéditeur pour l'envoi de SMS.

```graphql
type SenderName {
  id: ID!
  userId: ID!
  name: String!
  status: String!
  approvedAt: DateTime
  createdAt: DateTime!
  updatedAt: DateTime
}
```

#### SMSOrder

Représente une commande d'envoi de SMS.

```graphql
type SMSOrder {
  id: ID!
  userId: ID!
  senderNameId: ID!
  message: String!
  creditUsed: Int!
  status: String!
  scheduledAt: DateTime
  createdAt: DateTime!
  updatedAt: DateTime
}
```

#### SMSHistory

Représente l'historique d'envoi d'un SMS.

```graphql
type SMSHistory {
  id: ID!
  smsOrderId: ID!
  phoneNumberId: ID!
  status: String!
  messageId: String
  sentAt: DateTime!
  deliveredAt: DateTime
}
```

#### Contact

Représente un contact dans le carnet d'adresses.

```graphql
type Contact {
  id: ID!
  userId: ID!
  name: String!
  phoneNumber: String!
  email: String
  notes: String
  groups: [ContactGroup!]
  createdAt: DateTime!
  updatedAt: DateTime
}
```

#### ContactGroup

Représente un groupe de contacts.

```graphql
type ContactGroup {
  id: ID!
  userId: ID!
  name: String!
  description: String
  contacts: [Contact!]
  createdAt: DateTime!
  updatedAt: DateTime
}
```

#### ScheduledSMS

Représente un SMS programmé pour un envoi ultérieur.

```graphql
type ScheduledSMS {
  id: ID!
  userId: ID!
  senderNameId: ID!
  message: String!
  recipientsType: String!
  recipientsData: String!
  frequency: String!
  nextExecution: DateTime!
  isActive: Boolean!
  logs: [ScheduledSMSLog!]
  createdAt: DateTime!
  updatedAt: DateTime
}
```

#### SMSTemplate

Représente un modèle de SMS réutilisable.

```graphql
type SMSTemplate {
  id: ID!
  userId: ID!
  name: String!
  content: String!
  description: String
  isPublic: Boolean!
  createdAt: DateTime!
  updatedAt: DateTime
}
```

## Requêtes (Queries)

Voici les principales requêtes disponibles dans l'API GraphQL d'Oracle :

### Numéros de téléphone

```graphql
# Récupérer un numéro de téléphone par ID
query PhoneNumber($id: ID!) {
  phoneNumber(id: $id) {
    id
    number
    normalizedNumber
    countryCode
    operatorCode
    subscriberNumber
    segments {
      id
      type
      value
      description
    }
    createdAt
    updatedAt
  }
}

# Récupérer tous les numéros de téléphone
query PhoneNumbers($page: Int, $limit: Int) {
  phoneNumbers(page: $page, limit: $limit) {
    items {
      id
      number
      normalizedNumber
      countryCode
      operatorCode
      subscriberNumber
    }
    total
    page
    limit
  }
}

# Rechercher des numéros de téléphone
query SearchPhoneNumbers($search: String!, $page: Int, $limit: Int) {
  searchPhoneNumbers(search: $search, page: $page, limit: $limit) {
    items {
      id
      number
      normalizedNumber
    }
    total
    page
    limit
  }
}
```

### Segments

```graphql
# Récupérer tous les segments personnalisés
query CustomSegments($page: Int, $limit: Int) {
  customSegments(page: $page, limit: $limit) {
    items {
      id
      name
      pattern
      description
      isActive
    }
    total
    page
    limit
  }
}

# Récupérer un segment personnalisé par ID
query CustomSegment($id: ID!) {
  customSegment(id: $id) {
    id
    name
    pattern
    description
    isActive
    createdAt
    updatedAt
  }
}
```

### Utilisateurs

```graphql
# Récupérer l'utilisateur actuellement authentifié
query Me {
  me {
    id
    email
    name
    isAdmin
    createdAt
    updatedAt
  }
}

# Récupérer un utilisateur par ID
query User($id: ID!) {
  user(id: $id) {
    id
    email
    name
    isAdmin
    createdAt
    updatedAt
  }
}

# Récupérer tous les utilisateurs (admin uniquement)
query Users($page: Int, $limit: Int) {
  users(page: $page, limit: $limit) {
    items {
      id
      email
      name
      isAdmin
    }
    total
    page
    limit
  }
}
```

### SMS

```graphql
# Récupérer tous les noms d'expéditeur
query SenderNames($page: Int, $limit: Int) {
  senderNames(page: $page, limit: $limit) {
    items {
      id
      name
      status
      approvedAt
    }
    total
    page
    limit
  }
}

# Récupérer l'historique des SMS
query SMSHistory($page: Int, $limit: Int) {
  smsHistory(page: $page, limit: $limit) {
    items {
      id
      smsOrderId
      phoneNumberId
      status
      messageId
      sentAt
      deliveredAt
    }
    total
    page
    limit
  }
}

# Récupérer les commandes SMS
query SMSOrders($page: Int, $limit: Int) {
  smsOrders(page: $page, limit: $limit) {
    items {
      id
      senderNameId
      message
      creditUsed
      status
      scheduledAt
      createdAt
    }
    total
    page
    limit
  }
}

# Récupérer les SMS programmés
query ScheduledSMS($page: Int, $limit: Int) {
  scheduledSMS(page: $page, limit: $limit) {
    items {
      id
      senderNameId
      message
      recipientsType
      frequency
      nextExecution
      isActive
    }
    total
    page
    limit
  }
}

# Récupérer les modèles de SMS
query SMSTemplates($page: Int, $limit: Int) {
  smsTemplates(page: $page, limit: $limit) {
    items {
      id
      name
      content
      description
      isPublic
    }
    total
    page
    limit
  }
}
```

### Contacts

```graphql
# Récupérer tous les contacts
query Contacts($page: Int, $limit: Int) {
  contacts(page: $page, limit: $limit) {
    items {
      id
      name
      phoneNumber
      email
    }
    total
    page
    limit
  }
}

# Récupérer tous les groupes de contacts
query ContactGroups($page: Int, $limit: Int) {
  contactGroups(page: $page, limit: $limit) {
    items {
      id
      name
      description
    }
    total
    page
    limit
  }
}

# Récupérer les contacts d'un groupe
query ContactGroupContacts($groupId: ID!, $page: Int, $limit: Int) {
  contactGroupContacts(groupId: $groupId, page: $page, limit: $limit) {
    items {
      id
      name
      phoneNumber
      email
    }
    total
    page
    limit
  }
}
```

### Tableau de bord

```graphql
# Récupérer les statistiques du tableau de bord
query DashboardStats {
  dashboardStats {
    totalPhoneNumbers
    totalSMSSent
    totalSMSDelivered
    totalContacts
    totalContactGroups
    creditBalance
  }
}

# Récupérer les statistiques d'envoi de SMS par période
query SMSStatsByPeriod($period: String!) {
  smsStatsByPeriod(period: $period) {
    labels
    sent
    delivered
    failed
  }
}
```

## Mutations

Voici les principales mutations disponibles dans l'API GraphQL d'Oracle :

### Authentification

```graphql
# Connexion utilisateur
mutation Login($email: String!, $password: String!) {
  login(email: $email, password: $password) {
    token
    user {
      id
      email
      name
      isAdmin
    }
  }
}

# Déconnexion utilisateur
mutation Logout {
  logout
}

# Réinitialisation de mot de passe
mutation RequestPasswordReset($email: String!) {
  requestPasswordReset(email: $email)
}

mutation ResetPassword($token: String!, $password: String!) {
  resetPassword(token: $token, password: $password)
}
```

### Numéros de téléphone

```graphql
# Segmenter un numéro de téléphone
mutation SegmentPhoneNumber($number: String!) {
  segmentPhoneNumber(number: $number) {
    id
    number
    normalizedNumber
    countryCode
    operatorCode
    subscriberNumber
    segments {
      id
      type
      value
      description
    }
  }
}

# Créer un numéro de téléphone
mutation CreatePhoneNumber($input: PhoneNumberInput!) {
  createPhoneNumber(input: $input) {
    id
    number
    normalizedNumber
    countryCode
    operatorCode
    subscriberNumber
  }
}

# Mettre à jour un numéro de téléphone
mutation UpdatePhoneNumber($id: ID!, $input: PhoneNumberInput!) {
  updatePhoneNumber(id: $id, input: $input) {
    id
    number
    normalizedNumber
    countryCode
    operatorCode
    subscriberNumber
  }
}

# Supprimer un numéro de téléphone
mutation DeletePhoneNumber($id: ID!) {
  deletePhoneNumber(id: $id)
}
```

### Segments personnalisés

```graphql
# Créer un segment personnalisé
mutation CreateCustomSegment($input: CustomSegmentInput!) {
  createCustomSegment(input: $input) {
    id
    name
    pattern
    description
    isActive
  }
}

# Mettre à jour un segment personnalisé
mutation UpdateCustomSegment($id: ID!, $input: CustomSegmentInput!) {
  updateCustomSegment(id: $id, input: $input) {
    id
    name
    pattern
    description
    isActive
  }
}

# Supprimer un segment personnalisé
mutation DeleteCustomSegment($id: ID!) {
  deleteCustomSegment(id: $id)
}
```

### SMS

```graphql
# Envoyer un SMS
mutation SendSMS($input: SendSMSInput!) {
  sendSMS(input: $input) {
    id
    senderNameId
    message
    creditUsed
    status
  }
}

# Créer un nom d'expéditeur
mutation CreateSenderName($input: SenderNameInput!) {
  createSenderName(input: $input) {
    id
    name
    status
  }
}

# Programmer un SMS
mutation ScheduleSMS($input: ScheduleSMSInput!) {
  scheduleSMS(input: $input) {
    id
    senderNameId
    message
    recipientsType
    recipientsData
    frequency
    nextExecution
    isActive
  }
}

# Créer un modèle de SMS
mutation CreateSMSTemplate($input: SMSTemplateInput!) {
  createSMSTemplate(input: $input) {
    id
    name
    content
    description
    isPublic
  }
}
```

### Contacts

```graphql
# Créer un contact
mutation CreateContact($input: ContactInput!) {
  createContact(input: $input) {
    id
    name
    phoneNumber
    email
    notes
  }
}

# Mettre à jour un contact
mutation UpdateContact($id: ID!, $input: ContactInput!) {
  updateContact(id: $id, input: $input) {
    id
    name
    phoneNumber
    email
    notes
  }
}

# Supprimer un contact
mutation DeleteContact($id: ID!) {
  deleteContact(id: $id)
}

# Créer un groupe de contacts
mutation CreateContactGroup($input: ContactGroupInput!) {
  createContactGroup(input: $input) {
    id
    name
    description
  }
}

# Ajouter un contact à un groupe
mutation AddContactToGroup($contactId: ID!, $groupId: ID!) {
  addContactToGroup(contactId: $contactId, groupId: $groupId)
}

# Supprimer un contact d'un groupe
mutation RemoveContactFromGroup($contactId: ID!, $groupId: ID!) {
  removeContactFromGroup(contactId: $contactId, groupId: $groupId)
}
```

### Utilisateurs

```graphql
# Créer un utilisateur (admin uniquement)
mutation CreateUser($input: UserInput!) {
  createUser(input: $input) {
    id
    email
    name
    isAdmin
  }
}

# Mettre à jour un utilisateur
mutation UpdateUser($id: ID!, $input: UserInput!) {
  updateUser(id: $id, input: $input) {
    id
    email
    name
    isAdmin
  }
}

# Supprimer un utilisateur (admin uniquement)
mutation DeleteUser($id: ID!) {
  deleteUser(id: $id)
}
```

## Exemples d'utilisation

### Segmenter un numéro de téléphone

```javascript
const query = `
  mutation SegmentPhoneNumber($number: String!) {
    segmentPhoneNumber(number: $number) {
      id
      number
      normalizedNumber
      countryCode
      operatorCode
      subscriberNumber
      segments {
        id
        type
        value
        description
      }
    }
  }
`;

const variables = {
  number: "+22507123456",
};

fetch("https://votre-domaine.com/graphql.php", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    Authorization: "Bearer " + token,
  },
  body: JSON.stringify({
    query,
    variables,
  }),
})
  .then((response) => response.json())
  .then((data) => console.log(data));
```

### Envoyer un SMS

```javascript
const query = `
  mutation SendSMS($input: SendSMSInput!) {
    sendSMS(input: $input) {
      id
      senderNameId
      message
      creditUsed
      status
    }
  }
`;

const variables = {
  input: {
    senderNameId: "1",
    message: "Bonjour, ceci est un test.",
    recipients: ["+22507123456", "+22507654321"],
    scheduledAt: null,
  },
};

fetch("https://votre-domaine.com/graphql.php", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    Authorization: "Bearer " + token,
  },
  body: JSON.stringify({
    query,
    variables,
  }),
})
  .then((response) => response.json())
  .then((data) => console.log(data));
```

## Gestion des erreurs

L'API GraphQL d'Oracle utilise un système standardisé de gestion des erreurs. Chaque erreur contient les informations suivantes :

- **message** : Description de l'erreur
- **locations** : Emplacement de l'erreur dans la requête GraphQL
- **path** : Chemin de l'erreur dans la réponse
- **extensions** : Informations supplémentaires sur l'erreur

Exemple de réponse d'erreur :

```json
{
  "errors": [
    {
      "message": "Numéro de téléphone invalide",
      "locations": [
        {
          "line": 2,
          "column": 3
        }
      ],
      "path": ["segmentPhoneNumber"],
      "extensions": {
        "code": "VALIDATION_ERROR",
        "details": "Le format du numéro de téléphone est incorrect"
      }
    }
  ],
  "data": {
    "segmentPhoneNumber": null
  }
}
```

## Codes d'erreur courants

- **AUTHENTICATION_ERROR** : Erreur d'authentification
- **AUTHORIZATION_ERROR** : Erreur d'autorisation (permissions insuffisantes)
- **VALIDATION_ERROR** : Erreur de validation des données
- **NOT_FOUND** : Ressource non trouvée
- **INTERNAL_ERROR** : Erreur interne du serveur
- **RATE_LIMIT_EXCEEDED** : Limite de requêtes dépassée

## Pagination

L'API GraphQL d'Oracle utilise un système de pagination basé sur les paramètres `page` et `limit`. Les réponses paginées suivent cette structure :

```graphql
type PaginatedResponse {
  items: [Item!]!
  total: Int!
  page: Int!
  limit: Int!
}
```

- **items** : Liste des éléments pour la page actuelle
- **total** : Nombre total d'éléments
- **page** : Numéro de la page actuelle
- **limit** : Nombre d'éléments par page

## Filtrage et tri

Certaines requêtes supportent le filtrage et le tri des résultats via des paramètres supplémentaires :

```graphql
query PhoneNumbers(
  $page: Int
  $limit: Int
  $filter: PhoneNumberFilter
  $sort: PhoneNumberSort
) {
  phoneNumbers(page: $page, limit: $limit, filter: $filter, sort: $sort) {
    items {
      id
      number
      normalizedNumber
    }
    total
    page
    limit
  }
}
```

Exemple de variables :

```json
{
  "page": 1,
  "limit": 10,
  "filter": {
    "countryCode": "225"
  },
  "sort": {
    "field": "createdAt",
    "direction": "DESC"
  }
}
```

## Ressources supplémentaires

- [Documentation officielle de GraphQL](https://graphql.org/learn/)
- [GraphQL Playground](https://github.com/graphql/graphql-playground)
- [Apollo Client](https://www.apollographql.com/docs/react/)
