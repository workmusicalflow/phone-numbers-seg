# WhatsApp Store Documentation

## Overview

Le store WhatsApp (`whatsappStore.ts`) gère toutes les interactions avec l'API WhatsApp Business Cloud via GraphQL. Il est basé sur Pinia et utilise la Composition API de Vue 3.

## Features

- 🔄 Récupération de l'historique des messages
- 📤 Envoi de messages texte
- 📝 Envoi de messages template
- 🔍 Filtrage par numéro et statut
- 📖 Pagination intégrée
- ⚡ Gestion d'état centralisée

## Usage

### Import

```typescript
import { useWhatsAppStore } from '@/stores/whatsappStore';

const whatsappStore = useWhatsAppStore();
```

### Fetch Message History

```typescript
// Récupérer tous les messages
await whatsappStore.fetchMessageHistory();

// Récupérer avec des filtres
await whatsappStore.fetchMessageHistory(
  '+2250101010101', // phoneNumber
  'sent',           // status
  100,              // limit
  0                 // offset
);
```

### Send Text Message

```typescript
const message = await whatsappStore.sendMessage({
  recipient: '+2250101010101',
  type: 'text',
  content: 'Hello from Oracle'
});

if (message) {
  console.log('Message sent:', message.wabaMessageId);
}
```

### Send Template Message

```typescript
const template = await whatsappStore.sendTemplateMessage({
  recipient: '+2250101010101',
  templateName: 'hello_world',
  languageCode: 'fr',
  body1Param: 'John Doe',
  body2Param: 'Oracle',
  headerImageUrl: 'https://example.com/image.jpg'
});
```

### Filtering

```typescript
// Filtrer par numéro de téléphone et statut
whatsappStore.setFilters('+2250101010101', 'sent');

// Effacer les filtres
whatsappStore.clearFilters();
```

### Pagination

```typescript
// Changer de page
whatsappStore.setCurrentPage(2);

// Changer la taille de page
whatsappStore.setPageSize(25);

// Accéder aux messages paginés
const messages = whatsappStore.paginatedMessages;
const totalPages = whatsappStore.totalPages;
```

## State Properties

- `messages`: Array of WhatsAppMessageHistory
- `isLoading`: Boolean indicating loading state
- `error`: Error message if any
- `totalCount`: Total number of messages
- `currentPage`: Current page number
- `pageSize`: Number of items per page
- `filterPhoneNumber`: Filter by phone number
- `filterStatus`: Filter by message status

## Computed Properties

- `sortedMessages`: Messages sorted by timestamp
- `filteredMessages`: Messages filtered by criteria
- `paginatedMessages`: Current page of messages
- `totalPages`: Total number of pages

## Actions

| Action | Description | Parameters |
|--------|-------------|------------|
| `fetchMessageHistory` | Récupère l'historique | phoneNumber?, status?, limit?, offset? |
| `fetchMessageCount` | Compte les messages | phoneNumber?, status?, direction? |
| `sendMessage` | Envoie un message | WhatsAppMessageInput |
| `sendTemplateMessage` | Envoie un template | WhatsAppTemplateSendInput |
| `setCurrentPage` | Change la page | page: number |
| `setPageSize` | Change la taille de page | size: number |
| `setFilters` | Applique des filtres | phoneNumber?, status? |
| `clearFilters` | Efface les filtres | - |
| `refreshMessages` | Rafraîchit les messages | - |
| `fetchMessage` | Récupère un message | id: number |

## Component Example

```vue
<template>
  <div>
    <h3>WhatsApp Messages</h3>
    
    <!-- Filtres -->
    <q-input
      v-model="phoneFilter"
      label="Filtrer par numéro"
      @update:model-value="updateFilters"
    />
    
    <!-- Liste des messages -->
    <q-list v-if="!whatsappStore.isLoading">
      <q-item v-for="message in whatsappStore.paginatedMessages" :key="message.id">
        <q-item-section>
          <q-item-label>{{ message.phoneNumber }}</q-item-label>
          <q-item-label caption>{{ message.content }}</q-item-label>
        </q-item-section>
      </q-item>
    </q-list>
    
    <!-- Pagination -->
    <q-pagination
      v-model="currentPage"
      :max="whatsappStore.totalPages"
      @update:model-value="whatsappStore.setCurrentPage"
    />
    
    <!-- Envoi de message -->
    <q-btn @click="sendMessage">Envoyer un message</q-btn>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useWhatsAppStore } from '@/stores/whatsappStore';

const whatsappStore = useWhatsAppStore();
const phoneFilter = ref('');
const currentPage = ref(1);

onMounted(() => {
  whatsappStore.fetchMessageHistory();
});

function updateFilters() {
  whatsappStore.setFilters(phoneFilter.value);
}

async function sendMessage() {
  await whatsappStore.sendMessage({
    recipient: '+2250101010101',
    type: 'text',
    content: 'Message de test'
  });
}
</script>
```

## Type Definitions

```typescript
// Message dans l'historique
interface WhatsAppMessageHistory {
  id: string;
  wabaMessageId: string;
  phoneNumber: string;
  direction: 'INCOMING' | 'OUTGOING';
  type: string;
  content: string | null;
  status: string;
  timestamp: string;
  errorCode: number | null;
  errorMessage: string | null;
  conversationId: string | null;
  pricingCategory: string | null;
  mediaId: string | null;
  templateName: string | null;
  templateLanguage: string | null;
  contextData: string | null;
  createdAt: string;
  updatedAt: string | null;
}

// Input pour envoyer un message
interface WhatsAppMessageInput {
  recipient: string;
  type: 'text' | 'image' | 'video' | 'audio' | 'document';
  content: string | null;
  mediaUrl?: string | null;
}

// Input pour envoyer un template
interface WhatsAppTemplateSendInput {
  recipient: string;
  templateName: string;
  languageCode: string;
  headerImageUrl?: string | null;
  body1Param?: string | null;
  body2Param?: string | null;
  body3Param?: string | null;
}
```

## Error Handling

Le store gère automatiquement les erreurs et les stocke dans la propriété `error`. Vérifiez toujours cette propriété après une action :

```typescript
await whatsappStore.sendMessage(messageData);

if (whatsappStore.error) {
  console.error('Erreur:', whatsappStore.error);
  // Afficher une notification à l'utilisateur
}
```

## Performance Tips

1. **Pagination**: Utilisez la pagination pour éviter de charger trop de messages
2. **Filtrage**: Filtrez côté client avec les computed properties
3. **Refresh**: Utilisez `refreshMessages()` pour actualiser sans recharger toute la page
4. **Batch Operations**: Groupez les appels API quand possible

## Testing

Voir `__tests__/whatsappStore.test.ts` pour des exemples de tests unitaires.