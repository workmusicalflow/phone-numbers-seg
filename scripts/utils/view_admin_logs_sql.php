<?php

/**
 * Script pour consulter les journaux d'actions administrateur (version SQL directe)
 * 
 * Ce script permet de visualiser les journaux des actions effectuées par les administrateurs
 * dans la base de données en utilisant directement des requêtes SQL.
 * 
 * Usage:
 * php scripts/utils/view_admin_logs_sql.php [options]
 * 
 * Options:
 *   --limit=N         Limite le nombre de journaux à afficher (défaut: 20)
 *   --admin=ID        Filtre par ID d'administrateur
 *   --action=TYPE     Filtre par type d'action (ex: user_creation, user_update, etc.)
 *   --target=ID       Filtre par ID de cible
 *   --target-type=TYPE Filtre par type de cible (ex: user, sender_name, etc.)
 *   --format=FORMAT   Format de sortie (text, json) (défaut: text)
 *   --help            Affiche l'aide
 */

// Analyser les arguments de la ligne de commande
$options = getopt('', ['limit::', 'admin::', 'action::', 'target::', 'target-type::', 'format::', 'help']);

// Afficher l'aide si demandé
if (isset($options['help'])) {
    echo "Usage: php scripts/utils/view_admin_logs_sql.php [options]\n\n";
    echo "Options:\n";
    echo "  --limit=N         Limite le nombre de journaux à afficher (défaut: 20)\n";
    echo "  --admin=ID        Filtre par ID d'administrateur\n";
    echo "  --action=TYPE     Filtre par type d'action (ex: user_creation, user_update, etc.)\n";
    echo "  --target=ID       Filtre par ID de cible\n";
    echo "  --target-type=TYPE Filtre par type de cible (ex: user, sender_name, etc.)\n";
    echo "  --format=FORMAT   Format de sortie (text, json) (défaut: text)\n";
    echo "  --help            Affiche l'aide\n";
    exit(0);
}

// Définir les valeurs par défaut
$limit = isset($options['limit']) ? (int)$options['limit'] : 20;
$format = isset($options['format']) ? $options['format'] : 'text';

// Créer une connexion PDO
$dbConfig = require __DIR__ . '/../../src/config/database.php';
$driver = $dbConfig['driver'] ?? 'sqlite';

try {
    if ($driver === 'mysql') {
        $dsn = "mysql:host={$dbConfig['mysql']['host']};port={$dbConfig['mysql']['port']};dbname={$dbConfig['mysql']['database']}";
        $pdo = new PDO($dsn, $dbConfig['mysql']['username'], $dbConfig['mysql']['password']);
    } else {
        // Par défaut, utiliser SQLite
        $dsn = "sqlite:{$dbConfig['sqlite']['path']}";
        $pdo = new PDO($dsn);
    }

    // Configurer PDO pour lancer des exceptions en cas d'erreur
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Construire la requête SQL
    $sql = "
        SELECT al.*, u.username as admin_username 
        FROM admin_action_logs al
        JOIN users u ON al.admin_id = u.id
    ";

    $params = [];
    $whereConditions = [];

    if (isset($options['admin'])) {
        $whereConditions[] = "al.admin_id = :admin_id";
        $params[':admin_id'] = (int)$options['admin'];
    }

    if (isset($options['action'])) {
        $whereConditions[] = "al.action_type = :action_type";
        $params[':action_type'] = $options['action'];
    }

    if (isset($options['target'])) {
        $whereConditions[] = "al.target_id = :target_id";
        $params[':target_id'] = (int)$options['target'];

        if (isset($options['target-type'])) {
            $whereConditions[] = "al.target_type = :target_type";
            $params[':target_type'] = $options['target-type'];
        }
    }

    if (!empty($whereConditions)) {
        $sql .= " WHERE " . implode(" AND ", $whereConditions);
    }

    $sql .= " ORDER BY al.created_at DESC LIMIT :limit";
    $params[':limit'] = $limit;

    // Préparer et exécuter la requête
    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $value) {
        $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($key, $value, $paramType);
    }

    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Traiter les détails JSON
    foreach ($logs as &$log) {
        if (isset($log['details']) && !empty($log['details'])) {
            $log['details'] = json_decode($log['details'], true);
        } else {
            $log['details'] = [];
        }
    }

    // Afficher les journaux
    if ($format === 'json') {
        echo json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        echo "=== JOURNAUX D'ACTIONS ADMINISTRATEUR (SQL) ===\n\n";
        echo "Total: " . count($logs) . " journaux\n\n";

        foreach ($logs as $log) {
            echo "ID: {$log['id']}\n";
            echo "Admin: {$log['admin_username']} (ID: {$log['admin_id']})\n";
            echo "Action: {$log['action_type']}\n";
            echo "Cible: " . ($log['target_type'] ? "{$log['target_type']} (ID: {$log['target_id']})" : "Aucune") . "\n";
            echo "Date: {$log['created_at']}\n";

            if (!empty($log['details'])) {
                echo "Détails:\n";
                foreach ($log['details'] as $key => $value) {
                    if (is_array($value) || is_object($value)) {
                        $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                    } elseif (is_bool($value)) {
                        $value = $value ? 'true' : 'false';
                    }
                    echo "  - $key: $value\n";
                }
            } else {
                echo "Détails: Aucun\n";
            }

            echo "----------------------------------------\n";
        }
    }
} catch (PDOException $e) {
    echo "Erreur de base de données: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
