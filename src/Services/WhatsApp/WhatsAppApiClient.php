<?php

namespace App\Services\WhatsApp;

use App\Services\Interfaces\WhatsApp\WhatsAppApiClientInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
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
        
        // S'assurer que la base_url se termine par un '/'
        $baseUrl = $config['base_url'] ?? 'https://graph.facebook.com';
        if (substr($baseUrl, -1) !== '/') {
            $baseUrl .= '/';
        }
        
        $this->logger->debug('Initialisation du client WhatsApp API', [
            'base_url' => $baseUrl,
            'api_version' => $config['api_version'] ?? 'Non défini',
            'phone_number_id' => $config['phone_number_id'] ?? 'Non défini'
        ]);
        
        $this->httpClient = new Client([
            'base_uri' => $baseUrl,
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
        // Vérification des paramètres requis et définir une valeur par défaut pour api_version
        if (empty($this->config['api_version'])) {
            $this->logger->warning('api_version manquante, utilisation de la version v22.0 par défaut');
            $this->config['api_version'] = 'v22.0'; // Version par défaut
        }
        
        if (empty($this->config['phone_number_id'])) {
            throw new \Exception('Configuration WhatsApp incomplète: phone_number_id manquant');
        }
        
        // Utilisation de l'URL complète plutôt que d'un chemin relatif
        $baseUrl = $this->config['base_url'] ?? 'https://graph.facebook.com';
        // Nettoyer le baseUrl pour éviter les double slashes
        $baseUrl = rtrim($baseUrl, '/');
        
        // Garantir que les valeurs de configuration sont non-vides
        $apiVersion = $this->config['api_version'];
        $phoneNumberId = $this->config['phone_number_id'];
        
        $endpoint = sprintf('%s/%s/%s/messages', 
            $baseUrl,
            $apiVersion,
            $phoneNumberId
        );
        
        // Log pour débogage
        $this->logger->debug('Préparation envoi message WhatsApp', [
            'endpoint_complet' => $endpoint,
            'base_url' => $baseUrl,
            'api_version' => $this->config['api_version'],
            'phone_number_id' => $this->config['phone_number_id'],
            'access_token_length' => isset($this->config['access_token']) ? strlen($this->config['access_token']) : 0
        ]);
        
        try {
            // Utilisation de l'URL complète plutôt que de compter sur base_uri
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
            $logData = [
                'endpoint' => $endpoint,
                'payload' => $payload,
                'error' => $e->getMessage(),
            ];
            
            // Vérifier si l'exception est une RequestException qui possède la méthode hasResponse()
            if ($e instanceof RequestException) {
                $logData['response'] = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null;
            } else {
                $logData['response'] = null;
            }
            
            $this->logger->error('Erreur API WhatsApp', $logData);
            
            throw new \Exception('Erreur API WhatsApp : ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function uploadMedia(string $filePath, string $mimeType): string
    {
        // Vérification des paramètres requis et définir une valeur par défaut pour api_version
        if (empty($this->config['api_version'])) {
            $this->logger->warning('api_version manquante, utilisation de la version v22.0 par défaut');
            $this->config['api_version'] = 'v22.0'; // Version par défaut
        }
        
        if (empty($this->config['phone_number_id'])) {
            throw new \Exception('Configuration WhatsApp incomplète: phone_number_id manquant');
        }
        
        // Construction de l'URL complète
        $baseUrl = rtrim($this->config['base_url'] ?? 'https://graph.facebook.com', '/');
        $endpoint = sprintf('%s/%s/%s/media', 
            $baseUrl,
            $this->config['api_version'],
            $this->config['phone_number_id']
        );
        
        $this->logger->debug('Préparation upload média WhatsApp', [
            'endpoint_complet' => $endpoint,
            'file' => $filePath,
            'mime_type' => $mimeType
        ]);
        
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
        // Vérification des paramètres requis et définir une valeur par défaut pour api_version
        if (empty($this->config['api_version'])) {
            $this->logger->warning('api_version manquante, utilisation de la version v22.0 par défaut');
            $this->config['api_version'] = 'v22.0'; // Version par défaut
        }
        
        // Construction de l'URL complète
        $baseUrl = rtrim($this->config['base_url'] ?? 'https://graph.facebook.com', '/');
        $endpoint = sprintf('%s/%s/%s', 
            $baseUrl,
            $this->config['api_version'],
            $mediaId
        );
        
        $this->logger->debug('Obtention URL média WhatsApp', [
            'endpoint_complet' => $endpoint,
            'media_id' => $mediaId
        ]);
        
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
        // Vérifier que les configurations requises sont présentes
        if (empty($this->config['whatsapp_business_account_id'])) {
            $this->logger->error('Configuration WhatsApp incomplète: whatsapp_business_account_id manquant');
            // Afficher les valeurs actuelles pour diagnostiquer le problème
            $this->logger->info('Configuration actuelle', [
                'waba_id' => $this->config['whatsapp_business_account_id'] ?? 'Non défini',
                'api_version' => $this->config['api_version'] ?? 'Non défini',
                'access_token_length' => isset($this->config['access_token']) ? strlen($this->config['access_token']) : 0
            ]);
            throw new \Exception('Configuration WhatsApp incomplète: whatsapp_business_account_id manquant');
        }
        
        if (empty($this->config['api_version'])) {
            $this->logger->warning('api_version manquante, utilisation de la version v22.0 par défaut');
            $this->config['api_version'] = 'v22.0'; // Version par défaut
        }
        
        if (empty($this->config['access_token'])) {
            $this->logger->error('Configuration WhatsApp incomplète: access_token manquant');
            throw new \Exception('Configuration WhatsApp incomplète: access_token manquant');
        }

        // Construire l'endpoint complet pour l'API
        $baseUrl = rtrim($this->config['base_url'] ?? 'https://graph.facebook.com', '/');
        $endpoint = sprintf('%s/%s/%s/message_templates', 
            $baseUrl,
            $this->config['api_version'],
            $this->config['whatsapp_business_account_id']
        );
        
        // Log des tentatives pour traçage et débogage
        $this->logger->info('Récupération des templates WhatsApp', [
            'endpoint_complet' => $endpoint,
            'base_url' => $baseUrl,
            'waba_id' => $this->config['whatsapp_business_account_id'],
            'api_version' => $this->config['api_version'],
            'token_length' => strlen($this->config['access_token'])
        ]);
        
        // 1. Première tentative: utilisation de la méthode Guzzle HTTP client
        try {
            // Faire la requête avec gestion des timeouts
            $response = $this->httpClient->get($endpoint, [
                'query' => [
                    'limit' => 100
                ],
                'timeout' => 30, // Timeout assez élevé pour récupérer une réponse complète
                'connect_timeout' => 10, // Timeout de connexion initial
                'verify' => false // Désactiver la vérification SSL pour contourner les problèmes potentiels
            ]);
            
            // Obtenir et décoder le contenu
            $content = $response->getBody()->getContents();
            $this->logger->debug('Réponse API brute via Guzzle', [
                'status_code' => $response->getStatusCode(),
                'content_length' => strlen($content),
                'content_preview' => substr($content, 0, 100) . (strlen($content) > 100 ? '...' : '')
            ]);
            
            // Décoder le JSON
            $result = json_decode($content, true);
            
            // Vérifier si le décodage a échoué
            if ($result === null && json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Erreur décodage JSON', [
                    'error' => json_last_error_msg(),
                    'content_preview' => substr($content, 0, 100) . '...'
                ]);
                throw new \Exception('Erreur décodage JSON: ' . json_last_error_msg());
            }
            
            // Vérifier que $result est un tableau
            if (!is_array($result)) {
                $this->logger->error('Réponse API invalide: pas un tableau', [
                    'type' => gettype($result)
                ]);
                throw new \Exception('Réponse API invalide: pas un tableau, type=' . gettype($result));
            }
            
            // Vérifier la présence de la clé 'data'
            if (!isset($result['data']) || !is_array($result['data'])) {
                // Vérifier s'il y a un message d'erreur dans la réponse
                if (isset($result['error'])) {
                    $errorMessage = isset($result['error']['message']) ? 
                        $result['error']['message'] : 
                        (is_string($result['error']) ? $result['error'] : json_encode($result['error']));
                    
                    $errorCode = isset($result['error']['code']) ? $result['error']['code'] : 'UNKNOWN';
                    
                    $this->logger->error('API Meta a retourné une erreur', [
                        'error_code' => $errorCode,
                        'error_message' => $errorMessage,
                        'full_error' => $result['error']
                    ]);
                    
                    throw new \Exception('Erreur API Meta [' . $errorCode . ']: ' . $errorMessage);
                }
                
                $this->logger->warning('Clé "data" manquante ou non-array dans la réponse API', [
                    'keys' => is_array($result) ? array_keys($result) : 'N/A',
                    'data_type' => isset($result['data']) ? gettype($result['data']) : 'N/A'
                ]);
                throw new \Exception('Clé "data" manquante ou non-array dans la réponse API');
            }
            
            // Log du succès
            $this->logger->info('Templates WhatsApp récupérés avec succès via Guzzle', [
                'count' => count($result['data'])
            ]);
            
            return $result['data'];
        } 
        catch (GuzzleException $e) {
            // Log détaillé de l'erreur Guzzle
            $logData = [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
            
            // Vérifier si l'exception est une RequestException qui possède la méthode hasResponse()
            if ($e instanceof RequestException) {
                $logData['has_response'] = $e->hasResponse() ? 'Oui' : 'Non';
                
                if ($e->hasResponse()) {
                    $errorResponse = $e->getResponse()->getBody()->getContents();
                    $logData['response'] = $errorResponse;
                    
                    // Essayer de décoder la réponse d'erreur JSON
                    $decodedError = json_decode($errorResponse, true);
                    if (is_array($decodedError) && isset($decodedError['error'])) {
                        $logData['error_details'] = $decodedError['error'];
                    }
                }
            }
            
            $this->logger->error('Erreur réseau Guzzle lors de la récupération des templates WhatsApp', $logData);
            
            // Si Guzzle échoue, essayer la méthode cURL directe avant de propager l'exception
            $this->logger->info('Tentative de fallback avec cURL direct après échec Guzzle');
        } 
        catch (\Exception $e) {
            // Log détaillé des autres exceptions
            $this->logger->error('Exception lors de la récupération des templates WhatsApp via Guzzle', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Si nous avons une exception générique, essayer cURL avant de propager
            $this->logger->info('Tentative de fallback avec cURL direct après exception');
        }
        
        // 2. Deuxième tentative: utilisation de cURL directement
        try {
            $this->logger->info('Tentative de récupération des templates avec cURL direct');
            $result = $this->testDirectApiCall();
            
            if ($result !== null && isset($result['data']) && is_array($result['data'])) {
                $this->logger->info('Templates récupérés avec succès via cURL direct', [
                    'count' => count($result['data'])
                ]);
                return $result['data'];
            } else {
                $errorInfo = [];
                
                if (is_array($result)) {
                    if (isset($result['error'])) {
                        $errorInfo = [
                            'error' => $result['error'],
                            'message' => isset($result['error']['message']) ? $result['error']['message'] : 'Erreur inconnue'
                        ];
                    } else {
                        $errorInfo = ['keys' => array_keys($result)];
                    }
                } else {
                    $errorInfo = ['result_type' => gettype($result)];
                }
                
                $this->logger->error('cURL direct a échoué ou a retourné un format invalide', $errorInfo);
                throw new \Exception('cURL direct a échoué ou a retourné un format invalide: ' . json_encode($errorInfo));
            }
        } catch (\Throwable $e) {
            // Log final de l'erreur
            $this->logger->error('Échec complet de la récupération des templates WhatsApp', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Propager l'exception en dernière instance
            throw new \Exception('Impossible de récupérer les templates WhatsApp après plusieurs tentatives: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
    
    /**
     * Méthode de test direct utilisant cURL pour valider la connectivité API
     * 
     * @return array|null Données JSON décodées ou null en cas d'erreur
     */
    private function testDirectApiCall(): ?array
    {
        $this->logger->info('Test direct avec cURL', [
            'waba_id' => $this->config['whatsapp_business_account_id'],
            'api_version' => $this->config['api_version']
        ]);
        
        try {
            // Construire l'URL
            $url = 'https://graph.facebook.com/' . 
                $this->config['api_version'] . '/' . 
                $this->config['whatsapp_business_account_id'] . 
                '/message_templates?limit=100';
            
            // Initialiser cURL
            $ch = curl_init();
            
            // Configurer la requête
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->config['access_token'],
                'Content-Type: application/json'
            ]);
            
            // Exécuter la requête
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            // Fermer la ressource cURL
            curl_close($ch);
            
            // Vérifier les erreurs
            if ($error) {
                $this->logger->error('Erreur cURL lors du test direct', [
                    'error' => $error,
                    'http_code' => $httpCode
                ]);
                return null;
            }
            
            // Vérifier le code HTTP
            if ($httpCode < 200 || $httpCode >= 300) {
                $this->logger->error('Réponse HTTP non valide lors du test direct', [
                    'http_code' => $httpCode,
                    'response' => $response
                ]);
                return null;
            }
            
            // Décoder la réponse JSON
            $decodedResponse = json_decode($response, true);
            
            // Vérifier que la réponse est bien un tableau avec une clé 'data'
            if (!is_array($decodedResponse) || !isset($decodedResponse['data']) || !is_array($decodedResponse['data'])) {
                $this->logger->error('Réponse JSON invalide lors du test direct', [
                    'response' => substr($response, 0, 500) . '...'
                ]);
                return null;
            }
            
            // Log du succès
            $this->logger->info('Test direct cURL réussi', [
                'count' => count($decodedResponse['data'])
            ]);
            
            return $decodedResponse;
        } catch (\Throwable $e) {
            $this->logger->error('Exception lors du test direct avec cURL', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
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