<?php

declare(strict_types=1);

use App\Services\WhatsApp\CircuitBreaker\CircuitBreaker;
use App\Services\WhatsApp\CircuitBreaker\InMemoryCircuitBreakerStore;
use App\Services\WhatsApp\Retry\RetryPolicy;
use App\Services\WhatsApp\ResilientWhatsAppClient;
use GuzzleHttp\Client;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

return [
    // Circuit Breaker Store
    'whatsapp.circuit_breaker.store' => function () {
        return new InMemoryCircuitBreakerStore();
    },
    
    // Circuit Breaker
    'whatsapp.circuit_breaker' => function (ContainerInterface $c) {
        return new CircuitBreaker(
            name: 'whatsapp_api',
            failureThreshold: 5,        // Ouvrir après 5 échecs
            successThreshold: 2,        // Fermer après 2 succès en HALF_OPEN
            timeout: 60,               // Essayer de rouvrir après 60 secondes
            stateStore: $c->get('whatsapp.circuit_breaker.store')
        );
    },
    
    // Retry Policy
    'whatsapp.retry_policy' => function (ContainerInterface $c) {
        $logger = $c->get(LoggerInterface::class);
        
        return new RetryPolicy(
            maxAttempts: 3,
            baseDelayMs: 1000,         // 1 seconde
            multiplier: 2.0,           // Doubler le délai à chaque tentative
            maxDelayMs: 10000,         // Maximum 10 secondes
            retryableExceptions: [
                \RuntimeException::class,
                \GuzzleHttp\Exception\ConnectException::class,
                \GuzzleHttp\Exception\ServerException::class,
                \GuzzleHttp\Exception\RequestException::class,
            ],
            onRetry: function (\Throwable $e, int $attempt, int $delayMs) use ($logger) {
                $logger->warning('WhatsApp API retry attempt', [
                    'attempt' => $attempt,
                    'delay_ms' => $delayMs,
                    'error_type' => get_class($e),
                    'error_message' => $e->getMessage(),
                ]);
            }
        );
    },
    
    // HTTP Client avec timeouts configurés
    'whatsapp.http_client' => function () {
        return new Client([
            'timeout' => 30,           // Timeout total de 30 secondes
            'connect_timeout' => 10,   // Timeout de connexion de 10 secondes
            'http_errors' => true,     // Lancer des exceptions sur erreurs HTTP
        ]);
    },
    
    // Resilient WhatsApp Client
    ResilientWhatsAppClient::class => function (ContainerInterface $c) {
        $config = $c->get('whatsapp.config');
        
        return new ResilientWhatsAppClient(
            httpClient: $c->get('whatsapp.http_client'),
            circuitBreaker: $c->get('whatsapp.circuit_breaker'),
            retryPolicy: $c->get('whatsapp.retry_policy'),
            logger: $c->get(LoggerInterface::class),
            baseUrl: $config['api_url'] ?? 'https://graph.facebook.com/v22.0',
            accessToken: $config['access_token'] ?? $_ENV['WHATSAPP_ACCESS_TOKEN'] ?? ''
        );
    },
];