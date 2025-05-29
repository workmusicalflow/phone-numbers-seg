<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Commands;

use App\Entities\WhatsApp\WhatsAppMessageHistory;

/**
 * Résultat de l'exécution d'une commande d'envoi en masse
 */
class BulkSendResult extends CommandResult
{
    /**
     * @param array<string, WhatsAppMessageHistory> $successfulSends Messages envoyés avec succès (clé = numéro)
     * @param array<string, array{error: string, code?: string}> $failedSends Échecs d'envoi (clé = numéro)
     * @param array<string, mixed> $metadata Métadonnées supplémentaires
     */
    public function __construct(
        private readonly array $successfulSends = [],
        private readonly array $failedSends = [],
        array $metadata = []
    ) {
        $success = empty($failedSends) || count($successfulSends) > 0;
        $message = $this->generateSummaryMessage();
        
        parent::__construct($success, $message, $metadata);
    }

    /**
     * @return array<string, WhatsAppMessageHistory>
     */
    public function getSuccessfulSends(): array
    {
        return $this->successfulSends;
    }

    /**
     * @return array<string, array{error: string, code?: string}>
     */
    public function getFailedSends(): array
    {
        return $this->failedSends;
    }

    public function getTotalSent(): int
    {
        return count($this->successfulSends);
    }

    public function getTotalFailed(): int
    {
        return count($this->failedSends);
    }

    public function getTotalAttempted(): int
    {
        return $this->getTotalSent() + $this->getTotalFailed();
    }

    public function getSuccessRate(): float
    {
        if ($this->getTotalAttempted() === 0) {
            return 0.0;
        }
        
        return ($this->getTotalSent() / $this->getTotalAttempted()) * 100;
    }

    /**
     * Obtient le message pour un destinataire spécifique
     */
    public function getMessageForRecipient(string $recipient): ?WhatsAppMessageHistory
    {
        return $this->successfulSends[$recipient] ?? null;
    }

    /**
     * Obtient l'erreur pour un destinataire spécifique
     * 
     * @return array{error: string, code?: string}|null
     */
    public function getErrorForRecipient(string $recipient): ?array
    {
        return $this->failedSends[$recipient] ?? null;
    }

    /**
     * Vérifie si un destinataire spécifique a réussi
     */
    public function isRecipientSuccessful(string $recipient): bool
    {
        return isset($this->successfulSends[$recipient]);
    }

    /**
     * Obtient un résumé détaillé par type d'erreur
     * 
     * @return array<string, int>
     */
    public function getErrorSummary(): array
    {
        $summary = [];
        
        foreach ($this->failedSends as $failure) {
            $code = $failure['code'] ?? 'UNKNOWN';
            $summary[$code] = ($summary[$code] ?? 0) + 1;
        }
        
        return $summary;
    }

    private function generateSummaryMessage(): string
    {
        $total = $this->getTotalAttempted();
        $sent = $this->getTotalSent();
        $failed = $this->getTotalFailed();
        
        if ($total === 0) {
            return 'Aucun message à envoyer';
        }
        
        if ($failed === 0) {
            return sprintf('Tous les %d messages ont été envoyés avec succès', $sent);
        }
        
        if ($sent === 0) {
            return sprintf('Échec de l\'envoi des %d messages', $failed);
        }
        
        return sprintf(
            '%d messages envoyés sur %d (%.1f%% de réussite)',
            $sent,
            $total,
            $this->getSuccessRate()
        );
    }
}