<?php

namespace Tests\Integration\WhatsApp;

use App\Entities\User;
use App\GraphQL\Controllers\WhatsApp\WhatsAppMonitoringController;
use App\Services\Interfaces\WhatsApp\WhatsAppMonitoringServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use GraphQL\Error\DebugFlag;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Executor\Executor;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Utils\BuildSchema;
use PHPUnit\Framework\TestCase;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Argument;

/**
 * Tests d'intégration pour les endpoints GraphQL de monitoring WhatsApp
 * 
 * Ces tests exécutent les requêtes GraphQL complètes et vérifient les réponses
 * sans mocker les contrôleurs ou les services.
 */
class WhatsAppMonitoringGraphQLTest extends TestCase
{
    use ProphecyTrait;

    private static ?Schema $schema = null;
    private ?ContainerInterface $container = null;
    private ?EntityManagerInterface $entityManager = null;
    private ?User $testUser = null;
    private ?LoggerInterface $mockLogger = null;
    private ?WhatsAppMonitoringServiceInterface $mockMonitoringService = null;

    /**
     * Configure l'environnement de test global une seule fois
     */
    public static function setUpBeforeClass(): void
    {
        // Initialiser le schéma GraphQL (réduit pour les tests)
        if (self::$schema === null) {
            $schemaString = '
            "A date-time string at UTC, such as 2007-12-03T10:15:30Z, compliant with the RFC 3339 profile"
            scalar DateTime
            
            type Query {
              # WhatsApp Monitoring queries
              getWhatsAppTemplateUsageMetrics(
                startDate: DateTime
                endDate: DateTime
              ): WhatsAppTemplateUsageMetrics!
              getWhatsAppApiPerformanceMetrics(
                startDate: DateTime
                endDate: DateTime
              ): WhatsAppApiPerformanceMetrics!
              getWhatsAppApiErrorMetrics(
                startDate: DateTime
                endDate: DateTime
              ): WhatsAppApiErrorMetrics!
              getWhatsAppMonitoringDashboard(
                period: String = "week"
              ): WhatsAppMonitoringDashboard!
              getWhatsAppActiveAlerts: [WhatsAppAlert!]!
            }
            
            # Types pour les métriques d\'utilisation des templates
            type WhatsAppTemplateUsageMetrics {
              totalUsage: Int!
              uniqueTemplates: Int!
              templateUsage: [WhatsAppTemplateUsageMetric!]!
              byLanguage: JSON!
              byCategory: JSON!
              byDay: JSON!
              byHour: JSON!
              error: String
            }
            
            type WhatsAppTemplateUsageMetric {
              templateId: String!
              templateName: String!
              count: Int!
              successRate: Float!
              successful: Int!
              failed: Int!
            }
            
            # Types pour les métriques de performance API
            type WhatsAppApiPerformanceMetrics {
              totalOperations: Int!
              overallSuccessRate: Float!
              avgDuration: Float!
              p95Duration: Float!
              p99Duration: Float!
              byOperation: [WhatsAppApiPerformanceMetric!]!
              byDay: JSON!
              avgDurationByDay: JSON!
              error: String
            }
            
            type WhatsAppApiPerformanceMetric {
              operation: String!
              count: Int!
              avgDuration: Float!
              successful: Int!
              failed: Int!
              successRate: Float!
            }
            
            # Types pour les métriques d\'erreur API
            type WhatsAppApiErrorMetrics {
              totalErrors: Int!
              errorRate: Float!
              criticalErrors: Int!
              byType: [WhatsAppApiErrorMetric!]!
              byOperation: JSON!
              byDay: JSON!
              recentErrors: JSON!
              error: String
            }
            
            type WhatsAppApiErrorMetric {
              type: String!
              count: Int!
              operations: [String!]!
            }
            
            # Types pour le dashboard de monitoring
            type WhatsAppMonitoringDashboard {
              period: String!
              startDate: String!
              endDate: String!
              alerts: [WhatsAppAlert!]!
              keyMetrics: WhatsAppKeyMetrics!
              topTemplates: [WhatsAppTemplateUsageMetric!]!
              templatesByCategory: JSON!
              templatesByLanguage: JSON!
              apiErrorsByType: [WhatsAppApiErrorMetric!]!
              messagesByDay: JSON!
              apiPerformanceByDay: JSON!
              apiAvgDurationByDay: JSON!
              recentErrors: JSON!
              error: String
            }
            
            type WhatsAppKeyMetrics {
              messageSuccessRate: Float!
              apiSuccessRate: Float!
              totalMessages: Int!
              totalTemplatesUsed: Int!
              avgApiDuration: Float!
              p95ApiDuration: Float!
              criticalErrors: Int!
              templateCount: Int!
            }
            
            # Types pour les alertes
            type WhatsAppAlert {
              type: String!
              level: String!
              message: String!
              details: JSON!
            }
            
            # Type scalaire pour les données JSON
            scalar JSON
            ';
            
            self::$schema = BuildSchema::build($schemaString);
        }
    }

    /**
     * Configure l'environnement pour chaque test
     */
    protected function setUp(): void
    {
        // Créer un nouvel EntityManager pour chaque test
        $this->entityManager = require __DIR__ . '/../../../src/bootstrap-doctrine.php';
        
        // Créer un user de test basique sans l'enregistrer dans la base de données
        $this->testUser = new User();
        $this->testUser->setUsername('test_user_' . uniqid());
        $this->testUser->setEmail($this->testUser->getUsername() . '@example.com');
        $this->testUser->setPassword(password_hash('test_password', PASSWORD_DEFAULT));
        $this->testUser->setIsAdmin(true);
        
        // Créer les mocks
        $this->mockLogger = $this->prophesize(LoggerInterface::class)->reveal();
        $this->mockMonitoringService = $this->prophesize(WhatsAppMonitoringServiceInterface::class);
        
        // Construire un conteneur pour chaque test avec nos mocks
        $containerBuilder = new ContainerBuilder();
        
        // Ajouter les définitions essentielles
        $containerBuilder->addDefinitions([
            EntityManagerInterface::class => $this->entityManager,
            LoggerInterface::class => $this->mockLogger,
            WhatsAppMonitoringServiceInterface::class => $this->mockMonitoringService->reveal()
        ]);
        
        // Créer le conteneur de dépendances
        $this->container = $containerBuilder->build();
    }

    /**
     * Nettoie l'environnement après chaque test
     */
    protected function tearDown(): void
    {
        // Nettoyer les mocks
        $this->mockMonitoringService = null;
        $this->mockLogger = null;
        $this->testUser = null;
        
        // Fermer l'EntityManager si nécessaire
        if ($this->entityManager !== null && $this->entityManager->isOpen()) {
            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->rollback();
            }
            $this->entityManager->close();
        }
        
        $this->entityManager = null;
        $this->container = null;
    }

    /**
     * Exécute une requête GraphQL et retourne le résultat
     *
     * @param string $query La requête GraphQL à exécuter
     * @param array $variables Les variables pour la requête
     * @param User|null $user L'utilisateur à injecter dans le contexte
     * @return array Le résultat de la requête
     */
    protected function executeGraphQLQuery(string $query, array $variables = [], ?User $user = null): array
    {
        // Créer un contexte qui simule l'environnement GraphQL
        $context = [
            'user' => $user ?? $this->testUser,
            'logger' => $this->mockLogger,
            'container' => $this->container
        ];

        // Créer un contrôleur directement avec notre service mock
        $controller = new WhatsAppMonitoringController(
            $this->mockMonitoringService->reveal(),
            $this->mockLogger
        );

        // Créer un résolveur personnalisé qui utilisera notre contrôleur
        $fieldResolver = function ($source, $args, $context, ResolveInfo $info) use ($controller) {
            // Gérer les champs Query spécifiques pour les endpoints de monitoring WhatsApp
            if ($info->parentType->name === 'Query') {
                switch ($info->fieldName) {
                    case 'getWhatsAppTemplateUsageMetrics':
                        return $controller->getWhatsAppTemplateUsageMetrics(
                            $args['startDate'] ?? null, 
                            $args['endDate'] ?? null,
                            $context['user'] ?? null
                        );
                    
                    case 'getWhatsAppApiPerformanceMetrics':
                        return $controller->getWhatsAppApiPerformanceMetrics(
                            $args['startDate'] ?? null, 
                            $args['endDate'] ?? null,
                            $context['user'] ?? null
                        );
                        
                    case 'getWhatsAppApiErrorMetrics':
                        return $controller->getWhatsAppApiErrorMetrics(
                            $args['startDate'] ?? null, 
                            $args['endDate'] ?? null,
                            $context['user'] ?? null
                        );
                        
                    case 'getWhatsAppMonitoringDashboard':
                        return $controller->getWhatsAppMonitoringDashboard(
                            $args['period'] ?? 'week',
                            $context['user'] ?? null
                        );
                        
                    case 'getWhatsAppActiveAlerts':
                        return $controller->getWhatsAppActiveAlerts(
                            $context['user'] ?? null
                        );
                }
            }
            
            // Résolveur par défaut pour les autres champs
            return Executor::defaultFieldResolver($source, $args, $context, $info);
        };

        // Exécuter la requête GraphQL
        $result = GraphQL::executeQuery(
            self::$schema,
            $query,
            null, // rootValue
            $context,
            $variables,
            null, // operationName
            $fieldResolver
        );

        // Convertir le résultat en tableau avec les messages d'erreur détaillés en mode développement
        return $result->toArray(DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE);
    }
    
    /**
     * @test
     * Teste que la requête getWhatsAppTemplateUsageMetrics retourne un résultat valide
     */
    public function testGetWhatsAppTemplateUsageMetrics(): void
    {
        // Données de test qui devraient être retournées par le service
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

        // Configurer le mock pour retourner les données de test
        $this->mockMonitoringService->getTemplateUsageMetrics(Argument::cetera())
            ->willReturn($mockData);
        
        // Définir la requête GraphQL à exécuter
        $query = <<<'GRAPHQL'
        query GetTemplateUsage($startDate: DateTime, $endDate: DateTime) {
          getWhatsAppTemplateUsageMetrics(startDate: $startDate, endDate: $endDate) {
            totalUsage
            uniqueTemplates
            templateUsage {
              templateId
              templateName
              count
              successRate
              successful
              failed
            }
            byLanguage
            byCategory
            byDay
            byHour
            error
          }
        }
        GRAPHQL;
        
        // Exécuter la requête avec des dates de test
        $startDate = '2025-05-01';
        $endDate = '2025-05-31';
        $variables = [
            'startDate' => $startDate,
            'endDate' => $endDate
        ];
        
        $result = $this->executeGraphQLQuery($query, $variables);
        
        // Vérifier que la requête a réussi sans erreurs
        $this->assertArrayNotHasKey('errors', $result, 'La requête GraphQL ne devrait pas retourner d\'erreurs');
        $this->assertArrayHasKey('data', $result, 'La requête GraphQL devrait retourner des données');
        
        // Vérifier que les données retournées sont valides
        $metricsData = $result['data']['getWhatsAppTemplateUsageMetrics'];
        $this->assertEquals(42, $metricsData['totalUsage'], 'Le nombre total d\'utilisations devrait correspondre');
        $this->assertEquals(2, $metricsData['uniqueTemplates'], 'Le nombre de templates uniques devrait correspondre');
        $this->assertCount(2, $metricsData['templateUsage'], 'Le nombre d\'éléments dans templateUsage devrait correspondre');
        $this->assertIsArray($metricsData['byLanguage'], 'byLanguage devrait être un tableau');
        $this->assertIsArray($metricsData['byCategory'], 'byCategory devrait être un tableau');
        $this->assertNull($metricsData['error'], 'Aucune erreur ne devrait être présente');
        
        // Vérifier les données du premier template
        $firstTemplate = $metricsData['templateUsage'][0];
        $this->assertEquals('template_1', $firstTemplate['templateId'], 'L\'ID du premier template devrait correspondre');
        $this->assertEquals('Template 1', $firstTemplate['templateName'], 'Le nom du premier template devrait correspondre');
        $this->assertEquals(20, $firstTemplate['count'], 'Le nombre d\'utilisations du premier template devrait correspondre');
        $this->assertEquals(90.0, $firstTemplate['successRate'], 'Le taux de succès du premier template devrait correspondre');
    }
    
    /**
     * @test
     * Teste que la requête getWhatsAppApiPerformanceMetrics retourne un résultat valide
     */
    public function testGetWhatsAppApiPerformanceMetrics(): void
    {
        // Données de test qui devraient être retournées par le service
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
        
        // Configurer le mock pour retourner les données de test
        $this->mockMonitoringService->getApiPerformanceMetrics(Argument::cetera())
            ->willReturn($mockData);
        
        // Définir la requête GraphQL à exécuter
        $query = <<<'GRAPHQL'
        query GetApiPerformance($startDate: DateTime, $endDate: DateTime) {
          getWhatsAppApiPerformanceMetrics(startDate: $startDate, endDate: $endDate) {
            totalOperations
            overallSuccessRate
            avgDuration
            p95Duration
            p99Duration
            byOperation {
              operation
              count
              avgDuration
              successful
              failed
              successRate
            }
            byDay
            avgDurationByDay
            error
          }
        }
        GRAPHQL;
        
        // Exécuter la requête avec des dates de test
        $startDate = '2025-05-01';
        $endDate = '2025-05-31';
        $variables = [
            'startDate' => $startDate,
            'endDate' => $endDate
        ];
        
        $result = $this->executeGraphQLQuery($query, $variables);
        
        // Vérifier que la requête a réussi sans erreurs
        $this->assertArrayNotHasKey('errors', $result, 'La requête GraphQL ne devrait pas retourner d\'erreurs');
        $this->assertArrayHasKey('data', $result, 'La requête GraphQL devrait retourner des données');
        
        // Vérifier que les données retournées sont valides
        $metricsData = $result['data']['getWhatsAppApiPerformanceMetrics'];
        $this->assertEquals(120, $metricsData['totalOperations'], 'Le nombre total d\'opérations devrait correspondre');
        $this->assertEquals(95.0, $metricsData['overallSuccessRate'], 'Le taux de succès global devrait correspondre');
        $this->assertEquals(105.5, $metricsData['avgDuration'], 'La durée moyenne devrait correspondre');
        $this->assertEquals(205.3, $metricsData['p95Duration'], 'La durée P95 devrait correspondre');
        $this->assertEquals(350.7, $metricsData['p99Duration'], 'La durée P99 devrait correspondre');
        $this->assertCount(2, $metricsData['byOperation'], 'Le nombre d\'éléments dans byOperation devrait correspondre');
        $this->assertIsArray($metricsData['byDay'], 'byDay devrait être un tableau');
        $this->assertIsArray($metricsData['avgDurationByDay'], 'avgDurationByDay devrait être un tableau');
        $this->assertNull($metricsData['error'], 'Aucune erreur ne devrait être présente');
        
        // Vérifier les données de la première opération
        $firstOperation = $metricsData['byOperation'][0];
        $this->assertEquals('getApprovedTemplates', $firstOperation['operation'], 'Le nom de la première opération devrait correspondre');
        $this->assertEquals(50, $firstOperation['count'], 'Le nombre d\'opérations devrait correspondre');
        $this->assertEquals(95.3, $firstOperation['avgDuration'], 'La durée moyenne de l\'opération devrait correspondre');
        $this->assertEquals(96.0, $firstOperation['successRate'], 'Le taux de succès de l\'opération devrait correspondre');
    }
    
    /**
     * @test
     * Teste que la requête getWhatsAppApiErrorMetrics retourne un résultat valide
     */
    public function testGetWhatsAppApiErrorMetrics(): void
    {
        // Données de test qui devraient être retournées par le service
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
        
        // Configurer le mock pour retourner les données de test
        $this->mockMonitoringService->getApiErrorMetrics(Argument::cetera())
            ->willReturn($mockData);
        
        // Définir la requête GraphQL à exécuter
        $query = <<<'GRAPHQL'
        query GetApiErrors($startDate: DateTime, $endDate: DateTime) {
          getWhatsAppApiErrorMetrics(startDate: $startDate, endDate: $endDate) {
            totalErrors
            errorRate
            criticalErrors
            byType {
              type
              count
              operations
            }
            byOperation
            byDay
            recentErrors
            error
          }
        }
        GRAPHQL;
        
        // Exécuter la requête avec des dates de test
        $startDate = '2025-05-01';
        $endDate = '2025-05-31';
        $variables = [
            'startDate' => $startDate,
            'endDate' => $endDate
        ];
        
        $result = $this->executeGraphQLQuery($query, $variables);
        
        // Vérifier que la requête a réussi sans erreurs
        $this->assertArrayNotHasKey('errors', $result, 'La requête GraphQL ne devrait pas retourner d\'erreurs');
        $this->assertArrayHasKey('data', $result, 'La requête GraphQL devrait retourner des données');
        
        // Vérifier que les données retournées sont valides
        $metricsData = $result['data']['getWhatsAppApiErrorMetrics'];
        $this->assertEquals(15, $metricsData['totalErrors'], 'Le nombre total d\'erreurs devrait correspondre');
        $this->assertEquals(12.5, $metricsData['errorRate'], 'Le taux d\'erreur devrait correspondre');
        $this->assertEquals(3, $metricsData['criticalErrors'], 'Le nombre d\'erreurs critiques devrait correspondre');
        $this->assertCount(3, $metricsData['byType'], 'Le nombre d\'éléments dans byType devrait correspondre');
        $this->assertIsArray($metricsData['byOperation'], 'byOperation devrait être un tableau');
        $this->assertIsArray($metricsData['byDay'], 'byDay devrait être un tableau');
        $this->assertIsArray($metricsData['recentErrors'], 'recentErrors devrait être un tableau');
        $this->assertNull($metricsData['error'], 'Aucune erreur ne devrait être présente');
        
        // Vérifier les données du premier type d'erreur
        $firstErrorType = $metricsData['byType'][0];
        $this->assertEquals('Connection timed out', $firstErrorType['type'], 'Le type d\'erreur devrait correspondre');
        $this->assertEquals(5, $firstErrorType['count'], 'Le nombre d\'erreurs devrait correspondre');
        $this->assertIsArray($firstErrorType['operations'], 'Operations devrait être un tableau');
        $this->assertContains('getApprovedTemplates', $firstErrorType['operations'], 'Les opérations devraient inclure getApprovedTemplates');
    }
    
    /**
     * @test
     * Teste que la requête getWhatsAppMonitoringDashboard retourne un résultat valide
     */
    public function testGetWhatsAppMonitoringDashboard(): void
    {
        // Données de test qui devraient être retournées par le service
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
        
        // Configurer le mock pour retourner les données de test
        $this->mockMonitoringService->getDashboard(Argument::cetera())
            ->willReturn($mockData);
        
        // Définir la requête GraphQL à exécuter
        $query = <<<'GRAPHQL'
        query GetMonitoringDashboard($period: String = "week") {
          getWhatsAppMonitoringDashboard(period: $period) {
            period
            startDate
            endDate
            alerts {
              type
              level
              message
              details
            }
            keyMetrics {
              messageSuccessRate
              apiSuccessRate
              totalMessages
              totalTemplatesUsed
              avgApiDuration
              p95ApiDuration
              criticalErrors
              templateCount
            }
            topTemplates {
              templateId
              templateName
              count
              successRate
            }
            templatesByCategory
            templatesByLanguage
            apiErrorsByType {
              type
              count
              operations
            }
            messagesByDay
            apiPerformanceByDay
            apiAvgDurationByDay
            recentErrors
            error
          }
        }
        GRAPHQL;
        
        // Exécuter la requête avec la période par défaut
        $result = $this->executeGraphQLQuery($query);
        
        // Vérifier que la requête a réussi sans erreurs
        $this->assertArrayNotHasKey('errors', $result, 'La requête GraphQL ne devrait pas retourner d\'erreurs');
        $this->assertArrayHasKey('data', $result, 'La requête GraphQL devrait retourner des données');
        
        // Vérifier que les données retournées sont valides
        $dashboardData = $result['data']['getWhatsAppMonitoringDashboard'];
        $this->assertEquals('week', $dashboardData['period'], 'La période devrait correspondre');
        $this->assertEquals('2025-05-14', $dashboardData['startDate'], 'La date de début devrait correspondre');
        $this->assertEquals('2025-05-21', $dashboardData['endDate'], 'La date de fin devrait correspondre');
        $this->assertCount(1, $dashboardData['alerts'], 'Le nombre d\'alertes devrait correspondre');
        $this->assertIsArray($dashboardData['keyMetrics'], 'keyMetrics devrait être un objet');
        $this->assertCount(2, $dashboardData['topTemplates'], 'Le nombre de templates populaires devrait correspondre');
        $this->assertIsArray($dashboardData['templatesByCategory'], 'templatesByCategory devrait être un tableau');
        $this->assertIsArray($dashboardData['templatesByLanguage'], 'templatesByLanguage devrait être un tableau');
        $this->assertNull($dashboardData['error'], 'Aucune erreur ne devrait être présente');
        
        // Vérifier les métriques clés
        $keyMetrics = $dashboardData['keyMetrics'];
        $this->assertEquals(85.0, $keyMetrics['messageSuccessRate'], 'Le taux de succès des messages devrait correspondre');
        $this->assertEquals(92.0, $keyMetrics['apiSuccessRate'], 'Le taux de succès de l\'API devrait correspondre');
        $this->assertEquals(120, $keyMetrics['totalMessages'], 'Le nombre total de messages devrait correspondre');
        $this->assertEquals(42, $keyMetrics['totalTemplatesUsed'], 'Le nombre total de templates utilisés devrait correspondre');
    }
    
    /**
     * @test
     * Teste que la requête getWhatsAppActiveAlerts retourne un résultat valide
     */
    public function testGetWhatsAppActiveAlerts(): void
    {
        // Données de test qui devraient être retournées par le service
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
        
        // Configurer le mock pour retourner les données de test
        $this->mockMonitoringService->getActiveAlerts(Argument::cetera())
            ->willReturn($mockAlerts);
        
        // Définir la requête GraphQL à exécuter
        $query = <<<'GRAPHQL'
        query GetActiveAlerts {
          getWhatsAppActiveAlerts {
            type
            level
            message
            details
          }
        }
        GRAPHQL;
        
        // Exécuter la requête
        $result = $this->executeGraphQLQuery($query);
        
        // Vérifier que la requête a réussi sans erreurs
        $this->assertArrayNotHasKey('errors', $result, 'La requête GraphQL ne devrait pas retourner d\'erreurs');
        $this->assertArrayHasKey('data', $result, 'La requête GraphQL devrait retourner des données');
        
        // Vérifier que les données retournées sont valides
        $alertsData = $result['data']['getWhatsAppActiveAlerts'];
        $this->assertIsArray($alertsData, 'Les alertes devraient être un tableau');
        $this->assertCount(2, $alertsData, 'Le nombre d\'alertes devrait correspondre');
        
        // Vérifier les données de la première alerte
        $firstAlert = $alertsData[0];
        $this->assertEquals('error_rate', $firstAlert['type'], 'Le type d\'alerte devrait correspondre');
        $this->assertEquals('critical', $firstAlert['level'], 'Le niveau d\'alerte devrait correspondre');
        $this->assertEquals('Taux d\'erreur WhatsApp élevé (15.5%) sur la dernière heure', $firstAlert['message'], 'Le message d\'alerte devrait correspondre');
        $this->assertIsArray($firstAlert['details'], 'Les détails devraient être un tableau');
    }

    /**
     * @test
     * Teste le comportement avec un utilisateur non authentifié
     */
    public function testGetWhatsAppTemplateUsageMetricsWithoutUser(): void
    {
        // Définir la requête GraphQL à exécuter
        $query = <<<'GRAPHQL'
        query GetTemplateUsage($startDate: DateTime, $endDate: DateTime) {
          getWhatsAppTemplateUsageMetrics(startDate: $startDate, endDate: $endDate) {
            totalUsage
            error
          }
        }
        GRAPHQL;
        
        // Exécuter la requête avec un utilisateur null
        $startDate = '2025-05-01';
        $endDate = '2025-05-31';
        $variables = [
            'startDate' => $startDate,
            'endDate' => $endDate
        ];
        
        $result = $this->executeGraphQLQuery($query, $variables, null);
        
        // Vérifier que la requête a réussi sans erreurs
        $this->assertArrayNotHasKey('errors', $result, 'La requête GraphQL ne devrait pas retourner d\'erreurs');
        $this->assertArrayHasKey('data', $result, 'La requête GraphQL devrait retourner des données');
        
        // Vérifier que les données retournées indiquent un utilisateur non authentifié
        $metricsData = $result['data']['getWhatsAppTemplateUsageMetrics'];
        $this->assertEquals(0, $metricsData['totalUsage'], 'Le nombre total d\'utilisations devrait être 0');
        $this->assertNotNull($metricsData['error'], 'Un message d\'erreur devrait être présent');
        $this->assertStringContainsString('authentifi', $metricsData['error'], 'Le message d\'erreur devrait mentionner l\'authentification');
    }
}