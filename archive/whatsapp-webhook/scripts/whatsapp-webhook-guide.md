# Guide de configuration du webhook WhatsApp

## Étape 1 : Démarrer localtunnel

```bash
lt --port 8080 --subdomain oracle-whatsapp
```

Notez l'URL générée (ex: https://oracle-whatsapp.loca.lt)

## Étape 2 : Configuration dans Meta

1. Allez sur https://developers.facebook.com
2. Sélectionnez votre app
3. WhatsApp > Configuration
4. Dans la section Webhook, cliquez sur "Modifier"
5. Entrez :
   - URL du webhook : `https://oracle-whatsapp.loca.lt/whatsapp/webhook-simple.php`
   - Token de vérification : `oracle_whatsapp_verify_token_2025`
6. Cliquez sur "Vérifier et enregistrer"
7. Abonnez-vous aux champs "messages"

## Étape 3 : Tester la réception des messages

### 3.1 Démarrer le monitoring

Dans un nouveau terminal :
```bash
php scripts/monitor-webhook.php
```

### 3.2 Envoyer un message WhatsApp

1. Ouvrez WhatsApp sur votre téléphone
2. Envoyez un message au numéro de votre Business WhatsApp
3. Observez les logs dans le terminal de monitoring

### 3.3 Vérifier les logs

Les logs sont aussi sauvegardés dans :
```
var/logs/whatsapp/webhook_[date].json
```

## Étape 4 : Répondre aux messages

Une fois qu'un message est reçu, vous avez 24 heures pour répondre avec du texte :

```bash
php scripts/test-whatsapp-text-reply.php
```

## Structure des webhooks reçus

### Message entrant
```json
{
  "entry": [{
    "changes": [{
      "value": {
        "messages": [{
          "from": "2250777104936",
          "id": "wamid.xxx",
          "type": "text",
          "text": {
            "body": "Hello"
          }
        }]
      }
    }]
  }]
}
```

### Statut de message
```json
{
  "entry": [{
    "changes": [{
      "value": {
        "statuses": [{
          "id": "wamid.xxx",
          "status": "delivered",
          "recipient_id": "2250777104936"
        }]
      }
    }]
  }]
}
```

## Troubleshooting

### Le webhook ne reçoit rien

1. Vérifiez que localtunnel est actif
2. Vérifiez l'URL dans Meta
3. Testez manuellement :
   ```bash
   curl -X GET "https://oracle-whatsapp.loca.lt/whatsapp/webhook-simple.php?hub_mode=subscribe&hub_verify_token=oracle_whatsapp_verify_token_2025&hub_challenge=test"
   ```

### Erreur "Message outside 24-hour window"

- Cette erreur survient si vous essayez d'envoyer un message texte sans conversation active
- Solution : L'utilisateur doit d'abord vous envoyer un message

### Les logs ne s'affichent pas

- Vérifiez les permissions du dossier `var/logs/whatsapp/`
- Créez le dossier s'il n'existe pas :
  ```bash
  mkdir -p var/logs/whatsapp
  chmod 777 var/logs/whatsapp
  ```