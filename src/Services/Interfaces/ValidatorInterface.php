<?php

namespace App\Services\Interfaces;

use App\Exceptions\ValidationException;

/**
 * Interface pour les validateurs
 */
interface ValidatorInterface
{
    /**
     * Valide les données pour la création d'une entité
     * 
     * @param array $data Données à valider
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateCreate(array $data): array;

    /**
     * Valide les données pour la mise à jour d'une entité
     * 
     * @param int $id ID de l'entité à mettre à jour
     * @param array $data Données à valider
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateUpdate(int $id, array $data): array;

    /**
     * Valide les données pour la suppression d'une entité
     * 
     * @param int $id ID de l'entité à supprimer
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateDelete(int $id): array;
}
