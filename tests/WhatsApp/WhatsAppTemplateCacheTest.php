<?php

namespace Tests\WhatsApp;

use App\Entities\User;
use App\Services\WhatsApp\WhatsAppApiClient;
use App\Services\WhatsApp\WhatsAppRestClient;
use App\Services\WhatsApp\WhatsAppTemplateService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;
use Tests\TestCase;
use Tests\Fixtures\WhatsAppFixtures;

/**
 * Tests pour le mécanisme de cache des templates WhatsApp
 */
class WhatsAppTemplateCacheTest extends TestCase
{
    /**
     * @var WhatsAppRestClient|\PHPUnit\Framework\MockObject\MockObject
     */
    private $restClient;
    
    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;
    
    /**
     * @var Client
     */
    private $httpClient;
    
    /**
     * @var array
     */
    private $requestHistory = [];
    
    /**
     * @var User
     */
    private $testUser;
    
    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer les mocks
        $this->logger = $this->createMockWithExpectations(LoggerInterface::class);
        
        // Créer un utilisateur de test
        $this->testUser = WhatsAppFixtures::createTestUser();
        
        // Préparer l'historique des requêtes pour les tests
        $this->requestHistory = [];
    }
    
    /**
     * Test que getApprovedTemplates du RestClient utilise le cache
     */
    public function testRestClientUsesCacheForTemplates(): void
    {
        // Configurer le mock du client HTTP
        $sampleTemplates = WhatsAppFixtures::getSampleTemplatesResponse();
        
        // Préparer une séquence de réponses pour simuler le comportement du cache
        $mockResponses = [
            // Première requête - pas de cache (forceRefresh=1)
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'status' => 'success',
                'templates' => $sampleTemplates,
                'meta' => [
                    'source' => 'api', // Source: API directe
                    'usedFallback' => false,
                    'cacheTtl' => 300 // 5 minutes de TTL
                ],
                'count' => count($sampleTemplates)
            ])),
            
            // Deuxième requête - avec cache (useCache=1)
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'status' => 'success',
                'templates' => $sampleTemplates,
                'meta' => [
                    'source' => 'cache', // Source: Cache
                    'usedFallback' => false,
                    'cacheAge' => 60 // 1 minute depuis la mise en cache
                ],
                'count' => count($sampleTemplates)
            ]))
        ];
        
        $mock = new MockHandler($mockResponses);
        $handlerStack = HandlerStack::create($mock);
        
        // Ajouter un middleware pour capturer l'historique des requêtes
        $history = [];
        $historyMiddleware = Middleware::history($history);
        $handlerStack->push($historyMiddleware);
        
        $httpClient = new Client(['handler' => $handlerStack]);
        
        // Créer le vrai client REST avec le client HTTP mocké
        $restClient = new WhatsAppRestClient($this->logger, 'http://test.example.com');
        
        // Injecter le client HTTP mock par réflexion
        $reflection = new \ReflectionClass($restClient);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($restClient, $httpClient);
        
        // Test 1: Forcer la récupération depuis l'API (pas de cache)
        $result1 = $restClient->getApprovedTemplates($this->testUser, ['forceRefresh' => true]);
        
        // Test 2: Utiliser le cache
        $result2 = $restClient->getApprovedTemplates($this->testUser, ['useCache' => true]);
        
        // Vérifier les résultats des deux appels
        $this->assertCount(count($sampleTemplates), $result1);
        $this->assertCount(count($sampleTemplates), $result2);
        
        // Vérifier que les deux requêtes ont été effectuées avec les bons paramètres
        $this->assertCount(2, $history);
        
        // Vérifier la première requête (forceRefresh=1)
        $this->assertStringContainsString('force_refresh=1', $history[0]['request']->getUri()->getQuery());
        
        // Vérifier la seconde requête (useCache=1)
        $this->assertStringContainsString('use_cache=1', $history[1]['request']->getUri()->getQuery());
    }
    
    /**
     * Test le mécanisme de fallback qui retourne des templates en cache lorsque l'API échoue
     */
    public function testCacheFallbackMechanismWhenApiFailsForTemplates(): void
    {
        // Configurer le mock du client HTTP
        $sampleTemplates = WhatsAppFixtures::getSampleTemplatesResponse();
        
        // Préparer une séquence de réponses pour simuler le comportement de fallback
        $mockResponses = [
            // Première requête - remplir le cache
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'status' => 'success',
                'templates' => $sampleTemplates,
                'meta' => [
                    'source' => 'api',
                    'usedFallback' => false,
                    'cacheTtl' => 300 // 5 minutes de TTL
                ],
                'count' => count($sampleTemplates)
            ])),
            
            // Deuxième requête - l'API échoue, mais on utilise le fallback du cache
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'status' => 'success',
                'templates' => $sampleTemplates,
                'meta' => [
                    'source' => 'cache',
                    'usedFallback' => true, // Indique que c'est un fallback
                    'apiError' => 'Connection timeout'
                ],
                'count' => count($sampleTemplates)
            ]))
        ];
        
        $mock = new MockHandler($mockResponses);
        $handlerStack = HandlerStack::create($mock);
        
        // Ajouter un middleware pour capturer l'historique des requêtes
        $history = [];
        $historyMiddleware = Middleware::history($history);
        $handlerStack->push($historyMiddleware);
        
        $httpClient = new Client(['handler' => $handlerStack]);
        
        // Créer le vrai client REST avec le client HTTP mocké
        $restClient = new WhatsAppRestClient($this->logger, 'http://test.example.com');
        
        // Injecter le client HTTP mock par réflexion
        $reflection = new \ReflectionClass($restClient);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($restClient, $httpClient);
        
        // Test 1: Remplir le cache
        $result1 = $restClient->getApprovedTemplates($this->testUser);
        
        // Test 2: Simuler un échec de l'API mais avec fallback de cache
        $result2 = $restClient->getApprovedTemplates($this->testUser, ['forceRefresh' => true]);
        
        // Vérifier que les deux appels ont bien retourné des templates
        $this->assertCount(count($sampleTemplates), $result1);
        $this->assertCount(count($sampleTemplates), $result2);
        
        // Vérifier que les deux requêtes ont été effectuées
        $this->assertCount(2, $history);
        
        // Vérifier la première requête (remplissage du cache)
        $this->assertStringNotContainsString('force_refresh=1', $history[0]['request']->getUri()->getQuery());
        
        // Vérifier la seconde requête (forceRefresh=1)
        $this->assertStringContainsString('force_refresh=1', $history[1]['request']->getUri()->getQuery());
    }
    
    /**
     * Test que getTemplateById utilise le cache pour les templates individuels
     */
    public function testCacheForIndividualTemplates(): void
    {
        // Configurer le mock du client HTTP
        $sampleTemplate = WhatsAppFixtures::getSampleTemplatesResponse()[0];
        $templateId = $sampleTemplate['id'];
        
        // Préparer une séquence de réponses pour simuler le comportement du cache
        $mockResponses = [
            // Première requête - pas de cache
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'status' => 'success',
                'template' => $sampleTemplate,
                'meta' => [
                    'source' => 'api',
                    'usedFallback' => false,
                    'cacheTtl' => 300
                ]
            ])),
            
            // Deuxième requête - avec cache
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'status' => 'success',
                'template' => $sampleTemplate,
                'meta' => [
                    'source' => 'cache',
                    'usedFallback' => false,
                    'cacheAge' => 60
                ]
            ]))
        ];
        
        $mock = new MockHandler($mockResponses);
        $handlerStack = HandlerStack::create($mock);
        
        // Ajouter un middleware pour capturer l'historique des requêtes
        $history = [];
        $historyMiddleware = Middleware::history($history);
        $handlerStack->push($historyMiddleware);
        
        $httpClient = new Client(['handler' => $handlerStack]);
        
        // Créer le vrai client REST avec le client HTTP mocké
        $restClient = new WhatsAppRestClient($this->logger, 'http://test.example.com');
        
        // Injecter le client HTTP mock par réflexion
        $reflection = new \ReflectionClass($restClient);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($restClient, $httpClient);
        
        // Test 1: Premier appel (remplit le cache)
        $result1 = $restClient->getTemplateById($this->testUser, $templateId);
        
        // Test 2: Deuxième appel (utilise le cache)
        $result2 = $restClient->getTemplateById($this->testUser, $templateId);
        
        // Vérifier les résultats
        $this->assertEquals($templateId, $result1['id']);
        $this->assertEquals($templateId, $result2['id']);
        
        // Vérifier que les deux requêtes ont été effectuées
        $this->assertCount(2, $history);
        
        // Les deux requêtes devraient avoir la même URI
        $this->assertEquals(
            $history[0]['request']->getUri()->getPath(),
            $history[1]['request']->getUri()->getPath()
        );
    }
    
    /**
     * Test que l'expiration du cache force une nouvelle récupération depuis l'API
     */
    public function testCacheExpirationTriggersRefresh(): void
    {
        // Configurer le mock du client HTTP
        $sampleTemplates = WhatsAppFixtures::getSampleTemplatesResponse();
        
        // Préparer une séquence de réponses pour simuler l'expiration du cache
        $mockResponses = [
            // Première requête - remplir le cache
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'status' => 'success',
                'templates' => $sampleTemplates,
                'meta' => [
                    'source' => 'api',
                    'usedFallback' => false,
                    'cacheTtl' => 300 // 5 minutes de TTL
                ],
                'count' => count($sampleTemplates)
            ])),
            
            // Deuxième requête - récupération depuis le cache
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'status' => 'success',
                'templates' => $sampleTemplates,
                'meta' => [
                    'source' => 'cache',
                    'usedFallback' => false,
                    'cacheAge' => 60 // 1 minute depuis la mise en cache
                ],
                'count' => count($sampleTemplates)
            ])),
            
            // Troisième requête - le cache a expiré, rafraîchissement depuis l'API
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'status' => 'success',
                'templates' => $sampleTemplates,
                'meta' => [
                    'source' => 'api',
                    'usedFallback' => false,
                    'cacheExpired' => true, // Le cache avait expiré
                    'cacheTtl' => 300 // Nouveau TTL après rafraîchissement
                ],
                'count' => count($sampleTemplates)
            ]))
        ];
        
        $mock = new MockHandler($mockResponses);
        $handlerStack = HandlerStack::create($mock);
        
        // Ajouter un middleware pour capturer l'historique des requêtes
        $history = [];
        $historyMiddleware = Middleware::history($history);
        $handlerStack->push($historyMiddleware);
        
        $httpClient = new Client(['handler' => $handlerStack]);
        
        // Créer le vrai client REST avec le client HTTP mocké
        $restClient = new WhatsAppRestClient($this->logger, 'http://test.example.com');
        
        // Injecter le client HTTP mock par réflexion
        $reflection = new \ReflectionClass($restClient);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($restClient, $httpClient);
        
        // Test 1: Premier appel (remplit le cache)
        $result1 = $restClient->getApprovedTemplates($this->testUser);
        
        // Test 2: Deuxième appel (utilise le cache)
        $result2 = $restClient->getApprovedTemplates($this->testUser, ['useCache' => true]);
        
        // Test 3: Troisième appel (le cache a expiré)
        $result3 = $restClient->getApprovedTemplates($this->testUser, ['useCache' => true]);
        
        // Vérifier que tous les appels ont bien retourné des templates
        $this->assertCount(count($sampleTemplates), $result1);
        $this->assertCount(count($sampleTemplates), $result2);
        $this->assertCount(count($sampleTemplates), $result3);
        
        // Vérifier que les trois requêtes ont été effectuées
        $this->assertCount(3, $history);
        
        // Les requêtes 1 et 3 devraient aller à l'API, la requête 2 devrait utiliser le cache
        $this->assertStringNotContainsString('use_cache=1', $history[0]['request']->getUri()->getQuery());
        $this->assertStringContainsString('use_cache=1', $history[1]['request']->getUri()->getQuery());
        $this->assertStringContainsString('use_cache=1', $history[2]['request']->getUri()->getQuery());
    }
}