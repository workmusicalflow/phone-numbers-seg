<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entities\SMSOrder;
use App\Repositories\Doctrine\SMSOrderRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// Get the entity manager directly from bootstrap file
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

// Création du repository
$smsOrderRepository = new SMSOrderRepository($entityManager);

// Check if the SMSOrder table exists and create it if needed
try {
    // Try to get the metadata for the SMSOrder entity
    $smsOrderMetadata = $entityManager->getClassMetadata(SMSOrder::class);

    // Create a schema tool
    $schemaTool = new SchemaTool($entityManager);

    // Check if the table exists by trying to find one record
    $testQuery = $entityManager->createQuery('SELECT COUNT(o) FROM ' . SMSOrder::class . ' o');
    $testQuery->getResult();

    echo "SMSOrder table exists.\n\n";
} catch (\Exception $e) {
    // If there's an error, the table might not exist
    echo "SMSOrder table doesn't exist. Creating it...\n";

    try {
        // Create the schema for SMSOrder entity
        $schemaTool->createSchema([$smsOrderMetadata]);
        echo "SMSOrder table created successfully.\n\n";
    } catch (\Exception $e) {
        // If there's an error creating the schema, try to update it
        echo "Error creating schema: " . $e->getMessage() . "\n";
        echo "Trying to update schema instead...\n";
        $schemaTool->updateSchema([$smsOrderMetadata]);
        echo "SMSOrder table updated successfully.\n\n";
    }
}

// Test de création d'une commande SMS
echo "Test de création d'une commande SMS...\n";
$userId = 1; // ID d'un utilisateur existant
$quantity = 100; // Nombre de crédits SMS
$status = SMSOrder::STATUS_PENDING;

try {
    $smsOrder = $smsOrderRepository->create($userId, $quantity, $status);
    echo "Commande SMS créée avec succès. ID: " . $smsOrder->getId() . "\n";
    echo "Utilisateur: " . $smsOrder->getUserId() . "\n";
    echo "Quantité: " . $smsOrder->getQuantity() . "\n";
    echo "Statut: " . $smsOrder->getStatus() . "\n";
    echo "Date de création: " . $smsOrder->getCreatedAt()->format('Y-m-d H:i:s') . "\n";
    echo "Date de mise à jour: " . ($smsOrder->getUpdatedAt() ? $smsOrder->getUpdatedAt()->format('Y-m-d H:i:s') : 'NULL') . "\n";
    echo "\n";
} catch (Exception $e) {
    echo "Erreur lors de la création de la commande SMS: " . $e->getMessage() . "\n";
    exit(1);
}

// Test de récupération d'une commande SMS par ID
echo "Test de récupération d'une commande SMS par ID...\n";
$smsOrderId = $smsOrder->getId();
$retrievedSmsOrder = $smsOrderRepository->findById($smsOrderId);

if ($retrievedSmsOrder) {
    echo "Commande SMS récupérée avec succès. ID: " . $retrievedSmsOrder->getId() . "\n";
    echo "Utilisateur: " . $retrievedSmsOrder->getUserId() . "\n";
    echo "Quantité: " . $retrievedSmsOrder->getQuantity() . "\n";
    echo "Statut: " . $retrievedSmsOrder->getStatus() . "\n";
    echo "Date de création: " . $retrievedSmsOrder->getCreatedAt()->format('Y-m-d H:i:s') . "\n";
    echo "Date de mise à jour: " . ($retrievedSmsOrder->getUpdatedAt() ? $retrievedSmsOrder->getUpdatedAt()->format('Y-m-d H:i:s') : 'NULL') . "\n";
    echo "\n";
} else {
    echo "Erreur: Commande SMS non trouvée avec l'ID $smsOrderId\n";
    exit(1);
}

// Test de mise à jour du statut d'une commande SMS
echo "Test de mise à jour du statut d'une commande SMS...\n";
$newStatus = SMSOrder::STATUS_COMPLETED;

try {
    $result = $smsOrderRepository->updateStatus($smsOrderId, $newStatus);

    if ($result) {
        echo "Statut de la commande SMS mis à jour avec succès.\n";

        // Vérification de la mise à jour
        $updatedSmsOrder = $smsOrderRepository->findById($smsOrderId);
        echo "Nouveau statut: " . $updatedSmsOrder->getStatus() . "\n";
        echo "Date de mise à jour: " . ($updatedSmsOrder->getUpdatedAt() ? $updatedSmsOrder->getUpdatedAt()->format('Y-m-d H:i:s') : 'NULL') . "\n";
        echo "\n";
    } else {
        echo "Erreur: Impossible de mettre à jour le statut de la commande SMS\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "Erreur lors de la mise à jour du statut de la commande SMS: " . $e->getMessage() . "\n";
    exit(1);
}

// Test de récupération des commandes SMS par utilisateur
echo "Test de récupération des commandes SMS par utilisateur...\n";
$userOrders = $smsOrderRepository->findByUserId($userId);
echo "Nombre de commandes SMS pour l'utilisateur $userId: " . count($userOrders) . "\n";

foreach ($userOrders as $index => $order) {
    echo "Commande #" . ($index + 1) . ":\n";
    echo "  ID: " . $order->getId() . "\n";
    echo "  Quantité: " . $order->getQuantity() . "\n";
    echo "  Statut: " . $order->getStatus() . "\n";
    echo "  Date de création: " . $order->getCreatedAt()->format('Y-m-d H:i:s') . "\n";
}
echo "\n";

// Test de récupération des commandes SMS par statut
echo "Test de récupération des commandes SMS par statut...\n";
$completedOrders = $smsOrderRepository->findByStatus(SMSOrder::STATUS_COMPLETED);
echo "Nombre de commandes SMS avec le statut COMPLETED: " . count($completedOrders) . "\n";

foreach ($completedOrders as $index => $order) {
    echo "Commande #" . ($index + 1) . ":\n";
    echo "  ID: " . $order->getId() . "\n";
    echo "  Utilisateur: " . $order->getUserId() . "\n";
    echo "  Quantité: " . $order->getQuantity() . "\n";
    echo "  Date de création: " . $order->getCreatedAt()->format('Y-m-d H:i:s') . "\n";
}
echo "\n";

// Test de comptage des commandes SMS
echo "Test de comptage des commandes SMS...\n";
$totalCount = $smsOrderRepository->countAll();
echo "Nombre total de commandes SMS: $totalCount\n";

$userCount = $smsOrderRepository->countByUserId($userId);
echo "Nombre de commandes SMS pour l'utilisateur $userId: $userCount\n";

$completedCount = $smsOrderRepository->countByStatus(SMSOrder::STATUS_COMPLETED);
echo "Nombre de commandes SMS avec le statut COMPLETED: $completedCount\n";

$pendingCount = $smsOrderRepository->countByStatus(SMSOrder::STATUS_PENDING);
echo "Nombre de commandes SMS avec le statut PENDING: $pendingCount\n";
echo "\n";

// Test de suppression d'une commande SMS
echo "Test de suppression d'une commande SMS...\n";
try {
    $result = $smsOrderRepository->deleteById($smsOrderId);

    if ($result) {
        echo "Commande SMS supprimée avec succès.\n";

        // Vérification de la suppression
        $deletedSmsOrder = $smsOrderRepository->findById($smsOrderId);

        if ($deletedSmsOrder === null) {
            echo "Vérification réussie: La commande SMS n'existe plus.\n";
        } else {
            echo "Erreur: La commande SMS existe toujours après suppression.\n";
            exit(1);
        }
    } else {
        echo "Erreur: Impossible de supprimer la commande SMS\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "Erreur lors de la suppression de la commande SMS: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nTous les tests ont été exécutés avec succès!\n";
