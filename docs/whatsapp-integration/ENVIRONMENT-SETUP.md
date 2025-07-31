# Configuration de l'environnement WhatsApp

## Variables d'environnement requises

Pour utiliser l'intégration WhatsApp dans Oracle, vous devez configurer les variables d'environnement suivantes :

### Variables obligatoires

```bash
# Configuration API Meta
WHATSAPP_API_VERSION=v22.0
WHATSAPP_PHONE_NUMBER_ID=votre_phone_number_id
WHATSAPP_WABA_ID=votre_waba_id
WHATSAPP_APP_ID=votre_app_id
WHATSAPP_ACCESS_TOKEN=votre_access_token_longue_duree

# Sécurité Webhook
WHATSAPP_WEBHOOK_VERIFY_TOKEN=votre_token_verification_webhook
WHATSAPP_WEBHOOK_CALLBACK_URL=https://votre-domaine.com/whatsapp/webhook.php
```

### Variables optionnelles

```bash
# Configuration de journalisation et comportement
WHATSAPP_LOG_INCOMING_MESSAGES=true
WHATSAPP_AUTO_MARK_AS_READ=true
```

## Obtention des credentials

1. **App ID** : Visible dans le tableau de bord de votre application Meta
2. **Phone Number ID** : Récupérable via l'API ou dans WhatsApp Manager
3. **WABA ID** : ID du compte WhatsApp Business
4. **Access Token** : Générez un token système de longue durée (60 jours)
5. **Webhook Verify Token** : Créez une chaîne secrète unique

## Configuration locale (développement)

1. Copiez le fichier d'exemple :
   ```bash
   cp .env.whatsapp.example .env.whatsapp
   ```

2. Modifiez `.env.whatsapp` avec vos valeurs

3. Pour le développement local avec localtunnel, utilisez :
   ```bash
   ./scripts/start-webhook-server.sh
   ./scripts/start-webhook-tunnel.sh
   ```

## Configuration de production

- Utilisez un gestionnaire de secrets (ex: AWS Secrets Manager, HashiCorp Vault)
- Ne jamais committer les tokens d'accès dans le code
- Renouvelez le token d'accès avant expiration

## Validation de la configuration

Utilisez le script de vérification :
```bash
php scripts/check-whatsapp-config.php
```

Ce script vérifiera que toutes les variables sont correctement configurées.