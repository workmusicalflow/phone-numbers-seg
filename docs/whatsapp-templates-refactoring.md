# Architecture WhatsApp Templates - Documentation

## 1. Présentation

Cette documentation décrit la nouvelle architecture pour la gestion des templates WhatsApp dans Oracle. L'objectif principal est de standardiser le format des données des templates WhatsApp selon les spécifications exactes de l'API Meta Cloud.

L'architecture est conçue pour être :
- Robuste : gère correctement toutes les structures de templates
- Conforme : respecte le format exact de l'API Meta Cloud
- Extensible : permet d'ajouter facilement de nouvelles fonctionnalités
- Maintenable : séparation claire des responsabilités

## 2. Structure des fichiers

La nouvelle architecture est organisée comme suit :

- **Types** : `/frontend/src/types/`
  - `whatsapp-parameters.ts` : Définitions des interfaces pour l'API Meta
  - `whatsapp-templates.ts` : Définitions des interfaces internes (existant)

- **Services** : `/frontend/src/services/whatsapp/`
  - `templateParserV2.ts` : Service d'analyse des templates
  - `templateDataNormalizerV2.ts` : Service de normalisation des données
  - `index-v2.ts` : Exportations des services combinés

- **Client API** : `/frontend/src/services/`
  - `whatsappRestClientV2.ts` : Client REST pour l'API WhatsApp

- **Composants** : `/frontend/src/components/whatsapp/`
  - `WhatsAppMessageComposerV2.vue` : Composant de personnalisation des templates

## 3. Format API Meta Cloud

L'API Meta Cloud attend un format spécifique pour les messages template WhatsApp :

```json
{
  "messaging_product": "whatsapp",
  "to": "PHONE_NUMBER",
  "type": "template",
  "template": {
    "name": "TEMPLATE_NAME",
    "language": {
      "code": "LANGUAGE_CODE"
    },
    "components": [
      {
        "type": "header",
        "parameters": [
          {
            "type": "image|video|document",
            "image|video|document": {
              "link": "URL" // ou "id": "MEDIA_ID"
            }
          }
        ]
      },
      {
        "type": "body",
        "parameters": [
          {
            "type": "text|currency|date_time",
            "text": "VALUE"
            // ou objets currency/date_time
          }
        ]
      }
    ]
  }
}
```

## 4. Flux de travail

### 4.1. Analyse d'un template

1. Le template est analysé par `templateParserV2`
2. Les variables sont extraites et typées (texte, devise, date, etc.)
3. Les composants sont identifiés (en-tête, corps, pied de page)
4. Un résultat d'analyse (`TemplateAnalysisResult`) est généré

### 4.2. Préparation de l'interface utilisateur

1. Le composant `WhatsAppMessageComposerV2` utilise le résultat d'analyse
2. Les contrôles appropriés sont créés pour chaque type de variable
3. L'aperçu du message est généré en temps réel

### 4.3. Envoi d'un message

1. L'utilisateur personnalise le template et clique sur "Envoyer"
2. `templateDataNormalizerV2` convertit les données du formulaire en format Meta API
3. `whatsAppClientV2` envoie la requête au serveur API
4. Le serveur transmet la requête à l'API Meta Cloud
5. Le résultat est retourné au client

## 5. Interfaces principales

### 5.1. Types de paramètres

```typescript
// Paramètre texte
interface WhatsAppTextParameter {
  type: "text";
  text: string;
}

// Paramètre image
interface WhatsAppImageParameter {
  type: "image";
  image: {
    id?: string;
    link?: string;
  };
}

// Paramètre devise
interface WhatsAppCurrencyParameter {
  type: "currency";
  currency: {
    fallback_value: string;
    code: string;
    amount_1000: number;
  };
}

// Paramètre date/heure
interface WhatsAppDateTimeParameter {
  type: "date_time";
  date_time: {
    fallback_value: string;
    // Autres champs selon besoins (year, month, etc.)
  };
}
```

### 5.2. Structure de composants

```typescript
// Composant de template
interface WhatsAppTemplateComponent {
  type: "header" | "body" | "footer" | "button";
  parameters?: WhatsAppParameter[];
  // Champs spécifiques pour les boutons
}

// Message complet
interface WhatsAppTemplateMessage {
  messaging_product: "whatsapp";
  to: string;
  type: "template";
  template: {
    name: string;
    language: {
      code: string;
    };
    components?: WhatsAppTemplateComponent[];
  };
}
```

## 6. Services

### 6.1. WhatsAppTemplateParserV2

Responsabilités :
- Extraction des composants du template
- Analyse des variables et de leur contexte
- Détection intelligente des types de variables
- Génération des paramètres au format Meta API

### 6.2. TemplateDataNormalizerV2

Responsabilités :
- Conversion des données du formulaire en format API
- Normalisation des numéros de téléphone
- Préparation de la structure complète du message
- Validation des données avant envoi

### 6.3. WhatsAppClientV2

Responsabilités :
- Communication avec l'API REST du serveur
- Gestion des erreurs et réponses
- Fournir une interface simplifiée pour l'envoi de templates
- Compatibilité avec l'ancien format pour la transition

## 7. Intégration

### 7.1. Intégration dans les vues existantes

```vue
<template>
  <WhatsAppMessageComposerV2
    :template-data="selectedTemplate"
    :recipient-phone-number="recipientPhone"
    @message-sent="handleMessageSent"
    @change-template="selectAnotherTemplate"
    @cancel="cancelSending"
  />
</template>

<script>
import { WhatsAppMessageComposerV2 } from '@/components/whatsapp/WhatsAppMessageComposerV2.vue';
import { whatsAppTemplateServiceV2 } from '@/services/whatsapp/index-v2';

export default {
  components: {
    WhatsAppMessageComposerV2
  },
  // ...
  methods: {
    // Traitement d'un template sélectionné
    processSelectedTemplate(template) {
      const templateData = whatsAppTemplateServiceV2.processTemplate(
        template,
        this.recipientPhone
      );
      this.selectedTemplate = templateData;
    },
    // Gestion de l'envoi
    handleMessageSent(result) {
      if (result.success) {
        // Traitement en cas de succès
      } else {
        // Gestion des erreurs
      }
    }
  }
};
</script>
```

## 8. Transition et compatibilité

Pour assurer une transition en douceur, nous avons :

1. Maintenu les anciens composants et services fonctionnels
2. Ajouté une couche de compatibilité dans `whatsAppClientV2` pour l'ancien format
3. Créé de nouveaux composants V2 sans modifier les existants
4. Documenté clairement les différences et avantages

## 9. Avantages et améliorations

1. **Conformité stricte** avec les spécifications de l'API Meta Cloud
2. **Détection intelligente** des types de variables
3. **Support complet** des médias pour les en-têtes
4. **Validation renforcée** des données avant envoi
5. **Meilleure gestion des erreurs** avec messages détaillés
6. **Architecture évolutive** permettant d'ajouter facilement de nouvelles fonctionnalités
7. **Aperçu API en temps réel** pour faciliter le débogage

## 10. Limitations actuelles

1. **Support des boutons** : Non implémenté dans cette version, car hors du périmètre de personnalisation
2. **Variables avancées** : Support limité pour les structures complexes (listes, objets imbriqués)
3. **Cache des médias** : Pas de système de cache pour les médias fréquemment utilisés

## 11. Évolutions futures possibles

1. Ajout du support des boutons personnalisables (si besoin)
2. Système de cache pour les médias fréquemment utilisés
3. Prévisualisation des médias uploadés
4. Interface de gestion des templates créés localement
5. Système de validation plus avancé pour les variables
6. Statistiques d'utilisation des templates

## 12. Conclusion

Cette nouvelle architecture fournit une base solide pour la gestion des templates WhatsApp, tout en respectant strictement les spécifications de l'API Meta Cloud. Elle améliore la robustesse, la maintenabilité et l'évolutivité du système, tout en offrant une meilleure expérience utilisateur et développeur.