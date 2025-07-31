<?php

namespace Tests\GraphQL\Controllers\WhatsApp;

/**
 * Mock classes pour contourner les problèmes d'autoloading pendant les tests
 */

/**
 * This is a workaround for testing
 * This class will be autoloaded before the controller tries to use the real class
 */
class MockTypes
{
    public static function init()
    {
        // This just ensures this file gets loaded
    }
}

// Mock all required classes with the correct namespace
namespace App\GraphQL\Types\WhatsApp;

/**
 * Type pour les métriques d'utilisation des templates
 */
class WhatsAppTemplateUsageMetrics
{
    private $totalUsage;
    private $templateUsage;
    private $byLanguage;
    private $byCategory;
    private $byDay;
    private $byHour;
    private $uniqueTemplates;
    private $error;
    
    public function __construct(
        int $totalUsage, 
        array $templateUsage, 
        array $byLanguage, 
        array $byCategory, 
        array $byDay, 
        array $byHour, 
        int $uniqueTemplates, 
        ?string $error = null
    ) {
        $this->totalUsage = $totalUsage;
        $this->templateUsage = $templateUsage;
        $this->byLanguage = $byLanguage;
        $this->byCategory = $byCategory;
        $this->byDay = $byDay;
        $this->byHour = $byHour;
        $this->uniqueTemplates = $uniqueTemplates;
        $this->error = $error;
    }
    
    public function getTotalUsage(): int
    {
        return $this->totalUsage;
    }
    
    public function getTemplateUsage(): array
    {
        return $this->templateUsage;
    }
    
    public function getByLanguage(): array
    {
        return $this->byLanguage;
    }
    
    public function getByCategory(): array
    {
        return $this->byCategory;
    }
    
    public function getByDay(): array
    {
        return $this->byDay;
    }
    
    public function getByHour(): array
    {
        return $this->byHour;
    }
    
    public function getUniqueTemplates(): int
    {
        return $this->uniqueTemplates;
    }
    
    public function getError(): ?string
    {
        return $this->error;
    }
}

/**
 * Type pour une métrique d'utilisation de template
 */
class WhatsAppTemplateUsageMetric
{
    private $templateId;
    private $templateName;
    private $count;
    private $successRate;
    private $successful;
    private $failed;
    
    public function __construct(
        string $templateId,
        string $templateName,
        int $count,
        float $successRate,
        int $successful,
        int $failed
    ) {
        $this->templateId = $templateId;
        $this->templateName = $templateName;
        $this->count = $count;
        $this->successRate = $successRate;
        $this->successful = $successful;
        $this->failed = $failed;
    }
    
    public function getTemplateId(): string
    {
        return $this->templateId;
    }
    
    public function getTemplateName(): string
    {
        return $this->templateName;
    }
    
    public function getCount(): int
    {
        return $this->count;
    }
    
    public function getSuccessRate(): float
    {
        return $this->successRate;
    }
    
    public function getSuccessful(): int
    {
        return $this->successful;
    }
    
    public function getFailed(): int
    {
        return $this->failed;
    }
}

/**
 * Type pour les métriques de performance API
 */
class WhatsAppApiPerformanceMetrics
{
    private $totalOperations;
    private $overallSuccessRate;
    private $avgDuration;
    private $p95Duration;
    private $p99Duration;
    private $byOperation;
    private $byDay;
    private $avgDurationByDay;
    private $error;
    
    public function __construct(
        int $totalOperations,
        float $overallSuccessRate,
        float $avgDuration,
        float $p95Duration,
        float $p99Duration,
        array $byOperation,
        array $byDay,
        array $avgDurationByDay,
        ?string $error = null
    ) {
        $this->totalOperations = $totalOperations;
        $this->overallSuccessRate = $overallSuccessRate;
        $this->avgDuration = $avgDuration;
        $this->p95Duration = $p95Duration;
        $this->p99Duration = $p99Duration;
        $this->byOperation = $byOperation;
        $this->byDay = $byDay;
        $this->avgDurationByDay = $avgDurationByDay;
        $this->error = $error;
    }
    
    public function getTotalOperations(): int
    {
        return $this->totalOperations;
    }
    
    public function getOverallSuccessRate(): float
    {
        return $this->overallSuccessRate;
    }
    
    public function getAvgDuration(): float
    {
        return $this->avgDuration;
    }
    
    public function getP95Duration(): float
    {
        return $this->p95Duration;
    }
    
    public function getP99Duration(): float
    {
        return $this->p99Duration;
    }
    
    public function getByOperation(): array
    {
        return $this->byOperation;
    }
    
    public function getByDay(): array
    {
        return $this->byDay;
    }
    
    public function getAvgDurationByDay(): array
    {
        return $this->avgDurationByDay;
    }
    
    public function getError(): ?string
    {
        return $this->error;
    }
}

/**
 * Type pour une métrique de performance API
 */
class WhatsAppApiPerformanceMetric
{
    private $operation;
    private $count;
    private $avgDuration;
    private $successful;
    private $failed;
    private $successRate;
    
    public function __construct(
        string $operation, 
        int $count,
        float $avgDuration,
        int $successful,
        int $failed,
        float $successRate
    ) {
        $this->operation = $operation;
        $this->count = $count;
        $this->avgDuration = $avgDuration;
        $this->successful = $successful;
        $this->failed = $failed;
        $this->successRate = $successRate;
    }
    
    public function getOperation(): string
    {
        return $this->operation;
    }
    
    public function getCount(): int
    {
        return $this->count;
    }
    
    public function getAvgDuration(): float
    {
        return $this->avgDuration;
    }
    
    public function getSuccessful(): int
    {
        return $this->successful;
    }
    
    public function getFailed(): int
    {
        return $this->failed;
    }
    
    public function getSuccessRate(): float
    {
        return $this->successRate;
    }
}

/**
 * Type pour les métriques d'erreur API
 */
class WhatsAppApiErrorMetrics
{
    private $totalErrors;
    private $errorRate;
    private $criticalErrors;
    private $byType;
    private $byOperation;
    private $byDay;
    private $recentErrors;
    private $error;
    
    public function __construct(
        int $totalErrors,
        float $errorRate,
        int $criticalErrors,
        array $byType,
        array $byOperation,
        array $byDay,
        array $recentErrors,
        ?string $error = null
    ) {
        $this->totalErrors = $totalErrors;
        $this->errorRate = $errorRate;
        $this->criticalErrors = $criticalErrors;
        $this->byType = $byType;
        $this->byOperation = $byOperation;
        $this->byDay = $byDay;
        $this->recentErrors = $recentErrors;
        $this->error = $error;
    }
    
    public function getTotalErrors(): int
    {
        return $this->totalErrors;
    }
    
    public function getErrorRate(): float
    {
        return $this->errorRate;
    }
    
    public function getCriticalErrors(): int
    {
        return $this->criticalErrors;
    }
    
    public function getByType(): array
    {
        return $this->byType;
    }
    
    public function getByOperation(): array
    {
        return $this->byOperation;
    }
    
    public function getByDay(): array
    {
        return $this->byDay;
    }
    
    public function getRecentErrors(): array
    {
        return $this->recentErrors;
    }
    
    public function getError(): ?string
    {
        return $this->error;
    }
}

/**
 * Type pour une métrique d'erreur API
 */
class WhatsAppApiErrorMetric
{
    private $type;
    private $count;
    private $operations;
    
    public function __construct(
        string $type,
        int $count,
        array $operations
    ) {
        $this->type = $type;
        $this->count = $count;
        $this->operations = $operations;
    }
    
    public function getType(): string
    {
        return $this->type;
    }
    
    public function getCount(): int
    {
        return $this->count;
    }
    
    public function getOperations(): array
    {
        return $this->operations;
    }
}

/**
 * Type pour le dashboard de monitoring
 */
class WhatsAppMonitoringDashboard
{
    private $period;
    private $startDate;
    private $endDate;
    private $alerts;
    private $keyMetrics;
    private $topTemplates;
    private $templatesByCategory;
    private $templatesByLanguage;
    private $apiErrorsByType;
    private $messagesByDay;
    private $apiPerformanceByDay;
    private $apiAvgDurationByDay;
    private $recentErrors;
    private $error;
    
    public function __construct(
        string $period,
        string $startDate,
        string $endDate,
        array $alerts,
        array $keyMetrics,
        array $topTemplates,
        array $templatesByCategory,
        array $templatesByLanguage,
        array $apiErrorsByType,
        array $messagesByDay,
        array $apiPerformanceByDay,
        array $apiAvgDurationByDay,
        array $recentErrors,
        ?string $error = null
    ) {
        $this->period = $period;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->alerts = $alerts;
        $this->keyMetrics = $keyMetrics;
        $this->topTemplates = $topTemplates;
        $this->templatesByCategory = $templatesByCategory;
        $this->templatesByLanguage = $templatesByLanguage;
        $this->apiErrorsByType = $apiErrorsByType;
        $this->messagesByDay = $messagesByDay;
        $this->apiPerformanceByDay = $apiPerformanceByDay;
        $this->apiAvgDurationByDay = $apiAvgDurationByDay;
        $this->recentErrors = $recentErrors;
        $this->error = $error;
    }
    
    public function getPeriod(): string
    {
        return $this->period;
    }
    
    public function getStartDate(): string
    {
        return $this->startDate;
    }
    
    public function getEndDate(): string
    {
        return $this->endDate;
    }
    
    public function getAlerts(): array
    {
        return $this->alerts;
    }
    
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
    
    public function getTopTemplates(): array
    {
        return $this->topTemplates;
    }
    
    public function getTemplatesByCategory(): array
    {
        return $this->templatesByCategory;
    }
    
    public function getTemplatesByLanguage(): array
    {
        return $this->templatesByLanguage;
    }
    
    public function getApiErrorsByType(): array
    {
        return $this->apiErrorsByType;
    }
    
    public function getMessagesByDay(): array
    {
        return $this->messagesByDay;
    }
    
    public function getApiPerformanceByDay(): array
    {
        return $this->apiPerformanceByDay;
    }
    
    public function getApiAvgDurationByDay(): array
    {
        return $this->apiAvgDurationByDay;
    }
    
    public function getRecentErrors(): array
    {
        return $this->recentErrors;
    }
    
    public function getError(): ?string
    {
        return $this->error;
    }
}

/**
 * Type pour les métriques clés du dashboard
 */
class WhatsAppKeyMetrics
{
    private $messageSuccessRate;
    private $apiSuccessRate;
    private $totalMessages;
    private $totalTemplatesUsed;
    private $avgApiDuration;
    private $p95ApiDuration;
    private $criticalErrors;
    private $templateCount;
    
    public function __construct(
        float $messageSuccessRate,
        float $apiSuccessRate,
        int $totalMessages,
        int $totalTemplatesUsed,
        float $avgApiDuration,
        float $p95ApiDuration,
        int $criticalErrors,
        int $templateCount
    ) {
        $this->messageSuccessRate = $messageSuccessRate;
        $this->apiSuccessRate = $apiSuccessRate;
        $this->totalMessages = $totalMessages;
        $this->totalTemplatesUsed = $totalTemplatesUsed;
        $this->avgApiDuration = $avgApiDuration;
        $this->p95ApiDuration = $p95ApiDuration;
        $this->criticalErrors = $criticalErrors;
        $this->templateCount = $templateCount;
    }
    
    public function getMessageSuccessRate(): float
    {
        return $this->messageSuccessRate;
    }
    
    public function getApiSuccessRate(): float
    {
        return $this->apiSuccessRate;
    }
    
    public function getTotalMessages(): int
    {
        return $this->totalMessages;
    }
    
    public function getTotalTemplatesUsed(): int
    {
        return $this->totalTemplatesUsed;
    }
    
    public function getAvgApiDuration(): float
    {
        return $this->avgApiDuration;
    }
    
    public function getP95ApiDuration(): float
    {
        return $this->p95ApiDuration;
    }
    
    public function getCriticalErrors(): int
    {
        return $this->criticalErrors;
    }
    
    public function getTemplateCount(): int
    {
        return $this->templateCount;
    }
}

/**
 * Type pour une alerte
 */
class WhatsAppAlert
{
    private $type;
    private $level;
    private $message;
    private $details;
    
    public function __construct(
        string $type,
        string $level,
        string $message,
        array $details
    ) {
        $this->type = $type;
        $this->level = $level;
        $this->message = $message;
        $this->details = $details;
    }
    
    public function getType(): string
    {
        return $this->type;
    }
    
    public function getLevel(): string
    {
        return $this->level;
    }
    
    public function getMessage(): string
    {
        return $this->message;
    }
    
    public function getDetails(): array
    {
        return $this->details;
    }
}