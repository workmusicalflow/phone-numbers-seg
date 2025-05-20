# Référence technique pour l'envoi de templates WhatsApp

Ce document fournit une référence technique pour l'implémentation de l'envoi de templates WhatsApp dans l'application.

## Architecture

L'implémentation utilise une approche en couches :

1. **Frontend (Vue.js)** : Composant WhatsAppTemplates.vue qui permet aux utilisateurs de sélectionner un template et d'envoyer un message.
2. **API GraphQL** : Mutation sendWhatsAppTemplateV2 qui traite la demande d'envoi.
3. **Contrôleur** : WhatsAppTemplateController qui implémente la logique de la mutation.
4. **Service** : WhatsAppService qui communique avec l'API Meta Cloud pour envoyer les messages.

## Types GraphQL

### SendTemplateInput

```graphql
input SendTemplateInput {
  recipientPhoneNumber: String!
  templateName: String!
  templateLanguage: String!
  templateComponentsJsonString: String
  headerMediaUrl: String
  bodyVariables: [String]
  buttonVariables: [String]
}
```

Ce type d'entrée est utilisé pour fournir les données nécessaires à l'envoi d'un template WhatsApp.

### SendTemplateResult

```graphql
type SendTemplateResult {
  success: Boolean!
  messageId: String
  error: String
}
```

Ce type de résultat est renvoyé par la mutation sendWhatsAppTemplateV2 et indique si l'envoi a réussi, avec l'ID du message et un éventuel message d'erreur.

## Mutation GraphQL

```graphql
mutation SendWhatsAppTemplate($input: SendTemplateInput!) {
  sendWhatsAppTemplateV2(input: $input) {
    success
    messageId
    error
  }
}
```

## Fichiers clés

- `/src/GraphQL/Types/WhatsApp/SendTemplateInput.php` : Classe PHP pour le type d'entrée.
- `/src/GraphQL/Types/WhatsApp/SendTemplateResult.php` : Classe PHP pour le type de résultat.
- `/src/GraphQL/Controllers/WhatsApp/WhatsAppTemplateController.php` : Contrôleur qui implémente la mutation.
- `/src/Services/WhatsApp/WhatsAppService.php` : Service qui communique avec l'API Meta Cloud.
- `/frontend/src/views/WhatsAppTemplates.vue` : Vue qui permet aux utilisateurs d'envoyer des templates.

## Flux d'exécution

1. L'utilisateur saisit un numéro de téléphone et sélectionne un template dans l'interface.
2. Le composant Vue envoie une mutation GraphQL sendWhatsAppTemplateV2 avec les données saisies.
3. Le serveur GraphQL route la demande vers WhatsAppTemplateController.
4. Le contrôleur utilise WhatsAppService pour envoyer le message à l'API Meta Cloud.
5. Le résultat est renvoyé au frontend, qui affiche un message de succès ou d'erreur.

## Journalisation

L'implémentation inclut une journalisation détaillée pour faciliter le dépannage :

- Tous les appels à l'API Meta Cloud sont journalisés avec les données d'entrée et les réponses.
- Toutes les erreurs sont journalisées avec des informations détaillées sur la cause.
- Les résultats de la mutation sont journalisés pour suivre le traitement.

## Considérations de sécurité

- Toutes les entrées utilisateur sont validées avant d'être envoyées à l'API Meta Cloud.
- Les numéros de téléphone sont normalisés au format international.
- L'authentification utilisateur est vérifiée avant l'envoi de tout message.

## Limitations connues

- Les templates doivent être approuvés par Meta avant de pouvoir être utilisés.
- Les messages texte standard ne peuvent être envoyés que dans les 24 heures suivant la dernière interaction d'un utilisateur avec le numéro WhatsApp.
- Les templates peuvent contenir des variables personnalisées, un média en-tête et des boutons interactifs, mais tous ces éléments sont soumis à l'approbation de Meta.