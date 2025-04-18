<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entities\OrangeAPIConfig;
use App\Repositories\Doctrine\OrangeAPIConfigRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// Get the entity manager directly from bootstrap file
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

// Create the repository
$orangeAPIConfigRepository = new OrangeAPIConfigRepository($entityManager);

// Check if the OrangeAPIConfig table exists and create it if needed
try {
    // Try to get the metadata for the OrangeAPIConfig entity
    $orangeAPIConfigMetadata = $entityManager->getClassMetadata(OrangeAPIConfig::class);

    // Create a schema tool
    $schemaTool = new SchemaTool($entityManager);

    // Check if the table exists by trying to find one record
    $testQuery = $entityManager->createQuery('SELECT COUNT(c) FROM ' . OrangeAPIConfig::class . ' c');
    $testQuery->getResult();

    echo "OrangeAPIConfig table exists.\n\n";
} catch (\Exception $e) {
    // If there's an error, the table might not exist
    echo "OrangeAPIConfig table doesn't exist. Creating it...\n";

    try {
        // Get the metadata for the OrangeAPIConfig entity
        $orangeAPIConfigMetadata = $entityManager->getClassMetadata(OrangeAPIConfig::class);

        // Create a schema tool
        $schemaTool = new SchemaTool($entityManager);

        // Create the schema for OrangeAPIConfig entity
        $schemaTool->createSchema([$orangeAPIConfigMetadata]);
        echo "OrangeAPIConfig table created successfully.\n\n";
    } catch (\Exception $e) {
        // If there's an error creating the schema, try to update it
        echo "Error creating schema: " . $e->getMessage() . "\n";
        echo "Trying to update schema instead...\n";

        // Get the metadata for the OrangeAPIConfig entity
        $orangeAPIConfigMetadata = $entityManager->getClassMetadata(OrangeAPIConfig::class);

        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->updateSchema([$orangeAPIConfigMetadata]);
        echo "OrangeAPIConfig table updated successfully.\n\n";
    }
}

// Test creating an admin API configuration
echo "Test de création d'une configuration API Orange admin...\n";
$clientId = 'admin_client_id_' . time();
$clientSecret = 'admin_client_secret_' . time();

try {
    $adminConfig = $orangeAPIConfigRepository->create(null, $clientId, $clientSecret, true);
    echo "Configuration API Orange admin créée avec succès. ID: " . $adminConfig->getId() . "\n";
    echo "Client ID: " . $adminConfig->getClientId() . "\n";
    echo "Client Secret: " . $adminConfig->getClientSecret() . "\n";
    echo "Est admin: " . ($adminConfig->isAdmin() ? 'Oui' : 'Non') . "\n";
    echo "Date de création: " . $adminConfig->getCreatedAt()->format('Y-m-d H:i:s') . "\n";
    echo "\n";
} catch (Exception $e) {
    echo "Erreur lors de la création de la configuration API Orange admin: " . $e->getMessage() . "\n";
    exit(1);
}

// Test creating a user API configuration
echo "Test de création d'une configuration API Orange utilisateur...\n";
$userId = 1; // ID d'un utilisateur existant
$clientId = 'user_client_id_' . time();
$clientSecret = 'user_client_secret_' . time();

try {
    $userConfig = $orangeAPIConfigRepository->create($userId, $clientId, $clientSecret, false);
    echo "Configuration API Orange utilisateur créée avec succès. ID: " . $userConfig->getId() . "\n";
    echo "User ID: " . $userConfig->getUserId() . "\n";
    echo "Client ID: " . $userConfig->getClientId() . "\n";
    echo "Client Secret: " . $userConfig->getClientSecret() . "\n";
    echo "Est admin: " . ($userConfig->isAdmin() ? 'Oui' : 'Non') . "\n";
    echo "Date de création: " . $userConfig->getCreatedAt()->format('Y-m-d H:i:s') . "\n";
    echo "\n";
} catch (Exception $e) {
    echo "Erreur lors de la création de la configuration API Orange utilisateur: " . $e->getMessage() . "\n";
    exit(1);
}

// Test finding a configuration by ID
echo "Test de recherche d'une configuration par ID...\n";
$configId = $userConfig->getId();
$foundConfig = $orangeAPIConfigRepository->findById($configId);

if ($foundConfig) {
    echo "Configuration trouvée avec succès. ID: " . $foundConfig->getId() . "\n";
    echo "User ID: " . $foundConfig->getUserId() . "\n";
    echo "Client ID: " . $foundConfig->getClientId() . "\n";
    echo "Client Secret: " . $foundConfig->getClientSecret() . "\n";
    echo "Est admin: " . ($foundConfig->isAdmin() ? 'Oui' : 'Non') . "\n";
    echo "\n";
} else {
    echo "Erreur: Configuration non trouvée avec l'ID $configId\n";
    exit(1);
}

// Test finding the admin configuration
echo "Test de recherche de la configuration admin...\n";
$foundAdminConfig = $orangeAPIConfigRepository->findAdminConfig();

if ($foundAdminConfig) {
    echo "Configuration admin trouvée avec succès. ID: " . $foundAdminConfig->getId() . "\n";
    echo "Client ID: " . $foundAdminConfig->getClientId() . "\n";
    echo "Client Secret: " . $foundAdminConfig->getClientSecret() . "\n";
    echo "Est admin: " . ($foundAdminConfig->isAdmin() ? 'Oui' : 'Non') . "\n";
    echo "\n";
} else {
    echo "Erreur: Configuration admin non trouvée\n";
    exit(1);
}

// Test finding a configuration by user ID
echo "Test de recherche d'une configuration par ID utilisateur...\n";
$foundUserConfig = $orangeAPIConfigRepository->findByUserId($userId);

if ($foundUserConfig) {
    echo "Configuration utilisateur trouvée avec succès. ID: " . $foundUserConfig->getId() . "\n";
    echo "User ID: " . $foundUserConfig->getUserId() . "\n";
    echo "Client ID: " . $foundUserConfig->getClientId() . "\n";
    echo "Client Secret: " . $foundUserConfig->getClientSecret() . "\n";
    echo "Est admin: " . ($foundUserConfig->isAdmin() ? 'Oui' : 'Non') . "\n";
    echo "\n";
} else {
    echo "Erreur: Configuration utilisateur non trouvée pour l'ID utilisateur $userId\n";
    exit(1);
}

// Test finding all user configurations
echo "Test de recherche de toutes les configurations utilisateur...\n";
$userConfigs = $orangeAPIConfigRepository->findAllUserConfigs();
echo "Nombre de configurations utilisateur trouvées: " . count($userConfigs) . "\n";

foreach ($userConfigs as $index => $config) {
    echo "Configuration #" . ($index + 1) . ":\n";
    echo "  ID: " . $config->getId() . "\n";
    echo "  User ID: " . $config->getUserId() . "\n";
    echo "  Client ID: " . $config->getClientId() . "\n";
    echo "  Client Secret: " . $config->getClientSecret() . "\n";
    echo "  Est admin: " . ($config->isAdmin() ? 'Oui' : 'Non') . "\n";
}
echo "\n";

// Test deleting a configuration by user ID
echo "Test de suppression d'une configuration par ID utilisateur...\n";
$result = $orangeAPIConfigRepository->deleteByUserId($userId);

if ($result) {
    echo "Configuration utilisateur supprimée avec succès.\n";

    // Verify deletion
    $remainingConfig = $orangeAPIConfigRepository->findByUserId($userId);
    if ($remainingConfig === null) {
        echo "Vérification réussie: La configuration utilisateur a été supprimée.\n";
    } else {
        echo "Erreur: La configuration utilisateur existe toujours après la suppression.\n";
        exit(1);
    }
} else {
    echo "Erreur: Impossible de supprimer la configuration utilisateur\n";
    exit(1);
}

// Test deleting a configuration by ID
echo "Test de suppression d'une configuration par ID...\n";
$adminConfigId = $adminConfig->getId();
$result = $orangeAPIConfigRepository->deleteById($adminConfigId);

if ($result) {
    echo "Configuration admin supprimée avec succès.\n";

    // Verify deletion
    $remainingConfig = $orangeAPIConfigRepository->findById($adminConfigId);
    if ($remainingConfig === null) {
        echo "Vérification réussie: La configuration admin a été supprimée.\n";
    } else {
        echo "Erreur: La configuration admin existe toujours après la suppression.\n";
        exit(1);
    }
} else {
    echo "Erreur: Impossible de supprimer la configuration admin\n";
    exit(1);
}

echo "\nTous les tests ont été exécutés avec succès!\n";
