<?php

namespace App\Repositories\Interfaces;

use App\Entities\CustomSegment;

/**
 * Interface pour le repository CustomSegment
 */
interface CustomSegmentRepositoryInterface extends DoctrineRepositoryInterface
{
    /**
     * Trouve un segment personnalisé par son nom
     * 
     * @param string $name Nom du segment
     * @return CustomSegment|null
     */
    public function findByName(string $name): ?CustomSegment;

    /**
     * Trouve les numéros de téléphone associés à un segment personnalisé
     * 
     * @param int $segmentId ID du segment
     * @return array
     */
    public function findPhoneNumbersBySegmentId(int $segmentId): array;

    /**
     * Trouve les segments personnalisés associés à un numéro de téléphone
     * 
     * @param int $phoneNumberId ID du numéro de téléphone
     * @return array
     */
    public function findByPhoneNumberId(int $phoneNumberId): array;

    /**
     * Associe un numéro de téléphone à un segment personnalisé
     * 
     * @param int $phoneNumberId ID du numéro de téléphone
     * @param int $segmentId ID du segment
     * @return bool
     */
    public function addPhoneNumberToSegment(int $phoneNumberId, int $segmentId): bool;

    /**
     * Retire un numéro de téléphone d'un segment personnalisé
     * 
     * @param int $phoneNumberId ID du numéro de téléphone
     * @param int $segmentId ID du segment
     * @return bool
     */
    public function removePhoneNumberFromSegment(int $phoneNumberId, int $segmentId): bool;

    /**
     * Crée un nouveau segment personnalisé
     * 
     * @param string $name Nom du segment
     * @param string|null $description Description du segment
     * @param string|null $pattern Motif regex pour la segmentation automatique
     * @return CustomSegment
     */
    public function create(string $name, ?string $description = null, ?string $pattern = null): CustomSegment;
}
