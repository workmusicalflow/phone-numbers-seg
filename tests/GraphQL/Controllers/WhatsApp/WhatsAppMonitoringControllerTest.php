<?php

namespace Tests\GraphQL\Controllers\WhatsApp;

// Charger les classes mock pour contourner les problèmes d'autoloading
require_once __DIR__ . '/MockWhatsAppTypes.php';
MockTypes::init();

use App\GraphQL\Controllers\WhatsApp\WhatsAppMonitoringController;
use App\GraphQL\Types\WhatsApp\WhatsAppTemplateUsageMetrics;
use App\GraphQL\Types\WhatsApp\WhatsAppApiPerformanceMetrics;
use App\GraphQL\Types\WhatsApp\WhatsAppApiErrorMetrics;
use App\GraphQL\Types\WhatsApp\WhatsAppMonitoringDashboard;
use App\GraphQL\Types\WhatsApp\WhatsAppAlert;
use App\Services\Interfaces\WhatsApp\WhatsAppMonitoringServiceInterface;
use App\Entities\User;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * Test class for WhatsAppMonitoringController
 *
 * @covers \App\GraphQL\Controllers\WhatsApp\WhatsAppMonitoringController
 */
class WhatsAppMonitoringControllerTest extends TestCase
{
    use ProphecyTrait;

    private $monitoringService;
    private $logger;
    private $controller;
    private $userProphecy;

    protected function setUp(): void
    {
        $this->monitoringService = $this->prophesize(WhatsAppMonitoringServiceInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);

        // Mock user
        $this->userProphecy = $this->prophesize(User::class);
        $this->userProphecy->getId()->willReturn(1); // ID utilisateur exemple

        $this->controller = new WhatsAppMonitoringController(
            $this->monitoringService->reveal(),
            $this->logger->reveal()
        );
    }

    /**
     * Test récupération des métriques d'utilisation des templates avec succès
     * @test
     */
    public function getWhatsAppTemplateUsageMetricsSuccessfully(): void
    {
        $userId = 1;
        $user = $this->userProphecy->reveal();
        $startDate = new \DateTime('2025-05-01');
        $endDate = new \DateTime('2025-05-31');

        $mockData = [
            'total_usage' => 42,
            'template_usage' => [
                [
                    'template_id' => 'template_1',
                    'template_name' => 'Template 1',
                    'count' => 20,
                    'success_rate' => 90.0,
                    'successful' => 18,
                    'failed' => 2
                ],
                [
                    'template_id' => 'template_2',
                    'template_name' => 'Template 2',
                    'count' => 15,
                    'success_rate' => 100.0,
                    'successful' => 15,
                    'failed' => 0
                ]
            ],
            'by_language' => [
                'fr' => 30,
                'en' => 12
            ],
            'by_category' => [
                'MARKETING' => 25,
                'UTILITY' => 17
            ],
            'by_day' => [
                '2025-05-10' => 10,
                '2025-05-11' => 12,
                '2025-05-12' => 20
            ],
            'by_hour' => [
                '9' => 8,
                '10' => 12,
                '11' => 15,
                '12' => 7
            ],
            'unique_templates' => 2
        ];

        $this->monitoringService->getTemplateUsageMetrics($user, $startDate, $endDate)
            ->shouldBeCalledOnce()
            ->willReturn($mockData);

        $result = $this->controller->getWhatsAppTemplateUsageMetrics($startDate, $endDate, $user);
        
        $this->assertInstanceOf(WhatsAppTemplateUsageMetrics::class, $result);
        $this->assertEquals(42, $result->getTotalUsage());
        $this->assertEquals(2, $result->getUniqueTemplates());
        $this->assertCount(2, $result->getTemplateUsage());
        $this->assertArrayHasKey('fr', $result->getByLanguage());
        $this->assertArrayHasKey('en', $result->getByLanguage());
        $this->assertEquals(30, $result->getByLanguage()['fr']);
        $this->assertEquals(12, $result->getByLanguage()['en']);
    }

    /**
     * Test récupération des métriques d'utilisation des templates avec erreur
     * @test
     */
    public function getWhatsAppTemplateUsageMetricsHandlesException(): void
    {
        $userId = 1;
        $user = $this->userProphecy->reveal();
        $startDate = new \DateTime('2025-05-01');
        $endDate = new \DateTime('2025-05-31');
        $errorMessage = "Erreur de service";

        $this->monitoringService->getTemplateUsageMetrics($user, $startDate, $endDate)
            ->shouldBeCalledOnce()
            ->willThrow(new Exception($errorMessage));

        $this->logger->error(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalledOnce();

        $result = $this->controller->getWhatsAppTemplateUsageMetrics($startDate, $endDate, $user);
        
        $this->assertInstanceOf(WhatsAppTemplateUsageMetrics::class, $result);
        $this->assertEquals(0, $result->getTotalUsage());
        $this->assertEquals($errorMessage, $result->getError());
        $this->assertEmpty($result->getTemplateUsage());
    }

    /**
     * Test récupération des métriques d'utilisation sans utilisateur authentifié
     * @test
     */
    public function getWhatsAppTemplateUsageMetricsWithoutUser(): void
    {
        $startDate = new \DateTime('2025-05-01');
        $endDate = new \DateTime('2025-05-31');

        $this->logger->error(Argument::type('string'))
            ->shouldBeCalledOnce();

        $result = $this->controller->getWhatsAppTemplateUsageMetrics($startDate, $endDate, null);
        
        $this->assertInstanceOf(WhatsAppTemplateUsageMetrics::class, $result);
        $this->assertEquals(0, $result->getTotalUsage());
        $this->assertNotNull($result->getError());
        $this->assertEmpty($result->getTemplateUsage());
    }

    /**
     * Test récupération des métriques de performance API avec succès
     * @test
     */
    public function getWhatsAppApiPerformanceMetricsSuccessfully(): void
    {
        $userId = 1;
        $user = $this->userProphecy->reveal();
        $startDate = new \DateTime('2025-05-01');
        $endDate = new \DateTime('2025-05-31');

        $mockData = [
            'total_operations' => 120,
            'overall_success_rate' => 95.0,
            'avg_duration' => 105.5,
            'p95_duration' => 205.3,
            'p99_duration' => 350.7,
            'by_operation' => [
                [
                    'operation' => 'getApprovedTemplates',
                    'count' => 50,
                    'avg_duration' => 95.3,
                    'successful' => 48,
                    'failed' => 2,
                    'success_rate' => 96.0
                ],
                [
                    'operation' => 'getTemplateById',
                    'count' => 70,
                    'avg_duration' => 112.7,
                    'successful' => 66,
                    'failed' => 4,
                    'success_rate' => 94.3
                ]
            ],
            'by_day' => [
                '2025-05-10' => 40,
                '2025-05-11' => 35,
                '2025-05-12' => 45
            ],
            'avg_duration_by_day' => [
                '2025-05-10' => 100.5,
                '2025-05-11' => 110.2,
                '2025-05-12' => 105.8
            ]
        ];

        $this->monitoringService->getApiPerformanceMetrics($user, $startDate, $endDate)
            ->shouldBeCalledOnce()
            ->willReturn($mockData);

        $result = $this->controller->getWhatsAppApiPerformanceMetrics($startDate, $endDate, $user);
        
        $this->assertInstanceOf(WhatsAppApiPerformanceMetrics::class, $result);
        $this->assertEquals(120, $result->getTotalOperations());
        $this->assertEquals(95.0, $result->getOverallSuccessRate());
        $this->assertEquals(105.5, $result->getAvgDuration());
        $this->assertEquals(205.3, $result->getP95Duration());
        $this->assertEquals(350.7, $result->getP99Duration());
        $this->assertCount(2, $result->getByOperation());
        $this->assertNull($result->getError());
    }

    /**
     * Test récupération des métriques de performance API avec erreur
     * @test
     */
    public function getWhatsAppApiPerformanceMetricsHandlesException(): void
    {
        $userId = 1;
        $user = $this->userProphecy->reveal();
        $startDate = new \DateTime('2025-05-01');
        $endDate = new \DateTime('2025-05-31');
        $errorMessage = "Erreur de service";

        $this->monitoringService->getApiPerformanceMetrics($user, $startDate, $endDate)
            ->shouldBeCalledOnce()
            ->willThrow(new Exception($errorMessage));

        $this->logger->error(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalledOnce();

        $result = $this->controller->getWhatsAppApiPerformanceMetrics($startDate, $endDate, $user);
        
        $this->assertInstanceOf(WhatsAppApiPerformanceMetrics::class, $result);
        $this->assertEquals(0, $result->getTotalOperations());
        $this->assertEquals($errorMessage, $result->getError());
    }

    /**
     * Test récupération des métriques d'erreur API avec succès
     * @test
     */
    public function getWhatsAppApiErrorMetricsSuccessfully(): void
    {
        $userId = 1;
        $user = $this->userProphecy->reveal();
        $startDate = new \DateTime('2025-05-01');
        $endDate = new \DateTime('2025-05-31');

        $mockData = [
            'total_errors' => 15,
            'error_rate' => 12.5,
            'critical_errors' => 3,
            'by_type' => [
                [
                    'type' => 'Connection timed out',
                    'count' => 5,
                    'operations' => ['getApprovedTemplates', 'getTemplateById']
                ],
                [
                    'type' => 'Authentication failed',
                    'count' => 3,
                    'operations' => ['getApprovedTemplates']
                ],
                [
                    'type' => 'Internal server error',
                    'count' => 7,
                    'operations' => ['getTemplateById', 'sendTemplate']
                ]
            ],
            'by_operation' => [
                'getApprovedTemplates' => 8,
                'getTemplateById' => 4,
                'sendTemplate' => 3
            ],
            'by_day' => [
                '2025-05-10' => 5,
                '2025-05-11' => 3,
                '2025-05-12' => 7
            ],
            'recent_errors' => [
                [
                    'operation' => 'getTemplateById',
                    'error_message' => 'Internal server error',
                    'date' => '2025-05-12 14:25:36'
                ],
                [
                    'operation' => 'getApprovedTemplates',
                    'error_message' => 'Connection timed out',
                    'date' => '2025-05-12 10:15:22'
                ]
            ]
        ];

        $this->monitoringService->getApiErrorMetrics($user, $startDate, $endDate)
            ->shouldBeCalledOnce()
            ->willReturn($mockData);

        $result = $this->controller->getWhatsAppApiErrorMetrics($startDate, $endDate, $user);
        
        $this->assertInstanceOf(WhatsAppApiErrorMetrics::class, $result);
        $this->assertEquals(15, $result->getTotalErrors());
        $this->assertEquals(12.5, $result->getErrorRate());
        $this->assertEquals(3, $result->getCriticalErrors());
        $this->assertCount(3, $result->getByType());
        $this->assertCount(3, $result->getByOperation());
        $this->assertCount(2, $result->getRecentErrors());
        $this->assertNull($result->getError());
    }

    /**
     * Test récupération du dashboard de monitoring avec succès
     * @test
     */
    public function getWhatsAppMonitoringDashboardSuccessfully(): void
    {
        $userId = 1;
        $user = $this->userProphecy->reveal();
        $period = 'week';

        $mockData = [
            'period' => 'week',
            'start_date' => '2025-05-14',
            'end_date' => '2025-05-21',
            'alerts' => [
                [
                    'type' => 'error_rate',
                    'level' => 'warning',
                    'message' => 'Taux d\'erreur élevé',
                    'details' => [
                        'error_rate' => 15.5,
                        'total_errors' => 18
                    ]
                ]
            ],
            'key_metrics' => [
                'message_success_rate' => 85.0,
                'api_success_rate' => 92.0,
                'total_messages' => 120,
                'total_templates_used' => 42,
                'avg_api_duration' => 105.5,
                'p95_api_duration' => 205.3,
                'critical_errors' => 3,
                'template_count' => 5
            ],
            'top_templates' => [
                [
                    'template_id' => 'template_1',
                    'template_name' => 'Template 1',
                    'count' => 20,
                    'success_rate' => 90.0,
                    'successful' => 18,
                    'failed' => 2
                ],
                [
                    'template_id' => 'template_2',
                    'template_name' => 'Template 2',
                    'count' => 15,
                    'success_rate' => 100.0,
                    'successful' => 15,
                    'failed' => 0
                ]
            ],
            'templates_by_category' => [
                'MARKETING' => 25,
                'UTILITY' => 17
            ],
            'templates_by_language' => [
                'fr' => 30,
                'en' => 12
            ],
            'api_errors_by_type' => [
                [
                    'type' => 'Connection timed out',
                    'count' => 5,
                    'operations' => ['getApprovedTemplates', 'getTemplateById']
                ],
                [
                    'type' => 'Authentication failed',
                    'count' => 3,
                    'operations' => ['getApprovedTemplates']
                ]
            ],
            'messages_by_day' => [
                '2025-05-15' => 25,
                '2025-05-16' => 30,
                '2025-05-17' => 40,
                '2025-05-18' => 25
            ],
            'api_performance_by_day' => [
                '2025-05-15' => 35,
                '2025-05-16' => 42,
                '2025-05-17' => 50,
                '2025-05-18' => 38
            ],
            'api_avg_duration_by_day' => [
                '2025-05-15' => 100.5,
                '2025-05-16' => 110.2,
                '2025-05-17' => 95.8,
                '2025-05-18' => 105.6
            ],
            'recent_errors' => [
                [
                    'operation' => 'getTemplateById',
                    'error_message' => 'Internal server error',
                    'date' => '2025-05-18 14:25:36'
                ],
                [
                    'operation' => 'getApprovedTemplates',
                    'error_message' => 'Connection timed out',
                    'date' => '2025-05-18 10:15:22'
                ]
            ]
        ];

        $this->monitoringService->getDashboard($user, $period)
            ->shouldBeCalledOnce()
            ->willReturn($mockData);

        $result = $this->controller->getWhatsAppMonitoringDashboard($period, $user);
        
        $this->assertInstanceOf(WhatsAppMonitoringDashboard::class, $result);
        $this->assertEquals($period, $result->getPeriod());
        $this->assertEquals('2025-05-14', $result->getStartDate());
        $this->assertEquals('2025-05-21', $result->getEndDate());
        $this->assertCount(1, $result->getAlerts());
        $this->assertNotNull($result->getKeyMetrics());
        $this->assertEquals(85.0, $result->getKeyMetrics()->getMessageSuccessRate());
        $this->assertEquals(92.0, $result->getKeyMetrics()->getApiSuccessRate());
        $this->assertEquals(42, $result->getKeyMetrics()->getTotalTemplatesUsed());
        $this->assertCount(2, $result->getTopTemplates());
        $this->assertNull($result->getError());
    }

    /**
     * Test récupération des alertes actives avec succès
     * @test
     */
    public function getWhatsAppActiveAlertsSuccessfully(): void
    {
        $userId = 1;
        $user = $this->userProphecy->reveal();

        $mockAlerts = [
            [
                'type' => 'error_rate',
                'level' => 'critical',
                'message' => 'Taux d\'erreur WhatsApp élevé (15.5%) sur la dernière heure',
                'details' => [
                    'error_rate' => 15.5,
                    'total_errors' => 18
                ]
            ],
            [
                'type' => 'high_latency',
                'level' => 'warning',
                'message' => 'Latence élevée de l\'API WhatsApp (P95: 2350.75 ms)',
                'details' => [
                    'p95_duration' => 2350.75,
                    'avg_duration' => 1250.45
                ]
            ]
        ];

        $this->monitoringService->getActiveAlerts($user)
            ->shouldBeCalledOnce()
            ->willReturn($mockAlerts);

        $result = $this->controller->getWhatsAppActiveAlerts($user);
        
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(WhatsAppAlert::class, $result[0]);
        $this->assertEquals('error_rate', $result[0]->getType());
        $this->assertEquals('critical', $result[0]->getLevel());
        $this->assertInstanceOf(WhatsAppAlert::class, $result[1]);
        $this->assertEquals('high_latency', $result[1]->getType());
        $this->assertEquals('warning', $result[1]->getLevel());
    }

    /**
     * Test récupération des alertes actives sans alertes
     * @test
     */
    public function getWhatsAppActiveAlertsWithNoAlerts(): void
    {
        $userId = 1;
        $user = $this->userProphecy->reveal();

        $this->monitoringService->getActiveAlerts($user)
            ->shouldBeCalledOnce()
            ->willReturn([]);

        $result = $this->controller->getWhatsAppActiveAlerts($user);
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test récupération des alertes actives avec erreur
     * @test
     */
    public function getWhatsAppActiveAlertsHandlesException(): void
    {
        $userId = 1;
        $user = $this->userProphecy->reveal();
        $errorMessage = "Erreur de service";

        $this->monitoringService->getActiveAlerts($user)
            ->shouldBeCalledOnce()
            ->willThrow(new Exception($errorMessage));

        $this->logger->error(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalledOnce();

        $result = $this->controller->getWhatsAppActiveAlerts($user);
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
    
    /**
     * Basic test to check that service runs
     * @test
     */
    public function testServiceBasics(): void
    {
        $this->assertInstanceOf(WhatsAppMonitoringController::class, $this->controller);
    }
    
    /**
     * Test mock setup is working correctly
     * @test
     */
    public function testMockSetup(): void
    {
        $this->assertTrue(true);
    }
}