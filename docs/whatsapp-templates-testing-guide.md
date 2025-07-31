# Guide de test de l'intégration des templates WhatsApp

Ce guide explique comment tester l'intégration avec l'API WhatsApp Templates de Meta pour s'assurer que votre application récupère correctement les templates réels plutôt que d'utiliser les templates de fallback.

## Prérequis

- Avoir un compte WhatsApp Business
- Disposer des identifiants API Meta (jeton d'accès, ID du compte WhatsApp Business, ID du numéro de téléphone)
- PHP 8.3 et Composer installés
- Accès au code source et à l'application

## 1. Vérification des identifiants API Meta

Avant de tester l'intégration, assurez-vous que vos identifiants API sont correctement configurés :

```bash
# Ouvrir et vérifier le fichier .env
cat .env | grep WHATSAPP
```

Les variables suivantes doivent être définies :
- `WHATSAPP_APP_ID`
- `WHATSAPP_PHONE_NUMBER_ID`
- `WHATSAPP_WABA_ID` (WhatsApp Business Account ID)
- `WHATSAPP_API_VERSION` (généralement v22.0)
- `WHATSAPP_ACCESS_TOKEN` ou `WHATSAPP_API_TOKEN`

## 2. Tester la connexion directe à l'API Meta

Utilisez le script de test cURL pour vérifier que vous pouvez vous connecter directement à l'API Meta :

```bash
php scripts/test-whatsapp-templates-fetch.php --curl-only
```

Si la connexion réussit, vous devriez voir les templates de votre compte WhatsApp Business s'afficher. En cas d'échec, vérifiez le message d'erreur pour identifier le problème.

## 3. Tester l'implémentation complète

Utilisez le script de test complet pour vérifier que l'API client et le service de templates fonctionnent correctement :

```bash
php scripts/test-whatsapp-templates-fetch.php -v
```

L'option `-v` active le mode verbeux qui affiche plus de détails sur les templates récupérés.

## 4. Tester l'endpoint API

Testez l'endpoint API REST qui est utilisé par le frontend :

```bash
php scripts/test-api-whatsapp-templates.php -v
```

Cela vérifie que l'API renvoie correctement les templates et que les métadonnées (source, fallbacks, etc.) sont présentes et cohérentes.

## 5. Tester avec l'interface utilisateur

1. Lancez l'application frontend :
   ```bash
   cd frontend
   npm run dev
   ```

2. Accédez à l'interface de sélection des templates WhatsApp
3. Inspectez la console du navigateur pour voir les logs détaillés
4. Vérifiez que les templates affichés correspondent à ceux de votre compte Meta

## 6. Tester les différents scénarios de fallback

### Forcer l'utilisation de l'API Meta sans fallback

```bash
php scripts/test-api-whatsapp-templates.php -p "force_meta=true&no_fallback=true"
```

Cela devrait soit réussir avec les templates Meta, soit échouer si l'API n'est pas accessible.

### Forcer l'utilisation du cache

```bash
php scripts/test-api-whatsapp-templates.php -p "use_cache=true&force_refresh=false"
```

### Activer le mode debug pour un diagnostic détaillé

```bash
php scripts/test-api-whatsapp-templates.php -p "debug=true" -d
```

## 7. Vérifier les logs

Consultez les logs pour détecter les problèmes éventuels :

```bash
# Logs PHP
tail -f var/logs/app.log

# Logs Apache/Nginx (selon votre configuration)
tail -f /var/log/apache2/error.log
```

## Résolution des problèmes courants

### Erreur "Access token not valid"

Vérifiez que votre token d'accès est valide et n'a pas expiré. Vous pouvez le regénérer dans le tableau de bord Meta for Developers.

### Erreur "Permission denied"

Vérifiez que votre application Meta a les permissions nécessaires pour accéder à l'API WhatsApp Business.

### Aucun template n'est affiché

1. Vérifiez que vous avez bien des templates approuvés dans votre compte WhatsApp Business
2. Assurez-vous que l'ID du compte WhatsApp Business (WABA_ID) est correct
3. Testez la connexion directe avec l'option `--curl-only`

### Templates de fallback affichés au lieu des templates réels

1. Vérifiez les logs pour identifier la cause de l'échec de la connexion à l'API Meta
2. Testez avec les paramètres `force_meta=true&debug=true` pour obtenir plus d'informations
3. Assurez-vous que le format de réponse attendu par l'application correspond à celui fourni par l'API Meta

## Vérification des templates dans votre compte WhatsApp Business

Pour vérifier quels templates sont réellement approuvés dans votre compte :

1. Connectez-vous à [Facebook Business Manager](https://business.facebook.com/)
2. Accédez à votre compte WhatsApp Business
3. Allez dans la section "Templates de messages"
4. Vérifiez quels templates sont marqués comme "Approuvés"

Comparez cette liste avec les templates récupérés par votre application pour vous assurer qu'ils correspondent.