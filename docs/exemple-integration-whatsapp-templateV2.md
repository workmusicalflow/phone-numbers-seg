# Exemple d'intégration du nouveau composant WhatsAppMessageComposerV2

Cet exemple montre comment intégrer le nouveau composant `WhatsAppMessageComposerV2` dans la vue existante `WhatsAppTemplates.vue`.

## 1. Importer les nouveaux composants et services

```vue
<script>
// Imports existants...
import WhatsAppMessageComposerV2 from '@/components/whatsapp/WhatsAppMessageComposerV2.vue';
import { whatsAppTemplateServiceV2 } from '@/services/whatsapp/index-v2';
import { whatsAppClientV2 } from '@/services/whatsappRestClientV2';

export default {
  // ...
  components: {
    // Composants existants...
    WhatsAppMessageComposerV2
  },
  // ...
</script>
```

## 2. Ajouter un switch pour basculer entre les versions

```vue
<template>
  <!-- Contenu existant... -->
  
  <!-- Après la section de sélection du destinataire -->
  <div class="q-mt-md">
    <q-toggle
      v-model="useV2Components"
      label="Utiliser la nouvelle version des composants"
      color="primary"
    />
    <q-tooltip>
      Cette option utilise le nouveau format conforme à l'API Meta Cloud
    </q-tooltip>
  </div>
  
  <!-- Suite du contenu... -->
</template>

<script>
export default {
  // ...
  data() {
    return {
      // Données existantes...
      useV2Components: true, // Par défaut, utiliser la nouvelle version
    };
  },
  // ...
</script>
```

## 3. Modifier la section d'affichage du composant composer

```vue
<template>
  <!-- Conteneur du composant de personnalisation -->
  <div v-if="selectedTemplate" class="q-mt-md">
    <!-- Version v1 (existante) -->
    <WhatsAppMessageComposer
      v-if="!useV2Components"
      :template-data="selectedTemplate"
      :recipient-phone-number="phoneNumber"
      @message-sent="handleMessageSent"
      @change-template="resetSelectedTemplate"
      @cancel="resetSelectedTemplate"
    />
    
    <!-- Nouvelle version v2 -->
    <WhatsAppMessageComposerV2
      v-else
      :template-data="selectedTemplate"
      :recipient-phone-number="phoneNumber"
      @message-sent="handleMessageSent"
      @change-template="resetSelectedTemplate"
      @cancel="resetSelectedTemplate"
    />
  </div>
</template>
```

## 4. Adapter la méthode de traitement du template sélectionné

```vue
<script>
export default {
  // ...
  methods: {
    // ...
    selectEnhancedTemplate(template) {
      console.log('Template sélectionné:', template);
      
      if (this.useV2Components) {
        // Utiliser le service V2 pour préparer le template
        this.selectedTemplate = whatsAppTemplateServiceV2.processTemplate(
          template,
          this.phoneNumber
        );
      } else {
        // Code existant pour le format V1
        // ...code existant...
      }
    },
    
    // ...
  }
  // ...
</script>
```

## 5. Ajout d'informations sur la version utilisée dans l'historique

```vue
<script>
export default {
  // ...
  methods: {
    // ...
    handleMessageSent(result) {
      if (result.success) {
        this.$q.notify({
          type: 'positive',
          message: `Message envoyé avec succès à ${result.recipientPhoneNumber}`,
          position: 'top'
        });
        
        // Ajouter à l'historique avec indication de la version utilisée
        this.sentMessages.unshift({
          ...result,
          timestamp: result.timestamp || new Date().toISOString(),
          version: this.useV2Components ? 'v2' : 'v1'
        });
        
        this.resetSelectedTemplate();
      } else {
        this.$q.notify({
          type: 'negative',
          message: `Erreur lors de l'envoi: ${result.error}`,
          position: 'top'
        });
      }
    },
    // ...
  }
  // ...
</script>
```

## 6. Modification de l'historique pour afficher la version utilisée

```vue
<template>
  <!-- Dans la section historique des messages -->
  <q-item
    v-for="(message, index) in sentMessages"
    :key="index"
    class="q-my-sm"
    bordered
  >
    <!-- Contenu existant... -->
    
    <!-- Ajouter un badge indiquant la version -->
    <q-badge
      v-if="message.version"
      :color="message.version === 'v2' ? 'green' : 'blue'"
      class="q-ml-sm"
    >
      {{ message.version }}
    </q-badge>
  </q-item>
</template>
```

## 7. Exemple complet d'implémentation

Pour implémenter complètement cette fonctionnalité, vous devrez :

1. Importer les nouveaux composants et services
2. Ajouter un switch pour choisir la version
3. Modifier l'affichage conditionnel des composants
4. Adapter les méthodes de traitement des templates
5. Mettre à jour la gestion des messages envoyés
6. Modifier l'affichage de l'historique

Cette approche permet de :
- Tester les deux versions côte à côte
- Effectuer une transition progressive
- Collecter des retours des utilisateurs sur les deux versions
- Maintenir la compatibilité avec le code existant

## 8. Note sur les performances

La nouvelle version est conçue pour être plus efficace, mais pour les grands volumes de templates, il est recommandé de mettre en place un système de cache et de pagination si ce n'est pas déjà fait.

## 9. Intégration dans d'autres vues

Cette même approche peut être utilisée pour intégrer les nouveaux composants dans d'autres vues qui utilisent les templates WhatsApp, comme `WhatsApp.vue`.