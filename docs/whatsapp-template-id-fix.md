# Correction du problème "Unrecognized field: template_id"

## Problème

Le système rencontrait une erreur lors de l'utilisation des templates WhatsApp :

```
Unrecognized field: App\Entities\WhatsApp\WhatsAppTemplate::$template_id
```

Cette erreur se produisait parce que le code essayait de rechercher des templates en utilisant un champ `template_id` qui n'existait pas dans l'entité `WhatsAppTemplate` ou dans la table `whatsapp_templates` de la base de données.

## Analyse

1. L'erreur se produisait dans le service `WhatsAppService.php` qui tentait de rechercher un template en utilisant :
   ```php
   $template = $this->templateRepository->findOneBy(['template_id' => $templateName]);
   ```

2. Cette erreur se produisait à trois endroits différents dans le code :
   - Dans la méthode `sendTemplateMessage()` (ligne 231)
   - Dans la méthode `sendTemplateMessageWithComponents()` (ligne 430)
   - Dans la méthode `recordTemplateUsage()` (ligne 1468)

3. L'entité `WhatsAppTemplate` n'avait pas de champ nommé `template_id` défini comme propriété persistée dans la base de données, mais elle utilisait le champ `name` pour stocker l'identifiant du template.

## Solution

Pour résoudre ce problème, nous avons adopté une approche en trois parties :

1. **Modification des requêtes** : Remplacer les recherches utilisant `template_id` par des recherches utilisant `name` dans `WhatsAppService.php`.
   ```php
   // Au lieu de :
   $template = $this->templateRepository->findOneBy(['template_id' => $templateName]);
   
   // Utiliser :
   $template = $this->templateRepository->findOneBy(['name' => $templateName]);
   ```

2. **Ajout d'une propriété virtuelle** : Nous avons ajouté la propriété `$templateId` dans l'entité `WhatsAppTemplate` sans l'annoter pour ORM, ainsi que des méthodes `getTemplateId()` et `setTemplateId()`. Cela permet au code existant de continuer à fonctionner sans modification des autres parties du système.
   ```php
   /**
    * ID du template utilisé pour la correspondance avec l'API Meta
    * Remarque: ce champ n'est pas stocké dans la base de données,
    * c'est une propriété calculée qui retourne le nom du template
    */
   private ?string $templateId = null;
   
   /**
    * Obtenir l'ID du template
    * 
    * @return string|null
    */
   public function getTemplateId(): ?string
   {
       // Si templateId n'est pas défini, utiliser le nom du template
       // C'est une propriété virtuelle qui n'est pas persistée dans la base de données
       return $this->name;
   }
   ```

3. **Correction des méthodes auxiliaires** : Nous avons également corrigé les méthodes du repository `WhatsAppTemplateRepository.php` qui utilisaient des noms de champs incorrects, comme `metaTemplateName` et `languageCode`.

## Recommandations

1. **Vérification de la cohérence** : Lors de l'ajout de nouvelles fonctionnalités ou de la modification de fonctionnalités existantes, vérifier la cohérence entre les entités, les repositories et les services pour éviter des incompatibilités de nommage.

2. **Tests unitaires** : Ajouter des tests unitaires pour les fonctionnalités critiques comme l'envoi de templates WhatsApp afin de détecter les problèmes plus tôt.

3. **Migration de base de données** : Si nécessaire, envisager de créer une migration de base de données pour ajouter explicitement un champ `template_id` à la table `whatsapp_templates` afin de rendre le modèle plus cohérent avec le code.

## Conclusion

Cette correction permet au système d'envoyer des messages template WhatsApp sans erreur, tout en maintenant la compatibilité avec le code existant. La solution adoptée est minimalement invasive et ne nécessite pas de modification du schéma de la base de données.