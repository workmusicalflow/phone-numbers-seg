<?php

/**
 * Script to fix SMSResolver to use Doctrine entities
 * 
 * This script modifies the SMSResolver class to use Doctrine entities
 * instead of legacy models.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Define the file to modify
$filePath = __DIR__ . '/../src/GraphQL/Resolvers/SMSResolver.php';

// Read the file content
$content = file_get_contents($filePath);

// Create a backup of the original file
file_put_contents($filePath . '.bak', $content);
echo "Created backup of original file at {$filePath}.bak\n";

// Replace the legacy model imports with the Doctrine entity imports
$content = str_replace(
    'use App\Models\SMSHistory;',
    'use App\Entities\SMSHistory;',
    $content
);

$content = str_replace(
    'use App\Models\Segment;',
    'use App\Entities\Segment;',
    $content
);

$content = str_replace(
    'use App\Models\CustomSegment;',
    'use App\Entities\CustomSegment;',
    $content
);

// Write the modified content back to the file
file_put_contents($filePath, $content);
echo "Updated {$filePath} to use Doctrine entities instead of legacy models\n";

// Verify the changes
$newContent = file_get_contents($filePath);
if (
    strpos($newContent, 'use App\Entities\SMSHistory;') !== false &&
    strpos($newContent, 'use App\Entities\Segment;') !== false &&
    strpos($newContent, 'use App\Entities\CustomSegment;') !== false
) {
    echo "Verification successful: File was correctly modified\n";
} else {
    echo "Verification failed: File was not correctly modified\n";
}

echo "\nDone.\n";
