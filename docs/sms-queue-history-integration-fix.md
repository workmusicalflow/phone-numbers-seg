# Correction de l'intégration File d'attente SMS / Historique SMS

## Problème identifié

L'audit du système d'historique des SMS a révélé un problème critique : les SMS envoyés via la file d'attente (`SMSQueueService`) ne sont pas correctement enregistrés dans l'historique des SMS. Cela explique pourquoi l'historique n'est pas mis à jour lors des envois en masse.

La cause principale est que `SMSQueueService` dans la méthode `processNextBatch()` utilise directement `OrangeAPIClient->sendSMS()` sans passer par `SMSSenderService`, qui est responsable de déclencher les événements `sms.sent` et `sms.failed`. Ces événements sont nécessaires pour que l'observateur `SMSHistoryObserver` crée les entrées d'historique.

## Solution proposée

### 1. Modifier SMSQueueService pour utiliser SMSSenderService

La solution consiste à injecter `SMSSenderService` dans `SMSQueueService` et à l'utiliser pour envoyer les SMS depuis la file d'attente, assurant ainsi que les événements nécessaires soient déclenchés.

#### Modifications nécessaires :

1. **Ajouter la dépendance SMSSenderService** dans la classe SMSQueueService
2. **Mettre à jour le constructeur** pour injecter le service
3. **Modifier la méthode processNextBatch** pour utiliser SMSSenderService au lieu d'OrangeAPIClient directement
4. **Ajouter des données supplémentaires** aux événements pour la traçabilité

### 2. Mise à jour du système d'observateurs

Pour que l'intégration soit complète, le SMSHistoryObserver doit être mis à jour pour prendre en compte des données supplémentaires spécifiques à la file d'attente (batchId, queueId, etc.).

### 3. Configuration de l'injection de dépendances

Mise à jour de la configuration de l'injection de dépendances dans `services.php` pour refléter la nouvelle structure du constructeur.

## Plan d'implémentation

### 1. Modification de SMSQueueService

```php
// Ajouter une propriété pour SMSSenderService
/**
 * @var SMSSenderServiceInterface
 */
private $smsSenderService;

// Mettre à jour le constructeur
public function __construct(
    SMSQueueRepositoryInterface $smsQueueRepository,
    PhoneNumberRepositoryInterface $phoneNumberRepository,
    ContactRepositoryInterface $contactRepository,
    SegmentRepositoryInterface $segmentRepository,
    OrangeAPIClientInterface $orangeAPIClient,
    AuthServiceInterface $authService,
    LoggerInterface $logger,
    SMSSenderServiceInterface $smsSenderService, // Nouveau paramètre
    int $maxAttempts = 5
) {
    $this->smsQueueRepository = $smsQueueRepository;
    $this->phoneNumberRepository = $phoneNumberRepository;
    $this->contactRepository = $contactRepository;
    $this->segmentRepository = $segmentRepository;
    $this->orangeAPIClient = $orangeAPIClient;
    $this->authService = $authService;
    $this->logger = $logger;
    $this->smsSenderService = $smsSenderService; // Stockage du service
    $this->maxAttempts = $maxAttempts;
}

// Modifier la méthode processNextBatch
// Remplacer l'appel direct à OrangeAPIClient par SMSSenderService
$normalizedNumber = $this->normalizePhoneNumber($phoneNumber);
$result = $this->smsSenderService->sendSMS(
    $normalizedNumber, 
    $item->getMessage(), 
    $item->getSenderName(),
    $item->getUserId()
);

// Adapter le traitement du résultat selon le nouveau format
```

### 2. Mise à jour de SMSHistoryObserver

```php
// Dans la méthode update, ajouter le support pour les métadonnées supplémentaires
$userId = isset($data['userId']) ? $data['userId'] : null;
$queueId = isset($data['queueId']) ? $data['queueId'] : null;
$batchId = isset($data['batchId']) ? $data['batchId'] : null;

// Utiliser ces données lors de la création de l'entrée d'historique
```

### 3. Modifier le fichier de configuration DI

```php
// Mettre à jour la définition dans services.php
\App\Services\Interfaces\SMSQueueServiceInterface::class => factory(function (Container $container) {
    // ...
    return new \App\Services\SMSQueueService(
        // Paramètres existants
        $container->get(\App\Services\Interfaces\SMSSenderServiceInterface::class), // Nouveau
        $maxAttempts
    );
}),
```

## Tests à effectuer

1. **Test d'envoi en masse** via l'interface utilisateur - vérifier l'apparition des SMS dans l'historique
2. **Test de la file d'attente** en exécutant directement le script cron et en vérifiant l'historique
3. **Vérification des performances** pour s'assurer que les changements n'affectent pas négativement les performances d'envoi en masse

## Impact sur le système

Cette modification permettra une meilleure cohérence des données et améliorera l'expérience utilisateur en assurant que tous les SMS envoyés, qu'ils soient directs ou via la file d'attente, sont correctement enregistrés dans l'historique et visibles dans l'interface.

## Statut d'implémentation

☐ En attente d'approbation
☐ En cours d'implémentation  
☐ Implémenté et en test
☐ Déployé en production