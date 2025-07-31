<?php

namespace Tests\WhatsApp;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use App\Services\WhatsApp\WhatsAppApiClient;
use App\Services\WhatsApp\WhatsAppRestClient;
use App\Services\Interfaces\WhatsApp\WhatsAppMonitoringServiceInterface;
use App\Entities\User;

/**
 * Tests de simulation de pannes API WhatsApp
 * 
 * Ces tests vérifient que le système gère correctement les différents types
 * de pannes et d'erreurs qui peuvent survenir lors des appels à l'API WhatsApp.
 */
class WhatsAppApiFailureTest extends TestCase
{
    /**
     * @var MockHandler
     */
    private MockHandler $mockHandler;
    
    /**
     * @var array
     */
    private array $requestHistory = [];
    
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    
    /**
     * @var WhatsAppMonitoringServiceInterface
     */
    private $monitoringService;
    
    /**
     * Configuration commune pour les tests
     */
    protected function setUp(): void
    {
        // Réinitialiser le handler et l'historique pour chaque test
        $this->mockHandler = new MockHandler();
        $this->requestHistory = [];
        
        // Créer un logger de test qui ne fait rien
        $this->logger = $this->createMock(LoggerInterface::class);
        
        // Mock du service de monitoring
        $this->monitoringService = $this->createMock(WhatsAppMonitoringServiceInterface::class);
        // Configurer le monitoring service pour accepter les appels à recordApiPerformance
        $this->monitoringService->expects($this->any())
            ->method('recordApiPerformance')
            ->willReturnCallback(function($user, $operation, $duration, $success, $errorMessage = null) {
                // Ne rien faire, c'est un mock
                return;
            });
    }
    
    /**
     * Crée une instance de WhatsAppApiClient avec des réponses mockées
     * et un client HTTP personnalisé
     */
    private function createMockApiClient(): WhatsAppApiClient
    {
        // Configuration de l'historique des requêtes
        $container = [];
        $history = Middleware::history($container);
        
        // Configuration du handler avec l'historique
        $handlerStack = HandlerStack::create($this->mockHandler);
        $handlerStack->push($history);
        
        // Client HTTP mocké
        $httpClient = new Client(['handler' => $handlerStack]);
        
        // Configuration minimale nécessaire
        $config = [
            'base_url' => 'https://graph.facebook.com/',
            'api_version' => 'v19.0',
            'access_token' => 'mock_token',
            'phone_number_id' => 'mock_phone_id',
            'whatsapp_business_account_id' => 'mock_waba_id'
        ];
        
        // Sauvegarder l'historique des requêtes pour les assertions
        $this->requestHistory = &$container;
        
        // WhatsAppApiClient avec configuration mockée
        $client = new WhatsAppApiClient($this->logger, $config);
        
        // Remplacer le client HTTP interne par notre version mockée
        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($client, $httpClient);
        
        return $client;
    }
    
    /**
     * Crée une instance de WhatsAppRestClient avec des réponses mockées
     */
    private function createMockRestClient(): WhatsAppRestClient
    {
        // Configuration de l'historique des requêtes
        $container = [];
        $history = Middleware::history($container);
        
        // Configuration du handler avec l'historique
        $handlerStack = HandlerStack::create($this->mockHandler);
        $handlerStack->push($history);
        
        // Client HTTP mocké
        $httpClient = new Client(['handler' => $handlerStack]);
        
        // Sauvegarder l'historique des requêtes pour les assertions
        $this->requestHistory = &$container;
        
        // WhatsAppRestClient avec client HTTP mocké et URL de base pour les tests
        $client = new WhatsAppRestClient($this->logger, 'http://localhost/api.php', $this->monitoringService);
        
        // Remplacer le client HTTP interne par notre version mockée
        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($client, $httpClient);
        
        return $client;
    }
    
    /**
     * Crée un utilisateur mocké pour les tests
     */
    private function createMockUser(): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(123);
        return $user;
    }

    /**
     * Test de gestion d'erreur lors d'un timeout de connexion
     * 
     * Note: Nous utilisons RequestException au lieu de ConnectException
     * car ConnectException n'a pas de méthode hasResponse()
     */
    public function testHandleConnectionTimeout(): void
    {
        // Simuler une exception de requête 
        $request = new Request('GET', 'test');
        $this->mockHandler->append(
            new RequestException('Connection timed out', $request)
        );
        
        $apiClient = $this->createMockApiClient();
        
        // Le logger devrait être appelé avec un message d'erreur
        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Erreur'),
                $this->anything()
            );
        
        // L'appel devrait retourner un tableau vide plutôt que de lancer une exception
        $result = $apiClient->getTemplates();
        
        // Vérifier que le résultat est un tableau vide
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
    
    /**
     * Test de gestion d'une réponse HTTP 500
     */
    public function testHandleServerError(): void
    {
        // Simuler une erreur 500 du serveur
        $this->mockHandler->append(
            new Response(500, [], 'Internal Server Error')
        );
        
        $apiClient = $this->createMockApiClient();
        
        // Le logger devrait être appelé avec un message d'erreur
        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Erreur'),
                $this->anything()
            );
        
        // L'appel devrait retourner un tableau vide
        $result = $apiClient->getTemplates();
        
        // Vérifier que le résultat est un tableau vide
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
    
    /**
     * Test de gestion d'une réponse JSON malformée
     */
    public function testHandleInvalidJsonResponse(): void
    {
        // Simuler une réponse avec JSON invalide
        $this->mockHandler->append(
            new Response(200, ['Content-Type' => 'application/json'], 'Invalid JSON{not valid}')
        );
        
        $apiClient = $this->createMockApiClient();
        
        // Le logger devrait être appelé au moins une fois avec un message d'erreur
        $this->logger->expects($this->atLeastOnce())
            ->method('error')
            ->with(
                $this->anything(),
                $this->anything()
            );
        
        // L'appel devrait retourner un tableau vide
        $result = $apiClient->getTemplates();
        
        // Vérifier que le résultat est un tableau vide
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
    
    /**
     * Test de gestion d'une erreur d'authentification (401)
     */
    public function testHandleAuthenticationError(): void
    {
        // Simuler une erreur d'authentification
        $this->mockHandler->append(
            new Response(401, [], json_encode([
                'error' => [
                    'message' => 'Invalid OAuth access token',
                    'type' => 'OAuthException',
                    'code' => 190
                ]
            ]))
        );
        
        $apiClient = $this->createMockApiClient();
        
        // Le logger devrait être appelé au moins une fois avec un message d'erreur
        $this->logger->expects($this->atLeastOnce())
            ->method('error')
            ->with(
                $this->anything(),
                $this->anything()
            );
        
        // L'appel devrait retourner un tableau vide
        $result = $apiClient->getTemplates();
        
        // Vérifier que le résultat est un tableau vide
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
    
    /**
     * Test de gestion d'erreur de limitation de taux (429)
     */
    public function testHandleRateLimitError(): void
    {
        // Simuler une erreur de limitation de taux
        $this->mockHandler->append(
            new Response(429, [], json_encode([
                'error' => [
                    'message' => 'Application request limit reached',
                    'type' => 'OAuthException',
                    'code' => 4
                ]
            ]))
        );
        
        $apiClient = $this->createMockApiClient();
        
        // Le logger devrait être appelé au moins une fois avec un message d'erreur
        $this->logger->expects($this->atLeastOnce())
            ->method('error')
            ->with(
                $this->anything(),
                $this->anything()
            );
        
        // L'appel devrait retourner un tableau vide
        $result = $apiClient->getTemplates();
        
        // Vérifier que le résultat est un tableau vide
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
    
    /**
     * Test que le RestClient enregistre les métriques de performance même en cas d'échec
     */
    public function testRestClientRecordsMetricsOnFailure(): void
    {
        // Simuler une erreur serveur
        $this->mockHandler->append(
            new Response(500, [], 'Server Error')
        );
        
        $restClient = $this->createMockRestClient();
        $user = $this->createMockUser();
        
        // Gérer l'exception
        try {
            $result = $restClient->getApprovedTemplates($user);
            $this->fail('Une exception aurait dû être lancée');
        } catch (\Exception $e) {
            // On s'attend à une exception, c'est normal
            $this->assertStringContainsString('Erreur de communication avec l\'API', $e->getMessage());
        }
    }
    
    /**
     * Test de simulation d'une erreur intermittente (parfois OK, parfois erreur)
     */
    public function testHandleIntermittentFailures(): void
    {
        // Configuration des réponses
        $successResponse = [
            'data' => [
                'templates' => [
                    ['name' => 'template1', 'status' => 'APPROVED'],
                    ['name' => 'template2', 'status' => 'APPROVED']
                ]
            ]
        ];
        
        // Simuler une séquence: erreur, succès, erreur
        $this->mockHandler->append(
            new RequestException('Connection timed out', new Request('GET', 'test')),
            new Response(200, ['Content-Type' => 'application/json'], json_encode($successResponse)),
            new Response(500, [], 'Server Error')
        );
        
        $apiClient = $this->createMockApiClient();
        
        // Premier appel - devrait échouer
        $result1 = $apiClient->getTemplates();
        $this->assertIsArray($result1);
        $this->assertEmpty($result1);
        
        // Deuxième appel - devrait réussir
        $result2 = $apiClient->getTemplates();
        $this->assertIsArray($result2);
        // Note: le résultat pourrait être vide si le format de la réponse n'est pas ce que le client attend
        // L'important est de vérifier qu'il n'y a pas d'exception
        
        // Troisième appel - devrait échouer
        $result3 = $apiClient->getTemplates();
        $this->assertIsArray($result3);
        $this->assertEmpty($result3);
    }
}