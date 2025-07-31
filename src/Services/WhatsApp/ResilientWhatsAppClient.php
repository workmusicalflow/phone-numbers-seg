<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Services\WhatsApp\CircuitBreaker\CircuitBreaker;
use App\Services\WhatsApp\CircuitBreaker\CircuitBreakerOpenException;
use App\Services\WhatsApp\Retry\RetryPolicy;
use App\Services\WhatsApp\Retry\RetryableOperation;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Client WhatsApp résilient avec Circuit Breaker et Retry
 */
class ResilientWhatsAppClient
{
    private Client $httpClient;
    private CircuitBreaker $circuitBreaker;
    private RetryPolicy $retryPolicy;
    private LoggerInterface $logger;
    private string $baseUrl;
    private string $accessToken;
    
    public function __construct(
        Client $httpClient,
        CircuitBreaker $circuitBreaker,
        ?RetryPolicy $retryPolicy = null,
        ?LoggerInterface $logger = null,
        string $baseUrl = 'https://graph.facebook.com/v22.0',
        string $accessToken = ''
    ) {
        $this->httpClient = $httpClient;
        $this->circuitBreaker = $circuitBreaker;
        $this->retryPolicy = $retryPolicy ?? $this->createDefaultRetryPolicy();
        $this->logger = $logger ?? new NullLogger();
        $this->baseUrl = $baseUrl;
        $this->accessToken = $accessToken;
    }
    
    /**
     * Envoie un message template avec résilience
     */
    public function sendTemplateMessage(
        string $phoneNumberId,
        array $payload
    ): array {
        $operation = new RetryableOperation(
            'send_template_message',
            fn() => $this->doSendTemplateMessage($phoneNumberId, $payload),
            ['phoneNumberId' => $phoneNumberId, 'template' => $payload['template']['name'] ?? 'unknown']
        );
        
        try {
            return $this->executeWithResilience($operation);
        } catch (CircuitBreakerOpenException $e) {
            $this->logger->error('Circuit breaker is open for WhatsApp API', [
                'operation' => $operation->getName(),
                'context' => $operation->getContext(),
            ]);
            throw new \RuntimeException('WhatsApp API is temporarily unavailable', 0, $e);
        }
    }
    
    /**
     * Récupère les templates approuvés avec résilience
     */
    public function getApprovedTemplates(string $wabaId): array
    {
        $operation = new RetryableOperation(
            'get_approved_templates',
            fn() => $this->doGetApprovedTemplates($wabaId),
            ['wabaId' => $wabaId]
        );
        
        try {
            return $this->executeWithResilience($operation);
        } catch (CircuitBreakerOpenException $e) {
            $this->logger->warning('Circuit breaker is open for WhatsApp API, returning cached templates', [
                'operation' => $operation->getName(),
            ]);
            // Retourner un cache ou une liste vide en cas d'échec
            return [];
        }
    }
    
    /**
     * Upload un média avec résilience
     */
    public function uploadMedia(string $phoneNumberId, string $filePath, string $mimeType): array
    {
        $operation = new RetryableOperation(
            'upload_media',
            fn() => $this->doUploadMedia($phoneNumberId, $filePath, $mimeType),
            ['phoneNumberId' => $phoneNumberId, 'filePath' => $filePath]
        );
        
        return $this->executeWithResilience($operation);
    }
    
    private function executeWithResilience(RetryableOperation $operation)
    {
        return $this->circuitBreaker->call(function () use ($operation) {
            return $this->retryPolicy->execute(function () use ($operation) {
                $this->logger->debug('Executing operation', [
                    'operation' => $operation->getName(),
                    'context' => $operation->getContext(),
                ]);
                
                $startTime = microtime(true);
                
                try {
                    $result = $operation->execute();
                    
                    $duration = (microtime(true) - $startTime) * 1000;
                    $this->logger->info('Operation completed successfully', [
                        'operation' => $operation->getName(),
                        'duration_ms' => $duration,
                    ]);
                    
                    return $result;
                } catch (\Throwable $e) {
                    $duration = (microtime(true) - $startTime) * 1000;
                    $this->logger->error('Operation failed', [
                        'operation' => $operation->getName(),
                        'error' => $e->getMessage(),
                        'duration_ms' => $duration,
                    ]);
                    
                    throw $e;
                }
            });
        });
    }
    
    private function doSendTemplateMessage(string $phoneNumberId, array $payload): array
    {
        $response = $this->httpClient->post(
            "{$this->baseUrl}/{$phoneNumberId}/messages",
            [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]
        );
        
        $body = json_decode($response->getBody()->getContents(), true);
        
        if (!isset($body['messages'][0]['id'])) {
            throw new \RuntimeException('Invalid response from WhatsApp API');
        }
        
        return $body;
    }
    
    private function doGetApprovedTemplates(string $wabaId): array
    {
        $response = $this->httpClient->get(
            "{$this->baseUrl}/{$wabaId}/message_templates",
            [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                ],
                'query' => [
                    'status' => 'APPROVED',
                    'limit' => 100,
                ],
            ]
        );
        
        $body = json_decode($response->getBody()->getContents(), true);
        
        return $body['data'] ?? [];
    }
    
    private function doUploadMedia(string $phoneNumberId, string $filePath, string $mimeType): array
    {
        $response = $this->httpClient->post(
            "{$this->baseUrl}/{$phoneNumberId}/media",
            [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                ],
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => fopen($filePath, 'r'),
                        'filename' => basename($filePath),
                    ],
                    [
                        'name' => 'type',
                        'contents' => $mimeType,
                    ],
                    [
                        'name' => 'messaging_product',
                        'contents' => 'whatsapp',
                    ],
                ],
            ]
        );
        
        return json_decode($response->getBody()->getContents(), true);
    }
    
    private function createDefaultRetryPolicy(): RetryPolicy
    {
        return new RetryPolicy(
            maxAttempts: 3,
            baseDelayMs: 1000,
            multiplier: 2.0,
            maxDelayMs: 10000,
            retryableExceptions: [
                \RuntimeException::class,
                GuzzleException::class,
            ],
            onRetry: function (\Throwable $e, int $attempt, int $delayMs) {
                $this->logger->warning('Retrying operation', [
                    'attempt' => $attempt,
                    'delay_ms' => $delayMs,
                    'error' => $e->getMessage(),
                ]);
            }
        );
    }
}