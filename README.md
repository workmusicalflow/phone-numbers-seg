# Phone Numbers Segmentation Web Application

A web application for segmenting Côte d'Ivoire phone numbers into meaningful components.

## Overview

This application allows users to input phone numbers from Côte d'Ivoire and receive a breakdown of various segments (such as country code, operator, etc.) to facilitate better understanding and organization of phone number data.

## Supported Phone Number Formats

- International format with + prefix: +2250777104936
- International format with 00 prefix: 002250777104936
- Local format: 0777104936

## Features

- Input phone numbers for segmentation
- Process phone numbers to identify and extract meaningful segments
- Display segmented components in a clear, user-friendly interface
- Store phone numbers and their segments for future reference
- Support CRUD operations for phone numbers and segments

## Technology Stack

- **Backend**: PHP, SQLite
- **Frontend**: HTML/CSS, HTMX, Alpine.js
- **Testing**: PHPUnit

## Project Structure

- `src/`: Code source (models, services, controllers)
- `public/`: Publicly accessible files (HTML, CSS, JS)
- `tests/`: Unit and integration tests

## Installation

1. Clone the repository:

   ```
   git clone https://github.com/workmusicalflow/phone-numbers-seg.git
   ```

2. Install dependencies:

   ```
   composer install
   ```

3. Configure the database connection

4. Start the development server:
   ```
   php -S localhost:8000 -t public
   ```

## Development

This project follows a three-layer architecture:

- Presentation layer using HTMX and Alpine.js
- Business logic layer with PHP services
- Data layer with SQLite database

## Testing

Run tests with PHPUnit:

```
composer test
```

## API Documentation

### WhatsApp Bulk Send API

Endpoint pour envoyer des messages WhatsApp template à plusieurs destinataires.

#### Endpoint
```
POST /api/whatsapp/bulk-send.php
```

#### Headers requis
```
Authorization: Bearer {token}
Content-Type: application/json
```

#### Paramètres

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `recipients` | array | Oui | Liste des numéros de téléphone (format: +225XXXXXXXXXX) |
| `templateName` | string | Oui | Nom du template WhatsApp approuvé |
| `defaultParameters` | object | Non | Paramètres par défaut pour le template |
| `recipientParameters` | object | Non | Paramètres spécifiques par destinataire |
| `options` | object | Non | Options de traitement (voir ci-dessous) |

#### Options disponibles

| Option | Type | Défaut | Description |
|--------|------|---------|-------------|
| `batchSize` | int | 50 | Nombre de messages par batch |
| `delayBetweenBatches` | int | 1000 | Délai en ms entre les batches |
| `stopOnError` | bool | false | Arrêter l'envoi en cas d'erreur |

#### Exemple de requête

```json
{
  "recipients": ["+22501234567", "+22507654321"],
  "templateName": "hello_world",
  "defaultParameters": {
    "bodyParams": ["John"]
  },
  "options": {
    "batchSize": 50,
    "stopOnError": false
  }
}
```

#### Réponse succès (200)

```json
{
  "success": true,
  "message": "2 messages envoyés sur 2 (100.0% de réussite)",
  "data": {
    "totalSent": 2,
    "totalFailed": 0,
    "totalAttempted": 2,
    "successRate": 100.0,
    "errorSummary": {}
  }
}
```

#### Réponse avec erreurs partielles (207)

```json
{
  "success": true,
  "message": "1 messages envoyés sur 2 (50.0% de réussite)",
  "data": {
    "totalSent": 1,
    "totalFailed": 1,
    "totalAttempted": 2,
    "successRate": 50.0,
    "errorSummary": {
      "INVALID_NUMBER": 1
    }
  }
}
```

#### Limites

- Maximum **500 destinataires** par requête
- Les crédits SMS de l'utilisateur doivent être suffisants
- Utilise les templates WhatsApp approuvés uniquement

## License

Proprietary
