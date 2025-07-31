<?php

declare(strict_types=1);

namespace Tests\WhatsApp;

use App\Services\WhatsApp\Retry\RetryPolicy;
use PHPUnit\Framework\TestCase;

class RetryPolicyTest extends TestCase
{
    public function testSuccessfulOperationDoesNotRetry(): void
    {
        $attempts = 0;
        $policy = new RetryPolicy(maxAttempts: 3);
        
        $result = $policy->execute(function () use (&$attempts) {
            $attempts++;
            return 'success';
        });
        
        $this->assertEquals('success', $result);
        $this->assertEquals(1, $attempts);
    }
    
    public function testRetriesOnRetryableException(): void
    {
        $attempts = 0;
        $policy = new RetryPolicy(
            maxAttempts: 3,
            baseDelayMs: 10, // Délai court pour les tests
            retryableExceptions: [\RuntimeException::class]
        );
        
        $result = $policy->execute(function () use (&$attempts) {
            $attempts++;
            
            if ($attempts < 3) {
                throw new \RuntimeException('Temporary failure');
            }
            
            return 'success after retries';
        });
        
        $this->assertEquals('success after retries', $result);
        $this->assertEquals(3, $attempts);
    }
    
    public function testThrowsAfterMaxAttempts(): void
    {
        $attempts = 0;
        $policy = new RetryPolicy(
            maxAttempts: 3,
            baseDelayMs: 10,
            retryableExceptions: [\RuntimeException::class]
        );
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Always fails');
        
        $policy->execute(function () use (&$attempts) {
            $attempts++;
            throw new \RuntimeException('Always fails');
        });
        
        $this->assertEquals(3, $attempts);
    }
    
    public function testDoesNotRetryNonRetryableExceptions(): void
    {
        $attempts = 0;
        $policy = new RetryPolicy(
            maxAttempts: 3,
            retryableExceptions: [\RuntimeException::class]
        );
        
        $this->expectException(\InvalidArgumentException::class);
        
        $policy->execute(function () use (&$attempts) {
            $attempts++;
            throw new \InvalidArgumentException('Non-retryable');
        });
        
        $this->assertEquals(1, $attempts);
    }
    
    public function testExponentialBackoff(): void
    {
        $delays = [];
        $policy = new RetryPolicy(
            maxAttempts: 4,
            baseDelayMs: 100,
            multiplier: 2.0,
            maxDelayMs: 1000,
            retryableExceptions: [\RuntimeException::class],
            onRetry: function ($e, $attempt, $delay) use (&$delays) {
                $delays[] = $delay;
            }
        );
        
        try {
            $policy->execute(function () {
                throw new \RuntimeException('Always fails');
            });
        } catch (\RuntimeException $e) {
            // Expected
        }
        
        // Vérifier que les délais augmentent exponentiellement
        $this->assertCount(3, $delays); // 3 retries = 3 delays
        
        // Les délais devraient être approximativement 100, 200, 400
        // (avec jitter, ils peuvent varier de ±25%)
        $this->assertGreaterThan(75, $delays[0]);
        $this->assertLessThan(125, $delays[0]);
        
        $this->assertGreaterThan(150, $delays[1]);
        $this->assertLessThan(250, $delays[1]);
        
        $this->assertGreaterThan(300, $delays[2]);
        $this->assertLessThan(500, $delays[2]);
    }
    
    public function testMaxDelayIsRespected(): void
    {
        $delays = [];
        $policy = new RetryPolicy(
            maxAttempts: 5,
            baseDelayMs: 1000,
            multiplier: 10.0, // Croissance très rapide
            maxDelayMs: 2000,  // Mais plafonnée à 2 secondes
            retryableExceptions: [\RuntimeException::class],
            onRetry: function ($e, $attempt, $delay) use (&$delays) {
                $delays[] = $delay;
            }
        );
        
        try {
            $policy->execute(function () {
                throw new \RuntimeException('Always fails');
            });
        } catch (\RuntimeException $e) {
            // Expected
        }
        
        // Tous les délais devraient être plafonnés à maxDelayMs
        foreach ($delays as $delay) {
            $this->assertLessThanOrEqual(2000, $delay);
        }
    }
    
    public function testOnRetryCallback(): void
    {
        $retryLog = [];
        $policy = new RetryPolicy(
            maxAttempts: 3,
            baseDelayMs: 10,
            retryableExceptions: [\RuntimeException::class],
            onRetry: function (\Throwable $e, int $attempt, int $delay) use (&$retryLog) {
                $retryLog[] = [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                    'delay' => $delay,
                ];
            }
        );
        
        try {
            $policy->execute(function () {
                throw new \RuntimeException('Test error');
            });
        } catch (\RuntimeException $e) {
            // Expected
        }
        
        $this->assertCount(2, $retryLog); // 2 retries après l'échec initial
        $this->assertEquals(1, $retryLog[0]['attempt']);
        $this->assertEquals('Test error', $retryLog[0]['error']);
        $this->assertEquals(2, $retryLog[1]['attempt']);
    }
}