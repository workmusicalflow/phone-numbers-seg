<?php

namespace Tests\Repositories;

use App\Entities\WhatsApp\WhatsAppApiMetric;
use App\Repositories\Doctrine\WhatsApp\WhatsAppApiMetricRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

/**
 * Tests pour le repository WhatsAppApiMetric
 */
class WhatsAppApiMetricRepositoryTest extends TestCase
{
    /**
     * @var EntityManagerInterface|MockObject
     */
    private $mockedEntityManager;
    
    /**
     * @var EntityRepository|MockObject
     */
    private $innerRepository;
    
    /**
     * @var QueryBuilder|MockObject
     */
    private $queryBuilder;
    
    /**
     * @var \Doctrine\ORM\Query|MockObject
     */
    private $query;
    
    /**
     * @var WhatsAppApiMetricRepository
     */
    private $repository;
    
    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer les mocks
        $this->mockedEntityManager = $this->createMock(EntityManagerInterface::class);
        $this->innerRepository = $this->createMock(EntityRepository::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->query = $this->getMockBuilder('Doctrine\ORM\Query')
            ->disableOriginalConstructor()
            ->getMock();
        
        // Configuration du mock EntityManager
        $this->mockedEntityManager->expects($this->any())
            ->method('getRepository')
            ->with(WhatsAppApiMetric::class)
            ->willReturn($this->innerRepository);
            
        // Configuration du QueryBuilder
        $this->queryBuilder->expects($this->any())
            ->method('from')
            ->willReturn($this->queryBuilder);
        $this->queryBuilder->expects($this->any())
            ->method('select')
            ->willReturn($this->queryBuilder);
        $this->queryBuilder->expects($this->any())
            ->method('addSelect')
            ->willReturn($this->queryBuilder);
        $this->queryBuilder->expects($this->any())
            ->method('where')
            ->willReturn($this->queryBuilder);
        $this->queryBuilder->expects($this->any())
            ->method('andWhere')
            ->willReturn($this->queryBuilder);
        $this->queryBuilder->expects($this->any())
            ->method('setParameter')
            ->willReturn($this->queryBuilder);
        $this->queryBuilder->expects($this->any())
            ->method('orderBy')
            ->willReturn($this->queryBuilder);
        $this->queryBuilder->expects($this->any())
            ->method('groupBy')
            ->willReturn($this->queryBuilder);
        $this->queryBuilder->expects($this->any())
            ->method('getQuery')
            ->willReturn($this->query);
            
        // Configuration du mock de l'EntityManager pour createQueryBuilder
        $this->mockedEntityManager->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);
            
        // Créer le repository à tester
        $this->repository = new WhatsAppApiMetricRepository($this->mockedEntityManager);
    }
    
    /**
     * Test de save
     */
    public function testSave(): void
    {
        // Créer une métrique pour le test
        $metric = new WhatsAppApiMetric();
        $metric->setUserId(1);
        $metric->setOperation('testOperation');
        $metric->setDuration(123.45);
        $metric->setSuccess(true);
        $metric->setCreatedAt(new \DateTime());
        
        // Configurer l'EntityManager pour vérifier persist et flush
        $this->mockedEntityManager->expects($this->once())
            ->method('persist')
            ->with($this->equalTo($metric));
            
        $this->mockedEntityManager->expects($this->once())
            ->method('flush');
            
        // Appeler la méthode à tester
        $result = $this->repository->save($metric);
        
        // Vérifier le résultat
        $this->assertSame($metric, $result);
    }
    
    /**
     * Test de findBy
     */
    public function testFindBy(): void
    {
        // Créer des métriques de test
        $metrics = [
            $this->createMetric(1, 'testOp1', 100, true),
            $this->createMetric(1, 'testOp2', 200, false)
        ];
        
        // Configurer le repository interne
        $this->innerRepository->expects($this->once())
            ->method('findBy')
            ->with(
                ['userId' => 1],
                ['createdAt' => 'DESC'],
                10,
                0
            )
            ->willReturn($metrics);
            
        // Appeler la méthode à tester
        $result = $this->repository->findBy(
            ['userId' => 1],
            ['createdAt' => 'DESC'],
            10,
            0
        );
        
        // Vérifier le résultat
        $this->assertSame($metrics, $result);
    }
    
    /**
     * Test de count
     */
    public function testCount(): void
    {
        // Configurer Query mock
        $this->query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn('5');
            
        // Appeler la méthode à tester
        $count = $this->repository->count(['userId' => 1]);
        
        // Vérifier le résultat
        $this->assertEquals(5, $count);
    }
    
    /**
     * Test de count avec des critères utilisant des opérateurs
     */
    public function testCountWithOperators(): void
    {
        // Critères avec opérateurs
        $criteria = [
            'userId' => 1,
            'duration > ' => 100.0,
            'createdAt < ' => new \DateTime()
        ];
        
        // Configurer Query mock
        $this->query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn('3');
            
        // Appeler la méthode à tester
        $count = $this->repository->count($criteria);
        
        // Vérifier le résultat
        $this->assertEquals(3, $count);
    }
    
    /**
     * Test de getAverageDuration
     */
    public function testGetAverageDuration(): void
    {
        // Configurer Query mock
        $this->query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn('123.45');
            
        // Appeler la méthode à tester
        $avgDuration = $this->repository->getAverageDuration(['userId' => 1, 'success' => true]);
        
        // Vérifier le résultat
        $this->assertEquals(123.45, $avgDuration);
    }
    
    /**
     * Test de getAverageDuration avec résultat null (aucune métrique)
     */
    public function testGetAverageDurationWithNullResult(): void
    {
        // Configurer Query mock pour retourner null (aucune métrique trouvée)
        $this->query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(null);
            
        // Appeler la méthode à tester
        $avgDuration = $this->repository->getAverageDuration(['userId' => 1, 'success' => true]);
        
        // Vérifier que le résultat est 0.0
        $this->assertEquals(0.0, $avgDuration);
    }
    
    /**
     * Test de getAverageDuration avec des critères utilisant des opérateurs
     */
    public function testGetAverageDurationWithOperators(): void
    {
        // Critères avec opérateurs
        $criteria = [
            'userId' => 1,
            'duration > ' => 100.0,
            'createdAt < ' => new \DateTime()
        ];
        
        // Configurer Query mock
        $this->query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn('250.75');
            
        // Appeler la méthode à tester
        $avgDuration = $this->repository->getAverageDuration($criteria);
        
        // Vérifier le résultat
        $this->assertEquals(250.75, $avgDuration);
    }
    
    /**
     * Test de getP95Duration
     */
    public function testGetP95Duration(): void
    {
        // Créer des données de test pour les durées
        $durations = [
            ['duration' => 10.0],
            ['duration' => 20.0],
            ['duration' => 30.0],
            ['duration' => 40.0],
            ['duration' => 50.0],
            ['duration' => 60.0],
            ['duration' => 70.0],
            ['duration' => 80.0],
            ['duration' => 90.0],
            ['duration' => 100.0]
        ];
        
        // Configurer Query mock
        $this->query->expects($this->once())
            ->method('getScalarResult')
            ->willReturn($durations);
            
        // Appeler la méthode à tester
        $p95Duration = $this->repository->getP95Duration(['userId' => 1, 'success' => true]);
        
        // Les valeurs p95 peuvent varier selon l'implémentation exacte de l'algorithme
        // L'important est que nous récupérions une valeur dans le bon ordre de grandeur
        $this->assertLessThanOrEqual(100.0, $p95Duration);
        $this->assertGreaterThanOrEqual(90.0, $p95Duration);
    }
    
    /**
     * Test de getP95Duration avec un résultat vide
     */
    public function testGetP95DurationWithEmptyResult(): void
    {
        // Configurer Query mock pour retourner un tableau vide
        $this->query->expects($this->once())
            ->method('getScalarResult')
            ->willReturn([]);
            
        // Appeler la méthode à tester
        $p95Duration = $this->repository->getP95Duration(['userId' => 1, 'success' => true]);
        
        // Vérifier que le résultat est 0.0 lorsqu'il n'y a pas de données
        $this->assertEquals(0.0, $p95Duration);
    }
    
    /**
     * Test de getP95Duration avec une seule valeur
     */
    public function testGetP95DurationWithSingleValue(): void
    {
        // Configurer Query mock pour retourner une seule valeur
        $this->query->expects($this->once())
            ->method('getScalarResult')
            ->willReturn([
                ['duration' => 123.45]
            ]);
            
        // Appeler la méthode à tester
        $p95Duration = $this->repository->getP95Duration(['userId' => 1, 'success' => true]);
        
        // Vérifier que le résultat est la valeur unique
        $this->assertEquals(123.45, $p95Duration);
    }
    
    /**
     * Test de getMetricsByDay
     */
    public function testGetMetricsByDay(): void
    {
        $startDate = new \DateTime();
        
        // Données de test pour les métriques par jour
        $queryResult = [
            [
                'day' => '2025-05-20',
                'count' => '10',
                'avgDuration' => '123.45',
                'successful' => '8',
                'failed' => '2'
            ],
            [
                'day' => '2025-05-21',
                'count' => '5',
                'avgDuration' => '234.56',
                'successful' => '4',
                'failed' => '1'
            ]
        ];
        
        // Configurer Query mock
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($queryResult);
            
        // Appeler la méthode à tester
        $result = $this->repository->getMetricsByDay(1, $startDate);
        
        // Vérifier le résultat
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('2025-05-20', $result);
        $this->assertArrayHasKey('2025-05-21', $result);
        
        // Vérifier les données du premier jour
        $this->assertEquals('2025-05-20', $result['2025-05-20']['day']);
        $this->assertEquals(10, $result['2025-05-20']['count']);
        $this->assertEquals(123.45, $result['2025-05-20']['avg_duration']);
        $this->assertEquals(8, $result['2025-05-20']['successful']);
        $this->assertEquals(2, $result['2025-05-20']['failed']);
        $this->assertEquals(80.0, $result['2025-05-20']['success_rate']);
    }
    
    /**
     * Test de getMetricsByDay avec endDate spécifiée
     */
    public function testGetMetricsByDayWithEndDate(): void
    {
        $startDate = new \DateTime('2025-05-01');
        $endDate = new \DateTime('2025-05-31');
        
        // Données de test pour les métriques par jour
        $queryResult = [
            [
                'day' => '2025-05-20',
                'count' => '10',
                'avgDuration' => '123.45',
                'successful' => '8',
                'failed' => '2'
            ],
            [
                'day' => '2025-05-21',
                'count' => '5',
                'avgDuration' => '234.56',
                'successful' => '4',
                'failed' => '1'
            ]
        ];
        
        // Configurer Query mock
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($queryResult);
            
        // On vérifie simplement que la méthode andWhere est appelée, sans spécifier l'ordre exact
        // car PHPUnit 9 ne supporte plus correctement this->at()
        $this->queryBuilder->expects($this->atLeastOnce())
            ->method('andWhere')
            ->willReturn($this->queryBuilder);
            
        $this->queryBuilder->expects($this->atLeastOnce())
            ->method('setParameter')
            ->willReturn($this->queryBuilder);
            
        // Appeler la méthode à tester
        $result = $this->repository->getMetricsByDay(1, $startDate, $endDate);
        
        // Vérifier le résultat
        $this->assertCount(2, $result);
    }
    
    /**
     * Test de getMetricsByDay avec un résultat vide
     */
    public function testGetMetricsByDayWithEmptyResult(): void
    {
        $startDate = new \DateTime();
        
        // Configurer Query mock pour retourner un tableau vide
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);
            
        // Appeler la méthode à tester
        $result = $this->repository->getMetricsByDay(1, $startDate);
        
        // Vérifier que le résultat est un tableau vide
        $this->assertEmpty($result);
        $this->assertIsArray($result);
    }
    
    /**
     * Test de getMetricsByOperation
     */
    public function testGetMetricsByOperation(): void
    {
        $startDate = new \DateTime();
        
        // Données de test pour les métriques par opération
        $queryResult = [
            [
                'operation' => 'getApprovedTemplates',
                'count' => '10',
                'avgDuration' => '123.45',
                'successful' => '8',
                'failed' => '2'
            ],
            [
                'operation' => 'getTemplateById',
                'count' => '5',
                'avgDuration' => '234.56',
                'successful' => '4',
                'failed' => '1'
            ]
        ];
        
        // Configurer Query mock
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($queryResult);
            
        // Appeler la méthode à tester
        $result = $this->repository->getMetricsByOperation(1, $startDate);
        
        // Vérifier le résultat
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('getApprovedTemplates', $result);
        $this->assertArrayHasKey('getTemplateById', $result);
        
        // Vérifier les données de la première opération
        $this->assertEquals('getApprovedTemplates', $result['getApprovedTemplates']['operation']);
        $this->assertEquals(10, $result['getApprovedTemplates']['count']);
        $this->assertEquals(123.45, $result['getApprovedTemplates']['avg_duration']);
        $this->assertEquals(8, $result['getApprovedTemplates']['successful']);
        $this->assertEquals(2, $result['getApprovedTemplates']['failed']);
        $this->assertEquals(80.0, $result['getApprovedTemplates']['success_rate']);
    }
    
    /**
     * Test de getMetricsByOperation avec endDate spécifiée
     */
    public function testGetMetricsByOperationWithEndDate(): void
    {
        $startDate = new \DateTime('2025-05-01');
        $endDate = new \DateTime('2025-05-31');
        
        // Données de test pour les métriques par opération
        $queryResult = [
            [
                'operation' => 'getApprovedTemplates',
                'count' => '10',
                'avgDuration' => '123.45',
                'successful' => '8',
                'failed' => '2'
            ],
            [
                'operation' => 'getTemplateById',
                'count' => '5',
                'avgDuration' => '234.56',
                'successful' => '4',
                'failed' => '1'
            ]
        ];
        
        // Configurer Query mock
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($queryResult);
            
        // On vérifie simplement que la méthode andWhere est appelée, sans spécifier l'ordre exact
        // car PHPUnit 9 ne supporte plus correctement this->at()
        $this->queryBuilder->expects($this->atLeastOnce())
            ->method('andWhere')
            ->willReturn($this->queryBuilder);
            
        $this->queryBuilder->expects($this->atLeastOnce())
            ->method('setParameter')
            ->willReturn($this->queryBuilder);
            
        // Appeler la méthode à tester
        $result = $this->repository->getMetricsByOperation(1, $startDate, $endDate);
        
        // Vérifier le résultat
        $this->assertCount(2, $result);
    }
    
    /**
     * Test de getMetricsByOperation avec un résultat vide
     */
    public function testGetMetricsByOperationWithEmptyResult(): void
    {
        $startDate = new \DateTime();
        
        // Configurer Query mock pour retourner un tableau vide
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);
            
        // Appeler la méthode à tester
        $result = $this->repository->getMetricsByOperation(1, $startDate);
        
        // Vérifier que le résultat est un tableau vide
        $this->assertEmpty($result);
        $this->assertIsArray($result);
    }
    
    /**
     * Crée une métrique de test
     * 
     * @param int $userId
     * @param string $operation
     * @param float $duration
     * @param bool $success
     * @return WhatsAppApiMetric
     */
    private function createMetric(int $userId, string $operation, float $duration, bool $success): WhatsAppApiMetric
    {
        $metric = new WhatsAppApiMetric();
        $metric->setUserId($userId);
        $metric->setOperation($operation);
        $metric->setDuration($duration);
        $metric->setSuccess($success);
        $metric->setCreatedAt(new \DateTime());
        
        if (!$success) {
            $metric->setErrorMessage('Test error');
        }
        
        return $metric;
    }
}