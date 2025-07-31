# Guide de configuration de l'API WhatsApp Business Cloud

Ce guide détaille les étapes nécessaires pour configurer l'API WhatsApp Business Cloud pour l'application Oracle.

## Prérequis

- Un compte Meta for Developers
- Un compte WhatsApp Business
- Un numéro de téléphone WhatsApp Business (fourni par Meta)
- Un domaine avec HTTPS pour le webhook

## 1. Création de l'application Meta

1. Connectez-vous à [Meta for Developers](https://developers.facebook.com/)
2. Créez une nouvelle application :
   - Cliquez sur "Créer une application"
   - Sélectionnez "Business" comme type d'application
   - Entrez les détails de votre application (nom, email, etc.)
   - Cliquez sur "Créer une application"

3. Dans le tableau de bord de l'application, ajoutez le produit "WhatsApp" :
   - Cliquez sur "Ajouter des produits"
   - Sélectionnez "WhatsApp"

## 2. Configuration du numéro de téléphone WhatsApp Business

1. Dans la section "WhatsApp" > "Commencer", vous verrez un numéro de téléphone de test
2. Pour un environnement de production, vous devrez demander un numéro de téléphone WhatsApp Business

## 3. Obtention des identifiants et tokens

Récupérez les informations suivantes depuis le tableau de bord Meta for Developers :

1. **App ID** : Disponible dans les paramètres de l'application
2. **Phone Number ID** : Dans la section "WhatsApp" > "Commencer" > "Numéros de téléphone"
3. **WhatsApp Business Account ID** : Dans la section "WhatsApp" > "Configuration" > "Compte WhatsApp Business"
4. **Access Token** :
   - Allez dans "Outils pour développeurs" > "Tokens d'accès"
   - Créez un nouveau token d'accès système avec les autorisations WhatsApp
   - Ce token a une validité de 60 jours

## 4. Mise à jour de la configuration

Mettez à jour le fichier `src/config/whatsapp.php` avec les informations récupérées :

```php
return [
    // Informations d'identification de l'API Meta
    'app_id' => 'VOTRE_APP_ID',
    'phone_number_id' => 'VOTRE_PHONE_NUMBER_ID',
    'whatsapp_business_account_id' => 'VOTRE_WHATSAPP_BUSINESS_ACCOUNT_ID',
    'api_version' => 'v22.0',
    
    // Token d'accès Meta
    'access_token' => 'VOTRE_ACCESS_TOKEN',
    
    // Sécurité du Webhook
    'webhook_verify_token' => 'VOTRE_TOKEN_DE_VERIFICATION',
    
    // Reste de la configuration...
];
```

## 5. Configuration du webhook

1. Assurez-vous que votre serveur est accessible via HTTPS
2. Si vous êtes en développement local, utilisez ngrok pour exposer votre serveur :
   ```
   ngrok http 8000
   ```

3. Dans le tableau de bord Meta for Developers :
   - Allez dans "WhatsApp" > "Configuration"
   - Dans la section "Webhooks", cliquez sur "Configurer"
   - Entrez l'URL de votre webhook : `https://votre-domaine.com/whatsapp/webhook.php`
   - Entrez le token de vérification (celui que vous avez configuré dans `whatsapp.php`)
   - Sélectionnez les champs : "messages", "message_status_updates"
   - Cliquez sur "Vérifier et enregistrer"

4. Si la vérification échoue :
   - Vérifiez que votre webhook est accessible
   - Vérifiez que le token de vérification est le même dans le dashboard Meta et dans votre configuration
   - Consultez les logs pour plus d'informations

## 6. Création de templates de messages

Pour envoyer des messages autres que des réponses aux 24 dernières heures, vous devez utiliser des templates approuvés :

1. Allez dans [Meta Business Suite](https://business.facebook.com/)
2. Naviguez vers "WhatsApp" > "Templates"
3. Cliquez sur "Créer un template"
4. Suivez les instructions pour créer votre template :
   - Choisissez une catégorie
   - Configurez les composants (en-tête, corps, boutons)
   - Ajoutez des exemples de valeurs pour les paramètres variables
   - Soumettez pour approbation

5. Une fois approuvé, ajoutez le template à votre configuration dans `src/config/whatsapp.php`

## 7. Test de l'intégration

1. **Test du webhook** :
   - Envoyez un message WhatsApp au numéro de test
   - Vérifiez que le message est reçu dans votre base de données

2. **Test d'envoi de message** :
   - Utilisez l'API GraphQL pour envoyer un message
   - Vérifiez que le message est bien reçu sur le téléphone destinataire

## 8. Passer en production

Pour passer en production, vous devrez :

1. Demander un numéro de téléphone WhatsApp Business permanent
2. Mettre à jour votre Business Manager avec :
   - Les informations de votre entreprise
   - Un numéro de téléphone de contact pour vérification
   - Une adresse physique

3. Migrer de l'API de test à l'API de production :
   - Mettez à jour les identifiants dans votre configuration
   - Testez à nouveau l'intégration

## 9. Maintenance

- **Renouvellement des tokens** : Les tokens d'accès système expirent après 60 jours, prévoyez une procédure de renouvellement
- **Monitoring** : Mettez en place un système pour surveiller les échecs d'envoi de messages
- **Sauvegarde** : Assurez-vous que la base de données des messages est régulièrement sauvegardée

## Ressources utiles

- [Documentation officielle de l'API WhatsApp Business](https://developers.facebook.com/docs/whatsapp/cloud-api)
- [Guide de configuration des webhooks](https://developers.facebook.com/docs/whatsapp/cloud-api/guides/set-up-webhooks)
- [Guide d'envoi de messages](https://developers.facebook.com/docs/whatsapp/cloud-api/guides/send-messages)
- [Documentation sur les templates](https://developers.facebook.com/docs/whatsapp/cloud-api/guides/send-message-templates)