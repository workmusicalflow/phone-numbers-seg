<?php
/**
 * Script pour tester l'envoi de messages texte après réception d'un message
 * (dans la fenêtre de 24 heures)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entities\User;
use App\Entities\WhatsApp\WhatsAppMessageHistory;
use App\Services\WhatsApp\WhatsAppApiClient;
use App\Services\WhatsApp\WhatsAppService;
use App\Services\PhoneNumberNormalizerService;
use App\Repositories\Doctrine\WhatsApp\WhatsAppMessageHistoryRepository;
use Psr\Log\NullLogger;

// Configuration
$phoneNumber = '+2250777104936'; // Votre numéro WhatsApp
$message = "Merci pour votre message ! Ceci est une réponse automatique envoyée dans la fenêtre de 24 heures.";

echo "=== Test d'envoi de message texte WhatsApp ===\n\n";

// Charger la configuration
$config = require __DIR__ . '/../src/config/whatsapp.php';

// Créer un logger minimal
$logger = new NullLogger();

// Obtenir l'EntityManager via le bootstrap
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

// Charger l'utilisateur admin
$userRepository = $entityManager->getRepository(User::class);
$user = $userRepository->find(1); // ID admin = 1

if (!$user) {
    echo "Erreur : utilisateur admin non trouvé\n";
    exit(1);
}

echo "Utilisateur : " . $user->getUsername() . "\n";

// Créer le service de normalisation
$normalizer = new PhoneNumberNormalizerService();

// Créer le client API
$apiClient = new WhatsAppApiClient($logger, $config);

// Créer le repository des messages
$messageRepository = new WhatsAppMessageHistoryRepository($entityManager, WhatsAppMessageHistory::class);

// Créer un repository de templates avec une implémentation simple
$templateRepository = new class($entityManager) implements \App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateRepositoryInterface {
    private $em;
    public function __construct($em) { $this->em = $em; }
    public function find($id) { return null; }
    public function findAll() { return []; }
    public function save($entity) { $this->em->persist($entity); $this->em->flush(); }
    public function remove($entity) { $this->em->remove($entity); $this->em->flush(); }
    public function findByNameAndLanguage(string $name, string $language) { return null; }
    public function findByStatus(string $status) { return []; }
    public function findUserTemplates(User $user) { return []; }
    public function markAsActive(string $name, string $language): void {}
    public function clearCache(): void {}
};

// Créer le service WhatsApp
$whatsappService = new WhatsAppService(
    $apiClient,
    $messageRepository,
    $templateRepository,
    $logger,
    $config
);

echo "Envoi du message à : $phoneNumber\n";
echo "Message : $message\n\n";

try {
    $result = $whatsappService->sendMessage(
        $user,
        $phoneNumber,
        'text',
        $message
    );
    
    echo "✓ Message envoyé avec succès !\n";
    echo "ID WhatsApp : " . $result->getWabaMessageId() . "\n";
    echo "ID en base : " . $result->getId() . "\n";
    echo "Statut : " . $result->getStatus() . "\n";
    
} catch (Exception $e) {
    echo "✗ Erreur : " . $e->getMessage() . "\n";
    
    // Afficher plus de détails si c'est une erreur API
    if (strpos($e->getMessage(), 'API error') !== false || strpos($e->getMessage(), '24-hour') !== false) {
        echo "\nDétails de l'erreur :\n";
        echo "Cette erreur peut survenir si :\n";
        echo "1. Aucune conversation n'a été initiée par l'utilisateur\n";
        echo "2. La fenêtre de 24 heures est expirée\n";
        echo "3. Le numéro n'est pas enregistré sur WhatsApp\n";
        echo "\nSolution :\n";
        echo "1. Envoyez d'abord un message depuis WhatsApp vers le numéro de votre Business\n";
        echo "2. Attendez que le webhook reçoive le message\n";
        echo "3. Relancez ce script pour répondre\n";
    }
    
    // Afficher la trace complète pour debug
    echo "\n--- Trace complète ---\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=== Fin du test ===\n";