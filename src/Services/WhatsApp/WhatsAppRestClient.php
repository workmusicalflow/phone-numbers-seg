<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Entities\User;
use App\Services\Interfaces\WhatsApp\WhatsAppMonitoringServiceInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

/**
 * Client REST pour accéder aux endpoints WhatsApp de notre propre API
 * 
 * Cette classe fournit une couche d'abstraction pour communiquer avec notre 
 * API REST WhatsApp qui implémente des mécanismes de fallback robustes.
 */
class WhatsAppRestClient
{
    /**
     * @var Client
     */
    private Client $httpClient;
    
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    
    /**
     * @var string
     */
    private string $baseUrl;
    
    /**
     * @var WhatsAppMonitoringServiceInterface|null
     */
    private ?WhatsAppMonitoringServiceInterface $monitoringService = null;
    
    /**
     * Constructeur
     * 
     * @param LoggerInterface $logger
     * @param string $baseUrl URL de base de l'API REST (par défaut: l'URL de l'application)
     * @param WhatsAppMonitoringServiceInterface|null $monitoringService Service de monitoring (optionnel)
     */
    public function __construct(
        LoggerInterface $logger, 
        string $baseUrl = '',
        ?WhatsAppMonitoringServiceInterface $monitoringService = null
    ) {
        $this->logger = $logger;
        $this->baseUrl = !empty($baseUrl) ? $baseUrl : (isset($_SERVER['HTTP_HOST']) ? 'http://' . $_SERVER['HTTP_HOST'] : 'http://localhost:8000');
        $this->monitoringService = $monitoringService;
        
        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => 10,
            'http_errors' => false,
        ]);
    }
    
    /**
     * Récupère les templates WhatsApp approuvés
     * 
     * @param User $user Utilisateur authentifié
     * @param array $filters Filtres optionnels (name, language, category, status, useCache, forceRefresh)
     * @return array Templates WhatsApp
     * @throws \Exception Si une erreur se produit
     */
    public function getApprovedTemplates(User $user, array $filters = []): array
    {
        $this->logger->info('Récupération des templates WhatsApp via REST client', [
            'user_id' => $user->getId(),
            'filters' => $filters
        ]);
        
        $queryParams = [];
        
        // Ajouter les filtres aux paramètres de requête
        if (isset($filters['name']) && !empty($filters['name'])) {
            $queryParams['name'] = $filters['name'];
        }
        
        if (isset($filters['language']) && !empty($filters['language'])) {
            $queryParams['language'] = $filters['language'];
        }
        
        if (isset($filters['category']) && !empty($filters['category'])) {
            $queryParams['category'] = $filters['category'];
        }
        
        if (isset($filters['status']) && !empty($filters['status'])) {
            $queryParams['status'] = $filters['status'];
        }
        
        if (isset($filters['useCache'])) {
            $queryParams['use_cache'] = $filters['useCache'] ? '1' : '0';
        }
        
        if (isset($filters['forceRefresh'])) {
            $queryParams['force_refresh'] = $filters['forceRefresh'] ? '1' : '0';
        }
        
        $startTime = microtime(true);
        $success = false;
        $errorMessage = null;
        
        try {
            // Construire l'URL avec les paramètres de requête
            $endpointUrl = '/api.php?endpoint=whatsapp/templates/approved';
            if (!empty($queryParams)) {
                $endpointUrl .= '&' . http_build_query($queryParams);
            }
            
            // Effectuer la requête avec le jeton d'authentification
            $response = $this->httpClient->request('GET', $endpointUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getAuthToken($user),
                    'Accept' => 'application/json',
                ]
            ]);
            
            // Analyser la réponse
            $statusCode = $response->getStatusCode();
            $body = (string)$response->getBody();
            $data = json_decode($body, true);
            
            // Vérifier si la réponse est valide
            if ($statusCode !== 200 || !is_array($data)) {
                $errorMessage = 'Réponse API invalide: ' . $statusCode;
                $this->logger->error('Réponse API invalide pour templates', [
                    'status_code' => $statusCode,
                    'body' => $body
                ]);
                throw new \Exception($errorMessage);
            }
            
            // Vérifier si la réponse contient une erreur
            if (isset($data['status']) && $data['status'] === 'error') {
                $errorMessage = $data['message'] ?? 'Erreur API inconnue';
                $this->logger->error('Erreur API pour templates', [
                    'message' => $errorMessage
                ]);
                throw new \Exception($errorMessage);
            }
            
            // Vérifier si la réponse contient des templates et des métadonnées
            if (!isset($data['templates']) || !isset($data['meta'])) {
                $errorMessage = 'Format de réponse API inattendu';
                $this->logger->error('Format de réponse API inattendu pour templates', [
                    'data' => $data
                ]);
                throw new \Exception($errorMessage);
            }
            
            // Log des métadonnées pour monitoring
            $this->logger->info('Templates récupérés avec succès via REST client', [
                'source' => $data['meta']['source'] ?? 'unknown',
                'used_fallback' => $data['meta']['usedFallback'] ?? false,
                'count' => $data['count'] ?? count($data['templates'])
            ]);
            
            $success = true;
            return $data['templates'];
        } catch (RequestException $e) {
            $errorMessage = 'Erreur de communication avec l\'API: ' . $e->getMessage();
            $this->logger->error('Erreur HTTP lors de la récupération des templates', [
                'error' => $e->getMessage(),
                'url' => $endpointUrl ?? '/api.php?endpoint=whatsapp/templates/approved'
            ]);
            throw new \Exception($errorMessage);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $this->logger->error('Erreur lors de la récupération des templates via REST client', [
                'error' => $errorMessage
            ]);
            throw $e;
        } finally {
            // Enregistrer les métriques de performance
            $endTime = microtime(true);
            $duration = ($endTime - $startTime) * 1000; // Convertir en millisecondes
            
            if ($this->monitoringService !== null) {
                $this->monitoringService->recordApiPerformance(
                    $user,
                    'getApprovedTemplates',
                    $duration,
                    $success,
                    $success ? null : $errorMessage
                );
            }
        }
    }
    
    /**
     * Récupère un template spécifique par son ID
     * 
     * @param User $user Utilisateur authentifié
     * @param string $templateId ID du template
     * @return array Détails du template
     * @throws \Exception Si une erreur se produit
     */
    public function getTemplateById(User $user, string $templateId): array
    {
        $this->logger->info('Récupération du template par ID via REST client', [
            'user_id' => $user->getId(),
            'template_id' => $templateId
        ]);
        
        $startTime = microtime(true);
        $success = false;
        $errorMessage = null;
        
        try {
            $endpointUrl = '/api.php?endpoint=whatsapp/templates/' . urlencode($templateId);
            
            // Effectuer la requête avec le jeton d'authentification
            $response = $this->httpClient->request('GET', $endpointUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getAuthToken($user),
                    'Accept' => 'application/json',
                ]
            ]);
            
            // Analyser la réponse
            $statusCode = $response->getStatusCode();
            $body = (string)$response->getBody();
            $data = json_decode($body, true);
            
            // Vérifier si la réponse est valide
            if ($statusCode !== 200 || !is_array($data)) {
                $errorMessage = 'Réponse API invalide: ' . $statusCode;
                $this->logger->error('Réponse API invalide pour template par ID', [
                    'status_code' => $statusCode,
                    'body' => $body
                ]);
                throw new \Exception($errorMessage);
            }
            
            // Vérifier si la réponse contient une erreur
            if (isset($data['status']) && $data['status'] === 'error') {
                $errorMessage = $data['message'] ?? 'Erreur API inconnue';
                $this->logger->error('Erreur API pour template par ID', [
                    'message' => $errorMessage
                ]);
                throw new \Exception($errorMessage);
            }
            
            // Vérifier si la réponse contient le template
            if (!isset($data['template'])) {
                $errorMessage = 'Format de réponse API inattendu ou template non trouvé';
                $this->logger->error('Format de réponse API inattendu pour template par ID', [
                    'data' => $data
                ]);
                throw new \Exception($errorMessage);
            }
            
            $success = true;
            return $data['template'];
        } catch (RequestException $e) {
            $errorMessage = 'Erreur de communication avec l\'API: ' . $e->getMessage();
            $this->logger->error('Erreur HTTP lors de la récupération du template par ID', [
                'error' => $e->getMessage(),
                'template_id' => $templateId
            ]);
            throw new \Exception($errorMessage);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $this->logger->error('Erreur lors de la récupération du template par ID via REST client', [
                'error' => $errorMessage,
                'template_id' => $templateId
            ]);
            throw $e;
        } finally {
            // Enregistrer les métriques de performance
            $endTime = microtime(true);
            $duration = ($endTime - $startTime) * 1000; // Convertir en millisecondes
            
            if ($this->monitoringService !== null) {
                $this->monitoringService->recordApiPerformance(
                    $user,
                    'getTemplateById',
                    $duration,
                    $success,
                    $success ? null : $errorMessage
                );
            }
        }
    }
    
    /**
     * Obtient le jeton d'authentification pour l'utilisateur
     * 
     * @param User $user Utilisateur
     * @return string Jeton d'authentification
     */
    private function getAuthToken(User $user): string
    {
        // Dans un environnement réel, on utiliserait une méthode pour générer ou récupérer
        // le jeton JWT pour l'utilisateur
        // Pour les appels internes, on pourrait utiliser un jeton de service
        
        // Simplement pour la démonstration, on va générer un jeton fictif
        // qui contient l'ID de l'utilisateur
        return 'internal_token_' . $user->getId();
    }
}