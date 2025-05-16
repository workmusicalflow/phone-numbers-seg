# Configuration du Webhook WhatsApp avec stockage automatique

## 1. Démarrer localtunnel

```bash
lt --port 8080 --subdomain oracle-whatsapp
```

## 2. URL du nouveau webhook

Le nouveau webhook avec stockage automatique est maintenant disponible à :

```
https://oracle-whatsapp.loca.lt/whatsapp/webhook.php
```

(Remplacer `oracle-whatsapp` par votre sous-domaine localtunnel)

## 3. Mettre à jour dans Meta

1. Allez sur https://developers.facebook.com
2. Sélectionnez votre app
3. WhatsApp > Configuration
4. Dans la section Webhook :
   - URL : `https://oracle-whatsapp.loca.lt/whatsapp/webhook.php`
   - Token : `oracle_whatsapp_verify_token_2025`
5. Sauvegarder les changements

## 4. Caractéristiques du nouveau webhook

Le nouveau webhook `/whatsapp/webhook.php` :

- ✅ Vérifie la signature Meta pour la sécurité
- ✅ Stocke automatiquement tous les messages entrants
- ✅ Met à jour les statuts des messages envoyés
- ✅ Gère les différents types de messages (texte, image, audio, etc.)
- ✅ Associe les messages à l'utilisateur Oracle
- ✅ Sauvegarde les métadonnées complètes
- ✅ Log toutes les erreurs

## 5. Vérifier le stockage

Après avoir reçu des messages, vérifiez qu'ils sont bien stockés :

```bash
php scripts/test-whatsapp-webhook-storage.php
```

## 6. Structure des données stockées

### Messages entrants
- `wabaMessageId` : ID unique du message WhatsApp
- `phoneNumber` : Numéro de l'expéditeur
- `direction` : 'INCOMING'
- `type` : Type du message (text, image, audio, etc.)
- `content` : Contenu du message
- `status` : 'received'
- `metadata` : Données complètes du message

### Mises à jour de statut
- `status` : sent, delivered, read, failed
- `errors` : Détails des erreurs si échec
- `metadata.pricing` : Information de tarification

## 7. Monitoring

Surveillez les logs :

```bash
# Logs de l'application
tail -f var/logs/app.log

# Logs du webhook (ancienne version)
tail -f var/logs/whatsapp/webhook_*.json
```

## 8. Troubleshooting

### Le webhook ne reçoit rien
1. Vérifiez que localtunnel est actif
2. Vérifiez l'URL dans Meta
3. Testez la vérification :
   ```bash
   curl "https://oracle-whatsapp.loca.lt/whatsapp/webhook.php?hub_mode=subscribe&hub_verify_token=oracle_whatsapp_verify_token_2025&hub_challenge=test"
   ```

### Les messages ne sont pas stockés
1. Vérifiez les permissions de la base de données
2. Vérifiez que les colonnes `metadata` et `errors` existent
3. Consultez les logs d'erreur

### Erreur de signature
1. Vérifiez que `app_secret` est configuré dans WhatsApp config
2. Si en développement, la vérification peut être désactivée temporairement