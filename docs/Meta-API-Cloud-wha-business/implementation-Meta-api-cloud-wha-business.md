Voici une version réécrite et structurée du plan MVP, conçue pour être aussi claire et facile à suivre que possible, en intégrant les webhooks WhatsApp dans votre application PHP/Vue.js/SQLite existante.

---

**Plan Détaillé : MVP Intégration Webhooks WhatsApp entrants**

**Objectif :** Mettre en place la réception, le stockage (SQLite) et l'affichage (Vue/Quasar) des messages texte entrants de WhatsApp, en minimisant la complexité et en utilisant votre stack actuelle.

**Philosophie :** Priorité à la simplicité et à l'intégration rapide. Nous utiliserons vos composants existants (PHP-DI, DBAL, GraphQLite, Quasar) et n'ajouterons que le strict nécessaire. L'envoi de messages et la gestion des médias viendront après ce MVP.

---

**Partie 1 : Backend (PHP)**

**Étape 1.1 : Créer le Point d'Entrée pour les Webhooks**

*   **Objectif :** Avoir une URL unique que Meta peut appeler pour vous envoyer des notifications.
*   **Action :** Créez un nouveau fichier PHP directement dans votre dossier public.
*   **Fichier :** `public/webhook.php`

    ```php
    <?php
    // public/webhook.php
    // --- Point d'entrée dédié pour les webhooks WhatsApp ---

    // 1. Chargement initial (Autoloader, Environnement si nécessaire)
    require_once __DIR__ . '/../vendor/autoload.php'; 
    // Si vous chargez des variables d'env (.env) ou la config DI ici, faites-le

    use App\Infrastructure\Controller\WebhookController;
    use DI\ContainerBuilder; // Ou votre méthode habituelle pour obtenir le conteneur

    try {
        // 2. Configuration et récupération du conteneur DI
        // Assurez-vous que votre config DI (ex: config/di.php) est chargée
        $containerBuilder = new ContainerBuilder();
        // Ajoutez vos définitions de configuration DI ici si elles ne sont pas chargées automatiquement
        $containerBuilder->addDefinitions(__DIR__ . '/../config/di.php'); 
        $container = $containerBuilder->build();

        // 3. Récupération du contrôleur dédié aux webhooks
        $webhookController = $container->get(WebhookController::class);

        // 4. Détermination de la méthode HTTP (GET pour vérification, POST pour notifications)
        $method = $_SERVER['REQUEST_METHOD'];

        // 5. Routage simple vers la méthode appropriée du contrôleur
        if ($method === 'GET') {
            // Meta vérifie que votre endpoint est valide
            $webhookController->verifyWebhook(); 
        } elseif ($method === 'POST') {
            // Meta envoie une notification (nouveau message, statut, etc.)
            $webhookController->handleWebhook(); 
        } else {
            // Méthode non supportée (DELETE, PUT, etc.)
            http_response_code(405); // 405 Method Not Allowed
            echo json_encode(['error' => 'Method not allowed']);
        }

    } catch (\Exception $e) {
        // Gestion basique des erreurs pour ne pas planter silencieusement
        http_response_code(500); // 500 Internal Server Error
        // Logguez l'erreur si vous avez un logger configuré
        // error_log("Webhook Error: " . $e->getMessage()); 
        echo json_encode(['error' => 'Internal Server Error']);
    }
    ```

**Étape 1.2 : Implémenter le Contrôleur Webhook**

*   **Objectif :** Gérer la logique de vérification initiale et de traitement des notifications entrantes.
*   **Fichier :** `src/Infrastructure/Controller/WebhookController.php`

    ```php
    <?php
    // src/Infrastructure/Controller/WebhookController.php

    namespace App\Infrastructure\Controller;

    use App\Application\Service\WebhookVerificationService;
    use App\Application\Service\WhatsAppMessageService;
    use Psr\Log\LoggerInterface; // Optionnel: Si vous avez un logger PSR-3

    class WebhookController
    {
        private WebhookVerificationService $verificationService;
        private WhatsAppMessageService $messageService;
        private ?LoggerInterface $logger; // Optionnel

        // Injectez les services nécessaires via PHP-DI
        public function __construct(
            WebhookVerificationService $verificationService,
            WhatsAppMessageService $messageService,
            ?LoggerInterface $logger = null // Rend le logger optionnel
        ) {
            $this->verificationService = $verificationService;
            $this->messageService = $messageService;
            $this->logger = $logger;
        }

        /**
         * Gère la requête GET de Meta pour vérifier l'URL du Webhook.
         */
        public function verifyWebhook(): void
        {
            // Récupération des paramètres envoyés par Meta
            $challenge = $_GET['hub_challenge'] ?? null;
            $mode = $_GET['hub_mode'] ?? null;
            $token = $_GET['hub_verify_token'] ?? null;

            $this->logger?->info('Webhook verification attempt', ['mode' => $mode, 'token_provided' => !empty($token)]);

            // Vérification via le service dédié
            if ($challenge && $this->verificationService->verify($mode, $token)) {
                http_response_code(200); // OK
                echo $challenge; // Renvoyer le challenge tel quel
                $this->logger?->info('Webhook verification successful');
            } else {
                http_response_code(403); // Forbidden
                echo json_encode(['error' => 'Verification token mismatch or missing parameters']);
                $this->logger?->warning('Webhook verification failed', ['mode' => $mode, 'token_provided' => !empty($token)]);
            }
        }

        /**
         * Gère la requête POST de Meta contenant les notifications (messages entrants, etc.).
         */
        public function handleWebhook(): void
        {
            // 1. Récupérer le corps brut de la requête POST
            $payload = file_get_contents('php://input');
            if ($payload === false || empty($payload)) {
                $this->logger?->warning('Received empty webhook payload.');
                http_response_code(400); // Bad Request
                echo json_encode(['error' => 'Empty payload']);
                return;
            }

            // 2. Décoder le JSON
            $data = json_decode($payload, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger?->error('Failed to decode webhook JSON payload.', ['json_error' => json_last_error_msg()]);
                http_response_code(400); // Bad Request
                echo json_encode(['error' => 'Invalid JSON payload']);
                return;
            }

            // --- Sécurité: Validation de la Signature (IMPORTANT pour la Production) ---
            // TODO MVP: Implémenter la vérification de la signature X-Hub-Signature-256 ici avant de traiter les données en production.
            // Pour le MVP, nous faisons confiance à la source pour simplifier.
            // $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
            // if (! $this->verificationService->validateSignature($signature, $payload)) {
            //     $this->logger?->warning('Invalid webhook signature received.');
            //     http_response_code(401); // Unauthorized
            //     echo json_encode(['error' => 'Invalid signature']);
            //     return;
            // }
            // --- Fin TODO Sécurité ---

            // 3. Traiter les données via le service applicatif
            try {
                 $this->messageService->processWebhookData($data);
            } catch (\Exception $e) {
                // Logguer l'erreur de traitement mais répondre 200 OK quand même si possible
                $this->logger?->error('Error processing webhook data', ['exception' => $e]);
                // Selon la criticité, vous pourriez vouloir répondre 500 ici, mais attention Meta pourrait désactiver le webhook.
            }

            // 4. Confirmer la réception à Meta IMMEDIATEMENT (TRÈS IMPORTANT)
            // Quoi qu'il arrive (sauf erreur fatale avant), répondez 200 pour que Meta sache que vous avez reçu.
            http_response_code(200); // OK
            echo json_encode(['status' => 'success']); 
        }
    }
    ```

**Étape 1.3 : Implémenter les Services Applicatifs**

*   **Objectif :** Isoler la logique métier de la vérification et du traitement des messages.
*   **Fichier 1 :** `src/Application/Service/WebhookVerificationService.php`

    ```php
    <?php
    // src/Application/Service/WebhookVerificationService.php
    namespace App\Application\Service;

    class WebhookVerificationService
    {
        private string $expectedVerifyToken;

        // Injectez le token attendu depuis votre configuration DI
        public function __construct(string $webhookVerifyToken)
        {
            $this->expectedVerifyToken = $webhookVerifyToken;
        }

        /**
         * Vérifie si les paramètres de la requête GET correspondent à ceux attendus.
         */
        public function verify(?string $mode, ?string $token): bool
        {
            // Le mode doit être 'subscribe' et le token doit correspondre
            return $mode === 'subscribe' && $token === $this->expectedVerifyToken;
        }

        /**
         * TODO MVP: Valide la signature de la requête POST (pour la production).
         * Pour le MVP, cette méthode peut rester vide ou non implémentée.
         */
        public function validateSignature(string $signature, string $payload): bool
        {
            // $appSecret = // Votre secret d'application Meta (à injecter)
            // if (empty($signature) || strpos($signature, 'sha256=') !== 0) return false;
            // $providedHash = substr($signature, 7);
            // $calculatedHash = hash_hmac('sha256', $payload, $appSecret);
            // return hash_equals($calculatedHash, $providedHash);
            
            // Pour le MVP, on saute cette étape cruciale pour la production
             return true; 
        }
    }
    ```

*   **Fichier 2 :** `src/Application/Service/WhatsAppMessageService.php`

    ```php
    <?php
    // src/Application/Service/WhatsAppMessageService.php
    namespace App\Application\Service;

    use App\Domain\Entity\WhatsAppMessage;
    use App\Domain\Repository\WhatsAppMessageRepositoryInterface;
    use Psr\Log\LoggerInterface; // Optionnel

    class WhatsAppMessageService
    {
        private WhatsAppMessageRepositoryInterface $repository;
        private ?LoggerInterface $logger; // Optionnel

        public function __construct(
            WhatsAppMessageRepositoryInterface $repository,
            ?LoggerInterface $logger = null
        ) {
            $this->repository = $repository;
            $this->logger = $logger;
        }

        /**
         * Point d'entrée pour traiter le payload JSON complet reçu du webhook.
         */
        public function processWebhookData(array $data): void
        {
            // Log brut pour débogage facile (TRÈS UTILE !)
            $this->logRawData($data); 

            // Structure attendue: entry > changes > value > messages
            if (!isset($data['entry']) || !is_array($data['entry'])) {
                $this->logger?->warning('Webhook data missing "entry" array.');
                return;
            }

            foreach ($data['entry'] as $entry) {
                if (!isset($entry['changes']) || !is_array($entry['changes'])) continue;

                foreach ($entry['changes'] as $change) {
                    // On s'intéresse uniquement aux changements liés aux messages pour ce MVP
                    if (($change['field'] ?? null) !== 'messages') continue; 
                    
                    if (!isset($change['value']['messages']) || !is_array($change['value']['messages'])) continue;

                    // Traiter chaque message individuel dans la notification
                    foreach ($change['value']['messages'] as $messageData) {
                         $this->processSingleMessage($messageData, $change['value']['metadata'] ?? []);
                    }
                }
            }
        }

        /**
         * Traite les données d'un seul message WhatsApp reçu.
         */
        private function processSingleMessage(array $messageData, array $metadata): void
        {
             // Vérifier si on a déjà traité ce message_id pour éviter les doublons (si Meta renvoie plusieurs fois)
             if (isset($messageData['id']) && $this->repository->findByMessageId($messageData['id'])) {
                 $this->logger?->info('Skipping already processed message', ['message_id' => $messageData['id']]);
                 return;
             }

            // Créer une entité de message à partir des données brutes
            $message = $this->createMessageFromData($messageData, $metadata);
            if (!$message) return; // Ne pas traiter si les données essentielles manquent

            // Persister le message dans la base de données (SQLite)
            try {
                $this->repository->save($message);
                $this->logger?->info('WhatsApp message saved', ['message_id' => $message->getMessageId(), 'from' => $message->getFrom()]);
            } catch (\Exception $e) {
                 $this->logger?->error('Failed to save WhatsApp message', ['message_id' => $messageData['id'] ?? 'N/A', 'exception' => $e]);
            }

            // TODO MVP+: Ici, vous pourriez déclencher d'autres logiques (notifications internes, IA, etc.)
        }

        /**
         * Crée une instance de WhatsAppMessage à partir des données brutes.
         * Retourne null si les données essentielles manquent.
         */
        private function createMessageFromData(array $messageData, array $metadata): ?WhatsAppMessage
        {
            // Données minimales requises
            if (empty($messageData['id']) || empty($messageData['from']) || empty($messageData['timestamp']) || empty($messageData['type'])) {
                $this->logger?->warning('Skipping message due to missing essential data', ['data' => $messageData]);
                return null;
            }

            $message = new WhatsAppMessage();
            $message->setMessageId($messageData['id']);
            $message->setFrom($messageData['from']);
            $message->setTimestamp((int)$messageData['timestamp']);
            $message->setType($messageData['type']);
            
            // Stocker le JSON brut du message pour référence future
            $message->setRawData(json_encode($messageData)); 

            // Traiter le contenu spécifique au type de message (Focus sur texte pour MVP)
            switch ($messageData['type']) {
                case 'text':
                    $message->setContent($messageData['text']['body'] ?? '');
                    break;
                case 'image':
                    $message->setContent('[Image reçue]'); // Placeholder
                    // $message->setMediaId($messageData['image']['id'] ?? null); // Pourrait être ajouté si besoin plus tard
                    break;
                case 'audio':
                    $message->setContent('[Audio reçu]'); // Placeholder
                    break;
                // Ajoutez d'autres types si nécessaire (document, location, contacts...) avec des placeholders
                default:
                    $message->setContent('[' . ucfirst($messageData['type']) . ' reçu]'); // Placeholder générique
            }

            return $message;
        }

        /**
         * Fonction utilitaire pour logguer le payload brut dans un fichier.
         * Utile pour comprendre la structure des données envoyées par Meta.
         */
        private function logRawData(array $data): void
        {
            // Assurez-vous que le dossier 'logs' existe et est accessible en écriture
            $logDir = __DIR__ . '/../../../var/logs'; // Ajustez le chemin si nécessaire
            if (!is_dir($logDir)) {
                mkdir($logDir, 0775, true);
            }
            $logFile = $logDir . '/whatsapp_webhook_' . date('Y-m-d') . '.log';
            $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            
            // Ajouter la date/heure au log pour contexte
            $logEntry = "[" . date('Y-m-d H:i:s') . "] Received Webhook Payload:\n" . $jsonData . "\n---\n";
            
            file_put_contents($logFile, $logEntry, FILE_APPEND);
        }
    }
    ```

**Étape 1.4 : Définir le Domaine (Entité et Interface Repository)**

*   **Objectif :** Représenter un message WhatsApp et définir comment interagir avec la base de données.
*   **Fichier 1 :** `src/Domain/Entity/WhatsAppMessage.php`

    ```php
    <?php
    // src/Domain/Entity/WhatsAppMessage.php
    namespace App\Domain\Entity;

    class WhatsAppMessage
    {
        private ?int $id = null;        // Clé primaire SQLite (auto-increment)
        private string $messageId;     // ID unique du message WhatsApp (ex: wamid.XXX)
        private string $from;          // Numéro de l'expéditeur (ex: 22507...)
        private int $timestamp;       // Timestamp UNIX de réception du message
        private string $type;          // Type de message (text, image, audio, etc.)
        private ?string $content = null; // Contenu (texte du message, ou placeholder pour média)
        private ?string $rawData = null; // Stockage du JSON brut du message (optionnel mais utile)

        // --- Getters ---
        public function getId(): ?int { return $this->id; }
        public function getMessageId(): string { return $this->messageId; }
        public function getFrom(): string { return $this->from; }
        public function getTimestamp(): int { return $this->timestamp; }
        public function getType(): string { return $this->type; }
        public function getContent(): ?string { return $this->content; }
        public function getRawData(): ?string { return $this->rawData; }

        // --- Setters ---
        // Note: setId est souvent géré par le repository/ORM après insertion
        public function setId(?int $id): void { $this->id = $id; } 
        public function setMessageId(string $messageId): void { $this->messageId = $messageId; }
        public function setFrom(string $from): void { $this->from = $from; }
        public function setTimestamp(int $timestamp): void { $this->timestamp = $timestamp; }
        public function setType(string $type): void { $this->type = $type; }
        public function setContent(?string $content): void { $this->content = $content; }
        public function setRawData(?string $rawData): void { $this->rawData = $rawData; }
    }

    ```

*   **Fichier 2 :** `src/Domain/Repository/WhatsAppMessageRepositoryInterface.php`

    ```php
    <?php
    // src/Domain/Repository/WhatsAppMessageRepositoryInterface.php
    namespace App\Domain\Repository;

    use App\Domain\Entity\WhatsAppMessage;

    interface WhatsAppMessageRepositoryInterface
    {
        /**
         * Sauvegarde un nouveau message ou met à jour un message existant.
         */
        public function save(WhatsAppMessage $message): void;

        /**
         * Trouve un message par son ID unique WhatsApp (messageId).
         * Utile pour éviter les doublons.
         * Retourne le message trouvé ou null.
         */
        public function findByMessageId(string $messageId): ?WhatsAppMessage;

        /**
         * Récupère les N messages les plus récents.
         * @return WhatsAppMessage[]
         */
        public function findRecent(int $limit = 50): array;
        
        // TODO MVP+: Ajouter d'autres méthodes si besoin (findAll, findBySender, etc.)
    }
    ```

**Étape 1.5 : Implémenter le Repository pour SQLite**

*   **Objectif :** Écrire les requêtes SQL pour interagir avec la table SQLite.
*   **Fichier :** `src/Infrastructure/Repository/SqliteWhatsAppMessageRepository.php`

    ```php
    <?php
    // src/Infrastructure/Repository/SqliteWhatsAppMessageRepository.php
    namespace App\Infrastructure\Repository;

    use App\Domain\Entity\WhatsAppMessage;
    use App\Domain\Repository\WhatsAppMessageRepositoryInterface;
    use Doctrine\DBAL\Connection;
    use Doctrine\DBAL\ParameterType;
    use Psr\Log\LoggerInterface; // Optionnel

    class SqliteWhatsAppMessageRepository implements WhatsAppMessageRepositoryInterface
    {
        private Connection $connection; // Injectée via PHP-DI
        private ?LoggerInterface $logger; // Optionnel

        private const TABLE_NAME = 'whatsapp_messages';

        public function __construct(Connection $connection, ?LoggerInterface $logger = null)
        {
            $this->connection = $connection;
            $this->logger = $logger;
        }

        public function save(WhatsAppMessage $message): void
        {
            // Préparation des données pour l'insertion
            $data = [
                'message_id' => $message->getMessageId(),
                'sender' => $message->getFrom(),
                'timestamp' => $message->getTimestamp(),
                'type' => $message->getType(),
                'content' => $message->getContent(),
                'raw_data' => $message->getRawData(),
                'created_at' => time() // Ajoutons un timestamp de création interne
            ];

            try {
                // Utilisation de insert() de DBAL pour plus de simplicité
                $this->connection->insert(self::TABLE_NAME, $data);
                
                // Récupérer l'ID auto-incrémenté et le mettre dans l'entité
                $id = $this->connection->lastInsertId();
                if ($id) {
                    $message->setId((int) $id);
                }
            } catch (\Exception $e) {
                 $this->logger?->error('DBAL Error saving message', ['exception' => $e, 'data' => $data]);
                 // Relancer l'exception pour que l'appelant soit informé
                 throw new \RuntimeException("Failed to save WhatsApp message: " . $e->getMessage(), 0, $e);
            }
        }

        public function findByMessageId(string $messageId): ?WhatsAppMessage
        {
            $sql = "SELECT * FROM " . self::TABLE_NAME . " WHERE message_id = :message_id LIMIT 1";
            try {
                $stmt = $this->connection->prepare($sql);
                $stmt->bindValue('message_id', $messageId, ParameterType::STRING);
                $result = $stmt->executeQuery();
                $row = $result->fetchAssociative();

                return $row ? $this->hydrate($row) : null;
            } catch (\Exception $e) {
                $this->logger?->error('DBAL Error finding message by message_id', ['exception' => $e, 'message_id' => $messageId]);
                return null; // Retourner null en cas d'erreur DB
            }
        }

        public function findRecent(int $limit = 50): array
        {
            $sql = "SELECT * FROM " . self::TABLE_NAME . " ORDER BY timestamp DESC LIMIT :limit";
            try {
                $stmt = $this->connection->prepare($sql);
                $stmt->bindValue('limit', $limit, ParameterType::INTEGER);
                $results = $stmt->executeQuery();
                
                $messages = [];
                while ($row = $results->fetchAssociative()) {
                    $messages[] = $this->hydrate($row);
                }
                return $messages;
            } catch (\Exception $e) {
                $this->logger?->error('DBAL Error finding recent messages', ['exception' => $e, 'limit' => $limit]);
                return []; // Retourner un tableau vide en cas d'erreur DB
            }
        }

        /**
         * Fonction privée pour convertir une ligne de la DB en objet WhatsAppMessage.
         */
        private function hydrate(array $row): WhatsAppMessage
        {
            $message = new WhatsAppMessage();
            $message->setId((int)$row['id']);
            $message->setMessageId($row['message_id']);
            $message->setFrom($row['sender']);
            $message->setTimestamp((int)$row['timestamp']);
            $message->setType($row['type']);
            $message->setContent($row['content']);
            $message->setRawData($row['raw_data']);
            // Note: created_at n'est pas dans l'entité pour l'instant, mais est dans la DB

            return $message;
        }
    }

    ```

**Étape 1.6 : Créer/Mettre à Jour le Schéma SQLite**

*   **Objectif :** S'assurer que la table `whatsapp_messages` existe dans votre base SQLite.
*   **Action :** Créez ou adaptez un script PHP pour exécuter le SQL de création de table.
*   **Fichier :** `bin/setup-database.php` (ou un nom similaire)

    ```php
    <?php
    // bin/setup-database.php
    require_once __DIR__ . '/../vendor/autoload.php';

    use Doctrine\DBAL\DriverManager;
    use Doctrine\DBAL\Schema\Schema;
    use Doctrine\DBAL\Connection;

    // Récupérer le chemin de la DB (idéalement depuis config ou env)
    $dbPath = __DIR__ . '/../var/data/app.sqlite'; // Assurez-vous que le dossier var/data existe

    try {
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'path' => $dbPath]);
        $schemaManager = $connection->createSchemaManager(); // Utilisation correcte pour obtenir le SchemaManager

        $schema = new Schema();
        $tableName = 'whatsapp_messages';

        // Vérifier si la table existe déjà pour éviter les erreurs
        if (!$schemaManager->tablesExist([$tableName])) {
            echo "Table '$tableName' does not exist. Creating...\n";
            
            // Définition de la table
            $table = $schema->createTable($tableName);
            $table->addColumn('id', 'integer', ['autoincrement' => true]);
            $table->addColumn('message_id', 'string', ['length' => 255]); // ID WhatsApp
            $table->addColumn('sender', 'string', ['length' => 50]);      // Numéro expéditeur
            $table->addColumn('timestamp', 'integer');                   // Timestamp UNIX
            $table->addColumn('type', 'string', ['length' => 50]);        // Type de message
            $table->addColumn('content', 'text', ['notnull' => false]);   // Contenu texte ou placeholder
            $table->addColumn('raw_data', 'text', ['notnull' => false]);  // JSON brut du message
            $table->addColumn('created_at', 'integer');                  // Timestamp de création interne
            
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['message_id'], 'idx_unique_message_id'); // Assurer unicité des messages WA
            $table->addIndex(['timestamp'], 'idx_timestamp');              // Pour tri rapide par date
            $table->addIndex(['sender'], 'idx_sender');                    // Pour recherche par expéditeur

            // Exécuter le SQL pour créer la table
            $queries = $schema->toSql($connection->getDatabasePlatform());
            foreach ($queries as $query) {
                $connection->executeQuery($query);
            }
            echo "Table '$tableName' created successfully.\n";

        } else {
            echo "Table '$tableName' already exists. No action taken.\n";
            // TODO MVP+: Ici, on pourrait ajouter une logique de migration si le schéma change.
        }

    } catch (\Exception $e) {
        echo "Error setting up database: " . $e->getMessage() . "\n";
        exit(1); // Sortir avec un code d'erreur
    }

    echo "Database setup checked/completed.\n";
    exit(0); // Sortir avec succès
    ```
*   **Exécution :** Lancez `php bin/setup-database.php` depuis votre terminal.

**Étape 1.7 : Configurer l'Injection de Dépendances (PHP-DI)**

*   **Objectif :** Dire à PHP-DI comment construire les objets nécessaires.
*   **Fichier :** `config/di.php` (ou votre fichier de configuration principal)

    ```php
    <?php
    // config/di.php

    use App\Application\Service\WebhookVerificationService;
    use App\Application\Service\WhatsAppMessageService;
    use App\Domain\Repository\WhatsAppMessageRepositoryInterface;
    use App\Infrastructure\Controller\WebhookController;
    use App\Infrastructure\Repository\SqliteWhatsAppMessageRepository;
    use Doctrine\DBAL\Connection;
    use Doctrine\DBAL\DriverManager;
    use Psr\Log\LoggerInterface; // Si vous utilisez un logger
    // use Monolog\Logger; // Exemple si vous utilisez Monolog
    // use Monolog\Handler\StreamHandler; // Exemple
    use function DI\create;
    use function DI\get;
    use function DI\factory; // Utilisez factory pour la connexion DB

    // Charger les variables d'environnement si vous utilisez .env
    // $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    // $dotenv->safeLoad();

    return [
        // --- Configuration Spécifique WhatsApp ---
        'whatsapp.verify_token' => $_ENV['WHATSAPP_VERIFY_TOKEN'] ?? 'VOTRE_TOKEN_SECRET_ICI', // **IMPORTANT**: Ne pas hardcoder en prod, utiliser variable d'env !
        
        // --- Configuration Base de Données ---
        'db.path' => __DIR__ . '/../var/data/app.sqlite', // Chemin vers votre fichier SQLite

        // --- Définitions des Services ---

        // Logger (Optionnel, exemple avec Monolog)
        // LoggerInterface::class => function() {
        //     $logger = new Logger('whatsapp_mvp');
        //     $logFile = __DIR__ . '/../var/logs/app.log';
        //     $logger->pushHandler(new StreamHandler($logFile, Logger::DEBUG));
        //     return $logger;
        // },

        // Connexion Doctrine DBAL (Utiliser factory pour la création)
        Connection::class => factory(function ($c) {
            return DriverManager::getConnection([
                'driver' => 'pdo_sqlite',
                'path' => $c->get('db.path'),
            ]);
        })->lazy(), // lazy() peut aider pour les performances si non utilisé immédiatement

        // Service de Vérification Webhook
        WebhookVerificationService::class => create()
            ->constructor(get('whatsapp.verify_token')), // Injecte le token défini plus haut

        // Repository SQLite
        WhatsAppMessageRepositoryInterface::class => create(SqliteWhatsAppMessageRepository::class)
            ->constructor(
                get(Connection::class),
                // get(LoggerInterface::class) // Décommentez si vous injectez un logger
            ),

        // Service de Traitement des Messages
        WhatsAppMessageService::class => create()
            ->constructor(
                get(WhatsAppMessageRepositoryInterface::class),
                // get(LoggerInterface::class) // Décommentez si vous injectez un logger
            ),

        // Contrôleur Webhook
        WebhookController::class => create()
            ->constructor(
                get(WebhookVerificationService::class),
                get(WhatsAppMessageService::class),
                // get(LoggerInterface::class) // Décommentez si vous injectez un logger
            ),
            
        // Ajoutez ici d'autres dépendances nécessaires à votre application...
    ];
    ```
*   **Important :** Remplacez `'VOTRE_TOKEN_SECRET_ICI'` par un token réellement secret et sécurisé. Utilisez idéalement une variable d'environnement (`$_ENV['WHATSAPP_VERIFY_TOKEN']`) pour cela.

---

**Partie 2 : Configuration Locale et Meta**

**Étape 2.1 : Exposer votre Serveur Local avec `ngrok`**

*   **Objectif :** Donner à Meta une URL publique (HTTPS) qui pointe vers votre machine de développement.
*   **Actions :**
    1.  Si ce n'est pas déjà fait, téléchargez et installez `ngrok` ([ngrok.com](https://ngrok.com/download)).
    2.  Lancez votre serveur PHP local (ex: `php -S localhost:8080 -t public`). Notez le port (ici `8080`).
    3.  Ouvrez un *autre* terminal et lancez ngrok :
        ```bash
        ngrok http 8080 
        ```
        (Remplacez `8080` par le port de votre serveur PHP).
    4.  `ngrok` affichera une ligne "Forwarding" avec une URL HTTPS (ex: `https://<ID_ALEATOIRE>.ngrok-free.app`). **Copiez cette URL HTTPS.**

**Étape 2.2 : Configurer le Webhook dans le Tableau de Bord Meta App**

*   **Objectif :** Dire à Meta où envoyer les notifications et quels événements vous intéressent.
*   **Actions :**
    1.  Allez à votre application sur [Meta for Developers](https://developers.facebook.com/apps/).
    2.  Naviguez vers "WhatsApp" > "Configuration" dans le menu de gauche.
    3.  Trouvez la section "Webhooks". Cliquez sur "Modifier".
    4.  **URL de rappel :** Collez l'URL HTTPS fournie par `ngrok`, suivie du chemin vers votre point d'entrée. Exemple : `https://<ID_ALEATOIRE>.ngrok-free.app/webhook.php`
    5.  **Token de vérification :** Entrez *exactement* le même token que vous avez mis dans `config/di.php` (la valeur de `whatsapp.verify_token`).
    6.  Cliquez sur "**Vérifier et enregistrer**".
        *   Meta va envoyer une requête `GET` à votre `webhook.php`.
        *   Vérifiez les logs de votre serveur PHP et de `ngrok`. Si tout est correct, le tableau de bord Meta indiquera que la vérification a réussi.
    7.  Une fois vérifié, cliquez sur "**Gérer**" à côté de "Champs Webhook".
    8.  Abonnez-vous **uniquement** à l'événement `messages`. C'est suffisant pour recevoir les messages entrants et les statuts de base pour le MVP.
    9.  Cliquez sur "Terminé".

---

**Partie 3 : Frontend (Vue.js/Quasar) - Affichage Lecture Seule**

**Étape 3.1 : Exposer les Messages via GraphQL**

*   **Objectif :** Créer une requête GraphQL pour que le frontend puisse récupérer les messages stockés.
*   **Fichier :** `src/Infrastructure/GraphQL/WhatsAppQueries.php` (ou votre fichier de requêtes GraphQL existant)

    ```php
    <?php
    // src/Infrastructure/GraphQL/WhatsAppQueries.php
    namespace App\Infrastructure\GraphQL;

    use App\Domain\Entity\WhatsAppMessage; // Assurez-vous que GraphQLite peut "voir" cette entité
    use App\Domain\Repository\WhatsAppMessageRepositoryInterface;
    use TheCodingMachine\GraphQLite\Annotations\Query; // Ou l'annotation équivalente que vous utilisez
    use TheCodingMachine\GraphQLite\Annotations\UseInputType; // Si vous utilisez des DTOs pour les arguments

    class WhatsAppQueries
    {
        private WhatsAppMessageRepositoryInterface $repository;

        public function __construct(WhatsAppMessageRepositoryInterface $repository)
        {
            $this->repository = $repository;
        }

        /**
         * Récupère les messages WhatsApp les plus récents.
         * 
         * @Query() 
         * @param int $limit Le nombre maximum de messages à retourner (défaut 50).
         * @return WhatsAppMessage[] Une liste de messages WhatsApp.
         */
        public function getWhatsAppMessages(int $limit = 50): array
        {
            // Appelle directement la méthode du repository
            return $this->repository->findRecent($limit); 
            // GraphQLite (ou votre lib) devrait pouvoir sérialiser les objets WhatsAppMessage
            // Assurez-vous que les propriétés ont des getters ou sont publiques si nécessaire.
        }
        
        // TODO MVP+: Ajouter des mutations pour envoyer des messages plus tard.
        // /**
        //  * @Mutation()
        //  * @param string $to
        //  * @param string $message
        //  * @return bool // ou un type plus complexe
        //  */
        // public function sendWhatsAppTextMessage(string $to, string $message): bool 
        // { 
        //     // Logique d'envoi via MetaWhatsAppClient...
        //     return true; 
        // }
    }
    ```
*   **Intégration GraphQLite :** Assurez-vous que cette classe `WhatsAppQueries` est scannée par GraphQLite (via configuration ou attributs) et que l'entité `WhatsAppMessage` est également exposée si nécessaire (souvent automatique si les getters sont présents).

**Étape 3.2 : Afficher les Messages dans Quasar**

*   **Objectif :** Utiliser votre client GraphQL existant dans Vue pour appeler la requête `getWhatsAppMessages` et afficher les résultats dans une table ou une liste Quasar.
*   **Fichier :** Un composant Vue existant ou un nouveau (ex: `src/pages/WhatsAppChatPage.vue`)

    ```vue
    <template>
      <q-page padding>
        <div class="q-mb-md">
          <h4 class="text-h4">Messages WhatsApp Reçus</h4>
          <q-btn flat round icon="refresh" @click="fetchMessages" :loading="loading" aria-label="Rafraîchir"/>
        </div>

        <q-table
          title="Derniers Messages"
          :rows="messages"
          :columns="columns"
          row-key="messageId" 
          :loading="loading"
          :rows-per-page-options="[10, 25, 50]"
          v-model:pagination="pagination"
        >
          <template v-slot:body-cell-timestamp="props">
            <q-td :props="props">
              {{ formatTimestamp(props.value) }}
            </q-td>
          </template>

          <template v-slot:no-data>
            <div class="full-width row flex-center text-grey q-gutter-sm q-pa-md">
              <q-icon size="2em" name="chat_bubble_outline" />
              <span>
                Aucun message reçu pour le moment.
              </span>
            </div>
          </template>

          <template v-slot:loading>
             <q-inner-loading showing color="primary" />
          </template>
        </q-table>

         <!-- TODO MVP+: Ajouter ici une zone de saisie et un bouton pour envoyer des réponses -->

      </q-page>
    </template>

    <script setup lang="ts">
    import { ref, onMounted, computed } from 'vue';
    import { useQuasar, QTableProps } from 'quasar';
    // Importez votre client GraphQL (Apollo, urql, etc.)
    // import { useQuery } from '@vue/apollo-composable'; // Exemple avec Apollo
    // import gql from 'graphql-tag'; // Si nécessaire

    // --- State ---
    const $q = useQuasar();
    const messages = ref<any[]>([]); // Utilisez un type plus précis si possible (ex: WhatsAppMessage[])
    const loading = ref(false);
    const pagination = ref({
      sortBy: 'timestamp',
      descending: true,
      page: 1,
      rowsPerPage: 10
    });

    // --- GraphQL Query (Adaptez à votre client) ---
    // Exemple conceptuel - Remplacez par votre implémentation réelle
    const GET_MESSAGES_QUERY = ` 
      query GetWhatsAppMessages($limit: Int!) {
        getWhatsAppMessages(limit: $limit) {
          id
          messageId
          from
          timestamp
          type
          content
        }
      }
    `;

    // --- Colonnes pour QTable ---
    const columns: QTableProps['columns'] = [
      { name: 'from', label: 'Expéditeur', align: 'left', field: 'from', sortable: true },
      { name: 'content', label: 'Contenu', align: 'left', field: 'content', style: 'white-space: pre-wrap; min-width: 200px;' },
      { name: 'type', label: 'Type', align: 'center', field: 'type', sortable: true },
      { name: 'timestamp', label: 'Date', align: 'right', field: 'timestamp', sortable: true },
      // { name: 'messageId', label: 'ID Message', align: 'left', field: 'messageId' }, // Optionnel
    ];

    // --- Fonctions ---
    const formatTimestamp = (timestamp: number): string => {
      if (!timestamp) return '-';
      // Le timestamp de WhatsApp est en secondes
      return new Date(timestamp * 1000).toLocaleString(); 
    };

    const fetchMessages = async () => {
      loading.value = true;
      messages.value = []; // Vider avant de recharger
      try {
        // --- Exécutez votre requête GraphQL ici ---
        // Exemple avec un client fictif:
        // const { data } = await yourGraphQLClient.query({ 
        //   query: GET_MESSAGES_QUERY, 
        //   variables: { limit: 50 }, // Récupérer les 50 derniers par exemple
        //   fetchPolicy: 'network-only' // Pour forcer le rafraîchissement
        // });
        // messages.value = data?.getWhatsAppMessages || [];

        // --- Mock Data (à remplacer par l'appel réel) ---
         await new Promise(resolve => setTimeout(resolve, 1000)); // Simuler un délai réseau
         messages.value = [
           { id: 1, messageId: 'wamid.123', from: '2250102030405', timestamp: Math.floor(Date.now() / 1000) - 3600, type: 'text', content: 'Ceci est un message de test reçu.' },
           { id: 2, messageId: 'wamid.456', from: '2250708091011', timestamp: Math.floor(Date.now() / 1000) - 7200, type: 'image', content: '[Image reçue]' },
         ];
        // --- Fin Mock Data ---

      } catch (error) {
        console.error("Erreur lors de la récupération des messages WhatsApp:", error);
        $q.notify({
          type: 'negative',
          message: 'Impossible de charger les messages WhatsApp.',
          icon: 'warning'
        });
      } finally {
        loading.value = false;
      }
    };

    // --- Lifecycle Hooks ---
    onMounted(() => {
      fetchMessages();
    });

    </script>

    <style scoped>
    /* Ajoutez du style si nécessaire */
    </style>
    ```

---

**Partie 4 : Tests Manuels du MVP**

1.  **Lancez tout :**
    *   Votre serveur PHP local (`php -S ...`).
    *   Votre serveur `ngrok http ...`.
    *   Votre serveur de développement frontend (`quasar dev` ou `npm run dev`).
2.  **Vérifiez la config Meta :** Assurez-vous que le webhook est bien configuré avec l'URL ngrok et qu'il est marqué comme "Vérifié". L'événement `messages` doit être souscrit.
3.  **Envoyez un message Test :** Utilisez un téléphone personnel avec WhatsApp et envoyez un message texte simple à votre numéro WhatsApp Business configuré.
4.  **Observez les réactions :**
    *   **Terminal ngrok :** Vous devriez voir une ligne `POST /webhook.php 200 OK`.
    *   **Logs de votre serveur PHP / Fichier log :**
        *   Vous devriez voir le log de la tentative de vérification (`GET`) si Meta revérifie.
        *   Vous devriez voir le log du payload JSON brut reçu (`POST`).
        *   Vous devriez voir le log indiquant que le message a été sauvegardé.
    *   **Base de données SQLite :** Utilisez un outil comme DB Browser for SQLite pour vérifier qu'une nouvelle ligne a été ajoutée dans la table `whatsapp_messages`.
    *   **Interface Quasar :** Rafraîchissez la page (ou cliquez sur le bouton "Rafraîchir"). Le nouveau message devrait apparaître dans la table/liste.

---

**Récapitulatif des Exclusions Volontaires du MVP :**

*   Envoi de messages depuis l'application.
*   Validation de la signature du webhook (sécurité production).
*   Gestion avancée des médias (téléchargement, affichage).
*   Gestion des statuts de message (délivré, lu).
*   Traitement asynchrone / Files d'attente.
*   Cache.
*   Base de données autre que SQLite.
*   Déploiement en production.

---

**Prochaines Étapes (Après le MVP) :**

1.  Implémenter l'envoi de réponses texte simple (via une mutation GraphQL).
2.  Ajouter la validation de la signature du webhook.
3.  Préparer le déploiement en production (serveur HTTPS, variables d'env, SGBD plus robuste si nécessaire).
4.  Gérer plus de types de messages (médias).

Ce plan détaillé devrait vous guider pas à pas pour intégrer la réception des messages WhatsApp dans votre application de manière pragmatique et conforme à l'esprit MVP. Bonne implémentation !