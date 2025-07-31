# Implémentation du Pattern Observer

## Contexte et Problématique

Dans l'architecture initiale du projet Oracle, les composants étaient fortement couplés. Par exemple, le service d'envoi de SMS (`SMSService`) devait connaître directement le service d'historique pour enregistrer les SMS envoyés. Ce couplage fort rendait le code difficile à maintenir et à étendre.

## Solution Implémentée

Nous avons implémenté le pattern Observer pour découpler les composants. Ce pattern permet à un objet (le sujet) de notifier un ensemble d'observateurs lorsqu'un événement se produit, sans avoir à connaître ces observateurs.

## Composants Clés

### Interface Observer

```php
interface ObserverInterface
{
    /**
     * Méthode appelée lorsqu'un événement est déclenché
     *
     * @param string $eventName Nom de l'événement
     * @param array $data Données associées à l'événement
     * @return void
     */
    public function update(string $eventName, array $data): void;
}
```

### Interface Subject

```php
interface SubjectInterface
{
    /**
     * Attache un observateur au sujet
     *
     * @param ObserverInterface $observer
     * @return void
     */
    public function attach(ObserverInterface $observer): void;

    /**
     * Détache un observateur du sujet
     *
     * @param ObserverInterface $observer
     * @return void
     */
    public function detach(ObserverInterface $observer): void;

    /**
     * Notifie tous les observateurs d'un événement
     *
     * @param string $eventName
     * @param array $data
     * @return void
     */
    public function notify(string $eventName, array $data): void;

    /**
     * Attache un observateur pour un événement spécifique
     *
     * @param ObserverInterface $observer
     * @param string $eventName
     * @return void
     */
    public function attachForEvent(ObserverInterface $observer, string $eventName): void;

    /**
     * Détache un observateur d'un événement spécifique
     *
     * @param ObserverInterface $observer
     * @param string $eventName
     * @return void
     */
    public function detachFromEvent(ObserverInterface $observer, string $eventName): void;
}
```

### Implémentation du Gestionnaire d'Événements

```php
class EventManager implements SubjectInterface
{
    /**
     * @var array<string, ObserverInterface>
     */
    private $observers = [];

    /**
     * @var array<string, array<string, ObserverInterface>>
     */
    private $eventObservers = [];

    public function attach(ObserverInterface $observer): void
    {
        $id = spl_object_hash($observer);
        $this->observers[$id] = $observer;
    }

    public function detach(ObserverInterface $observer): void
    {
        $id = spl_object_hash($observer);
        unset($this->observers[$id]);
    }

    public function notify(string $eventName, array $data): void
    {
        // Notifier tous les observateurs généraux
        foreach ($this->observers as $observer) {
            $observer->update($eventName, $data);
        }

        // Notifier les observateurs spécifiques à cet événement
        if (isset($this->eventObservers[$eventName])) {
            foreach ($this->eventObservers[$eventName] as $observer) {
                $observer->update($eventName, $data);
            }
        }
    }

    public function attachForEvent(ObserverInterface $observer, string $eventName): void
    {
        $id = spl_object_hash($observer);
        $this->eventObservers[$eventName][$id] = $observer;
    }

    public function detachFromEvent(ObserverInterface $observer, string $eventName): void
    {
        $id = spl_object_hash($observer);
        unset($this->eventObservers[$eventName][$id]);
    }
}
```

### Observateur pour l'Historique SMS

```php
class SMSHistoryObserver implements ObserverInterface
{
    private $repository;

    public function __construct(SMSHistoryRepository $repository)
    {
        $this->repository = $repository;
    }

    public function update(string $eventName, array $data): void
    {
        if ($eventName === 'sms.sent') {
            // Enregistrer un SMS envoyé avec succès
            $this->repository->save(new SMSHistory(
                $data['phoneNumber'],
                $data['message'],
                'SENT',
                null,
                new \DateTime()
            ));
        } elseif ($eventName === 'sms.failed') {
            // Enregistrer un SMS dont l'envoi a échoué
            $this->repository->save(new SMSHistory(
                $data['phoneNumber'],
                $data['message'],
                'FAILED',
                $data['error'] ?? 'Unknown error',
                new \DateTime()
            ));
        }
    }
}
```

### Service d'Envoi de SMS Utilisant le Pattern Observer

```php
class SMSSenderService implements SMSSenderServiceInterface
{
    private $orangeAPIClient;
    private $eventManager;

    public function __construct(
        OrangeAPIClientInterface $orangeAPIClient,
        SubjectInterface $eventManager
    ) {
        $this->orangeAPIClient = $orangeAPIClient;
        $this->eventManager = $eventManager;
    }

    public function sendSMS(string $phoneNumber, string $message, ?string $senderName = null): bool
    {
        try {
            // Envoyer le SMS via l'API Orange
            $result = $this->orangeAPIClient->sendSMS($phoneNumber, $message, $senderName);

            // Notifier les observateurs que le SMS a été envoyé
            $this->eventManager->notify('sms.sent', [
                'phoneNumber' => $phoneNumber,
                'message' => $message,
                'senderName' => $senderName ?? 'System',
                'messageId' => $result['messageId'] ?? null
            ]);

            return true;
        } catch (\Exception $e) {
            // Notifier les observateurs que l'envoi du SMS a échoué
            $this->eventManager->notify('sms.failed', [
                'phoneNumber' => $phoneNumber,
                'message' => $message,
                'senderName' => $senderName ?? 'System',
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}
```

### Configuration dans le Conteneur d'Injection de Dépendances

```php
// Dans di.php
SubjectInterface::class => factory(function () {
    return new EventManager();
}),

SMSHistoryObserver::class => factory(function (Container $container) {
    return new SMSHistoryObserver(
        $container->get(SMSHistoryRepository::class)
    );
}),

// Configuration des observateurs
\App\Services\SMSSenderService::class => factory(function (Container $container) {
    $service = new \App\Services\SMSSenderService(
        $container->get(\App\Services\Interfaces\OrangeAPIClientInterface::class),
        $container->get(SubjectInterface::class)
    );

    // Configuration des observateurs
    $eventManager = $container->get(SubjectInterface::class);
    $eventManager->attachForEvent(
        $container->get(SMSHistoryObserver::class),
        'sms.sent'
    );
    $eventManager->attachForEvent(
        $container->get(SMSHistoryObserver::class),
        'sms.failed'
    );

    return $service;
}),
```

## Avantages Obtenus

1. **Découplage** : Le service d'envoi de SMS n'a plus besoin de connaître le service d'historique.
2. **Extensibilité** : Ajout facile de nouveaux observateurs sans modifier le code existant.
3. **Flexibilité** : Les observateurs peuvent être ajoutés ou supprimés dynamiquement.
4. **Réutilisabilité** : Le même mécanisme d'événements peut être utilisé pour d'autres parties de l'application.
5. **Testabilité** : Les composants peuvent être testés indépendamment.

## Exemple d'Extension

Pour ajouter un nouvel observateur, par exemple pour envoyer des notifications par email lorsqu'un SMS échoue, il suffit de créer un nouvel observateur et de l'attacher au gestionnaire d'événements :

```php
class EmailNotificationObserver implements ObserverInterface
{
    private $emailService;

    public function __construct(EmailServiceInterface $emailService)
    {
        $this->emailService = $emailService;
    }

    public function update(string $eventName, array $data): void
    {
        if ($eventName === 'sms.failed') {
            // Envoyer une notification par email
            $this->emailService->sendEmail(
                'admin@example.com',
                'SMS Failed',
                "SMS to {$data['phoneNumber']} failed: {$data['error']}"
            );
        }
    }
}

// Dans di.php
EmailNotificationObserver::class => factory(function (Container $container) {
    return new EmailNotificationObserver(
        $container->get(EmailServiceInterface::class)
    );
}),

// Configuration des observateurs
\App\Services\SMSSenderService::class => factory(function (Container $container) {
    $service = new \App\Services\SMSSenderService(
        $container->get(\App\Services\Interfaces\OrangeAPIClientInterface::class),
        $container->get(SubjectInterface::class)
    );

    // Configuration des observateurs
    $eventManager = $container->get(SubjectInterface::class);
    $eventManager->attachForEvent(
        $container->get(SMSHistoryObserver::class),
        'sms.sent'
    );
    $eventManager->attachForEvent(
        $container->get(SMSHistoryObserver::class),
        'sms.failed'
    );
    $eventManager->attachForEvent(
        $container->get(EmailNotificationObserver::class),
        'sms.failed'
    );

    return $service;
}),
```

## Autres Utilisations Possibles

Le pattern Observer peut être utilisé dans d'autres parties de l'application :

1. **Journalisation** : Enregistrer les actions importantes dans un journal.
2. **Audit** : Suivre les modifications apportées aux données.
3. **Cache** : Invalider le cache lorsque les données sont modifiées.
4. **Notifications** : Envoyer des notifications aux utilisateurs.
5. **Statistiques** : Collecter des statistiques sur l'utilisation de l'application.

## Conclusion

L'implémentation du pattern Observer a considérablement amélioré l'architecture de l'application en découplant les composants et en facilitant l'extension du système. Cette approche permet d'ajouter facilement de nouvelles fonctionnalités sans modifier le code existant, tout en améliorant la testabilité et la maintenabilité du code.
