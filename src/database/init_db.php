<?php

/**
 * Database Initialization Script
 * 
 * This script creates the SQLite database and runs the initial migrations.
 */

// Define the database directory and file
$dbDir = __DIR__;
$dbFile = $dbDir . '/database.sqlite';

// Create the database file if it doesn't exist
if (!file_exists($dbFile)) {
    touch($dbFile);
    echo "Database file created: $dbFile\n";
} else {
    echo "Database file already exists: $dbFile\n";
}

// Connect to the database
try {
    $pdo = new PDO("sqlite:$dbFile");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to the database successfully\n";
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

// Run the migrations
$migrationFile = $dbDir . '/migrations/create_tables.sql';
if (file_exists($migrationFile)) {
    try {
        $sql = file_get_contents($migrationFile);
        $pdo->exec($sql);
        echo "Migrations executed successfully\n";
    } catch (PDOException $e) {
        die("Migration failed: " . $e->getMessage() . "\n");
    }
} else {
    die("Migration file not found: $migrationFile\n");
}

// Insert some sample custom segments if they don't exist
try {
    // Check if custom_segments table exists
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='custom_segments'");
    $tableExists = $stmt->fetchColumn();

    if ($tableExists) {
        // Check if there are any segments
        $stmt = $pdo->query("SELECT COUNT(*) FROM custom_segments");
        $count = $stmt->fetchColumn();

        if ($count == 0) {
            // Insert sample segments
            $sampleSegments = [
                ['name' => 'VIP Clients', 'description' => 'High-value clients with priority support'],
                ['name' => 'Entreprises', 'description' => 'Business clients'],
                ['name' => 'Particuliers', 'description' => 'Individual clients'],
                ['name' => 'Secteur Santé', 'description' => 'Healthcare sector clients'],
                ['name' => 'Secteur Éducation', 'description' => 'Education sector clients'],
                ['name' => 'Secteur Finance', 'description' => 'Finance sector clients'],
                ['name' => 'Secteur Commerce', 'description' => 'Retail sector clients'],
                ['name' => 'Nouveaux Clients', 'description' => 'Clients added in the last 30 days']
            ];

            $stmt = $pdo->prepare("INSERT INTO custom_segments (name, description) VALUES (:name, :description)");

            foreach ($sampleSegments as $segment) {
                $stmt->bindParam(':name', $segment['name']);
                $stmt->bindParam(':description', $segment['description']);
                $stmt->execute();
            }

            echo "Sample custom segments added\n";
        }
    }
} catch (PDOException $e) {
    echo "Warning: Could not add sample segments: " . $e->getMessage() . "\n";
}

echo "Database initialization completed\n";
