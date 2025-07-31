# Dépannage de l'intégration des templates WhatsApp

Ce document décrit les problèmes rencontrés lors de l'implémentation des templates WhatsApp et les solutions appliquées.

## Problème #1 : Ordre des paramètres dans le constructeur SMSService

### Symptôme
- Erreur PHP : "Optional parameter $phoneNumberRepository declared before required parameter $logger"

### Cause
- Dans la classe SMSService, les paramètres optionnels étaient déclarés avant les paramètres obligatoires dans le constructeur, ce qui est interdit en PHP 8.3.

### Solution
- Réorganisation des paramètres du constructeur pour placer les paramètres obligatoires en premier.
- Mise à jour de l'ordre des affectations des propriétés dans le corps du constructeur.

## Problème #2 : Type GraphQL "SendTemplateInput" inconnu

### Symptôme
- Erreur GraphQL : "Unknown type 'SendTemplateInput'"

### Cause
- Le type d'entrée pour la mutation sendWhatsAppTemplateV2 n'était pas défini dans le schéma GraphQL.

### Solution
1. Définition du type SendTemplateInput dans le fichier schema.graphql
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

2. Création d'une classe PHP correspondante avec les annotations GraphQLite
   ```php
   #[Input]
   class SendTemplateInput
   {
       // Propriétés et méthodes...
   }
   ```

## Problème #3 : Retour null pour un champ non nullable

### Symptôme
- Erreur GraphQL : "Cannot return null for non-nullable field \"Mutation.sendWhatsAppTemplateV2\"."
- Plus tard : "Cannot return null for non-nullable field \"SendTemplateResult.success\"."

### Cause
- La mutation retournait null au lieu d'un objet du type SendTemplateResult.
- Le champ success du type SendTemplateResult recevait une valeur null.

### Solutions appliquées de manière incrémentale :

1. **Création d'une classe dédiée pour le résultat**
   ```php
   #[Type]
   class SendTemplateResult
   {
       // ...
   }
   ```

2. **Création d'un nouveau contrôleur dédié aux templates WhatsApp**
   ```php
   #[Type]
   class WhatsAppTemplateController
   {
       #[Mutation]
       #[Logged]
       public function sendWhatsAppTemplateV2(
           SendTemplateInput $input,
           ?GraphQLContext $context = null
       ): SendTemplateResult {
           // ...
       }
   }
   ```

3. **Utilisation d'annotations traditionnelles pour le typage GraphQL**
   ```php
   /**
    * @Type(name="SendTemplateResult")
    */
   class SimpleSendTemplateResult
   {
       /**
        * @Field(name="success")
        */
       public function getSuccess(): bool
       {
           // Si pour une raison quelconque success est null, on retourne false
           return $this->success === null ? false : $this->success;
       }
       // ...
   }
   ```

4. **Conversion explicite du résultat en tableau associatif**
   ```php
   $arrayResult = [
       'success' => $result->getSuccess() ?? false,
       'messageId' => $result->getMessageId(),
       'error' => $result->getError()
   ];
   return $arrayResult;
   ```

5. **Mise à jour de la configuration DI**
   - Ajout du contrôleur, des types d'entrée et de sortie dans la configuration d'injection de dépendances.

## Leçons apprises

1. **Typage et non-nullabilité** : Dans GraphQL, les champs marqués comme non-nullables (avec !) doivent absolument retourner une valeur non-null. Il est important de s'assurer que toutes les propriétés d'un objet de retour sont correctement initialisées.

2. **Conventions de nommage** : Le nom du type dans le schéma GraphQL doit correspondre exactement au nom généré par GraphQLite à partir de la classe PHP, ou être explicitement spécifié avec l'annotation `@Type(name="...")`.

3. **Traçage des erreurs** : L'ajout de logs détaillés à chaque étape du traitement facilite grandement le diagnostic des problèmes GraphQL.

4. **Approche progressive** : Commencer par des solutions simples (ajout de logs), puis augmenter progressivement la complexité (création de classes dédiées, modification du routage) permet de résoudre les problèmes pas à pas.

## Améliorations futures

- Uniformiser l'approche pour toutes les mutations WhatsApp
- Refactoriser le code pour éliminer les éléments redondants 
- Remplacer le routage manuel des mutations dans graphql.php par une découverte automatique via GraphQLite
- Ajouter des tests unitaires pour les nouveaux composants