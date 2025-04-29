# Documentation de l'API REST

Cette documentation décrit les endpoints de l'API REST disponibles dans l'application de segmentation de numéros de téléphone.

## Base URL

Tous les endpoints sont accessibles à partir de l'URL de base : `/api.php`

## Format des Réponses

Toutes les réponses sont au format JSON, sauf indication contraire (comme pour les exports CSV/Excel).

## Authentification

L'API ne nécessite pas d'authentification pour le moment.

## Gestion des Erreurs

En cas d'erreur, l'API renvoie un code HTTP approprié et un objet JSON avec une propriété `error` contenant le message d'erreur :

```json
{
  "error": "Description de l'erreur"
}
```

## Endpoints

### Numéros de Téléphone

#### Lister tous les numéros de téléphone

```
GET /api.php?endpoint=phones
```

**Paramètres de requête :**

- `limit` (optionnel) : Nombre maximum de numéros à retourner (défaut : 100)
- `offset` (optionnel) : Index de départ pour la pagination (défaut : 0)

**Réponse :**

```json
{
  "phoneNumbers": [
    {
      "id": 1,
      "number": "+2250777104936",
      "civility": "M.",
      "firstName": "Jean",
      "name": "Dupont",
      "company": "ABC Corp",
      "sector": "Technologie",
      "notes": "Client depuis 2020",
      "dateAdded": "2025-03-15 10:30:45",
      "segments": [
        {
          "id": 1,
          "type": "country_code",
          "value": "225"
        },
        {
          "id": 2,
          "type": "country",
          "value": "Côte d'Ivoire"
        },
        {
          "id": 3,
          "type": "operator",
          "value": "Orange"
        },
        {
          "id": 4,
          "type": "number_type",
          "value": "Mobile"
        }
      ],
      "customSegments": [
        {
          "id": 1,
          "name": "Client VIP",
          "description": "Clients importants"
        }
      ]
    }
  ],
  "total": 150,
  "limit": 100,
  "offset": 0
}
```

#### Obtenir un numéro de téléphone spécifique

```
GET /api.php?endpoint=<id>
```

**Paramètres :**

- `<id>` : ID du numéro de téléphone

**Réponse :**

```json
{
  "id": 1,
  "number": "+2250777104936",
  "civility": "M.",
  "firstName": "Jean",
  "name": "Dupont",
  "company": "ABC Corp",
  "sector": "Technologie",
  "notes": "Client depuis 2020",
  "dateAdded": "2025-03-15 10:30:45",
  "segments": [
    {
      "id": 1,
      "type": "country_code",
      "value": "225"
    },
    {
      "id": 2,
      "type": "country",
      "value": "Côte d'Ivoire"
    },
    {
      "id": 3,
      "type": "operator",
      "value": "Orange"
    },
    {
      "id": 4,
      "type": "number_type",
      "value": "Mobile"
    }
  ],
  "customSegments": [
    {
      "id": 1,
      "name": "Client VIP",
      "description": "Clients importants"
    }
  ]
}
```

#### Créer un nouveau numéro de téléphone

```
POST /api.php?endpoint=phones
```

**Corps de la requête :**

```json
{
  "number": "+2250777104936",
  "civility": "M.",
  "firstName": "Jean",
  "name": "Dupont",
  "company": "ABC Corp",
  "sector": "Technologie",
  "notes": "Client depuis 2020",
  "segments": [1, 2]
}
```

**Réponse :**

```json
{
  "id": 1,
  "number": "+2250777104936",
  "civility": "M.",
  "firstName": "Jean",
  "name": "Dupont",
  "company": "ABC Corp",
  "sector": "Technologie",
  "notes": "Client depuis 2020",
  "dateAdded": "2025-03-15 10:30:45",
  "segments": [
    {
      "id": 1,
      "type": "country_code",
      "value": "225"
    },
    {
      "id": 2,
      "type": "country",
      "value": "Côte d'Ivoire"
    },
    {
      "id": 3,
      "type": "operator",
      "value": "Orange"
    },
    {
      "id": 4,
      "type": "number_type",
      "value": "Mobile"
    }
  ],
  "customSegments": [
    {
      "id": 1,
      "name": "Client VIP",
      "description": "Clients importants"
    },
    {
      "id": 2,
      "name": "Prospect",
      "description": "Prospects à contacter"
    }
  ]
}
```

#### Mettre à jour un numéro de téléphone

```
PUT /api.php?endpoint=<id>
```

**Paramètres :**

- `<id>` : ID du numéro de téléphone

**Corps de la requête :**

```json
{
  "name": "Nouveau Nom",
  "company": "Nouvelle Entreprise",
  "segments": [1, 3]
}
```

**Réponse :**

```json
{
  "id": 1,
  "number": "+2250777104936",
  "civility": "M.",
  "firstName": "Jean",
  "name": "Nouveau Nom",
  "company": "Nouvelle Entreprise",
  "sector": "Technologie",
  "notes": "Client depuis 2020",
  "dateAdded": "2025-03-15 10:30:45",
  "segments": [
    {
      "id": 1,
      "type": "country_code",
      "value": "225"
    },
    {
      "id": 2,
      "type": "country",
      "value": "Côte d'Ivoire"
    },
    {
      "id": 3,
      "type": "operator",
      "value": "Orange"
    },
    {
      "id": 4,
      "type": "number_type",
      "value": "Mobile"
    }
  ],
  "customSegments": [
    {
      "id": 1,
      "name": "Client VIP",
      "description": "Clients importants"
    },
    {
      "id": 3,
      "name": "Santé",
      "description": "Secteur de la santé"
    }
  ]
}
```

#### Supprimer un numéro de téléphone

```
DELETE /api.php?endpoint=<id>
```

**Paramètres :**

- `<id>` : ID du numéro de téléphone

**Réponse :**

- Code HTTP 204 (No Content) en cas de succès

#### Rechercher des numéros de téléphone

```
GET /api.php?endpoint=phones/search&q=<query>
```

**Paramètres de requête :**

- `q` : Terme de recherche
- `limit` (optionnel) : Nombre maximum de numéros à retourner (défaut : 100)
- `offset` (optionnel) : Index de départ pour la pagination (défaut : 0)

**Réponse :**

```json
{
  "phoneNumbers": [
    {
      "id": 1,
      "number": "+2250777104936",
      "civility": "M.",
      "firstName": "Jean",
      "name": "Dupont",
      "company": "ABC Corp",
      "sector": "Technologie",
      "notes": "Client depuis 2020",
      "dateAdded": "2025-03-15 10:30:45",
      "segments": [...],
      "customSegments": [...]
    }
  ],
  "limit": 100,
  "offset": 0
}
```

### Segmentation

#### Segmenter un numéro sans l'enregistrer

```
POST /api.php?endpoint=segment
```

**Corps de la requête :**

```json
{
  "number": "+2250777104936",
  "civility": "M.",
  "firstName": "Jean",
  "name": "Dupont",
  "company": "ABC Corp"
}
```

**Réponse :**

```json
{
  "number": "+2250777104936",
  "civility": "M.",
  "firstName": "Jean",
  "name": "Dupont",
  "company": "ABC Corp",
  "segments": [
    {
      "type": "country_code",
      "value": "225"
    },
    {
      "type": "country",
      "value": "Côte d'Ivoire"
    },
    {
      "type": "operator",
      "value": "Orange"
    },
    {
      "type": "number_type",
      "value": "Mobile"
    }
  ]
}
```

#### Segmenter plusieurs numéros sans les enregistrer

```
POST /api.php?endpoint=batch-segment
```

**Corps de la requête :**

```json
{
  "numbers": ["+2250777104936", "+2250141399354", "+2250546560953"]
}
```

**Réponse :**

```json
{
  "results": [
    {
      "number": "+2250777104936",
      "valid": true,
      "segments": [
        {
          "type": "country_code",
          "value": "225"
        },
        {
          "type": "country",
          "value": "Côte d'Ivoire"
        },
        {
          "type": "operator",
          "value": "Orange"
        },
        {
          "type": "number_type",
          "value": "Mobile"
        }
      ]
    },
    {
      "number": "+2250141399354",
      "valid": true,
      "segments": [...]
    },
    {
      "number": "+2250546560953",
      "valid": true,
      "segments": [...]
    }
  ],
  "summary": {
    "total": 3,
    "valid": 3,
    "invalid": 0
  }
}
```

#### Créer plusieurs numéros en lot

```
POST /api.php?endpoint=batch-phones
```

**Corps de la requête :**

```json
{
  "numbers": ["+2250777104936", "+2250141399354", "+2250546560953"]
}
```

**Réponse :**

```json
{
  "results": [
    {
      "number": "+2250777104936",
      "valid": true,
      "id": 1,
      "segments": [...]
    },
    {
      "number": "+2250141399354",
      "valid": true,
      "id": 2,
      "segments": [...]
    },
    {
      "number": "+2250546560953",
      "valid": true,
      "id": 3,
      "segments": [...]
    }
  ],
  "summary": {
    "total": 3,
    "valid": 3,
    "invalid": 0,
    "created": 3,
    "duplicates": 0
  }
}
```

### Segments Personnalisés

#### Lister tous les segments personnalisés

```
GET /api.php?endpoint=segments
```

**Réponse :**

```json
[
  {
    "id": 1,
    "name": "Client VIP",
    "description": "Clients importants"
  },
  {
    "id": 2,
    "name": "Prospect",
    "description": "Prospects à contacter"
  }
]
```

#### Obtenir un segment personnalisé spécifique

```
GET /api.php?endpoint=segments/<id>
```

**Paramètres :**

- `<id>` : ID du segment personnalisé

**Réponse :**

```json
{
  "id": 1,
  "name": "Client VIP",
  "description": "Clients importants",
  "phoneNumbers": [
    {
      "id": 1,
      "number": "+2250777104936",
      "name": "Dupont"
    },
    {
      "id": 3,
      "number": "+2250546560953",
      "name": "Traoré"
    }
  ]
}
```

#### Créer un segment personnalisé

```
POST /api.php?endpoint=segments
```

**Corps de la requête :**

```json
{
  "name": "Nouveau Segment",
  "description": "Description du nouveau segment"
}
```

**Réponse :**

```json
{
  "id": 3,
  "name": "Nouveau Segment",
  "description": "Description du nouveau segment"
}
```

#### Mettre à jour un segment personnalisé

```
PUT /api.php?endpoint=segments/<id>
```

**Paramètres :**

- `<id>` : ID du segment personnalisé

**Corps de la requête :**

```json
{
  "name": "Segment Modifié",
  "description": "Nouvelle description"
}
```

**Réponse :**

```json
{
  "id": 3,
  "name": "Segment Modifié",
  "description": "Nouvelle description"
}
```

#### Supprimer un segment personnalisé

```
DELETE /api.php?endpoint=segments/<id>
```

**Paramètres :**

- `<id>` : ID du segment personnalisé

**Réponse :**

- Code HTTP 204 (No Content) en cas de succès

#### Ajouter un numéro à un segment

```
POST /api.php?endpoint=phones/<phoneId>/segments/<segmentId>
```

**Paramètres :**

- `<phoneId>` : ID du numéro de téléphone
- `<segmentId>` : ID du segment personnalisé

**Réponse :**

- Code HTTP 204 (No Content) en cas de succès

#### Retirer un numéro d'un segment

```
DELETE /api.php?endpoint=phones/<phoneId>/segments/<segmentId>
```

**Paramètres :**

- `<phoneId>` : ID du numéro de téléphone
- `<segmentId>` : ID du segment personnalisé

**Réponse :**

- Code HTTP 204 (No Content) en cas de succès

#### Obtenir les numéros d'un segment

```
GET /api.php?endpoint=segments/<id>/phones
```

**Paramètres :**

- `<id>` : ID du segment personnalisé
- `limit` (optionnel) : Nombre maximum de numéros à retourner (défaut : 100)
- `offset` (optionnel) : Index de départ pour la pagination (défaut : 0)

**Réponse :**

```json
{
  "phoneNumbers": [
    {
      "id": 1,
      "number": "+2250777104936",
      "civility": "M.",
      "firstName": "Jean",
      "name": "Dupont",
      "company": "ABC Corp",
      "sector": "Technologie",
      "notes": "Client depuis 2020",
      "dateAdded": "2025-03-15 10:30:45",
      "segments": [...],
      "customSegments": [...]
    }
  ],
  "total": 25,
  "limit": 100,
  "offset": 0
}
```

### Import/Export

#### Importer des numéros depuis un fichier CSV

```
POST /api.php?endpoint=import-csv
```

**Corps de la requête :**

- Format : `multipart/form-data`
- Champs :
  - `csv_file` : Fichier CSV à importer
  - `has_header` (optionnel) : Indique si le fichier a une ligne d'en-tête (défaut : true)
  - `phone_column` (optionnel) : Index de la colonne contenant les numéros (défaut : 0)
  - `civility_column` (optionnel) : Index de la colonne contenant les civilités (défaut : -1)
  - `first_name_column` (optionnel) : Index de la colonne contenant les prénoms (défaut : -1)
  - `name_column` (optionnel) : Index de la colonne contenant les noms (défaut : -1)
  - `company_column` (optionnel) : Index de la colonne contenant les entreprises (défaut : -1)
  - `sector_column` (optionnel) : Index de la colonne contenant les secteurs (défaut : -1)
  - `notes_column` (optionnel) : Index de la colonne contenant les notes (défaut : -1)
  - `skip_invalid` (optionnel) : Ignorer les numéros invalides (défaut : true)
  - `segment_immediately` (optionnel) : Segmenter les numéros immédiatement (défaut : true)

**Réponse :**

```json
{
  "status": "success",
  "message": "Import completed successfully",
  "summary": {
    "total": 100,
    "valid": 95,
    "invalid": 5,
    "created": 90,
    "duplicates": 5
  },
  "invalid_numbers": [
    {
      "number": "123456",
      "reason": "Invalid phone number format"
    }
  ]
}
```

#### Importer des numéros depuis du texte

```
POST /api.php?endpoint=import-text
```

**Corps de la requête :**

```json
{
  "numbers": "+2250777104936, +2250141399354, +2250546560953",
  "skip_invalid": true,
  "segment_immediately": true
}
```

**Réponse :**

```json
{
  "status": "success",
  "message": "Import completed successfully",
  "summary": {
    "total": 3,
    "valid": 3,
    "invalid": 0,
    "created": 3,
    "duplicates": 0
  },
  "invalid_numbers": []
}
```

#### Exporter des numéros au format CSV

```
GET /api.php?endpoint=export-csv
```

**Paramètres de requête :**

- `include_headers` (optionnel) : Inclure une ligne d'en-tête (défaut : true)
- `delimiter` (optionnel) : Délimiteur à utiliser (défaut : ",")
- `enclosure` (optionnel) : Caractère d'encadrement (défaut : '"')
- `escape` (optionnel) : Caractère d'échappement (défaut : "\\")
- `include_segments` (optionnel) : Inclure les segments (défaut : true)
- `include_contact_info` (optionnel) : Inclure les informations de contact (défaut : true)
- `download_file` (optionnel) : Télécharger le fichier (défaut : true)
- `filename` (optionnel) : Nom du fichier (défaut : "phone_numbers_export_YYYY-MM-DD_HH-MM-SS.csv")
- `search` (optionnel) : Filtrer les numéros par terme de recherche
- `limit` (optionnel) : Nombre maximum de numéros à exporter (défaut : 5000)
- `offset` (optionnel) : Index de départ pour la pagination (défaut : 0)

**Réponse :**

- Contenu CSV si `download_file` est true
- Objet JSON avec le statut et le message en cas d'erreur

#### Exporter des numéros au format Excel

```
GET /api.php?endpoint=export-excel
```

**Paramètres de requête :**

- `include_headers` (optionnel) : Inclure une ligne d'en-tête (défaut : true)
- `include_segments` (optionnel) : Inclure les segments (défaut : true)
- `include_contact_info` (optionnel) : Inclure les informations de contact (défaut : true)
- `download_file` (optionnel) : Télécharger le fichier (défaut : true)
- `filename` (optionnel) : Nom du fichier (défaut : "phone_numbers_export_YYYY-MM-DD_HH-MM-SS.xlsx")
- `search` (optionnel) : Filtrer les numéros par terme de recherche
- `limit` (optionnel) : Nombre maximum de numéros à exporter (défaut : 5000)
- `offset` (optionnel) : Index de départ pour la pagination (défaut : 0)

**Réponse :**

- Contenu Excel si `download_file` est true
- Objet JSON avec le statut et le message en cas d'erreur

### SMS

#### Obtenir les segments pour l'envoi de SMS

```
GET /api.php?endpoint=sms/segments
```

**Réponse :**

```json
{
  "status": "success",
  "segments": [
    {
      "id": 1,
      "name": "Client VIP",
      "description": "Clients importants",
      "phoneNumberCount": 25
    },
    {
      "id": 2,
      "name": "Prospect",
      "description": "Prospects à contacter",
      "phoneNumberCount": 50
    }
  ]
}
```

#### Envoyer un SMS à un numéro

```
POST /api.php?endpoint=sms/send
```

**Corps de la requête :**

```json
{
  "number": "+2250777104936",
  "message": "Bonjour, ceci est un message de test."
}
```

**Réponse :**

```json
{
  "status": "success",
  "result": {
    "messageId": "SMSxxxxxxxx",
    "status": "SENT",
    "recipient": "+2250777104936",
    "senderAddress": "tel:+2250595016840",
    "senderName": "225HBC"
  }
}
```

#### Envoyer un SMS à plusieurs numéros

```
POST /api.php?endpoint=sms/bulk
```

**Corps de la requête :**

```json
{
  "numbers": ["+2250777104936", "+2250141399354", "+2250546560953"],
  "message": "Bonjour, ceci est un message de test."
}
```

**Réponse :**

```json
{
  "status": "success",
  "results": [
    {
      "number": "+2250777104936",
      "status": "success",
      "messageId": "SMSxxxxxxxx"
    },
    {
      "number": "+2250141399354",
      "status": "success",
      "messageId": "SMSyyyyyyyy"
    },
    {
      "number": "+2250546560953",
      "status": "success",
      "messageId": "SMSzzzzzzzz"
    }
  ],
  "summary": {
    "total": 3,
    "successful": 3,
    "failed": 0
  }
}
```

#### Envoyer un SMS à un segment

```
POST /api.php?endpoint=sms/segments/<id>/send
```

**Paramètres :**

- `<id>` : ID du segment personnalisé

**Corps de la requête :**

```json
{
  "message": "Bonjour, ceci est un message de test."
}
```

**Réponse :**

```json
{
  "status": "success",
  "segment": {
    "id": 1,
    "name": "Client VIP"
  },
  "results": [
    {
      "number": "+2250777104936",
      "status": "success",
      "messageId": "SMSxxxxxxxx"
    },
    {
      "number": "+2250141399354",
      "status": "success",
      "messageId": "SMSyyyyyyyy"
    }
  ],
  "summary": {
    "total": 2,
    "successful": 2,
    "failed": 0
  }
}
```

## Codes d'Erreur HTTP

- `200 OK` : Requête traitée avec succès
- `201 Created` : Ressource créée avec succès
- `204 No Content` : Requête traitée avec succès, pas de contenu à renvoyer
- `400 Bad Request` : Requête invalide (paramètres manquants, format incorrect, etc.)
- `404 Not Found` : Ressource non trouvée
- `500 Internal Server Error` : Erreur interne du serveur

## Exemples d'Utilisation

### Exemple 1 : Segmenter un numéro

```bash
curl -X POST \
  "http://example.com/api.php?endpoint=segment" \
  -H "Content-Type: application/json" \
  -d '{
    "number": "+2250777104936"
  }'
```

### Exemple 2 : Créer un segment personnalisé

```bash
curl -X POST \
  "http://example.com/api.php?endpoint=segments" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Nouveau Segment",
    "description": "Description du nouveau segment"
  }'
```

### Exemple 3 : Exporter des numéros au format CSV

```bash
curl -X GET \
  "http://example.com/api.php?endpoint=export-csv&include_segments=true&limit=1000" \
  -o "export.csv"
```
