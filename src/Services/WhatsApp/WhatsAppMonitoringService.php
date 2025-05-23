<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Entities\User;
use App\Entities\WhatsApp\WhatsAppApiMetric;
use App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateHistoryRepositoryInterface;
use App\Repositories\Interfaces\WhatsApp\WhatsAppMessageHistoryRepositoryInterface;
use App\Repositories\Interfaces\WhatsApp\WhatsAppApiMetricRepositoryInterface;
use App\Services\Interfaces\WhatsApp\WhatsAppMonitoringServiceInterface;
use Psr\Log\LoggerInterface;

/**
 * Service pour le monitoring et les métriques de l'API WhatsApp
 */
class WhatsAppMonitoringService implements WhatsAppMonitoringServiceInterface
{
    /**
     * @var WhatsAppTemplateHistoryRepositoryInterface
     */
    private WhatsAppTemplateHistoryRepositoryInterface $templateHistoryRepository;
    
    /**
     * @var WhatsAppMessageHistoryRepositoryInterface
     */
    private WhatsAppMessageHistoryRepositoryInterface $messageHistoryRepository;
    
    /**
     * @var WhatsAppApiMetricRepositoryInterface
     */
    private WhatsAppApiMetricRepositoryInterface $apiMetricRepository;
    
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    
    /**
     * @var array
     */
    private array $cache = [];
    
    /**
     * @var int 
     */
    private int $cacheTtl = 300; // 5 minutes
    
    /**
     * Constructeur
     */
    public function __construct(
        WhatsAppTemplateHistoryRepositoryInterface $templateHistoryRepository,
        WhatsAppMessageHistoryRepositoryInterface $messageHistoryRepository,
        WhatsAppApiMetricRepositoryInterface $apiMetricRepository,
        LoggerInterface $logger
    ) {
        $this->templateHistoryRepository = $templateHistoryRepository;
        $this->messageHistoryRepository = $messageHistoryRepository;
        $this->apiMetricRepository = $apiMetricRepository;
        $this->logger = $logger;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getTemplateUsageMetrics(
        User $user,
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null
    ): array {
        $cacheKey = sprintf(
            'template_usage_metrics_%d_%s_%s',
            $user->getId(),
            $startDate ? $startDate->format('Ymd') : 'all',
            $endDate ? $endDate->format('Ymd') : 'now'
        );
        
        // Vérifier le cache
        if (isset($this->cache[$cacheKey]) && $this->cache[$cacheKey]['timestamp'] > (time() - $this->cacheTtl)) {
            return $this->cache[$cacheKey]['data'];
        }
        
        try {
            $this->logger->info('Récupération des métriques d\'utilisation des templates WhatsApp', [
                'user_id' => $user->getId(),
                'start_date' => $startDate ? $startDate->format('Y-m-d') : 'all',
                'end_date' => $endDate ? $endDate->format('Y-m-d') : 'now'
            ]);
            
            // Construire la requête
            $criteria = ['oracle_user_id' => $user->getId()];
            
            if ($startDate !== null) {
                $criteria['used_at >='] = $startDate;
            }
            
            if ($endDate !== null) {
                $criteria['used_at <='] = $endDate;
            }
            
            // Récupérer l'historique d'utilisation des templates
            $templateHistory = $this->templateHistoryRepository->findBy(
                $criteria,
                ['used_at' => 'DESC']
            );
            
            // Analyser les données pour générer les métriques
            $templateUsage = [];
            $templateByLanguage = [];
            $templateByCategory = [];
            $usageByDay = [];
            $usageByHour = [];
            
            foreach ($templateHistory as $entry) {
                $templateId = $entry->getTemplateId();
                $language = $entry->getLanguage();
                $category = $entry->getCategory();
                $usedAt = $entry->getUsedAt();
                
                // Usage par template
                if (!isset($templateUsage[$templateId])) {
                    $templateUsage[$templateId] = [
                        'template_id' => $templateId,
                        'template_name' => $entry->getTemplateName(),
                        'count' => 0,
                        'success_rate' => 0,
                        'successful' => 0,
                        'failed' => 0
                    ];
                }
                
                $templateUsage[$templateId]['count']++;
                
                if ($entry->getStatus() === 'sent' || $entry->getStatus() === 'delivered' || $entry->getStatus() === 'read') {
                    $templateUsage[$templateId]['successful']++;
                } else {
                    $templateUsage[$templateId]['failed']++;
                }
                
                // Calculer le taux de réussite
                $templateUsage[$templateId]['success_rate'] = round(
                    ($templateUsage[$templateId]['successful'] / $templateUsage[$templateId]['count']) * 100,
                    2
                );
                
                // Usage par langue
                if (!isset($templateByLanguage[$language])) {
                    $templateByLanguage[$language] = 0;
                }
                $templateByLanguage[$language]++;
                
                // Usage par catégorie
                if (!isset($templateByCategory[$category])) {
                    $templateByCategory[$category] = 0;
                }
                $templateByCategory[$category]++;
                
                // Usage par jour
                $day = $usedAt->format('Y-m-d');
                if (!isset($usageByDay[$day])) {
                    $usageByDay[$day] = 0;
                }
                $usageByDay[$day]++;
                
                // Usage par heure
                $hour = $usedAt->format('H');
                if (!isset($usageByHour[$hour])) {
                    $usageByHour[$hour] = 0;
                }
                $usageByHour[$hour]++;
            }
            
            // Trier par utilisation
            usort($templateUsage, function ($a, $b) {
                return $b['count'] - $a['count'];
            });
            
            $result = [
                'total_usage' => count($templateHistory),
                'template_usage' => array_values($templateUsage),
                'by_language' => $templateByLanguage,
                'by_category' => $templateByCategory,
                'by_day' => $usageByDay,
                'by_hour' => $usageByHour,
                'unique_templates' => count($templateUsage)
            ];
            
            // Mise en cache
            $this->cache[$cacheKey] = [
                'timestamp' => time(),
                'data' => $result
            ];
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des métriques d\'utilisation des templates', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);
            
            return [
                'total_usage' => 0,
                'template_usage' => [],
                'by_language' => [],
                'by_category' => [],
                'by_day' => [],
                'by_hour' => [],
                'unique_templates' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function getApiPerformanceMetrics(
        User $user,
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null,
        ?string $apiVersion = null
    ): array {
        $cacheKey = sprintf(
            'api_performance_metrics_%d_%s_%s_%s',
            $user->getId(),
            $startDate ? $startDate->format('Ymd') : 'all',
            $endDate ? $endDate->format('Ymd') : 'now',
            $apiVersion ?? 'all'
        );
        
        // Vérifier le cache
        if (isset($this->cache[$cacheKey]) && $this->cache[$cacheKey]['timestamp'] > (time() - $this->cacheTtl)) {
            return $this->cache[$cacheKey]['data'];
        }
        
        try {
            $this->logger->info('Récupération des métriques de performance de l\'API WhatsApp', [
                'user_id' => $user->getId(),
                'start_date' => $startDate ? $startDate->format('Y-m-d') : 'all',
                'end_date' => $endDate ? $endDate->format('Y-m-d') : 'now'
            ]);
            
            // Construire la requête
            $criteria = ['user_id' => $user->getId()];
            
            if ($startDate !== null) {
                $criteria['created_at >='] = $startDate;
            }
            
            if ($endDate !== null) {
                $criteria['created_at <='] = $endDate;
            }
            
            // Filtrer par version d'API si spécifiée
            if ($apiVersion !== null) {
                $criteria['api_version'] = $apiVersion;
            }
            
            // Récupérer les métriques de performance API
            $apiMetrics = $this->apiMetricRepository->findBy(
                $criteria,
                ['created_at' => 'DESC']
            );
            
            // Analyser les données pour générer les métriques
            $operationPerformance = [];
            $overallSuccessRate = 0;
            $totalOperations = 0;
            $successfulOperations = 0;
            $operationsByDay = [];
            $avgDurationByDay = [];
            $avgDuration = 0;
            $p95Duration = 0;
            $p99Duration = 0;
            
            // Collecter toutes les durées pour calculer les percentiles
            $allDurations = [];
            
            foreach ($apiMetrics as $metric) {
                $operation = $metric->getOperation();
                $success = $metric->isSuccess();
                $duration = $metric->getDuration();
                $createdAt = $metric->getCreatedAt();
                
                // Ajouter à la liste des durées
                $allDurations[] = $duration;
                
                // Performance par opération
                if (!isset($operationPerformance[$operation])) {
                    $operationPerformance[$operation] = [
                        'operation' => $operation,
                        'count' => 0,
                        'successful' => 0,
                        'failed' => 0,
                        'success_rate' => 0,
                        'avg_duration' => 0,
                        'total_duration' => 0
                    ];
                }
                
                $operationPerformance[$operation]['count']++;
                $operationPerformance[$operation]['total_duration'] += $duration;
                
                if ($success) {
                    $operationPerformance[$operation]['successful']++;
                    $successfulOperations++;
                } else {
                    $operationPerformance[$operation]['failed']++;
                }
                
                // Calculer le taux de réussite et la durée moyenne
                $operationPerformance[$operation]['success_rate'] = round(
                    ($operationPerformance[$operation]['successful'] / $operationPerformance[$operation]['count']) * 100,
                    2
                );
                
                $operationPerformance[$operation]['avg_duration'] = round(
                    $operationPerformance[$operation]['total_duration'] / $operationPerformance[$operation]['count'],
                    2
                );
                
                // Opérations par jour
                $day = $createdAt->format('Y-m-d');
                if (!isset($operationsByDay[$day])) {
                    $operationsByDay[$day] = 0;
                    $avgDurationByDay[$day] = ['total' => 0, 'count' => 0];
                }
                $operationsByDay[$day]++;
                $avgDurationByDay[$day]['total'] += $duration;
                $avgDurationByDay[$day]['count']++;
                
                $totalOperations++;
            }
            
            // Calculer les métriques globales
            if ($totalOperations > 0) {
                $overallSuccessRate = round(($successfulOperations / $totalOperations) * 100, 2);
                $avgDuration = round(array_sum($allDurations) / count($allDurations), 2);
                
                // Calculer la durée moyenne par jour
                foreach ($avgDurationByDay as $day => $data) {
                    $avgDurationByDay[$day] = round($data['total'] / $data['count'], 2);
                }
                
                // Calculer les percentiles
                sort($allDurations);
                $p95Index = (int)ceil(count($allDurations) * 0.95) - 1;
                $p99Index = (int)ceil(count($allDurations) * 0.99) - 1;
                
                if ($p95Index >= 0 && isset($allDurations[$p95Index])) {
                    $p95Duration = round($allDurations[$p95Index], 2);
                }
                
                if ($p99Index >= 0 && isset($allDurations[$p99Index])) {
                    $p99Duration = round($allDurations[$p99Index], 2);
                }
            }
            
            // Trier par nombre d'opérations
            usort($operationPerformance, function ($a, $b) {
                return $b['count'] - $a['count'];
            });
            
            $result = [
                'total_operations' => $totalOperations,
                'overall_success_rate' => $overallSuccessRate,
                'avg_duration' => $avgDuration,
                'p95_duration' => $p95Duration,
                'p99_duration' => $p99Duration,
                'by_operation' => array_values($operationPerformance),
                'by_day' => $operationsByDay,
                'avg_duration_by_day' => $avgDurationByDay
            ];
            
            // Mise en cache
            $this->cache[$cacheKey] = [
                'timestamp' => time(),
                'data' => $result
            ];
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des métriques de performance API', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);
            
            return [
                'total_operations' => 0,
                'overall_success_rate' => 0,
                'avg_duration' => 0,
                'p95_duration' => 0,
                'p99_duration' => 0,
                'by_operation' => [],
                'by_day' => [],
                'avg_duration_by_day' => [],
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function getApiErrorMetrics(
        User $user,
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null
    ): array {
        $cacheKey = sprintf(
            'api_error_metrics_%d_%s_%s',
            $user->getId(),
            $startDate ? $startDate->format('Ymd') : 'all',
            $endDate ? $endDate->format('Ymd') : 'now'
        );
        
        // Vérifier le cache
        if (isset($this->cache[$cacheKey]) && $this->cache[$cacheKey]['timestamp'] > (time() - $this->cacheTtl)) {
            return $this->cache[$cacheKey]['data'];
        }
        
        try {
            $this->logger->info('Récupération des métriques d\'erreur de l\'API WhatsApp', [
                'user_id' => $user->getId(),
                'start_date' => $startDate ? $startDate->format('Y-m-d') : 'all',
                'end_date' => $endDate ? $endDate->format('Y-m-d') : 'now'
            ]);
            
            // Construire la requête
            $criteria = [
                'user_id' => $user->getId(),
                'success' => false
            ];
            
            if ($startDate !== null) {
                $criteria['created_at >='] = $startDate;
            }
            
            if ($endDate !== null) {
                $criteria['created_at <='] = $endDate;
            }
            
            // Récupérer les métriques d'erreur API
            $errorMetrics = $this->apiMetricRepository->findBy(
                $criteria,
                ['created_at' => 'DESC']
            );
            
            // Récupérer également les métriques réussies pour le calcul du taux d'erreur
            $successCriteria = [
                'user_id' => $user->getId(),
                'success' => true
            ];
            
            if ($startDate !== null) {
                $successCriteria['created_at >='] = $startDate;
            }
            
            if ($endDate !== null) {
                $successCriteria['created_at <='] = $endDate;
            }
            
            $successCount = $this->apiMetricRepository->count($successCriteria);
            $totalOperations = count($errorMetrics) + $successCount;
            
            // Analyser les données pour générer les métriques
            $errorsByType = [];
            $errorsByOperation = [];
            $errorsByDay = [];
            $recentErrors = [];
            $criticalErrors = 0;
            
            foreach ($errorMetrics as $metric) {
                $operation = $metric->getOperation();
                $errorMessage = $metric->getErrorMessage() ?? 'Unknown error';
                $createdAt = $metric->getCreatedAt();
                
                // Simplifier le message d'erreur pour le regroupement
                $errorType = $this->simplifyErrorMessage($errorMessage);
                
                // Erreurs par type
                if (!isset($errorsByType[$errorType])) {
                    $errorsByType[$errorType] = [
                        'type' => $errorType,
                        'count' => 0,
                        'operations' => []
                    ];
                }
                
                $errorsByType[$errorType]['count']++;
                
                if (!in_array($operation, $errorsByType[$errorType]['operations'])) {
                    $errorsByType[$errorType]['operations'][] = $operation;
                }
                
                // Erreurs par opération
                if (!isset($errorsByOperation[$operation])) {
                    $errorsByOperation[$operation] = 0;
                }
                $errorsByOperation[$operation]++;
                
                // Erreurs par jour
                $day = $createdAt->format('Y-m-d');
                if (!isset($errorsByDay[$day])) {
                    $errorsByDay[$day] = 0;
                }
                $errorsByDay[$day]++;
                
                // Collecter les erreurs récentes (limité à 20)
                if (count($recentErrors) < 20) {
                    $recentErrors[] = [
                        'operation' => $operation,
                        'error_message' => $errorMessage,
                        'date' => $createdAt->format('Y-m-d H:i:s')
                    ];
                }
                
                // Compter les erreurs critiques
                if ($this->isCriticalError($errorMessage)) {
                    $criticalErrors++;
                }
            }
            
            // Trier par nombre d'erreurs
            usort($errorsByType, function ($a, $b) {
                return $b['count'] - $a['count'];
            });
            
            // Calculer le taux d'erreur global
            $errorRate = $totalOperations > 0 
                ? round((count($errorMetrics) / $totalOperations) * 100, 2) 
                : 0;
            
            $result = [
                'total_errors' => count($errorMetrics),
                'error_rate' => $errorRate,
                'critical_errors' => $criticalErrors,
                'by_type' => array_values($errorsByType),
                'by_operation' => $errorsByOperation,
                'by_day' => $errorsByDay,
                'recent_errors' => $recentErrors
            ];
            
            // Mise en cache
            $this->cache[$cacheKey] = [
                'timestamp' => time(),
                'data' => $result
            ];
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des métriques d\'erreur API', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);
            
            return [
                'total_errors' => 0,
                'error_rate' => 0,
                'critical_errors' => 0,
                'by_type' => [],
                'by_operation' => [],
                'by_day' => [],
                'recent_errors' => [],
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function recordApiPerformance(
        User $user,
        string $operation,
        float $duration,
        bool $success,
        ?string $errorMessage = null,
        string $apiVersion = 'v1',  // Ajout de la version de l'API
        string $endpoint = ''       // Ajout de l'endpoint spécifique
    ): void {
        try {
            $this->logger->debug('Enregistrement d\'une métrique de performance WhatsApp', [
                'user_id' => $user->getId(),
                'operation' => $operation,
                'api_version' => $apiVersion,
                'endpoint' => $endpoint,
                'duration' => $duration,
                'success' => $success
            ]);
            
            $metric = new WhatsAppApiMetric();
            $metric->setUserId($user->getId());
            $metric->setOperation($operation);
            $metric->setDuration($duration);
            $metric->setSuccess($success);
            $metric->setApiVersion($apiVersion);  // Stocker la version d'API
            $metric->setEndpoint($endpoint);      // Stocker l'endpoint
            
            if (!$success && $errorMessage !== null) {
                $metric->setErrorMessage($errorMessage);
                
                // Log spécifique pour les erreurs
                $this->logger->warning('Erreur API WhatsApp détectée', [
                    'user_id' => $user->getId(),
                    'operation' => $operation,
                    'api_version' => $apiVersion,
                    'endpoint' => $endpoint,
                    'error' => $errorMessage,
                    'duration' => $duration
                ]);
                
                // Vérifier si c'est une erreur critique
                if ($this->isCriticalError($errorMessage)) {
                    $this->logger->error('ERREUR CRITIQUE API WhatsApp', [
                        'user_id' => $user->getId(),
                        'operation' => $operation,
                        'api_version' => $apiVersion,
                        'endpoint' => $endpoint,
                        'error' => $errorMessage,
                        'duration' => $duration
                    ]);
                    
                    // TODO: Implémenter un système d'alerte (email, SMS, etc.)
                }
            }
            
            // Ajouter des métadonnées JSON pour des informations supplémentaires
            $metadata = [
                'api_version' => $apiVersion,
                'endpoint' => $endpoint,
                'timestamp' => (new \DateTime())->format('c')
            ];
            $metric->setMetadata(json_encode($metadata));
            
            $metric->setCreatedAt(new \DateTime());
            $this->apiMetricRepository->save($metric);
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'enregistrement d\'une métrique de performance', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId(),
                'operation' => $operation,
                'api_version' => $apiVersion
            ]);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function getDashboard(User $user, string $period = 'week'): array {
        try {
            $this->logger->info('Génération du dashboard WhatsApp', [
                'user_id' => $user->getId(),
                'period' => $period
            ]);
            
            // Déterminer la période
            $startDate = new \DateTime();
            
            switch ($period) {
                case 'day':
                    $startDate->modify('-1 day');
                    break;
                    
                case 'week':
                    $startDate->modify('-7 days');
                    break;
                    
                case 'month':
                    $startDate->modify('-30 days');
                    break;
                    
                case 'year':
                    $startDate->modify('-365 days');
                    break;
                    
                default:
                    $startDate->modify('-7 days');
            }
            
            // Récupérer les différentes métriques
            $templateMetrics = $this->getTemplateUsageMetrics($user, $startDate);
            $performanceMetrics = $this->getApiPerformanceMetrics($user, $startDate);
            $errorMetrics = $this->getApiErrorMetrics($user, $startDate);
            
            // Récupérer les alertes actives
            $alerts = $this->getActiveAlerts($user);
            
            // Calculer des indicateurs clés supplémentaires
            $messageSuccess = $this->messageHistoryRepository->countByStatus($user->getId(), ['sent', 'delivered', 'read']);
            $messageFailed = $this->messageHistoryRepository->countByStatus($user->getId(), ['failed']);
            $messageTotal = $messageSuccess + $messageFailed;
            $messageSuccessRate = $messageTotal > 0 ? round(($messageSuccess / $messageTotal) * 100, 2) : 0;
            
            // Construire le dashboard
            return [
                'period' => $period,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => (new \DateTime())->format('Y-m-d'),
                'alerts' => $alerts,
                'key_metrics' => [
                    'message_success_rate' => $messageSuccessRate,
                    'api_success_rate' => $performanceMetrics['overall_success_rate'],
                    'total_messages' => $messageTotal,
                    'total_templates_used' => $templateMetrics['total_usage'],
                    'avg_api_duration' => $performanceMetrics['avg_duration'],
                    'p95_api_duration' => $performanceMetrics['p95_duration'],
                    'critical_errors' => $errorMetrics['critical_errors'],
                    'template_count' => $templateMetrics['unique_templates']
                ],
                'top_templates' => array_slice($templateMetrics['template_usage'], 0, 5),
                'templates_by_category' => $templateMetrics['by_category'],
                'templates_by_language' => $templateMetrics['by_language'],
                'api_errors_by_type' => array_slice($errorMetrics['by_type'], 0, 5),
                'messages_by_day' => $templateMetrics['by_day'],
                'api_performance_by_day' => $performanceMetrics['by_day'],
                'api_avg_duration_by_day' => $performanceMetrics['avg_duration_by_day'],
                'recent_errors' => $errorMetrics['recent_errors']
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la génération du dashboard WhatsApp', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);
            
            return [
                'period' => $period,
                'error' => $e->getMessage(),
                'key_metrics' => [
                    'message_success_rate' => 0,
                    'api_success_rate' => 0,
                    'total_messages' => 0,
                    'total_templates_used' => 0,
                    'avg_api_duration' => 0,
                    'p95_api_duration' => 0,
                    'critical_errors' => 0,
                    'template_count' => 0
                ],
                'alerts' => []
            ];
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function getActiveAlerts(User $user): array {
        try {
            $this->logger->info('Récupération des alertes actives WhatsApp', [
                'user_id' => $user->getId()
            ]);
            
            $alerts = [];
            
            // Alerte 1: Taux d'erreur élevé sur la dernière heure
            $startDate = new \DateTime();
            $startDate->modify('-1 hour');
            
            $hourlyErrorMetrics = $this->getApiErrorMetrics($user, $startDate);
            
            if ($hourlyErrorMetrics['error_rate'] > 10) {
                $alerts[] = [
                    'type' => 'error_rate',
                    'level' => 'critical',
                    'message' => sprintf(
                        'Taux d\'erreur WhatsApp élevé (%.2f%%) sur la dernière heure',
                        $hourlyErrorMetrics['error_rate']
                    ),
                    'details' => [
                        'error_rate' => $hourlyErrorMetrics['error_rate'],
                        'total_errors' => $hourlyErrorMetrics['total_errors']
                    ]
                ];
            }
            
            // Alerte 2: Latence API élevée
            $startDate = new \DateTime();
            $startDate->modify('-3 hours');
            
            $recentPerformanceMetrics = $this->getApiPerformanceMetrics($user, $startDate);
            
            if ($recentPerformanceMetrics['p95_duration'] > 2000) {
                $alerts[] = [
                    'type' => 'high_latency',
                    'level' => 'warning',
                    'message' => sprintf(
                        'Latence élevée de l\'API WhatsApp (P95: %.2f ms)',
                        $recentPerformanceMetrics['p95_duration']
                    ),
                    'details' => [
                        'p95_duration' => $recentPerformanceMetrics['p95_duration'],
                        'avg_duration' => $recentPerformanceMetrics['avg_duration']
                    ]
                ];
            }
            
            // Alerte 3: Erreurs critiques récentes
            if ($hourlyErrorMetrics['critical_errors'] > 0) {
                $alerts[] = [
                    'type' => 'critical_errors',
                    'level' => 'critical',
                    'message' => sprintf(
                        '%d erreur(s) critique(s) détectée(s) sur la dernière heure',
                        $hourlyErrorMetrics['critical_errors']
                    ),
                    'details' => [
                        'critical_errors' => $hourlyErrorMetrics['critical_errors'],
                        'recent_errors' => $hourlyErrorMetrics['recent_errors']
                    ]
                ];
            }
            
            // Alerte 4: Taux de succès des messages bas
            $startDate = new \DateTime();
            $startDate->modify('-24 hours');
            
            $messageSuccess = $this->messageHistoryRepository->countByStatus($user->getId(), ['sent', 'delivered', 'read'], $startDate);
            $messageFailed = $this->messageHistoryRepository->countByStatus($user->getId(), ['failed'], $startDate);
            $messageTotal = $messageSuccess + $messageFailed;
            
            if ($messageTotal > 0) {
                $messageSuccessRate = round(($messageSuccess / $messageTotal) * 100, 2);
                
                if ($messageSuccessRate < 90) {
                    $alerts[] = [
                        'type' => 'low_message_success',
                        'level' => 'warning',
                        'message' => sprintf(
                            'Taux de succès des messages WhatsApp bas (%.2f%%) sur les dernières 24h',
                            $messageSuccessRate
                        ),
                        'details' => [
                            'success_rate' => $messageSuccessRate,
                            'total_messages' => $messageTotal,
                            'failed_messages' => $messageFailed
                        ]
                    ];
                }
            }
            
            return $alerts;
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des alertes actives', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);
            
            return [];
        }
    }
    
    /**
     * Simplifie un message d'erreur pour le regroupement
     * 
     * @param string $errorMessage Le message d'erreur complet
     * @return string Le message simplifié
     */
    private function simplifyErrorMessage(string $errorMessage): string {
        // Enlever les détails variables
        $simplified = preg_replace('/\d+/', '{number}', $errorMessage);
        $simplified = preg_replace('/[\'"][^"\']*[\'"]/', '{value}', $simplified);
        
        // Limiter la longueur
        if (strlen($simplified) > 100) {
            $simplified = substr($simplified, 0, 97) . '...';
        }
        
        return $simplified;
    }
    
    /**
     * Génère un rapport comparatif entre les API v1 et v2
     * 
     * @param User $user L'utilisateur
     * @param \DateTime|null $startDate Date de début pour la période d'analyse
     * @param \DateTime|null $endDate Date de fin pour la période d'analyse
     * @return array Rapport de comparaison
     */
    public function generateApiVersionComparisonReport(
        User $user,
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null
    ): array {
        try {
            $this->logger->info('Génération du rapport de comparaison d\'API WhatsApp', [
                'user_id' => $user->getId(),
                'start_date' => $startDate ? $startDate->format('Y-m-d') : 'all',
                'end_date' => $endDate ? $endDate->format('Y-m-d') : 'now'
            ]);
            
            // Obtenir les métriques pour chaque version
            $v1Metrics = $this->getApiPerformanceMetrics($user, $startDate, $endDate, 'v1');
            $v2Metrics = $this->getApiPerformanceMetrics($user, $startDate, $endDate, 'v2');
            
            // Obtenir les taux d'erreur pour chaque version
            $v1ErrorMetrics = $this->getApiErrorMetrics($user, $startDate, $endDate);
            $v1ErrorMetrics['api_version'] = 'v1';
            
            $v2ErrorMetrics = $this->getApiErrorMetrics($user, $startDate, $endDate);
            $v2ErrorMetrics['api_version'] = 'v2';
            
            // Calculer les différences
            $successRateDiff = $v2Metrics['overall_success_rate'] - $v1Metrics['overall_success_rate'];
            $avgDurationDiff = $v1Metrics['avg_duration'] - $v2Metrics['avg_duration']; // Note: positif = v2 est plus rapide
            $p95DurationDiff = $v1Metrics['p95_duration'] - $v2Metrics['p95_duration'];
            
            // Calculer les pourcentages d'utilisation
            $totalOperations = $v1Metrics['total_operations'] + $v2Metrics['total_operations'];
            $v1Percentage = $totalOperations > 0 ? ($v1Metrics['total_operations'] / $totalOperations) * 100 : 0;
            $v2Percentage = $totalOperations > 0 ? ($v2Metrics['total_operations'] / $totalOperations) * 100 : 0;
            
            // Construire le rapport
            $report = [
                'period' => [
                    'start_date' => $startDate ? $startDate->format('Y-m-d') : 'all time',
                    'end_date' => $endDate ? $endDate->format('Y-m-d') : 'now'
                ],
                'usage' => [
                    'total_operations' => $totalOperations,
                    'v1_operations' => $v1Metrics['total_operations'],
                    'v2_operations' => $v2Metrics['total_operations'],
                    'v1_percentage' => round($v1Percentage, 2),
                    'v2_percentage' => round($v2Percentage, 2)
                ],
                'performance' => [
                    'success_rate' => [
                        'v1' => $v1Metrics['overall_success_rate'],
                        'v2' => $v2Metrics['overall_success_rate'],
                        'difference' => round($successRateDiff, 2),
                        'improvement' => $successRateDiff >= 0
                    ],
                    'avg_duration' => [
                        'v1' => $v1Metrics['avg_duration'],
                        'v2' => $v2Metrics['avg_duration'],
                        'difference' => round($avgDurationDiff, 2),
                        'improvement' => $avgDurationDiff >= 0
                    ],
                    'p95_duration' => [
                        'v1' => $v1Metrics['p95_duration'],
                        'v2' => $v2Metrics['p95_duration'],
                        'difference' => round($p95DurationDiff, 2),
                        'improvement' => $p95DurationDiff >= 0
                    ]
                ],
                'errors' => [
                    'v1' => [
                        'total_errors' => $v1ErrorMetrics['total_errors'],
                        'error_rate' => $v1ErrorMetrics['error_rate'],
                        'critical_errors' => $v1ErrorMetrics['critical_errors']
                    ],
                    'v2' => [
                        'total_errors' => $v2ErrorMetrics['total_errors'],
                        'error_rate' => $v2ErrorMetrics['error_rate'],
                        'critical_errors' => $v2ErrorMetrics['critical_errors']
                    ]
                ],
                'operations' => [
                    'v1' => $this->groupOperationsByType($v1Metrics['by_operation']),
                    'v2' => $this->groupOperationsByType($v2Metrics['by_operation'])
                ],
                'summary' => $this->generateComparisonSummary(
                    $v1Metrics,
                    $v2Metrics,
                    $v1ErrorMetrics,
                    $v2ErrorMetrics
                )
            ];
            
            return $report;
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la génération du rapport de comparaison d\'API', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);
            
            return [
                'error' => $e->getMessage(),
                'status' => 'error'
            ];
        }
    }
    
    /**
     * Groupe les opérations par type pour le rapport de comparaison
     * 
     * @param array $operations Liste des opérations et leurs métriques
     * @return array Opérations groupées par type
     */
    private function groupOperationsByType(array $operations): array {
        $grouped = [
            'template_operations' => [],
            'message_operations' => [],
            'media_operations' => [],
            'other_operations' => []
        ];
        
        foreach ($operations as $operation) {
            $name = $operation['operation'];
            
            if (strpos($name, 'template') !== false) {
                $grouped['template_operations'][$name] = $operation;
            } else if (strpos($name, 'message') !== false) {
                $grouped['message_operations'][$name] = $operation;
            } else if (strpos($name, 'media') !== false) {
                $grouped['media_operations'][$name] = $operation;
            } else {
                $grouped['other_operations'][$name] = $operation;
            }
        }
        
        return $grouped;
    }
    
    /**
     * Génère un résumé textuel de la comparaison
     * 
     * @param array $v1Metrics Métriques de performance v1
     * @param array $v2Metrics Métriques de performance v2
     * @param array $v1ErrorMetrics Métriques d'erreur v1
     * @param array $v2ErrorMetrics Métriques d'erreur v2
     * @return string Résumé de la comparaison
     */
    private function generateComparisonSummary(
        array $v1Metrics,
        array $v2Metrics,
        array $v1ErrorMetrics,
        array $v2ErrorMetrics
    ): string {
        $successRateDiff = $v2Metrics['overall_success_rate'] - $v1Metrics['overall_success_rate'];
        $avgDurationDiff = $v1Metrics['avg_duration'] - $v2Metrics['avg_duration'];
        
        if ($v2Metrics['total_operations'] < 10) {
            return "Données insuffisantes pour l'API V2. Plus d'utilisation est nécessaire pour une comparaison fiable.";
        }
        
        if ($successRateDiff > 5 && $avgDurationDiff > 50) {
            return "L'API V2 est significativement plus performante avec un taux de succès supérieur de {$successRateDiff}% et une réduction de latence moyenne de {$avgDurationDiff}ms.";
        } else if ($successRateDiff > 5) {
            return "L'API V2 présente un meilleur taux de réussite (+{$successRateDiff}%) mais des performances similaires en termes de latence.";
        } else if ($avgDurationDiff > 50) {
            return "L'API V2 est plus rapide ({$avgDurationDiff}ms de réduction) mais avec un taux de réussite similaire.";
        } else if ($successRateDiff < -5 || $avgDurationDiff < -50) {
            return "L'API V2 présente actuellement des métriques moins favorables que V1. Une investigation peut être nécessaire.";
        } else {
            return "Les performances des API V1 et V2 sont comparables, avec des différences mineures.";
        }
    }
    
    /**
     * Vérifie si un message d'erreur est critique
     * 
     * @param string $errorMessage Le message d'erreur
     * @return bool True si l'erreur est critique
     */
    private function isCriticalError(string $errorMessage): bool {
        $criticalPatterns = [
            'authorization',
            'authentication failed',
            'access denied',
            'quota exceeded',
            'rate limit',
            'database error',
            'connection failed',
            'timeout',
            'internal server error',
            '5[0-9][0-9] error',
            'critical',
            'fatal',
            'security'
        ];
        
        foreach ($criticalPatterns as $pattern) {
            if (stripos($errorMessage, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
}