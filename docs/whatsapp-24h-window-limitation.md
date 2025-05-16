# Guide : Limitation de la fenêtre de 24 heures WhatsApp

## ⚠️ Règle Fondamentale de l'API WhatsApp Business

**L'API WhatsApp impose une limitation stricte : les messages texte normaux ne peuvent être envoyés qu'à des utilisateurs ayant interagi avec votre business dans les dernières 24 heures.**

## Comprendre la fenêtre de 24 heures

### Qu'est-ce que la fenêtre de service client ?

La "customer service window" (fenêtre de service client) est une période de 24 heures pendant laquelle vous pouvez envoyer n'importe quel type de message à un utilisateur WhatsApp. Cette fenêtre s'ouvre uniquement quand l'utilisateur initie une interaction.

### Comment s'ouvre une fenêtre ?

Une fenêtre de 24 heures s'ouvre quand :
- ✅ L'utilisateur envoie un message à votre numéro WhatsApp Business
- ✅ L'utilisateur clique sur un bouton dans un de vos messages interactifs
- ✅ L'utilisateur répond à un message template que vous avez envoyé
- ✅ L'utilisateur clique sur une publicité "Click to WhatsApp"

### Comment se ferme une fenêtre ?

La fenêtre se ferme automatiquement :
- ❌ 24 heures après la dernière interaction de l'utilisateur
- ❌ Immédiatement si l'utilisateur bloque votre numéro

## Types de messages et restrictions

### Pendant la fenêtre de 24h (conversation ouverte)

Vous pouvez envoyer :
- ✅ Messages texte simples
- ✅ Messages avec médias (images, vidéos, documents)
- ✅ Messages interactifs (boutons, listes)
- ✅ Messages de localisation
- ✅ Messages de contact
- ✅ Messages template (mais ce n'est pas nécessaire)

### Hors de la fenêtre de 24h (conversation fermée)

Vous pouvez UNIQUEMENT envoyer :
- ✅ Messages template pré-approuvés
- ❌ AUCUN autre type de message

## Erreurs courantes

### Erreur typique hors fenêtre

```json
{
  "error": {
    "message": "(#131030) Recipient is not a valid WhatsApp user or is outside the allowed window",
    "type": "OAuthException",
    "code": 131030,
    "error_subcode": 2655006,
    "fbtrace_id": "A_1234567890"
  }
}
```

### Autres codes d'erreur liés

- `131030` : Destinataire hors fenêtre ou invalide
- `131047` : Re-engagement message outside 24h window
- `131051` : Message type not allowed outside window

## Stratégies d'implémentation dans Oracle

### 1. Tracking de la fenêtre de conversation

```php
// Entité WhatsAppConversation
class WhatsAppConversation {
    private $userId;
    private $phoneNumber;
    private $lastUserInteractionAt;
    private $windowExpiresAt;
    private $isOpen;
    
    public function isWindowOpen(): bool {
        return $this->windowExpiresAt > new DateTime();
    }
    
    public function updateUserInteraction(): void {
        $this->lastUserInteractionAt = new DateTime();
        $this->windowExpiresAt = (new DateTime())->modify('+24 hours');
        $this->isOpen = true;
    }
}
```

### 2. Vérification avant envoi

```php
// Dans WhatsAppService
public function sendMessage($user, $recipient, $type, $content) {
    $conversation = $this->conversationRepo->findByPhoneNumber($recipient);
    
    // Si pas de conversation ou fenêtre fermée
    if (!$conversation || !$conversation->isWindowOpen()) {
        if ($type === 'text') {
            throw new WhatsAppWindowClosedException(
                "Cannot send text message outside 24h window. Use template instead."
            );
        }
    }
    
    // Continuer avec l'envoi...
}
```

### 3. Webhook pour maintenir l'état

```php
// Dans WhatsAppWebhookController
public function handleIncomingMessage($payload) {
    $phoneNumber = $payload['from'];
    $conversation = $this->conversationRepo->findByPhoneNumber($phoneNumber);
    
    if (!$conversation) {
        $conversation = new WhatsAppConversation();
        $conversation->setPhoneNumber($phoneNumber);
    }
    
    $conversation->updateUserInteraction();
    $this->conversationRepo->save($conversation);
    
    // Traiter le message...
}
```

## Cas d'usage pratiques

### Scénario 1 : Support client

1. Client envoie : "J'ai un problème avec ma commande"
2. ✅ Fenêtre ouverte pour 24h
3. Support peut répondre avec des messages texte normaux
4. Si résolution prend > 24h, utiliser un template pour relancer

### Scénario 2 : Marketing

1. Entreprise veut envoyer une promotion
2. ❌ Pas de fenêtre ouverte (client n'a pas interagi)
3. Doit utiliser un template marketing approuvé
4. Si client répond au template, fenêtre s'ouvre

### Scénario 3 : Notifications transactionnelles

1. Commande confirmée, entreprise veut notifier
2. ❌ Pas de fenêtre ouverte automatiquement
3. Utiliser un template transactionnel (ex: order_confirmation)
4. Fenêtre s'ouvre si client répond

## Best Practices

### 1. Toujours vérifier l'état de la fenêtre

```javascript
// Frontend Vue.js
async sendWhatsAppMessage(recipient, message) {
  try {
    // Vérifier d'abord si fenêtre ouverte
    const windowStatus = await this.checkConversationWindow(recipient);
    
    if (!windowStatus.isOpen && message.type === 'text') {
      this.showTemplateSelector(); // Forcer l'utilisation d'un template
      return;
    }
    
    // Envoyer le message
    await this.whatsappStore.sendMessage(recipient, message);
  } catch (error) {
    if (error.code === 131030) {
      this.handleWindowClosedError();
    }
  }
}
```

### 2. Préparer des templates pour tous les cas

```yaml
Templates nécessaires:
- welcome_message: Pour initier une conversation
- order_confirmation: Pour notifications de commande
- appointment_reminder: Pour rappels de RDV
- customer_feedback: Pour demander des avis
- reengagement: Pour réactiver des clients inactifs
```

### 3. UI/UX adaptatif

```vue
<template>
  <div class="message-composer">
    <div v-if="!conversationWindow.isOpen" class="window-closed-warning">
      ⚠️ La fenêtre de conversation est fermée. 
      Vous pouvez uniquement envoyer des messages template.
    </div>
    
    <select v-if="!conversationWindow.isOpen" v-model="selectedTemplate">
      <option v-for="template in templates" :value="template.id">
        {{ template.name }}
      </option>
    </select>
    
    <textarea 
      v-else 
      v-model="messageText"
      placeholder="Tapez votre message..."
    />
  </div>
</template>
```

## Tests et Validation

### Script de test pour la fenêtre

```php
// scripts/test-whatsapp-window.php
$testCases = [
    // Cas 1: Fenêtre ouverte
    [
        'recipient' => '+2250777104936',
        'lastInteraction' => '-2 hours',
        'messageType' => 'text',
        'shouldSucceed' => true
    ],
    // Cas 2: Fenêtre fermée
    [
        'recipient' => '+2250777104937',
        'lastInteraction' => '-25 hours',
        'messageType' => 'text',
        'shouldSucceed' => false
    ],
    // Cas 3: Template hors fenêtre
    [
        'recipient' => '+2250777104938',
        'lastInteraction' => '-48 hours',
        'messageType' => 'template',
        'shouldSucceed' => true
    ]
];
```

## Conclusion

La gestion de la fenêtre de 24 heures est CRITIQUE pour une intégration WhatsApp réussie. Ne pas la respecter entraînera :
- Des échecs d'envoi de messages
- Une mauvaise expérience utilisateur
- Des coûts inutiles (tentatives d'envoi échouées)
- Potentiellement des pénalités de Meta

Toujours :
1. Tracker l'état des conversations
2. Vérifier avant d'envoyer
3. Avoir des templates de secours
4. Informer l'utilisateur des limitations