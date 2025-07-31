<?php

namespace Tests\Utils;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

/**
 * Assertions spécifiques pour les tests WhatsApp
 */
trait WhatsAppAssertions
{
    /**
     * Vérifie qu'une métrique de performance API est correcte
     * 
     * @param array $metric
     * @param string $operation
     * @param int $count
     * @param float $avgDuration
     * @param int $successCount
     * @param int $failedCount
     * @param float $successRate
     */
    public function assertApiPerformanceMetric(
        array $metric,
        string $operation,
        int $count,
        float $avgDuration,
        int $successCount,
        int $failedCount,
        float $successRate
    ): void {
        Assert::assertEquals($operation, $metric['operation']);
        Assert::assertEquals($count, $metric['count']);
        Assert::assertEquals($successCount, $metric['successful']);
        Assert::assertEquals($failedCount, $metric['failed']);
        Assert::assertEquals($successRate, $metric['success_rate']);
        Assert::assertEqualsWithDelta($avgDuration, $metric['avg_duration'], 0.1);
    }
    
    /**
     * Vérifie qu'une métrique d'erreur API est correcte
     * 
     * @param array $metric
     * @param string $type
     * @param int $count
     * @param array $operations
     */
    public function assertApiErrorMetric(
        array $metric,
        string $type,
        int $count,
        array $operations
    ): void {
        Assert::assertEquals($type, $metric['type']);
        Assert::assertEquals($count, $metric['count']);
        
        foreach ($operations as $operation) {
            Assert::assertContains($operation, $metric['operations']);
        }
    }
    
    /**
     * Vérifie qu'une métrique d'utilisation de template est correcte
     * 
     * @param array $metric
     * @param string $templateId
     * @param string $templateName
     * @param int $count
     * @param float $successRate
     * @param int $successCount
     * @param int $failedCount
     */
    public function assertTemplateUsageMetric(
        array $metric,
        string $templateId,
        string $templateName,
        int $count,
        float $successRate,
        int $successCount,
        int $failedCount
    ): void {
        Assert::assertEquals($templateId, $metric['template_id']);
        Assert::assertEquals($templateName, $metric['template_name']);
        Assert::assertEquals($count, $metric['count']);
        Assert::assertEquals($successCount, $metric['successful']);
        Assert::assertEquals($failedCount, $metric['failed']);
        Assert::assertEquals($successRate, $metric['success_rate']);
    }
    
    /**
     * Vérifie qu'une alerte est correcte
     * 
     * @param array $alert
     * @param string $type
     * @param string $level
     * @param string $message
     */
    public function assertAlert(
        array $alert,
        string $type,
        string $level,
        string $message
    ): void {
        Assert::assertEquals($type, $alert['type']);
        Assert::assertEquals($level, $alert['level']);
        Assert::assertStringContainsString($message, $alert['message']);
    }
    
    /**
     * Vérifie qu'un tableau de métriques contient une métrique spécifique
     * 
     * @param array $metrics
     * @param string $field
     * @param string $value
     */
    public function assertMetricsContains(array $metrics, string $field, string $value): void
    {
        $found = false;
        
        foreach ($metrics as $metric) {
            if (isset($metric[$field]) && $metric[$field] === $value) {
                $found = true;
                break;
            }
        }
        
        Assert::assertTrue($found, "Métrique avec $field=$value non trouvée");
    }
}