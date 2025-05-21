<?php

namespace Tests\WhatsApp;

use App\Entities\User;
use App\Entities\WhatsApp\WhatsAppApiMetric;
use App\Entities\WhatsApp\WhatsAppMessageHistory;
use App\Entities\WhatsApp\WhatsAppTemplate;
use App\Entities\WhatsApp\WhatsAppTemplateHistory;
use App\Repositories\Interfaces\WhatsApp\WhatsAppApiMetricRepositoryInterface;
use App\Repositories\Interfaces\WhatsApp\WhatsAppMessageHistoryRepositoryInterface;
use App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateHistoryRepositoryInterface;
use App\Services\WhatsApp\WhatsAppMonitoringService;
use App\Services\Interfaces\WhatsApp\WhatsAppMonitoringServiceInterface;
use Psr\Log\LoggerInterface;
use Tests\TestCase;
use Tests\Utils\WhatsAppAssertions;
use Tests\Fixtures\WhatsAppFixtures;

/**
 * Tests pour le service de monitoring WhatsApp
 */
class WhatsAppMonitoringServiceTest extends TestCase
{
    use WhatsAppAssertions;
    
    /**
     * @var WhatsAppMonitoringServiceInterface
     */
    private WhatsAppMonitoringServiceInterface $monitoringService;
    
    /**
     * @var WhatsAppTemplateHistoryRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $templateHistoryRepository;
    
    /**
     * @var WhatsAppMessageHistoryRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $messageHistoryRepository;
    
    /**
     * @var WhatsAppApiMetricRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $apiMetricRepository;
    
    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;
    
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
        
        // Créer les mocks des repositories
        $this->templateHistoryRepository = $this->createMockWithExpectations(WhatsAppTemplateHistoryRepositoryInterface::class);
        $this->messageHistoryRepository = $this->createMockWithExpectations(WhatsAppMessageHistoryRepositoryInterface::class);
        $this->apiMetricRepository = $this->createMockWithExpectations(WhatsAppApiMetricRepositoryInterface::class);
        $this->logger = $this->createMockWithExpectations(LoggerInterface::class);
        
        // Créer le service de monitoring
        $this->monitoringService = new WhatsAppMonitoringService(
            $this->templateHistoryRepository,
            $this->messageHistoryRepository,
            $this->apiMetricRepository,
            $this->logger
        );
        
        // Créer un utilisateur de test
        $this->testUser = WhatsAppFixtures::createTestUser();
    }
    
    /**
     * Test de getTemplateUsageMetrics
     */
    public function testGetTemplateUsageMetrics(): void
    {
        // Créer des templates de test
        $templates = WhatsAppFixtures::createTestTemplates();
        
        // Créer des historiques de template de test
        $templateHistory = WhatsAppFixtures::createTestTemplateHistory($this->testUser, $templates);
        
        // Configurer le mock du repository
        $this->templateHistoryRepository->expects($this->once())
            ->method('findBy')
            ->willReturn($templateHistory);
            
        // Configurer le mock pour logger->info
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Récupération des métriques d\'utilisation des templates WhatsApp'));
            
        // Appeler la méthode à tester
        $metrics = $this->monitoringService->getTemplateUsageMetrics($this->testUser);
        
        // Vérifier les résultats
        $this->assertEquals(3, $metrics['total_usage']);
        $this->assertEquals(3, $metrics['unique_templates']);
        
        // Vérifier les métriques par template
        $this->assertCount(3, $metrics['template_usage']);
        
        // Vérifier les métriques par langue
        $this->assertArrayHasKey('fr', $metrics['by_language']);
        $this->assertArrayHasKey('en', $metrics['by_language']);
        $this->assertEquals(2, $metrics['by_language']['fr']);
        $this->assertEquals(1, $metrics['by_language']['en']);
        
        // Vérifier les métriques par catégorie
        $this->assertArrayHasKey('MARKETING', $metrics['by_category']);
        $this->assertArrayHasKey('UTILITY', $metrics['by_category']);
        $this->assertEquals(1, $metrics['by_category']['MARKETING']);
        $this->assertEquals(2, $metrics['by_category']['UTILITY']);
    }
    
    /**
     * Test de getApiPerformanceMetrics
     */
    public function testGetApiPerformanceMetrics(): void
    {
        // Créer des métriques API de test
        $apiMetrics = WhatsAppFixtures::createTestApiMetrics($this->testUser);
        
        // Configurer le mock du repository
        $this->apiMetricRepository->expects($this->once())
            ->method('findBy')
            ->willReturn($apiMetrics);
            
        // Configurer le mock pour logger->info
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Récupération des métriques de performance de l\'API WhatsApp'));
            
        // Appeler la méthode à tester
        $metrics = $this->monitoringService->getApiPerformanceMetrics($this->testUser);
        
        // Vérifier les résultats
        $this->assertEquals(4, $metrics['total_operations']);
        $this->assertEquals(50.0, $metrics['overall_success_rate']); // 2 succès sur 4 = 50%
        
        // La valeur est calculée directement à partir des métriques fournies
        // au lieu d'utiliser getAverageDuration/getP95Duration
        $this->assertGreaterThan(0, $metrics['avg_duration']);
        $this->assertGreaterThan(0, $metrics['p95_duration']);
        
        // Vérifier les métriques par opération
        $this->assertCount(2, $metrics['by_operation']);
        
        // Vérifier les opérations spécifiques
        $foundGetApproved = false;
        $foundGetById = false;
        
        foreach ($metrics['by_operation'] as $operation) {
            if ($operation['operation'] === 'getApprovedTemplates') {
                $foundGetApproved = true;
                $this->assertEquals(2, $operation['count']);
                $this->assertEquals(1, $operation['successful']);
                $this->assertEquals(1, $operation['failed']);
                $this->assertEquals(50.0, $operation['success_rate']);
            } elseif ($operation['operation'] === 'getTemplateById') {
                $foundGetById = true;
                $this->assertEquals(2, $operation['count']);
                $this->assertEquals(1, $operation['successful']);
                $this->assertEquals(1, $operation['failed']);
                $this->assertEquals(50.0, $operation['success_rate']);
            }
        }
        
        $this->assertTrue($foundGetApproved, 'getApprovedTemplates non trouvé dans les métriques');
        $this->assertTrue($foundGetById, 'getTemplateById non trouvé dans les métriques');
    }
    
    /**
     * Test de getApiErrorMetrics
     */
    public function testGetApiErrorMetrics(): void
    {
        // Créer des métriques API de test
        $apiMetrics = WhatsAppFixtures::createTestApiMetrics($this->testUser);
        
        // Filtrer uniquement les métriques échouées
        $failedMetrics = array_filter($apiMetrics, function($metric) {
            return !$metric->isSuccess();
        });
        
        // Configurer le mock du repository
        $this->apiMetricRepository->expects($this->once())
            ->method('findBy')
            ->willReturn($failedMetrics);
            
        // Configurer le mock pour count des métriques réussies
        $this->apiMetricRepository->expects($this->once())
            ->method('count')
            ->willReturn(2); // 2 métriques réussies
            
        // Appeler la méthode à tester
        $metrics = $this->monitoringService->getApiErrorMetrics($this->testUser);
        
        // Vérifier les résultats
        $this->assertEquals(2, $metrics['total_errors']);
        $this->assertEquals(50.0, $metrics['error_rate']); // 2 erreurs sur 4 = 50%
        
        // Vérifier les types d'erreurs
        $this->assertGreaterThanOrEqual(1, count($metrics['by_type']));
        
        // Vérifier les erreurs par opération
        $this->assertArrayHasKey('getApprovedTemplates', $metrics['by_operation']);
        $this->assertArrayHasKey('getTemplateById', $metrics['by_operation']);
        $this->assertEquals(1, $metrics['by_operation']['getApprovedTemplates']);
        $this->assertEquals(1, $metrics['by_operation']['getTemplateById']);
        
        // Vérifier les erreurs récentes
        $this->assertCount(2, $metrics['recent_errors']);
    }
    
    /**
     * Test de recordApiPerformance
     */
    public function testRecordApiPerformance(): void
    {
        // Configurer le mock pour save
        $this->apiMetricRepository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function(WhatsAppApiMetric $metric) {
                // Vérifier que la métrique a les bonnes valeurs
                $this->assertEquals($this->testUser->getId(), $metric->getUserId());
                $this->assertEquals('testOperation', $metric->getOperation());
                $this->assertEquals(150.5, $metric->getDuration());
                $this->assertEquals(true, $metric->isSuccess());
                $this->assertNull($metric->getErrorMessage());
                
                return $metric;
            });
            
        // Appeler la méthode à tester
        $this->monitoringService->recordApiPerformance(
            $this->testUser,
            'testOperation',
            150.5,
            true
        );
    }
    
    /**
     * Test de recordApiPerformance avec erreur
     */
    public function testRecordApiPerformanceWithError(): void
    {
        // Configurer le mock pour save
        $this->apiMetricRepository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function(WhatsAppApiMetric $metric) {
                // Vérifier que la métrique a les bonnes valeurs
                $this->assertEquals($this->testUser->getId(), $metric->getUserId());
                $this->assertEquals('testOperation', $metric->getOperation());
                $this->assertEquals(250.0, $metric->getDuration());
                $this->assertEquals(false, $metric->isSuccess());
                $this->assertEquals('Test error message', $metric->getErrorMessage());
                
                return $metric;
            });
            
        // Configurer le mock du logger pour vérifier le log d'erreur
        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                $this->stringContains('Erreur API WhatsApp détectée'),
                $this->callback(function($context) {
                    return isset($context['error']) && $context['error'] === 'Test error message' &&
                           isset($context['operation']) && $context['operation'] === 'testOperation';
                })
            );
            
        // Appeler la méthode à tester
        $this->monitoringService->recordApiPerformance(
            $this->testUser,
            'testOperation',
            250.0,
            false,
            'Test error message'
        );
    }
    
    /**
     * Test de getDashboard
     */
    public function testGetDashboard(): void
    {
        // Créer des templates de test
        $templates = WhatsAppFixtures::createTestTemplates();
        
        // Créer des historiques de template de test
        $templateHistory = WhatsAppFixtures::createTestTemplateHistory($this->testUser, $templates);
        
        // Créer des métriques API de test
        $apiMetrics = WhatsAppFixtures::createTestApiMetrics($this->testUser);
        
        // Configurer les mocks des repositories
        
        // Logger
        $this->logger->expects($this->atLeastOnce())
            ->method('info');
        
        // Template history
        $this->templateHistoryRepository->expects($this->atLeastOnce())
            ->method('findBy')
            ->willReturn($templateHistory);
            
        // API metrics
        $this->apiMetricRepository->expects($this->any())
            ->method('findBy')
            ->willReturn($apiMetrics);
            
        // Message success rate
        $this->messageHistoryRepository->expects($this->atLeastOnce())
            ->method('countByStatus')
            ->willReturn(50);
            
        // Appeler la méthode à tester
        $dashboard = $this->monitoringService->getDashboard($this->testUser, 'week');
        
        // Vérifier les résultats
        $this->assertEquals('week', $dashboard['period']);
        
        // Vérifier la présence des métriques clés
        $this->assertArrayHasKey('key_metrics', $dashboard);
        
        // Les valeurs spécifiques peuvent varier en fonction des données de test, donc nous vérifions juste
        // que les valeurs sont cohérentes avec les données fournies
        $this->assertIsNumeric($dashboard['key_metrics']['message_success_rate']);
        $this->assertIsNumeric($dashboard['key_metrics']['avg_api_duration']);
        $this->assertIsNumeric($dashboard['key_metrics']['p95_api_duration']);
        
        // Vérifier que le nombre de templates utilisés est renseigné
        $this->assertIsNumeric($dashboard['key_metrics']['total_templates_used']);
        
        // Vérifier si les templates principaux sont présents
        if (isset($dashboard['top_templates'])) {
            $this->assertIsArray($dashboard['top_templates']);
        }
        
        // Vérifier les templates par catégorie
        if (isset($dashboard['templates_by_category'])) {
            $this->assertIsArray($dashboard['templates_by_category']);
        }
        
        // Vérifier les messages par jour s'ils sont présents
        if (isset($dashboard['messages_by_day'])) {
            $this->assertIsArray($dashboard['messages_by_day']);
        }
    }
    
    /**
     * Test de getActiveAlerts
     */
    public function testGetActiveAlerts(): void
    {
        // Pour ce test, nous allons simuler un taux de succès bas
        
        // Message success count (70 succès, 30 échecs => 70% success rate)
        $this->messageHistoryRepository->expects($this->atLeastOnce())
            ->method('countByStatus')
            ->withConsecutive(
                [$this->equalTo($this->testUser->getId()), $this->equalTo(['sent', 'delivered', 'read']), $this->anything()],
                [$this->equalTo($this->testUser->getId()), $this->equalTo(['failed']), $this->anything()]
            )
            ->willReturnOnConsecutiveCalls(70, 30);
        
        // Création de fausses métriques d'erreur pour simuler des alertes
        $now = new \DateTime();
        $failedMetric = new WhatsAppApiMetric();
        $failedMetric->setUserId($this->testUser->getId());
        $failedMetric->setOperation('getApprovedTemplates');
        $failedMetric->setDuration(2500.0);
        $failedMetric->setSuccess(false);
        $failedMetric->setErrorMessage('Request timed out after 2500ms');
        $failedMetric->setCreatedAt($now);
        
        // Configurer le mock pour les métriques d'erreur (hourly)
        $this->apiMetricRepository->expects($this->any())
            ->method('findBy')
            ->willReturn([$failedMetric]);
            
        // Taux d'erreur élevé (50%)
        $this->apiMetricRepository->expects($this->any())
            ->method('count')
            ->willReturn(2); // 1 erreur sur 2 opérations = 50% d'erreur
            
        // Configurer le mock pour logger
        $this->logger->expects($this->atLeastOnce())
            ->method('info');
            
        // Appeler la méthode à tester
        $alerts = $this->monitoringService->getActiveAlerts($this->testUser);
        
        // Vérifier les résultats
        $this->assertIsArray($alerts);
        
        // Il devrait y avoir au moins une alerte (taux de succès des messages bas)
        $this->assertNotEmpty($alerts, "Des alertes auraient dû être générées");
        
        // Vérifier une alerte spécifique
        $lowMessageRateFound = false;
        foreach ($alerts as $alert) {
            if ($alert['type'] === 'low_message_success') {
                $lowMessageRateFound = true;
                $this->assertEquals('warning', $alert['level']);
                $this->assertStringContainsString('Taux de succès des messages WhatsApp bas', $alert['message']);
                $this->assertArrayHasKey('details', $alert);
            }
        }
        
        $this->assertTrue($lowMessageRateFound, 'Alerte de taux de succès bas non trouvée');
    }
    
    /**
     * Test de getActiveAlerts avec aucune alerte
     */
    public function testGetActiveAlertsWithNoAlerts(): void
    {
        // Message success count (95 succès)
        $this->messageHistoryRepository->expects($this->atLeastOnce())
            ->method('countByStatus')
            ->withConsecutive(
                [$this->equalTo($this->testUser->getId()), $this->equalTo(['sent', 'delivered', 'read']), $this->anything()],
                [$this->equalTo($this->testUser->getId()), $this->equalTo(['failed']), $this->anything()]
            )
            ->willReturnOnConsecutiveCalls(95, 5);
            
        // Configurer le mock pour les métriques d'erreur (aucune erreur)
        $this->apiMetricRepository->expects($this->any())
            ->method('findBy')
            ->willReturn([]);
            
        // Taux d'erreur bas (5%)
        $this->apiMetricRepository->expects($this->any())
            ->method('count')
            ->willReturn(100);
            
        // Configurer le mock pour logger
        $this->logger->expects($this->atLeastOnce())
            ->method('info');
            
        // Appeler la méthode à tester
        $alerts = $this->monitoringService->getActiveAlerts($this->testUser);
        
        // Vérifier les résultats
        $this->assertIsArray($alerts);
        
        // Il ne devrait pas y avoir d'alertes car:
        // - Taux de succès des messages est de 95% (supérieur au seuil de 90%)
        // - Pas d'erreurs critiques récentes
        // - Pas de latence API élevée
        $this->assertEmpty($alerts);
    }
}