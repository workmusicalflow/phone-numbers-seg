# WhatsApp API Endpoints

## Structure actuelle

- **send-template.php** : Endpoint principal pour l'envoi de templates ✅
- **upload.php** : Upload de médias WhatsApp ✅
- **status.php** : Vérification du statut des messages ✅
- **templates/approved.php** : Récupération des templates approuvés ✅

## Webhooks (dans /public/whatsapp/)

- **webhook.php** : Webhook principal pour les callbacks Meta ✅
- **webhook-simple.php** : Version simplifiée du webhook

## Fichiers archivés

- **/archive/whatsapp/api/send-template-v1.php** : Ancienne version obsolète
- **/tests/mocks/whatsapp/send-template-mock.php** : Mock pour tests locaux

## Format de requête pour send-template.php

**Endpoint** : `POST /api/whatsapp/send-template.php`

**Headers requis** :
```
Content-Type: application/json
Authorization: Bearer {token} (si authentification activée)
```

**Corps de la requête** :
```json
{
  "recipientPhoneNumber": "+2250700000000",
  "templateName": "hello_world",
  "templateLanguage": "fr",
  "bodyVariables": ["John", "Doe"],
  "headerMediaUrl": "https://example.com/image.jpg",
  "headerMediaId": "media_id_123",
  "templateComponentsJsonString": "{...}"
}
```

**Réponse en cas de succès** :
```json
{
  "success": true,
  "messageId": "wamid.xxxxx",
  "timestamp": "2025-05-27T10:00:00Z"
}
```

**Réponse en cas d'erreur** :
```json
{
  "success": false,
  "error": "Description de l'erreur"
}
```

## Notes importantes

1. **Validation des numéros** : Les numéros doivent être au format E.164 (+XXXXXXXXXXXX)
2. **Templates** : Seuls les templates approuvés par Meta peuvent être utilisés
3. **Rate limiting** : Respecter les limites de l'API Meta
4. **Logs** : Tous les envois sont loggés dans `/var/logs/whatsapp/`

Consulter `/docs/whatsapp-api-endpoints-clarification.md` pour plus de détails.