<?php

/**
 * Script to fix SMS history creation in SMSService
 * 
 * This script modifies the SMSService class to create a Doctrine entity
 * instead of a legacy model when saving SMS history.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Define the file to modify
$filePath = __DIR__ . '/../src/Services/SMSService.php';

// Read the file content
$content = file_get_contents($filePath);

// Create a backup of the original file
file_put_contents($filePath . '.bak', $content);
echo "Created backup of original file at {$filePath}.bak\n";

// Replace the legacy model import with the Doctrine entity import
$content = str_replace(
    'use App\Models\SMSHistory;',
    'use App\Entities\SMSHistory;',
    $content
);

// Replace the legacy model creation with Doctrine entity creation
$oldCode = <<<'EOD'
                $smsHistory = new SMSHistory(
                    null,
                    $originalNumber,
                    $message,
                    $isSuccess ? 'SENT' : 'FAILED',
                    $senderAddress, // Use value obtained from client
                    $senderName,    // Use value obtained from client
                    $phoneNumberId,
                    $messageId,
                    $errorMessage, // Log error message if sending failed
                    null, // segmentId - handled in sendSMSToSegment
                    $userId
                );
EOD;

$newCode = <<<'EOD'
                $smsHistory = new SMSHistory();
                $smsHistory->setPhoneNumber($originalNumber);
                $smsHistory->setMessage($message);
                $smsHistory->setStatus($isSuccess ? 'SENT' : 'FAILED');
                $smsHistory->setSenderAddress($senderAddress);
                $smsHistory->setSenderName($senderName);
                $smsHistory->setPhoneNumberId($phoneNumberId);
                $smsHistory->setMessageId($messageId);
                $smsHistory->setErrorMessage($errorMessage);
                $smsHistory->setSegmentId(null); // segmentId - handled in sendSMSToSegment
                $smsHistory->setUserId($userId);
                $smsHistory->setCreatedAt(new \DateTime());
EOD;

$content = str_replace($oldCode, $newCode, $content);

// Write the modified content back to the file
file_put_contents($filePath, $content);
echo "Updated {$filePath} to use Doctrine entity instead of legacy model\n";

// Verify the changes
$newContent = file_get_contents($filePath);
if (
    strpos($newContent, 'use App\Entities\SMSHistory;') !== false &&
    strpos($newContent, '$smsHistory = new SMSHistory();') !== false
) {
    echo "Verification successful: File was correctly modified\n";
} else {
    echo "Verification failed: File was not correctly modified\n";
}

echo "\nDone.\n";
