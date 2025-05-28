<?php

namespace Tests\WhatsApp;

use App\Entities\User;
use App\Services\Interfaces\WhatsApp\WhatsAppMonitoringServiceInterface;
use App\Services\WhatsApp\WhatsAppRestClient;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;
use Tests\TestCase;
use Tests\Fixtures\WhatsAppFixtures;

/**
 * Tests pour le client REST WhatsApp
 */
class WhatsAppRestClientTest extends TestCase
{
    /**
     * @var WhatsAppRestClient
     */
    private WhatsAppRestClient $restClient;
    
    /**
     * @var Client|\PHPUnit\Framework\MockObject\MockObject
     */
    private $httpClient;
    
    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;
    
    /**
     * @var WhatsAppMonitoringServiceInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $monitoringService;
    
    /**
     * @var User
     */
    private User $testUser;
    
    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer les mocks
        $this->httpClient = $this->createMockWithExpectations(Client::class);
        $this->logger = $this->createMockWithExpectations(LoggerInterface::class);
        $this->monitoringService = $this->createMockWithExpectations(WhatsAppMonitoringServiceInterface::class);
        
        // Remplacer le client HTTP dans RestClient
        /** @var LoggerInterface $loggerMock */
        $loggerMock = $this->logger;
        /** @var WhatsAppMonitoringServiceInterface|null $monitoringServiceMock */
        $monitoringServiceMock = $this->monitoringService;
        
        $this->restClient = new WhatsAppRestClient(
            $loggerMock,
            'http://test.example.com',
            $monitoringServiceMock
        );
        
        // Injecter le client HTTP mock par réflexion
        $reflection = new \ReflectionClass($this->restClient);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($this->restClient, $this->httpClient);
        
        // Créer un utilisateur de test
        $this->testUser = WhatsAppFixtures::createTestUser();
    }
    
    /**
     * Test getApprovedTemplates avec succès
     */
    public function testGetApprovedTemplatesSuccess(): void
    {
        $sampleTemplates = WhatsAppFixtures::getSampleTemplatesResponse();
        
        // Préparer la réponse
        $responseBody = json_encode([
            'status' => 'success',
            'templates' => $sampleTemplates,
            'meta' => [
                'source' => 'api',
                'usedFallback' => false
            ],
            'count' => count($sampleTemplates)
        ]);
        
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            $responseBody
        );
        
        // Configurer le mock du client HTTP
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                $this->stringContains('/api.php?endpoint=whatsapp/templates/approved'),
                $this->anything()
            )
            ->willReturn($response);
        
        // Configurer le mock du service de monitoring
        $this->monitoringService->expects($this->once())
            ->method('recordApiPerformance')
            ->with(
                $this->testUser,
                'getApprovedTemplates',
                $this->anything(),
                true,
                null
            );
        
        // Appeler la méthode à tester
        $result = $this->restClient->getApprovedTemplates($this->testUser);
        
        // Vérifier le résultat
        $this->assertCount(count($sampleTemplates), $result);
        $this->assertEquals($sampleTemplates[0]['id'], $result[0]['id']);
        $this->assertEquals($sampleTemplates[0]['name'], $result[0]['name']);
    }
    
    /**
     * Test getApprovedTemplates avec filtres
     */
    public function testGetApprovedTemplatesWithFilters(): void
    {
        $sampleTemplates = array_filter(
            WhatsAppFixtures::getSampleTemplatesResponse(),
            function($template) {
                return $template['category'] === 'UTILITY' && $template['language'] === 'fr';
            }
        );
        $sampleTemplates = array_values($sampleTemplates); // Réindexer le tableau
        
        // Préparer la réponse
        $responseBody = json_encode([
            'status' => 'success',
            'templates' => $sampleTemplates,
            'meta' => [
                'source' => 'api',
                'usedFallback' => false
            ],
            'count' => count($sampleTemplates)
        ]);
        
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            $responseBody
        );
        
        // Définir les filtres à tester
        $filters = [
            'category' => 'UTILITY',
            'language' => 'fr'
        ];
        
        // Configurer le mock du client HTTP
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                $this->callback(function($url) {
                    return strpos($url, '/api.php?endpoint=whatsapp/templates/approved') !== false &&
                           strpos($url, 'category=UTILITY') !== false &&
                           strpos($url, 'language=fr') !== false;
                }),
                $this->anything()
            )
            ->willReturn($response);
        
        // Configurer le mock du service de monitoring
        $this->monitoringService->expects($this->once())
            ->method('recordApiPerformance')
            ->with(
                $this->testUser,
                'getApprovedTemplates',
                $this->anything(),
                true,
                null
            );
        
        // Appeler la méthode à tester avec les filtres
        $result = $this->restClient->getApprovedTemplates($this->testUser, $filters);
        
        // Vérifier le résultat
        $this->assertCount(count($sampleTemplates), $result);
        $this->assertEquals('appointment_reminder_fr', $result[0]['id']);
        $this->assertEquals('fr', $result[0]['language']);
        $this->assertEquals('UTILITY', $result[0]['category']);
    }
    
    /**
     * Test getApprovedTemplates avec erreur
     */
    public function testGetApprovedTemplatesError(): void
    {
        $errorMessage = 'API error';
        
        // Préparer la réponse d'erreur
        $responseBody = json_encode([
            'status' => 'error',
            'message' => $errorMessage
        ]);
        
        $response = new Response(
            400,
            ['Content-Type' => 'application/json'],
            $responseBody
        );
        
        // Configurer le mock du client HTTP
        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($response);
        
        // Configurer le mock du service de monitoring
        $this->monitoringService->expects($this->once())
            ->method('recordApiPerformance')
            ->with(
                $this->testUser,
                'getApprovedTemplates',
                $this->anything(),
                false,
                $this->stringContains('Réponse API invalide: 400')
            );
        
        // Attendre une exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Réponse API invalide: 400');
        
        // Appeler la méthode à tester
        $this->restClient->getApprovedTemplates($this->testUser);
    }
    
    /**
     * Test getApprovedTemplates avec exception réseau
     */
    public function testGetApprovedTemplatesNetworkException(): void
    {
        $errorMessage = 'Connection timed out';
        
        // Créer une exception réseau
        $request = new Request('GET', '/api.php?endpoint=whatsapp/templates/approved');
        $exception = new RequestException(
            $errorMessage,
            $request
        );
        
        // Configurer le mock du client HTTP
        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException($exception);
        
        // Configurer le mock du service de monitoring
        $this->monitoringService->expects($this->once())
            ->method('recordApiPerformance')
            ->with(
                $this->testUser,
                'getApprovedTemplates',
                $this->anything(),
                false,
                $this->stringContains($errorMessage)
            );
        
        // Attendre une exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Erreur de communication avec l\'API: ' . $errorMessage);
        
        // Appeler la méthode à tester
        $this->restClient->getApprovedTemplates($this->testUser);
    }
    
    /**
     * Test getTemplateById avec succès
     */
    public function testGetTemplateByIdSuccess(): void
    {
        $templateId = 'marketing_promo_fr';
        $sampleTemplate = WhatsAppFixtures::getSampleTemplatesResponse()[0];
        
        // Préparer la réponse
        $responseBody = json_encode([
            'status' => 'success',
            'template' => $sampleTemplate,
            'meta' => [
                'source' => 'api',
                'usedFallback' => false
            ]
        ]);
        
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            $responseBody
        );
        
        // Configurer le mock du client HTTP
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                $this->stringContains('/api.php?endpoint=whatsapp/templates/' . $templateId),
                $this->anything()
            )
            ->willReturn($response);
        
        // Configurer le mock du service de monitoring
        $this->monitoringService->expects($this->once())
            ->method('recordApiPerformance')
            ->with(
                $this->testUser,
                'getTemplateById',
                $this->anything(),
                true,
                null
            );
        
        // Appeler la méthode à tester
        $result = $this->restClient->getTemplateById($this->testUser, $templateId);
        
        // Vérifier le résultat
        $this->assertEquals($sampleTemplate['id'], $result['id']);
        $this->assertEquals($sampleTemplate['name'], $result['name']);
    }
    
    /**
     * Test getTemplateById avec template non trouvé
     */
    public function testGetTemplateByIdNotFound(): void
    {
        $templateId = 'non_existent';
        $errorMessage = 'Template not found';
        
        // Préparer la réponse d'erreur
        $responseBody = json_encode([
            'status' => 'error',
            'message' => $errorMessage
        ]);
        
        $response = new Response(
            404,
            ['Content-Type' => 'application/json'],
            $responseBody
        );
        
        // Configurer le mock du client HTTP
        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($response);
        
        // Configurer le mock du service de monitoring
        $this->monitoringService->expects($this->once())
            ->method('recordApiPerformance')
            ->with(
                $this->testUser,
                'getTemplateById',
                $this->anything(),
                false,
                $this->stringContains('Réponse API invalide: 404')
            );
        
        // Attendre une exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Réponse API invalide: 404');
        
        // Appeler la méthode à tester
        $this->restClient->getTemplateById($this->testUser, $templateId);
    }
    
    /**
     * Test getApprovedTemplates avec option de mise en cache
     */
    public function testGetApprovedTemplatesWithCacheOptions(): void
    {
        $sampleTemplates = WhatsAppFixtures::getSampleTemplatesResponse();
        
        // Préparer la réponse avec métadonnées de cache
        $responseBody = json_encode([
            'status' => 'success',
            'templates' => $sampleTemplates,
            'meta' => [
                'source' => 'cache',
                'usedFallback' => true
            ],
            'count' => count($sampleTemplates)
        ]);
        
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            $responseBody
        );
        
        // Définir les options de cache
        $filters = [
            'useCache' => true,
            'forceRefresh' => false
        ];
        
        // Configurer le mock du client HTTP
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                $this->stringContains('/api.php?endpoint=whatsapp/templates/approved&use_cache=1&force_refresh=0'),
                $this->anything()
            )
            ->willReturn($response);
        
        // Configurer le mock du service de monitoring
        $this->monitoringService->expects($this->once())
            ->method('recordApiPerformance')
            ->with(
                $this->testUser,
                'getApprovedTemplates',
                $this->anything(),
                true,
                null
            );
        
        // Appeler la méthode à tester avec les options de cache
        $result = $this->restClient->getApprovedTemplates($this->testUser, $filters);
        
        // Vérifier le résultat
        $this->assertCount(count($sampleTemplates), $result);
        $this->assertEquals($sampleTemplates[0]['id'], $result[0]['id']);
    }
    
    /**
     * Test getApprovedTemplates avec réponse mal formée (JSON invalide)
     */
    public function testGetApprovedTemplatesWithInvalidJsonResponse(): void
    {
        // Préparer une réponse avec JSON invalide
        $invalidJson = '{"status": "success", "templates": [';
        
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            $invalidJson
        );
        
        // Configurer le mock du client HTTP
        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($response);
        
        // Configurer le mock du service de monitoring
        $this->monitoringService->expects($this->once())
            ->method('recordApiPerformance')
            ->with(
                $this->testUser,
                'getApprovedTemplates',
                $this->anything(),
                false,
                $this->anything()
            );
        
        // Attendre une exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Réponse API invalide: 200');
        
        // Appeler la méthode à tester
        $this->restClient->getApprovedTemplates($this->testUser);
    }
    
    /**
     * Test getApprovedTemplates avec timeout d'API
     */
    public function testGetApprovedTemplatesWithTimeout(): void
    {
        $errorMessage = 'cURL error 28: Operation timed out';
        
        // Créer une exception de type timeout
        $request = new Request('GET', '/api.php?endpoint=whatsapp/templates/approved');
        $exception = new RequestException(
            $errorMessage,
            $request
        );
        
        // Configurer le mock du client HTTP
        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException($exception);
        
        // Configurer le mock du service de monitoring
        $this->monitoringService->expects($this->once())
            ->method('recordApiPerformance')
            ->with(
                $this->testUser,
                'getApprovedTemplates',
                $this->anything(),
                false,
                $this->stringContains('timed out')
            );
        
        // Attendre une exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Erreur de communication avec l\'API: ' . $errorMessage);
        
        // Appeler la méthode à tester
        $this->restClient->getApprovedTemplates($this->testUser);
    }
    
    /**
     * Test getTemplateById avec une réponse sans template
     */
    public function testGetTemplateByIdWithMissingTemplateData(): void
    {
        $templateId = 'valid_template';
        
        // Préparer une réponse avec des données manquantes
        $responseBody = json_encode([
            'status' => 'success',
            // Le champ 'template' est manquant
            'meta' => [
                'source' => 'api',
                'usedFallback' => false
            ]
        ]);
        
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            $responseBody
        );
        
        // Configurer le mock du client HTTP
        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($response);
        
        // Configurer le mock du service de monitoring
        $this->monitoringService->expects($this->once())
            ->method('recordApiPerformance')
            ->with(
                $this->testUser,
                'getTemplateById',
                $this->anything(),
                false,
                $this->stringContains('inattendu')
            );
        
        // Attendre une exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Format de réponse API inattendu ou template non trouvé');
        
        // Appeler la méthode à tester
        $this->restClient->getTemplateById($this->testUser, $templateId);
    }
}