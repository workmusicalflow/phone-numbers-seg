<?php

declare(strict_types=1);

namespace App\Services\Interfaces\WhatsApp;

use App\Entities\User;

/**
 * Interface pour le service de monitoring WhatsApp
 */
interface WhatsAppMonitoringServiceInterface
{
    /**
     * Récupère les métriques d'utilisation des templates WhatsApp
     * 
     * @param User $user Utilisateur pour lequel récupérer les métriques
     * @param \DateTime|null $startDate Date de début pour la période (null = sans limite)
     * @param \DateTime|null $endDate Date de fin pour la période (null = aujourd'hui)
     * @return array Métriques d'utilisation des templates
     */
    public function getTemplateUsageMetrics(
        User $user,
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null
    ): array;
    
    /**
     * Récupère les métriques de performance de l'API WhatsApp
     * 
     * @param User $user Utilisateur pour lequel récupérer les métriques
     * @param \DateTime|null $startDate Date de début pour la période (null = sans limite)
     * @param \DateTime|null $endDate Date de fin pour la période (null = aujourd'hui)
     * @return array Métriques de performance
     */
    public function getApiPerformanceMetrics(
        User $user,
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null
    ): array;
    
    /**
     * Récupère les métriques d'erreur de l'API WhatsApp
     * 
     * @param User $user Utilisateur pour lequel récupérer les métriques
     * @param \DateTime|null $startDate Date de début pour la période (null = sans limite)
     * @param \DateTime|null $endDate Date de fin pour la période (null = aujourd'hui)
     * @return array Métriques d'erreur
     */
    public function getApiErrorMetrics(
        User $user,
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null
    ): array;
    
    /**
     * Enregistre une métrique de performance pour l'API WhatsApp
     * 
     * @param User $user Utilisateur concerné
     * @param string $operation Type d'opération (getTemplates, sendMessage, etc.)
     * @param float $duration Durée de l'opération en ms
     * @param bool $success Indique si l'opération a réussi
     * @param string|null $errorMessage Message d'erreur éventuel
     * @return void
     */
    public function recordApiPerformance(
        User $user,
        string $operation,
        float $duration,
        bool $success,
        ?string $errorMessage = null
    ): void;
    
    /**
     * Obtient le dashboard de monitoring WhatsApp
     * 
     * @param User $user Utilisateur pour lequel récupérer le dashboard
     * @param string $period Période (day, week, month, year)
     * @return array Données du dashboard
     */
    public function getDashboard(User $user, string $period = 'week'): array;
    
    /**
     * Obtient les alertes actives pour le monitoring WhatsApp
     * 
     * @param User $user Utilisateur pour lequel récupérer les alertes
     * @return array Liste des alertes actives
     */
    public function getActiveAlerts(User $user): array;
}