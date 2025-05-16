# Mise à jour des composants WhatsApp

## Vue d'ensemble

Les composants WhatsApp ont été mis à jour pour utiliser le nouveau store WhatsApp et la nouvelle structure de données basée sur `WhatsAppMessageHistory`.

## Changements effectués

### 1. WhatsAppMessageList.vue

**Modifications principales :**
- Utilise maintenant `useWhatsAppStore` au lieu de l'ancien store
- Changement des filtres de `sender`/`type` à `phoneNumber`/`status`
- Mise à jour des colonnes du tableau :
  - `Phone Number` au lieu de `Sender`
  - `Direction` (INCOMING/OUTGOING) au lieu de l'ancienne structure
  - Nouveaux champs : `messageId`, `contactPhone`, `queueId`
- Mise à jour des méthodes pour utiliser les nouvelles actions du store
- Support de la pagination avec les nouvelles propriétés

**Structure des données :**
```typescript
interface WhatsAppMessageHistory {
  id: string;
  wabaMessageId: string;
  phoneNumber: string;
  direction: 'INCOMING' | 'OUTGOING';
  type: string;
  content: string | null;
  status: string;
  messageId: string | null;
  contactPhone: string | null;
  queueId: string | null;
  sentAt: Date;
  deliveredAt: Date | null;
  readAt: Date | null;
  createdAt: Date;
  updatedAt: Date;
}
```

### 2. WhatsAppSendMessage.vue

**Modifications principales :**
- Utilise les nouvelles méthodes du store : `sendMessage()` et `sendTemplate()`
- Chargement dynamique des templates utilisateur via `loadUserTemplates()`
- Structure des messages modifiée :
  ```typescript
  // Text message
  await whatsAppStore.sendMessage({
    recipient: normalizedRecipient,
    type: 'text',
    content: textMessage
  });

  // Template message
  await whatsAppStore.sendTemplate({
    recipient: normalizedRecipient,
    templateName: selectedTemplate,
    languageCode: templateLanguage,
    components: components
  });
  ```
- Gestion améliorée des erreurs avec messages d'erreur détaillés
- Rafraîchissement automatique de la liste des messages après envoi

**Features ajoutées :**
- Chargement des templates utilisateur au montage du composant
- Support des composants de template (header, body avec paramètres)
- Validation robuste des numéros de téléphone
- Notification de succès/échec avec Quasar

### 3. Structure des composants de template

Les composants de template suivent maintenant la structure de l'API WhatsApp :

```typescript
const components = [
  {
    type: 'header',
    parameters: [{
      type: 'image',
      image: { link: imageUrl }
    }]
  },
  {
    type: 'body',
    parameters: [
      { type: 'text', text: param1 },
      { type: 'text', text: param2 }
    ]
  }
];
```

## Tests

Des tests unitaires ont été créés pour les deux composants :
- `WhatsAppMessageList.test.ts`
- `WhatsAppSendMessage.test.ts`

Les tests couvrent :
- Le rendu correct des composants
- Les interactions utilisateur
- L'envoi de messages (texte et template)
- La gestion des erreurs
- La validation des données
- Le chargement des templates

## Utilisation

### Pour afficher la liste des messages :
```vue
<template>
  <WhatsAppMessageList />
</template>

<script setup lang="ts">
import WhatsAppMessageList from '@/components/whatsapp/WhatsAppMessageList.vue';
</script>
```

### Pour envoyer des messages :
```vue
<template>
  <WhatsAppSendMessage />
</template>

<script setup lang="ts">
import WhatsAppSendMessage from '@/components/whatsapp/WhatsAppSendMessage.vue';
</script>
```

## Notes importantes

1. Les composants nécessitent que l'utilisateur soit authentifié
2. Les templates sont chargés dynamiquement depuis l'API
3. Les numéros de téléphone sont automatiquement normalisés (ajout du code pays 225)
4. Les messages sont rechargés automatiquement après l'envoi
5. La pagination est gérée automatiquement par le store

## Prochaines étapes

1. Implémenter le support des médias (images, documents)
2. Ajouter la prévisualisation des messages
3. Implémenter l'historique des conversations par contact
4. Ajouter le support des messages entrants via webhooks