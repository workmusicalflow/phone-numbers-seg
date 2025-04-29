<?php

namespace Tests\Repositories\Doctrine;

use App\Entities\SMSQueue;
use App\Repositories\Doctrine\SMSQueueRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SMSQueueRepositoryTest extends TestCase
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SMSQueueRepository
     */
    private $repository;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        // Create a simple in-memory SQLite database for testing
        $config = Setup::createAnnotationMetadataConfiguration([__DIR__ . '/../../../src/Entities'], true);
        $connection = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];
        
        $this->entityManager = EntityManager::create($connection, $config);
        
        // Create schema
        $tool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $classes = [$this->entityManager->getClassMetadata(SMSQueue::class)];
        $tool->createSchema($classes);
        
        // Create a mock logger
        $this->logger = $this->createMock(LoggerInterface::class);
        
        // Create the repository to test
        $this->repository = new SMSQueueRepository($this->entityManager, $this->logger);
    }

    /**
     * Clean up test environment
     */
    protected function tearDown(): void
    {
        // Drop the schema
        $tool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $classes = [$this->entityManager->getClassMetadata(SMSQueue::class)];
        $tool->dropSchema($classes);
        
        $this->entityManager->close();
        $this->entityManager = null;
        $this->repository = null;
        $this->logger = null;
    }

    /**
     * Test saving and finding an SMS queue entry
     */
    public function testSaveAndFind(): void
    {
        // Create a test SMS queue entry
        $smsQueue = new SMSQueue();
        $smsQueue->setPhoneNumber('+1234567890');
        $smsQueue->setMessage('Test message');
        $smsQueue->setUserId(1);
        $smsQueue->setPriority(SMSQueue::PRIORITY_HIGH);
        $smsQueue->setBatchId('test_batch_' . uniqid());
        $smsQueue->setSenderName('Test Sender');
        $smsQueue->setSenderAddress('tel:+1234567890');
        $smsQueue->setStatus(SMSQueue::STATUS_PENDING);
        $smsQueue->setCreatedAt(new \DateTime());
        $smsQueue->setNextAttemptAt(new \DateTime());
        
        // Save it
        $savedSmsQueue = $this->repository->save($smsQueue);
        
        // Verify it has an ID
        $this->assertNotNull($savedSmsQueue->getId());
        
        // Find it by ID
        $foundSmsQueue = $this->repository->findById($savedSmsQueue->getId());
        
        // Verify the found SMS queue entry
        $this->assertNotNull($foundSmsQueue);
        $this->assertEquals($savedSmsQueue->getId(), $foundSmsQueue->getId());
        $this->assertEquals('+1234567890', $foundSmsQueue->getPhoneNumber());
        $this->assertEquals('Test message', $foundSmsQueue->getMessage());
        $this->assertEquals(SMSQueue::STATUS_PENDING, $foundSmsQueue->getStatus());
    }
    
    /**
     * Test updating the status of an SMS queue entry
     */
    public function testUpdateStatus(): void
    {
        // Create and save a test SMS queue entry
        $smsQueue = new SMSQueue();
        $smsQueue->setPhoneNumber('+1234567890');
        $smsQueue->setMessage('Test message');
        $smsQueue->setStatus(SMSQueue::STATUS_PENDING);
        $smsQueue->setCreatedAt(new \DateTime());
        $smsQueue->setNextAttemptAt(new \DateTime());
        
        $smsQueue = $this->repository->save($smsQueue);
        
        // Update its status
        $result = $this->repository->updateStatus($smsQueue->getId(), SMSQueue::STATUS_PROCESSING);
        
        // Verify the update was successful
        $this->assertTrue($result);
        
        // Find it again to verify the status was updated
        $updatedSmsQueue = $this->repository->findById($smsQueue->getId());
        $this->assertEquals(SMSQueue::STATUS_PROCESSING, $updatedSmsQueue->getStatus());
    }
    
    /**
     * Test finding SMS queue entries by status
     */
    public function testFindByStatus(): void
    {
        // Create and save multiple SMS queue entries with different statuses
        $pendingSmsQueue = new SMSQueue();
        $pendingSmsQueue->setPhoneNumber('+1234567890');
        $pendingSmsQueue->setMessage('Pending message');
        $pendingSmsQueue->setStatus(SMSQueue::STATUS_PENDING);
        $pendingSmsQueue->setCreatedAt(new \DateTime());
        $pendingSmsQueue->setNextAttemptAt(new \DateTime());
        $this->repository->save($pendingSmsQueue);
        
        $processingSmsQueue = new SMSQueue();
        $processingSmsQueue->setPhoneNumber('+1234567890');
        $processingSmsQueue->setMessage('Processing message');
        $processingSmsQueue->setStatus(SMSQueue::STATUS_PROCESSING);
        $processingSmsQueue->setCreatedAt(new \DateTime());
        $processingSmsQueue->setNextAttemptAt(new \DateTime());
        $this->repository->save($processingSmsQueue);
        
        $sentSmsQueue = new SMSQueue();
        $sentSmsQueue->setPhoneNumber('+1234567890');
        $sentSmsQueue->setMessage('Sent message');
        $sentSmsQueue->setStatus(SMSQueue::STATUS_SENT);
        $sentSmsQueue->setCreatedAt(new \DateTime());
        $sentSmsQueue->setNextAttemptAt(new \DateTime());
        $this->repository->save($sentSmsQueue);
        
        // Find by PENDING status
        $pendingEntries = $this->repository->findByStatus(SMSQueue::STATUS_PENDING);
        $this->assertCount(1, $pendingEntries);
        $this->assertEquals('Pending message', $pendingEntries[0]->getMessage());
        
        // Find by PROCESSING status
        $processingEntries = $this->repository->findByStatus(SMSQueue::STATUS_PROCESSING);
        $this->assertCount(1, $processingEntries);
        $this->assertEquals('Processing message', $processingEntries[0]->getMessage());
        
        // Find by SENT status
        $sentEntries = $this->repository->findByStatus(SMSQueue::STATUS_SENT);
        $this->assertCount(1, $sentEntries);
        $this->assertEquals('Sent message', $sentEntries[0]->getMessage());
    }
    
    /**
     * Test counting SMS queue entries by status
     */
    public function testCountByStatus(): void
    {
        // Create and save multiple SMS queue entries with different statuses
        $pendingSmsQueue = new SMSQueue();
        $pendingSmsQueue->setPhoneNumber('+1234567890');
        $pendingSmsQueue->setMessage('Pending message 1');
        $pendingSmsQueue->setStatus(SMSQueue::STATUS_PENDING);
        $pendingSmsQueue->setCreatedAt(new \DateTime());
        $pendingSmsQueue->setNextAttemptAt(new \DateTime());
        $this->repository->save($pendingSmsQueue);
        
        $anotherPendingSmsQueue = new SMSQueue();
        $anotherPendingSmsQueue->setPhoneNumber('+1234567890');
        $anotherPendingSmsQueue->setMessage('Pending message 2');
        $anotherPendingSmsQueue->setStatus(SMSQueue::STATUS_PENDING);
        $anotherPendingSmsQueue->setCreatedAt(new \DateTime());
        $anotherPendingSmsQueue->setNextAttemptAt(new \DateTime());
        $this->repository->save($anotherPendingSmsQueue);
        
        $sentSmsQueue = new SMSQueue();
        $sentSmsQueue->setPhoneNumber('+1234567890');
        $sentSmsQueue->setMessage('Sent message');
        $sentSmsQueue->setStatus(SMSQueue::STATUS_SENT);
        $sentSmsQueue->setCreatedAt(new \DateTime());
        $sentSmsQueue->setNextAttemptAt(new \DateTime());
        $this->repository->save($sentSmsQueue);
        
        // Count by status
        $pendingCount = $this->repository->countByStatus(SMSQueue::STATUS_PENDING);
        $this->assertEquals(2, $pendingCount);
        
        $sentCount = $this->repository->countByStatus(SMSQueue::STATUS_SENT);
        $this->assertEquals(1, $sentCount);
        
        $processingCount = $this->repository->countByStatus(SMSQueue::STATUS_PROCESSING);
        $this->assertEquals(0, $processingCount);
    }
    
    /**
     * Test the batch saving functionality
     */
    public function testSaveBatch(): void
    {
        // Create multiple SMS queue entries
        $smsQueue1 = new SMSQueue();
        $smsQueue1->setPhoneNumber('+1234567890');
        $smsQueue1->setMessage('Batch message 1');
        $smsQueue1->setStatus(SMSQueue::STATUS_PENDING);
        $smsQueue1->setCreatedAt(new \DateTime());
        $smsQueue1->setNextAttemptAt(new \DateTime());
        
        $smsQueue2 = new SMSQueue();
        $smsQueue2->setPhoneNumber('+0987654321');
        $smsQueue2->setMessage('Batch message 2');
        $smsQueue2->setStatus(SMSQueue::STATUS_PENDING);
        $smsQueue2->setCreatedAt(new \DateTime());
        $smsQueue2->setNextAttemptAt(new \DateTime());
        
        // Save them in a batch
        $result = $this->repository->saveBatch([$smsQueue1, $smsQueue2]);
        
        // Verify the batch save was successful
        $this->assertTrue($result);
        
        // Verify both entries have IDs now
        $this->assertNotNull($smsQueue1->getId());
        $this->assertNotNull($smsQueue2->getId());
        
        // Count the total number of entries to verify both were saved
        $count = $this->repository->countByStatus(SMSQueue::STATUS_PENDING);
        $this->assertEquals(2, $count);
    }
    
    /**
     * Test the functionality to find entries by batch ID
     */
    public function testFindByBatchId(): void
    {
        // Create a batch ID
        $batchId = 'test_batch_' . uniqid();
        
        // Create and save multiple SMS queue entries with the same batch ID
        $smsQueue1 = new SMSQueue();
        $smsQueue1->setPhoneNumber('+1234567890');
        $smsQueue1->setMessage('Batch entry 1');
        $smsQueue1->setStatus(SMSQueue::STATUS_PENDING);
        $smsQueue1->setBatchId($batchId);
        $smsQueue1->setCreatedAt(new \DateTime());
        $smsQueue1->setNextAttemptAt(new \DateTime());
        $this->repository->save($smsQueue1);
        
        $smsQueue2 = new SMSQueue();
        $smsQueue2->setPhoneNumber('+0987654321');
        $smsQueue2->setMessage('Batch entry 2');
        $smsQueue2->setStatus(SMSQueue::STATUS_PENDING);
        $smsQueue2->setBatchId($batchId);
        $smsQueue2->setCreatedAt(new \DateTime());
        $smsQueue2->setNextAttemptAt(new \DateTime());
        $this->repository->save($smsQueue2);
        
        // Create another entry with a different batch ID
        $smsQueue3 = new SMSQueue();
        $smsQueue3->setPhoneNumber('+5555555555');
        $smsQueue3->setMessage('Different batch');
        $smsQueue3->setStatus(SMSQueue::STATUS_PENDING);
        $smsQueue3->setBatchId('different_batch');
        $smsQueue3->setCreatedAt(new \DateTime());
        $smsQueue3->setNextAttemptAt(new \DateTime());
        $this->repository->save($smsQueue3);
        
        // Find by batch ID
        $batchEntries = $this->repository->findByBatchId($batchId);
        
        // Verify only the entries with the matching batch ID were returned
        $this->assertCount(2, $batchEntries);
        $this->assertEquals($batchId, $batchEntries[0]->getBatchId());
        $this->assertEquals($batchId, $batchEntries[1]->getBatchId());
    }
}