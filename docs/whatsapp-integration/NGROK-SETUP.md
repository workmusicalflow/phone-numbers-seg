# Configuration de ngrok pour les tests des webhooks WhatsApp

Ce guide explique comment configurer et utiliser ngrok pour tester les webhooks WhatsApp en développement local.

## Pourquoi ngrok ?

Pour recevoir des notifications de l'API WhatsApp Business, Meta a besoin d'un point de terminaison (webhook) accessible publiquement via HTTPS. En développement local, votre serveur n'est généralement pas accessible depuis Internet. C'est là qu'intervient ngrok, qui crée un tunnel sécurisé vers votre serveur local.

## Installation de ngrok

### 1. Téléchargement

Téléchargez ngrok depuis [le site officiel](https://ngrok.com/download) ou installez-le via un gestionnaire de paquets :

**macOS (avec Homebrew)**:
```bash
brew install ngrok
```

**Linux (avec snap)**:
```bash
snap install ngrok
```

**Windows (avec Chocolatey)**:
```bash
choco install ngrok
```

### 2. Création d'un compte

1. Créez un compte gratuit sur [ngrok.com](https://ngrok.com/signup)
2. Récupérez votre jeton d'authentification dans le tableau de bord

### 3. Configuration

Authentifiez votre installation ngrok avec votre jeton :

```bash
ngrok config add-authtoken <votre-token>
```

## Utilisation avec Oracle pour les webhooks WhatsApp

### 1. Démarrage du serveur PHP local

Lancez d'abord votre serveur PHP local sur le port habituel (par exemple 8000) :

```bash
cd /Users/ns2poportable/Desktop/phone-numbers-seg
php -S localhost:8000 -t public
```

### 2. Démarrage de ngrok

Dans un nouveau terminal, lancez ngrok en pointant vers le même port :

```bash
ngrok http 8000
```

ngrok affichera une interface avec des informations sur le tunnel, notamment :
- L'URL publique (ex: `https://a1b2c3d4e5f6.ngrok.io`)
- Le statut des connexions
- Les requêtes HTTP reçues

### 3. Configuration du webhook Meta avec l'URL ngrok

1. Copiez l'URL HTTPS fournie par ngrok (ex: `https://a1b2c3d4e5f6.ngrok.io`)
2. Dans la console Meta for Developers, configurez votre webhook WhatsApp avec :
   - URL du webhook : `https://a1b2c3d4e5f6.ngrok.io/whatsapp/webhook.php`
   - Token de vérification : celui configuré dans `src/config/whatsapp.php`
   - Champs à abonner : `messages`, `message_status_updates`

### 4. Test du webhook

Envoyez un message WhatsApp au numéro de test associé à votre compte WhatsApp Business. Vous devriez voir :

1. La requête apparaître dans l'interface ngrok
2. Un log dans votre terminal PHP
3. Le message stocké dans votre base de données

## Conseils et astuces

### URL persistante (version payante)

Avec un compte ngrok payant, vous pouvez obtenir une URL persistante qui ne change pas à chaque redémarrage, ce qui évite de reconfigurer le webhook à chaque session.

### Inspection des requêtes

ngrok fournit une interface web d'inspection à l'adresse http://localhost:4040 qui permet de:
- Voir toutes les requêtes entrantes
- Examiner les en-têtes et payloads
- Rejouer les requêtes

### Logs additionnels

Pour un débogage approfondi, modifiez `public/whatsapp/webhook.php` pour activer plus de logs :

```php
// Stockage des logs pour débogage en développement
file_put_contents(
    __DIR__ . '/../../var/logs/whatsapp_webhook_' . date('Y-m-d_H-i-s') . '.json',
    $payload
);
```

### Redémarrage après inactivité

ngrok peut se déconnecter après une période d'inactivité. Si cela arrive, redémarrez simplement ngrok et mettez à jour l'URL du webhook dans la console Meta for Developers.

## Alternatives à ngrok

Si vous rencontrez des limitations avec ngrok (problèmes de connexion, restrictions géographiques, etc.), vous pouvez utiliser ces alternatives :

### 1. Localtunnel
```bash
# Installation
npm install -g localtunnel

# Utilisation
lt --port 8000
```
- **Avantages** : Simple, open-source, pas besoin de compte
- **Documentation** : [https://github.com/localtunnel/localtunnel](https://github.com/localtunnel/localtunnel)

### 2. Cloudflare Tunnel (anciennement Argo Tunnel)
```bash
# Installation
brew install cloudflared  # macOS
# ou
curl -L --output cloudflared.deb https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb
sudo dpkg -i cloudflared.deb  # Linux

# Utilisation
cloudflared tunnel --url http://localhost:8000
```
- **Avantages** : Service fiable de Cloudflare, bonne couverture mondiale
- **Documentation** : [https://developers.cloudflare.com/cloudflare-one/connections/connect-apps](https://developers.cloudflare.com/cloudflare-one/connections/connect-apps)

### 3. Serveo
```bash
# Aucune installation nécessaire
ssh -R 80:localhost:8000 serveo.net
```
- **Avantages** : Fonctionne avec SSH, pas besoin d'installer de logiciel
- **Documentation** : [https://serveo.net/](https://serveo.net/)

### 4. PageKite
```bash
# Installation
pip install pagekite

# Utilisation
python -m pagekite.manual --add=80:localhost:8000 yourname.pagekite.me
```
- **Avantages** : Bonne couverture internationale
- **Documentation** : [https://pagekite.net/](https://pagekite.net/)

### 5. Telebit
```bash
# Installation
npm install -g telebit

# Utilisation
telebit http 8000
```
- **Avantages** : Alternative open-source
- **Documentation** : [https://telebit.cloud/](https://telebit.cloud/)

## Passage en production

Une fois votre développement terminé, remplacez l'URL ngrok par l'URL de production de votre serveur dans la configuration du webhook Meta.