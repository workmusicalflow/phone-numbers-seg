<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces\WhatsApp;

use App\Entities\WhatsApp\WhatsAppApiMetric;

/**
 * Interface pour le repository des métriques API WhatsApp
 */
interface WhatsAppApiMetricRepositoryInterface
{
    /**
     * Sauvegarde une métrique API
     * 
     * @param WhatsAppApiMetric $metric La métrique à sauvegarder
     * @return WhatsAppApiMetric La métrique sauvegardée
     */
    public function save(WhatsAppApiMetric $metric): WhatsAppApiMetric;
    
    /**
     * Recherche des métriques API selon des critères
     * 
     * @param array $criteria Les critères de recherche
     * @param array|null $orderBy Ordre de tri
     * @param int|null $limit Limite de résultats
     * @param int|null $offset Décalage des résultats
     * @return array Les métriques trouvées
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;
    
    /**
     * Compte le nombre de métriques selon des critères
     * 
     * @param array $criteria Les critères de comptage
     * @return int Le nombre de métriques
     */
    public function count(array $criteria): int;
    
    /**
     * Calcule la durée moyenne des opérations selon des critères
     * 
     * @param array $criteria Les critères de recherche
     * @return float La durée moyenne
     */
    public function getAverageDuration(array $criteria): float;
    
    /**
     * Calcule le percentile 95 de la durée des opérations selon des critères
     * 
     * @param array $criteria Les critères de recherche
     * @return float Le percentile 95 de la durée
     */
    public function getP95Duration(array $criteria): float;
    
    /**
     * Obtient les métriques de performance agrégées par jour
     * 
     * @param int $userId ID de l'utilisateur
     * @param \DateTime $startDate Date de début
     * @param \DateTime|null $endDate Date de fin
     * @return array Les métriques agrégées par jour
     */
    public function getMetricsByDay(int $userId, \DateTime $startDate, ?\DateTime $endDate = null): array;
    
    /**
     * Obtient les métriques de performance agrégées par opération
     * 
     * @param int $userId ID de l'utilisateur
     * @param \DateTime $startDate Date de début
     * @param \DateTime|null $endDate Date de fin
     * @return array Les métriques agrégées par opération
     */
    public function getMetricsByOperation(int $userId, \DateTime $startDate, ?\DateTime $endDate = null): array;
}