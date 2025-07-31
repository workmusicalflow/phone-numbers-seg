# Exemple d'Utilisation : Patterns Command & Observer

## Vue d'Ensemble

La Phase 2 introduit les patterns Command et Observer pour améliorer l'architecture :

- **Command Pattern** : Encapsule chaque action comme un objet
- **Observer Pattern** : Système d'événements découplé
- **Command Bus** : Orchestrateur centralisé

## Architecture Implémentée

```
┌─────────────────┐     ┌──────────────┐     ┌─────────────────┐
│   Controller    │────▶│ Command Bus  │────▶│    Command      │
│                 │     │              │     │                 │
└─────────────────┘     └──────────────┘     └─────────────────┘
                               │                      │
                               ▼                      ▼
                        ┌──────────────┐     ┌─────────────────┐
                        │ Middlewares  │     │ WhatsApp Service│
                        │              │     │                 │
                        └──────────────┘     └─────────────────┘
                                                     │
                                                     ▼
                        ┌──────────────┐     ┌─────────────────┐
                        │Event Dispatch│────▶│   Listeners     │
                        │              │     │                 │
                        └──────────────┘     └─────────────────┘
```

## Exemple d'Utilisation

### 1. Configuration dans le Controller

```php
// Dans un contrôleur GraphQL ou REST
class WhatsAppController
{
    private WhatsAppServiceInterface $whatsappService;
    
    public function sendTemplate(array $data)
    {
        // Le service utilise maintenant le Command Pattern en interne
        $messageHistory = $this->whatsappService->sendTemplateMessage(
            $user,
            $data['recipient'],
            $data['templateName'],
            $data['languageCode'],
            $data['headerImageUrl'] ?? null,
            $data['bodyParams'] ?? []
        );
        
        return [
            'success' => true,
            'messageId' => $messageHistory->getWabaMessageId()
        ];
    }
}
```

### 2. Ce qui se passe en coulisses

#### Étape 1 : Création de la commande
```php
$command = new SendTemplateCommand(
    $user,
    $recipient,
    $templateName,
    // ... autres paramètres
);
```

#### Étape 2 : Passage par le Command Bus
```php
// Le Command Bus exécute les middlewares
LoggingMiddleware::before($command); // Log le début

// Exécution de la commande
$result = $command->execute();

LoggingMiddleware::after($command, $result); // Log la fin
```

#### Étape 3 : Déclenchement des événements
```php
// En cas de succès
$event = new TemplateMessageSentEvent(...);
$eventDispatcher->dispatch($event);

// Les listeners réagissent
CreditDeductionListener::handle($event);  // Déduit les crédits
NotificationListener::handle($event);      // Envoie notification
```

## Avantages de cette Architecture

### 1. **Découplage**
- Les actions sont indépendantes
- Les effets de bord sont gérés par les listeners
- Facile d'ajouter de nouvelles fonctionnalités

### 2. **Traçabilité**
```php
// Chaque commande a un ID unique
[2025-05-27 10:00:00] INFO: Command execution started {
    "command": "send_template_message",
    "command_id": "cmd_12345",
    "user_id": 42,
    "template": "hello_world"
}
```

### 3. **Extensibilité**
Ajouter un nouveau listener est trivial :
```php
// Nouveau listener pour analytics
class AnalyticsListener implements ListenerInterface
{
    public function handle(EventInterface $event): void
    {
        if ($event instanceof TemplateMessageSentEvent) {
            $this->analytics->track('whatsapp_sent', [
                'template' => $event->getTemplateName(),
                'user' => $event->getUser()->getId()
            ]);
        }
    }
}

// L'ajouter
$eventDispatcher->addListener(
    'whatsapp.template_message.sent',
    new AnalyticsListener($analytics)
);
```

### 4. **Testabilité**
```php
// Test unitaire simple
public function testSendTemplateCommand()
{
    $command = new SendTemplateCommand(...);
    
    // Mock du service
    $mockService = $this->createMock(WhatsAppServiceRefactored::class);
    $mockService->expects($this->once())
        ->method('sendTemplateMessage')
        ->willReturn($expectedResult);
    
    $result = $command->execute();
    
    $this->assertTrue($result->isSuccess());
}
```

## Envoi en Batch

La nouvelle architecture permet facilement l'envoi en batch :

```php
// Envoyer 100 messages
$messages = [
    ['recipient' => '+22501234567', 'templateName' => 'welcome', ...],
    ['recipient' => '+22507654321', 'templateName' => 'reminder', ...],
    // ... 98 autres
];

$results = $whatsappService->sendBatchTemplateMessages($messages);

// Résultats individuels
foreach ($results as $key => $result) {
    if ($result->isSuccess()) {
        echo "Message $key envoyé : " . $result->getData()->getWabaMessageId();
    } else {
        echo "Message $key échoué : " . $result->getMessage();
    }
}
```

## Statistiques en Temps Réel

```php
// Récupérer les stats du Command Bus
$stats = $whatsappService->getCommandStatistics();

/*
[
    'send_template_message' => [
        'total' => 1543,
        'success' => 1520,
        'failure' => 23,
        'avg_duration' => 0.245, // secondes
        'total_duration' => 378.135
    ]
]
*/
```

## Migration Progressive

### Activer la version avec Commands :

```php
// Dans src/config/di.php
if (getenv('USE_WHATSAPP_COMMANDS') === 'true') {
    $definitions = array_merge(
        $definitions, 
        require __DIR__ . '/di/whatsapp-with-commands.php'
    );
}
```

### Ou pour tester :

```bash
# Activer temporairement
export USE_WHATSAPP_COMMANDS=true
php artisan serve
```

## Conclusion

Les patterns Command et Observer apportent :

- **Flexibilité** : Ajout facile de nouvelles fonctionnalités
- **Maintenabilité** : Code mieux organisé et découplé
- **Observabilité** : Meilleur logging et monitoring
- **Évolutivité** : Prêt pour l'exécution asynchrone

Cette architecture prépare parfaitement la Phase 3 (Circuit Breaker et Retry) car :
- Les commandes peuvent être rejouées facilement
- Les événements permettent de monitorer les échecs
- Le Command Bus peut implémenter des stratégies de retry