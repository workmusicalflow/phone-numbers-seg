# API REST WhatsApp

Cette documentation décrit les endpoints REST disponibles pour interagir avec les fonctionnalités WhatsApp de l'application Oracle.

## Authentification

Tous les endpoints (à l'exception du webhook) nécessitent une authentification via un token Bearer dans l'en-tête HTTP :

```
Authorization: Bearer <token>
```

## Endpoints

### Webhook

#### Vérification du webhook
- **Méthode**: GET
- **URL**: `/api.php?endpoint=whatsapp/webhook`
- **Description**: Endpoint utilisé par Meta pour vérifier le webhook lors de sa configuration initiale.
- **Paramètres Query**:
  - `hub_mode`: Mode de vérification (fourni par Meta)
  - `hub_challenge`: Challenge à retourner (fourni par Meta)
  - `hub_verify_token`: Token de vérification

#### Réception des événements webhook
- **Méthode**: POST
- **URL**: `/api.php?endpoint=whatsapp/webhook`
- **Description**: Point d'entrée pour les événements WhatsApp (messages entrants, statuts de messages, etc.)
- **Corps de la requête**: JSON (format défini par l'API WhatsApp)

### Messages

#### Obtenir l'historique des messages
- **Méthode**: GET
- **URL**: `/api.php?endpoint=whatsapp/messages`
- **Description**: Récupère l'historique des messages WhatsApp
- **Paramètres Query**:
  - `phone_number` (optionnel): Filtrer par numéro de téléphone
  - `status` (optionnel): Filtrer par statut (sent, delivered, read, failed)
  - `limit` (optionnel): Nombre maximum de messages à retourner (par défaut: 100)
  - `offset` (optionnel): Décalage pour la pagination (par défaut: 0)

#### Envoyer un message texte
- **Méthode**: POST
- **URL**: `/api.php?endpoint=whatsapp/messages/text`
- **Description**: Envoie un message texte WhatsApp
- **Corps de la requête**:
  ```json
  {
    "recipient": "22507xxxxxxxx",
    "message": "Votre message texte ici",
    "context_message_id": "wamid.xxxxxxx" // Optionnel, pour les réponses
  }
  ```

#### Envoyer un message média
- **Méthode**: POST
- **URL**: `/api.php?endpoint=whatsapp/messages/media`
- **Description**: Envoie un message média WhatsApp (image, vidéo, audio, document)
- **Corps de la requête**:
  ```json
  {
    "recipient": "22507xxxxxxxx",
    "type": "image", // Ou "video", "audio", "document"
    "media_url": "https://example.com/image.jpg", // URL du média
    // OU
    "media_id": "123456789", // ID d'un média précédemment uploadé
    "caption": "Description du média" // Optionnel
  }
  ```

#### Envoyer un message template
- **Méthode**: POST
- **URL**: `/api.php?endpoint=whatsapp/messages/template`
- **Description**: Envoie un message template WhatsApp (version simplifiée)
- **Corps de la requête**:
  ```json
  {
    "recipient": "22507xxxxxxxx",
    "template_name": "nom_du_template",
    "language_code": "fr",
    "header_image_url": "https://example.com/image.jpg", // Optionnel
    "body_params": ["param1", "param2"] // Paramètres pour les variables dans le template
  }
  ```

#### Envoyer un message template avancé
- **Méthode**: POST
- **URL**: `/api.php?endpoint=whatsapp/messages/template/advanced`
- **Description**: Envoie un message template WhatsApp avec des composants détaillés
- **Corps de la requête**:
  ```json
  {
    "recipient": "22507xxxxxxxx",
    "template_name": "nom_du_template",
    "language_code": "fr",
    "components": [
      {
        "type": "header",
        "parameters": [
          {
            "type": "image",
            "image": {
              "link": "https://example.com/image.jpg"
            }
          }
        ]
      },
      {
        "type": "body",
        "parameters": [
          {
            "type": "text",
            "text": "param1"
          },
          {
            "type": "text",
            "text": "param2"
          }
        ]
      }
    ],
    "header_media_id": "123456789" // Optionnel, ID média pour l'en-tête
  }
  ```

#### Envoyer un message interactif
- **Méthode**: POST
- **URL**: `/api.php?endpoint=whatsapp/messages/interactive`
- **Description**: Envoie un message interactif WhatsApp (boutons, listes, etc.)
- **Corps de la requête**:
  ```json
  {
    "recipient": "22507xxxxxxxx",
    "interactive": {
      "type": "button",
      "body": {
        "text": "Veuillez faire un choix"
      },
      "action": {
        "buttons": [
          {
            "type": "reply",
            "reply": {
              "id": "option_1",
              "title": "Option 1"
            }
          },
          {
            "type": "reply",
            "reply": {
              "id": "option_2",
              "title": "Option 2"
            }
          }
        ]
      }
    }
  }
  ```

#### Marquer un message comme lu
- **Méthode**: POST
- **URL**: `/api.php?endpoint=whatsapp/messages/read`
- **Description**: Marque un message WhatsApp comme lu
- **Corps de la requête**:
  ```json
  {
    "message_id": "wamid.xxxxxxx"
  }
  ```

### Médias

#### Uploader un média
- **Méthode**: POST
- **URL**: `/api.php?endpoint=whatsapp/media/upload`
- **Description**: Uploade un média pour une utilisation ultérieure
- **Corps de la requête**:
  ```json
  {
    "file_path": "/chemin/vers/fichier.jpg",
    "mime_type": "image/jpeg"
  }
  ```

#### Télécharger un média
- **Méthode**: GET
- **URL**: `/api.php?endpoint=whatsapp/media/download`
- **Description**: Télécharge un média à partir de son ID
- **Paramètres Query**:
  - `media_id`: ID du média à télécharger

#### Obtenir l'URL d'un média
- **Méthode**: GET
- **URL**: `/api.php?endpoint=whatsapp/media/url`
- **Description**: Obtient l'URL d'un média à partir de son ID
- **Paramètres Query**:
  - `media_id`: ID du média

### Templates

#### Obtenir tous les templates
- **Méthode**: GET
- **URL**: `/api.php?endpoint=whatsapp/templates`
- **Description**: Récupère tous les templates WhatsApp disponibles pour l'utilisateur

#### Obtenir un template par ID
- **Méthode**: GET
- **URL**: `/api.php?endpoint=whatsapp/templates/{template_id}`
- **Description**: Récupère les détails d'un template spécifique
- **Paramètres Path**:
  - `template_id`: ID du template WhatsApp

## Réponses

Toutes les réponses sont formatées en JSON avec la structure suivante :

### Succès
```json
{
  "status": "success",
  "message": "Message de succès",
  // Autres données spécifiques à l'endpoint
}
```

### Erreur
```json
{
  "status": "error",
  "message": "Description de l'erreur"
}
```

## Codes HTTP

- **200**: Requête traitée avec succès
- **201**: Ressource créée avec succès
- **204**: Requête traitée avec succès, pas de contenu à retourner
- **400**: Erreur de requête côté client
- **401**: Authentification requise
- **403**: Accès interdit
- **404**: Ressource non trouvée
- **500**: Erreur interne du serveur