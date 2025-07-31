<?php

declare(strict_types=1);

namespace App\GraphQL\Controllers\WhatsApp;

use App\Entities\User;
use App\GraphQL\Types\WhatsApp\WhatsAppApiPerformanceMetrics;
use App\GraphQL\Types\WhatsApp\WhatsAppTemplateUsageMetrics;
use App\GraphQL\Types\WhatsApp\WhatsAppApiErrorMetrics;
use App\GraphQL\Types\WhatsApp\WhatsAppMonitoringDashboard;
use App\GraphQL\Types\WhatsApp\WhatsAppAlert;
use App\Services\Interfaces\WhatsApp\WhatsAppMonitoringServiceInterface;
use Psr\Log\LoggerInterface;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;

/**
 * Contrôleur GraphQL pour le monitoring WhatsApp
 */
class WhatsAppMonitoringController
{
    /**
     * @var WhatsAppMonitoringServiceInterface
     */
    private WhatsAppMonitoringServiceInterface $monitoringService;
    
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    
    /**
     * Constructeur
     */
    public function __construct(
        WhatsAppMonitoringServiceInterface $monitoringService,
        LoggerInterface $logger
    ) {
        $this->monitoringService = $monitoringService;
        $this->logger = $logger;
    }
    
    /**
     * Récupère les métriques d'utilisation des templates WhatsApp
     * 
     * @param \DateTime|null $startDate Date de début pour la période
     * @param \DateTime|null $endDate Date de fin pour la période
     * @return WhatsAppTemplateUsageMetrics
     */
    #[Query]
    #[Logged]
    public function getWhatsAppTemplateUsageMetrics(
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null,
        #[InjectUser] ?User $user = null
    ): WhatsAppTemplateUsageMetrics {
        if ($user === null) {
            $this->logger->error('Tentative d\'accès aux métriques sans utilisateur authentifié');
            return new WhatsAppTemplateUsageMetrics(
                0, [], [], [], [], [], 0, 'Utilisateur non authentifié'
            );
        }
        
        try {
            $metrics = $this->monitoringService->getTemplateUsageMetrics($user, $startDate, $endDate);
            return new WhatsAppTemplateUsageMetrics(
                $metrics['total_usage'],
                $metrics['template_usage'],
                $metrics['by_language'],
                $metrics['by_category'],
                $metrics['by_day'],
                $metrics['by_hour'],
                $metrics['unique_templates'],
                $metrics['error'] ?? null
            );
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des métriques d\'utilisation des templates', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);
            
            return new WhatsAppTemplateUsageMetrics(
                0, [], [], [], [], [], 0, $e->getMessage()
            );
        }
    }
    
    /**
     * Récupère les métriques de performance de l'API WhatsApp
     * 
     * @param \DateTime|null $startDate Date de début pour la période
     * @param \DateTime|null $endDate Date de fin pour la période
     * @return WhatsAppApiPerformanceMetrics
     */
    #[Query]
    #[Logged]
    public function getWhatsAppApiPerformanceMetrics(
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null,
        #[InjectUser] ?User $user = null
    ): WhatsAppApiPerformanceMetrics {
        if ($user === null) {
            $this->logger->error('Tentative d\'accès aux métriques sans utilisateur authentifié');
            return new WhatsAppApiPerformanceMetrics(
                0, 0, 0, 0, 0, [], [], [], 'Utilisateur non authentifié'
            );
        }
        
        try {
            $metrics = $this->monitoringService->getApiPerformanceMetrics($user, $startDate, $endDate);
            return new WhatsAppApiPerformanceMetrics(
                $metrics['total_operations'],
                $metrics['overall_success_rate'],
                $metrics['avg_duration'],
                $metrics['p95_duration'],
                $metrics['p99_duration'],
                $metrics['by_operation'],
                $metrics['by_day'],
                $metrics['avg_duration_by_day'],
                $metrics['error'] ?? null
            );
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des métriques de performance API', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);
            
            return new WhatsAppApiPerformanceMetrics(
                0, 0, 0, 0, 0, [], [], [], $e->getMessage()
            );
        }
    }
    
    /**
     * Récupère les métriques d'erreur de l'API WhatsApp
     * 
     * @param \DateTime|null $startDate Date de début pour la période
     * @param \DateTime|null $endDate Date de fin pour la période
     * @return WhatsAppApiErrorMetrics
     */
    #[Query]
    #[Logged]
    public function getWhatsAppApiErrorMetrics(
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null,
        #[InjectUser] ?User $user = null
    ): WhatsAppApiErrorMetrics {
        if ($user === null) {
            $this->logger->error('Tentative d\'accès aux métriques sans utilisateur authentifié');
            return new WhatsAppApiErrorMetrics(
                0, 0, 0, [], [], [], [], 'Utilisateur non authentifié'
            );
        }
        
        try {
            $metrics = $this->monitoringService->getApiErrorMetrics($user, $startDate, $endDate);
            return new WhatsAppApiErrorMetrics(
                $metrics['total_errors'],
                $metrics['error_rate'],
                $metrics['critical_errors'],
                $metrics['by_type'],
                $metrics['by_operation'],
                $metrics['by_day'],
                $metrics['recent_errors'],
                $metrics['error'] ?? null
            );
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des métriques d\'erreur API', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);
            
            return new WhatsAppApiErrorMetrics(
                0, 0, 0, [], [], [], [], $e->getMessage()
            );
        }
    }
    
    /**
     * Récupère le dashboard de monitoring WhatsApp
     * 
     * @param string $period Période (day, week, month, year)
     * @return WhatsAppMonitoringDashboard
     */
    #[Query]
    #[Logged]
    public function getWhatsAppMonitoringDashboard(
        string $period = 'week',
        #[InjectUser] ?User $user = null
    ): WhatsAppMonitoringDashboard {
        if ($user === null) {
            $this->logger->error('Tentative d\'accès au dashboard sans utilisateur authentifié');
            return new WhatsAppMonitoringDashboard(
                'week',
                (new \DateTime())->format('Y-m-d'),
                (new \DateTime())->format('Y-m-d'),
                [],
                [
                    'message_success_rate' => 0,
                    'api_success_rate' => 0,
                    'total_messages' => 0,
                    'total_templates_used' => 0,
                    'avg_api_duration' => 0,
                    'p95_api_duration' => 0,
                    'critical_errors' => 0,
                    'template_count' => 0
                ],
                [],
                [],
                [],
                [],
                [],
                [],
                [],
                [],
                'Utilisateur non authentifié'
            );
        }
        
        try {
            $dashboard = $this->monitoringService->getDashboard($user, $period);
            return new WhatsAppMonitoringDashboard(
                $dashboard['period'],
                $dashboard['start_date'],
                $dashboard['end_date'],
                $dashboard['alerts'],
                $dashboard['key_metrics'],
                $dashboard['top_templates'],
                $dashboard['templates_by_category'],
                $dashboard['templates_by_language'],
                $dashboard['api_errors_by_type'],
                $dashboard['messages_by_day'],
                $dashboard['api_performance_by_day'],
                $dashboard['api_avg_duration_by_day'],
                $dashboard['recent_errors'],
                $dashboard['error'] ?? null
            );
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération du dashboard', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);
            
            return new WhatsAppMonitoringDashboard(
                $period,
                (new \DateTime())->format('Y-m-d'),
                (new \DateTime())->format('Y-m-d'),
                [],
                [
                    'message_success_rate' => 0,
                    'api_success_rate' => 0,
                    'total_messages' => 0,
                    'total_templates_used' => 0,
                    'avg_api_duration' => 0,
                    'p95_api_duration' => 0,
                    'critical_errors' => 0,
                    'template_count' => 0
                ],
                [],
                [],
                [],
                [],
                [],
                [],
                [],
                [],
                $e->getMessage()
            );
        }
    }
    
    /**
     * Récupère les alertes actives pour le monitoring WhatsApp
     * 
     * @return array<WhatsAppAlert>
     */
    #[Query]
    #[Logged]
    public function getWhatsAppActiveAlerts(
        #[InjectUser] ?User $user = null
    ): array {
        if ($user === null) {
            $this->logger->error('Tentative d\'accès aux alertes sans utilisateur authentifié');
            return [];
        }
        
        try {
            $alerts = $this->monitoringService->getActiveAlerts($user);
            return array_map(function ($alert) {
                return new WhatsAppAlert(
                    $alert['type'],
                    $alert['level'],
                    $alert['message'],
                    $alert['details'] ?? []
                );
            }, $alerts);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des alertes actives', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);
            
            return [];
        }
    }
}