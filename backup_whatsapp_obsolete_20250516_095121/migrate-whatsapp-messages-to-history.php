<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Doctrine\DBAL\DriverManager;

echo "=== Migration whatsapp_messages → whatsapp_message_history ===\n\n";

try {
    // Connexion à la base de données
    $connectionParams = [
        'driver' => 'pdo_sqlite',
        'path' => __DIR__ . '/../../var/database.sqlite',
    ];
    
    $connection = DriverManager::getConnection($connectionParams);
    
    // 1. Vérifier s'il y a des données à migrer
    $countQuery = "SELECT COUNT(*) as count FROM whatsapp_messages";
    $result = $connection->executeQuery($countQuery);
    $count = $result->fetchOne();
    
    echo "Nombre de messages à migrer: $count\n";
    
    if ($count == 0) {
        echo "Aucun message à migrer.\n";
        exit(0);
    }
    
    // 2. Créer une sauvegarde de la table originale
    echo "\nCréation d'une sauvegarde de whatsapp_messages...\n";
    $backupQuery = "CREATE TABLE IF NOT EXISTS whatsapp_messages_backup AS SELECT * FROM whatsapp_messages";
    $connection->executeStatement($backupQuery);
    echo "✓ Sauvegarde créée: whatsapp_messages_backup\n";
    
    // 3. Commencer la migration
    echo "\nDébut de la migration...\n";
    $connection->beginTransaction();
    
    try {
        // Obtenir tous les messages
        $selectQuery = "SELECT * FROM whatsapp_messages ORDER BY timestamp";
        $messages = $connection->fetchAllAssociative($selectQuery);
        
        $migrated = 0;
        $errors = [];
        
        foreach ($messages as $message) {
            try {
                // Déterminer la direction (IN/OUT) basée sur le sender/recipient
                // Supposons que si sender est un numéro de téléphone WhatsApp Business, c'est OUT
                // Sinon c'est IN
                $direction = 'INBOUND'; // Par défaut
                if ($message['recipient'] !== null) {
                    $direction = 'OUTBOUND';
                }
                
                // Préparer les données pour la nouvelle table
                $insertData = [
                    'waba_message_id' => $message['messageId'],
                    'phone_number' => $message['sender'] ?? $message['recipient'],
                    'direction' => $direction,
                    'type' => strtoupper($message['type']),
                    'content' => $message['content'],
                    'status' => $message['status'] ?? 'DELIVERED',
                    'timestamp' => date('Y-m-d H:i:s', $message['timestamp']),
                    'error_code' => null,
                    'error_message' => null,
                    'conversation_id' => null,
                    'pricing_category' => null,
                    'media_id' => $message['mediaUrl'] ? md5($message['mediaUrl']) : null,
                    'template_name' => null,
                    'template_language' => null,
                    'context_data' => json_encode(['rawData' => json_decode($message['rawData'], true)]),
                    'created_at' => date('Y-m-d H:i:s', $message['createdAt']),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'oracle_user_id' => 1, // À ADAPTER: Utiliser l'ID réel de l'utilisateur
                    'contact_id' => null // À ADAPTER: Lier aux contacts si possible
                ];
                
                // Insérer dans la nouvelle table
                $insertQuery = "INSERT INTO whatsapp_message_history (
                    waba_message_id, phone_number, direction, type, content, 
                    status, timestamp, error_code, error_message, conversation_id,
                    pricing_category, media_id, template_name, template_language,
                    context_data, created_at, updated_at, oracle_user_id, contact_id
                ) VALUES (
                    :waba_message_id, :phone_number, :direction, :type, :content,
                    :status, :timestamp, :error_code, :error_message, :conversation_id,
                    :pricing_category, :media_id, :template_name, :template_language,
                    :context_data, :created_at, :updated_at, :oracle_user_id, :contact_id
                )";
                
                $connection->executeStatement($insertQuery, $insertData);
                $migrated++;
                
                if ($migrated % 100 == 0) {
                    echo "Migré: $migrated messages...\n";
                }
                
            } catch (Exception $e) {
                $errors[] = [
                    'messageId' => $message['messageId'],
                    'error' => $e->getMessage()
                ];
            }
        }
        
        // Commit la transaction
        $connection->commit();
        
        echo "\n=== Résultats de la migration ===\n";
        echo "Messages migrés avec succès: $migrated\n";
        echo "Erreurs: " . count($errors) . "\n";
        
        if (!empty($errors)) {
            echo "\nDétail des erreurs:\n";
            foreach ($errors as $error) {
                echo "- Message ID {$error['messageId']}: {$error['error']}\n";
            }
        }
        
        // 4. Créer un rapport de migration
        $reportFile = __DIR__ . '/../../var/logs/whatsapp_migration_' . date('Y-m-d_H-i-s') . '.json';
        $report = [
            'date' => date('Y-m-d H:i:s'),
            'total_messages' => $count,
            'migrated' => $migrated,
            'errors' => $errors
        ];
        file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT));
        echo "\nRapport de migration sauvegardé: $reportFile\n";
        
        // 5. Option pour supprimer l'ancienne table (commenté par sécurité)
        echo "\nLa table originale whatsapp_messages a été conservée.\n";
        echo "Pour la supprimer après vérification, exécutez:\n";
        echo "DROP TABLE whatsapp_messages;\n";
        
    } catch (Exception $e) {
        $connection->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    echo "\nErreur lors de la migration: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=== Migration terminée ===\n";