# Patterns de conception

Cette page présente les principaux patterns de conception utilisés dans le projet Oracle. Ces patterns sont des solutions éprouvées à des problèmes récurrents en conception logicielle, et ils contribuent à la qualité, la maintenabilité et l'évolutivité du code.

## Vue d'ensemble

Le projet Oracle utilise plusieurs patterns de conception pour résoudre différents types de problèmes :

1. **Patterns structurels** : Organisation des classes et objets

   - Repository
   - Adapter
   - Facade

2. **Patterns comportementaux** : Communication entre objets

   - Observer
   - Chain of Responsibility
   - Strategy
   - Command

3. **Patterns créationnels** : Création d'objets
   - Factory
   - Singleton
   - Builder

## Pattern Repository

### Description

Le pattern Repository fournit une abstraction de la couche de données, isolant la logique métier des détails de persistance. Il agit comme une collection en mémoire d'objets, offrant des méthodes pour ajouter, supprimer, mettre à jour et récupérer des objets.

### Utilisation dans Oracle

Dans Oracle, chaque entité métier (PhoneNumber, Segment, User, etc.) a son propre repository qui encapsule la logique d'accès aux données.

### Exemple

```php
// Interface du repository
interface PhoneNumberRepositoryInterface
{
    public function findById(int $id): ?PhoneNumber;
    public function findByNumber(string $number): ?PhoneNumber;
    public function findAll(): array;
    public function save(PhoneNumber $phone): void;
    public function delete(PhoneNumber $phone): void;
}

// Implémentation du repository
class PhoneNumberRepository implements PhoneNumberRepositoryInterface
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?PhoneNumber
    {
        $stmt = $this->pdo->prepare('SELECT * FROM phone_numbers WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) {
            return null;
        }

        return $this->hydrate($data);
    }

    // Autres méthodes...
}
```

### Avantages

- Séparation des préoccupations
- Facilité de test (possibilité de mocker le repository)
- Centralisation de la logique d'accès aux données
- Abstraction de la source de données (possibilité de changer de base de données sans modifier la logique métier)

## Pattern Observer

### Description

Le pattern Observer définit une relation un-à-plusieurs entre objets, de sorte que lorsqu'un objet change d'état, tous ses observateurs sont notifiés et mis à jour automatiquement.

### Utilisation dans Oracle

Dans Oracle, le pattern Observer est utilisé pour :

- Notifier les administrateurs lors de l'envoi de SMS
- Mettre à jour l'historique des SMS
- Envoyer des notifications en temps réel aux utilisateurs

### Exemple

```php
// Interface du sujet
interface SubjectInterface
{
    public function attach(ObserverInterface $observer): void;
    public function detach(ObserverInterface $observer): void;
    public function notify(): void;
}

// Interface de l'observateur
interface ObserverInterface
{
    public function update(SubjectInterface $subject): void;
}

// Implémentation du sujet
class SMSService implements SubjectInterface
{
    private $observers = [];
    private $lastSentSMS;

    public function attach(ObserverInterface $observer): void
    {
        $this->observers[] = $observer;
    }

    public function detach(ObserverInterface $observer): void
    {
        $key = array_search($observer, $this->observers, true);
        if ($key !== false) {
            unset($this->observers[$key]);
        }
    }

    public function notify(): void
    {
        foreach ($this->observers as $observer) {
            $observer->update($this);
        }
    }

    public function sendSMS(string $to, string $message): void
    {
        // Logique d'envoi de SMS
        $this->lastSentSMS = [
            'to' => $to,
            'message' => $message,
            'timestamp' => time()
        ];

        // Notifier les observateurs
        $this->notify();
    }

    public function getLastSentSMS(): array
    {
        return $this->lastSentSMS;
    }
}

// Implémentation de l'observateur
class SMSHistoryObserver implements ObserverInterface
{
    private $historyRepository;

    public function __construct(SMSHistoryRepositoryInterface $historyRepository)
    {
        $this->historyRepository = $historyRepository;
    }

    public function update(SubjectInterface $subject): void
    {
        if ($subject instanceof SMSService) {
            $smsData = $subject->getLastSentSMS();

            $history = new SMSHistory();
            $history->phone_number = $smsData['to'];
            $history->message = $smsData['message'];
            $history->sent_at = date('Y-m-d H:i:s', $smsData['timestamp']);

            $this->historyRepository->save($history);
        }
    }
}
```

### Avantages

- Couplage faible entre le sujet et les observateurs
- Support pour la diffusion de changements d'état
- Extensibilité (ajout facile de nouveaux observateurs)

## Pattern Chain of Responsibility

### Description

Le pattern Chain of Responsibility permet de passer une requête le long d'une chaîne de gestionnaires. Chaque gestionnaire décide soit de traiter la requête, soit de la passer au gestionnaire suivant dans la chaîne.

### Utilisation dans Oracle

Dans Oracle, ce pattern est utilisé pour la segmentation des numéros de téléphone, où chaque gestionnaire est responsable d'une partie spécifique du numéro (code pays, code opérateur, numéro d'abonné).

### Exemple

```php
// Interface du gestionnaire
interface SegmentationHandlerInterface
{
    public function setNext(SegmentationHandlerInterface $handler): SegmentationHandlerInterface;
    public function handle(string $number, array &$segments): void;
}

// Gestionnaire abstrait
abstract class AbstractSegmentationHandler implements SegmentationHandlerInterface
{
    protected $nextHandler;

    public function setNext(SegmentationHandlerInterface $handler): SegmentationHandlerInterface
    {
        $this->nextHandler = $handler;
        return $handler;
    }

    public function handle(string $number, array &$segments): void
    {
        if ($this->nextHandler) {
            $this->nextHandler->handle($number, $segments);
        }
    }
}

// Gestionnaire concret pour le code pays
class CountryCodeHandler extends AbstractSegmentationHandler
{
    public function handle(string $number, array &$segments): void
    {
        // Extraire le code pays
        if (substr($number, 0, 1) === '0') {
            $segments['countryCode'] = '225'; // Côte d'Ivoire
        } else {
            // Logique pour d'autres pays
        }

        // Passer au gestionnaire suivant
        parent::handle($number, $segments);
    }
}

// Gestionnaire concret pour le code opérateur
class OperatorCodeHandler extends AbstractSegmentationHandler
{
    public function handle(string $number, array &$segments): void
    {
        // Extraire le code opérateur
        $operatorCode = substr($number, 1, 2);
        $segments['operatorCode'] = $operatorCode;

        // Passer au gestionnaire suivant
        parent::handle($number, $segments);
    }
}

// Gestionnaire concret pour le numéro d'abonné
class SubscriberNumberHandler extends AbstractSegmentationHandler
{
    public function handle(string $number, array &$segments): void
    {
        // Extraire le numéro d'abonné
        $subscriberNumber = substr($number, 3);
        $segments['subscriberNumber'] = $subscriberNumber;

        // Passer au gestionnaire suivant
        parent::handle($number, $segments);
    }
}

// Utilisation de la chaîne
class ChainOfResponsibilityPhoneSegmentationService implements PhoneSegmentationServiceInterface
{
    private $chain;

    public function __construct()
    {
        // Construire la chaîne
        $this->chain = new CountryCodeHandler();
        $this->chain->setNext(new OperatorCodeHandler())
                   ->setNext(new SubscriberNumberHandler());
    }

    public function segmentPhone(string $number): array
    {
        $segments = [];
        $this->chain->handle($number, $segments);
        return $segments;
    }
}
```

### Avantages

- Réduction du couplage entre l'émetteur d'une requête et ses récepteurs
- Flexibilité dans l'attribution des responsabilités aux objets
- Possibilité d'ajouter ou de supprimer des gestionnaires sans modifier le code client

## Pattern Strategy

### Description

Le pattern Strategy définit une famille d'algorithmes, encapsule chacun d'eux et les rend interchangeables. Il permet à l'algorithme de varier indépendamment des clients qui l'utilisent.

### Utilisation dans Oracle

Dans Oracle, ce pattern est utilisé pour implémenter différentes stratégies de segmentation de numéros de téléphone en fonction du pays ou de l'opérateur.

### Exemple

```php
// Interface de la stratégie
interface SegmentationStrategyInterface
{
    public function segment(string $number): array;
}

// Stratégie concrète pour la Côte d'Ivoire
class IvoryCoastSegmentationStrategy implements SegmentationStrategyInterface
{
    public function segment(string $number): array
    {
        $segments = [];

        // Code pays
        $segments['countryCode'] = '225';

        // Code opérateur
        $operatorCode = substr($number, 1, 2);
        $segments['operatorCode'] = $operatorCode;

        // Numéro d'abonné
        $subscriberNumber = substr($number, 3);
        $segments['subscriberNumber'] = $subscriberNumber;

        return $segments;
    }
}

// Stratégie concrète pour un autre pays
class OtherCountrySegmentationStrategy implements SegmentationStrategyInterface
{
    public function segment(string $number): array
    {
        // Logique de segmentation pour un autre pays
        // ...

        return $segments;
    }
}

// Factory pour créer la stratégie appropriée
class SegmentationStrategyFactory
{
    public function createStrategy(string $number): SegmentationStrategyInterface
    {
        // Déterminer la stratégie en fonction du numéro
        if (substr($number, 0, 1) === '0') {
            return new IvoryCoastSegmentationStrategy();
        } else {
            return new OtherCountrySegmentationStrategy();
        }
    }
}

// Utilisation de la stratégie
class PhoneSegmentationService implements PhoneSegmentationServiceInterface
{
    private $strategyFactory;

    public function __construct(SegmentationStrategyFactory $strategyFactory)
    {
        $this->strategyFactory = $strategyFactory;
    }

    public function segmentPhone(string $number): array
    {
        $strategy = $this->strategyFactory->createStrategy($number);
        return $strategy->segment($number);
    }
}
```

### Avantages

- Encapsulation des algorithmes dans des classes séparées
- Élimination des instructions conditionnelles
- Facilité pour ajouter de nouvelles stratégies
- Possibilité de changer de stratégie à l'exécution

## Pattern Factory

### Description

Le pattern Factory fournit une interface pour créer des objets dans une superclasse, mais permet aux sous-classes de modifier le type d'objets créés.

### Utilisation dans Oracle

Dans Oracle, ce pattern est utilisé pour créer des stratégies de segmentation, des gestionnaires de segmentation, et d'autres objets complexes.

### Exemple

```php
// Factory pour créer des stratégies de segmentation
class SegmentationStrategyFactory
{
    public function createStrategy(string $number): SegmentationStrategyInterface
    {
        // Déterminer la stratégie en fonction du numéro
        if (substr($number, 0, 1) === '0') {
            return new IvoryCoastSegmentationStrategy();
        } else if (substr($number, 0, 3) === '233') {
            return new GhanaSegmentationStrategy();
        } else if (substr($number, 0, 3) === '234') {
            return new NigeriaSegmentationStrategy();
        } else {
            return new DefaultSegmentationStrategy();
        }
    }
}

// Factory pour créer des gestionnaires de segmentation
class SegmentationHandlerFactory
{
    public function createHandlerChain(): SegmentationHandlerInterface
    {
        $countryCodeHandler = new CountryCodeHandler();
        $operatorCodeHandler = new OperatorCodeHandler();
        $subscriberNumberHandler = new SubscriberNumberHandler();

        $countryCodeHandler->setNext($operatorCodeHandler);
        $operatorCodeHandler->setNext($subscriberNumberHandler);

        return $countryCodeHandler;
    }
}
```

### Avantages

- Centralisation de la logique de création d'objets
- Facilité pour ajouter de nouveaux types d'objets
- Réduction du couplage entre le créateur et les produits concrets

## Pattern Adapter

### Description

Le pattern Adapter permet à des interfaces incompatibles de travailler ensemble. Il agit comme un pont entre deux interfaces incompatibles.

### Utilisation dans Oracle

Dans Oracle, ce pattern est utilisé pour adapter l'API Orange SMS à l'interface SMSSenderService du projet.

### Exemple

```php
// Interface cible
interface SMSSenderServiceInterface
{
    public function send(string $to, string $message): bool;
    public function getStatus(string $messageId): string;
}

// Classe adaptée (API Orange)
class OrangeAPI
{
    public function sendSMS(array $params): array
    {
        // Logique d'envoi de SMS via l'API Orange
        return [
            'success' => true,
            'message_id' => 'MSG123456',
            'status' => 'sent'
        ];
    }

    public function checkDeliveryStatus(string $id): array
    {
        // Vérification du statut de livraison
        return [
            'message_id' => $id,
            'status' => 'delivered',
            'delivery_time' => '2023-04-08T15:30:45Z'
        ];
    }
}

// Adaptateur
class OrangeSMSAdapter implements SMSSenderServiceInterface
{
    private $orangeAPI;

    public function __construct(OrangeAPI $orangeAPI)
    {
        $this->orangeAPI = $orangeAPI;
    }

    public function send(string $to, string $message): bool
    {
        $params = [
            'to' => $to,
            'message' => $message,
            'sender' => 'ORACLE',
            'type' => 'text'
        ];

        $result = $this->orangeAPI->sendSMS($params);
        return $result['success'];
    }

    public function getStatus(string $messageId): string
    {
        $result = $this->orangeAPI->checkDeliveryStatus($messageId);
        return $result['status'];
    }
}
```

### Avantages

- Réutilisation de classes existantes sans modification
- Interopérabilité entre des interfaces incompatibles
- Séparation de l'interface et de l'implémentation

## Pattern Facade

### Description

Le pattern Facade fournit une interface unifiée à un ensemble d'interfaces dans un sous-système. Il définit une interface de plus haut niveau qui rend le sous-système plus facile à utiliser.

### Utilisation dans Oracle

Dans Oracle, ce pattern est utilisé pour simplifier l'interaction avec le sous-système de segmentation et d'envoi de SMS.

### Exemple

```php
// Facade
class SMSBusinessService implements SMSBusinessServiceInterface
{
    private $segmentationService;
    private $senderService;
    private $historyService;
    private $validationService;

    public function __construct(
        PhoneSegmentationServiceInterface $segmentationService,
        SMSSenderServiceInterface $senderService,
        SMSHistoryServiceInterface $historyService,
        SMSValidationServiceInterface $validationService
    ) {
        $this->segmentationService = $segmentationService;
        $this->senderService = $senderService;
        $this->historyService = $historyService;
        $this->validationService = $validationService;
    }

    public function sendSMS(string $to, string $message, string $sender): bool
    {
        // Valider le message
        if (!$this->validationService->validateMessage($message)) {
            throw new ValidationException('Message invalide');
        }

        // Segmenter le numéro
        $segments = $this->segmentationService->segmentPhone($to);

        // Envoyer le SMS
        $success = $this->senderService->send($to, $message);

        // Enregistrer l'historique
        if ($success) {
            $this->historyService->recordSMS($to, $message, $sender);
        }

        return $success;
    }
}
```

### Avantages

- Simplification de l'interface d'un sous-système complexe
- Réduction du couplage entre les clients et le sous-système
- Encapsulation de la logique métier complexe

## Pattern Singleton

### Description

Le pattern Singleton garantit qu'une classe n'a qu'une seule instance et fournit un point d'accès global à cette instance.

### Utilisation dans Oracle

Dans Oracle, ce pattern est utilisé pour des services qui doivent être uniques dans l'application, comme la configuration, la connexion à la base de données, et le logger.

### Exemple

```php
// Singleton pour la connexion à la base de données
class Database
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        $config = require __DIR__ . '/../config/database.php';

        $dsn = "{$config['driver']}:";
        if ($config['driver'] === 'sqlite') {
            $dsn .= $config['database'];
        } else {
            $dsn .= "host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
        }

        $this->pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    // Empêcher le clonage
    private function __clone() {}

    // Empêcher la désérialisation
    private function __wakeup() {}
}

// Utilisation
$db = Database::getInstance();
$pdo = $db->getConnection();
```

### Avantages

- Garantie d'une instance unique
- Point d'accès global à cette instance
- Initialisation paresseuse (l'instance n'est créée que lorsqu'elle est nécessaire)

## Pattern Command

### Description

Le pattern Command encapsule une requête sous forme d'objet, permettant de paramétrer des clients avec différentes requêtes, de mettre en file d'attente ou de journaliser les requêtes, et de prendre en charge les opérations annulables.

### Utilisation dans Oracle

Dans Oracle, ce pattern est utilisé pour implémenter les opérations d'envoi de SMS planifiés et les actions administratives.

### Exemple

```php
// Interface de la commande
interface CommandInterface
{
    public function execute(): void;
}

// Commande concrète pour l'envoi de SMS
class SendSMSCommand implements CommandInterface
{
    private $smsService;
    private $to;
    private $message;
    private $sender;

    public function __construct(
        SMSServiceInterface $smsService,
        string $to,
        string $message,
        string $sender
    ) {
        $this->smsService = $smsService;
        $this->to = $to;
        $this->message = $message;
        $this->sender = $sender;
    }

    public function execute(): void
    {
        $this->smsService->send($this->to, $this->message, $this->sender);
    }
}

// Commande concrète pour l'approbation d'un nom d'expéditeur
class ApproveSenderNameCommand implements CommandInterface
{
    private $senderNameService;
    private $senderNameId;

    public function __construct(
        SenderNameServiceInterface $senderNameService,
        int $senderNameId
    ) {
        $this->senderNameService = $senderNameService;
        $this->senderNameId = $senderNameId;
    }

    public function execute(): void
    {
        $this->senderNameService->approve($this->senderNameId);
    }
}

// Invocateur
class CommandInvoker
{
    private $commands = [];

    public function addCommand(CommandInterface $command): void
    {
        $this->commands[] = $command;
    }

    public function executeCommands(): void
    {
        foreach ($this->commands as $command) {
            $command->execute();
        }

        $this->commands = [];
    }
}

// Utilisation
$invoker = new CommandInvoker();
$invoker->addCommand(new SendSMSCommand($smsService, '0777104936', 'Hello', 'ORACLE'));
$invoker->addCommand(new ApproveSenderNameCommand($senderNameService, 123));
$invoker->executeCommands();
```

### Avantages

- Découplage entre l'invocateur et le récepteur
- Possibilité de paramétrer des clients avec différentes requêtes
- Support pour les opérations annulables
- Facilité pour implémenter des files d'attente et des journaux de commandes

## Pattern Builder

### Description

Le pattern Builder sépare la construction d'un objet complexe de sa représentation, permettant de créer différentes représentations avec le même processus de construction.

### Utilisation dans Oracle

Dans Oracle, ce pattern est utilisé pour construire des objets complexes comme les rapports, les configurations d'API, et les requêtes de segmentation.

### Exemple

```php
// Produit
class SMSReport
{
    public $title;
    public $period;
    public $data = [];
    public $charts = [];
    public $summary;

    public function display(): string
    {
        // Logique d'affichage du rapport
        return "Rapport: {$this->title} ({$this->period})";
    }
}

// Interface du builder
interface SMSReportBuilderInterface
{
    public function setTitle(string $title): self;
    public function setPeriod(string $from, string $to): self;
    public function addData(array $data): self;
    public function addChart(string $type, array $data): self;
    public function setSummary(string $summary): self;
    public function build(): SMSReport;
}

// Builder concret
class SMSReportBuilder implements SMSReportBuilderInterface
{
    private $report;

    public function __construct()
    {
        $this->reset();
    }

    public function reset(): void
    {
        $this->report = new SMSReport();
    }

    public function setTitle(string $title): self
    {
        $this->report->title = $title;
        return $this;
    }

    public function setPeriod(string $from, string $to): self
    {
        $this->report->period = "Du {$from} au {$to}";
        return $this;
    }

    public function addData(array $data): self
    {
        $this->report->data = array_merge($this->report->data, $data);
        return $this;
    }

    public function addChart(string $type, array $data): self
    {
        $this->report->charts[] = [
            'type' => $type,
            'data' => $data
        ];
        return $this;
    }

    public function setSummary(string $summary): self
    {
        $this->report->summary = $summary;
        return $this;
    }

    public function build(): SMSReport
    {
        $result = $this->report;
        $this->reset();
        return $result;
    }
}

// Directeur
class SMSReportDirector
{
    private $builder;

    public function __construct(SMSReportBuilderInterface $builder)
    {
        $this->builder = $builder;
    }

    public function buildDailyReport(string $date): SMSReport
    {
        return $this->builder
            ->setTitle('Rapport quotidien des SMS')
            ->setPeriod($date, $date)
            ->addData(['sms_count' => 150, 'delivery_rate' => 0.98])
            ->addChart('pie', ['delivered' => 147, 'failed' => 3])
            ->setSummary('Taux de livraison excellent pour la journée')
            ->build();
    }

    public function buildMonthlyReport(string $month, string $year): SMSReport
    {
        // Logique pour construire un rapport mensuel
        return $this->builder
            ->setTitle('Rapport mensuel des SMS')
            ->setPeriod("{$month}/01/{$year}", "{$month}/31/{$year}")
            // Autres étapes de construction
            ->build();
    }
}

// Utilisation
$builder = new SMSReportBuilder();
$director = new SMSReportDirector($builder);
$dailyReport = $director->buildDailyReport('2023-04-08');
echo $dailyReport->display();
```

### Avantages

- Séparation de la construction et de la représentation
- Contrôle précis du processus de construction
- Possibilité de créer différentes représentations avec le même processus
- Construction étape par étape, ce qui permet de différer certaines étapes

## Conclusion

Les patterns de conception utilisés dans le projet Oracle contribuent à sa qualité, sa maintenabilité et son évolutivité. Ils fournissent des solutions éprouvées à des problèmes récurrents en conception logicielle, et permettent de structurer le code de manière claire et cohérente.

L'utilisation judicieuse de ces patterns a permis de :

- Réduire le couplage entre les composants
- Améliorer la cohésion des classes
- Faciliter les tests unitaires et d'intégration
- Rendre le code plus évolutif et maintenable

La connaissance de ces patterns est essentielle pour comprendre l'architecture du projet et pour contribuer efficacement à son développement.
