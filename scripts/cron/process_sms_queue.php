<?php
/**
 * SMS Queue Worker Script
 * 
 * This script processes pending SMS messages in the queue.
 * It should be set up to run periodically via cron job.
 * 
 * Example crontab entry:
 * * * * * * php /path/to/process_sms_queue.php --batch-size=50 --max-runtime=55 >> /var/log/sms_queue.log 2>&1
 */

require_once __DIR__ . '/../../vendor/autoload.php';

// Parse command-line arguments
$options = getopt('', ['batch-size:', 'max-runtime:', 'delay:', 'verbose', 'help']);

if (isset($options['help'])) {
    echo "SMS Queue Worker\n";
    echo "Usage: php process_sms_queue.php [options]\n\n";
    echo "Options:\n";
    echo "  --batch-size=N     Process N messages per batch (default: 50)\n";
    echo "  --max-runtime=N    Maximum runtime in seconds (default: 55)\n";
    echo "  --delay=N          Delay between batches in seconds (default: 1)\n";
    echo "  --verbose          Enable verbose output\n";
    echo "  --help             Display this help message\n";
    exit(0);
}

$batchSize = isset($options['batch-size']) ? (int)$options['batch-size'] : 50;
$maxRuntime = isset($options['max-runtime']) ? (int)$options['max-runtime'] : 55;
$delay = isset($options['delay']) ? (int)$options['delay'] : 1;
$verbose = isset($options['verbose']);

// Create lock file to prevent concurrent execution
$lockFile = sys_get_temp_dir() . '/sms_queue_worker.lock';

if (file_exists($lockFile)) {
    $pid = file_get_contents($lockFile);
    // Check if the process is still running
    if (posix_kill((int)$pid, 0)) {
        echo date('Y-m-d H:i:s') . " - Another instance is already running with PID: $pid\n";
        exit(0);
    } else {
        echo date('Y-m-d H:i:s') . " - Found stale lock file. Previous process is not running. Proceeding.\n";
    }
}

// Write current PID to lock file
file_put_contents($lockFile, getmypid());

try {
    // Initialize container
    $container = new \App\GraphQL\DIContainer();
    
    // Get services
    $smsQueueService = $container->get(\App\Services\Interfaces\SMSQueueServiceInterface::class);
    $logger = $container->get(\Psr\Log\LoggerInterface::class);
    
    $logger->info('SMS Queue Worker started', [
        'pid' => getmypid(),
        'batchSize' => $batchSize,
        'maxRuntime' => $maxRuntime,
        'delay' => $delay
    ]);
    
    if ($verbose) {
        echo date('Y-m-d H:i:s') . " - SMS Queue Worker started\n";
    }
    
    // Get initial stats
    $startStats = $smsQueueService->getQueueStats();
    if ($verbose) {
        echo date('Y-m-d H:i:s') . " - Initial queue stats: " . 
             "Pending: {$startStats['pending']}, " . 
             "Processing: {$startStats['processing']}, " . 
             "Total: {$startStats['total']}\n";
    }
    
    $startTime = time();
    $totalSent = 0;
    $totalFailed = 0;
    $batchesProcessed = 0;
    
    // Process batches until maximum runtime is reached or no more pending messages
    while (time() - $startTime < $maxRuntime) {
        // Process a batch
        $result = $smsQueueService->processNextBatch($batchSize);
        $batchesProcessed++;
        
        $totalSent += $result['sent'];
        $totalFailed += $result['failed'];
        
        if ($verbose) {
            echo date('Y-m-d H:i:s') . " - Batch #{$batchesProcessed}: " . 
                 "Sent: {$result['sent']}, " . 
                 "Failed: {$result['failed']}, " . 
                 "Total: {$result['total']}\n";
        }
        
        // If no messages were processed, no need to continue
        if ($result['total'] === 0) {
            if ($verbose) {
                echo date('Y-m-d H:i:s') . " - No more pending messages. Exiting.\n";
            }
            break;
        }
        
        // Delay between batches
        if ($delay > 0 && time() - $startTime < $maxRuntime) {
            sleep($delay);
        }
    }
    
    // Get final stats
    $endStats = $smsQueueService->getQueueStats();
    $runtime = time() - $startTime;
    
    $logger->info('SMS Queue Worker finished', [
        'batchesProcessed' => $batchesProcessed,
        'sent' => $totalSent,
        'failed' => $totalFailed,
        'runtime' => $runtime,
        'pendingRemaining' => $endStats['pending']
    ]);
    
    if ($verbose) {
        echo date('Y-m-d H:i:s') . " - SMS Queue Worker finished\n";
        echo "Summary:\n";
        echo "- Runtime: {$runtime} seconds\n";
        echo "- Batches processed: {$batchesProcessed}\n";
        echo "- SMS sent: {$totalSent}\n";
        echo "- SMS failed: {$totalFailed}\n";
        echo "- Pending messages remaining: {$endStats['pending']}\n";
    }
    
} catch (\Exception $e) {
    if (isset($logger)) {
        $logger->error('SMS Queue Worker error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
    
    echo date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
} finally {
    // Always remove lock file on exit
    if (file_exists($lockFile)) {
        unlink($lockFile);
    }
}