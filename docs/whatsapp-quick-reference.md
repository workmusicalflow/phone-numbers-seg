# WhatsApp API - Référence Rapide

## 🚨 Règle #1 : La fenêtre de 24 heures

**JAMAIS** de messages texte sans conversation ouverte !

```
Utilisateur envoie message → Fenêtre ouverte 24h → Vous pouvez envoyer des messages texte
                                     ↓
                                Après 24h → SEULEMENT des templates
```

## Vérification rapide avant envoi

```php
// TOUJOURS vérifier avant d'envoyer
if ($messageType === 'text' && !$conversationWindow->isOpen()) {
    throw new Exception("Utiliser un template !");
}
```

## Erreurs communes et solutions

| Erreur | Signification | Solution |
|--------|--------------|----------|
| `#131030` | Hors fenêtre 24h | Utiliser un template |
| `#131047` | Re-engagement interdit | Utiliser un template |
| `#131051` | Type de message interdit | Vérifier la fenêtre |

## Checklist de test

- [ ] Tester envoi avec fenêtre ouverte (< 24h)
- [ ] Tester envoi avec fenêtre fermée (> 24h)
- [ ] Tester envoi de template hors fenêtre
- [ ] Vérifier les webhooks de réception
- [ ] Vérifier le tracking des conversations

## Templates essentiels

1. **hello_world** - Test de base
2. **welcome_message** - Accueil nouveau client
3. **order_confirmation** - Confirmation commande
4. **customer_support** - Initier support
5. **reengagement** - Réactiver client inactif

## Commandes utiles

```bash
# Tester l'envoi dans la fenêtre
php scripts/test-whatsapp-text-with-di.php

# Monitoring webhook
php scripts/monitor-webhook.php

# Vérifier les logs
tail -f var/logs/whatsapp/webhook_*.json
```

## URLs importantes

- [Meta Developers](https://developers.facebook.com)
- [WhatsApp Manager](https://business.facebook.com/wa/manage/)
- [Documentation API](https://developers.facebook.com/docs/whatsapp/cloud-api)
- [Codes d'erreur](https://developers.facebook.com/docs/whatsapp/cloud-api/support/error-codes)