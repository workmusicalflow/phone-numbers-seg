# Guide de test du webhook WhatsApp

Ce document explique comment tester le webhook WhatsApp pour l'intégration avec l'API WhatsApp Business Cloud.

## Prérequis

- PHP 8.3 installé et configuré
- Composer installé
- Node.js installé
- Localtunnel installé globalement (`npm install -g localtunnel`)
- Compte WhatsApp Business et accès à l'API Cloud

## Configuration du serveur local

1. Assurez-vous que le serveur PHP est en cours d'exécution:

```bash
cd /Users/ns2poportable/Desktop/phone-numbers-seg
php -S localhost:8000 -t public
```

2. Vérifiez que le webhook est accessible en local:

```bash
curl "http://localhost:8000/whatsapp/webhook.php?hub_mode=subscribe&hub_verify_token=oracle_whatsapp_webhook_verification_token&hub_challenge=123456789"
```

Vous devriez recevoir "123456789" comme réponse si tout fonctionne correctement.

## Exposition du webhook avec Localtunnel

Pour que Meta puisse envoyer des notifications à votre webhook, il doit être accessible depuis Internet. Nous utilisons Localtunnel pour cela:

```bash
./scripts/start-webhook-tunnel.sh 8000
```

Ce script créera une URL publique qui ressemble à `https://oracle-whatsapp-webhook.loca.lt`.

## Configuration dans Meta Developers Dashboard

1. Connectez-vous à votre [compte Meta pour développeurs](https://developers.facebook.com)
2. Naviguez vers votre application
3. Allez dans la configuration WhatsApp > Webhook
4. Configurez le webhook avec:
   - URL du callback: `https://oracle-whatsapp-webhook.loca.lt/whatsapp/webhook.php`
   - Token de vérification: `oracle_whatsapp_webhook_verification_token`
   - Champs d'abonnement: `messages`, `message_status_updates`

## Tests locaux

Vous pouvez tester le webhook localement avec le script fourni:

```bash
./scripts/test-whatsapp-webhook.sh
```

Ce script effectue:
1. Un test GET pour simuler la vérification du webhook par Meta
2. Un test POST pour simuler la réception d'un message WhatsApp

## Vérification des logs

Les logs des webhooks sont écrits dans:

```
/Users/ns2poportable/Desktop/phone-numbers-seg/var/logs/whatsapp_webhook_*.json
```

Vous pouvez les consulter pour vérifier les données reçues en cours de développement.

## Dépannage

### Problèmes courants

1. **Erreur 500 lors de la vérification:**
   - Vérifiez que le token de vérification dans `src/config/whatsapp.php` correspond à celui utilisé dans la requête.
   - Vérifiez les permissions du dossier `/var/logs`.

2. **Message reçu mais non traité:**
   - Examinez les logs pour identifier l'erreur.
   - Vérifiez que la table WhatsAppMessage existe et est accessible.

3. **Problèmes de connexion Localtunnel:**
   - Si Localtunnel est bloqué, essayez un autre service comme [serveo.net](https://serveo.net/).

### Commandes utiles

- Vérifier les erreurs PHP:
  ```bash
  tail -f /Users/ns2poportable/Desktop/phone-numbers-seg/var/logs/app.log
  ```

- Voir les derniers webhooks reçus:
  ```bash
  ls -lt /Users/ns2poportable/Desktop/phone-numbers-seg/var/logs/whatsapp_webhook_*
  ```

- Afficher le contenu d'un webhook:
  ```bash
  cat /Users/ns2poportable/Desktop/phone-numbers-seg/var/logs/whatsapp_webhook_*.json | jq
  ```