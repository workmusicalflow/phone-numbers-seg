<?php

namespace App\Services\WhatsApp;

use App\Services\Interfaces\WhatsApp\WhatsAppApiClientInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Client pour l'API WhatsApp Business Cloud
 */
class WhatsAppApiClient implements WhatsAppApiClientInterface
{
    private Client $httpClient;
    private LoggerInterface $logger;
    private array $config;
    
    /**
     * Constructeur
     *
     * @param LoggerInterface $logger
     * @param array $config Configuration WhatsApp
     */
    public function __construct(LoggerInterface $logger, array $config)
    {
        $this->logger = $logger;
        $this->config = $config;
        
        $this->httpClient = new Client([
            'base_uri' => $config['base_url'] ?? 'https://graph.facebook.com/',
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $config['access_token'],
                'Content-Type' => 'application/json'
            ]
        ]);
    }
    
    /**
     * {@inheritdoc}
     */
    public function sendMessage(array $payload): array
    {
        $endpoint = $this->config['api_version'] . '/' . $this->config['phone_number_id'] . '/messages';
        
        try {
            $response = $this->httpClient->post($endpoint, [
                'json' => $payload
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            
            $this->logger->info('Message WhatsApp envoyé', [
                'endpoint' => $endpoint,
                'recipient' => $payload['to'] ?? null,
                'type' => $payload['type'] ?? null,
                'message_id' => $result['messages'][0]['id'] ?? null
            ]);
            
            return $result;
            
        } catch (GuzzleException $e) {
            $this->logger->error('Erreur API WhatsApp', [
                'endpoint' => $endpoint,
                'payload' => $payload,
                'error' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null
            ]);
            
            throw new \Exception('Erreur API WhatsApp : ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function uploadMedia(string $filePath, string $mimeType): string
    {
        $endpoint = $this->config['api_version'] . '/' . $this->config['phone_number_id'] . '/media';
        
        try {
            $response = $this->httpClient->post($endpoint, [
                'multipart' => [
                    [
                        'name' => 'messaging_product',
                        'contents' => 'whatsapp'
                    ],
                    [
                        'name' => 'file',
                        'contents' => fopen($filePath, 'r'),
                        'filename' => basename($filePath),
                        'headers' => [
                            'Content-Type' => $mimeType
                        ]
                    ]
                ]
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            
            $this->logger->info('Média WhatsApp uploadé', [
                'file' => $filePath,
                'mime_type' => $mimeType,
                'media_id' => $result['id'] ?? null
            ]);
            
            if (!isset($result['id'])) {
                throw new \Exception('ID du média non retourné par l\'API');
            }
            
            return $result['id'];
            
        } catch (GuzzleException $e) {
            $this->logger->error('Erreur upload média WhatsApp', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            
            throw new \Exception('Erreur upload média : ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function downloadMedia(string $mediaId): array
    {
        // Obtenir d'abord l'URL du média
        $url = $this->getMediaUrl($mediaId);
        
        try {
            $response = $this->httpClient->get($url);
            
            return [
                'content' => $response->getBody()->getContents(),
                'content_type' => $response->getHeader('Content-Type')[0] ?? 'application/octet-stream'
            ];
            
        } catch (GuzzleException $e) {
            $this->logger->error('Erreur téléchargement média WhatsApp', [
                'media_id' => $mediaId,
                'error' => $e->getMessage()
            ]);
            
            throw new \Exception('Erreur téléchargement média : ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function getMediaUrl(string $mediaId): string
    {
        $endpoint = $this->config['api_version'] . '/' . $mediaId;
        
        try {
            $response = $this->httpClient->get($endpoint);
            $result = json_decode($response->getBody()->getContents(), true);
            
            if (!isset($result['url'])) {
                throw new \Exception('URL du média non retournée par l\'API');
            }
            
            return $result['url'];
            
        } catch (GuzzleException $e) {
            $this->logger->error('Erreur obtention URL média WhatsApp', [
                'media_id' => $mediaId,
                'error' => $e->getMessage()
            ]);
            
            throw new \Exception('Erreur obtention URL média : ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function getTemplates(): array
    {
        $endpoint = $this->config['api_version'] . '/' . $this->config['whatsapp_business_account_id'] . '/message_templates';
        
        try {
            $response = $this->httpClient->get($endpoint, [
                'query' => [
                    'limit' => 100
                ]
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            
            return $result['data'] ?? [];
            
        } catch (GuzzleException $e) {
            $this->logger->error('Erreur récupération templates WhatsApp', [
                'error' => $e->getMessage()
            ]);
            
            throw new \Exception('Erreur récupération templates : ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function createTemplate(array $template): array
    {
        $endpoint = $this->config['api_version'] . '/' . $this->config['whatsapp_business_account_id'] . '/message_templates';
        
        try {
            $response = $this->httpClient->post($endpoint, [
                'json' => $template
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            
            $this->logger->info('Template WhatsApp créé', [
                'template_name' => $template['name'] ?? null,
                'template_id' => $result['id'] ?? null
            ]);
            
            return $result;
            
        } catch (GuzzleException $e) {
            $this->logger->error('Erreur création template WhatsApp', [
                'template' => $template,
                'error' => $e->getMessage()
            ]);
            
            throw new \Exception('Erreur création template : ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function deleteTemplate(string $templateName): bool
    {
        $endpoint = $this->config['api_version'] . '/' . $this->config['whatsapp_business_account_id'] . '/message_templates';
        
        try {
            $response = $this->httpClient->delete($endpoint, [
                'query' => [
                    'name' => $templateName
                ]
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            
            $this->logger->info('Template WhatsApp supprimé', [
                'template_name' => $templateName,
                'success' => $result['success'] ?? false
            ]);
            
            return $result['success'] ?? false;
            
        } catch (GuzzleException $e) {
            $this->logger->error('Erreur suppression template WhatsApp', [
                'template_name' => $templateName,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
}