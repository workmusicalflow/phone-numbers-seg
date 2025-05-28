<?php

declare(strict_types=1);

namespace Tests\WhatsApp;

use App\Services\WhatsApp\CircuitBreaker\CircuitBreaker;
use App\Services\WhatsApp\CircuitBreaker\CircuitBreakerOpenException;
use App\Services\WhatsApp\CircuitBreaker\InMemoryCircuitBreakerStore;
use PHPUnit\Framework\TestCase;

class CircuitBreakerTest extends TestCase
{
    private CircuitBreaker $circuitBreaker;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->circuitBreaker = new CircuitBreaker(
            name: 'test',
            failureThreshold: 3,
            successThreshold: 2,
            timeout: 1, // 1 seconde pour les tests
            stateStore: new InMemoryCircuitBreakerStore()
        );
    }
    
    public function testCircuitBreakerStartsClosed(): void
    {
        $this->assertEquals('closed', $this->circuitBreaker->getState());
    }
    
    public function testSuccessfulCallsKeepCircuitClosed(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $result = $this->circuitBreaker->call(fn() => 'success');
            $this->assertEquals('success', $result);
        }
        
        $this->assertEquals('closed', $this->circuitBreaker->getState());
    }
    
    public function testCircuitOpensAfterFailureThreshold(): void
    {
        // Faire échouer 3 fois (seuil)
        for ($i = 0; $i < 3; $i++) {
            try {
                $this->circuitBreaker->call(function () {
                    throw new \RuntimeException('Test failure');
                });
            } catch (\RuntimeException $e) {
                // Expected
            }
        }
        
        $this->assertEquals('open', $this->circuitBreaker->getState());
        
        // Les appels suivants doivent échouer immédiatement
        $this->expectException(CircuitBreakerOpenException::class);
        $this->circuitBreaker->call(fn() => 'should not execute');
    }
    
    public function testCircuitTransitionsToHalfOpenAfterTimeout(): void
    {
        // Ouvrir le circuit
        for ($i = 0; $i < 3; $i++) {
            try {
                $this->circuitBreaker->call(function () {
                    throw new \RuntimeException('Test failure');
                });
            } catch (\RuntimeException $e) {
                // Expected
            }
        }
        
        $this->assertEquals('open', $this->circuitBreaker->getState());
        
        // Attendre le timeout
        sleep(2);
        
        // Le prochain appel devrait passer en HALF_OPEN
        $result = $this->circuitBreaker->call(fn() => 'success');
        $this->assertEquals('success', $result);
        
        // Toujours en HALF_OPEN car il faut 2 succès
        $this->assertEquals('half_open', $this->circuitBreaker->getState());
        
        // Un deuxième succès devrait fermer le circuit
        $result = $this->circuitBreaker->call(fn() => 'success');
        $this->assertEquals('success', $result);
        $this->assertEquals('closed', $this->circuitBreaker->getState());
    }
    
    public function testHalfOpenFailureReopensCircuit(): void
    {
        // Ouvrir le circuit
        for ($i = 0; $i < 3; $i++) {
            try {
                $this->circuitBreaker->call(function () {
                    throw new \RuntimeException('Test failure');
                });
            } catch (\RuntimeException $e) {
                // Expected
            }
        }
        
        // Attendre le timeout
        sleep(2);
        
        // Échec en HALF_OPEN devrait rouvrir immédiatement
        try {
            $this->circuitBreaker->call(function () {
                throw new \RuntimeException('Test failure in half-open');
            });
        } catch (\RuntimeException $e) {
            // Expected
        }
        
        $this->assertEquals('open', $this->circuitBreaker->getState());
    }
    
    public function testResetClosesCircuit(): void
    {
        // Ouvrir le circuit
        for ($i = 0; $i < 3; $i++) {
            try {
                $this->circuitBreaker->call(function () {
                    throw new \RuntimeException('Test failure');
                });
            } catch (\RuntimeException $e) {
                // Expected
            }
        }
        
        $this->assertEquals('open', $this->circuitBreaker->getState());
        
        // Reset
        $this->circuitBreaker->reset();
        
        $this->assertEquals('closed', $this->circuitBreaker->getState());
        
        // Les appels devraient fonctionner à nouveau
        $result = $this->circuitBreaker->call(fn() => 'success');
        $this->assertEquals('success', $result);
    }
}