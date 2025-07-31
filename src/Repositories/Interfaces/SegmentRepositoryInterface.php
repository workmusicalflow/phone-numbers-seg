<?php

namespace App\Repositories\Interfaces;

use App\Entities\Segment;

/**
 * Interface pour le repository Segment
 */
interface SegmentRepositoryInterface extends DoctrineRepositoryInterface
{
    /**
     * Trouve les segments par ID de numéro de téléphone
     *
     * @param int $phoneNumberId ID du numéro de téléphone
     * @return array
     */
    public function findByPhoneNumberId(int $phoneNumberId): array;

    /**
     * Supprime tous les segments pour un numéro de téléphone
     * 
     * @param int $phoneNumberId ID du numéro de téléphone
     * @return bool
     */
    public function deleteByPhoneNumberId(int $phoneNumberId): bool;

    /**
     * Crée un nouveau segment
     * 
     * @param string $segmentType Type de segment
     * @param string $value Valeur du segment
     * @param int $phoneNumberId ID du numéro de téléphone
     * @return Segment
     */
    public function create(string $segmentType, string $value, int $phoneNumberId): Segment;
}
