<?php
/**
 * Script de test pour l'intégration WhatsApp Business Cloud API
 * 
 * Ce script permet de tester les différentes fonctionnalités de l'intégration WhatsApp:
 * 1. Vérification de la configuration
 * 2. Envoi d'un message texte de test
 * 3. Envoi d'un message template de test
 * 4. Vérification du webhook
 * 5. Test de la base de données
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/bootstrap-doctrine.php';

use App\Entities\WhatsApp\WhatsAppMessage;
use App\Repositories\Interfaces\WhatsApp\WhatsAppMessageRepositoryInterface;
use App\Services\Interfaces\WhatsApp\WhatsAppApiClientInterface;
use App\Services\Interfaces\WhatsApp\WebhookVerificationServiceInterface;
use App\Services\Interfaces\WhatsApp\WhatsAppMessageServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Exception as DBALException;

// Couleurs pour l'output console
const GREEN = "\033[32m";
const RED = "\033[31m";
const YELLOW = "\033[33m";
const RESET = "\033[0m";

// Fonction d'aide pour afficher des messages formatés
function printStatus($message, $status = null) {
    switch ($status) {
        case 'success':
            echo GREEN . "[✓] " . RESET . $message . PHP_EOL;
            break;
        case 'error':
            echo RED . "[✗] " . RESET . $message . PHP_EOL;
            break;
        case 'warning':
            echo YELLOW . "[!] " . RESET . $message . PHP_EOL;
            break;
        case 'info':
            echo "[i] " . $message . PHP_EOL;
            break;
        default:
            echo $message . PHP_EOL;
    }
}

function printHeader($title) {
    echo PHP_EOL . "=== " . strtoupper($title) . " ===" . PHP_EOL;
}

function printSeparator() {
    echo "------------------------------------------------" . PHP_EOL;
}

// Fonction pour demander à l'utilisateur de choisir un test
function promptTest() {
    echo PHP_EOL;
    echo "Choisissez un test à exécuter:" . PHP_EOL;
    echo "1. Vérifier la configuration" . PHP_EOL;
    echo "2. Tester l'envoi d'un message texte" . PHP_EOL;
    echo "3. Tester l'envoi d'un message template" . PHP_EOL;
    echo "4. Vérifier la configuration du webhook" . PHP_EOL;
    echo "5. Tester la base de données WhatsApp" . PHP_EOL;
    echo "6. Exécuter tous les tests" . PHP_EOL;
    echo "0. Quitter" . PHP_EOL;
    
    echo "Votre choix: ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);
    
    return $line;
}

// Fonction pour demander un numéro de téléphone de test
function promptPhoneNumber() {
    echo "Entrez un numéro de téléphone de test (format: +XXXXXXXXXXXX): ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);
    
    // Validation basique
    if (!preg_match('/^\+?[0-9]{10,15}$/', $line)) {
        printStatus("Format de numéro invalide. Veuillez utiliser le format +XXXXXXXXXXXX", 'error');
        return promptPhoneNumber();
    }
    
    return $line;
}

// Fonction pour tester la configuration
function testConfiguration($container) {
    printHeader("Test de la Configuration");
    
    // 1. Vérifier la configuration WhatsApp
    try {
        $config = $container->get('whatsapp.config');
        
        if (empty($config['app_id'])) {
            printStatus("app_id manquant ou vide dans la configuration", 'error');
        } else {
            printStatus("app_id: " . $config['app_id'], 'success');
        }
        
        if (empty($config['phone_number_id'])) {
            printStatus("phone_number_id manquant ou vide dans la configuration", 'error');
        } else {
            printStatus("phone_number_id: " . $config['phone_number_id'], 'success');
        }
        
        if (empty($config['whatsapp_business_account_id'])) {
            printStatus("whatsapp_business_account_id manquant ou vide dans la configuration", 'error');
        } else {
            printStatus("whatsapp_business_account_id: " . $config['whatsapp_business_account_id'], 'success');
        }
        
        if (empty($config['api_version'])) {
            printStatus("api_version manquant ou vide dans la configuration", 'error');
        } else {
            printStatus("api_version: " . $config['api_version'], 'success');
        }
        
        if (empty($config['access_token']) || $config['access_token'] === 'EAAQ93dlFUw4BO...') {
            printStatus("access_token manquant ou non configuré dans la configuration", 'error');
        } else {
            printStatus("access_token: " . substr($config['access_token'], 0, 10) . "...", 'success');
        }
        
        if (empty($config['webhook_verify_token'])) {
            printStatus("webhook_verify_token manquant ou vide dans la configuration", 'error');
        } else {
            printStatus("webhook_verify_token: " . $config['webhook_verify_token'], 'success');
        }
        
        // 2. Vérifier les templates
        if (empty($config['templates']) || !is_array($config['templates'])) {
            printStatus("Templates manquants ou invalides dans la configuration", 'error');
        } else {
            printStatus("Nombre de templates configurés: " . count($config['templates']), 'success');
            foreach ($config['templates'] as $key => $template) {
                printStatus("Template: " . $key, 'info');
            }
        }
        
        return true;
    } catch (\Exception $e) {
        printStatus("Erreur lors de la vérification de la configuration: " . $e->getMessage(), 'error');
        return false;
    }
}

// Fonction pour tester l'envoi d'un message texte
function testSendTextMessage($container) {
    printHeader("Test d'Envoi de Message Texte");
    
    $phoneNumber = promptPhoneNumber();
    
    try {
        // Obtenir le client API
        $apiClient = $container->get(WhatsAppApiClientInterface::class);
        
        // Message de test
        $message = 'Ceci est un message de test envoyé par le script test_whatsapp_integration.php [' . date('Y-m-d H:i:s') . ']';
        
        printStatus("Envoi du message: " . $message, 'info');
        printStatus("Au numéro: " . $phoneNumber, 'info');
        
        // Envoyer le message
        $result = $apiClient->sendTextMessage($phoneNumber, $message);
        
        // Vérifier la réponse
        if (isset($result['messages']) && !empty($result['messages'])) {
            $messageId = $result['messages'][0]['id'] ?? 'inconnu';
            printStatus("Message envoyé avec succès! ID: " . $messageId, 'success');
            return true;
        } else {
            printStatus("Échec de l'envoi du message.", 'error');
            if (isset($result['error'])) {
                printStatus("Erreur: " . json_encode($result['error']), 'error');
            }
            return false;
        }
    } catch (\Exception $e) {
        printStatus("Exception lors de l'envoi du message: " . $e->getMessage(), 'error');
        return false;
    }
}

// Fonction pour tester l'envoi d'un message template
function testSendTemplateMessage($container) {
    printHeader("Test d'Envoi de Message Template");
    
    $phoneNumber = promptPhoneNumber();
    
    try {
        // Obtenir le client API et la configuration
        $apiClient = $container->get(WhatsAppApiClientInterface::class);
        $config = $container->get('whatsapp.config');
        
        // Sélectionner un template
        if (empty($config['templates']) || !is_array($config['templates'])) {
            printStatus("Aucun template disponible dans la configuration", 'error');
            return false;
        }
        
        // Utiliser le premier template disponible
        $templateNames = array_keys($config['templates']);
        $templateName = $templateNames[0];
        $template = $config['templates'][$templateName];
        
        printStatus("Utilisation du template: " . $templateName, 'info');
        printStatus("Langue: " . ($template['language'] ?? 'fr'), 'info');
        printStatus("Au numéro: " . $phoneNumber, 'info');
        
        // Composants (si disponibles)
        $components = $template['components'] ?? [];
        
        // Envoyer le message template
        $result = $apiClient->sendTemplateMessage(
            $phoneNumber,
            $templateName,
            $template['language'] ?? 'fr',
            $components
        );
        
        // Vérifier la réponse
        if (isset($result['messages']) && !empty($result['messages'])) {
            $messageId = $result['messages'][0]['id'] ?? 'inconnu';
            printStatus("Template envoyé avec succès! ID: " . $messageId, 'success');
            return true;
        } else {
            printStatus("Échec de l'envoi du template.", 'error');
            if (isset($result['error'])) {
                printStatus("Erreur: " . json_encode($result['error']), 'error');
            }
            return false;
        }
    } catch (\Exception $e) {
        printStatus("Exception lors de l'envoi du template: " . $e->getMessage(), 'error');
        return false;
    }
}

// Fonction pour vérifier la configuration du webhook
function testWebhook($container) {
    printHeader("Test de la Configuration du Webhook");
    
    try {
        // Obtenir le service de vérification
        $verificationService = $container->get(WebhookVerificationServiceInterface::class);
        $config = $container->get('whatsapp.config');
        
        // Vérifier le token
        $mode = 'subscribe';
        $token = $config['webhook_verify_token'] ?? '';
        
        printStatus("Mode de vérification: " . $mode, 'info');
        printStatus("Token de vérification: " . $token, 'info');
        
        $isValid = $verificationService->verifyToken($mode, $token);
        
        if ($isValid) {
            printStatus("Vérification du token réussie!", 'success');
        } else {
            printStatus("Échec de la vérification du token", 'error');
        }
        
        // Informations pour configurer le webhook dans Meta for Developers
        printStatus("URL du webhook à configurer dans Meta for Developers:", 'info');
        printStatus("https://votre-domaine.com/whatsapp/webhook.php", 'info');
        printStatus("Pour les tests en local, utilisez ngrok:", 'info');
        printStatus("ngrok http 8000", 'info');
        printStatus("Puis utilisez l'URL générée: https://xxx-xxx-xxx-xxx.ngrok.io/whatsapp/webhook.php", 'info');
        
        return $isValid;
    } catch (\Exception $e) {
        printStatus("Exception lors du test du webhook: " . $e->getMessage(), 'error');
        return false;
    }
}

// Fonction pour tester la base de données
function testDatabase($container) {
    printHeader("Test de la Base de Données WhatsApp");
    
    try {
        // Vérifier si la table existe
        $entityManager = $container->get(EntityManagerInterface::class);
        $connection = $entityManager->getConnection();
        
        // Vérifier l'existence de la table
        try {
            $tableExists = $connection->executeQuery("
                SELECT name FROM sqlite_master 
                WHERE type='table' AND name='whatsapp_messages'
            ")->fetchAssociative();
            
            if ($tableExists) {
                printStatus("Table whatsapp_messages existe", 'success');
            } else {
                printStatus("Table whatsapp_messages n'existe pas", 'error');
                printStatus("Exécutez scripts/migrate_whatsapp_messages.php pour créer la table", 'info');
                return false;
            }
        } catch (DBALException $e) {
            printStatus("Erreur lors de la vérification de la table: " . $e->getMessage(), 'error');
            return false;
        }
        
        // Tester l'insertion/récupération d'un message de test
        $repository = $container->get(WhatsAppMessageRepositoryInterface::class);
        
        // Créer un message de test
        $testMessage = new WhatsAppMessage();
        $testMessage->setMessageId('test_' . time());
        $testMessage->setSender('+1234567890');
        $testMessage->setRecipient('+0987654321');
        $testMessage->setTimestamp(time());
        $testMessage->setType('test');
        $testMessage->setContent('Message de test pour la base de données');
        $testMessage->setRawData(json_encode(['test' => true]));
        
        // Sauvegarder le message
        $savedMessage = $repository->save($testMessage);
        
        // Vérifier la sauvegarde
        if ($savedMessage->getId()) {
            printStatus("Message de test sauvegardé avec ID: " . $savedMessage->getId(), 'success');
            
            // Récupérer le message
            $retrievedMessage = $repository->findByMessageId($savedMessage->getMessageId());
            
            if ($retrievedMessage && $retrievedMessage->getId() === $savedMessage->getId()) {
                printStatus("Message retrouvé avec succès", 'success');
                
                // Supprimer le message de test
                $connection->executeStatement(
                    "DELETE FROM whatsapp_messages WHERE message_id = ?",
                    [$savedMessage->getMessageId()]
                );
                
                printStatus("Message de test supprimé", 'success');
            } else {
                printStatus("Échec de la récupération du message", 'error');
            }
        } else {
            printStatus("Échec de la sauvegarde du message", 'error');
        }
        
        // Compte le nombre de messages dans la table
        $count = $connection->executeQuery("SELECT COUNT(*) FROM whatsapp_messages")->fetchOne();
        printStatus("Nombre total de messages dans la base: " . $count, 'info');
        
        return true;
    } catch (\Exception $e) {
        printStatus("Exception lors du test de la base de données: " . $e->getMessage(), 'error');
        return false;
    }
}

// Programme principal
try {
    printHeader("Test de l'Intégration WhatsApp Business Cloud API");
    
    // Récupération du conteneur DI
    global $container;
    
    if (!$container) {
        printStatus("Erreur: Le conteneur d'injection de dépendances n'est pas disponible", 'error');
        exit(1);
    }
    
    // Menu principal
    while (true) {
        $choice = promptTest();
        
        switch ($choice) {
            case '1':
                testConfiguration($container);
                break;
            case '2':
                testSendTextMessage($container);
                break;
            case '3':
                testSendTemplateMessage($container);
                break;
            case '4':
                testWebhook($container);
                break;
            case '5':
                testDatabase($container);
                break;
            case '6':
                // Exécuter tous les tests
                testConfiguration($container);
                testWebhook($container);
                testDatabase($container);
                
                // Demander confirmation pour les tests d'envoi de messages
                echo "Voulez-vous exécuter les tests d'envoi de messages ? (o/n): ";
                $handle = fopen("php://stdin", "r");
                $confirm = strtolower(trim(fgets($handle)));
                fclose($handle);
                
                if ($confirm === 'o' || $confirm === 'oui') {
                    testSendTextMessage($container);
                    testSendTemplateMessage($container);
                }
                break;
            case '0':
                // Quitter
                printStatus("Au revoir!", 'info');
                exit(0);
            default:
                printStatus("Choix invalide. Veuillez réessayer.", 'warning');
        }
        
        printSeparator();
    }
} catch (\Exception $e) {
    printStatus("Exception non gérée: " . $e->getMessage(), 'error');
    exit(1);
}