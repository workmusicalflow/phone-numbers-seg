<?php

/**
 * Database Initialization Script
 * 
 * This script connects to the configured database (MySQL or SQLite)
 * and runs all migrations found in the migrations directory.
 */

// Include Composer autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Load environment variables from .env file
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Load database configuration
$config = require __DIR__ . '/../config/database.php';

// Debug output to verify environment variables
echo "Using database driver: " . getenv('DB_DRIVER') . "\n";

// Force SQLite as the database driver due to MySQL service error
$config['driver'] = 'sqlite';
$dbDriver = 'sqlite';
echo "Forcing database driver to: sqlite\n";

$dbDir = __DIR__;
$migrationsDir = $dbDir . '/migrations';

$pdo = null;

// Connect to the database
try {
    if ($dbDriver === 'mysql') {
        $mysqlConfig = $config['mysql'];
        $dsn = "mysql:host={$mysqlConfig['host']};port={$mysqlConfig['port']};dbname={$mysqlConfig['database']};charset={$mysqlConfig['charset']}";
        $pdo = new PDO($dsn, $mysqlConfig['username'], $mysqlConfig['password'], $mysqlConfig['options']);
        echo "Connected to MySQL database '{$mysqlConfig['database']}' successfully\n";
    } elseif ($dbDriver === 'sqlite') {
        $sqliteConfig = $config['sqlite'];
        $dbFile = $sqliteConfig['path'];
        // Ensure directory exists
        if (!is_dir(dirname($dbFile))) {
            mkdir(dirname($dbFile), 0777, true);
        }
        // Create the database file if it doesn't exist
        if (!file_exists($dbFile)) {
            touch($dbFile);
            echo "SQLite database file created: $dbFile\n";
        } else {
            echo "SQLite database file already exists: $dbFile\n";
        }
        $dsn = "sqlite:$dbFile";
        $pdo = new PDO($dsn);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "Connected to SQLite database successfully\n";
    } else {
        die("Unsupported database driver: $dbDriver\n");
    }
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

// --- Migration Logic ---

// Function to check if a migration has already been run (simple version: check table existence)
function hasTable($pdo, $tableName, $dbDriver)
{
    try {
        if ($dbDriver === 'mysql') {
            $stmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
        } else { // sqlite
            $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$tableName'");
        }
        return $stmt->fetchColumn() !== false;
    } catch (PDOException $e) {
        // Handle cases where the query might fail (e.g., table doesn't exist yet)
        return false;
    }
}

// Get all .sql files from the migrations directory
$migrationFiles = glob($migrationsDir . '/*.sql');
sort($migrationFiles); // Ensure migrations run in order

echo "Found " . count($migrationFiles) . " migration files.\n";

foreach ($migrationFiles as $migrationFile) {
    $migrationName = basename($migrationFile);
    echo "Processing migration: $migrationName\n";

    // Basic check to avoid re-running simple create table migrations
    // More robust migration tracking (e.g., a migrations table) is recommended for complex projects
    $tableName = '';
    if (preg_match('/^create_(\w+)_table\.sql$/', $migrationName, $matches)) {
        $tableName = $matches[1];
    } elseif (preg_match('/^create_user_admin_tables\.sql$/', $migrationName) || preg_match('/^create_user_admin_tables_sqlite\.sql$/', $migrationName)) {
        // Special case for the combined user/admin migration
        $tableName = 'users'; // Check for one of the tables created by this script
    } elseif (preg_match('/^add_civility_firstname_fields\.sql$/', $migrationName)) {
        // Check if columns exist for ALTER TABLE migrations (more complex)
        // For simplicity here, we'll assume it needs to run if the file exists
        // A proper migration system would track this better.
        $tableName = 'phone_numbers'; // Check the target table
        try {
            if ($dbDriver === 'mysql') {
                $stmt = $pdo->query("SHOW COLUMNS FROM phone_numbers LIKE 'civility'");
            } else { // sqlite
                $stmt = $pdo->query("PRAGMA table_info(phone_numbers)");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $found = false;
                foreach ($columns as $col) {
                    if ($col['name'] === 'civility') $found = true;
                }
                if ($found) continue; // Skip if column exists
            }
            if ($stmt->fetchColumn()) {
                echo "Skipping migration $migrationName as 'civility' column already exists.\n";
                continue;
            }
        } catch (PDOException $e) { /* table might not exist yet */
        }
    }

    // Skip MySQL migration if using SQLite and vice versa
    if ($migrationName === 'create_user_admin_tables.sql' && $dbDriver === 'sqlite') {
        echo "Skipping MySQL migration $migrationName as we're using SQLite.\n";
        continue;
    }

    if ($migrationName === 'create_user_admin_tables_sqlite.sql' && $dbDriver === 'mysql') {
        echo "Skipping SQLite migration $migrationName as we're using MySQL.\n";
        continue;
    }

    if ($tableName && hasTable($pdo, $tableName, $dbDriver) && $migrationName !== 'add_civility_firstname_fields.sql') {
        // Skip CREATE TABLE if table exists, but allow ALTER TABLE to proceed (basic check)
        echo "Skipping migration $migrationName as table '$tableName' likely already exists.\n";
        continue;
    }


    // Execute the migration SQL
    try {
        $sql = file_get_contents($migrationFile);
        // MySQL can execute multiple statements separated by ;, SQLite usually needs one at a time
        if ($dbDriver === 'mysql') {
            // Split statements carefully, handling potential semicolons within comments or strings is complex
            // This basic split might fail on complex SQL files. Consider a dedicated migration tool.
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    $pdo->exec($statement);
                }
            }
        } else {
            $pdo->exec($sql); // SQLite PDO driver handles multiple statements better
        }
        echo "Successfully executed migration: $migrationName\n";
    } catch (PDOException $e) {
        // Log the specific statement that failed if possible
        echo "Migration failed for $migrationName: " . $e->getMessage() . "\n";
        // Optionally, stop further migrations on failure
        // die("Stopping due to migration failure.\n");
    }
}


// --- Sample Data Seeding (Optional) ---

// Insert sample custom segments if the table is empty (example for SQLite, adapt for MySQL if needed)
if ($dbDriver === 'sqlite' && hasTable($pdo, 'custom_segments', $dbDriver)) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM custom_segments");
        $count = $stmt->fetchColumn();

        if ($count == 0) {
            $sampleSegments = [
                ['name' => 'VIP Clients', 'description' => 'High-value clients with priority support'],
                ['name' => 'Entreprises', 'description' => 'Business clients'],
                ['name' => 'Particuliers', 'description' => 'Individual clients'],
                // Add other segments as needed
            ];

            $stmt = $pdo->prepare("INSERT INTO custom_segments (name, description) VALUES (:name, :description)");
            foreach ($sampleSegments as $segment) {
                $stmt->bindParam(':name', $segment['name']);
                $stmt->bindParam(':description', $segment['description']);
                $stmt->execute();
            }
            echo "Sample custom segments added (SQLite)\n";
        }
    } catch (PDOException $e) {
        echo "Warning: Could not add sample segments (SQLite): " . $e->getMessage() . "\n";
    }
}
// Add similar seeding logic for MySQL if required, checking table existence first.


echo "Database initialization completed for driver: $dbDriver\n";
