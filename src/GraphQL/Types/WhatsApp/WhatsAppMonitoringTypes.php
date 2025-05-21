<?php

declare(strict_types=1);

namespace App\GraphQL\Types\WhatsApp;

use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;

/**
 * Type pour une métrique de performance API
 */
#[Type]
class WhatsAppApiPerformanceMetric
{
    public function __construct(
        private string $operation, 
        private int $count,
        private float $avgDuration,
        private int $successful,
        private int $failed,
        private float $successRate
    ) {}
    
    #[Field]
    public function getOperation(): string
    {
        return $this->operation;
    }
    
    #[Field]
    public function getCount(): int
    {
        return $this->count;
    }
    
    #[Field]
    public function getAvgDuration(): float
    {
        return $this->avgDuration;
    }
    
    #[Field]
    public function getSuccessful(): int
    {
        return $this->successful;
    }
    
    #[Field]
    public function getFailed(): int
    {
        return $this->failed;
    }
    
    #[Field]
    public function getSuccessRate(): float
    {
        return $this->successRate;
    }
}

/**
 * Type pour les métriques de performance API
 */
#[Type]
class WhatsAppApiPerformanceMetrics
{
    public function __construct(
        private int $totalOperations,
        private float $overallSuccessRate,
        private float $avgDuration,
        private float $p95Duration,
        private float $p99Duration,
        private array $byOperation,
        private array $byDay,
        private array $avgDurationByDay,
        private ?string $error = null
    ) {}
    
    #[Field]
    public function getTotalOperations(): int
    {
        return $this->totalOperations;
    }
    
    #[Field]
    public function getOverallSuccessRate(): float
    {
        return $this->overallSuccessRate;
    }
    
    #[Field]
    public function getAvgDuration(): float
    {
        return $this->avgDuration;
    }
    
    #[Field]
    public function getP95Duration(): float
    {
        return $this->p95Duration;
    }
    
    #[Field]
    public function getP99Duration(): float
    {
        return $this->p99Duration;
    }
    
    #[Field]
    public function getByOperation(): array
    {
        return array_map(function ($item) {
            return new WhatsAppApiPerformanceMetric(
                $item['operation'],
                $item['count'],
                $item['avg_duration'],
                $item['successful'],
                $item['failed'],
                $item['success_rate']
            );
        }, $this->byOperation);
    }
    
    #[Field]
    public function getByDay(): array
    {
        return $this->byDay;
    }
    
    #[Field]
    public function getAvgDurationByDay(): array
    {
        return $this->avgDurationByDay;
    }
    
    #[Field]
    public function getError(): ?string
    {
        return $this->error;
    }
}

/**
 * Type pour une métrique d'utilisation de template
 */
#[Type]
class WhatsAppTemplateUsageMetric
{
    public function __construct(
        private string $templateId,
        private string $templateName,
        private int $count,
        private float $successRate,
        private int $successful,
        private int $failed
    ) {}
    
    #[Field]
    public function getTemplateId(): string
    {
        return $this->templateId;
    }
    
    #[Field]
    public function getTemplateName(): string
    {
        return $this->templateName;
    }
    
    #[Field]
    public function getCount(): int
    {
        return $this->count;
    }
    
    #[Field]
    public function getSuccessRate(): float
    {
        return $this->successRate;
    }
    
    #[Field]
    public function getSuccessful(): int
    {
        return $this->successful;
    }
    
    #[Field]
    public function getFailed(): int
    {
        return $this->failed;
    }
}

/**
 * Type pour les métriques d'utilisation des templates
 */
#[Type]
class WhatsAppTemplateUsageMetrics
{
    public function __construct(
        private int $totalUsage,
        private array $templateUsage,
        private array $byLanguage,
        private array $byCategory,
        private array $byDay,
        private array $byHour,
        private int $uniqueTemplates,
        private ?string $error = null
    ) {}
    
    #[Field]
    public function getTotalUsage(): int
    {
        return $this->totalUsage;
    }
    
    #[Field]
    public function getTemplateUsage(): array
    {
        return array_map(function ($item) {
            return new WhatsAppTemplateUsageMetric(
                $item['template_id'],
                $item['template_name'],
                $item['count'],
                $item['success_rate'],
                $item['successful'],
                $item['failed']
            );
        }, $this->templateUsage);
    }
    
    #[Field]
    public function getByLanguage(): array
    {
        return $this->byLanguage;
    }
    
    #[Field]
    public function getByCategory(): array
    {
        return $this->byCategory;
    }
    
    #[Field]
    public function getByDay(): array
    {
        return $this->byDay;
    }
    
    #[Field]
    public function getByHour(): array
    {
        return $this->byHour;
    }
    
    #[Field]
    public function getUniqueTemplates(): int
    {
        return $this->uniqueTemplates;
    }
    
    #[Field]
    public function getError(): ?string
    {
        return $this->error;
    }
}

/**
 * Type pour une métrique d'erreur API
 */
#[Type]
class WhatsAppApiErrorMetric
{
    public function __construct(
        private string $type,
        private int $count,
        private array $operations
    ) {}
    
    #[Field]
    public function getType(): string
    {
        return $this->type;
    }
    
    #[Field]
    public function getCount(): int
    {
        return $this->count;
    }
    
    #[Field]
    public function getOperations(): array
    {
        return $this->operations;
    }
}

/**
 * Type pour les métriques d'erreur API
 */
#[Type]
class WhatsAppApiErrorMetrics
{
    public function __construct(
        private int $totalErrors,
        private float $errorRate,
        private int $criticalErrors,
        private array $byType,
        private array $byOperation,
        private array $byDay,
        private array $recentErrors,
        private ?string $error = null
    ) {}
    
    #[Field]
    public function getTotalErrors(): int
    {
        return $this->totalErrors;
    }
    
    #[Field]
    public function getErrorRate(): float
    {
        return $this->errorRate;
    }
    
    #[Field]
    public function getCriticalErrors(): int
    {
        return $this->criticalErrors;
    }
    
    #[Field]
    public function getByType(): array
    {
        return array_map(function ($item) {
            return new WhatsAppApiErrorMetric(
                $item['type'],
                $item['count'],
                $item['operations']
            );
        }, $this->byType);
    }
    
    #[Field]
    public function getByOperation(): array
    {
        return $this->byOperation;
    }
    
    #[Field]
    public function getByDay(): array
    {
        return $this->byDay;
    }
    
    #[Field]
    public function getRecentErrors(): array
    {
        return $this->recentErrors;
    }
    
    #[Field]
    public function getError(): ?string
    {
        return $this->error;
    }
}

/**
 * Type pour les métriques clés du dashboard
 */
#[Type]
class WhatsAppKeyMetrics
{
    public function __construct(
        private float $messageSuccessRate,
        private float $apiSuccessRate,
        private int $totalMessages,
        private int $totalTemplatesUsed,
        private float $avgApiDuration,
        private float $p95ApiDuration,
        private int $criticalErrors,
        private int $templateCount
    ) {}
    
    #[Field]
    public function getMessageSuccessRate(): float
    {
        return $this->messageSuccessRate;
    }
    
    #[Field]
    public function getApiSuccessRate(): float
    {
        return $this->apiSuccessRate;
    }
    
    #[Field]
    public function getTotalMessages(): int
    {
        return $this->totalMessages;
    }
    
    #[Field]
    public function getTotalTemplatesUsed(): int
    {
        return $this->totalTemplatesUsed;
    }
    
    #[Field]
    public function getAvgApiDuration(): float
    {
        return $this->avgApiDuration;
    }
    
    #[Field]
    public function getP95ApiDuration(): float
    {
        return $this->p95ApiDuration;
    }
    
    #[Field]
    public function getCriticalErrors(): int
    {
        return $this->criticalErrors;
    }
    
    #[Field]
    public function getTemplateCount(): int
    {
        return $this->templateCount;
    }
}

/**
 * Type pour le dashboard de monitoring
 */
#[Type]
class WhatsAppMonitoringDashboard
{
    public function __construct(
        private string $period,
        private string $startDate,
        private string $endDate,
        private array $alerts,
        private array $keyMetrics,
        private array $topTemplates,
        private array $templatesByCategory,
        private array $templatesByLanguage,
        private array $apiErrorsByType,
        private array $messagesByDay,
        private array $apiPerformanceByDay,
        private array $apiAvgDurationByDay,
        private array $recentErrors,
        private ?string $error = null
    ) {}
    
    #[Field]
    public function getPeriod(): string
    {
        return $this->period;
    }
    
    #[Field]
    public function getStartDate(): string
    {
        return $this->startDate;
    }
    
    #[Field]
    public function getEndDate(): string
    {
        return $this->endDate;
    }
    
    #[Field]
    public function getAlerts(): array
    {
        return array_map(function ($item) {
            return new WhatsAppAlert(
                $item['type'],
                $item['level'],
                $item['message'],
                $item['details'] ?? []
            );
        }, $this->alerts);
    }
    
    #[Field]
    public function getKeyMetrics(): WhatsAppKeyMetrics
    {
        return new WhatsAppKeyMetrics(
            $this->keyMetrics['message_success_rate'],
            $this->keyMetrics['api_success_rate'],
            $this->keyMetrics['total_messages'],
            $this->keyMetrics['total_templates_used'],
            $this->keyMetrics['avg_api_duration'],
            $this->keyMetrics['p95_api_duration'],
            $this->keyMetrics['critical_errors'],
            $this->keyMetrics['template_count']
        );
    }
    
    #[Field]
    public function getTopTemplates(): array
    {
        return array_map(function ($item) {
            return new WhatsAppTemplateUsageMetric(
                $item['template_id'],
                $item['template_name'],
                $item['count'],
                $item['success_rate'],
                $item['successful'],
                $item['failed']
            );
        }, $this->topTemplates);
    }
    
    #[Field]
    public function getTemplatesByCategory(): array
    {
        return $this->templatesByCategory;
    }
    
    #[Field]
    public function getTemplatesByLanguage(): array
    {
        return $this->templatesByLanguage;
    }
    
    #[Field]
    public function getApiErrorsByType(): array
    {
        return array_map(function ($item) {
            return new WhatsAppApiErrorMetric(
                $item['type'],
                $item['count'],
                $item['operations']
            );
        }, $this->apiErrorsByType);
    }
    
    #[Field]
    public function getMessagesByDay(): array
    {
        return $this->messagesByDay;
    }
    
    #[Field]
    public function getApiPerformanceByDay(): array
    {
        return $this->apiPerformanceByDay;
    }
    
    #[Field]
    public function getApiAvgDurationByDay(): array
    {
        return $this->apiAvgDurationByDay;
    }
    
    #[Field]
    public function getRecentErrors(): array
    {
        return $this->recentErrors;
    }
    
    #[Field]
    public function getError(): ?string
    {
        return $this->error;
    }
}

/**
 * Type pour une alerte
 */
#[Type]
class WhatsAppAlert
{
    public function __construct(
        private string $type,
        private string $level,
        private string $message,
        private array $details
    ) {}
    
    #[Field]
    public function getType(): string
    {
        return $this->type;
    }
    
    #[Field]
    public function getLevel(): string
    {
        return $this->level;
    }
    
    #[Field]
    public function getMessage(): string
    {
        return $this->message;
    }
    
    #[Field]
    public function getDetails(): array
    {
        return $this->details;
    }
}