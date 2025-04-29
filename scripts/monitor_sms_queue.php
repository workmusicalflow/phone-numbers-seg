<?php

/**
 * SMS Queue Monitoring Script
 * 
 * This script monitors the SMS queue to ensure the new Doctrine implementation
 * is functioning correctly. It doesn't make any changes but provides diagnostic
 * information about the current state of the queue.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entities\SMSQueue;
use App\Repositories\Interfaces\SMSQueueRepositoryInterface;
use DI\ContainerBuilder;

// Parse command line arguments
$options = getopt('', ['verbose::', 'help::']);

if (isset($options['help'])) {
    echo "SMS Queue Monitoring Script\n";
    echo "=========================\n\n";
    echo "This script monitors the SMS queue and shows its current state.\n\n";
    echo "Options:\n";
    echo "  --verbose    Show detailed information\n";
    echo "  --help       Show this help message\n";
    exit(0);
}

$verbose = isset($options['verbose']);

// Build container
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../src/config/di.php');
$container = $containerBuilder->build();

// Get repository
$repository = $container->get(SMSQueueRepositoryInterface::class);

echo "SMS Queue Monitor - " . date('Y-m-d H:i:s') . "\n";
echo "==============================" . str_repeat("=", strlen(date('Y-m-d H:i:s'))) . "\n\n";

try {
    // Check repository type
    echo "Repository type: " . get_class($repository) . "\n\n";
    
    // Get queue statistics
    $pendingCount = $repository->countByStatus(SMSQueue::STATUS_PENDING);
    $processingCount = $repository->countByStatus(SMSQueue::STATUS_PROCESSING);
    $sentCount = $repository->countByStatus(SMSQueue::STATUS_SENT);
    $failedCount = $repository->countByStatus(SMSQueue::STATUS_FAILED);
    $cancelledCount = $repository->countByStatus(SMSQueue::STATUS_CANCELLED);
    $totalCount = $pendingCount + $processingCount + $sentCount + $failedCount + $cancelledCount;
    
    echo "Queue Statistics:\n";
    echo "----------------\n";
    echo "Pending:    $pendingCount\n";
    echo "Processing: $processingCount\n";
    echo "Sent:       $sentCount\n";
    echo "Failed:     $failedCount\n";
    echo "Cancelled:  $cancelledCount\n";
    echo "----------------\n";
    echo "Total:      $totalCount\n\n";
    
    // Show pending messages if verbose
    if ($verbose && $pendingCount > 0) {
        $pendingMessages = $repository->findByStatus(SMSQueue::STATUS_PENDING, 10);
        
        echo "Recent Pending Messages (top 10):\n";
        echo "--------------------------------\n";
        foreach ($pendingMessages as $index => $message) {
            $createdAt = $message->getCreatedAt()->format('Y-m-d H:i:s');
            $nextAttemptAt = $message->getNextAttemptAt() ? $message->getNextAttemptAt()->format('Y-m-d H:i:s') : 'N/A';
            
            echo ($index + 1) . ". ID: " . $message->getId() . "\n";
            echo "   Created: $createdAt | Next attempt: $nextAttemptAt\n";
            echo "   Phone: " . $message->getPhoneNumber() . "\n";
            echo "   Message: " . (strlen($message->getMessage()) > 50 ? substr($message->getMessage(), 0, 47) . '...' : $message->getMessage()) . "\n";
            echo "\n";
        }
    }
    
    // Check for stuck processing messages
    $threshold = new \DateTime('-10 minutes');
    $stuckMessages = $repository->findExpiredProcessing($threshold);
    
    if (count($stuckMessages) > 0) {
        echo "WARNING: Found " . count($stuckMessages) . " stuck processing messages!\n";
        
        if ($verbose) {
            echo "Stuck Messages:\n";
            echo "--------------\n";
            foreach ($stuckMessages as $index => $message) {
                $lastAttemptAt = $message->getLastAttemptAt() ? $message->getLastAttemptAt()->format('Y-m-d H:i:s') : 'N/A';
                
                echo ($index + 1) . ". ID: " . $message->getId() . "\n";
                echo "   Last attempt: $lastAttemptAt\n";
                echo "   Phone: " . $message->getPhoneNumber() . "\n";
                echo "   Message: " . (strlen($message->getMessage()) > 50 ? substr($message->getMessage(), 0, 47) . '...' : $message->getMessage()) . "\n";
                echo "\n";
            }
        }
    } else {
        echo "No stuck processing messages found.\n\n";
    }
    
    // Check next batch that would be processed
    $nextBatch = $repository->findNextBatch(5);
    
    echo "Next messages to be processed (top 5):\n";
    echo "------------------------------------\n";
    
    if (count($nextBatch) > 0) {
        foreach ($nextBatch as $index => $message) {
            $nextAttemptAt = $message->getNextAttemptAt() ? $message->getNextAttemptAt()->format('Y-m-d H:i:s') : 'N/A';
            $userId = $message->getUserId() ?: 'N/A';
            
            echo ($index + 1) . ". ID: " . $message->getId() . "\n";
            echo "   Next attempt: $nextAttemptAt | Priority: " . $message->getPriority() . " | User ID: $userId\n";
            echo "   Phone: " . $message->getPhoneNumber() . "\n";
            if ($verbose) {
                echo "   Message: " . (strlen($message->getMessage()) > 50 ? substr($message->getMessage(), 0, 47) . '...' : $message->getMessage()) . "\n";
            }
            echo "\n";
        }
    } else {
        echo "No messages in queue ready to be processed.\n";
    }
    
    echo "\nMonitoring complete. Queue appears to be functioning normally.\n";
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}